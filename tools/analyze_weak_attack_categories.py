"""Read-only diagnosis of saved IDS candidates; never fits or deploys a model.

Reconstructs the notebook's data/split cells, measures raw feature collisions,
and inspects errors from an existing full-training candidate. Output is a local
JSON report. Run using the project's .venv-ids Python interpreter.
"""
import json
from pathlib import Path

import joblib
import numpy as np
import pandas as pd
from sklearn.neighbors import NearestNeighbors
from sklearn.pipeline import make_pipeline
from sklearn.impute import SimpleImputer
from sklearn.preprocessing import FunctionTransformer, StandardScaler

ROOT = Path(__file__).resolve().parents[1]
WEAK = ['BOT', 'WEB ATTACK - BRUTE FORCE', 'WEB ATTACK - SQL INJECTION', 'WEB ATTACK - XSS']


def collision_report(data, signature):
    pairs = data[[signature, 'label']].drop_duplicates()
    cardinality = pairs.groupby(signature)['label'].nunique()
    conflicting = set(cardinality[cardinality > 1].index)
    result = {}
    for label in WEAK:
        own = data[data['label'] == label]
        signatures = set(own[signature]) & conflicting
        other = pairs[pairs[signature].isin(signatures) & (pairs['label'] != label)]
        result[label] = {
            'raw_rows': len(own),
            'distinct_feature_vectors': int(own[signature].nunique()),
            'rows_with_conflicting_labels': int(own[signature].isin(conflicting).sum()),
            'conflicting_vector_count': len(signatures),
            'other_labels': other['label'].value_counts().to_dict(),
        }
    return result


