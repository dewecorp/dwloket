-- Database Backup
-- Generated: 2026-02-15 19:35:47
-- Database: dwloket

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


-- --------------------------------------------------------
-- Table structure for table `admin_activity_logs`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `admin_activity_logs`;

CREATE TABLE `admin_activity_logs` (
  `id_log` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL DEFAULT '0',
  `username` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `nama_user` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
  `action` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `module` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `ip_address` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_module` (`module`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=268 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `admin_activity_logs`

INSERT INTO `admin_activity_logs` (`id_log`, `user_id`, `username`, `nama_user`, `action`, `module`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
('263', '2', 'admin', 'NUR HUDA', 'login', 'system', 'User berhasil login ke sistem', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-15 13:10:45'),
('264', '2', 'admin', 'NUR HUDA', 'create', 'transaksi', 'Menambah transaksi: KHOLIS S/KALIS (ID: 520020509342)', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-15 13:11:58'),
('265', '2', 'admin', 'NUR HUDA', 'update', 'saldo', 'Saldo dikurangi: Rp 104.000 - Transaksi: KHOLIS S/KALIS (ID: 520020509342) - PLN Pasca Bayar', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-15 13:11:58'),
('266', '2', 'admin', 'NUR HUDA', 'update', 'saldo', 'Saldo dikurangi: ID 10 dari Rp 424.500 menjadi Rp 320.500', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-15 13:11:58'),
('267', '2', 'admin', 'NUR HUDA', 'delete', 'backup', 'Menghapus backup: backup_2025-12-18_09-13-04.sql', '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/144.0.0.0 Safari/537.36 Edg/144.0.0.0', '2026-02-15 19:35:42');


-- --------------------------------------------------------
-- Table structure for table `orderkuota_deposit`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `orderkuota_deposit`;

CREATE TABLE `orderkuota_deposit` (
  `id_deposit` int NOT NULL AUTO_INCREMENT,
  `tgl` datetime NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ref_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `transaction_id` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `status` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `keterangan` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_deposit`),
  KEY `idx_tgl` (`tgl`),
  KEY `idx_ref_id` (`ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `orderkuota_deposit`

-- No data in table `orderkuota_deposit`


-- --------------------------------------------------------
-- Table structure for table `pelanggan`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pelanggan`;

CREATE TABLE `pelanggan` (
  `id_pelanggan` int NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `no_idpel` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_pelanggan`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `pelanggan`

INSERT INTO `pelanggan` (`id_pelanggan`, `nama`, `no_idpel`) VALUES
('2', 'NUR HUDA', '45117500426'),
('3', 'TPQ ASSALAMAH', '14311140462'),
('5', 'DIMYATI', '86030038441'),
('6', 'HISYAM AHMAD', '520020664080'),
('9', 'SRI BATHI', '520020784796'),
('10', 'HARTANTO', '14338468755'),
('11', 'SARKAWI', '520020509931'),
('12', 'SYARONI', '520020786612'),
('13', 'SUBIYANTO', '520021150627'),
('15', 'DARJO KUMATUN', '520020509213'),
('16', 'SUPARDI', '520020509495'),
('17', 'AMINUDDIN', '32013850485'),
('18', 'SITI NGATEMI', '520021307881'),
('19', 'KHOLIS S/KALIS', '520020509342'),
('20', 'KASENAN', '520020732762'),
('21', 'MUHDI/JUMANAH', '520020509375'),
('22', 'SUDARLIM', '14419375788'),
('23', 'MARKAM MARIYAM', '520020509509'),
('25', 'JUMADI', '520020509168'),
('28', 'DARSONO', '520021070308'),
('29', 'MBAK SITI XL', '085936107927'),
('31', 'MUHAMMAD NURROIN', '082137145353'),
('32', 'DEWI ANISAH', '081227999566'),
('33', 'MBAK SITI PULSA', '081325587075'),
('39', 'PAK ALI', '45028196124'),
('40', 'PARTI/HARYANTO', '56212261889'),
('41', 'KASTIMAH', '520021067899'),
('42', 'YONO ARYANTO', '520020510237');


-- --------------------------------------------------------
-- Table structure for table `tb_jenisbayar`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `tb_jenisbayar`;

CREATE TABLE `tb_jenisbayar` (
  `id_bayar` int NOT NULL AUTO_INCREMENT,
  `jenis_bayar` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_bayar`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `tb_jenisbayar`

INSERT INTO `tb_jenisbayar` (`id_bayar`, `jenis_bayar`) VALUES
('1', 'Token PLN'),
('2', 'PLN Pasca Bayar'),
('3', 'Pulsa Telkomsel'),
('4', 'Shopee Pay'),
('5', 'Data Internet Telkomsel'),
('6', 'PDAM '),
('7', 'Data Internet 3'),
('8', 'Data Internet AXIS'),
('9', 'Data Internet Smartfren'),
('10', 'Data Internet XL'),
('11', 'Data Internet Indosat'),
('12', 'Indihome'),
('13', 'Wifi ID'),
('15', 'E-Mandiri'),
('16', 'Transfer Uang'),
('17', 'Pulsa XL'),
('18', 'Pulsa AXIS'),
('19', 'Pulsa Indosat'),
('20', 'Pulsa TRI'),
('21', 'Pulsa SMARTFREN'),
('22', 'Grab Ovo'),
('23', 'BPJS Ketenagakerjaan'),
('24', 'BPJS Kesehatan'),
('25', 'E-Toll'),
('28', 'BRIZZI');


-- --------------------------------------------------------
-- Table structure for table `tb_produk_orderkuota`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `tb_produk_orderkuota`;

CREATE TABLE `tb_produk_orderkuota` (
  `id_produk` int NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `produk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `harga` decimal(15,2) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `id_bayar` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_produk`),
  UNIQUE KEY `unique_kode` (`kode`),
  KEY `idx_kategori` (`kategori`),
  KEY `idx_id_bayar` (`id_bayar`),
  KEY `idx_status` (`status`),
  KEY `idx_harga` (`harga`)
) ENGINE=InnoDB AUTO_INCREMENT=3768 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `tb_produk_orderkuota`

INSERT INTO `tb_produk_orderkuota` (`id_produk`, `kode`, `produk`, `kategori`, `harga`, `status`, `id_bayar`, `created_at`, `updated_at`) VALUES
('35', 'A5', 'Axis 5.000', 'PULSA', '5847.00', '1', '3', '2025-12-20 05:03:09', '2025-12-20 05:03:09'),
('36', 'A10', 'Axis 10.000', 'PULSA', '10838.00', '1', '3', '2025-12-20 05:03:09', '2025-12-20 05:03:09'),
('37', 'A15', 'Axis 15.000', 'PULSA', '14993.00', '0', '3', '2025-12-20 05:03:09', '2025-12-24 10:10:38'),
('38', 'AXTP20', 'Axis Transfer 20.000', 'PULSA', '19390.00', '1', '3', '2025-12-20 05:03:09', '2025-12-20 05:03:09'),
('39', 'A25', 'Axis 25.000', 'PULSA', '24930.00', '1', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('40', 'A30', 'Axis 30.000', 'PULSA', '29940.00', '1', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('41', 'A40', 'Axis 40.000', 'PULSA', '39974.00', '1', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('42', 'A50', 'Axis 50.000', 'PULSA', '49929.00', '1', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('43', 'A60', 'Axis 60.000', 'PULSA', '59945.00', '1', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('44', 'A70', 'Axis 70.000', 'PULSA', '69928.00', '1', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('45', 'A80', 'Axis 80.000', 'PULSA', '79935.00', '1', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('46', 'A90', 'Axis 90.000', 'PULSA', '89918.00', '1', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('47', 'A100', 'Axis 100.000', 'PULSA', '99634.00', '1', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('48', 'A150', 'Axis 150.000', 'PULSA', '149950.00', '1', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('49', 'A200', 'Axis 200.000', 'PULSA', '199318.00', '1', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('50', 'A300', 'Axis H2H 300.000', 'PULSA', '299202.00', '1', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('51', 'A500', 'Axis H2H 500.000', 'PULSA', '498670.00', '1', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('52', 'A1JT', 'Axis H2H 1.000.000', 'PULSA', '996840.00', '1', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('53', 'BYU1', 'By U 1.000', 'PULSA', '1825.00', '0', '3', '2025-12-20 05:03:10', '2025-12-20 05:03:10'),
('54', 'BYU2', 'By U 2.000', 'PULSA', '2775.00', '1', '3', '2025-12-20 05:03:11', '2025-12-20 05:03:11'),
('55', 'BYU3', 'By U 3.000', 'PULSA', '3785.00', '1', '3', '2025-12-20 05:03:11', '2025-12-20 05:03:11'),
('56', 'BYU4', 'By U 4.000', 'PULSA', '4775.00', '1', '3', '2025-12-20 05:03:11', '2025-12-20 05:03:11'),
('57', 'BYU5', 'By U 5.000', 'PULSA', '5280.00', '1', '3', '2025-12-20 05:03:11', '2025-12-20 05:03:11'),
('58', 'BYU10', 'By U 10.000', 'PULSA', '10180.00', '1', '3', '2025-12-20 05:03:11', '2025-12-20 05:03:11'),
('59', 'BYU15', 'By U 15.000', 'PULSA', '15025.00', '1', '3', '2025-12-20 05:03:11', '2025-12-20 05:03:11'),
('3752', 'BYRPLN', 'PLN Pasca Bayar', 'PLN PASCA', '10.00', '1', NULL, '2025-12-20 20:57:29', '2025-12-20 20:57:29'),
('3753', 'PLNPRA20', 'Token PLN 20.000', 'PLN PRA', '23000.00', '1', NULL, '2025-12-20 21:11:05', '2025-12-20 21:11:05'),
('3754', 'PLNPRA50', 'Token PLN 50.000', 'PLN PRA', '53000.00', '1', NULL, '2025-12-26 18:04:22', '2025-12-26 18:04:22'),
('3755', 'PLNPRA100', 'Token PLN 100.000', 'PLN PRA', '105000.00', '1', NULL, '2025-12-26 18:06:04', '2025-12-26 18:06:04'),
('3756', 'PLNNONTAG', 'PLN NON TAGLIST', 'PLN NON TAGLIST', '10.00', '1', NULL, '2025-12-26 18:08:49', '2025-12-26 18:08:49'),
('3757', 'PLNPRA200', 'Token PLN 200.000', 'PLN PRA', '205000.00', '1', NULL, '2025-12-26 20:54:51', '2025-12-26 20:54:51'),
('3758', 'TSEL5', 'Pulsa Telkomsel 5000', 'PULSA TSEL', '7000.00', '1', NULL, '2025-12-29 13:43:56', '2025-12-29 13:43:56'),
('3759', 'TSEL10', 'Pulsa Telkomsel 10000', 'PULSA TSEL', '12000.00', '1', NULL, '2025-12-29 13:44:36', '2025-12-29 13:44:36'),
('3760', 'TSEL15', 'Pulsa Telkomsel 15000', 'PULSA TSEL', '17000.00', '1', NULL, '2025-12-29 13:44:59', '2025-12-29 13:44:59'),
('3761', 'TSEL20', 'Pulsa Telkomsel 20000', 'PULSA TSEL', '22000.00', '1', NULL, '2025-12-29 13:45:25', '2025-12-29 13:45:25'),
('3762', 'TSEL50', 'Pulsa Telkomsel 50000', 'PULSA TSEL', '52000.00', '1', NULL, '2025-12-29 13:45:49', '2025-12-29 13:45:49'),
('3763', 'TSEL100', 'Pulsa Telkomsel 100000', 'PULSA TSEL', '99000.00', '1', NULL, '2025-12-29 14:22:54', '2025-12-29 14:23:58'),
('3764', 'TSEL25', 'Pulsa Telkomsel 25000', 'PULSA TSEL', '27000.00', '1', NULL, '2025-12-29 20:11:53', '2025-12-29 20:11:53'),
('3765', 'TSEL200000', 'Pulsa Telkomsel 200000', 'PULSA TSEL', '199000.00', '1', NULL, '2025-12-29 20:16:24', '2025-12-29 20:16:24'),
('3766', 'TSEL250000', 'Pulsa Telkomsel 250000', 'PULSA TSEL', '245000.00', '1', NULL, '2025-12-29 20:20:25', '2025-12-29 20:20:25'),
('3767', 'PLNPRA500', 'Token PLN 500.000', 'PLN PRA', '505000.00', '1', NULL, '2025-12-30 03:59:46', '2025-12-30 03:59:46');


-- --------------------------------------------------------
-- Table structure for table `tb_saldo`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `tb_saldo`;

CREATE TABLE `tb_saldo` (
  `id_saldo` int NOT NULL AUTO_INCREMENT,
  `tgl` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `saldo` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_saldo`)
) ENGINE=InnoDB AUTO_INCREMENT=41 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `tb_saldo`

INSERT INTO `tb_saldo` (`id_saldo`, `tgl`, `saldo`) VALUES
('10', '2021-03-22', '320500'),
('11', '2021-03-30', '150000'),
('12', '2021-04-05', '150000'),
('13', '2021-04-10', '150000'),
('14', '2021-04-20', '250000'),
('15', '2021-04-23', '1000000'),
('16', '2021-05-05', '100000'),
('17', '2021-05-20', '350000'),
('18', '2021-05-23', '170000'),
('19', '2021-05-29', '130000'),
('20', '2021-05-30', '200000'),
('23', '2021-06-20', '400000'),
('24', '2021-06-27', '300000'),
('25', '2021-07-21', '350000'),
('26', '2021-07-26', '400000'),
('27', '2021-08-21', '300000'),
('28', '2021-08-26', '180000'),
('32', '2025-12-26', '-23000'),
('33', '2025-12-26', '-87000'),
('34', '2025-12-26', '-16000'),
('35', '2025-12-27', '-53000'),
('36', '2025-12-27', '53000'),
('37', '2025-12-27', '-53000'),
('38', '2025-12-27', '-65000'),
('39', '2025-12-28', '-100000'),
('40', '2026-01-27', '16500');


-- --------------------------------------------------------
-- Table structure for table `tb_saldo_akhir`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `tb_saldo_akhir`;

CREATE TABLE `tb_saldo_akhir` (
  `id_saldo_akhr` int NOT NULL AUTO_INCREMENT,
  `saldo_masuk` int NOT NULL,
  `saldo_keluar` int NOT NULL,
  PRIMARY KEY (`id_saldo_akhr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `tb_saldo_akhir`

-- No data in table `tb_saldo_akhir`


-- --------------------------------------------------------
-- Table structure for table `tb_user`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `tb_user`;

CREATE TABLE `tb_user` (
  `id_user` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `foto` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` enum('admin','user') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `tb_user`

INSERT INTO `tb_user` (`id_user`, `username`, `password`, `nama`, `email`, `foto`, `level`) VALUES
('2', 'admin', '$2y$10$Fkuw0EjnmaeX4FeKiqTVn.5PGePBBUluzv.CVl9u9wcBFXR.QNSfW', 'NUR HUDA', 'ibnu.hasan3@gmail.com', 'foto-1614766465.JPG', 'admin'),
('3', 'admin2', 'admin2', 'HUDA NUR', 'hudadotkom@gmail.com', 'foto-1614779727.jpg', 'admin');


-- --------------------------------------------------------
-- Table structure for table `transaksi`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `transaksi`;

CREATE TABLE `transaksi` (
  `id_transaksi` int NOT NULL AUTO_INCREMENT,
  `tgl` date NOT NULL,
  `idpel` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `produk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `id_bayar` int NOT NULL,
  `harga` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('Lunas','Belum') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ket` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id_transaksi`),
  KEY `id_bayar` (`id_bayar`),
  CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_bayar`) REFERENCES `tb_jenisbayar` (`id_bayar`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=431 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table `transaksi`

INSERT INTO `transaksi` (`id_transaksi`, `tgl`, `idpel`, `nama`, `produk`, `id_bayar`, `harga`, `status`, `ket`) VALUES
('309', '2025-02-17', '520020509342', 'KHOLIS S/KALIS', NULL, '2', '45500', 'Lunas', ''),
('310', '2025-02-18', '520021150627', 'SUBIYANTO', NULL, '2', '24000', 'Lunas', ''),
('311', '2025-02-20', '520020664080', 'HISYAM AHMAD', NULL, '2', '8500', 'Lunas', ''),
('312', '2025-02-21', '520020509375', 'MUHDI/JUMANAH', NULL, '2', '31000', 'Lunas', ''),
('313', '2025-02-23', '14338468755', 'HARTANTO', NULL, '1', '52000', 'Lunas', ''),
('314', '2025-02-23', '520020509168', 'JUMADI', NULL, '2', '25000', 'Lunas', ''),
('315', '2025-02-24', '520020509213', 'DARJO/KUMATUN', NULL, '2', '19000', 'Lunas', ''),
('316', '2025-03-17', '520020509375', 'MUHDI/JUMANAH', NULL, '2', '30000', 'Lunas', ''),
('317', '2025-03-17', '520020509342', 'KHOLIS S/KALIS', NULL, '2', '49000', 'Lunas', ''),
('318', '2025-03-20', '520021070308', 'DARSONO', NULL, '2', '8000', 'Lunas', ''),
('319', '2025-03-21', '520020509168', 'JUMADI', NULL, '2', '30000', 'Lunas', ''),
('320', '2025-03-21', '520020509213', 'DARJO/KUMATUN', NULL, '2', '18500', 'Lunas', ''),
('321', '2025-03-24', '520020509509', 'MARKAM MARIYAM', NULL, '2', '14000', 'Lunas', ''),
('322', '2025-04-16', '520020509342', 'KHOLIS S/KALIS', NULL, '2', '106000', 'Lunas', ''),
('323', '2025-04-19', '520020509375', 'MUHDI/JUMANAH', NULL, '2', '70000', 'Lunas', ''),
('324', '2025-04-19', '520021070308', 'DARSONO', NULL, '2', '12000', 'Lunas', ''),
('325', '2025-04-19', '520021150627', 'SUBIYANTO', NULL, '2', '60000', 'Lunas', ''),
('326', '2025-04-21', '520020509168', 'JUMADI', NULL, '2', '58000', 'Lunas', ''),
('327', '2025-04-23', '520020509213', 'DARJO/KUMATUN', NULL, '2', '32000', 'Lunas', ''),
('328', '2025-05-16', '520020509342', 'KHOLIS S/KALIS', NULL, '2', '87000', 'Lunas', ''),
('329', '2025-05-19', '520020509168', 'JUMADI', NULL, '2', '59000', 'Lunas', ''),
('330', '2025-05-21', '520021150627', 'SUBIYANTO', NULL, '2', '61000', 'Lunas', ''),
('331', '2025-05-24', '520020509213', 'DARJO/KUMATUN', NULL, '2', '32000', 'Lunas', ''),
('332', '2025-05-29', '520021070308', 'DARSONO', NULL, '2', '14500', 'Lunas', ''),
('333', '2025-06-16', '520020509342', 'KHOLIS S/KALIS', NULL, '2', '106000', 'Lunas', ''),
('334', '2025-07-17', '520020509168', 'JUMADI', NULL, '2', '55000', 'Lunas', ''),
('335', '2025-07-18', '520020509342', 'KHOLIS S/KALIS', NULL, '2', '97000', 'Lunas', ''),
('336', '2025-07-18', '520020664080', 'HISYAM AHMAD', NULL, '2', '12000', 'Lunas', ''),
('337', '2025-07-19', '14419375788', 'SUDARLIM', NULL, '1', '53000', 'Lunas', '3899-1329-6229-2399-0846'),
('338', '2025-07-19', '520020509375', 'MUHDI/JUMANAH', NULL, '2', '70000', 'Lunas', ''),
('339', '2025-07-21', '520021070308', 'DARSONO', NULL, '2', '13000', 'Lunas', ''),
('340', '2025-07-23', '520020509213', 'DARJO/KUMATUN', NULL, '2', '32000', 'Lunas', ''),
('341', '2025-07-25', '520020509495', 'SUPARDI', NULL, '2', '71000', 'Lunas', ''),
('342', '2025-07-25', '520020509509', 'MARKAM MARIYAM', NULL, '2', '17000', 'Lunas', ''),
('343', '2025-07-26', '520021150627', 'SUBIYANTO', NULL, '2', '61000', 'Lunas', ''),
('344', '2025-07-31', '520020732762', 'KASENAN', NULL, '2', '98000', 'Lunas', ''),
('345', '2025-08-16', '520020509342', 'KHOLIS S/KALIS', NULL, '2', '92000', 'Lunas', ''),
('346', '2025-08-18', '14419375788', 'SUDARLIM', NULL, '1', '53000', 'Belum', '4292-1633-5908-3100-7470'),
('347', '2025-08-20', '520020509375', 'MUHDI/JUMANAH', NULL, '2', '70000', 'Lunas', ''),
('348', '2025-08-21', '520020664080', 'HISYAM AHMAD', NULL, '2', '16000', 'Lunas', ''),
('349', '2025-08-21', '520021150627', 'SUBIYANTO', NULL, '2', '56000', 'Lunas', ''),
('350', '2025-08-21', '520020509168', 'JUMADI', NULL, '2', '48000', 'Lunas', ''),
('351', '2025-08-22', '520021070308', 'DARSONO', NULL, '2', '15000', 'Lunas', ''),
('352', '2025-08-25', '520020509213', 'DARJO/KUMATUN', NULL, '2', '30000', 'Lunas', ''),
('353', '2025-09-06', '14338468755', 'HARTANTO', NULL, '1', '23000', 'Lunas', '4871-3806-4465-3345-6316'),
('354', '2025-09-15', '520020509342', 'KHOLIS S/KALIS', NULL, '2', '70000', 'Lunas', ''),
('355', '2025-09-15', '520021150627', 'SUBIYANTO', NULL, '2', '60000', 'Lunas', ''),
('356', '2025-09-15', '520020664080', 'HISYAM AHMAD', NULL, '2', '12000', 'Lunas', ''),
('357', '2025-09-17', '520020509375', 'MUHDI/JUMANAH', NULL, '2', '73000', 'Lunas', ''),
('358', '2025-09-19', '14338468755', 'HARTANTO', NULL, '1', '53000', 'Lunas', '2121-0306-9505-0301-7857'),
('359', '2025-09-25', '520021070308', 'DARSONO', NULL, '2', '17000', 'Lunas', ''),
('360', '2025-09-25', '14338468755', 'HARTANTO', NULL, '1', '23000', 'Lunas', '3340-9493-2842-8624-0302'),
('361', '2025-09-26', '520020509213', 'DARJO/KUMATUN', NULL, '2', '32000', 'Lunas', ''),
('362', '2025-09-27', '520020509168', 'JUMADI', NULL, '2', '61000', 'Lunas', ''),
('363', '2025-10-14', '520020509342', 'KHOLIS S/KALIS', NULL, '2', '106000', 'Lunas', ''),
('364', '2025-10-18', '520020509375', 'MUHDI/JUMANAH', NULL, '2', '78000', 'Lunas', ''),
('367', '2025-10-20', '520021150627', 'SUBIYANTO', NULL, '2', '58000', 'Lunas', ''),
('369', '2025-10-23', '14338468755', 'HARTANTO', NULL, '1', '23000', 'Lunas', '2141-2236-1951-9872-2098'),
('370', '2025-10-23', '520021070308', 'DARSONO', NULL, '2', '16000', 'Lunas', ''),
('371', '2025-10-25', '520020509495', 'SUPARDI', NULL, '2', '81000', 'Lunas', ''),
('372', '2025-10-25', '520020509509', 'MARKAM MARIYAM', NULL, '2', '19000', 'Lunas', ''),
('373', '2025-10-30', '520020732762', 'KASENAN', NULL, '2', '84000', 'Lunas', ''),
('374', '2025-10-30', '520020510237', 'YONO ARYANTO', NULL, '2', '125000', 'Lunas', ''),
('375', '2025-11-08', '14338468755', 'HARTANTO', NULL, '1', '23000', 'Lunas', '5694-1702-3231-6230-3968'),
('376', '2025-11-16', '520020509342', 'KHOLIS S/KALIS', NULL, '2', '96000', 'Lunas', ''),
('377', '2025-11-16', '14338468755', 'HARTANTO', NULL, '1', '53000', 'Lunas', '3922-7160-3151-2562-9962'),
('378', '2025-11-19', '520021070308', 'DARSONO', NULL, '2', '13000', 'Lunas', ''),
('381', '2025-11-23', '520020509168', 'JUMADI', NULL, '2', '68000', 'Lunas', ''),
('382', '2025-11-24', '14419375788', 'SUDARLIM', NULL, '1', '53000', 'Lunas', '2884-9399-3208-2842-3224'),
('383', '2025-11-26', '520020509495', 'SUPARDI', NULL, '2', '79000', 'Lunas', ''),
('384', '2025-11-26', '520020509509', 'MARKAM MARIYAM', NULL, '2', '17000', 'Lunas', ''),
('385', '2025-11-29', '520021067899', 'KASTIMAH', NULL, '2', '161000', 'Lunas', ''),
('386', '2025-12-01', '14338468755', 'HARTANTO', NULL, '1', '23000', 'Lunas', '3681-8740-9912-7760-6852'),
('387', '2025-12-08', '14338468755', 'HARTANTO', NULL, '1', '105000', 'Lunas', '0451-7080-5457-1539-3623'),
('388', '2025-12-17', '520020509342', 'KHOLIS S/KALIS', NULL, '2', '105000', 'Lunas', ''),
('408', '2025-12-23', '520021070308', 'DARSONO', 'PLN Pasca Bayar', '1', '16000', 'Lunas', 'BYRPLN - PLN Pasca Bayar'),
('409', '2025-12-23', '520020664080', 'HISYAM AHMAD', 'PLN Pasca Bayar', '1', '22000', 'Lunas', 'BYRPLN - PLN Pasca Bayar'),
('410', '2025-12-24', '520020509213', 'DARJO KUMATUN', 'BYRPLN', '1', '30000', 'Lunas', 'PLN Pasca Bayar'),
('411', '2025-12-25', '45117500426', 'NUR HUDA', 'PLNPRA20', '1', '103000', 'Lunas', '0088-8173-1943-3627-6907'),
('412', '2025-12-26', '32013850485', 'AMINUDDIN', 'Token PLN 20.000', '1', '23000', 'Lunas', 'PLNPRA20 - Token PLN 20.000'),
('413', '2025-12-26', '520020509495', 'SUPARDI', 'PLN Pasca Bayar', '1', '87000', 'Lunas', 'BYRPLN - PLN Pasca Bayar'),
('414', '2025-12-26', '520020509509', 'MARKAM MARIYAM', 'PLN Pasca Bayar', '1', '16000', 'Lunas', 'BYRPLN - PLN Pasca Bayar'),
('415', '2025-12-27', '14419375788', 'SUDARLIM', 'PLNPRA50', '1', '53000', 'Lunas', '6756-7053-2120-4573-6964'),
('416', '2025-12-27', '520020509168', 'JUMADI', 'PLN Pasca Bayar', '1', '65000', 'Lunas', 'BYRPLN - PLN Pasca Bayar'),
('417', '2025-12-28', '32013850485', 'AMINUDDIN', 'PLN Pasca Bayar', '1', '100000', 'Lunas', 'BYRPLN - PLN Pasca Bayar'),
('418', '2025-12-29', '082137145353', 'MUHAMMAD NURROIN', 'Pulsa Telkomsel 50000', '1', '52000', 'Lunas', 'TSEL50 - Pulsa Telkomsel 50000'),
('419', '2026-01-14', '520020509342', 'KHOLIS S/KALIS', 'PLN Pasca Bayar', '1', '105000', 'Lunas', 'BYRPLN - PLN Pasca Bayar'),
('420', '2026-01-23', '520020664080', 'HISYAM AHMAD', 'PLN Pasca Bayar', '1', '30000', 'Lunas', 'BYRPLN - PLN Pasca Bayar'),
('421', '2026-01-24', '520020509168', 'JUMADI', 'PLN Pasca Bayar', '1', '60000', 'Lunas', 'BYRPLN - PLN Pasca Bayar'),
('422', '2026-01-24', '520020509213', 'DARJO KUMATUN', 'PLN Pasca Bayar', '1', '32000', 'Lunas', 'BYRPLN - PLN Pasca Bayar'),
('423', '2026-01-27', '520020509509', 'MARKAM MARIYAM', 'BYRPLN', '1', '17000', 'Lunas', 'PLN Pasca Bayar'),
('424', '2026-01-27', '520020509495', 'SUPARDI', 'PLN Pasca Bayar', '1', '86000', 'Lunas', 'BYRPLN - PLN Pasca Bayar'),
('425', '2026-01-28', '14338468755', 'HARTANTO', 'Token PLN 20.000', '1', '23000', 'Lunas', '3668-6747-4802-1441-7047'),
('426', '2026-01-31', '520021067899', 'KASTIMAH', 'PLN Pasca Bayar', '1', '153000', 'Lunas', 'BYRPLN - PLN Pasca Bayar'),
('427', '2026-02-04', '14338468755', 'HARTANTO', 'Token PLN 20.000', '1', '23000', 'Lunas', '5329-8284-2502-3212-3718'),
('428', '2026-02-06', '14419375788', 'SUDARLIM', 'Token PLN 100.000', '1', '105000', 'Lunas', 'PLNPRA100 - Token PLN 100.000'),
('429', '2026-02-11', '14338468755', 'HARTANTO', 'Token PLN 50.000', '1', '53000', 'Lunas', 'PLNPRA50 - Token PLN 50.000'),
('430', '2026-02-15', '520020509342', 'KHOLIS S/KALIS', 'PLN Pasca Bayar', '1', '104000', 'Lunas', 'BYRPLN - PLN Pasca Bayar');

COMMIT;
