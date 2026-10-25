"""Train a model whose input schema matches the MikroTik NetFlow collector.

The CICIDS CSV files are suitable for bootstrapping the pipeline. A production
release must additionally be validated with labeled captures from the target
MikroTik network because CICFlowMeter and NetFlow do not produce identical data.
"""

from __future__ import annotations

import argparse
from contextlib import contextmanager
import json
import os
import re
import sys
import threading
import time
import unicodedata
from pathlib import Path

# joblib cannot query physical cores on some Windows installations. Logical
# CPU count is sufficient and avoids a noisy, harmless warning.
os.environ["LOKY_MAX_CPU_COUNT"] = str(os.cpu_count() or 1)

import joblib
import numpy as np
import pandas as pd
from sklearn.base import clone
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import HistGradientBoostingClassifier, RandomForestClassifier
from sklearn.impute import SimpleImputer
from sklearn.metrics import classification_report, confusion_matrix, precision_recall_curve
from sklearn.model_selection import train_test_split
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler

TRAINING_DIR = Path(__file__).resolve().parent
SETUP_DIR = TRAINING_DIR.parent

from training_schema import NETFLOW_FEATURES, SCHEMA_VERSION

RANDOM_STATE = 42
BENIGN = "BENIGN"


@contextmanager
def activity_bar(label: str):
    """Show an indeterminate progress bar while a blocking ML operation runs."""
    stop_event = threading.Event()
    started = time.monotonic()

    def render() -> None:
        width = 24
        position = 0
        direction = 1
        while not stop_event.is_set():
            cells = ["-"] * width
            cells[position] = "#"
            elapsed = time.monotonic() - started
            sys.stdout.write(
                f"\r{label:<32} [{''.join(cells)}] elapsed {elapsed:8.1f}s"
            )
            sys.stdout.flush()
            position += direction
            if position >= width - 1 or position <= 0:
                direction *= -1
            stop_event.wait(0.5)

    worker = threading.Thread(target=render, daemon=True)
    worker.start()
    try:
        yield
    finally:
        stop_event.set()
        worker.join(timeout=2)
        elapsed = time.monotonic() - started
        sys.stdout.write("\r" + " " * 78 + "\r")
        print(f"{label}: completed in {elapsed:.1f}s", flush=True)


def normalize_name(value: str) -> str:
    value = unicodedata.normalize("NFKC", str(value)).strip()
    value = re.sub(r"[^0-9A-Za-z]+", "_", value)
    return re.sub(r"_+", "_", value).strip("_").lower()


def normalize_label(value: object) -> str:
    value = unicodedata.normalize("NFKC", str(value))
    value = value.replace("–", "-").replace("—", "-").replace("�", "-")
    return re.sub(r"\s+", " ", value).strip().upper()


def read_csv_sample(csv_path: Path, sample_size: int | None, seed: int) -> pd.DataFrame:
    if sample_size is None:
        return pd.read_csv(csv_path, low_memory=False)

    # Keep the rows with the smallest random keys. This is a deterministic,
    # bounded-memory uniform sample over the complete time-ordered capture.
    generator = np.random.default_rng(seed)
    sample = pd.DataFrame()
    for chunk in pd.read_csv(csv_path, chunksize=50_000, low_memory=False):
        chunk["__sample_key"] = generator.random(len(chunk))
        sample = pd.concat([sample, chunk], ignore_index=True)
        if len(sample) > sample_size:
            sample = sample.nsmallest(sample_size, "__sample_key")
    return sample.drop(columns="__sample_key").reset_index(drop=True)


def load_dataset(dataset_dir: Path, max_rows_per_file: int | None) -> pd.DataFrame:
    frames: list[pd.DataFrame] = []
    csv_files = sorted(dataset_dir.glob("*.csv"))
    if not csv_files:
        raise FileNotFoundError(f"No CSV files found in {dataset_dir}")

    for file_index, csv_path in enumerate(csv_files):
        frame = read_csv_sample(
            csv_path,
            sample_size=max_rows_per_file,
            seed=RANDOM_STATE + file_index,
        )
        frame.columns = [normalize_name(column) for column in frame.columns]
        label_column = next((column for column in frame.columns if "label" in column), None)
        if label_column is None:
            raise ValueError(f"No label column found in {csv_path.name}")

        missing = [feature for feature in NETFLOW_FEATURES if feature not in frame.columns]
        if missing:
            raise ValueError(f"{csv_path.name} is missing required features: {missing}")

        selected = frame[NETFLOW_FEATURES].copy()
        selected["label_attack"] = frame[label_column].map(normalize_label)
        selected["capture_id"] = csv_path.name
        frames.append(selected)
        print(f"Loaded {len(selected):,} rows from {csv_path.name}", flush=True)

    data = pd.concat(frames, ignore_index=True)
    for feature in NETFLOW_FEATURES:
        data[feature] = pd.to_numeric(data[feature], errors="coerce")
    data[NETFLOW_FEATURES] = data[NETFLOW_FEATURES].replace([np.inf, -np.inf], np.nan)
    data["label_binary"] = (data["label_attack"] != BENIGN).astype(np.int8)
    return data


