-- Script untuk menambahkan kolom produk ke tabel transaksi
-- Jalankan script ini di phpMyAdmin atau MySQL client
--
-- PERHATIAN: Script ini WAJIB dijalankan sebelum menggunakan fitur produk di form tambah transaksi
-- Jika kolom produk belum ada, query INSERT akan error

-- Cek apakah kolom sudah ada (optional, untuk menghindari error jika sudah ada)
SET @dbname = DATABASE();
SET @tablename = "transaksi";
SET @columnname = "produk";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (TABLE_SCHEMA = @dbname)
      AND (TABLE_NAME = @tablename)
      AND (COLUMN_NAME = @columnname)
  ) > 0,
  "SELECT 1",
  "ALTER TABLE `transaksi` ADD COLUMN `produk` VARCHAR(255) NULL AFTER `nama`"
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Atau jika ingin langsung menambahkan (akan error jika kolom sudah ada):
-- ALTER TABLE `transaksi` ADD COLUMN `produk` VARCHAR(255) NULL AFTER `nama`;

