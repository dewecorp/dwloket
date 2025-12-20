<?php
/**
 * Debug Script untuk Import Excel Multitab
 * Script ini membantu debugging proses import Excel dengan multiple tabs/sheets
 */

// Include koneksi database
require_once __DIR__ . '/../config/koneksi.php';

// Cek apakah PhpSpreadsheet tersedia
$phpspreadsheet_path = __DIR__ . '/../vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php';
$autoload_path = __DIR__ . '/../vendor/autoload.php';

$phpspreadsheet_available = false;
if (file_exists($phpspreadsheet_path) || file_exists($autoload_path)) {
    if (file_exists($autoload_path)) {
        require_once $autoload_path;
    }
    $phpspreadsheet_available = true;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Excel Multitab Import</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .section {
            margin: 20px 0;
            padding: 15px;
            background: #f9f9f9;
            border-left: 4px solid #007bff;
        }
        .section h2 {
            margin-top: 0;
            color: #007bff;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
        .info {
            color: #17a2b8;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        table th {
            background: #007bff;
            color: white;
        }
        table tr:nth-child(even) {
            background: #f9f9f9;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        .upload-form {
            background: #e7f3ff;
            padding: 20px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .upload-form input[type="file"] {
            margin: 10px 0;
        }
        .upload-form button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .upload-form button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug Excel Multitab Import</h1>

        <?php if (!$phpspreadsheet_available): ?>
        <div class="section">
            <h2 class="error">❌ PhpSpreadsheet Tidak Tersedia</h2>
            <p>Library PhpSpreadsheet tidak ditemukan. Pastikan sudah diinstall dengan Composer:</p>
            <pre>composer require phpoffice/phpspreadsheet</pre>
            <p>Atau pastikan file berikut ada:</p>
            <ul>
                <li><code><?= htmlspecialchars($autoload_path) ?></code></li>
                <li><code><?= htmlspecialchars($phpspreadsheet_path) ?></code></li>
            </ul>
        </div>
        <?php else: ?>
        <div class="section">
            <h2 class="success">✅ PhpSpreadsheet Tersedia</h2>
            <p>Library PhpSpreadsheet sudah terdeteksi dan siap digunakan.</p>
        </div>

        <div class="upload-form">
            <h2>📤 Upload File Excel untuk Debug</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="file" name="excel_file" accept=".xlsx,.xls" required>
                <br>
                <button type="submit" name="debug_excel">🔍 Debug Excel Multitab</button>
            </form>
        </div>

        <?php
        if (isset($_POST['debug_excel']) && isset($_FILES['excel_file'])) {
            $file = $_FILES['excel_file'];

            echo '<div class="section">';
            echo '<h2>📋 File Info</h2>';
            echo '<table>';
            echo '<tr><th>Property</th><th>Value</th></tr>';
            echo '<tr><td>Name</td><td>' . htmlspecialchars($file['name']) . '</td></tr>';
            echo '<tr><td>Size</td><td>' . number_format($file['size'] / 1024, 2) . ' KB</td></tr>';
            echo '<tr><td>Type</td><td>' . htmlspecialchars($file['type']) . '</td></tr>';
            echo '<tr><td>Error Code</td><td>' . $file['error'] . '</td></tr>';
            echo '<tr><td>Tmp Name</td><td>' . htmlspecialchars($file['tmp_name']) . '</td></tr>';
            echo '<tr><td>File Exists</td><td>' . (file_exists($file['tmp_name']) ? 'YES' : 'NO') . '</td></tr>';
            echo '</table>';
            echo '</div>';

            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo '<div class="section">';
                echo '<h2 class="error">❌ Upload Error</h2>';
                echo '<p>Error Code: ' . $file['error'] . '</p>';
                echo '</div>';
            } elseif (!file_exists($file['tmp_name'])) {
                echo '<div class="section">';
                echo '<h2 class="error">❌ File Tidak Ditemukan</h2>';
                echo '<p>File temporary tidak ditemukan di: ' . htmlspecialchars($file['tmp_name']) . '</p>';
                echo '</div>';
            } else {
                // Step 1: Baca Excel dengan readExcelWithSheets
                echo '<div class="section">';
                echo '<h2>Step 1: Membaca File Excel</h2>';

                try {
                    // Fungsi readExcelWithSheets (copy dari jenis_bayar.php)
                    function readExcelWithSheetsDebug($file_path) {
                        $phpspreadsheet_path = __DIR__ . '/../vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php';
                        $autoload_path = __DIR__ . '/../vendor/autoload.php';

                        if (file_exists($autoload_path)) {
                            require_once $autoload_path;
                        }

                        try {
                            if (!file_exists($file_path)) {
                                return ['success' => false, 'message' => 'File tidak ditemukan: ' . basename($file_path)];
                            }

                            if (!is_readable($file_path)) {
                                return ['success' => false, 'message' => 'File tidak bisa dibaca: ' . basename($file_path)];
                            }

                            if (filesize($file_path) == 0) {
                                return ['success' => false, 'message' => 'File kosong (0 bytes): ' . basename($file_path)];
                            }

                            $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
                            if ($file_ext == 'xlsx') {
                                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                            } elseif ($file_ext == 'xls') {
                                $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                            } else {
                                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_path);
                            }

                            $reader->setReadDataOnly(true);
                            if ($file_ext == 'xls') {
                                $reader->setReadEmptyCells(false);
                            }

                            $spreadsheet = $reader->load($file_path);
                            $sheetNames = $spreadsheet->getSheetNames();
                            $totalSheets = count($sheetNames);

                            $sheets_data = [];

                            foreach ($sheetNames as $sheetIndex => $sheetName) {
                                $worksheet = $spreadsheet->getSheet($sheetIndex);
                                $sheet_name_raw = $sheetName;
                                $sheet_name = trim($sheet_name_raw);

                                $sheet_name = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $sheet_name);
                                $sheet_name = preg_replace('/\s+/', ' ', $sheet_name);
                                $sheet_name = trim($sheet_name);

                                if (empty($sheet_name)) {
                                    $sheet_name = 'UMUM';
                                }

                                $sheet_data = [];

                                foreach ($worksheet->getRowIterator() as $row) {
                                    $rowData = [];
                                    $cellIterator = $row->getCellIterator();
                                    $cellIterator->setIterateOnlyExistingCells(false);

                                    foreach ($cellIterator as $cell) {
                                        $value = $cell->getCalculatedValue();
                                        $rowData[] = $value !== null ? trim((string)$value) : '';
                                    }

                                    if (!empty(array_filter($rowData, function($v) { return $v !== ''; }))) {
                                        $sheet_data[] = $rowData;
                                    }
                                }

                                if (!empty($sheet_data)) {
                                    $sheets_data[$sheet_name] = $sheet_data;
                                }
                            }

                            return ['success' => true, 'sheets' => $sheets_data];
                        } catch (Exception $e) {
                            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
                        }
                    }

                    $excel_result = readExcelWithSheetsDebug($file['tmp_name']);

                    if (!$excel_result['success']) {
                        echo '<p class="error">❌ Gagal membaca Excel: ' . htmlspecialchars($excel_result['message']) . '</p>';
                    } else {
                        $sheets_data = $excel_result['sheets'];

                        echo '<p class="success">✅ File Excel berhasil dibaca!</p>';
                        echo '<p><strong>Total Sheets:</strong> ' . count($sheets_data) . '</p>';
                        echo '<p><strong>Sheet Names:</strong> ' . implode(', ', array_keys($sheets_data)) . '</p>';

                        // Step 2: Detail setiap sheet
                        echo '<h3>Detail Setiap Sheet:</h3>';
                        foreach ($sheets_data as $sheet_name => $rows) {
                            echo '<div style="margin: 15px 0; padding: 10px; background: white; border: 1px solid #ddd; border-radius: 4px;">';
                            echo '<h4 style="color: #007bff; margin-top: 0;">📄 Sheet: "' . htmlspecialchars($sheet_name) . '"</h4>';
                            echo '<p><strong>Total Rows:</strong> ' . count($rows) . '</p>';

                            if (count($rows) > 0) {
                                echo '<h5>Sample Data (First 5 rows):</h5>';
                                echo '<table>';
                                echo '<tr><th>Row</th><th>Data</th></tr>';

                                $sample_count = min(5, count($rows));
                                for ($i = 0; $i < $sample_count; $i++) {
                                    echo '<tr>';
                                    echo '<td>' . ($i + 1) . '</td>';
                                    echo '<td><pre style="margin: 0;">' . htmlspecialchars(json_encode($rows[$i], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre></td>';
                                    echo '</tr>';
                                }
                                echo '</table>';

                                // Step 3: Parsing data untuk setiap sheet
                                echo '<h5>Parsed Data (First 3 rows):</h5>';
                                echo '<table>';
                                echo '<tr><th>Row</th><th>Kode</th><th>Produk</th><th>Harga</th><th>Status</th><th>Kategori (from sheet)</th></tr>';

                                $header_skipped = false;
                                $row_index = 0;

                                foreach ($rows as $row) {
                                    $row_index++;

                                    // Skip header
                                    if (!$header_skipped && $row_index == 1) {
                                        $cols = array_map('trim', $row);
                                        $first_row_lower = strtolower(implode(' ', $cols));
                                        $header_keywords = 0;
                                        if (strpos($first_row_lower, 'kode') !== false) $header_keywords++;
                                        if (strpos($first_row_lower, 'produk') !== false) $header_keywords++;
                                        if (strpos($first_row_lower, 'harga') !== false) $header_keywords++;
                                        if (strpos($first_row_lower, 'keterangan') !== false) $header_keywords++;
                                        if (strpos($first_row_lower, 'status') !== false) $header_keywords++;

                                        if ($header_keywords >= 2) {
                                            echo '<tr><td colspan="6" class="info">⚠️ Row ' . $row_index . ' di-skip sebagai header (keywords: ' . $header_keywords . ')</td></tr>';
                                            $header_skipped = true;
                                            continue;
                                        }
                                    }

                                    if ($row_index > 3) break; // Hanya tampilkan 3 baris pertama

                                    $cols = array_map('trim', $row);
                                    $col_count = count($cols);

                                    // Parse data - support beberapa format
                                    $kode = '';
                                    $produk = '';
                                    $harga_str = '';
                                    $status_val = 'aktif';

                                    // Deteksi format berdasarkan jumlah kolom
                                    if ($col_count >= 6) {
                                        // Format 6+ kolom: bisa beberapa variasi
                                        // Format orderkuota: (kosong), (+Masa Aktif), Kode, Keterangan, Harga, Status
                                        // Setelah skip 2 kolom pertama, hanya baca 4 kolom: Kode, Keterangan, Harga, Status
                                        $col0_empty = empty(trim($cols[0]));

                                        if ($col0_empty) {
                                            // Format orderkuota: skip kolom 0 dan 1, baca 4 kolom berikutnya
                                            // Kolom 2: Kode, Kolom 3: Keterangan/Produk, Kolom 4: Harga, Kolom 5: Status
                                            $kode = isset($cols[2]) ? trim($cols[2]) : '';
                                            $produk = isset($cols[3]) ? trim($cols[3]) : '';
                                            $harga_str = isset($cols[4]) ? trim($cols[4]) : '';
                                            $status_val = isset($cols[5]) ? strtolower(trim($cols[5])) : 'aktif';
                                        } else {
                                            // Format standar: Kode, Keterangan, Produk, Kategori, Harga, Status
                                            $kode = isset($cols[0]) ? trim($cols[0]) : '';
                                            $produk = isset($cols[2]) ? trim($cols[2]) : (isset($cols[1]) ? trim($cols[1]) : '');
                                            $harga_str = isset($cols[4]) ? trim($cols[4]) : (isset($cols[3]) ? trim($cols[3]) : '');
                                            $status_val = isset($cols[5]) ? strtolower(trim($cols[5])) : 'aktif';
                                        }
                                    } elseif ($col_count == 4) {
                                        // Format 4 kolom: Kode, Produk, Harga, Status
                                        $kode = isset($cols[0]) ? trim($cols[0]) : '';
                                        $produk = isset($cols[1]) ? trim($cols[1]) : '';
                                        $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                                        $status_val = isset($cols[3]) ? strtolower(trim($cols[3])) : 'aktif';
                                    } elseif ($col_count == 3) {
                                        // Format 3 kolom: Kode, Produk, Harga
                                        $kode = isset($cols[0]) ? trim($cols[0]) : '';
                                        $produk = isset($cols[1]) ? trim($cols[1]) : '';
                                        $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                                        $status_val = 'aktif';
                                    } else {
                                        // Format default: ambil dari kolom pertama yang tersedia
                                        $kode = isset($cols[0]) ? trim($cols[0]) : '';
                                        $produk = isset($cols[1]) ? trim($cols[1]) : '';
                                        $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                                        $status_val = isset($cols[3]) ? strtolower(trim($cols[3])) : 'aktif';
                                    }

                                    // Parse harga
                                    $harga_clean = preg_replace('/[^0-9]/', '', $harga_str);
                                    $harga = floatval($harga_clean);
                                    if ($harga <= 0) $harga = 0;

                                    // Parse status
                                    $status = (in_array($status_val, ['1', 'aktif', 'yes', 'y', 'true', 'open']) ? 1 : 0);

                                    // Kategori dari nama sheet
                                    $kategori = trim($sheet_name);
                                    $kategori = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $kategori);
                                    $kategori = preg_replace('/\s+/', ' ', $kategori);
                                    $kategori = trim($kategori);
                                    if (empty($kategori)) $kategori = 'UMUM';

                                    echo '<tr>';
                                    echo '<td>' . $row_index . '</td>';
                                    echo '<td>' . htmlspecialchars($kode) . '</td>';
                                    echo '<td>' . htmlspecialchars($produk) . '</td>';
                                    echo '<td>' . number_format($harga, 0, ',', '.') . '</td>';
                                    echo '<td>' . ($status ? 'Aktif' : 'Tidak Aktif') . '</td>';
                                    echo '<td>' . htmlspecialchars($kategori) . '</td>';
                                    echo '</tr>';
                                }
                                echo '</table>';
                            } else {
                                echo '<p class="warning">⚠️ Sheet ini kosong (tidak ada data)</p>';
                            }

                            echo '</div>';
                        }

                        // Step 4: Test Insert (hanya untuk 3 baris pertama dari sheet pertama)
                        echo '<h2>Step 4: Test Insert ke Database</h2>';

                        $test_sheet_name = array_key_first($sheets_data);
                        $test_rows = $sheets_data[$test_sheet_name];
                        $test_count = min(3, count($test_rows));

                        if ($test_count > 0) {
                            echo '<p>Testing insert untuk <strong>' . $test_count . ' baris pertama</strong> dari sheet <strong>"' . htmlspecialchars($test_sheet_name) . '"</strong>:</p>';

                            // Nonaktifkan foreign key check sementara
                            $koneksi->query("SET FOREIGN_KEY_CHECKS = 0");

                            $test_success = 0;
                            $test_error = 0;

                            $header_skipped = false;
                            $row_index = 0;

                            foreach ($test_rows as $row) {
                                $row_index++;

                                // Skip header
                                if (!$header_skipped && $row_index == 1) {
                                    $cols = array_map('trim', $row);
                                    $first_row_lower = strtolower(implode(' ', $cols));
                                    $header_keywords = 0;
                                    if (strpos($first_row_lower, 'kode') !== false) $header_keywords++;
                                    if (strpos($first_row_lower, 'produk') !== false) $header_keywords++;
                                    if (strpos($first_row_lower, 'harga') !== false) $header_keywords++;
                                    if (strpos($first_row_lower, 'keterangan') !== false) $header_keywords++;
                                    if (strpos($first_row_lower, 'status') !== false) $header_keywords++;

                                    if ($header_keywords >= 2) {
                                        echo '<div style="margin: 10px 0; padding: 10px; background: #fff3cd; border-left: 3px solid #ffc107;">';
                                        echo '<strong>⚠️ Row ' . $row_index . ' di-skip sebagai header</strong><br>';
                                        echo 'Header keywords found: ' . $header_keywords . '<br>';
                                        echo 'Raw data: <pre style="margin: 5px 0; font-size: 11px;">' . htmlspecialchars(json_encode($cols, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                                        echo '</div>';
                                        $header_skipped = true;
                                        continue;
                                    }
                                }

                                if ($row_index > $test_count + 1) break;

                                $cols = array_map('trim', $row);
                                $col_count = count($cols);

                                // Debug: tampilkan raw data
                                echo '<div style="margin: 5px 0; padding: 5px; background: #e7f3ff; font-size: 11px;">';
                                echo '<strong>Raw Row ' . $row_index . ':</strong> col_count=' . $col_count . ' | ';
                                echo 'Data: <code>' . htmlspecialchars(json_encode(array_slice($cols, 0, min(8, $col_count)))) . '</code>';
                                echo '</div>';

                                // Parse data - support beberapa format (sama seperti di atas)
                                $kode = '';
                                $produk = '';
                                $harga_str = '';
                                $status_val = 'aktif';

                                if ($col_count >= 6) {
                                    // Format 6+ kolom: bisa beberapa variasi
                                    // Format orderkuota: (kosong), (+Masa Aktif), Kode, Keterangan, Harga, Status
                                    // Setelah skip 2 kolom pertama, hanya baca 4 kolom: Kode, Keterangan, Harga, Status
                                    $col0_empty = empty(trim($cols[0]));

                                    if ($col0_empty) {
                                        // Format orderkuota: skip kolom 0 dan 1, baca 4 kolom berikutnya
                                        // Kolom 2: Kode, Kolom 3: Keterangan/Produk, Kolom 4: Harga, Kolom 5: Status
                                        $kode = isset($cols[2]) ? trim($cols[2]) : '';
                                        $produk = isset($cols[3]) ? trim($cols[3]) : '';
                                        $harga_str = isset($cols[4]) ? trim($cols[4]) : '';
                                        $status_val = isset($cols[5]) ? strtolower(trim($cols[5])) : 'aktif';
                                    } else {
                                        // Format standar: Kode, Keterangan, Produk, Kategori, Harga, Status
                                        $kode = isset($cols[0]) ? trim($cols[0]) : '';
                                        $produk = isset($cols[2]) ? trim($cols[2]) : (isset($cols[1]) ? trim($cols[1]) : '');
                                        $harga_str = isset($cols[4]) ? trim($cols[4]) : (isset($cols[3]) ? trim($cols[3]) : '');
                                        $status_val = isset($cols[5]) ? strtolower(trim($cols[5])) : 'aktif';
                                    }
                                } elseif ($col_count == 4) {
                                    // Format 4 kolom: Kode, Produk, Harga, Status
                                    $kode = isset($cols[0]) ? trim($cols[0]) : '';
                                    $produk = isset($cols[1]) ? trim($cols[1]) : '';
                                    $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                                    $status_val = isset($cols[3]) ? strtolower(trim($cols[3])) : 'aktif';
                                } elseif ($col_count == 3) {
                                    // Format 3 kolom: Kode, Produk, Harga
                                    $kode = isset($cols[0]) ? trim($cols[0]) : '';
                                    $produk = isset($cols[1]) ? trim($cols[1]) : '';
                                    $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                                    $status_val = 'aktif';
                                } else {
                                    // Format default
                                    $kode = isset($cols[0]) ? trim($cols[0]) : '';
                                    $produk = isset($cols[1]) ? trim($cols[1]) : '';
                                    $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                                    $status_val = isset($cols[3]) ? strtolower(trim($cols[3])) : 'aktif';
                                }

                                // Debug: tampilkan hasil parsing
                                echo '<div style="margin: 5px 0; padding: 5px; background: #d1ecf1; font-size: 11px;">';
                                echo '<strong>Parsed Row ' . $row_index . ':</strong> ';
                                echo 'kode="' . htmlspecialchars($kode) . '", ';
                                echo 'produk="' . htmlspecialchars($produk) . '", ';
                                echo 'harga_str="' . htmlspecialchars($harga_str) . '", ';
                                echo 'status_val="' . htmlspecialchars($status_val) . '"';
                                echo '</div>';

                                // Validasi
                                if (empty($kode)) {
                                    $kode = 'TEST_' . $test_sheet_name . '_' . $row_index;
                                }
                                if (empty($produk)) {
                                    $produk = 'PRODUK_TEST_' . $row_index;
                                }

                                // Parse harga
                                $harga_clean = preg_replace('/[^0-9]/', '', $harga_str);
                                $harga = floatval($harga_clean);
                                if ($harga <= 0) {
                                    $harga = 1000;
                                }

                                // Parse status
                                $status = (in_array($status_val, ['1', 'aktif', 'yes', 'y', 'true', 'open']) ? 1 : 0);

                                // Kategori dari nama sheet
                                $kategori = trim($test_sheet_name);
                                $kategori = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $kategori);
                                $kategori = preg_replace('/\s+/', ' ', $kategori);
                                $kategori = trim($kategori);
                                if (empty($kategori)) $kategori = 'UMUM';

                                // Escape
                                $kode_escaped = mysqli_real_escape_string($koneksi, $kode);
                                $produk_escaped = mysqli_real_escape_string($koneksi, $produk);
                                $kategori_escaped = mysqli_real_escape_string($koneksi, $kategori);

                                // Insert query
                                $insert_query = "INSERT INTO tb_produk_orderkuota (kode, produk, kategori, harga, status, id_bayar)
                                                VALUES ('$kode_escaped', '$produk_escaped', '$kategori_escaped', $harga, $status, NULL)
                                                ON DUPLICATE KEY UPDATE
                                                produk = VALUES(produk),
                                                kategori = VALUES(kategori),
                                                harga = VALUES(harga),
                                                status = VALUES(status),
                                                updated_at = CURRENT_TIMESTAMP";

                                echo '<div style="margin: 10px 0; padding: 10px; background: #f9f9f9; border-left: 3px solid #007bff;">';
                                echo '<strong>Row ' . $row_index . ':</strong><br>';
                                echo 'Kode: <code>' . htmlspecialchars($kode) . '</code><br>';
                                echo 'Produk: <code>' . htmlspecialchars($produk) . '</code><br>';
                                echo 'Harga: <code>' . number_format($harga, 0, ',', '.') . '</code><br>';
                                echo 'Status: <code>' . ($status ? 'Aktif' : 'Tidak Aktif') . '</code><br>';
                                echo 'Kategori: <code>' . htmlspecialchars($kategori) . '</code> (dari sheet name)<br>';
                                echo '<br><strong>Query:</strong><br>';
                                echo '<pre style="font-size: 11px;">' . htmlspecialchars($insert_query) . '</pre>';

                                if ($koneksi->query($insert_query)) {
                                    echo '<span class="success">✓ SUCCESS - Inserted/Updated</span>';
                                    $test_success++;
                                } else {
                                    echo '<span class="error">✗ ERROR: ' . htmlspecialchars($koneksi->error) . '</span>';
                                    $test_error++;
                                }

                                echo '</div>';
                            }

                            // Aktifkan kembali foreign key check
                            $koneksi->query("SET FOREIGN_KEY_CHECKS = 1");

                            echo '<div class="section">';
                            echo '<h3>Test Results:</h3>';
                            echo '<p><span class="success">Success: ' . $test_success . '</span> | <span class="error">Error: ' . $test_error . '</span></p>';
                            echo '</div>';
                        }
                    }

                } catch (Exception $e) {
                    echo '<p class="error">❌ Exception: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                } catch (Error $e) {
                    echo '<p class="error">❌ Fatal Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
                }

                echo '</div>';
            }
        }
        ?>
        <?php endif; ?>
    </div>
</body>
</html>