def main():
    notebook = json.loads((ROOT / 'ids_ensemble-api/Retrain_IDS_Model.ipynb').read_text())
    ns = {'__name__': 'diagnostic'}
    for i in [1, 2]:
        exec(compile(''.join(notebook['cells'][i]['source']), f'notebook_cell_{i}', 'exec'), ns)
    if ns['FEATURE_MODE'] != 'netflow14' or ns['TRAIN_MODE'] != 'full':
        raise RuntimeError('This audit requires the unchanged full netflow14 notebook configuration.')
    ns.update(collision_report=collision_report)
    source = ''.join(notebook['cells'][4]['source'])
    source = source.replace(
        "selected['label'] = chunk['label'].map(normalize_label)",
        "selected['label'] = chunk['label'].map(normalize_label)\n"
        "        selected['_signature78'] = pd.util.hash_pandas_object(\n"
        "            chunk[CICIDS_FEATURES].apply(pd.to_numeric, errors='coerce')\n"
        "                .replace([np.inf, -np.inf], np.nan).astype(float), index=False)",
    )
    source = source.replace(
        'original_rows = len(data)',
        "raw_collisions14 = collision_report(data, '_signature')\n"
        "raw_collisions78 = collision_report(data, '_signature78')\n"
        'original_rows = len(data)',
    )
    exec(compile(source, 'audit_dataset_loader', 'exec'), ns)
    exec(compile(''.join(notebook['cells'][5]['source']), 'notebook_split', 'exec'), ns)
    report_path = max((ROOT / 'ids_ensemble-api/model/candidates').glob('*_full_*.metrics.json'), key=lambda p: p.stat().st_mtime)
    metrics = json.loads(report_path.read_text())
    model_path = report_path.with_suffix('').with_suffix('.joblib')
    import hashlib
    if hashlib.sha256(model_path.read_bytes()).hexdigest() != metrics['model_sha256']:
        raise RuntimeError('Saved candidate does not match its metrics checksum.')
    bundle = joblib.load(model_path)
    if bundle['features'] != ns['FEATURES'] or bundle['config']['seed'] != ns['SEED']:
        raise RuntimeError('Candidate configuration differs from reconstructed split.')
    Xv, fv = ns['X_validation'], ns['family_validation']
    Xt, ft = ns['X_test'], ns['family_test']
    attack_column = list(bundle['bin'].classes_).index(1)
    validation_probability = bundle['bin'].predict_proba(Xv)[:, attack_column]
    test_probability = bundle['bin'].predict_proba(Xt)[:, attack_column]
    # Verify the reconstructed test partition reproduces the saved report.
    threshold = bundle['best_threshold']
    detected = test_probability >= threshold
    predicted = np.full(len(Xt), 'BENIGN', dtype=object)
    predicted[detected] = bundle['mul'].predict(Xt.iloc[np.flatnonzero(detected)])
    from sklearn.metrics import confusion_matrix
    np.testing.assert_array_equal(confusion_matrix(ft, predicted, labels=metrics['confusion_matrix_labels']), metrics['confusion_matrix'])
    errors = {}
    for label in WEAK:
        positions = np.flatnonzero(np.asarray(ft) == label)
        stage_b = bundle['mul'].predict(Xt.iloc[positions])
        errors[label] = {
            'test_support': len(positions),
            'stage_a_rejections': int((~detected[positions]).sum()),
            'stage_b_only_predictions': pd.Series(stage_b).value_counts().to_dict(),
            'end_to_end_predictions': pd.Series(predicted[positions]).value_counts().to_dict(),
        }
    tradeoffs = []
    for value in sorted(set([0.05, 0.10, 0.20, threshold, 0.50, 0.80])):
        row = {'threshold': value, 'benign_false_positive_rate': float((validation_probability[np.asarray(fv) == 'BENIGN'] >= value).mean())}
        for label in WEAK:
            row[label] = float((validation_probability[np.asarray(fv) == label] >= value).mean())
        tradeoffs.append(row)
    # A descriptive neighborhood check, not another classifier or deployment gate.
    # Fit transformations and the reference set using training rows only.
    train_frame = ns['X_train'].copy()
    train_frame['_label'] = np.asarray(ns['family_train'])
    reference = pd.concat([
        group.sample(n=min(len(group), 20_000 if label == 'BENIGN' else 2_000), random_state=42)
        for label, group in train_frame.groupby('_label')
    ])
    transform = make_pipeline(
        SimpleImputer(strategy='median'),
        FunctionTransformer(lambda x: np.sign(x) * np.log1p(np.abs(x))),
        StandardScaler(),
    )
    reference_x = transform.fit_transform(reference[bundle['features']])
    neighbors = NearestNeighbors(n_neighbors=5).fit(reference_x)
    neighborhood = {}
    for label in WEAK:
        positions = np.flatnonzero(np.asarray(ft) == label)
        distances, indices = neighbors.kneighbors(transform.transform(Xt.iloc[positions]))
        labels = np.asarray(reference['_label'])[indices]
        neighborhood[label] = {
            'nearest_training_label_counts': pd.Series(labels[:, 0]).value_counts().to_dict(),
            'same_family_fraction_of_five_neighbors': float((labels == label).mean()),
            'median_nearest_distance': float(np.median(distances[:, 0])),
        }
    report = {
        'candidate': str(model_path), 'checksum': metrics['model_sha256'],
        'reconstructed_test_matches_saved_metrics': True,
        'raw_collisions_netflow14': ns['raw_collisions14'],
        'raw_collisions_cicids78': ns['raw_collisions78'],
        'test_errors': errors, 'validation_threshold_tradeoffs': tradeoffs,
        'training_neighborhoods': neighborhood,
        'neighborhood_caveat': 'Descriptive distances in imputed signed-log standardized features using sampled training references; not proof that classes cannot be learned.',
        'test_caveat': 'Diagnostic inspection of this test set means future tuned candidates need a fresh independent evaluation set.',
    }
    output = ROOT / 'storage/app/weak-attack-analysis.json'
    output.write_text(json.dumps(report, indent=2), encoding='utf-8')
    print(json.dumps(report, indent=2), flush=True)
    print('Saved', output, flush=True)


if __name__ == '__main__':
    main()
