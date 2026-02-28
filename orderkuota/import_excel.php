<?php
/**
 * Script untuk import data produk dari file Excel ke database
 *
 * Usage:
 * 1. Pastikan file Excel ada di folder orderkuota
 * 2. Format Excel: Kolom A=Kode, B=Keterangan, C=Produk, D=Kategori, E=Harga, F=Status
 * 3. Jalankan script ini melalui browser
 * 4. Data akan di-import ke tabel tb_produk_orderkuota
 */

// Cek apakah diakses via browser atau CLI
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    // Output HTML untuk browser
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Import Produk dari Excel</title>';
    echo '<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}';
    echo '.container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}';
    echo 'h1{color:#333;border-bottom:2px solid #007bff;padding-bottom:10px;}';
    echo '.success{color:#28a745;background:#d4edda;padding:10px;border-radius:4px;margin:10px 0;}';
    echo '.error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:4px;margin:10px 0;}';
    echo '.info{color:#0c5460;background:#d1ecf1;padding:10px;border-radius:4px;margin:10px 0;}';
    echo '.warning{color:#856404;background:#fff3cd;padding:10px;border-radius:4px;margin:10px 0;}';
    echo 'pre{background:#f8f9fa;padding:10px;border-radius:4px;overflow-x:auto;}';
    echo '.btn{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:4px;margin-top:20px;margin-right:10px;}';
    echo '.btn:hover{background:#0056b3;} .btn-success{background:#28a745;} .btn-success:hover{background:#218838;}';
    echo 'table{border-collapse:collapse;width:100%;margin-top:20px;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#007bff;color:white;}</style></head><body><div class="container">';
    echo '<h1>Import Produk dari Excel</h1>';
}

include_once('../config/koneksi.php');
include_once('../config/config.php');

// Mapping kategori dari Excel ke id_bayar di tb_jenisbayar
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

// Fungsi untuk membaca file Excel (menggunakan PHPExcel atau PhpSpreadsheet)
function readExcelFile($file_path) {
    // Cek apakah file ada
    if (!file_exists($file_path)) {
        return ['success' => false, 'message' => 'File tidak ditemukan'];
    }

    // Coba gunakan PhpSpreadsheet (jika tersedia)
    $phpspreadsheet_path = __DIR__ . '/../vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php';
    if (file_exists($phpspreadsheet_path)) {
        require_once __DIR__ . '/../vendor/autoload.php';

        try {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($file_path);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = [];

            foreach ($worksheet->getRowIterator() as $row) {
                $rowData = [];
                $cellIterator = $row->getCellIterator();
                $cellIterator->setIterateOnlyExistingCells(false);

                foreach ($cellIterator as $cell) {
                    $rowData[] = $cell->getCalculatedValue();
                }

                if (!empty(array_filter($rowData))) { // Skip baris kosong
                    $data[] = $rowData;
                }
            }

            return ['success' => true, 'data' => $data];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error membaca Excel: ' . $e->getMessage()];
        }
    }

    // Fallback: Gunakan SimpleXLSX jika tersedia
    /*
    $simplexlsx_path = __DIR__ . '/../vendor/simplexlsx/simplexlsx/src/SimpleXLSX.php';
    if (file_exists($simplexlsx_path)) {
        require_once $simplexlsx_path;

        if ($xlsx = SimpleXLSX::parse($file_path)) {
            return ['success' => true, 'data' => $xlsx->rows()];
        } else {
            return ['success' => false, 'message' => 'Error: ' . SimpleXLSX::parseError()];
        }
    }
    */

    // Fallback terakhir: Baca sebagai CSV (jika Excel bisa diexport ke CSV)
    // Atau gunakan pendekatan manual dengan parsing sederhana
    return ['success' => false, 'message' => 'Library Excel tidak tersedia. Silakan install PhpSpreadsheet atau SimpleXLSX.'];
}

