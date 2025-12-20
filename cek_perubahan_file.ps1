# Script untuk cek apakah file berubah setelah edit
# Gunakan untuk debug masalah file yang hilang

param(
    [Parameter(Mandatory=$true)]
    [string]$FilePath
)

Write-Host "=== CEK PERUBAHAN FILE ===" -ForegroundColor Cyan
Write-Host "File: $FilePath" -ForegroundColor Yellow
Write-Host ""

# Cek apakah file ada
if (-not (Test-Path $FilePath)) {
    Write-Host "✗ File tidak ditemukan!" -ForegroundColor Red
    exit 1
}

# Cek status git
Write-Host "[1] Status Git:" -ForegroundColor Cyan
$gitStatus = git status --short $FilePath 2>&1
if ($gitStatus) {
    Write-Host $gitStatus -ForegroundColor Yellow
} else {
    Write-Host "  Tidak ada perubahan di git" -ForegroundColor Green
}

# Cek hash file
Write-Host "`n[2] Hash File (MD5):" -ForegroundColor Cyan
$fileHash = (Get-FileHash -Path $FilePath -Algorithm MD5).Hash
Write-Host "  $fileHash" -ForegroundColor White

# Cek waktu modifikasi
Write-Host "`n[3] Waktu Modifikasi:" -ForegroundColor Cyan
$lastWrite = (Get-Item $FilePath).LastWriteTime
Write-Host "  $lastWrite" -ForegroundColor White

# Cek ukuran file
Write-Host "`n[4] Ukuran File:" -ForegroundColor Cyan
$fileSize = (Get-Item $FilePath).Length
Write-Host "  $fileSize bytes" -ForegroundColor White

# Cek apakah ada di git index
Write-Host "`n[5] Status di Git Index:" -ForegroundColor Cyan
$gitLsFiles = git ls-files --stage $FilePath 2>&1
if ($gitLsFiles) {
    Write-Host "  File ter-track di git" -ForegroundColor Green
    $gitHash = git hash-object $FilePath
    Write-Host "  Git hash: $gitHash" -ForegroundColor White
} else {
    Write-Host "  File tidak ter-track di git" -ForegroundColor Yellow
}

# Cek diff dengan HEAD
Write-Host "`n[6] Perbedaan dengan HEAD:" -ForegroundColor Cyan
$gitDiff = git diff HEAD -- $FilePath 2>&1
if ($gitDiff) {
    Write-Host "  Ada perubahan yang belum di-commit" -ForegroundColor Yellow
    $diffLines = ($gitDiff | Measure-Object -Line).Lines
    Write-Host "  Jumlah baris berbeda: $diffLines" -ForegroundColor White
} else {
    Write-Host "  Tidak ada perbedaan dengan HEAD" -ForegroundColor Green
}

Write-Host "`n=== SELESAI ===" -ForegroundColor Cyan

