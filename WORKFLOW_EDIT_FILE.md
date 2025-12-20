# WORKFLOW EDIT FILE - Mencegah File Hilang

## ⚠️ MASALAH YANG TERJADI
File yang sudah di-edit dan di-save hilang kembali, bahkan di disk juga hilang.

## ✅ SOLUSI YANG SUDAH DITERAPKAN

1. **Git Config sudah diperbaiki**
   - `core.autocrlf = false` (mencegah line ending issue)
   - `core.safecrlf = false` (mencegah auto-restore)

2. **Script Helper sudah dibuat**
   - `simpan_sekarang.bat` - Commit cepat
   - `simpan_ke_git.bat` - Commit dengan pesan custom
   - `cek_perubahan_file.ps1` - Cek status file
   - `backup_sebelum_edit.ps1` - Backup sebelum edit

## 📋 WORKFLOW YANG WAJIB DIIKUTI

### SETIAP KALI EDIT FILE:

1. **SEBELUM EDIT** (Opsional tapi disarankan):
   ```powershell
   .\backup_sebelum_edit.ps1 "jenisbayar\import_produk_excel.php"
   ```

2. **EDIT FILE** di editor

3. **SAVE FILE** (Ctrl+S) - Pastikan benar-benar save!

4. **CEK PERUBAHAN** (PENTING!):
   ```powershell
   .\cek_perubahan_file.ps1 "jenisbayar\import_produk_excel.php"
   ```
   - Pastikan hash file berubah
   - Pastikan waktu modifikasi baru

5. **LANGSUNG COMMIT** (JANGAN TUNGGU!):
   ```batch
   simpan_sekarang.bat
   ```
   ATAU
   ```batch
   simpan_ke_git.bat
   ```

6. **VERIFIKASI**:
   ```bash
   git log --oneline -1
   ```
   - Pastikan commit ada di log

## 🚨 JANGAN LAKUKAN INI:

- ❌ Jangan tutup editor sebelum commit
- ❌ Jangan reload project sebelum commit
- ❌ Jangan restart komputer sebelum commit
- ❌ Jangan edit banyak file sekaligus tanpa commit

## 🔍 JIKA FILE MASIH HILANG:

1. Cek apakah benar-benar di-save:
   ```powershell
   .\cek_perubahan_file.ps1 "jenisbayar\import_produk_excel.php"
   ```

2. Cek git status:
   ```bash
   git status jenisbayar/import_produk_excel.php
   ```

3. Cek apakah ada di backup:
   ```powershell
   Get-ChildItem .\backups\file_backups\ | Sort-Object LastWriteTime -Descending | Select-Object -First 5
   ```

4. Restore dari backup jika perlu:
   ```powershell
   Copy-Item ".\backups\file_backups\20241221_120000_import_produk_excel.php" "jenisbayar\import_produk_excel.php" -Force
   ```

## 💡 TIPS:

1. **Gunakan Auto-Save** di editor (jika tersedia)
2. **Commit sering** - jangan tunggu banyak perubahan
3. **Gunakan backup** untuk file penting
4. **Cek status** setelah setiap edit

## 📞 MASALAH TERUS BERLANJUT?

1. Cek VS Code/Cursor settings:
   - `git.enableAutoRefresh` = false
   - `files.autoSave` = afterDelay

2. Nonaktifkan extension yang terkait git/auto-save

3. Restart editor dan komputer

4. Cek apakah ada sync tool (OneDrive, Dropbox) yang conflict