// Handle form upload
if (isset($_POST['import_excel']) && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "Error upload file: " . $file['error'];
        if (!$is_cli) {
            echo '<div class="error">' . htmlspecialchars($error_msg) . '</div>';
            echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn">Kembali</a>';
            echo '</div></body></html>';
            exit;
        }
        die($error_msg . "\n");
    }

    // Validasi ekstensi file
    $allowed_extensions = ['xls', 'xlsx', 'csv'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($file_extension, $allowed_extensions)) {
        $error_msg = "Format file tidak didukung. Gunakan file Excel (.xls, .xlsx) atau CSV (.csv)";
        if (!$is_cli) {
            echo '<div class="error">' . htmlspecialchars($error_msg) . '</div>';
            echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn">Kembali</a>';
            echo '</div></body></html>';
            exit;
        }
        die($error_msg . "\n");
    }

    // Pindahkan file ke temporary location
    $upload_dir = __DIR__ . '/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $temp_file = $upload_dir . 'temp_' . time() . '_' . basename($file['name']);

    if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
        $error_msg = "Gagal memindahkan file upload";
        if (!$is_cli) {
            echo '<div class="error">' . htmlspecialchars($error_msg) . '</div>';
            echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn">Kembali</a>';
            echo '</div></body></html>';
            exit;
        }
        die($error_msg . "\n");
    }

    // Baca file Excel
    $excel_data = readExcelFile($temp_file);

    // Hapus file temporary
    @unlink($temp_file);

    if (!$excel_data['success']) {
        if (!$is_cli) {
            echo '<div class="error">' . htmlspecialchars($excel_data['message']) . '</div>';
            echo '<div class="info">';
            echo '<strong>Solusi:</strong><br>';
            echo '1. Install PhpSpreadsheet: <code>composer require phpoffice/phpspreadsheet</code><br>';
            echo '2. Atau gunakan file Excel yang sudah dikonversi ke CSV<br>';
            echo '3. Format CSV: Kode,Keterangan,Produk,Kategori,Harga,Status (dengan header di baris pertama)';
            echo '</div>';
            echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn">Kembali</a>';
            echo '</div></body></html>';
            exit;
        }
        die($excel_data['message'] . "\n");
    }

    $rows = $excel_data['data'];

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

    $koneksi->query($create_table_query);

    // Proses import data
    $total_data = count($rows);
    $success_count = 0;
    $skip_count = 0;
    $error_count = 0;
    $errors = [];
    $start_row = 1; // Mulai dari baris 2 (skip header)

    if (!$is_cli) {
        echo '<div class="info">Memulai import ' . number_format($total_data - 1) . ' produk (skip header)...</div>';
        echo '<pre>';
    }

    for ($i = $start_row; $i < $total_data; $i++) {
        $row = $rows[$i];

        // Format Excel: Kolom A=Kode, B=Keterangan, C=Produk, D=Kategori, E=Harga, F=Status
        $kode = isset($row[0]) ? trim($row[0]) : '';
        $keterangan = isset($row[1]) ? trim($row[1]) : '';
        $produk = isset($row[2]) ? trim($row[2]) : '';
        $kategori = isset($row[3]) ? trim($row[3]) : '';
        $harga = isset($row[4]) ? floatval(str_replace([',', '.'], '', $row[4])) : 0;
        $status = isset($row[5]) ? (strtolower(trim($row[5])) == 'aktif' || trim($row[5]) == '1' ? 1 : 0) : 1;

        // Skip jika kode kosong
        if (empty($kode)) {
            $skip_count++;
            continue;
        }

        $kode = mysqli_real_escape_string($koneksi, $kode);
        $keterangan = mysqli_real_escape_string($koneksi, $keterangan);
        $produk = mysqli_real_escape_string($koneksi, $produk);
        $kategori = mysqli_real_escape_string($koneksi, $kategori);
        $id_bayar = getIdBayarByKategori($kategori, $kategori_mapping);

        // Cek apakah produk sudah ada
        $check_query = "SELECT id_produk FROM tb_produk_orderkuota WHERE kode = ?";
        $stmt_check = $koneksi->prepare($check_query);
        $stmt_check->bind_param("s", $kode);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();

        if ($check_result && $check_result->num_rows > 0) {
            // Update jika sudah ada
            $id_bayar_val = $id_bayar ? intval($id_bayar) : null;
            $update_query = "UPDATE tb_produk_orderkuota
                            SET keterangan = ?,
                                produk = ?,
                                kategori = ?,
                                harga = ?,
                                status = ?,
                                id_bayar = ?,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE kode = ?";

            $stmt_update = $koneksi->prepare($update_query);
            // "sssidis" -> string, string, string, integer, double/decimal (use d), integer, string
            // harga is decimal, so 'd' is appropriate. status is tinyint (i).
            $stmt_update->bind_param("sssdiss", $keterangan, $produk, $kategori, $harga, $status, $id_bayar_val, $kode);

            if ($stmt_update->execute()) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = "Error update kode $kode: " . $stmt_update->error;
            }
            $stmt_update->close();
        } else {
            // Insert jika belum ada
            $id_bayar_val = $id_bayar ? intval($id_bayar) : null;
            $insert_query = "INSERT INTO tb_produk_orderkuota
                            (kode, keterangan, produk, kategori, harga, status, id_bayar)
                            VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt_insert = $koneksi->prepare($insert_query);
            $stmt_insert->bind_param("ssssidi", $kode, $keterangan, $produk, $kategori, $harga, $status, $id_bayar_val);

            if ($stmt_insert->execute()) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = "Error insert kode $kode: " . $stmt_insert->error;
            }
            $stmt_insert->close();
        }
        $stmt_check->close();

        // Progress indicator setiap 100 record
        if (($i - $start_row + 1) % 100 == 0) {
            $progress_msg = "Progress: " . ($i - $start_row + 1) . "/" . ($total_data - $start_row) . " records processed...";
            if ($is_cli) {
                echo $progress_msg . "\n";
            } else {
                echo htmlspecialchars($progress_msg) . "\n";
                flush();
                ob_flush();
            }
        }
    }

    // Hasil akhir
    if ($is_cli) {
        echo "\n=== HASIL IMPORT ===\n";
        echo "Total data: " . ($total_data - $start_row) . "\n";
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
            echo "Total data: <strong>" . number_format($total_data - $start_row) . "</strong><br>";
            echo "Berhasil: <strong>" . number_format($success_count) . "</strong><br>";
            if ($skip_count > 0) {
                echo "Skip: <strong>" . number_format($skip_count) . "</strong><br>";
            }
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '<strong>Import Selesai dengan Error!</strong><br>';
            echo "Total data: <strong>" . number_format($total_data - $start_row) . "</strong><br>";
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

        echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn btn-success">Kembali ke Produk & Harga</a>';
        echo '</div></body></html>';
    }
    exit;
}

