@echo off
cd /d "%~dp0"

echo Limpiando procesos PHP antiguos...
taskkill /F /IM php.exe >nul 2>&1

echo Generando key y limpiando cache...
php artisan key:generate --force
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

echo Iniciando Laravel en http://127.0.0.1:8080
php artisan serve --host=127.0.0.1 --port=8080

if errorlevel 1 pause
