@echo off
echo ========================================
echo   PUSH KE REPO WEB BACKUP
echo ========================================
echo.

REM Cek apakah remote repo-web ada
git remote get-url repo-web >nul 2>&1
if errorlevel 1 (
    echo ERROR: Remote 'repo-web' tidak ditemukan!
    echo Silakan setup remote terlebih dahulu.
    pause
    exit /b 1
)

echo [1/3] Menambahkan semua perubahan...
git add -A
if errorlevel 1 (
    echo ERROR: Gagal menambahkan file!
    pause
    exit /b 1
)
echo OK
echo.

echo [2/3] Membuat commit jika ada perubahan...
git diff --cached --quiet
if errorlevel 1 (
    set /p commit_msg="Masukkan pesan commit (atau tekan Enter untuk default): "
    if "%commit_msg%"=="" set commit_msg=Update: Perubahan terbaru - %date% %time%
    git commit -m "%commit_msg%"
    if errorlevel 1 (
        echo ERROR: Gagal membuat commit!
        pause
        exit /b 1
    )
    echo OK - Commit dibuat
) else (
    echo INFO: Tidak ada perubahan untuk di-commit
)
echo.

echo [3/3] Push ke REPO WEB...
git push repo-web master
if errorlevel 1 (
    echo ERROR: Gagal push ke REPO WEB!
    pause
    exit /b 1
)
echo OK - Push berhasil!
echo.

echo ========================================
echo   SELESAI - File sudah di-backup!
echo ========================================
echo.
echo Lokasi backup: H:\DATA 5\REPO WEB
echo.
pause