// Tampilkan form upload jika belum submit
if (!$is_cli) {
    // Cari file Excel yang ada di folder
    $excel_files = [];
    $dir = __DIR__;
    $files = scandir($dir);
    foreach ($files as $file) {
        if (preg_match('/\.(xls|xlsx)$/i', $file)) {
            $excel_files[] = $file;
        }
    }

    echo '<div class="info">';
    echo '<strong>Format File Excel yang Diharapkan:</strong><br>';
    echo 'Baris pertama adalah header (akan di-skip)<br>';
    echo 'Kolom A: Kode<br>';
    echo 'Kolom B: Keterangan<br>';
    echo 'Kolom C: Produk<br>';
    echo 'Kolom D: Kategori<br>';
    echo 'Kolom E: Harga<br>';
    echo 'Kolom F: Status (1/Aktif atau 0/Tidak Aktif)<br>';
    echo '</div>';

    if (!empty($excel_files)) {
        echo '<div class="warning">';
        echo '<strong>File Excel yang Ditemukan di Folder:</strong><br>';
        foreach ($excel_files as $file) {
            echo '- ' . htmlspecialchars($file) . '<br>';
        }
        echo '</div>';
    }

    echo '<form method="POST" enctype="multipart/form-data" style="margin-top:20px;">';
    echo '<div style="margin-bottom:15px;">';
    echo '<label style="display:block;margin-bottom:5px;font-weight:bold;">Pilih File Excel (.xls, .xlsx):</label>';
    echo '<input type="file" name="excel_file" accept=".xls,.xlsx,.csv" required style="padding:8px;width:100%;max-width:400px;">';
    echo '</div>';
    echo '<button type="submit" name="import_excel" class="btn btn-success">Import Excel</button>';
    echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn">Kembali</a>';
    echo '</form>';

    echo '</div></body></html>';
} else {
    echo "Script ini harus dijalankan melalui browser untuk upload file.\n";
}
?>


