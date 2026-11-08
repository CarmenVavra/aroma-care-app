@echo off
REM Startet AromaCare lokal: Laravel-Server (Port 8000), Vite-Dev-Server und Queue-Worker (E-Mails).
cd /d "%~dp0"

if not exist vendor (
    echo Installiere Composer-Abhaengigkeiten ...
    call composer install
)
if not exist node_modules (
    echo Installiere npm-Abhaengigkeiten ...
    call npm install
)

call php artisan migrate --graceful

start "AromaCare-PHP" cmd /k php artisan serve --host=127.0.0.1 --port=8000
start "AromaCare-Vite" cmd /k npm run dev
start "AromaCare-Queue" cmd /k php artisan queue:work --tries=3

echo.
echo AromaCare laeuft unter http://127.0.0.1:8000
echo Zum Beenden stop.bat ausfuehren.
timeout /t 3 >nul
start "" http://127.0.0.1:8000
