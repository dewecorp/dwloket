# Dokumentasi Tabel Produk OrderKuota

Dokumentasi ini menjelaskan bagaimana menggunakan tabel produk dan harga untuk memudahkan penampilan produk di laman orderkuota dan tambah transaksi.

## Struktur Database

### Tabel: `tb_produk_orderkuota`

Tabel ini menyimpan produk dan harga detail dari OrderKuota dengan struktur:

- `id_produk` - Primary key (auto increment)
- `kode` - Kode produk dari OrderKuota (unique)
- `keterangan` - Deskripsi produk
- `produk` - Nama produk
- `kategori` - Kategori produk (KUOTA SMARTFREN, KUOTA AXIS, dll)
- `harga` - Harga produk
- `status` - Status produk (1 = Aktif, 0 = Tidak Aktif)
- `id_bayar` - Foreign key ke `tb_jenisbayar` (relasi dengan jenis pembayaran)
- `created_at` - Timestamp pembuatan
- `updated_at` - Timestamp update

### Relasi

Tabel ini memiliki relasi dengan `tb_jenisbayar` melalui field `id_bayar`. Ini memungkinkan produk untuk dikelompokkan berdasarkan jenis pembayaran yang sesuai.

## Setup Awal

### 1. Membuat Tabel

Jalankan SQL berikut di phpMyAdmin atau MySQL client:

```sql
-- File: orderkuota/create_table_produk.sql
-- Atau import melalui phpMyAdmin
```

Atau tabel akan dibuat otomatis saat menjalankan script import.

### 2. Import Data dari JSON

Ada dua cara untuk mengimport data:

#### Cara 1: Melalui Browser
1. Pastikan file `orderkuota_price_data.json` ada di folder `orderkuota`
2. Buka browser dan akses: `http://your-domain/orderkuota/import_produk.php`
3. Tunggu hingga proses import selesai
4. Anda akan melihat statistik hasil import

#### Cara 2: Melalui Command Line
```bash
php orderkuota/import_produk.php
```

### 3. Mapping Kategori ke Jenis Bayar

Script import otomatis akan memetakan kategori produk ke jenis bayar berdasarkan mapping yang ada di file `import_produk.php`. Jika kategori tidak ditemukan, `id_bayar` akan di-set NULL.

## Penggunaan

### 1. Menampilkan Produk di Halaman OrderKuota

Akses halaman produk melalui:
```
http://your-domain/orderkuota/produk.php
```

Fitur:
- Filter berdasarkan jenis pembayaran
- Filter berdasarkan kategori
- Pencarian produk
- Tampilan produk dalam card layout
- Copy kode produk ke clipboard dengan klik

### 2. Menggunakan Produk di Halaman Tambah Transaksi

Di halaman `transaksi/tambah.php`:

1. Pilih jenis pembayaran dari grid
2. Sistem akan otomatis memuat produk yang terkait dengan jenis pembayaran tersebut
3. Klik pada produk untuk mengisi harga secara otomatis
4. Lanjutkan mengisi form transaksi lainnya

### 3. Helper Functions

File `libs/produk_helper.php` menyediakan beberapa fungsi helper:

#### `getProdukByKategori($id_bayar, $kategori, $only_active)`
Mengambil produk berdasarkan kategori atau jenis bayar.

#### `getProdukByKode($kode)`
Mengambil produk berdasarkan kode.

#### `getAllKategori()`
Mengambil semua kategori unik beserta jumlah produk.

#### `getProdukByIdBayar($id_bayar, $only_active)`
Mengambil produk berdasarkan ID jenis bayar.

#### `searchProduk($keyword, $only_active)`
Mencari produk berdasarkan keyword.

#### `getProdukStats()`
Mengambil statistik produk.

## Contoh Penggunaan Helper Function

```php
<?php
require_once 'libs/produk_helper.php';

// Ambil produk berdasarkan jenis bayar
$produk_smartfren = getProdukByIdBayar(9); // 9 = Data Internet Smartfren

// Ambil produk berdasarkan kategori
$produk_kuota = getProdukByKategori(null, 'KUOTA SMARTFREN');

// Cari produk
$hasil_cari = searchProduk('Smart 30GB');

// Ambil statistik
$stats = getProdukStats();
echo "Total produk: " . $stats['total'];
echo "Produk aktif: " . $stats['aktif'];
?>
```

## Update Data Produk

Untuk update data produk:

1. Ganti file `orderkuota_price_data.json` dengan data terbaru
2. Jalankan lagi script import (`import_produk.php`)
3. Script akan melakukan update untuk produk yang sudah ada (berdasarkan kode)
4. Produk baru akan di-insert

## Troubleshooting

### Produk tidak muncul setelah import

1. Cek apakah tabel sudah dibuat: `SHOW TABLES LIKE 'tb_produk_orderkuota'`
2. Cek apakah ada data: `SELECT COUNT(*) FROM tb_produk_orderkuota`
3. Pastikan file JSON valid formatnya
4. Cek error log PHP untuk detail error

### Produk tidak muncul di halaman tambah transaksi

1. Pastikan `id_bayar` sudah ter-mapping dengan benar
2. Cek apakah produk memiliki `status = 1` (aktif)
3. Pastikan JavaScript di halaman berjalan dengan benar (cek console browser)

### Mapping kategori tidak sesuai

Edit file `orderkuota/import_produk.php` dan ubah array `$kategori_mapping` sesuai kebutuhan.

## File yang Dibuat/Dimodifikasi

1. `orderkuota/create_table_produk.sql` - SQL untuk membuat tabel
2. `orderkuota/import_produk.php` - Script import data dari JSON
3. `libs/produk_helper.php` - Helper functions untuk produk
4. `orderkuota/produk.php` - Halaman untuk melihat daftar produk
5. `transaksi/tambah.php` - Dimodifikasi untuk menampilkan produk
6. `transaksi/get_produk.php` - Endpoint AJAX untuk mengambil produk

## Catatan Penting

- Pastikan file JSON menggunakan format yang benar (array di dalam key "value")
- Import data bisa memakan waktu beberapa detik untuk data yang besar
- Produk dengan `status = 0` tidak akan ditampilkan di halaman user (hanya di admin)
- Mapping kategori ke jenis bayar bisa disesuaikan sesuai kebutuhan


