@echo off
REM Script cepat untuk commit semua perubahan dengan pesan default
echo Menyimpan perubahan ke Git...

git add -A
git commit -m "Update: Perubahan terbaru - %date% %time%"

if errorlevel 1 (
    echo ERROR: Gagal menyimpan!
    pause
) else (
    echo OK - Perubahan berhasil disimpan!
    timeout /t 2 >nul
)

