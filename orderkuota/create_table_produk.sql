-- Table structure for table `tb_produk_orderkuota`
-- Tabel untuk menyimpan produk dan harga detail dari OrderKuota

CREATE TABLE IF NOT EXISTS `tb_produk_orderkuota` (
  `id_produk` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) NOT NULL,
  `keterangan` text NOT NULL,
  `produk` varchar(255) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `harga` decimal(15,2) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT '1 = Aktif, 0 = Tidak Aktif',
  `id_bayar` int(11) DEFAULT NULL COMMENT 'Relasi ke tb_jenisbayar',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_produk`),
  UNIQUE KEY `unique_kode` (`kode`),
  KEY `idx_kategori` (`kategori`),
  KEY `idx_id_bayar` (`id_bayar`),
  KEY `idx_status` (`status`),
  KEY `idx_harga` (`harga`),
  CONSTRAINT `fk_produk_jenisbayar` FOREIGN KEY (`id_bayar`) REFERENCES `tb_jenisbayar` (`id_bayar`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


