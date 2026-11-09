@echo off
REM Beendet die von start.bat gestarteten Prozesse (PHP-Server, Vite, Queue-Worker).
taskkill /FI "WINDOWTITLE eq AromaCare-PHP*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq AromaCare-Vite*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq AromaCare-Queue*" /T /F >nul 2>&1
REM Falls die Server ohne Fenster weiterlaufen: Prozesse auf Backend- (9400) und Vite-Port (6600) beenden.
powershell -NoProfile -Command "Get-NetTCPConnection -LocalPort 9400,6600 -State Listen -ErrorAction SilentlyContinue | ForEach-Object { Stop-Process -Id $_.OwningProcess -Force -ErrorAction SilentlyContinue }"
if exist "%~dp0public\hot" del /q "%~dp0public\hot"
echo AromaCare wurde gestoppt.
