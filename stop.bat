@echo off
REM Beendet die von start.bat gestarteten Prozesse (PHP-Server, Vite, Queue-Worker).
taskkill /FI "WINDOWTITLE eq AromaCare-PHP*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq AromaCare-Vite*" /T /F >nul 2>&1
taskkill /FI "WINDOWTITLE eq AromaCare-Queue*" /T /F >nul 2>&1
echo AromaCare wurde gestoppt.
