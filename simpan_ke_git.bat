@echo off
echo ========================================
echo Setup Git Repository - DW Loket Jepara
echo ========================================
echo.
echo Script ini akan membantu menyimpan project ke Git repository
echo di H:\REPO WEB\dwloket_ok.git
echo.
pause

REM Jalankan PowerShell script
PowerShell -ExecutionPolicy Bypass -File "%~dp0setup_git_repo.ps1"

pause

