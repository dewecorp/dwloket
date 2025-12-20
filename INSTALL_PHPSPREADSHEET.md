# Cara Install PhpSpreadsheet Library

## Opsi 1: Menggunakan Composer (Disarankan)

### Install Composer (jika belum ada)
1. Download Composer dari: https://getcomposer.org/download/
2. Atau jika menggunakan Laragon, Composer biasanya sudah terinstall
3. Cek dengan menjalankan: `composer --version`

### Install PhpSpreadsheet
```bash
cd h:\laragon\www\dwloket_ok
composer install
```

Atau jika hanya ingin install PhpSpreadsheet:
```bash
composer require phpoffice/phpspreadsheet
```

## Opsi 2: Download Manual

1. Download PhpSpreadsheet dari: https://github.com/PHPOffice/PhpSpreadsheet/releases
2. Download versi terbaru (zip file)
3. Extract ke folder: `vendor/phpoffice/phpspreadsheet`
4. Pastikan struktur folder: `vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/`

## Opsi 3: Menggunakan Laragon

Jika menggunakan Laragon:
1. Buka terminal di folder project
2. Jalankan: `composer install`
3. Atau: `composer require phpoffice/phpspreadsheet`

## Verifikasi Install

Setelah install, cek apakah file berikut ada:
- `vendor/autoload.php`
- `vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php`

Jika file-file tersebut ada, library sudah terinstall dengan benar.

## Catatan

- Template Excel akan tersedia jika PhpSpreadsheet terinstall
- Jika belum terinstall, template akan berupa CSV (tetap bisa digunakan)
- File CSV bisa dibuka di Excel, diisi data, lalu disimpan sebagai Excel (.xlsx)




