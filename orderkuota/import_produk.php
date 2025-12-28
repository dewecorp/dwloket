<?php
/**
 * Script untuk import data produk dari file JSON ke database
 *
 * Usage:
 * 1. Pastikan file orderkuota_price_data.json ada di folder orderkuota
 * 2. Jalankan script ini melalui browser atau CLI
 * 3. Data akan di-import ke tabel tb_produk_orderkuota
 */

// Cek apakah diakses via browser atau CLI
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    // Output HTML untuk browser
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Import Produk</title>';
    echo '<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}';
    echo '.container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}';
    echo 'h1{color:#333;border-bottom:2px solid #007bff;padding-bottom:10px;}';
    echo '.success{color:#28a745;background:#d4edda;padding:10px;border-radius:4px;margin:10px 0;}';
    echo '.error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:4px;margin:10px 0;}';
    echo '.info{color:#0c5460;background:#d1ecf1;padding:10px;border-radius:4px;margin:10px 0;}';
    echo 'pre{background:#f8f9fa;padding:10px;border-radius:4px;overflow-x:auto;}';
    echo '.btn{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:4px;margin-top:20px;}';
    echo '.btn:hover{background:#0056b3;}</style></head><body><div class="container">';
    echo '<h1>Import Produk OrderKuota</h1>';
}

include_once('../config/koneksi.php');
include_once('../config/config.php');

// Mapping kategori dari JSON ke id_bayar di tb_jenisbayar
$kategori_mapping = [
    // Data Internet
    'KUOTA SMARTFREN' => 9,        // Data Internet Smartfren
    'KUOTA AXIS' => 8,              // Data Internet AXIS
    'KUOTA XL' => 10,               // Data Internet XL
    'KUOTA INDOSAT' => 11,          // Data Internet Indosat
    'KUOTA TELKOMSEL' => 5,         // Data Internet Telkomsel
    'KUOTA TRI' => 7,               // Data Internet 3
    'KUOTA 3' => 7,                 // Data Internet 3 (alias)

    // Pulsa
    'PULSA TELKOMSEL' => 3,         // Pulsa Telkomsel
    'PULSA XL' => 17,               // Pulsa XL
    'PULSA AXIS' => 18,             // Pulsa AXIS
    'PULSA INDOSAT' => 19,          // Pulsa Indosat
    'PULSA TRI' => 20,              // Pulsa TRI
    'PULSA SMARTFREN' => 21,        // Pulsa SMARTFREN

    // PLN
    'TOKEN PLN' => 1,               // Token PLN
    'PLN PASCA BAYAR' => 2,         // PLN Pasca Bayar
    'PLN' => 1,                     // Token PLN (default untuk PLN)

    // PDAM
    'PDAM' => 6,                    // PDAM

    // BPJS
    'BPJS KESEHATAN' => 24,         // BPJS Kesehatan
    'BPJS KETENAGAKERJAAN' => 23,   // BPJS Ketenagakerjaan
    'BPJS' => 24,                   // BPJS Kesehatan (default)

    // E-Wallet & Payment
    'SHOPEE PAY' => 4,              // Shopee Pay
    'GRAB OVO' => 22,               // Grab Ovo
    'E-MANDIRI' => 15,              // E-Mandiri
    'BRIZZI' => 14,                 // BRIZZI
    'E-TOLL' => 25,                 // E-Toll

    // Internet Rumah
    'INDIHOME' => 12,               // Indihome
    'WIFI ID' => 13,                // Wifi ID

    // Transfer
    'TRANSFER UANG' => 16,          // Transfer Uang
];

// Path ke file JSON
$json_file = __DIR__ . '/orderkuota_price_data.json';

// Cek apakah file JSON ada
if (!file_exists($json_file)) {
    $error_msg = "Error: File orderkuota_price_data.json tidak ditemukan di folder orderkuota";
    if ($is_cli) {
        die($error_msg . "\n");
    } else {
        echo '<div class="error">' . htmlspecialchars($error_msg) . '</div>';
        echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn">Kembali</a>';
        echo '</div></body></html>';
        exit;
    }
}

