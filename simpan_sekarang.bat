@echo off
chcp 65001 >nul
echo ========================================
echo Menyimpan Project ke Git Repository
echo ========================================
echo.
echo Script ini akan:
echo 1. Menambahkan semua file ke Git
echo 2. Menyimpan (commit) perubahan
echo 3. Mengirim ke repository di H:\REPO WEB
echo.
pause

cd /d "%~dp0"

REM Cek apakah Git terinstall
git --version >nul 2>&1
if errorlevel 1 (
    echo.
    echo ERROR: Git tidak ditemukan!
    echo.
    echo Silakan install Git terlebih dahulu:
    echo 1. Download dari: https://git-scm.com/download/win
    echo 2. Install Git
    echo 3. Restart Command Prompt
    echo 4. Jalankan script ini lagi
    echo.
    pause
    exit /b 1
)

REM Setup remote jika belum ada
echo.
echo [1/4] Mengecek remote repository...
git remote -v >nul 2>&1
if errorlevel 1 (
    echo Membuat repository di H:\REPO WEB...
    if not exist "H:\REPO WEB" mkdir "H:\REPO WEB"
    cd /d "H:\REPO WEB"
    if not exist "dwloket_ok.git" (
        git clone --bare "%~dp0" dwloket_ok.git
    )
    cd /d "%~dp0"
    git remote add origin "H:\REPO WEB\dwloket_ok.git"
    echo ✓ Remote repository berhasil ditambahkan
) else (
    echo ✓ Remote repository sudah ada
)

REM Tambahkan semua file
echo.
echo [2/4] Menambahkan semua file ke Git...
git add .
if errorlevel 1 (
    echo ERROR: Gagal menambahkan file
    pause
    exit /b 1
)
echo ✓ File berhasil ditambahkan

REM Commit
echo.
echo [3/4] Menyimpan perubahan...
set "COMMIT_MSG=Update: DW Loket Jepara - %date% %time%"
git commit -m "%COMMIT_MSG%"
if errorlevel 1 (
    echo ⚠ Tidak ada perubahan untuk di-commit atau sudah up-to-date
) else (
    echo ✓ Perubahan berhasil disimpan
)

REM Push
echo.
echo [4/4] Mengirim ke repository di H:\REPO WEB...
git push -u origin master 2>nul
if errorlevel 1 (
    git push -u origin main 2>nul
    if errorlevel 1 (
        echo ⚠ Push gagal, tapi perubahan sudah disimpan lokal
        echo Anda bisa push manual nanti dengan: git push
    ) else (
        echo ✓ Berhasil dikirim ke repository!
    )
) else (
    echo ✓ Berhasil dikirim ke repository!
)

echo.
echo ========================================
echo Selesai!
echo ========================================
echo.
echo Project sudah tersimpan di:
echo H:\REPO WEB\dwloket_ok.git
echo.
echo File yang berwarna akan kembali normal setelah ini.
echo.
pause

