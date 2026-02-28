@echo off
echo ==========================================
echo DW LOKET AUTO BACKUP & GIT PUSH
echo ==========================================

echo [1/5] Menambahkan file ke git...
git add .

echo [2/5] Membuat commit...
:INPUT_MSG
set "commit_msg="
set /p commit_msg="Masukkan pesan commit (tekan Enter untuk default): "
if "%commit_msg%"=="" set commit_msg="Auto backup update"

echo.
echo Pesan commit yang akan digunakan: "%commit_msg%"
set /p confirm="Apakah sudah benar? (Y/N): "
if /i "%confirm%" neq "Y" (
    echo Silakan ulangi...
    echo.
    goto INPUT_MSG
)

git commit -m "%commit_msg%"

echo [3/5] Konfigurasi remote GitHub...
:: Coba tambah remote origin, sembunyikan error jika sudah ada
git remote add origin https://github.com/dewecorp/dwloket 2>NUL
:: Pastikan URL origin benar
git remote set-url origin https://github.com/dewecorp/dwloket

echo [4/5] Upload ke GitHub (Push)...
git push -u origin main

echo [5/5] Membuat file backup ZIP (dwloket_full_backup.zip)...
:: Menggunakan PowerShell untuk zip, mengecualikan folder .git dan file zip itu sendiri
powershell -Command "Get-ChildItem -Path . -Exclude '.git','dwloket_full_backup.zip','*.zip' | Compress-Archive -DestinationPath dwloket_full_backup.zip -Force"

echo.
echo ==========================================
echo PROSES SELESAI!
echo ==========================================
pause
