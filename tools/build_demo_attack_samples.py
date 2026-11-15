"""Build CICIDS2017 replay fixtures checked against Stage B; never sends traffic.

The legacy Stage A artifact does not reliably detect these labeled attack rows.
Do not describe these fixtures as verified end-to-end attack predictions.
"""
import csv
import json
import math
import random
import re
from collections import defaultdict
from pathlib import Path

import joblib
import pandas as pd

ROOT = Path(__file__).resolve().parents[1]


def normalize(value):
    return re.sub(r"[^a-z0-9]+", "_", value.strip().lower()).strip("_")


def main():
    bundle = joblib.load(ROOT / "setup/Training/artifacts/ids_ensemble.joblib")
    features = [f.strip() for f in bundle["features"]]
    pools = defaultdict(list)
    seen = defaultdict(int)
    rng = random.Random(42)
    for path in sorted((ROOT / "setup/Training/Dataset").glob("*.csv")):
        print(f"Reading {path.name}", flush=True)
        with path.open(encoding="utf-8-sig", errors="replace", newline="") as stream:
            reader = csv.reader(stream)
            names = []
            duplicates = defaultdict(int)
            for name in next(reader):
                name = normalize(name)
                suffix = duplicates[name]
                duplicates[name] += 1
                names.append(name + (f"_{suffix}" if suffix else ""))
            label_index = names.index("label")
            indices = [names.index(f) for f in features]
            for row in reader:
                label = row[label_index].strip().replace("\ufffd", "-").replace("–", "-")
                if label.upper() == "BENIGN":
                    continue
                seen[label] += 1
                slot = len(pools[label]) if len(pools[label]) < 400 else rng.randrange(seen[label])
                if slot >= 400:
                    continue
                try:
                    values = [float(row[i]) for i in indices]
                except ValueError:
                    continue
                if not all(math.isfinite(v) for v in values):
                    continue
                item = dict(zip(features, values))
                if slot == len(pools[label]):
                    pools[label].append(item)
                else:
                    pools[label][slot] = item
    output = {}
    for label, rows in sorted(pools.items()):
        frame = pd.DataFrame(rows, columns=features)
        probabilities = bundle["bin"].predict_proba(frame)[:, 1]
        predictions = bundle["mul"].predict(frame)
        samples = []
        fingerprints = set()
        for row, predicted in zip(rows, predictions):
            if normalize(str(predicted)) != normalize(label):
                continue
            fingerprint = tuple(row.values())
            if fingerprint in fingerprints:
                continue
            fingerprints.add(fingerprint)
            samples.append(row)
            if len(samples) == 5:
                break
        print(ascii(label), "matching:", len(samples), "max probability:", max(probabilities), "families:", ascii(sorted(set(predictions))), flush=True)
        if len(samples) != 5:
            raise RuntimeError(f"Insufficient distinct samples for {label}")
        output[label] = samples
        print(f"{label}: {len(samples)} family-checked samples", flush=True)
    if len(output) != 14:
        raise RuntimeError(f"Expected 14 attacks, found {len(output)}")
    target = ROOT / "resources/data/demo-attacks.json"
    target.parent.mkdir(parents=True, exist_ok=True)
    target.write_text(json.dumps(output, indent=2, allow_nan=False) + "\n", encoding="utf-8")


if __name__ == "__main__":
    main()
