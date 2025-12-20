# Panduan Import Produk Excel

## Cara Install Library PhpSpreadsheet

### Opsi 1: Menggunakan Composer (Disarankan)
```bash
cd h:\laragon\www\dwloket_ok
composer install
```

Jika composer tidak terdeteksi, install Composer terlebih dahulu dari: https://getcomposer.org/download/

### Opsi 2: Download Manual
1. Download PhpSpreadsheet dari: https://github.com/PHPOffice/PhpSpreadsheet/releases
2. Extract ke folder `vendor/phpoffice/phpspreadsheet`
3. Download autoload.php atau buat sendiri

### Opsi 3: Menggunakan Laragon
Jika menggunakan Laragon, biasanya composer sudah terinstall. Coba:
```bash
composer require phpoffice/phpspreadsheet
```

## Format File Excel yang Didukung

### Format Excel (.xlsx, .xls)
- **Nama Sheet = Kategori Produk** (contoh: PULSA TELKOMSEL, KUOTA SMARTFREN)
- **Kolom A**: Kode (contoh: T5, SMDC30)
- **Kolom B**: Produk/Nama (contoh: Telkomsel 5.000)
- **Kolom C**: Harga (contoh: 5500)
- **Kolom D**: Status (Aktif/Tidak Aktif atau 1/0)

### Format CSV (.csv)
- **Kolom 1**: Kode
- **Kolom 2**: Produk/Nama
- **Kolom 3**: Harga
- **Kolom 4**: Status

## Cara Menggunakan Template

1. **Download Template**
   - Klik tombol "Download Template (CSV/Excel)" di halaman Import Produk
   - Template akan berisi contoh data untuk berbagai kategori

2. **Isi Data**
   - Buka file template di Excel
   - Isi data sesuai format (Kode, Produk, Harga, Status)
   - Untuk Excel: Pastikan nama sheet sesuai kategori (contoh: PULSA TELKOMSEL)

3. **Import**
   - Kembali ke halaman Import Produk
   - Pilih file yang sudah diisi
   - Klik "Upload & Preview File"
   - Konfirmasi import

## Mapping Kategori ke Jenis Bayar

Sistem akan otomatis memetakan kategori ke jenis bayar:
- PULSA TELKOMSEL → Pulsa Telkomsel
- KUOTA SMARTFREN → Data Internet Smartfren
- KUOTA AXIS → Data Internet AXIS
- TOKEN PLN → Token PLN
- dll.

Jika kategori tidak ditemukan, id_bayar akan di-set NULL (tetap bisa diimport).

## Tips

1. **Gunakan Template**: Selalu gunakan template yang disediakan untuk memastikan format benar
2. **Nama Sheet Penting**: Untuk Excel, nama sheet akan digunakan sebagai kategori
3. **Header Otomatis**: Baris pertama akan otomatis di-skip jika mengandung kata "Kode", "Produk", "Harga", atau "Status"
4. **Harga**: Gunakan angka saja (tanpa titik atau koma), contoh: 5500 bukan 5.500
5. **Status**: Gunakan "Aktif" atau "1" untuk aktif, "Tidak Aktif" atau "0" untuk tidak aktif

## Troubleshooting

### Error: PhpSpreadsheet tidak tersedia
- Install library menggunakan composer: `composer install`
- Atau gunakan format CSV sebagai alternatif

### Data tidak masuk ke database
- Cek log error PHP untuk detail
- Pastikan format file sesuai template
- Pastikan tidak ada karakter khusus yang tidak valid

### Import gagal
- Pastikan file tidak terlalu besar (maksimal 10MB)
- Pastikan format file benar (.xlsx, .xls, atau .csv)
- Pastikan minimal ada 1 baris data setelah header




