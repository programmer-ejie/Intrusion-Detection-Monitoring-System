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

os.environ["LOKY_MAX_CPU_COUNT"] = str(os.cpu_count() or 1)

import joblib
import pandas as pd
from flask import Flask, request, jsonify
import pymysql
from dotenv import load_dotenv

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
load_dotenv(os.path.join(BASE_DIR, ".env"))

app = Flask(__name__)

MODEL_DIR = os.path.join(BASE_DIR, "data", "model")
BUNDLE_FILENAME = os.getenv("IDS_MODEL_FILENAME", "netflow_ids.joblib").strip()
BUNDLE_PATH = os.path.join(MODEL_DIR, BUNDLE_FILENAME)

print("Initializing IDS Ensemble API...")
print(f"Loading model bundle from {BUNDLE_PATH} ...")
bundle = joblib.load(BUNDLE_PATH)

model_bin = bundle["bin"]
model_mul = bundle["mul"]

# Normalize expected feature names
feature_names = [f.strip() for f in bundle["features"]]
best_thr = float(os.getenv("IDS_THRESHOLD", bundle["best_threshold"]))
if not 0.0 <= best_thr <= 1.0:
    raise ValueError("IDS_THRESHOLD must be between 0.0 and 1.0")
schema_version = str(bundle.get("schema_version", "unknown"))

print(f"Loaded bundle with {len(feature_names)} features.")
print(f"Stage A threshold: {best_thr:.3f}")
print(f"Schema version: {schema_version}")
print("First 10 expected features:", feature_names[:10])

LOG_DIR = os.getenv("LOG_DIR", os.path.join(BASE_DIR, "logs"))
LOG_FILE = os.path.join(LOG_DIR, "predictions.jsonl")

DB_HOST = os.getenv("DB_HOST", "127.0.0.1").strip()
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_NAME = os.getenv("DB_DATABASE", "").strip()
DB_USER = os.getenv("DB_USERNAME", "").strip()
DB_PASSWORD = os.getenv("DB_PASSWORD", "").strip()
DB_TABLE = os.getenv("DB_TABLE", "intrusion_logs").strip()
DB_INCIDENT_TABLE = os.getenv("DB_INCIDENT_TABLE", "threat_incidents").strip()
INCIDENT_WINDOW_MINUTES = max(1, int(os.getenv("INCIDENT_WINDOW_MINUTES", "15")))

def assign_incident(cursor, record: dict, now_text: str):
    if record.get("risk_level") != "attack":
        return None

    pppoe_name = (record.get("pppoe_name") or "").strip() or None
    attack_type = (record.get("attack_type") or "unknown").strip() or "unknown"
    incident_id = None

    if pppoe_name:
        cursor.execute(
            f"""
            SELECT id FROM {DB_INCIDENT_TABLE}
            WHERE pppoe_name = %s AND attack_type = %s
              AND (status = 'blocked' OR
                   (status = 'unresolved' AND
                    last_detected_at >= DATE_SUB(%s, INTERVAL %s MINUTE)))
            ORDER BY CASE WHEN status = 'blocked' THEN 0 ELSE 1 END,
                     last_detected_at DESC
            LIMIT 1 FOR UPDATE
            """,
            (pppoe_name, attack_type, now_text, INCIDENT_WINDOW_MINUTES),
        )
        row = cursor.fetchone()
        incident_id = row[0] if row else None

    if incident_id:
        cursor.execute(
            f"""UPDATE {DB_INCIDENT_TABLE}
                SET event_count = event_count + 1,
                    last_detected_at = %s, updated_at = %s
                WHERE id = %s""",
            (now_text, now_text, incident_id),
        )
        return incident_id

    cursor.execute(
        f"""INSERT INTO {DB_INCIDENT_TABLE}
            (pppoe_name, attack_type, status, event_count,
             first_detected_at, last_detected_at, created_at, updated_at)
            VALUES (%s, %s, 'unresolved', 1, %s, %s, %s, %s)""",
        (pppoe_name, attack_type, now_text, now_text, now_text, now_text),
    )
    return cursor.lastrowid

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

    now_text = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    payload = {
        "src_ip": record.get("src_ip"),
        "pppoe_name": record.get("pppoe_name"),
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
        "created_at": now_text,
        "updated_at": now_text,
    }

    try:
        conn = pymysql.connect(
            host=DB_HOST,
            port=DB_PORT,
            user=DB_USER,
            password=DB_PASSWORD,
            database=DB_NAME,
            charset="utf8mb4",
            autocommit=False,
        )
        try:
            with conn.cursor() as cursor:
                payload["incident_id"] = assign_incident(cursor, record, now_text)
                columns = ", ".join(payload.keys())
                placeholders = ", ".join(["%s"] * len(payload))
                sql = f"INSERT INTO {DB_TABLE} ({columns}) VALUES ({placeholders})"
                cursor.execute(sql, list(payload.values()))
            conn.commit()
        except Exception:
            conn.rollback()
            raise
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
        "dst_port": "destination_port",
        "flow_pkts_s": "flow_packets_s",
        "tot_fwd_pkts": "total_fwd_packets",
        "tot_bwd_pkts": "total_backward_packets",
        "tot_fwd_bytes": "total_length_of_fwd_packets",
        "tot_bwd_bytes": "total_length_of_bwd_packets",
        "fwd_pkt_len_mean": "fwd_packet_length_mean",
        "bwd_pkt_len_mean": "bwd_packet_length_mean",
    }
    for src_key, dst_key in aliases.items():
        if src_key in data and dst_key not in data:
            data[dst_key] = data[src_key]

    # Debug counters
    present = sum(1 for f in feature_names if f in data)
    missing = len(feature_names) - present

    if missing:
        missing_names = [f for f in feature_names if f not in data]
        return jsonify({
            "error": "missing_model_features",
            "schema_version": schema_version,
            "missing_expected_features": missing_names,
            "present_expected_features": present,
            "expected_features_count": len(feature_names),
        }), 422

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
        "api_version": "debug-v3",
        "schema_version": schema_version,
    })

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000, debug=True)
