# Script untuk backup file sebelum edit
# Jalankan script ini sebelum edit file penting

param(
    [Parameter(Mandatory=$true)]
    [string]$FilePath
)

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$backupDir = ".\backups\file_backups"
$fileName = Split-Path -Leaf $FilePath
$backupPath = Join-Path $backupDir "$timestamp`_$fileName"

# Buat folder backup jika belum ada
if (-not (Test-Path $backupDir)) {
    New-Item -ItemType Directory -Path $backupDir -Force | Out-Null
}

# Backup file jika ada
if (Test-Path $FilePath) {
    Copy-Item -Path $FilePath -Destination $backupPath -Force
    Write-Host "✓ Backup dibuat: $backupPath" -ForegroundColor Green
} else {
    Write-Host "⚠ File tidak ditemukan: $FilePath" -ForegroundColor Yellow
}

