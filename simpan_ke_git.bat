@echo off
echo ========================================
echo   SIMPAN PERUBAHAN KE GIT REPOSITORY
echo ========================================
echo.

REM Cek apakah ini folder git
git status >nul 2>&1
if errorlevel 1 (
    echo ERROR: Folder ini bukan Git Repository!
    echo Silakan jalankan setup_git_repo.ps1 terlebih dahulu.
    pause
    exit /b 1
)

echo [1/4] Menambahkan semua perubahan ke staging...
git add -A
if errorlevel 1 (
    echo ERROR: Gagal menambahkan file ke staging!
    pause
    exit /b 1
)
echo OK - File berhasil ditambahkan
echo.

echo [2/4] Mengecek status perubahan...
git status --short
echo.

echo [3/4] Membuat commit...
set /p commit_msg="Masukkan pesan commit (atau tekan Enter untuk default): "
if "%commit_msg%"=="" set commit_msg=Update: Perubahan terbaru - %date% %time%

git commit -m "%commit_msg%"
if errorlevel 1 (
    echo ERROR: Gagal membuat commit!
    pause
    exit /b 1
)
echo OK - Commit berhasil dibuat
echo.

echo [4/4] Mengecek apakah ada remote repository...
git remote -v >nul 2>&1
if errorlevel 1 (
    echo INFO: Belum ada remote repository yang dikonfigurasi.
    echo       Commit sudah tersimpan di local repository.
) else (
    echo INFO: Ada remote repository. Gunakan 'git push' untuk mengirim ke remote.
)
echo.

echo ========================================
echo   SELESAI - Perubahan sudah tersimpan!
echo ========================================
echo.
pause

