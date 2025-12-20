# Script Setup Git Repository untuk DW Loket Jepara
# Versi yang diperbaiki - lebih sederhana dan aman

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Setup Git Repository - DW Loket Jepara" -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""

# Cek apakah Git terinstall
Write-Host "[1/6] Mengecek instalasi Git..." -ForegroundColor Yellow
try {
    $gitVersion = git --version 2>&1
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Git ditemukan: $gitVersion" -ForegroundColor Green
    } else {
        throw "Git tidak ditemukan"
    }
} catch {
    Write-Host "ERROR: Git tidak ditemukan!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Silakan install Git terlebih dahulu:" -ForegroundColor Yellow
    Write-Host "1. Download dari: https://git-scm.com/download/win" -ForegroundColor White
    Write-Host "2. Install Git" -ForegroundColor White
    Write-Host "3. Restart PowerShell/Command Prompt" -ForegroundColor White
    Write-Host "4. Jalankan script ini lagi" -ForegroundColor White
    Write-Host ""
    Read-Host "Tekan Enter untuk keluar"
    exit 1
}
Write-Host ""

# Cek folder REPO WEB
Write-Host "[2/6] Mengecek folder H:\REPO WEB..." -ForegroundColor Yellow
$repoPath = "H:\REPO WEB"
if (-not (Test-Path $repoPath)) {
    Write-Host "Membuat folder H:\REPO WEB..." -ForegroundColor Yellow
    try {
        New-Item -ItemType Directory -Path $repoPath -Force | Out-Null
        Write-Host "✓ Folder H:\REPO WEB berhasil dibuat" -ForegroundColor Green
    } catch {
        Write-Host "ERROR: Gagal membuat folder H:\REPO WEB" -ForegroundColor Red
        Read-Host "Tekan Enter untuk keluar"
        exit 1
    }
} else {
    Write-Host "✓ Folder H:\REPO WEB sudah ada" -ForegroundColor Green
}
Write-Host ""

# Cek apakah repo sudah ada
Write-Host "[3/6] Mengecek repository di H:\REPO WEB\dwloket_ok..." -ForegroundColor Yellow
$bareRepoPath = Join-Path $repoPath "dwloket_ok.git"
if (Test-Path $bareRepoPath) {
    Write-Host "⚠ Repository sudah ada di $bareRepoPath" -ForegroundColor Yellow
    $overwrite = Read-Host "Apakah ingin menghapus dan membuat ulang? (y/n)"
    if ($overwrite -eq "y" -or $overwrite -eq "Y") {
        try {
            Remove-Item -Path $bareRepoPath -Recurse -Force
            Write-Host "✓ Repository lama dihapus" -ForegroundColor Green
        } catch {
            Write-Host "ERROR: Gagal menghapus repository lama" -ForegroundColor Red
            Read-Host "Tekan Enter untuk keluar"
            exit 1
        }
    } else {
        Write-Host "Menggunakan repository yang sudah ada" -ForegroundColor Yellow
    }
}
Write-Host ""

# Buat bare repository jika belum ada
if (-not (Test-Path $bareRepoPath)) {
    Write-Host "[4/6] Membuat bare repository di H:\REPO WEB\dwloket_ok.git..." -ForegroundColor Yellow
    $currentPath = Get-Location
    try {
        Set-Location $repoPath
        $currentPathEscaped = $currentPath.Path
        git clone --bare $currentPathEscaped dwloket_ok.git
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✓ Bare repository berhasil dibuat" -ForegroundColor Green
        } else {
            throw "Gagal membuat bare repository"
        }
        Set-Location $currentPath
    } catch {
        Write-Host "ERROR: Gagal membuat bare repository" -ForegroundColor Red
        Write-Host "Error: $_" -ForegroundColor Red
        Set-Location $currentPath
        Read-Host "Tekan Enter untuk keluar"
        exit 1
    }
} else {
    Write-Host "[4/6] Repository sudah ada, melewati pembuatan..." -ForegroundColor Yellow
}
Write-Host ""