// Baca file JSON
$json_content = file_get_contents($json_file);

if ($json_content === false) {
    die("Error: Gagal membaca file JSON. Pastikan file dapat diakses.\n");
}

$data = json_decode($json_content, true);

// Cek error JSON
if (json_last_error() !== JSON_ERROR_NONE) {
    $error_msg = "Error: Format JSON tidak valid. JSON Error: " . json_last_error_msg();
    if ($is_cli) {
        die($error_msg . "\n");
    } else {
        echo '<div class="error">' . htmlspecialchars($error_msg) . '</div>';
        echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn">Kembali</a>';
        echo '</div></body></html>';
        exit;
    }
}

// Cek struktur data
if (!$data) {
    $error_msg = "Error: Data JSON kosong atau null";
    if ($is_cli) {
        die($error_msg . "\n");
    } else {
        echo '<div class="error">' . htmlspecialchars($error_msg) . '</div>';
        echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn">Kembali</a>';
        echo '</div></body></html>';
        exit;
    }
}

// Cek apakah ada key 'value'
if (!isset($data['value'])) {
    // Mungkin struktur JSON berbeda, coba cek apakah langsung array
    if (is_array($data)) {
        // Jika langsung array, wrap ke dalam 'value'
        $data = ['value' => $data];
    } else {
        $error_msg = "Error: Format JSON tidak valid. Key 'value' tidak ditemukan.";
        if ($is_cli) {
            die($error_msg . "\n");
        } else {
            echo '<div class="error">' . htmlspecialchars($error_msg) . '</div>';
            echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn">Kembali</a>';
            echo '</div></body></html>';
            exit;
        }
    }
}

if (!is_array($data['value'])) {
    $error_msg = "Error: Data 'value' bukan array. Type: " . gettype($data['value']);
    if ($is_cli) {
        die($error_msg . "\n");
    } else {
        echo '<div class="error">' . htmlspecialchars($error_msg) . '</div>';
        echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn">Kembali</a>';
        echo '</div></body></html>';
        exit;
    }
}

// Fungsi untuk mendapatkan id_bayar berdasarkan kategori
function getIdBayarByKategori($kategori, $mapping) {
    $kategori_upper = strtoupper(trim($kategori));

    // Cek exact match
    if (isset($mapping[$kategori_upper])) {
        return $mapping[$kategori_upper];
    }

    // Cek partial match
    foreach ($mapping as $key => $id_bayar) {
        if (strpos($kategori_upper, $key) !== false || strpos($key, $kategori_upper) !== false) {
            return $id_bayar;
        }
    }

    // Default: null jika tidak ditemukan
    return null;
}

