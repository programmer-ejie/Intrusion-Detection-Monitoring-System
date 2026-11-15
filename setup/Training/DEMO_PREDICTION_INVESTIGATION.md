# Demo prediction investigation

The selected attack is the source label of a replay sample. The application sends
the feature values to Render and displays its actual response. A benign response
does not create an active threat.

## Confirmed local defect

`Training Model.ipynb` creates one `pre` ColumnTransformer, places that same object
in both `model_bin` and `model_mul`, fits Stage A, selects its threshold, and then
fits Stage B. Fitting Stage B refits the shared scaler on attack-only data. The
saved Stage A classifier therefore receives a different transformation from the
one used when it was trained and calibrated.

Loading `artifacts/ids_ensemble.joblib` confirms:

- `bundle['bin'].named_steps['pre'] is bundle['mul'].named_steps['pre']` is True.
- The shared scaler's `n_samples_seen_` is 390352.
- Saved binary classes are `[0, 1]`; the notebook assigns attack to 1. Selecting
  probability column 1 is correct for this artifact.
- There are 78 expected features. Each of the 70 demo rows contains all 78 with
  finite numeric values, so missing demo features do not explain this failure.
- The saved detection threshold is 0.999859849387428 (99.9859849387428%).

## Local replay results

All five demo samples for each category were tested against both existing local
artifacts without changing model files or contacting the prediction POST endpoint.

| Attack category | Legacy Stage A detections / 5 | NetFlow Stage A detections / 5 |
| --- | ---: | ---: |
| Bot | 0 | 0 |
| DDoS | 0 | 5 |
| DoS GoldenEye | 0 | 5 |
| DoS Hulk | 0 | 5 |
| DoS Slowhttptest | 0 | 5 |
| DoS slowloris | 0 | 4 |
| FTP-Patator | 0 | 4 |
| Heartbleed | 0 | 0 |
| Infiltration | 0 | 0 |
| PortScan | 0 | 5 |
| SSH-Patator | 0 | 3 |
| Web Attack - Brute Force | 0 | 0 |
| Web Attack - SQL Injection | 0 | 0 |
| Web Attack - XSS | 0 | 0 |
| Total | 0 / 70 | 36 / 70 |

The legacy Stage B classifier matches all 70 source categories when evaluated
directly, but the API only runs Stage B if Stage A first detects an attack.
The fixture generator checks Stage B only; these fixtures were never verified
as successful end-to-end attack detections.

The newer artifact, `setup/data/model/netflow_ids.joblib`, has 14 features,
independent preprocessors, and a threshold of 0.9865455953940052. The existing
`train_netflow_model.py` explicitly clones preprocessors and checks independence
after saving and reloading. Its detection failures for several fixture categories
still require per-category validation and calibration.

These are diagnostic results on selected replay fixtures, not unbiased model
accuracy estimates or production validation.

## Render evidence and uncertainty

The user's screenshot shows Render returning benign with a 0.06% attack
probability for SQL Injection sample 4. The local legacy artifact returns about
0.17911% for the same fixture; the local NetFlow artifact returns about 95.76015%,
which is still below its threshold. Thus the screenshot supports a Stage A
rejection, but does not establish which exact artifact or runtime Render uses.
The deployed artifact's checksum, version, preprocessing, and threshold must be
checked before attributing Render's result to this exact local model.

## Recommended correction

1. Identify the artifact actually deployed on Render and its feature schema.
2. Use independently fitted preprocessors for both stages. A deep copy of an
   already-corrupted scaler does not restore Stage A's original transformation.
3. Retrain the affected model or use a properly validated replacement; calibrate
   the threshold on validation data and evaluate both stages together after reload.
4. Inspect attack recall per category and benign false positives. Do not simply
   lower the threshold until demo samples pass.
5. Generate multiple demo fixtures that pass both stages with the corrected
   artifact, and disclose categories for which enough distinct samples cannot be
   verified.
6. Deploy a matching API/model pair. The current MikroTik sniffer supplies the
   smaller NetFlow feature contract, while the Render endpoint previously exposed
   78 features. Real traffic compatibility needs checking independently of the
   full-feature demo payload.

No deployed model, prediction threshold, Block action, or Recover action was
changed during this investigation. No additional live prediction records were
created.
