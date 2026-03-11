@echo off
setlocal

set "PROJECT=C:\Users\ryo25\OneDrive\dev\tcpg_system_admin_laravel"
set "SCRIPT=%PROJECT%\scripts\admin_preflight.ps1"

if not exist "%PROJECT%\artisan" (
  echo [ERROR] artisan not found: %PROJECT%
  pause
  exit /b 1
)

if not exist "%SCRIPT%" (
  echo [ERROR] script not found: %SCRIPT%
  pause
  exit /b 1
)

powershell -NoProfile -ExecutionPolicy Bypass -File "%SCRIPT%" -StartServer -Port 8082
if errorlevel 1 (
  echo.
  echo [FAILED] Preflight failed. Startup cancelled.
  pause
  exit /b 1
)

echo.
echo [OK] Admin started: http://127.0.0.1:8082
pause
exit /b 0
