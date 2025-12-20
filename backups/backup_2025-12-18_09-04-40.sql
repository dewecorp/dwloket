-- Database Backup
-- Generated: 2025-12-18 09:04:41
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
  `id_log` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL DEFAULT '0',
  `username` varchar(100) NOT NULL DEFAULT '',
  `nama_user` varchar(100) NOT NULL DEFAULT '',
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` text,
  `ip_address` varchar(50) DEFAULT NULL,
  `user_agent` text,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action` (`action`),
  KEY `idx_module` (`module`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=latin1;

-- Dumping data for table `admin_activity_logs`

INSERT INTO `admin_activity_logs` (`id_log`, `user_id`, `username`, `nama_user`, `action`, `module`, `description`, `ip_address`, `user_agent`, `created_at`) VALUES
('1', '0', 'system', 'System', 'test', 'test', 'Test insert setelah membuat tabel', NULL, NULL, '2025-12-17 19:58:35'),
('2', '2', 'admin', 'NUR HUDA', 'test', 'test', 'Test dari cek_logging.php pada 2025-12-17 19:59:16', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 19:59:16'),
('3', '2', 'admin', 'NUR HUDA', 'test', 'test', 'Test dari cek_logging.php pada 2025-12-17 19:59:22', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 19:59:22'),
('4', '2', 'admin', 'NUR HUDA', 'login', 'auth', 'User berhasil login ke sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 19:59:34'),
('5', '2', 'admin', 'NUR HUDA', 'update', 'pelanggan', 'Mengedit pelanggan ID: 15 - Dari: DARJO/KUMATUN 2 (ID: 520020509213) menjadi: DARJO/KUMATUN (ID: 520020509213)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 20:03:26'),
('6', '2', 'admin', 'NUR HUDA', 'update', 'pelanggan', 'Mengedit pelanggan ID: 15 - Dari: DARJO/KUMATUN (ID: 520020509213) menjadi: DARJO/KUMATUN (ID: 520020509213)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 20:03:34'),
('7', '2', 'admin', 'NUR HUDA', 'update', 'pelanggan', 'Mengedit pelanggan ID: 15 - Dari: DARJO/KUMATUN (ID: 520020509213) menjadi: DARJO/KUMATUN (ID: 520020509213)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 20:03:39'),
('8', '2', 'admin', 'NUR HUDA', 'update', 'pelanggan', 'Mengedit pelanggan ID: 7 - Dari: ADNAN JAWAHIR (ID: 520021142578) menjadi: ADNAN JAWAHIR 2 (ID: 520021142578)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 20:06:40'),
('9', '2', 'admin', 'NUR HUDA', 'update', 'pelanggan', 'Mengedit pelanggan ID: 7 - Dari: ADNAN JAWAHIR 2 (ID: 520021142578) menjadi: ADNAN JAWAHIR (ID: 520021142578)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 20:07:00'),
('10', '2', 'admin', 'NUR HUDA', 'delete', 'pelanggan', 'Menghapus pelanggan: ADNAN JAWAHIR (ID: 520021142578)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 20:13:06'),
('11', '2', 'admin', 'NUR HUDA', 'logout', 'auth', 'User logout dari sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 20:27:31'),
('12', '2', 'admin', 'NUR HUDA', 'login', 'auth', 'User berhasil login ke sistem', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 20:27:35'),
('13', '2', 'admin', 'NUR HUDA', 'delete', 'transaksi', 'Menghapus transaksi ID: 380 - DARJO/KUMATUN (ID: 520020509213)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 20:49:44'),
('14', '2', 'admin', 'NUR HUDA', 'delete', 'saldo', 'Menghapus saldo ID: 22 - Rp. 200.000 (Tanggal: 2021-06-17)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 20:51:20'),
('15', '2', 'admin', 'NUR HUDA', 'delete', 'jenisbayar', 'Menghapus jenis pembayaran: BRIZZI', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 20:51:40'),
('16', '2', 'admin', 'NUR HUDA', 'delete', 'transaksi', 'Menghapus transaksi ID: 20 - DIMYATI (ID: 86030038441)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 20:53:00'),
('17', '2', 'admin', 'NUR HUDA', 'delete', 'transaksi', 'Menghapus transaksi ID: 379 - SUBIYANTO (ID: 520021150627)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 20:53:11'),
('18', '2', 'admin', 'NUR HUDA', 'delete', 'saldo', 'Menghapus saldo ID: 21 - Rp. 400.000 (Tanggal: 2021-05-31)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-17 20:57:34'),
('19', '2', 'admin', 'NUR HUDA', 'delete', 'backup', 'Menghapus backup: test_backup_2025-12-17_22-27-54.sql', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-18 07:17:37'),
('20', '2', 'system', 'System', 'delete', 'backup', 'Menghapus backup: test_verify_2025-12-18_00-02-21.sql', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-18 07:32:31'),
('21', '2', 'system', 'System', 'backup', 'database', 'Membuat backup database: backup_2025-12-18_08-58-49.sql (41.36 KB)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-18 08:59:02'),
('22', '2', 'system', 'System', 'backup', 'database', 'Membuat backup database: backup_2025-12-18_08-59-14.sql (41.64 KB)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-18 08:59:27'),
('23', '2', 'system', 'System', 'delete', 'backup', 'Menghapus backup: test_verify_2025-12-18_00-10-28.sql', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-18 08:59:37'),
('24', '2', 'system', 'System', 'backup', 'database', 'Membuat backup database: backup_2025-12-18_09-01-54.sql (42.18 KB)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-18 09:01:58'),
('25', '2', 'system', 'System', 'backup', 'database', 'Membuat backup database: backup_2025-12-18_09-02-04.sql (42.46 KB)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-18 09:02:08'),
('26', '2', 'system', 'System', 'backup', 'database', 'Membuat backup database: backup_2025-12-18_09-02-19.sql (42.74 KB)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-18 09:02:22'),
('27', '2', 'system', 'System', 'delete', 'backup', 'Menghapus backup: backup_2025-12-18_08-37-01.sql', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-18 09:03:30'),
('28', '2', 'system', 'System', 'backup', 'database', 'Membuat backup database: backup_2025-12-18_09-04-32.sql (43.27 KB)', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/143.0.0.0 Safari/537.36 Edg/143.0.0.0', '2025-12-18 09:04:35');


-- --------------------------------------------------------
-- Table structure for table `orderkuota_deposit`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `orderkuota_deposit`;

CREATE TABLE `orderkuota_deposit` (
  `id_deposit` int(11) NOT NULL AUTO_INCREMENT,
  `tgl` datetime NOT NULL,
  `amount` decimal(15,2) NOT NULL,
  `payment_method` varchar(50) DEFAULT NULL,
  `ref_id` varchar(100) DEFAULT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `keterangan` text,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_deposit`),
  KEY `idx_tgl` (`tgl`),
  KEY `idx_ref_id` (`ref_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Dumping data for table `orderkuota_deposit`

-- No data in table `orderkuota_deposit`


-- --------------------------------------------------------
-- Table structure for table `pelanggan`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `pelanggan`;

CREATE TABLE `pelanggan` (
  `id_pelanggan` int(11) NOT NULL AUTO_INCREMENT,
  `nama` varchar(100) NOT NULL,
  `no_idpel` varchar(100) NOT NULL,
  PRIMARY KEY (`id_pelanggan`)
) ENGINE=InnoDB AUTO_INCREMENT=43 DEFAULT CHARSET=latin1;

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
('14', 'NGASIMAN', '520020509367'),
('15', 'DARJO/KUMATUN', '520020509213'),
('16', 'SUPARDI', '520020509495'),
('17', 'AMINUDDIN', '32013850485'),
('18', 'SITI NGATEMI', '520021307881'),
('19', 'KHOLIS S/KALIS', '520020509342'),
('20', 'KASENAN', '520020732762'),
('21', 'MUHDI/JUMANAH', '520020509375'),
('22', 'SUDARLIM', '14419375788'),
('23', 'MARKAM MARIYAM', '520020509509'),
('25', 'JUMADI', '520020509168'),
('27', 'ZAINI/NARNIT', '520020765894'),
('28', 'DARSONO', '520021070308'),
('29', 'MBAK SITI XL', '085936107927'),
('30', 'VIOLA', '083183037136'),
('31', 'MUHAMMAD NURROIN', '082137145353'),
('32', 'DEWI ANISAH', '081227999566'),
('33', 'MBAK SITI PULSA', '081325587075'),
('34', 'SALWA', '087737627807'),
('35', 'IDA ROHALIA', '082314010696'),
('38', 'NN', '86037218053'),
('39', 'PAK ALI', '45028196124'),
('40', 'PARTI/HARYANTO', '56212261889'),
('41', 'KASTIMAH', '520021067899'),
('42', 'YONO ARYANTO', '520020510237');


-- --------------------------------------------------------
-- Table structure for table `tb_jenisbayar`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `tb_jenisbayar`;

CREATE TABLE `tb_jenisbayar` (
  `id_bayar` int(11) NOT NULL AUTO_INCREMENT,
  `jenis_bayar` varchar(100) NOT NULL,
  PRIMARY KEY (`id_bayar`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=latin1;

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
('25', 'E-Toll');


-- --------------------------------------------------------
-- Table structure for table `tb_saldo`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `tb_saldo`;

CREATE TABLE `tb_saldo` (
  `id_saldo` int(11) NOT NULL AUTO_INCREMENT,
  `tgl` varchar(50) NOT NULL,
  `saldo` varchar(100) NOT NULL,
  PRIMARY KEY (`id_saldo`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=latin1;

-- Dumping data for table `tb_saldo`

INSERT INTO `tb_saldo` (`id_saldo`, `tgl`, `saldo`) VALUES
('8', '2021-03-14', '300000'),
('9', '2021-03-17', '400000'),
('10', '2021-03-22', '480000'),
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
('29', '2021-09-08', '150000'),
('30', '2021-09-18', '170000'),
('31', '2024-06-26', '500000');


-- --------------------------------------------------------
-- Table structure for table `tb_saldo_akhir`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `tb_saldo_akhir`;

CREATE TABLE `tb_saldo_akhir` (
  `id_saldo_akhr` int(11) NOT NULL AUTO_INCREMENT,
  `saldo_masuk` int(50) NOT NULL,
  `saldo_keluar` int(50) NOT NULL,
  PRIMARY KEY (`id_saldo_akhr`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Dumping data for table `tb_saldo_akhir`

-- No data in table `tb_saldo_akhir`


-- --------------------------------------------------------
-- Table structure for table `tb_user`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `tb_user`;

CREATE TABLE `tb_user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(50) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `foto` varchar(50) NOT NULL,
  `level` enum('admin','user') NOT NULL,
  PRIMARY KEY (`id_user`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

-- Dumping data for table `tb_user`

INSERT INTO `tb_user` (`id_user`, `username`, `password`, `nama`, `email`, `foto`, `level`) VALUES
('2', 'admin', 'admin', 'NUR HUDA', 'ibnu.hasan3@gmail.com', 'foto-1614766465.JPG', 'admin'),
('3', 'admin2', 'admin2', 'HUDA NUR', 'hudadotkom@gmail.com', 'foto-1614779727.jpg', 'admin');


-- --------------------------------------------------------
-- Table structure for table `transaksi`
-- --------------------------------------------------------

DROP TABLE IF EXISTS `transaksi`;

CREATE TABLE `transaksi` (
  `id_transaksi` int(11) NOT NULL AUTO_INCREMENT,
  `tgl` date NOT NULL,
  `idpel` varchar(100) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `id_bayar` int(11) NOT NULL,
  `harga` varchar(100) NOT NULL,
  `status` enum('Lunas','Belum') NOT NULL,
  `ket` varchar(50) NOT NULL,
  PRIMARY KEY (`id_transaksi`),
  KEY `id_bayar` (`id_bayar`),
  CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`id_bayar`) REFERENCES `tb_jenisbayar` (`id_bayar`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=390 DEFAULT CHARSET=latin1;

-- Dumping data for table `transaksi`

INSERT INTO `transaksi` (`id_transaksi`, `tgl`, `idpel`, `nama`, `id_bayar`, `harga`, `status`, `ket`) VALUES
('21', '2021-01-25', '0001', 'MBAK SITI', '3', '22000', 'Lunas', ''),
('22', '2021-01-26', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', ''),
('23', '2021-01-25', '86037218053', 'NUR HUDA', '16', '13200', 'Belum', ''),
('24', '2021-01-29', '86030038441', 'DIMYATI', '1', '105000', 'Lunas', ''),
('25', '2021-01-30', '86037218053', 'NUR HUDA', '8', '28351', 'Belum', ''),
('26', '2021-01-30', '0003', 'MUHAMMAD NURROIN', '3', '22000', 'Belum', ''),
('27', '2021-01-30', '0003', 'MUHAMMAD NURROIN', '17', '32000', 'Belum', ''),
('28', '2021-02-01', '86037218053', 'NUR HUDA', '8', '30000', 'Belum', ''),
('29', '2021-02-01', '004', 'DEWI ANISAH', '4', '100500', 'Lunas', ''),
('30', '2021-02-01', '004', 'DEWI ANISAH', '4', '50500', 'Lunas', ''),
('31', '2021-02-02', '86037218053', 'NUR HUDA', '5', '15000', 'Belum', ''),
('32', '2021-02-03', '14311140462', 'TPQ ASSALAMAH', '1', '105000', 'Lunas', ''),
('33', '2021-02-06', '002', 'VIOLA', '8', '25000', 'Lunas', ''),
('34', '2021-02-07', '86037218053', 'NUR HUDA', '20', '20000', 'Belum', ''),
('35', '2021-02-08', '004', 'DEWI ANISAH', '3', '20000', 'Belum', ''),
('36', '2021-02-08', '86037218053', 'NUR HUDA', '8', '20000', 'Belum', ''),
('37', '2021-02-09', '085936107927', 'MBAK SITI', '10', '40000', 'Lunas', ''),
('38', '2021-02-13', '86037218053', 'NUR HUDA', '8', '15000', 'Belum', ''),
('39', '2021-02-15', '86030038441', 'DIMYATI', '1', '105000', 'Lunas', ''),
('40', '2021-02-17', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('41', '2021-02-17', '86037218053', 'NUR HUDA', '8', '20000', 'Belum', ''),
('42', '2021-02-19', '520020786612', 'SYARONI', '2', '63000', 'Lunas', ''),
('43', '2021-02-21', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', ''),
('44', '2021-02-21', '520021150627', 'SUBIYANTO', '2', '66000', 'Lunas', ''),
('45', '2021-02-22', '86037218053', 'NUR HUDA', '8', '20000', 'Belum', ''),
('46', '2021-02-23', '081227999566', 'DEWI ANISAH', '3', '22000', 'Lunas', ''),
('47', '2021-03-15', '087737627807', 'SALWA', '10', '62000', 'Lunas', ''),
('48', '2021-03-15', '083183037136', 'VIOLA', '18', '7000', 'Lunas', ''),
('49', '2021-02-26', '86037218053', 'NUR HUDA', '8', '20000', 'Belum', ''),
('51', '2021-03-02', '86030038441', 'DIMYATI', '1', '105000', 'Lunas', ''),
('52', '2021-03-03', '86037218053', 'NUR HUDA', '5', '17000', 'Belum', ''),
('53', '2021-03-03', '86037218053', 'NUR HUDA', '18', '25000', 'Belum', ''),
('54', '2021-03-04', '082137145353', 'MUHAMMAD NURROIN', '3', '22000', 'Lunas', ''),
('55', '2021-03-04', '14311140462', 'TPQ ASSALAMAH', '1', '105000', 'Lunas', ''),
('56', '2021-03-04', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('57', '2021-03-06', '082314010696', 'IDA ROHALIA', '3', '22000', 'Lunas', ''),
('58', '2021-03-06', '082314010696', 'IDA ROHALIA', '20', '52000', 'Lunas', ''),
('59', '2021-03-06', '86037218053', 'NUR HUDA', '8', '20000', 'Belum', ''),
('60', '2021-03-11', '86037218053', 'NUR HUDA', '8', '20000', 'Belum', ''),
('61', '2021-03-15', '085936107927', 'MBAK SITI XL', '10', '40000', 'Lunas', ''),
('62', '2021-03-15', '083183037136', 'VIOLA', '8', '15000', 'Lunas', ''),
('63', '2021-03-14', '14338468755', 'HARTANTO', '1', '53000', 'Lunas', ''),
('64', '2021-03-15', '86037218053', 'NUR HUDA', '8', '20000', 'Belum', ''),
('65', '2021-03-15', '86037218053', 'NUR HUDA', '3', '15000', 'Belum', ''),
('67', '2021-03-17', '86037218053', 'NUR HUDA', '16', '117000', 'Lunas', ''),
('68', '2021-03-18', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('69', '2021-03-20', '86037218053', 'NUR HUDA', '8', '16000', 'Belum', ''),
('70', '2021-03-21', '082314010696', 'IDA ROHALIA', '3', '52000', 'Belum', ''),
('71', '2021-03-22', '520020732762', 'KASENAN', '2', '420000', 'Lunas', ''),
('72', '2021-03-22', '520021150627', 'SUBIYANTO', '2', '61000', 'Lunas', ''),
('73', '2021-03-25', '86037218053', 'NUR HUDA', '8', '22000', 'Belum', ''),
('74', '2021-03-25', '520020509213', 'DARJO/KUMATUN', '2', '20000', 'Lunas', ''),
('75', '2021-03-25', '86037218053', 'NUR HUDA', '3', '22000', 'Belum', ''),
('76', '2021-03-26', '520020786612', 'SYARONI', '2', '63000', 'Lunas', ''),
('77', '2021-03-27', '087737627807', 'SALWA', '10', '62000', 'Lunas', ''),
('78', '2021-03-28', '86037218053', 'NUR HUDA', '8', '15000', 'Belum', ''),
('79', '2021-03-30', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('80', '2021-03-30', '081227999566', 'DEWI ANISAH', '3', '22000', 'Belum', ''),
('81', '2021-03-31', '86030038441', 'DIMYATI', '1', '23000', 'Lunas', ''),
('82', '2021-04-03', '86030038441', 'DIMYATI', '1', '23000', 'Lunas', ''),
('83', '2021-04-05', '86030038441', 'DIMYATI', '1', '105000', 'Lunas', ''),
('84', '2021-04-07', '86037218053', 'NUR HUDA', '8', '16000', 'Belum', ''),
('85', '2021-04-07', '083183037136', 'VIOLA', '8', '15000', 'Lunas', ''),
('86', '2021-04-10', '082137145353', 'MUHAMMAD NURROIN', '3', '22000', 'Belum', ''),
('87', '2021-04-10', '085936107927', 'MBAK SITI XL', '10', '40000', 'Lunas', ''),
('88', '2021-04-18', '520020509509', 'MARKAM MARIYAM', '2', '8000', 'Lunas', ''),
('89', '2021-04-19', '520020509495', 'SUPARDI', '2', '14000', 'Lunas', ''),
('90', '2021-04-19', '86037218053', 'NUR HUDA', '8', '15000', 'Belum', ''),
('91', '2021-04-19', '520021150627', 'SUBIYANTO', '2', '57500', 'Lunas', ''),
('92', '2021-04-20', '520020786612', 'SYARONI', '2', '104000', 'Lunas', ''),
('93', '2021-04-20', '083183037136', 'VIOLA', '8', '25000', 'Lunas', ''),
('94', '2021-04-23', '86037218053', 'NUR HUDA', '8', '20000', 'Belum', ''),
('95', '2021-04-23', '520020784796', 'SRI BATHI', '2', '50500', 'Lunas', ''),
('96', '2021-04-23', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('97', '2021-04-23', '081227999566', 'DEWI ANISAH', '4', '150000', 'Lunas', ''),
('98', '2021-04-25', '081227999566', 'DEWI ANISAH', '4', '75000', 'Lunas', ''),
('99', '2021-04-25', '081227999566', 'DEWI ANISAH', '3', '30000', 'Lunas', ''),
('100', '2021-04-25', '520020664080', 'HISYAM AHMAD', '2', '11000', 'Lunas', ''),
('101', '2021-04-25', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', ''),
('102', '2021-05-05', '86037218053', 'NUR HUDA', '8', '20000', 'Belum', ''),
('103', '2021-05-05', '081227999566', 'DEWI ANISAH', '4', '50000', 'Belum', ''),
('104', '2021-05-09', '14338468755', 'HARTANTO', '1', '53000', 'Lunas', ''),
('105', '2021-05-20', '520020509342', 'KHOLIS S/KALIS', '2', '28500', 'Lunas', ''),
('106', '2021-05-20', '520020664080', 'HISYAM AHMAD', '2', '8500', 'Lunas', ''),
('107', '2021-05-21', '520020786612', 'SYARONI', '2', '86000', 'Lunas', ''),
('108', '2021-05-22', '520020509495', 'SUPARDI', '2', '16500', 'Lunas', ''),
('109', '2021-05-22', '520020509509', 'MARKAM MARIYAM', '2', '10500', 'Lunas', ''),
('110', '2021-05-22', '45013589978', 'SUDARLIM', '1', '53000', 'Belum', ''),
('111', '2021-05-23', '520021150627', 'SUBIYANTO', '2', '65500', 'Lunas', ''),
('112', '2021-05-27', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('113', '2021-05-29', '520020784796', 'SRI BATHI', '2', '60000', 'Lunas', ''),
('114', '2021-05-29', '86030038441', 'DIMYATI', '1', '105000', 'Lunas', ''),
('115', '2021-05-29', '520020509168', 'JUMADI', '2', '18000', 'Lunas', ''),
('116', '2021-06-02', '86037218053', 'NUR HUDA', '8', '20000', 'Belum', ''),
('117', '2021-06-15', '86037218053', 'NUR HUDA', '8', '13000', 'Belum', ''),
('118', '2021-06-15', '86037218053', 'NUR HUDA', '9', '28000', 'Belum', ''),
('119', '2021-06-15', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('120', '2021-06-15', '86030038441', 'DIMYATI', '1', '105000', 'Lunas', ''),
('121', '2021-06-16', '14311140462', 'TPQ ASSALAMAH', '1', '105000', 'Lunas', ''),
('122', '2021-06-17', '14338468755', 'HARTANTO', '1', '53000', 'Lunas', ''),
('123', '2021-06-17', '087737627807', 'SALWA', '10', '62000', 'Lunas', ''),
('124', '2021-06-19', '520021150627', 'SUBIYANTO', '2', '64000', 'Lunas', ''),
('125', '2021-06-20', '520020509495', 'SUPARDI', '2', '15500', 'Lunas', ''),
('126', '2021-06-20', '520020509509', 'MARKAM MARIYAM', '2', '7500', 'Lunas', ''),
('127', '2021-06-20', '520020664080', 'HISYAM AHMAD', '2', '9000', 'Lunas', ''),
('128', '2021-06-20', '081227999566', 'DEWI ANISAH', '4', '110000', 'Lunas', ''),
('129', '2021-06-22', '520020509213', 'DARJO/KUMATUN', '2', '26500', 'Lunas', ''),
('130', '2021-06-25', '520021070308', 'DARSONO', '2', '6500', 'Lunas', ''),
('136', '2021-06-27', '520020509168', 'JUMADI', '2', '18500', 'Lunas', ''),
('140', '2021-06-29', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('141', '2021-06-29', '86030038441', 'DIMYATI', '1', '105000', 'Lunas', ''),
('142', '2021-07-01', '86037218053', 'NUR HUDA', '3', '12000', 'Belum', ''),
('143', '2021-07-04', '14311140462', 'TPQ ASSALAMAH', '1', '105000', 'Lunas', ''),
('144', '2021-07-06', '86037218053', 'NUR HUDA', '8', '20000', 'Belum', ''),
('145', '2021-07-15', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', ''),
('146', '2021-07-17', '087737627807', 'SALWA', '10', '40000', 'Lunas', ''),
('147', '2021-07-17', '520020784796', 'SRI BATHI', '2', '56500', 'Lunas', ''),
('148', '2021-07-17', '86030038441', 'DIMYATI', '1', '105000', 'Lunas', ''),
('149', '2021-07-19', '520020509495', 'SUPARDI', '2', '16000', 'Lunas', ''),
('150', '2021-07-19', '520020509509', 'MARKAM MARIYAM', '2', '6000', 'Lunas', ''),
('151', '2021-07-19', '520020664080', 'HISYAM AHMAD', '2', '10000', 'Lunas', ''),
('152', '2021-07-21', '520021150627', 'SUBIYANTO', '2', '65000', 'Lunas', ''),
('153', '2021-07-21', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('154', '2021-07-22', '45013589978', 'SUDARLIM', '1', '53000', 'Lunas', ''),
('155', '2021-07-23', '520020509168', 'JUMADI', '2', '18500', 'Lunas', ''),
('156', '2021-07-25', '520021070308', 'DARSONO', '2', '8500', 'Lunas', ''),
('157', '2021-07-27', '087737627807', 'SALWA', '10', '60000', 'Lunas', ''),
('158', '2021-07-31', '083183037136', 'VIOLA', '8', '15000', 'Belum', ''),
('159', '2021-07-31', '86030038441', 'DIMYATI', '1', '105000', 'Lunas', ''),
('160', '2021-08-02', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('165', '2021-08-03', '081227999566', 'DEWI ANISAH', '4', '85000', 'Lunas', ''),
('166', '2021-08-08', '082137145353', 'MUHAMMAD NURROIN', '3', '32000', 'Lunas', ''),
('167', '2021-08-09', '081227999566', 'DEWI ANISAH', '3', '20000', 'Belum', ''),
('168', '2021-08-09', '085936107927', 'MBAK SITI XL', '10', '60000', 'Lunas', ''),
('169', '2021-08-13', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('170', '2021-08-16', '86037218053', 'NUR HUDA', '8', '20000', 'Belum', ''),
('171', '2021-08-16', '86030038441', 'DIMYATI', '1', '53000', 'Lunas', ''),
('172', '2021-08-19', '520020784796', 'SRI BATHI', '2', '48000', 'Lunas', ''),
('173', '2021-08-20', '86030038441', 'DIMYATI', '1', '105000', 'Lunas', ''),
('174', '2021-08-20', '520020786612', 'SYARONI', '2', '60500', 'Lunas', ''),
('175', '2021-08-21', '520020509495', 'SUPARDI', '2', '19000', 'Lunas', ''),
('176', '2021-08-21', '520020509509', 'MARKAM MARIYAM', '2', '11000', 'Lunas', ''),
('177', '2021-08-21', '520021150627', 'SUBIYANTO', '2', '68000', 'Lunas', ''),
('178', '2021-08-21', '082137145353', 'MUHAMMAD NURROIN', '3', '32000', 'Lunas', ''),
('179', '2021-08-23', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('180', '2021-08-26', '14338468755', 'HARTANTO', '1', '53000', 'Lunas', ''),
('181', '2021-08-27', '520021070308', 'DARSONO', '2', '9000', 'Lunas', ''),
('182', '2021-08-28', '520020509168', 'JUMADI', '2', '23000', 'Lunas', ''),
('183', '2021-09-02', '082137145353', 'MUHAMMAD NURROIN', '3', '27000', 'Lunas', ''),
('184', '2021-09-08', '86030038441', 'DIMYATI', '1', '105000', 'Lunas', ''),
('185', '2021-09-10', '081325587075', 'MBAK SITI PULSA', '3', '23000', 'Belum', ''),
('186', '2021-09-14', '082137145353', 'MUHAMMAD NURROIN', '3', '27000', 'Belum', ''),
('187', '2021-09-15', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('188', '2021-09-16', '520020784796', 'SRI BATHI', '7', '22000', 'Lunas', ''),
('189', '2021-09-18', '520020784796', 'SRI BATHI', '2', '40000', 'Lunas', ''),
('190', '2021-09-20', '520020786612', 'SYARONI', '2', '50000', 'Lunas', ''),
('191', '2021-09-20', '520020509495', 'SUPARDI', '2', '16000', 'Lunas', ''),
('192', '2021-09-20', '520020509509', 'MARKAM MARIYAM', '2', '8000', 'Lunas', ''),
('193', '2021-09-23', '520020509168', 'JUMADI', '2', '26500', 'Lunas', ''),
('194', '2021-09-23', '083183037136', 'VIOLA', '8', '30000', 'Belum', ''),
('195', '2021-09-25', '86037218053', 'NUR HUDA', '1', '23000', 'Belum', ''),
('196', '2021-09-25', '520020509213', 'DARJO/KUMATUN', '2', '25000', 'Lunas', ''),
('197', '2021-09-27', '082137145353', 'MUHAMMAD NURROIN', '3', '47000', 'Lunas', ''),
('198', '2021-09-30', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('199', '2021-10-01', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', ''),
('200', '2021-10-10', '085936107927', 'MBAK SITI XL', '10', '40000', 'Lunas', ''),
('201', '2021-10-10', '86030038441', 'DIMYATI', '1', '53000', 'Lunas', ''),
('202', '2021-10-12', '86037218053', 'NUR HUDA', '1', '53000', 'Belum', ''),
('203', '2021-10-17', '082137145353', 'MUHAMMAD NURROIN', '3', '27000', 'Lunas', ''),
('204', '2021-10-17', '86030038441', 'DIMYATI', '1', '53000', 'Lunas', ''),
('205', '2021-10-17', '082137145353', 'MUHAMMAD NURROIN', '3', '27000', 'Belum', ''),
('206', '2021-10-19', '520020786612', 'SYARONI', '2', '52500', 'Lunas', ''),
('207', '2021-10-20', '520020664080', 'HISYAM AHMAD', '2', '7000', 'Lunas', ''),
('208', '2021-10-22', '087737627807', 'SALWA', '10', '63000', 'Lunas', ''),
('209', '2021-10-22', '081325587075', 'MBAK SITI PULSA', '3', '23000', 'Lunas', ''),
('210', '2021-10-23', '520020509168', 'JUMADI', '2', '24500', 'Lunas', ''),
('211', '2021-10-31', '86030038441', 'DIMYATI', '1', '23000', 'Lunas', ''),
('212', '2021-10-31', '86030038441', 'DIMYATI', '1', '23000', 'Lunas', ''),
('213', '2021-10-31', '32013850485', 'AMINUDDIN', '1', '53000', 'Lunas', ''),
('214', '2021-11-03', '86030038441', 'DIMYATI', '1', '53000', 'Lunas', ''),
('215', '2021-11-08', '085936107927', 'MBAK SITI XL', '10', '40000', 'Lunas', ''),
('216', '2021-11-08', '081325587075', 'MBAK SITI PULSA', '3', '17000', 'Lunas', ''),
('217', '2021-11-16', '86030038441', 'DIMYATI', '1', '105000', 'Lunas', ''),
('218', '2021-11-18', '081325587075', 'MBAK SITI PULSA', '3', '23000', 'Lunas', ''),
('219', '2021-11-21', '520020786612', 'SYARONI', '2', '167000', 'Lunas', 'ada sisa pembayaran kemaren'),
('220', '2021-11-27', '081325587075', 'MBAK SITI PULSA', '3', '23000', 'Lunas', 'belum membayar'),
('221', '2022-02-09', '520021150627', 'SUBIYANTO', '2', '86000', 'Lunas', 'hfghf'),
('222', '2022-02-10', '14419375788', 'SUDARLIM', '1', '23000', 'Belum', '1724-0680-0503-4881-4238'),
('223', '2023-04-10', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '1758-5781-9062-5068-5574'),
('224', '2023-05-29', '45028196124', 'PAK ALI', '1', '23000', 'Lunas', '5069-1873-8703-8387-7738'),
('225', '2023-05-31', '520021067899', 'KASTIMAH', '2', '11000', 'Lunas', ''),
('226', '2023-09-17', '520021070308', 'DARSONO', '2', '10000', 'Lunas', ''),
('227', '2023-09-19', '520020664080', 'HISYAM AHMAD', '2', '12000', 'Lunas', ''),
('228', '2023-09-21', '520020509342', 'KHOLIS S/KALIS', '2', '95000', 'Lunas', ''),
('229', '2023-11-27', '520020509168', 'JUMADI', '2', '55500', 'Lunas', ''),
('231', '2023-11-27', '520020732762', 'KASENAN', '2', '285000', 'Lunas', ''),
('232', '2023-11-27', '520020510237', 'YONO ARYANTO', '2', '120000', 'Lunas', ''),
('233', '2024-06-21', '520020786612', 'SYARONI', '2', '86000', 'Lunas', ''),
('234', '2024-06-22', '86030038441', 'DIMYATI', '1', '105000', 'Belum', ''),
('235', '2024-06-23', '520020509168', 'JUMADI', '2', '62000', 'Lunas', ''),
('236', '2024-06-26', '520020509213', 'DARJO/KUMATUN', '2', '32000', 'Lunas', ''),
('237', '2024-06-29', '520020509495', 'SUPARDI', '2', '65000', 'Lunas', ''),
('238', '2024-06-29', '520020509509', 'MARKAM MARIYAM', '2', '18000', 'Lunas', ''),
('239', '2024-08-10', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '	5244-8053-3897-9472-7899'),
('240', '2024-08-15', '520020509342', 'KHOLIS S/KALIS', '2', '110500', 'Lunas', ''),
('241', '2024-08-20', '520020509375', 'MUHDI/JUMANAH', '2', '74000', 'Lunas', ''),
('242', '2024-08-21', '520021070308', 'DARSONO', '2', '14500', 'Lunas', ''),
('243', '2024-08-21', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '7348-7800-1252-9572-3616'),
('244', '2024-08-22', '520020786612', 'SYARONI', '2', '160500', 'Lunas', ''),
('245', '2024-08-23', '520021150627', 'SUBIYANTO', '2', '86500', 'Lunas', ''),
('246', '2024-08-27', '520020509168', 'JUMADI', '2', '57500', 'Lunas', ''),
('247', '2024-08-28', '14419375788', 'SUDARLIM', '1', '53000', 'Belum', ''),
('248', '2024-08-28', '520020509213', 'DARJO/KUMATUN', '2', '32000', 'Lunas', ''),
('249', '2024-08-28', '520020509495', 'SUPARDI', '2', '66500', 'Lunas', ''),
('250', '2024-08-28', '520020509509', 'MARKAM MARIYAM', '2', '17000', 'Lunas', ''),
('251', '2024-09-06', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '3444-2746-1250-7268-7496'),
('252', '2024-09-17', '520020786612', 'SYARONI', '2', '146000', 'Lunas', ''),
('253', '2024-09-18', '520020509342', 'KHOLIS S/KALIS', '2', '116000', 'Lunas', ''),
('254', '2024-09-18', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '	4212-5630-3206-3847-8561'),
('255', '2024-09-18', '520020509375', 'MUHDI/JUMANAH', '2', '75000', 'Lunas', ''),
('256', '2024-09-21', '520021070308', 'DARSONO', '2', '15000', 'Lunas', ''),
('257', '2024-09-21', '520020664080', 'HISYAM AHMAD', '2', '33000', 'Lunas', ''),
('258', '2024-09-21', '520021150627', 'SUBIYANTO', '2', '94000', 'Lunas', ''),
('259', '2024-09-25', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '2514-2093-1197-6928-9459'),
('260', '2024-09-25', '520020509168', 'JUMADI', '2', '60000', 'Lunas', ''),
('262', '2024-09-27', '520020509213', 'DARJO/KUMATUN', '2', '35000', 'Lunas', ''),
('263', '2024-09-28', '520020509495', 'SUPARDI', '2', '66000', 'Lunas', ''),
('264', '2024-09-28', '520020509495', 'SUPARDI', '2', '17000', 'Lunas', ''),
('265', '2024-10-01', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '2001-1673-6076-1269-7273'),
('266', '2024-10-10', '520021150627', 'SUBIYANTO', '2', '79000', 'Lunas', ''),
('267', '2024-10-13', '14338468755', 'HARTANTO', '2', '23000', 'Lunas', '1233-5953-3852-1440-0589'),
('268', '2024-10-16', '520020509342', 'KHOLIS S/KALIS', '2', '111000', 'Lunas', ''),
('269', '2024-10-20', '520020509375', 'MUHDI/JUMANAH', '2', '74000', 'Lunas', ''),
('270', '2024-10-22', '520020509168', 'JUMADI', '2', '59000', 'Lunas', ''),
('271', '2024-10-23', '14419375788', 'SUDARLIM', '1', '53000', 'Lunas', '1107-0787-2439-8711-2892'),
('272', '2024-10-23', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '3260-6435-3998-3053-0028'),
('273', '2024-10-26', '520020786612', 'SYARONI', '2', '162000', 'Lunas', ''),
('274', '2024-10-27', '520020509213', 'DARJO/KUMATUN', '2', '34000', 'Lunas', ''),
('275', '2024-10-27', '520020509495', 'SUPARDI', '2', '66000', 'Lunas', ''),
('276', '2024-10-27', '520020509509', 'MARKAM MARIYAM', '2', '17000', 'Lunas', ''),
('277', '2024-10-29', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '2987-6892-2573-3101-4737'),
('278', '2024-11-03', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '0918-7982-7745-1713-6795'),
('279', '2024-11-07', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '4855-9954-3855-6633-5536'),
('280', '2024-11-13', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '6362-4272-9481-5910-6228'),
('281', '2024-11-18', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '2665-5909-1835-6496-0042'),
('282', '2024-11-18', '520020509342', 'KHOLIS S/KALIS', '2', '118000', 'Lunas', ''),
('283', '2024-11-18', '520021150627', 'SUBIYANTO', '2', '70000', 'Lunas', ''),
('284', '2024-11-19', '520020509375', 'MUHDI/JUMANAH', '2', '80000', 'Lunas', ''),
('285', '2024-11-24', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '	1414-1789-7795-1075-6340'),
('286', '2024-11-24', '520021070308', 'DARSONO', '2', '14500', 'Lunas', ''),
('287', '2024-11-25', '520020786612', 'SYARONI', '2', '162000', 'Lunas', ''),
('288', '2024-11-26', '520020509168', 'JUMADI', '2', '66000', 'Lunas', ''),
('289', '2024-11-29', '520020509495', 'SUPARDI', '2', '73500', 'Lunas', ''),
('290', '2024-11-29', '520020509509', 'MARKAM MARIYAM', '2', '16500', 'Lunas', ''),
('291', '2024-12-15', '520020509342', 'KHOLIS S/KALIS', '2', '106000', 'Lunas', ''),
('292', '2024-12-19', '520021150627', 'SUBIYANTO', '2', '70000', 'Lunas', ''),
('293', '2024-12-20', '520020509375', 'MUHDI/JUMANAH', '2', '70000', 'Lunas', ''),
('294', '2024-12-21', '520021070308', 'DARSONO', '2', '14000', 'Lunas', ''),
('295', '2024-12-27', '520020509168', 'JUMADI', '2', '59000', 'Lunas', ''),
('296', '2024-12-27', '520020509495', 'SUPARDI', '2', '64000', 'Lunas', ''),
('297', '2024-12-27', '520020509509', 'MARKAM MARIYAM', '2', '16000', 'Lunas', ''),
('298', '2024-12-27', '520020509213', 'DARJO/KUMATUN', '2', '33000', 'Lunas', ''),
('299', '2024-12-31', '520020732762', 'KASENAN', '2', '407000', 'Lunas', ''),
('300', '2025-01-01', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '3386-0063-7749-7116-3998'),
('301', '2025-01-14', '520020509168', 'JUMADI', '2', '53500', 'Lunas', ''),
('302', '2025-01-15', '520020509342', 'KHOLIS S/KALIS', '2', '111000', 'Lunas', ''),
('303', '2025-01-17', '520020664080', 'HISYAM AHMAD', '2', '19000', 'Lunas', ''),
('304', '2025-01-19', '520020509375', 'MUHDI/JUMANAH', '2', '48500', 'Lunas', ''),
('305', '2025-01-19', '520021150627', 'SUBIYANTO', '2', '48000', 'Lunas', ''),
('306', '2025-01-20', '520021070308', 'DARSONO', '2', '11000', 'Lunas', ''),
('307', '2025-01-27', '520020509213', 'DARJO/KUMATUN', '2', '32500', 'Lunas', ''),
('308', '2025-01-30', '520020732762', 'KASENAN', '2', '330000', 'Lunas', ''),
('309', '2025-02-17', '520020509342', 'KHOLIS S/KALIS', '2', '45500', 'Lunas', ''),
('310', '2025-02-18', '520021150627', 'SUBIYANTO', '2', '24000', 'Lunas', ''),
('311', '2025-02-20', '520020664080', 'HISYAM AHMAD', '2', '8500', 'Lunas', ''),
('312', '2025-02-21', '520020509375', 'MUHDI/JUMANAH', '2', '31000', 'Lunas', ''),
('313', '2025-02-23', '14338468755', 'HARTANTO', '1', '52000', 'Lunas', ''),
('314', '2025-02-23', '520020509168', 'JUMADI', '2', '25000', 'Lunas', ''),
('315', '2025-02-24', '520020509213', 'DARJO/KUMATUN', '2', '19000', 'Lunas', ''),
('316', '2025-03-17', '520020509375', 'MUHDI/JUMANAH', '2', '30000', 'Lunas', ''),
('317', '2025-03-17', '520020509342', 'KHOLIS S/KALIS', '2', '49000', 'Lunas', ''),
('318', '2025-03-20', '520021070308', 'DARSONO', '2', '8000', 'Lunas', ''),
('319', '2025-03-21', '520020509168', 'JUMADI', '2', '30000', 'Lunas', ''),
('320', '2025-03-21', '520020509213', 'DARJO/KUMATUN', '2', '18500', 'Lunas', ''),
('321', '2025-03-24', '520020509509', 'MARKAM MARIYAM', '2', '14000', 'Lunas', ''),
('322', '2025-04-16', '520020509342', 'KHOLIS S/KALIS', '2', '106000', 'Lunas', ''),
('323', '2025-04-19', '520020509375', 'MUHDI/JUMANAH', '2', '70000', 'Lunas', ''),
('324', '2025-04-19', '520021070308', 'DARSONO', '2', '12000', 'Lunas', ''),
('325', '2025-04-19', '520021150627', 'SUBIYANTO', '2', '60000', 'Lunas', ''),
('326', '2025-04-21', '520020509168', 'JUMADI', '2', '58000', 'Lunas', ''),
('327', '2025-04-23', '520020509213', 'DARJO/KUMATUN', '2', '32000', 'Lunas', ''),
('328', '2025-05-16', '520020509342', 'KHOLIS S/KALIS', '2', '87000', 'Lunas', ''),
('329', '2025-05-19', '520020509168', 'JUMADI', '2', '59000', 'Lunas', ''),
('330', '2025-05-21', '520021150627', 'SUBIYANTO', '2', '61000', 'Lunas', ''),
('331', '2025-05-24', '520020509213', 'DARJO/KUMATUN', '2', '32000', 'Lunas', ''),
('332', '2025-05-29', '520021070308', 'DARSONO', '2', '14500', 'Lunas', ''),
('333', '2025-06-16', '520020509342', 'KHOLIS S/KALIS', '2', '106000', 'Lunas', ''),
('334', '2025-07-17', '520020509168', 'JUMADI', '2', '55000', 'Lunas', ''),
('335', '2025-07-18', '520020509342', 'KHOLIS S/KALIS', '2', '97000', 'Lunas', ''),
('336', '2025-07-18', '520020664080', 'HISYAM AHMAD', '2', '12000', 'Lunas', ''),
('337', '2025-07-19', '14419375788', 'SUDARLIM', '1', '53000', 'Lunas', '3899-1329-6229-2399-0846'),
('338', '2025-07-19', '520020509375', 'MUHDI/JUMANAH', '2', '70000', 'Lunas', ''),
('339', '2025-07-21', '520021070308', 'DARSONO', '2', '13000', 'Lunas', ''),
('340', '2025-07-23', '520020509213', 'DARJO/KUMATUN', '2', '32000', 'Lunas', ''),
('341', '2025-07-25', '520020509495', 'SUPARDI', '2', '71000', 'Lunas', ''),
('342', '2025-07-25', '520020509509', 'MARKAM MARIYAM', '2', '17000', 'Lunas', ''),
('343', '2025-07-26', '520021150627', 'SUBIYANTO', '2', '61000', 'Lunas', ''),
('344', '2025-07-31', '520020732762', 'KASENAN', '2', '98000', 'Lunas', ''),
('345', '2025-08-16', '520020509342', 'KHOLIS S/KALIS', '2', '92000', 'Lunas', ''),
('346', '2025-08-18', '14419375788', 'SUDARLIM', '1', '53000', 'Belum', '4292-1633-5908-3100-7470'),
('347', '2025-08-20', '520020509375', 'MUHDI/JUMANAH', '2', '70000', 'Lunas', ''),
('348', '2025-08-21', '520020664080', 'HISYAM AHMAD', '2', '16000', 'Lunas', ''),
('349', '2025-08-21', '520021150627', 'SUBIYANTO', '2', '56000', 'Lunas', ''),
('350', '2025-08-21', '520020509168', 'JUMADI', '2', '48000', 'Lunas', ''),
('351', '2025-08-22', '520021070308', 'DARSONO', '2', '15000', 'Lunas', ''),
('352', '2025-08-25', '520020509213', 'DARJO/KUMATUN', '2', '30000', 'Lunas', ''),
('353', '2025-09-06', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '4871-3806-4465-3345-6316'),
('354', '2025-09-15', '520020509342', 'KHOLIS S/KALIS', '2', '70000', 'Lunas', ''),
('355', '2025-09-15', '520021150627', 'SUBIYANTO', '2', '60000', 'Lunas', ''),
('356', '2025-09-15', '520020664080', 'HISYAM AHMAD', '2', '12000', 'Lunas', ''),
('357', '2025-09-17', '520020509375', 'MUHDI/JUMANAH', '2', '73000', 'Lunas', ''),
('358', '2025-09-19', '14338468755', 'HARTANTO', '1', '53000', 'Lunas', '2121-0306-9505-0301-7857'),
('359', '2025-09-25', '520021070308', 'DARSONO', '2', '17000', 'Lunas', ''),
('360', '2025-09-25', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '3340-9493-2842-8624-0302'),
('361', '2025-09-26', '520020509213', 'DARJO/KUMATUN', '2', '32000', 'Lunas', ''),
('362', '2025-09-27', '520020509168', 'JUMADI', '2', '61000', 'Lunas', ''),
('363', '2025-10-14', '520020509342', 'KHOLIS S/KALIS', '2', '106000', 'Lunas', ''),
('364', '2025-10-18', '520020509375', 'MUHDI/JUMANAH', '2', '78000', 'Lunas', ''),
('365', '2025-10-20', '14419375788', 'SUDARLIM', '1', '53000', 'Lunas', '0737-5286-2399-6945-0681'),
('366', '2025-10-20', '520020664080', 'HISYAM AHMAD', '2', '14000', 'Lunas', ''),
('367', '2025-10-20', '520021150627', 'SUBIYANTO', '2', '58000', 'Lunas', ''),
('368', '2025-10-22', '520020509168', 'JUMADI', '2', '65000', 'Lunas', ''),
('369', '2025-10-23', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '2141-2236-1951-9872-2098'),
('370', '2025-10-23', '520021070308', 'DARSONO', '2', '16000', 'Lunas', ''),
('371', '2025-10-25', '520020509495', 'SUPARDI', '2', '81000', 'Lunas', ''),
('372', '2025-10-25', '520020509509', 'MARKAM MARIYAM', '2', '19000', 'Lunas', ''),
('373', '2025-10-30', '520020732762', 'KASENAN', '2', '84000', 'Lunas', ''),
('374', '2025-10-30', '520020510237', 'YONO ARYANTO', '2', '125000', 'Lunas', ''),
('375', '2025-11-08', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '5694-1702-3231-6230-3968'),
('376', '2025-11-16', '520020509342', 'KHOLIS S/KALIS', '2', '96000', 'Lunas', ''),
('377', '2025-11-16', '14338468755', 'HARTANTO', '1', '53000', 'Lunas', '3922-7160-3151-2562-9962'),
('378', '2025-11-19', '520021070308', 'DARSONO', '2', '13000', 'Lunas', ''),
('381', '2025-11-23', '520020509168', 'JUMADI', '2', '68000', 'Lunas', ''),
('382', '2025-11-24', '14419375788', 'SUDARLIM', '1', '53000', 'Lunas', '2884-9399-3208-2842-3224'),
('383', '2025-11-26', '520020509495', 'SUPARDI', '2', '79000', 'Lunas', ''),
('384', '2025-11-26', '520020509509', 'MARKAM MARIYAM', '2', '17000', 'Lunas', ''),
('385', '2025-11-29', '520021067899', 'KASTIMAH', '2', '161000', 'Lunas', ''),
('386', '2025-12-01', '14338468755', 'HARTANTO', '1', '23000', 'Lunas', '3681-8740-9912-7760-6852'),
('387', '2025-12-08', '14338468755', 'HARTANTO', '1', '105000', 'Lunas', '0451-7080-5457-1539-3623'),
('388', '2025-12-17', '520020509342', 'KHOLIS S/KALIS', '2', '105000', 'Lunas', '');

COMMIT;
