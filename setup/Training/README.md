# MikroTik NetFlow IDS training

`train_netflow_model.py` replaces the defective notebook path for deployment.
It trains against the exact feature contract in `training_schema.py`, gives
Stage A and Stage B independent preprocessors, chooses the threshold on a
validation split, and evaluates only after saving and reloading the artifact.
Every run also reports a balanced validation of 100 benign and 100 attack rows
drawn only from the untouched test partition. Very small smoke runs use the
largest balanced sample available and mark the 200-sample validation incomplete.
Long-running training stages display an animated activity bar and elapsed time.
The estimators do not expose a reliable percentage, so the bar confirms activity
rather than estimating completion.

Quick smoke test:

```powershell
..\..\.venv-ids\Scripts\python.exe train_netflow_model.py --max-rows-per-file 1000
```

Sampled runs save to `../data/model/netflow_ids.smoke.joblib` and never
overwrite the full model.

Full bootstrap training:

```powershell
..\..\.venv-ids\Scripts\python.exe train_netflow_model.py
```

The generated `dataset_profile` is `cicids-bootstrap`. Do not treat its test
metrics as production validation. Before deployment, evaluate the saved model
against labeled traffic captured from the actual MikroTik environment.
