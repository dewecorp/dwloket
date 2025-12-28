# Cleanup Transaksi Lama - Dokumentasi

## Deskripsi
Sistem ini secara otomatis menghapus transaksi yang lebih dari 1 tahun untuk menghemat ruang database.

## Fitur
1. **Auto Cleanup** - Berjalan otomatis saat dashboard diakses (maksimal 1 kali per hari)
2. **Manual Cleanup** - Bisa dijalankan manual melalui halaman admin
3. **Dry Run** - Mode untuk menghitung tanpa menghapus
4. **Statistik** - Menampilkan jumlah transaksi lama yang akan dihapus

## Cara Penggunaan

### 1. Auto Cleanup (Otomatis)
Cleanup akan berjalan otomatis saat:
- Dashboard diakses
- Maksimal 1 kali per hari (untuk menghindari overhead)

### 2. Manual Cleanup (Admin)
1. Buka halaman: `admin/cleanup_transaksi.php`
2. Lihat statistik transaksi lama
3. Pilih opsi:
   - **Dry Run**: Hanya menghitung, tidak menghapus
   - **Jalankan Cleanup**: Menghapus transaksi lama
4. Klik tombol "Jalankan Cleanup Manual"

### 3. Cron Job (Opsional)
Untuk menjalankan cleanup via cron job:

```bash
# Tambahkan ke crontab (jalankan setiap hari jam 2 pagi)
0 2 * * * /usr/bin/php /path/to/cron/cleanup_transaksi.php
```

Atau akses via browser:
```
http://yourdomain.com/cron/cleanup_transaksi.php
```

## File yang Dibuat
1. `libs/transaksi_cleanup.php` - Fungsi helper untuk cleanup
2. `admin/cleanup_transaksi.php` - Halaman admin untuk manual cleanup
3. `cron/cleanup_transaksi.php` - Cron job handler
4. `logs/last_transaksi_cleanup.txt` - File untuk tracking cleanup terakhir

## Keamanan
- Cleanup hanya menghapus transaksi yang lebih dari 1 tahun
- Proses tidak dapat dibatalkan setelah dijalankan
- Disarankan untuk backup database sebelum cleanup
- Semua aktivitas cleanup dicatat di log aktivitas

## Catatan Penting
- **Backup Database**: Selalu backup database sebelum menjalankan cleanup manual
- **Dry Run**: Gunakan dry run terlebih dahulu untuk melihat berapa banyak transaksi yang akan dihapus
- **Auto Cleanup**: Berjalan maksimal 1 kali per hari untuk menghindari overhead
- **Log**: Semua aktivitas cleanup dicatat di `admin_activity_logs`

## Troubleshooting
Jika cleanup tidak berjalan:
1. Pastikan direktori `logs/` ada dan bisa ditulis
2. Cek file `logs/last_transaksi_cleanup.txt` untuk melihat cleanup terakhir
3. Cek log aktivitas untuk melihat error jika ada

