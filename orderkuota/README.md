# Integrasi OrderKuota.com

Fitur ini memungkinkan Anda melakukan pembayaran langsung dari aplikasi DW Loket tanpa perlu membuka aplikasi OrderKuota secara terpisah.

## Fitur

1. **Cek Harga** - Cek harga produk sebelum melakukan pembayaran
2. **Cek Saldo** - Lihat saldo OrderKuota Anda
3. **Pembayaran Langsung** - Lakukan pembayaran langsung dari aplikasi
4. **Integrasi Transaksi** - Transaksi otomatis tersimpan ke database
5. **Logging Aktivitas** - Semua transaksi tercatat di activity log

## Setup API Key

1. Buka file `libs/orderkuota_api.php`
2. Cari baris berikut:
   ```php
   private $api_key = ''; // Set API key dari orderkuota.com
   private $api_secret = ''; // Set API secret dari orderkuota.com
   ```
3. Isi dengan API key dan secret yang Anda dapatkan dari OrderKuota.com
4. Atau, Anda bisa set melalui config.php dengan menambahkan:
   ```php
   define('ORDERKUOTA_API_KEY', 'your_api_key_here');
   define('ORDERKUOTA_API_SECRET', 'your_api_secret_here');
   ```

## Cara Menggunakan

1. Buka menu **OrderKuota** di sidebar
2. Pilih jenis produk (PLN, Pulsa, Data, dll)
3. Masukkan nomor tujuan (nomor meteran/listrik untuk PLN, nomor HP untuk Pulsa/Data)
4. Klik **"Cek Harga"** untuk melihat harga
5. Harga akan terisi otomatis
6. Klik **"Bayar Sekarang"** untuk melakukan pembayaran
7. Transaksi akan otomatis tersimpan ke database

## Catatan Penting

- Pastikan saldo OrderKuota mencukupi sebelum melakukan pembayaran
- Semua transaksi yang berhasil akan otomatis tersimpan dengan status "Lunas"
- Reference ID akan otomatis dibuat untuk setiap transaksi
- Semua aktivitas pembayaran akan tercatat di activity log

## Troubleshooting

Jika terjadi error:
1. Pastikan API key dan secret sudah benar
2. Pastikan koneksi internet stabil
3. Cek saldo OrderKuota apakah mencukupi
4. Pastikan nomor tujuan sudah benar sesuai jenis produk





