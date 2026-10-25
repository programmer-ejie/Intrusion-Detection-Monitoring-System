# Local IDS Setup

This folder contains the local Python services used by the intrusion detection system.

## Files

- `Detection Process.py`  
  Runs the local model API and writes detections directly into Hostinger MySQL.

- `Mikrotek PPPoe Sniffer.py`  
  Collects NetFlow data from MikroTik and sends feature payloads to the local detector.

- `Pending Blocker.py`  
  Polls the database every 20 seconds, resolves blocked PPPoE usernames to their current addresses, and removes subscriber-owned rules when restored.

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

- `unresolved` = a new incident awaiting an administrator action
- `blocked` = the subscriber is continuously reconciled to a MikroTik block rule
- `resolved` = internet access was restored and the incident remains in history

Threat status is stored in `threat_incidents`. Individual detections reference an
incident through `intrusion_logs.incident_id`. Matching detections are grouped for
15 minutes by default; a detection after a resolved incident always creates a new
unresolved incident. Set `INCIDENT_WINDOW_MINUTES` in `setup/.env` to change the
grouping window.

## Notes

- The detector writes directly to the Hostinger database configured in `.env`.
- Laravel reads the same database, so the dashboard updates automatically.
- Firewall rules are owned by stable PPPoE usernames and reconciled when a subscriber's dynamic address changes.
- Run `php artisan migrate --force` after deploying incident-tracking updates, then restart all three local services.