// Buat tabel jika belum ada
$create_table_query = "
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
  KEY `idx_harga` (`harga`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

// Jalankan query create table (tanpa foreign key constraint untuk fleksibilitas)
$koneksi->query($create_table_query);

// Mulai import
$total_data = count($data['value']);
$success_count = 0;
$skip_count = 0;
$error_count = 0;
$errors = [];

if ($is_cli) {
    echo "=== IMPORT DATA PRODUK ORDERKUOTA ===\n";
    echo "Total data: $total_data\n";
    echo "Memulai import...\n\n";
} else {
    echo '<div class="info">Memulai import ' . number_format($total_data) . ' produk...</div>';
    echo '<pre>';
    flush();
    ob_flush();
}

foreach ($data['value'] as $index => $item) {
    $kode = mysqli_real_escape_string($koneksi, $item['kode'] ?? '');
    $keterangan = mysqli_real_escape_string($koneksi, $item['keterangan'] ?? '');
    $produk = mysqli_real_escape_string($koneksi, $item['produk'] ?? '');
    $kategori = mysqli_real_escape_string($koneksi, $item['kategori'] ?? '');
    $harga = floatval($item['harga'] ?? 0);
    $status = intval($item['status'] ?? 1);
    $id_bayar = getIdBayarByKategori($kategori, $kategori_mapping);

    // Skip jika kode kosong
    if (empty($kode)) {
        $skip_count++;
        continue;
    }

    // Cek apakah produk sudah ada
    $check_query = "SELECT id_produk FROM tb_produk_orderkuota WHERE kode = '$kode'";
    $check_result = $koneksi->query($check_query);

    if ($check_result && $check_result->num_rows > 0) {
        // Update jika sudah ada
        $id_bayar_sql = $id_bayar ? intval($id_bayar) : 'NULL';
        $update_query = "UPDATE tb_produk_orderkuota
                        SET keterangan = '$keterangan',
                            produk = '$produk',
                            kategori = '$kategori',
                            harga = $harga,
                            status = $status,
                            id_bayar = $id_bayar_sql,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE kode = '$kode'";

        if ($koneksi->query($update_query)) {
            $success_count++;
        } else {
            $error_count++;
            $errors[] = "Error update kode $kode: " . $koneksi->error;
        }
    } else {
        // Insert jika belum ada
        $id_bayar_sql = $id_bayar ? intval($id_bayar) : 'NULL';
        $insert_query = "INSERT INTO tb_produk_orderkuota
                        (kode, keterangan, produk, kategori, harga, status, id_bayar)
                        VALUES ('$kode', '$keterangan', '$produk', '$kategori', $harga, $status, $id_bayar_sql)";

        if ($koneksi->query($insert_query)) {
            $success_count++;
        } else {
            $error_count++;
            $errors[] = "Error insert kode $kode: " . $koneksi->error;
        }
    }

    // Progress indicator setiap 100 record
    if (($index + 1) % 100 == 0) {
        $progress_msg = "Progress: " . ($index + 1) . "/$total_data records processed...";
        if ($is_cli) {
            echo $progress_msg . "\n";
        } else {
            echo htmlspecialchars($progress_msg) . "\n";
            flush();
            ob_flush();
        }
    }
}

// Tambahkan foreign key constraint jika belum ada (opsional)
// $add_fk_query = "ALTER TABLE tb_produk_orderkuota
//                  ADD CONSTRAINT fk_produk_jenisbayar
//                  FOREIGN KEY (id_bayar) REFERENCES tb_jenisbayar(id_bayar)
//                  ON DELETE SET NULL ON UPDATE CASCADE";
// $koneksi->query($add_fk_query);

// Hasil akhir
if ($is_cli) {
    echo "\n=== HASIL IMPORT ===\n";
    echo "Total data: $total_data\n";
    echo "Berhasil: $success_count\n";
    echo "Skip: $skip_count\n";
    echo "Error: $error_count\n";

    if ($error_count > 0 && count($errors) > 0) {
        echo "\n=== ERROR DETAILS (First 10) ===\n";
        foreach (array_slice($errors, 0, 10) as $error) {
            echo "- $error\n";
        }
    }

    echo "\nImport selesai!\n";
} else {
    echo '</pre>';

    if ($error_count == 0) {
        echo '<div class="success">';
        echo '<strong>Import Berhasil!</strong><br>';
        echo "Total data: <strong>" . number_format($total_data) . "</strong><br>";
        echo "Berhasil: <strong>" . number_format($success_count) . "</strong><br>";
        if ($skip_count > 0) {
            echo "Skip: <strong>" . number_format($skip_count) . "</strong><br>";
        }
        echo '</div>';
    } else {
        echo '<div class="error">';
        echo '<strong>Import Selesai dengan Error!</strong><br>';
        echo "Total data: <strong>" . number_format($total_data) . "</strong><br>";
        echo "Berhasil: <strong>" . number_format($success_count) . "</strong><br>";
        echo "Skip: <strong>" . number_format($skip_count) . "</strong><br>";
        echo "Error: <strong>" . number_format($error_count) . "</strong><br>";

        if (count($errors) > 0) {
            echo '<details style="margin-top:10px;"><summary>Error Details (First 10)</summary><ul>';
            foreach (array_slice($errors, 0, 10) as $error) {
                echo '<li>' . htmlspecialchars($error) . '</li>';
            }
            echo '</ul></details>';
        }
        echo '</div>';
    }

    echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn">Kembali ke Produk & Harga</a>';
    echo '</div></body></html>';
}
?>


