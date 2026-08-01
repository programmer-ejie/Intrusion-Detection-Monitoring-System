import traceback
import sys


def _pause_on_error(exc_type, exc, tb):
    traceback.print_exception(exc_type, exc, tb)
    try:
        input("\nPress Enter to close...")
    except EOFError:
        pass


sys.excepthook = _pause_on_error

import os, json, time, math
from datetime import datetime
import joblib
import pandas as pd
from flask import Flask, request, jsonify
import pymysql
from dotenv import load_dotenv

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
load_dotenv(os.path.join(BASE_DIR, ".env"))

app = Flask(__name__)

MODEL_DIR = os.path.join(BASE_DIR, "data", "model")
BUNDLE_FILENAME = "ids_ensemble.joblib"
BUNDLE_PATH = os.path.join(MODEL_DIR, BUNDLE_FILENAME)

print("Initializing IDS Ensemble API...")
print(f"Loading model bundle from {BUNDLE_PATH} ...")
bundle = joblib.load(BUNDLE_PATH)

model_bin = bundle["bin"]
model_mul = bundle["mul"]

# Normalize expected feature names
feature_names = [f.strip() for f in bundle["features"]]
best_thr = float(bundle["best_threshold"])

print(f"Loaded bundle with {len(feature_names)} features.")
print(f"Stage A threshold: {best_thr:.3f}")
print("First 10 expected features:", feature_names[:10])

LOG_DIR = os.getenv("LOG_DIR", os.path.join(BASE_DIR, "logs"))
LOG_FILE = os.path.join(LOG_DIR, "predictions.jsonl")

DB_HOST = os.getenv("DB_HOST", "127.0.0.1").strip()
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_NAME = os.getenv("DB_DATABASE", "").strip()
DB_USER = os.getenv("DB_USERNAME", "").strip()
DB_PASSWORD = os.getenv("DB_PASSWORD", "").strip()
DB_TABLE = os.getenv("DB_TABLE", "intrusion_logs").strip()

def append_log(record: dict):
    try:
        os.makedirs(LOG_DIR, exist_ok=True)
        with open(LOG_FILE, "a", encoding="utf-8") as f:
            f.write(json.dumps(record, ensure_ascii=False) + "\n")
    except Exception as e:
        print(f"[LOG] Failed to write log: {e}")

def save_to_database(record: dict):
    if not DB_NAME or not DB_USER:
        print("[DB] Missing database credentials; skipping insert.")
        return

    payload = {
        "src_ip": record.get("src_ip"),
        "dst_ip": record.get("dst_ip"),
        "src_port": record.get("src_port"),
        "dst_port": record.get("dst_port"),
        "protocol": record.get("protocol"),
        "flow_duration": record.get("flow_duration"),
        "flow_pkts_s": record.get("flow_pkts_s"),
        "flow_bytes_s": record.get("flow_bytes_s"),
        "tot_fwd_pkts": record.get("tot_fwd_pkts"),
        "tot_bwd_pkts": record.get("tot_bwd_pkts"),
        "tot_fwd_bytes": record.get("tot_fwd_bytes"),
        "tot_bwd_bytes": record.get("tot_bwd_bytes"),
        "fwd_pkt_len_mean": record.get("fwd_pkt_len_mean"),
        "bwd_pkt_len_mean": record.get("bwd_pkt_len_mean"),
        "fwd_iat_mean": record.get("fwd_iat_mean"),
        "bwd_iat_mean": record.get("bwd_iat_mean"),
        "risk_level": record.get("risk_level", "benign"),
        "prob_attack": record.get("prob_attack", 0.0),
        "attack_type": record.get("attack_type"),
        "status": None,
        "created_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
        "updated_at": datetime.now().strftime("%Y-%m-%d %H:%M:%S"),
    }

    try:
        conn = pymysql.connect(
            host=DB_HOST,
            port=DB_PORT,
            user=DB_USER,
            password=DB_PASSWORD,
            database=DB_NAME,
            charset="utf8mb4",
            autocommit=True,
        )
        try:
            with conn.cursor() as cursor:
                columns = ", ".join(payload.keys())
                placeholders = ", ".join(["%s"] * len(payload))
                sql = f"INSERT INTO {DB_TABLE} ({columns}) VALUES ({placeholders})"
                cursor.execute(sql, list(payload.values()))
            print("[DB] log saved ✅")
        finally:
            conn.close()
    except Exception as e:
        print(f"[DB] save error: {e}")

@app.route("/")
def home():
    return "IDS Ensemble API is running."

# ✅ NEW: expose expected feature list (so your sender can match exactly)
@app.route("/api/features", methods=["GET"])
def features():
    return jsonify({
        "count": len(feature_names),
        "features": feature_names
    })

@app.route("/api/analyze", methods=["POST"])
def analyze():
    data = request.get_json(force=True) or {}

    # Normalize incoming keys
    data = {str(k).strip(): v for k, v in data.items()}

    # ✅ NEW: accept sender snake_case keys and map them into CIC-style keys (if model uses those)
    aliases = {
        "flow_duration": "Flow Duration",
        "flow_pkts_s": "Flow Packets/s",
        "flow_bytes_s": "Flow Bytes/s",
        "tot_fwd_pkts": "Total Fwd Packets",
        "tot_bwd_pkts": "Total Backward Packets",
        "tot_fwd_bytes": "Total Length of Fwd Packets",
        "tot_bwd_bytes": "Total Length of Bwd Packets",
        "fwd_pkt_len_mean": "Fwd Packet Length Mean",
        "bwd_pkt_len_mean": "Bwd Packet Length Mean",
        "fwd_iat_mean": "Fwd IAT Mean",
        "bwd_iat_mean": "Bwd IAT Mean",
    }
    for src_key, dst_key in aliases.items():
        if src_key in data and dst_key not in data:
            data[dst_key] = data[src_key]

    # Debug counters
    present = sum(1 for f in feature_names if f in data)
    missing = len(feature_names) - present

    # Optional server-side debug in Render logs (comment out later)
    if missing > 0:
        missing_names = [f for f in feature_names if f not in data]
        print("MISSING SAMPLE:", missing_names[:15])
        present_names = [f for f in feature_names if f in data]
        print("PRESENT SAMPLE:", present_names[:15])

    # Build model input in exact order
    x_list = []
    for fname in feature_names:
        val = data.get(fname, 0.0)
        try:
            val = float(val)
            if math.isnan(val) or math.isinf(val):
                val = 0.0
        except Exception:
            val = 0.0
        x_list.append(val)

    row_df = pd.DataFrame([x_list], columns=feature_names)

    proba_attack = float(model_bin.predict_proba(row_df)[0, 1])
    is_attack = int(proba_attack >= best_thr)

    risk_level = "benign"
    attack_type = None
    if is_attack == 1:
        risk_level = "attack"
        attack_type = str(model_mul.predict(row_df)[0])

    result = {
        "risk_level": risk_level,
        "prob_attack": proba_attack,
        "attack_type": attack_type,
        "used_threshold": best_thr,
    }

    record = {"ts": int(time.time()), **data, **result}
    append_log(record)
    save_to_database(record)

    return jsonify({
        **result,
        "present_expected_features": present,
        "missing_expected_features": missing,
        "received_features_count": len(data.keys()),
        "api_version": "debug-v3"
    })

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)
