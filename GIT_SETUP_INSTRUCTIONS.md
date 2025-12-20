# Instruksi Setup Git Repository

## Status Saat Ini
✅ Project sudah diinisialisasi sebagai Git repository (folder `.git` sudah ada)
✅ File `.gitignore` sudah dikonfigurasi
❌ Belum ada remote repository yang dikonfigurasi
❌ Repo belum ada di `H:\REPO WEB\dwloket_ok`

## Langkah-langkah Setup

### 1. Pastikan Git Terinstall
Buka Command Prompt atau PowerShell dan jalankan:
```bash
git --version
```

Jika Git belum terinstall, download dari: https://git-scm.com/download/win

### 2. Setup Remote Repository di H:\REPO WEB

#### Opsi A: Menggunakan Git Bash atau Command Prompt
```bash
# Buka Git Bash atau Command Prompt di folder project
cd H:\laragon\www\dwloket_ok

# Tambahkan remote repository (jika repo sudah ada di H:\REPO WEB\dwloket_ok)
git remote add origin H:\REPO WEB\dwloket_ok

# Atau jika ingin menggunakan format file://
git remote add origin file:///H/REPO WEB/dwloket_ok
```

#### Opsi B: Membuat Bare Repository di H:\REPO WEB
```bash
# Buat bare repository di H:\REPO WEB
cd H:\REPO WEB
git clone --bare H:\laragon\www\dwloket_ok dwloket_ok.git

# Kembali ke folder project
cd H:\laragon\www\dwloket_ok

# Tambahkan remote
git remote add origin H:\REPO WEB\dwloket_ok.git
```

### 3. Commit Perubahan Saat Ini (jika belum ada commit)
```bash
# Cek status
git status

# Tambahkan semua file
git add .

# Commit
git commit -m "Initial commit - DW Loket Jepara Application"

# Atau commit dengan pesan lebih detail
git commit -m "Initial commit: DW Loket Jepara Application
- Sistem transaksi pembayaran
- Manajemen produk dan harga
- OrderKuota integration
- Security improvements (password hashing, SQL injection prevention)
- Dynamic page titles
- Modern UI/UX"
```

### 4. Push ke Remote Repository
```bash
# Push ke remote (jika menggunakan bare repository)
git push -u origin master

# Atau jika branch utama adalah main
git push -u origin main
```

### 5. Verifikasi
```bash
# Cek remote repository
git remote -v

# Cek status
git status

# Cek log commit
git log --oneline
```

## Catatan Penting

1. **File yang Diabaikan (`.gitignore`)**:
   - `vendor/`, `node_modules/` - Dependencies
   - `logs/`, `*.log` - Log files
   - `backups/`, `*.zip`, `*.sql` - Backup files
   - `config/database.php` - Database credentials (sensitive)
   - File temporary dan cache

2. **File Sensitif yang TIDAK di-commit**:
   - `config/database.php` - Sudah ada di `.gitignore`
   - File backup dan log
   - File dengan credentials

3. **Setup Git User (jika belum)**:
```bash
git config --global user.name "Your Name"
git config --local user.email "your.email@example.com"
```

## Troubleshooting

### Jika Git tidak dikenali:
1. Install Git dari https://git-scm.com/download/win
2. Restart Command Prompt/PowerShell setelah install
3. Atau tambahkan Git ke PATH environment variable

### Jika ada konflik:
```bash
# Pull perubahan terlebih dahulu
git pull origin master

# Resolve conflict, lalu
git add .
git commit -m "Resolve conflict"
git push origin master
```

### Jika ingin mengubah remote URL:
```bash
# Hapus remote lama
git remote remove origin

# Tambahkan remote baru
git remote add origin H:\REPO WEB\dwloket_ok.git
```

## Rekomendasi Workflow

1. **Sebelum membuat perubahan:**
   ```bash
   git pull origin master
   ```

2. **Setelah membuat perubahan:**
   ```bash
   git status
   git add .
   git commit -m "Deskripsi perubahan"
   git push origin master
   ```

3. **Buat branch untuk fitur baru:**
   ```bash
   git checkout -b feature/nama-fitur
   # ... buat perubahan ...
   git add .
   git commit -m "Add: nama fitur"
   git push origin feature/nama-fitur
   ```

## File Penting yang Sudah Ada

- ✅ `.gitignore` - Konfigurasi file yang diabaikan
- ✅ `SECURITY_IMPROVEMENTS.md` - Dokumentasi perbaikan keamanan
- ✅ `config/database.example.php` - Template database config

## Next Steps

1. Install Git (jika belum)
2. Setup remote repository di `H:\REPO WEB\dwloket_ok`
3. Commit semua perubahan saat ini
4. Push ke remote repository
5. Setup regular backup/commit workflow

