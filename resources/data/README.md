Demo flow fixtures contain five distinct labeled CICIDS2017 rows for each of
the paper's 14 attack categories. They contain all 78 features required by the
current Render API. They are replayed as JSON, not as network attack traffic.

Rebuild with `.venv-ids/Scripts/python.exe tools/build_demo_attack_samples.py`
when the original CSVs in `setup/Training/Dataset` and the trusted local model
artifact are available. The generator checks each row against Stage B.

These are not verified end-to-end attack predictions. The local legacy
78-feature Stage A model assigns these attack rows probabilities below its
saved threshold (0.999859849387428). The application displays the actual
remote prediction, including benign or a different attack label. Repairing or
redeploying the model is separate from the demo UI change.

Render may save predictions directly or forward them synchronously to Laravel.
The demo action reuses that new log when it appears in the shared database;
otherwise it saves the confirmed prediction locally and links attack results
to an incident. It never assigns a selected attack label as a prediction.
