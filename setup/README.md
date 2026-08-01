# Local IDS Setup

This folder contains the local Python services used by the intrusion detection system.

## Files

- `Detection Process.py`  
  Runs the local model API and writes detections directly into Hostinger MySQL.

- `Mikrotek PPPoe Sniffer.py`  
  Collects NetFlow data from MikroTik and sends feature payloads to the local detector.

- `Pending Blocker.py`  
  Polls the database every 20 seconds, keeps `blocked` destination IPs synced on MikroTik, and removes `resolved` destination IPs from the MikroTik firewall.

- `.env`  
  Shared environment settings for the local Python scripts.

## Install

Install the required Python packages:

```bash
pip install -r data/requirements.txt
```

## Run Order

Open three terminals in this folder and start the scripts in this order:

```bash
python "Detection Process.py"
python "Mikrotek PPPoe Sniffer.py"
python "Pending Blocker.py"
```

### One-click launcher

If you want a single double-click launch on Windows, run:

```text
Start-IDS.bat
```

It opens one console window per service.

## Status Flow

- `NULL` = unresolved
- `blocked` = MikroTik block should be applied
- `resolved` = manually cleared and eligible for unblocking

## Notes

- The detector writes directly to the Hostinger database configured in `.env`.
- Laravel reads the same database, so the dashboard updates automatically.
- For MikroTik blocking to actually cut internet access, the pending blocker now creates a direct firewall drop rule per blocked destination IP.
