@echo off
setlocal
chcp 65001 >NUL

pushd "%~dp0"
echo ==========================================
echo DW LOKET AUTO BACKUP & GIT PUSH
echo ==========================================
echo Script: %~f0
echo Folder: %cd%
echo.

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
if errorlevel 1 goto COMMIT_FAIL
goto COMMIT_OK

:COMMIT_FAIL
echo.
echo Tidak ada perubahan untuk di-commit atau commit gagal. Melanjutkan...

:COMMIT_OK

echo [3/5] Konfigurasi remote GitHub...
:: Coba tambah remote origin, sembunyikan error jika sudah ada
git remote add origin https://github.com/dewecorp/dwloket 2>NUL
:: Pastikan URL origin benar
git remote set-url origin https://github.com/dewecorp/dwloket

echo [4/5] Upload ke GitHub (Push)...
:: Pastikan referensi remote terbaru
git fetch --all --prune
for /f "tokens=*" %%b in ('git rev-parse --abbrev-ref HEAD') do set CUR_BRANCH=%%b
echo Branch aktif: %CUR_BRANCH%

:: Coba push normal dulu
git push -u origin HEAD:main
if not errorlevel 1 goto AFTER_PUSH

echo Push ditolak (kemungkinan non-fast-forward). Mencoba force-with-lease yang aman...
git push --force-with-lease origin HEAD:main
if not errorlevel 1 goto AFTER_PUSH

echo Gagal push meskipun force-with-lease. Coba tarik perubahan remote lalu push lagi...
git pull --rebase origin main
git push -u origin HEAD:main

:AFTER_PUSH

echo [5/5] Membuat file backup ZIP (dwloket_full_backup.zip)...
set "ZIP_NAME=dwloket_full_backup.zip"
echo Membuat ZIP, mohon tunggu...
powershell -NoProfile -ExecutionPolicy Bypass -Command "$ErrorActionPreference='Stop'; $dest=Join-Path (Get-Location) '%ZIP_NAME%'; if(Test-Path $dest){Remove-Item $dest -Force}; $items=Get-ChildItem -Force | Where-Object { $_.Name -ne '.git' -and $_.Name -ne '%ZIP_NAME%' -and $_.Name -notlike '*.zip' }; if(-not $items){ throw 'Tidak ada file untuk di-zip (folder kosong?)' }; Compress-Archive -LiteralPath $items.FullName -DestinationPath $dest -Force; if(-not (Test-Path $dest)){ throw 'Gagal membuat ZIP' }"
if errorlevel 1 (
    echo.
    echo GAGAL membuat backup ZIP. File tidak jadi dibuat.
    goto END_FAIL
)
if not exist "%ZIP_NAME%" (
    echo.
    echo GAGAL membuat backup ZIP. File tidak ditemukan.
    goto END_FAIL
)

for %%F in ("%ZIP_NAME%") do set "ZIP_FULL=%%~fF"
echo Backup ZIP berhasil dibuat: %ZIP_FULL%

echo.
echo ==========================================
echo PROSES SELESAI!
echo ==========================================
pause
goto END_OK

:END_FAIL
echo.
echo ==========================================
echo PROSES GAGAL!
echo ==========================================
pause
popd
exit /b 1

:END_OK
popd
exit /b 0
