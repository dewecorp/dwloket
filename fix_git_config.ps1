# Script untuk fix git config yang mungkin menyebabkan masalah
# Jalankan script ini sekali untuk memperbaiki konfigurasi git

Write-Host "=== FIX GIT CONFIG ===" -ForegroundColor Cyan
Write-Host ""

# Backup config saat ini
Write-Host "[1] Backup config saat ini..." -ForegroundColor Yellow
git config --list > git_config_backup.txt
Write-Host "✓ Config disimpan ke: git_config_backup.txt" -ForegroundColor Green

# Fix autocrlf - bisa menyebabkan masalah di Windows
Write-Host "`n[2] Fix core.autocrlf..." -ForegroundColor Yellow
git config core.autocrlf false
Write-Host "✓ core.autocrlf = false" -ForegroundColor Green

# Enable file system cache untuk performa
Write-Host "`n[3] Enable fscache..." -ForegroundColor Yellow
git config core.fscache true
Write-Host "✓ core.fscache = true" -ForegroundColor Green

# Disable safe checkout yang bisa restore file
Write-Host "`n[4] Disable safe checkout..." -ForegroundColor Yellow
git config core.safecrlf false
Write-Host "✓ core.safecrlf = false" -ForegroundColor Green

# Set ignorecase untuk Windows
Write-Host "`n[5] Set ignorecase..." -ForegroundColor Yellow
git config core.ignorecase true
Write-Host "✓ core.ignorecase = true" -ForegroundColor Green

Write-Host "`n=== SELESAI ===" -ForegroundColor Cyan
Write-Host "Config git sudah diperbaiki!" -ForegroundColor Green
Write-Host "`nCatatan: Jika masih ada masalah, coba:" -ForegroundColor Yellow
Write-Host "  1. Tutup dan buka kembali VS Code/Cursor" -ForegroundColor White
Write-Host "  2. Restart komputer" -ForegroundColor White
Write-Host "  3. Cek extension yang mungkin auto-restore file" -ForegroundColor White