def select_threshold(y_true: pd.Series, probabilities: np.ndarray, target_recall: float) -> float:
    precision, recall, thresholds = precision_recall_curve(y_true, probabilities)
    candidates = np.flatnonzero(recall[:-1] >= target_recall)
    if not len(candidates):
        return 0.5
    best = candidates[np.argmax(precision[:-1][candidates])]
    return float(thresholds[best])


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("--dataset", type=Path, default=TRAINING_DIR / "Dataset")
    parser.add_argument(
        "--output",
        type=Path,
        default=None,
    )
    parser.add_argument("--target-recall", type=float, default=0.95)
    parser.add_argument("--max-rows-per-file", type=int, default=None)
    args = parser.parse_args()

    if args.output is None:
        output_name = (
            "netflow_ids.smoke.joblib"
            if args.max_rows_per_file is not None
            else "netflow_ids.joblib"
        )
        args.output = SETUP_DIR / "data" / "model" / output_name

    if not 0 < args.target_recall <= 1:
        parser.error("--target-recall must be in (0, 1]")

    print("[1/7] Loading and normalizing datasets", flush=True)
    data = load_dataset(args.dataset, args.max_rows_per_file)
    if data["label_binary"].nunique() < 2:
        raise ValueError(
            "The selected rows contain only one binary class. Use the full dataset "
            "or choose a sample containing both BENIGN and attack rows."
        )
    if data.loc[data["label_binary"] == 1, "label_attack"].nunique() < 2:
        raise ValueError("Stage B requires at least two attack families")
    print(f"Loaded {len(data):,} total rows", flush=True)
    print("[2/7] Creating train, validation, and test partitions", flush=True)
    X = data[NETFLOW_FEATURES]
    y_binary = data["label_binary"]
    y_family = data["label_attack"]

    # Train/validation/test are separate. Final metrics are computed only after
    # reloading the saved artifact.
    X_train, X_other, y_train, y_other, family_train, family_other = train_test_split(
        X,
        y_binary,
        y_family,
        test_size=0.30,
        stratify=y_binary,
        random_state=RANDOM_STATE,
    )
    X_validation, X_test, y_validation, y_test, _, family_test = train_test_split(
        X_other,
        y_other,
        family_other,
        test_size=0.50,
        stratify=y_other,
        random_state=RANDOM_STATE,
    )

    preprocessor = ColumnTransformer(
        [
            (
                "numeric",
                Pipeline(
                    [
                        ("imputer", SimpleImputer(strategy="median")),
                        ("scale", StandardScaler()),
                    ]
                ),
                NETFLOW_FEATURES,
            )
        ],
        remainder="drop",
    )

    # clone() is deliberate: the two fitted pipelines must never share a scaler.
    binary_model = Pipeline(
        [
            ("pre", clone(preprocessor)),
            (
                "clf",
                HistGradientBoostingClassifier(
                    max_iter=200,
                    learning_rate=0.08,
                    max_leaf_nodes=31,
                    class_weight="balanced",
                    random_state=RANDOM_STATE,
                ),
            ),
        ]
    )
    family_model = Pipeline(
        [
            ("pre", clone(preprocessor)),
            (
                "clf",
                RandomForestClassifier(
                    n_estimators=200,
                    max_depth=24,
                    class_weight="balanced_subsample",
                    n_jobs=-1,
                    random_state=RANDOM_STATE,
                ),
            ),
        ]
    )

    started = time.time()
    print("[3/7] Training Stage A: benign vs attack", flush=True)
    with activity_bar("Stage A binary model"):
        binary_model.fit(X_train, y_train)
    attack_mask = y_train.to_numpy() == 1
    print("[4/7] Training Stage B: attack family", flush=True)
    with activity_bar("Stage B family model"):
        family_model.fit(X_train.iloc[attack_mask], family_train.iloc[attack_mask])

    print("[5/7] Selecting threshold on validation data", flush=True)
    with activity_bar("Threshold calibration"):
        validation_probability = binary_model.predict_proba(X_validation)[:, 1]
        threshold = select_threshold(
            y_validation, validation_probability, args.target_recall
        )

    bundle = {
        "bin": binary_model,
        "mul": family_model,
        "features": list(NETFLOW_FEATURES),
        "best_threshold": threshold,
        "schema_version": SCHEMA_VERSION,
        "dataset_profile": "cicids-bootstrap",
        "trained_at": time.strftime("%Y-%m-%d %H:%M:%S"),
        "config": {
            "run_type": "smoke" if args.max_rows_per_file is not None else "full",
            "max_rows_per_file": args.max_rows_per_file,
            "random_state": RANDOM_STATE,
            "target_recall": args.target_recall,
            "rows": len(data),
            "training_seconds": time.time() - started,
        },
    }
    print("[6/7] Saving and reloading the trained bundle", flush=True)
    args.output.parent.mkdir(parents=True, exist_ok=True)
    with activity_bar("Save/reload model bundle"):
        joblib.dump(bundle, args.output)

        # Reload before reporting. This catches serialization/shared-state bugs.
        restored = joblib.load(args.output)
    if restored["bin"].named_steps["pre"] is restored["mul"].named_steps["pre"]:
        raise RuntimeError("Stage A and Stage B unexpectedly share preprocessing state")

    print("[7/7] Evaluating untouched test data", flush=True)
    with activity_bar("Final model evaluation"):
        test_probability = restored["bin"].predict_proba(X_test)[:, 1]
        test_prediction = (test_probability >= restored["best_threshold"]).astype(
            np.int8
        )

    # Reproducible balanced validation: 100 benign and 100 attack rows sampled
    # only from the untouched test partition.
    generator = np.random.default_rng(RANDOM_STATE)
    y_test_array = y_test.to_numpy()
    benign_positions = np.flatnonzero(y_test_array == 0)
    attack_positions = np.flatnonzero(y_test_array == 1)
    samples_per_class = min(100, len(benign_positions), len(attack_positions))
    if samples_per_class < 1:
        raise ValueError("The test partition must contain both benign and attack rows")
    balanced_validation_complete = samples_per_class == 100
    if not balanced_validation_complete:
        print(
            "SMOKE TEST: only "
            f"{samples_per_class} row(s) per class are available in the test partition; "
            "run full training for the required 200-sample validation."
        )
    balanced_positions = np.concatenate(
        [
            generator.choice(benign_positions, size=samples_per_class, replace=False),
            generator.choice(attack_positions, size=samples_per_class, replace=False),
        ]
    )
    generator.shuffle(balanced_positions)
    balanced_truth = y_test_array[balanced_positions]
    balanced_probability = test_probability[balanced_positions]
    balanced_prediction = (
        balanced_probability >= restored["best_threshold"]
    ).astype(np.int8)

    selected_attack_positions = balanced_positions[balanced_truth == 1]
    family_truth = family_test.iloc[selected_attack_positions]
    family_prediction = restored["mul"].predict(
        X_test.iloc[selected_attack_positions]
    )

    metrics = {
        "run_type": "smoke" if args.max_rows_per_file is not None else "full",
        "schema_version": SCHEMA_VERSION,
        "threshold": restored["best_threshold"],
        "confusion_matrix": confusion_matrix(y_test, test_prediction).tolist(),
        "classification_report": classification_report(
            y_test, test_prediction, output_dict=True, zero_division=0
        ),
        "balanced_validation": {
            "seed": RANDOM_STATE,
            "requested_samples": 200,
            "complete_200_sample_validation": balanced_validation_complete,
            "samples": samples_per_class * 2,
            "benign_samples": samples_per_class,
            "attack_samples": samples_per_class,
            "confusion_matrix": confusion_matrix(
                balanced_truth, balanced_prediction, labels=[0, 1]
            ).tolist(),
            "classification_report": classification_report(
                balanced_truth,
                balanced_prediction,
                labels=[0, 1],
                output_dict=True,
                zero_division=0,
            ),
            "attack_family_report": classification_report(
                family_truth,
                family_prediction,
                output_dict=True,
                zero_division=0,
            ),
            "probability_summary": {
                "benign_min": float(balanced_probability[balanced_truth == 0].min()),
                "benign_max": float(balanced_probability[balanced_truth == 0].max()),
                "attack_min": float(balanced_probability[balanced_truth == 1].min()),
                "attack_max": float(balanced_probability[balanced_truth == 1].max()),
            },
        },
        "warning": (
            "CICIDS bootstrap metrics are not production validation. Test with "
            "labeled MikroTik NetFlow captures before deployment."
        ),
    }
    metrics_path = args.output.with_suffix(".metrics.json")
    metrics_path.write_text(json.dumps(metrics, indent=2), encoding="utf-8")
    print(json.dumps(metrics, indent=2))
    print(f"Saved model: {args.output}")
    print(f"Saved metrics: {metrics_path}")


if __name__ == "__main__":
    main()
