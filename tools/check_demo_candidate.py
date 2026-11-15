"""Predict every current modal fixture locally; no API calls or database writes."""
import csv
import hashlib
import json
import os
import re
from collections import Counter
from pathlib import Path

os.environ.setdefault('LOKY_MAX_CPU_COUNT', str(os.cpu_count() or 1))
import joblib
import numpy as np
import pandas as pd

ROOT = Path(__file__).resolve().parents[1]


def normalize(value):
    return re.sub(r'[^A-Z0-9]+', '', str(value).upper())


def main():
    directory = max((ROOT / 'ids_ensemble-api/model/candidates').glob('stage_b_experiments_*'), key=lambda p: p.stat().st_mtime)
    comparison = json.loads((directory / 'comparison.json').read_text())
    metadata = next(row for row in comparison if row['name'] == 'cicids78_hist_gradient_boosting')
    model_path = directory / Path(metadata['model_path']).name
    checksum = hashlib.sha256(model_path.read_bytes()).hexdigest()
    if checksum != metadata['model_sha256']:
        raise RuntimeError('Candidate checksum does not match its validation report.')
    model = joblib.load(model_path)
    fixtures_path = ROOT / 'resources/data/demo-attacks.json'
    fixtures = json.loads(fixtures_path.read_text())
    items = [(attack, index + 1, features) for attack, rows in fixtures.items() for index, features in enumerate(rows)]
    for attack, index, features in items:
        missing = set(model['features']) - set(features)
        if missing:
            raise ValueError(f'{attack} sample {index} missing {missing}')
    frame = pd.DataFrame([features for _, _, features in items], columns=model['features'])
    if not np.isfinite(frame.to_numpy(dtype=float)).all():
        raise ValueError('Fixtures include invalid numeric values.')
    attack_column = list(model['bin'].classes_).index(1)
    probability = model['bin'].predict_proba(frame)[:, attack_column]
    detected = probability >= model['best_threshold']
    predicted = np.full(len(items), 'BENIGN', dtype=object)
    if detected.any():
        predicted[detected] = model['mul'].predict(frame.iloc[np.flatnonzero(detected)])
    details = []
    for i, (attack, sample, _) in enumerate(items):
        details.append({
            'selected_attack': attack, 'sample': sample,
            'risk_level': 'attack' if detected[i] else 'benign',
            'attack_probability': float(probability[i]),
            'predicted_label': str(predicted[i]),
            'matches_selected_attack': bool(detected[i] and normalize(predicted[i]) == normalize(attack)),
        })
    summary = []
    for attack in fixtures:
        rows = [row for row in details if row['selected_attack'] == attack]
        summary.append({
            'attack': attack, 'samples': len(rows),
            'correct_type': sum(row['matches_selected_attack'] for row in rows),
            'benign': sum(row['risk_level'] == 'benign' for row in rows),
            'different_attack': sum(row['risk_level'] == 'attack' and not row['matches_selected_attack'] for row in rows),
            'predictions': dict(Counter(row['predicted_label'] for row in rows)),
        })
    output = ROOT / 'ids_ensemble-api/reports'
    output.mkdir(exist_ok=True)
    report = {
        'model_path': str(model_path), 'model_sha256': checksum,
        'fixtures_sha256': hashlib.sha256(fixtures_path.read_bytes()).hexdigest(),
        'threshold': float(model['best_threshold']), 'feature_count': len(model['features']),
        'total_samples': len(details), 'total_benign': int((~detected).sum()),
        'total_correct_type': sum(row['matches_selected_attack'] for row in details),
        'caveat': 'Current selected demonstration fixtures only; not independent accuracy or deployed Render predictions.',
        'summary': summary, 'samples': details,
    }
    (output / 'demo_candidate_predictions.json').write_text(json.dumps(report, indent=2), encoding='utf-8')
    with (output / 'demo_candidate_predictions.csv').open('w', newline='', encoding='utf-8') as stream:
        writer = csv.DictWriter(stream, fieldnames=list(details[0]))
        writer.writeheader()
        writer.writerows(details)
    lines = ['# Current demo sample predictions', '',
             f"Candidate: `{metadata['name']}`. Threshold: {model['best_threshold']:.12f}.", '',
             'Local replay of all current modal samples; no API calls or database writes.', '',
             '| Selected attack | Correct type | Benign | Different attack |',
             '| --- | ---: | ---: | ---: |']
    for row in summary:
        lines.append(f"| {row['attack']} | {row['correct_type']}/{row['samples']} | {row['benign']} | {row['different_attack']} |")
    lines.extend(['', '## Every sample', '', '| Selected attack | Sample | Returned prediction | Attack probability |', '| --- | ---: | --- | ---: |'])
    for row in details:
        lines.append(f"| {row['selected_attack']} | {row['sample']} | {row['predicted_label']} | {row['attack_probability']:.6%} |")
    lines.extend(['', report['caveat'], 'Render and the modal sample catalog were not changed.', ''])
    (output / 'demo_candidate_predictions.md').write_text('\n'.join(lines), encoding='utf-8')
    print(json.dumps({key: value for key, value in report.items() if key != 'samples'}, indent=2))
    print('MISMATCHED_SAMPLES', json.dumps([row for row in details if not row['matches_selected_attack']]))


if __name__ == '__main__':
    main()
