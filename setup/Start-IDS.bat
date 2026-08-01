@echo off
setlocal
cd /d "%~dp0"

echo Starting IDS services...

start "IDS Detector" cmd /k python "Detection Process.py"
start "IDS Sniffer" cmd /k python "Mikrotek PPPoe Sniffer.py"
start "IDS Blocker" cmd /k python "Pending Blocker.py"

echo All services launched.
echo Leave these windows open while the IDS is running.
endlocal