# Setup remote origin
Write-Host "[5/6] Mengatur remote repository..." -ForegroundColor Yellow
try {
    $remoteCheck = git remote -v 2>&1 | Out-String
    if ($remoteCheck -match "origin") {
        Write-Host "⚠ Remote 'origin' sudah ada" -ForegroundColor Yellow
        $updateRemote = Read-Host "Apakah ingin mengupdate remote? (y/n)"
        if ($updateRemote -eq "y" -or $updateRemote -eq "Y") {
            git remote remove origin 2>&1 | Out-Null
            git remote add origin $bareRepoPath
            if ($LASTEXITCODE -eq 0) {
                Write-Host "✓ Remote 'origin' berhasil diupdate" -ForegroundColor Green
            } else {
                throw "Gagal mengupdate remote"
            }
        }
    } else {
        git remote add origin $bareRepoPath
        if ($LASTEXITCODE -eq 0) {
            Write-Host "✓ Remote 'origin' berhasil ditambahkan" -ForegroundColor Green
        } else {
            throw "Gagal menambahkan remote"
        }
    }
} catch {
    Write-Host "ERROR: Gagal mengatur remote repository" -ForegroundColor Red
    Write-Host "Error: $_" -ForegroundColor Red
    Read-Host "Tekan Enter untuk keluar"
    exit 1
}
Write-Host ""

# Commit dan push
Write-Host "[6/6] Menyimpan perubahan ke repository..." -ForegroundColor Yellow
Write-Host ""

# Cek status
Write-Host "Status repository:" -ForegroundColor Cyan
git status
Write-Host ""

# Tanyakan apakah ingin commit
$commit = Read-Host "Apakah ingin commit semua perubahan sekarang? (y/n)"
if ($commit -eq "y" -or $commit -eq "Y") {
    # Tambahkan semua file
    Write-Host "Menambahkan file ke staging..." -ForegroundColor Yellow
    git add .

    # Commit
    $commitMessage = Read-Host "Masukkan pesan commit (atau tekan Enter untuk menggunakan default)"
    if ([string]::IsNullOrWhiteSpace($commitMessage)) {
        $commitMessage = "Initial commit: DW Loket Jepara Application - $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')"
    }

    Write-Host "Membuat commit..." -ForegroundColor Yellow
    git commit -m $commitMessage

    if ($LASTEXITCODE -eq 0) {
        Write-Host "✓ Commit berhasil dibuat" -ForegroundColor Green
        Write-Host ""

        # Push ke remote
        Write-Host "Mengirim ke repository di H:\REPO WEB..." -ForegroundColor Yellow
        try {
            $branchOutput = git branch --show-current 2>&1
            $branch = $branchOutput.Trim()
            if ([string]::IsNullOrWhiteSpace($branch)) {
                # Coba cek branch master atau main
                $allBranches = git branch 2>&1 | Out-String
                if ($allBranches -match "master") {
                    $branch = "master"
                } elseif ($allBranches -match "main") {
                    $branch = "main"
                } else {
                    # Buat branch master jika belum ada
                    git checkout -b master 2>&1 | Out-Null
                    $branch = "master"
                }
            }

            git push -u origin $branch

            if ($LASTEXITCODE -eq 0) {
                Write-Host "✓ Push berhasil! Project sudah tersimpan di H:\REPO WEB\dwloket_ok.git" -ForegroundColor Green
            } else {
                Write-Host "⚠ Push gagal, tapi commit sudah dibuat lokal" -ForegroundColor Yellow
                Write-Host "Anda bisa push manual nanti dengan: git push -u origin $branch" -ForegroundColor Yellow
            }
        } catch {
            Write-Host "⚠ Error saat push: $_" -ForegroundColor Yellow
            Write-Host "Commit sudah dibuat lokal, push bisa dilakukan manual nanti" -ForegroundColor Yellow
        }
    } else {
        Write-Host "⚠ Tidak ada perubahan untuk di-commit atau commit gagal" -ForegroundColor Yellow
    }
} else {
    Write-Host "Commit dibatalkan. Anda bisa commit manual nanti dengan:" -ForegroundColor Yellow
    Write-Host "  git add ." -ForegroundColor White
    Write-Host "  git commit -m 'Pesan commit'" -ForegroundColor White
    Write-Host "  git push origin master" -ForegroundColor White
}
Write-Host ""

# Tampilkan informasi
Write-Host "========================================" -ForegroundColor Cyan
Write-Host "Setup Selesai!" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "Repository Location:" -ForegroundColor Yellow
Write-Host "  Local:  $(Get-Location)" -ForegroundColor White
Write-Host "  Remote: $bareRepoPath" -ForegroundColor White
Write-Host ""
Write-Host "Perintah Git yang berguna:" -ForegroundColor Yellow
Write-Host "  git status              - Cek status perubahan" -ForegroundColor White
Write-Host "  git add .               - Tambahkan semua perubahan" -ForegroundColor White
Write-Host "  git commit -m 'pesan'   - Simpan perubahan" -ForegroundColor White
Write-Host "  git push                - Kirim ke repository" -ForegroundColor White
Write-Host "  git pull                - Ambil perubahan terbaru" -ForegroundColor White
Write-Host ""
Read-Host "Tekan Enter untuk keluar"
