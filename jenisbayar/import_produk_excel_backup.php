<?php
/**
 * Import Produk dari Excel/CSV
 * Format: Kategori | Produk | Harga (3 kolom)
 * Mendukung file Excel (.xls, .xlsx) dan CSV
 */
include_once('../header.php');
include_once('../config/config.php');

// Cek apakah PhpSpreadsheet tersedia
$phpspreadsheet_available = false;
$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
    if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        $phpspreadsheet_available = true;
    }
}

/**
 * Fungsi untuk membaca file Excel dengan semua sheet menggunakan PhpSpreadsheet
 * Setiap sheet akan menjadi kategori produk yang berbeda
 */
function readExcelWithSheets($file_path) {
    global $phpspreadsheet_available;

    if (!$phpspreadsheet_available) {
        return ['success' => false, 'message' => 'PhpSpreadsheet tidak tersedia. Silakan jalankan: composer install'];
    }

    try {
        // Validasi file sebelum dibaca
        if (!file_exists($file_path)) {
            return ['success' => false, 'message' => 'File tidak ditemukan: ' . basename($file_path)];
        }

        if (!is_readable($file_path)) {
            return ['success' => false, 'message' => 'File tidak bisa dibaca: ' . basename($file_path)];
        }

        if (filesize($file_path) == 0) {
            return ['success' => false, 'message' => 'File kosong (0 bytes): ' . basename($file_path)];
        }

        // Buat reader yang tepat berdasarkan ekstensi file
        $file_ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        if ($file_ext == 'xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } elseif ($file_ext == 'xls') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        } else {
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_path);
        }

        $reader->setReadDataOnly(true);
        // Set untuk menangani file .xls yang mungkin memiliki format lama
        if ($file_ext == 'xls') {
            $reader->setReadEmptyCells(false);
        }

        // Load spreadsheet untuk mendapatkan semua sheet names
        $spreadsheet = $reader->load($file_path);

        // Dapatkan semua nama sheet terlebih dahulu
        $sheetNames = $spreadsheet->getSheetNames();
        $totalSheets = count($sheetNames);
        @error_log("Import Excel: Found " . $totalSheets . " sheet(s): " . implode(", ", $sheetNames));

        $sheets_data = [];

        // Loop setiap sheet berdasarkan nama sheet untuk memastikan semua diproses
        foreach ($sheetNames as $sheetIndex => $sheetName) {
            // Ambil worksheet berdasarkan index
            $worksheet = $spreadsheet->getSheet($sheetIndex);

            // Gunakan nama dari array sheetNames untuk konsistensi
            $sheet_name_raw = $sheetName;
            $sheet_name = trim($sheet_name_raw);

            // Debug: log nama sheet yang sedang diproses
            @error_log("Import Excel: Processing sheet #" . ($sheetIndex + 1) . "/" . $totalSheets . ": '" . $sheet_name_raw . "'");

            // Bersihkan karakter khusus yang tidak valid
            $sheet_name = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $sheet_name); // Hanya huruf, angka, spasi, dash, underscore
            $sheet_name = preg_replace('/\s+/', ' ', $sheet_name); // Normalisasi multiple spasi
            $sheet_name = trim($sheet_name);

            // Jika kosong setelah dibersihkan, gunakan nama default
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

                // Skip baris kosong
                if (!empty(array_filter($rowData, function($v) { return $v !== ''; }))) {
                    $sheet_data[] = $rowData;
                }
            }

            if (!empty($sheet_data)) {
                $sheets_data[$sheet_name] = $sheet_data;
                // Debug: log jumlah baris data yang ditemukan di sheet ini
                @error_log("Import Excel: ✓ Sheet '$sheet_name' has " . count($sheet_data) . " rows - DATA SAVED");
            } else {
                @error_log("Import Excel: ✗ Sheet '$sheet_name' is empty or has no valid data - SKIPPED");
            }
        }

        // Debug: log total sheet yang berhasil dibaca
        $sheet_names_list = implode(", ", array_keys($sheets_data));
        @error_log("Import Excel: ===== SUMMARY =====");
        @error_log("Import Excel: Total sheets in file: " . $totalSheets);
        @error_log("Import Excel: Sheets with data: " . count($sheets_data));
        @error_log("Import Excel: Sheet names imported: " . $sheet_names_list);
        @error_log("Import Excel: ====================");

        return ['success' => true, 'sheets' => $sheets_data];
    } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
        @error_log("Import Excel Reader Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error membaca file Excel: ' . $e->getMessage() . '. Pastikan file Excel tidak korup dan format benar.'];
    } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
        @error_log("Import Excel PhpSpreadsheet Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error memproses file Excel: ' . $e->getMessage() . '. Pastikan file Excel valid.'];
    } catch (Exception $e) {
        @error_log("Import Excel General Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Error membaca Excel: ' . $e->getMessage()];
    } catch (Error $e) {
        @error_log("Import Excel Fatal Error: " . $e->getMessage());
        return ['success' => false, 'message' => 'Fatal error membaca Excel: ' . $e->getMessage()];
    }
}

// Mapping kategori ke id_bayar
$kategori_mapping = [
    'KUOTA SMARTFREN' => 9, 'KUOTA AXIS' => 8, 'KUOTA XL' => 10, 'KUOTA INDOSAT' => 11,
    'KUOTA TELKOMSEL' => 5, 'KUOTA TRI' => 7, 'KUOTA 3' => 7,
    'PULSA TELKOMSEL' => 3, 'PULSA XL' => 17, 'PULSA AXIS' => 18,
    'PULSA INDOSAT' => 19, 'PULSA TRI' => 20, 'PULSA SMARTFREN' => 21,
    'TOKEN PLN' => 1, 'PLN PASCA BAYAR' => 2, 'PLN' => 1,
    'PDAM' => 6, 'BPJS KESEHATAN' => 24, 'BPJS KETENAGAKERJAAN' => 23, 'BPJS' => 24,
    'SHOPEE PAY' => 4, 'GRAB OVO' => 22, 'E-MANDIRI' => 15, 'BRIZZI' => 14, 'E-TOLL' => 25,
    'INDIHOME' => 12, 'WIFI ID' => 13, 'TRANSFER UANG' => 16,
];

function getIdBayarByKategori($kategori, $mapping) {
    global $koneksi;
    $kategori_upper = strtoupper(trim($kategori));

    // Cek exact match dulu
    if (isset($mapping[$kategori_upper])) {
        $id_bayar = $mapping[$kategori_upper];
        // Validasi bahwa id_bayar ada di database
        $check = $koneksi->query("SELECT id_bayar FROM tb_jenisbayar WHERE id_bayar = " . intval($id_bayar));
        if ($check && $check->num_rows > 0) {
            return $id_bayar;
        }
    }

    // Cek partial match
    foreach ($mapping as $key => $id_bayar) {
        if (strpos($kategori_upper, $key) !== false || strpos($key, $kategori_upper) !== false) {
            // Validasi bahwa id_bayar ada di database
            $check = $koneksi->query("SELECT id_bayar FROM tb_jenisbayar WHERE id_bayar = " . intval($id_bayar));
            if ($check && $check->num_rows > 0) {
                return $id_bayar;
            }
        }
    }

    return null;
}

// Buat tabel jika belum ada
$create_table_query = "
CREATE TABLE IF NOT EXISTS `tb_produk_orderkuota` (
  `id_produk` int(11) NOT NULL AUTO_INCREMENT,
  `kode` varchar(50) NOT NULL,
  `produk` varchar(255) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `harga` decimal(15,2) NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '1',
  `id_bayar` int(11) DEFAULT NULL,
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

// Hapus kolom keterangan jika masih ada (untuk tabel yang sudah ada)
$check_column = $koneksi->query("SHOW COLUMNS FROM tb_produk_orderkuota LIKE 'keterangan'");
if ($check_column && $check_column->num_rows > 0) {
    $koneksi->query("ALTER TABLE tb_produk_orderkuota DROP COLUMN keterangan");
}
?>
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Import Produk dari Excel/CSV</h4>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                            <li class="breadcrumb-item"><a href="<?=base_url('jenisbayar/jenis_bayar.php')?>" class="text-muted">Produk & Harga</a></li>
                            <li class="breadcrumb-item text-muted active" aria-current="page">Import Excel/CSV</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="modern-card">
            <div class="modern-card-header">
                <h4><i class="fa fa-file-upload"></i> Import Produk dari Excel/CSV</h4>
            </div>
            <div class="modern-card-body">

<?php
// Enable error reporting untuk development (hapus di production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timeout dan memory limit untuk import besar
@set_time_limit(300); // 5 menit
@ini_set('memory_limit', '256M');

// Debug: Tampilkan informasi POST untuk troubleshooting
if (isset($_GET['debug']) && $_GET['debug'] == '1') {
    echo '<div style="background:#f0f0f0;padding:10px;margin:10px;border:1px solid #ccc;">';
    echo '<strong>DEBUG INFO:</strong><br>';
    echo 'POST keys: ' . implode(', ', array_keys($_POST)) . '<br>';
    echo 'FILES keys: ' . implode(', ', array_keys($_FILES)) . '<br>';
    echo 'import_data isset: ' . (isset($_POST['import_data']) ? 'YES' : 'NO') . '<br>';
    echo 'file_import isset: ' . (isset($_FILES['file_import']) ? 'YES' : 'NO') . '<br>';
    if (isset($_FILES['file_import'])) {
        echo 'file_import error: ' . $_FILES['file_import']['error'] . '<br>';
        echo 'file_import name: ' . ($_FILES['file_import']['name'] ?? 'N/A') . '<br>';
        echo 'file_import size: ' . ($_FILES['file_import']['size'] ?? 'N/A') . '<br>';
        echo 'file_import tmp_name: ' . (isset($_FILES['file_import']['tmp_name']) ? $_FILES['file_import']['tmp_name'] : 'N/A') . '<br>';
        echo 'tmp_name exists: ' . (isset($_FILES['file_import']['tmp_name']) && file_exists($_FILES['file_import']['tmp_name']) ? 'YES' : 'NO') . '<br>';
    }
    echo 'confirm_import isset: ' . (isset($_POST['confirm_import']) ? 'YES' : 'NO') . '<br>';
    if (isset($_POST['sheets_data'])) {
        echo 'sheets_data length: ' . strlen($_POST['sheets_data']) . '<br>';
    }
    echo '</div>';
}

if (isset($_POST['import_data']) && isset($_FILES['file_import'])) {
    // Flush output buffer untuk memastikan tidak ada output sebelumnya
    if (ob_get_level() > 0) {
        ob_clean();
    }

    $file = $_FILES['file_import'];

    // Debug logging
    @error_log("Import Excel: File upload detected - name: " . ($file['name'] ?? 'N/A') . ", size: " . ($file['size'] ?? 'N/A') . ", error: " . ($file['error'] ?? 'N/A'));

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="alert alert-danger">Error upload file: ' . $file['error'] . '</div>';
        echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn btn-secondary">Kembali</a>';
        echo '</div></div></div>';
        include_once('../footer.php');
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    // Terima CSV dan Excel
    $allowed_extensions = ['csv', 'xls', 'xlsx'];
    if (!in_array($ext, $allowed_extensions)) {
        echo '<div class="alert alert-danger">';
        echo '<strong>File harus berformat CSV atau Excel (.xls, .xlsx)!</strong><br><br>';
        echo 'File yang diupload: <strong>' . htmlspecialchars($file['name']) . '</strong><br>';
        echo 'Format yang didukung: CSV, XLS, XLSX<br>';
        echo '</div>';
        echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Kembali</a>';
        echo '</div></div></div>';
        include_once('../footer.php');
        exit;
    }

    $sheets_data = [];
    $is_excel_multitab = false;
    $success_read = false;

    // Jika file Excel, baca menggunakan PhpSpreadsheet dengan semua sheet
    if (in_array($ext, ['xls', 'xlsx'])) {
        if (!$phpspreadsheet_available) {
            echo '<div class="alert alert-danger">';
            echo '<strong>PhpSpreadsheet tidak tersedia!</strong><br><br>';
            echo 'Untuk mengimport file Excel, silakan install PhpSpreadsheet terlebih dahulu:<br>';
            echo '<code>composer require phpoffice/phpspreadsheet</code><br><br>';
            echo 'Atau gunakan format CSV sebagai alternatif.';
            echo '</div>';
            echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Kembali</a>';
            echo '</div></div></div>';
            include_once('../footer.php');
            exit;
        }

        @error_log("Import Excel: Attempting to read file: " . $file['name'] . " (" . $file['size'] . " bytes)");

        // Pastikan file masih ada (kadang file temp sudah dihapus)
        if (!file_exists($file['tmp_name'])) {
            echo '<div class="alert alert-danger">';
            echo '<strong>Error: File temporary tidak ditemukan!</strong><br><br>';
            echo 'File mungkin sudah terhapus atau terjadi error saat upload.<br>';
            echo 'Silakan upload file lagi.';
            echo '</div>';
            echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Kembali</a>';
            echo '</div></div></div>';
            include_once('../footer.php');
            exit;
        }

        $result = readExcelWithSheets($file['tmp_name']);
        @error_log("Import Excel: Read result - success: " . ($result['success'] ? 'YES' : 'NO') . ", message: " . ($result['message'] ?? 'N/A'));
        if ($result['success']) {
            $sheets_data = $result['sheets'];
            $is_excel_multitab = true;
            $success_read = true;

            // Validasi: pastikan ada data sheet
            if (empty($sheets_data) || count($sheets_data) == 0) {
                echo '<div class="alert alert-danger">';
                echo '<strong>File Excel tidak memiliki data!</strong><br><br>';
                echo 'Pastikan file memiliki sheet dengan data yang valid.<br>';
                echo 'Setiap sheet akan menjadi kategori produk yang berbeda.';
                echo '</div>';
                echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Kembali</a>';
                echo '</div></div></div>';
                include_once('../footer.php');
                exit;
            }
        } else {
            echo '<div class="alert alert-danger">';
            echo '<strong>Gagal membaca file Excel!</strong><br><br>';
            echo htmlspecialchars($result['message']);
            echo '</div>';
            echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Kembali</a>';
            echo '</div></div></div>';
            include_once('../footer.php');
            exit;
        }
    } else {
            $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'CP1252'];

        foreach ($encodings as $encoding) {
        $content = @file_get_contents($file['tmp_name']);
        if ($content === false) continue;

        $content_utf8 = @mb_convert_encoding($content, 'UTF-8', $encoding);
        if ($content_utf8 === false) continue;

        $temp_file = sys_get_temp_dir() . '/import_' . time() . '_' . rand(1000,9999) . '.csv';
        @file_put_contents($temp_file, $content_utf8);

        $handle = @fopen($temp_file, 'r');
        if ($handle === false) continue;

        // Deteksi delimiter
        $sample = @fgets($handle);
        if ($sample === false) {
            fclose($handle);
            @unlink($temp_file);
            continue;
        }
        rewind($handle);

        $delimiter = ',';
        $tab_count = substr_count($sample, "\t");
        $comma_count = substr_count($sample, ',');
        $semicolon_count = substr_count($sample, ';');

        if ($tab_count > $comma_count && $tab_count > $semicolon_count) {
            $delimiter = "\t";
        } elseif ($semicolon_count > $comma_count) {
            $delimiter = ';';
        }

        // Skip BOM jika ada
        $first_char = @fread($handle, 3);
        if ($first_char !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $rows_temp = [];
        $line_num = 0;

        while (($row = @fgetcsv($handle, 0, $delimiter)) !== false) {
            $line_num++;

            // Skip header jika ada
            if ($line_num == 1) {
                $first_row_lower = strtolower(implode(' ', $row));
                if (strpos($first_row_lower, 'kategori') !== false ||
                    strpos($first_row_lower, 'produk') !== false ||
                    strpos($first_row_lower, 'harga') !== false) {
                    continue;
                }
            }

            // Filter dan trim
            $row_filtered = array_map(function($cell) {
                $cell = trim($cell);
                $cell = preg_replace('/[\x00-\x1F\x7F]/', '', $cell);
                return $cell;
            }, $row);

            // Skip baris kosong
            if (!empty(array_filter($row_filtered))) {
                $rows_temp[] = $row_filtered;
            }
        }
        fclose($handle);
        @unlink($temp_file);

            if (count($rows_temp) > 0) {
                // Untuk CSV, konversi ke format sheets_data dengan satu sheet
                // Kategori diambil dari kolom pertama, jika kosong gunakan 'UMUM'
                $csv_sheet_data = [];
                $current_csv_kategori = 'UMUM';

                foreach ($rows_temp as $row) {
                    $kategori = isset($row[0]) ? trim($row[0]) : '';
                    if (!empty($kategori)) {
                        $current_csv_kategori = $kategori;
                    }
                    // Simpan dengan kategori sebagai key
                    if (!isset($csv_sheet_data[$current_csv_kategori])) {
                        $csv_sheet_data[$current_csv_kategori] = [];
                    }
                    $csv_sheet_data[$current_csv_kategori][] = $row;
                }

                $sheets_data = $csv_sheet_data;
                $success_read = true;
                break;
            }
        }
    }

    // Validasi success_read dan sheets_data
    if (!$success_read) {
        echo '<div class="alert alert-danger">';
        echo '<strong>Error: Gagal membaca file!</strong><br><br>';
        echo 'Proses membaca file gagal. Silakan cek:<br>';
        echo '- Format file sudah benar<br>';
        echo '- File tidak corrupt<br>';
        echo '- File memiliki data yang valid<br>';
        echo '</div>';
        echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Kembali</a>';
        echo '</div></div></div>';
        include_once('../footer.php');
        exit;
    }

    if (empty($sheets_data) || !is_array($sheets_data) || count($sheets_data) == 0) {
        echo '<div class="alert alert-danger">';
        echo '<strong>Error: File tidak memiliki data!</strong><br><br>';
        echo 'Tidak ada data sheet yang ditemukan dalam file.<br>';
        echo 'Pastikan file memiliki format:<br>';
        if ($is_excel_multitab) {
            echo '<strong>Excel Multi-Tab:</strong> Setiap sheet akan menjadi kategori produk. Format per sheet: <code>Kode | Produk | Harga</code><br>';
        } else {
            echo '<code>Kategori, Produk, Harga</code> (untuk CSV)<br>';
        }
        echo 'atau<br>';
        echo '<code>Kolom A=Kategori, Kolom B=Produk, Kolom C=Harga</code><br><br>';
        echo 'Contoh:<br>';
        echo '<code>KUOTA SMARTFREN,Smart 30GB All + 60GB,135650</code><br>';
        echo '</div>';
        echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Kembali</a>';
        echo '</div></div></div>';
        include_once('../footer.php');
        exit;
    }

    // Tampilkan preview untuk semua sheet
    $total_rows_all_sheets = 0;
    foreach ($sheets_data as $sheet_name => $sheet_rows) {
        $total_rows_all_sheets += count($sheet_rows);
    }

    echo '<div class="alert alert-success">';
    echo '<strong>✓ File berhasil dibaca!</strong><br>';
    if ($is_excel_multitab) {
        echo 'Total sheet: <strong>' . count($sheets_data) . '</strong> | ';
        echo 'Total baris: <strong>' . $total_rows_all_sheets . '</strong><br>';
        echo 'Sheet yang ditemukan: <strong>' . implode(', ', array_keys($sheets_data)) . '</strong><br>';
        echo '<small>Setiap sheet akan menjadi kategori produk yang berbeda</small>';
    } else {
        echo 'Total baris: <strong>' . $total_rows_all_sheets . '</strong><br>';
    }
    echo '</div>';

    // Preview semua sheet
    echo '<div class="alert alert-info"><strong>Preview Data (10 baris pertama per sheet):</strong></div>';
    foreach ($sheets_data as $sheet_name => $rows) {
        echo '<h5>Sheet/Kategori: <strong>' . htmlspecialchars($sheet_name) . '</strong> (' . count($rows) . ' baris)</h5>';
        echo '<div class="table-responsive">';
        echo '<table class="table modern-table">';
        echo '<thead><tr><th>No</th><th>Kode</th><th>Produk/Keterangan</th><th>Harga</th><th>Status</th></tr></thead><tbody>';
        $preview_count = min(10, count($rows));
        for ($i = 0; $i < $preview_count; $i++) {
            echo '<tr>';
            echo '<td>' . ($i + 1) . '</td>';
            // Format bisa berbeda: 3, 4, atau 6+ kolom
            $col_count = count($rows[$i]);
            if ($col_count >= 6 && empty(trim($rows[$i][0]))) {
                // Format orderkuota: skip kolom 0 dan 1
                echo '<td>' . htmlspecialchars($rows[$i][2] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($rows[$i][3] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($rows[$i][4] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($rows[$i][5] ?? 'Aktif') . '</td>';
            } elseif ($col_count >= 4) {
                echo '<td>' . htmlspecialchars($rows[$i][0] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($rows[$i][1] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($rows[$i][2] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($rows[$i][3] ?? 'Aktif') . '</td>';
            } else {
                echo '<td>' . htmlspecialchars($rows[$i][0] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($rows[$i][1] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($rows[$i][2] ?? '') . '</td>';
                echo '<td>Aktif</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table></div>';
        echo '<hr>';
    }

    // Konfirmasi import
    if (!isset($_POST['confirm_import'])) {
        // Validasi preview
        $valid_count = 0;
        foreach ($sheets_data as $sheet_name => $rows) {
            foreach ($rows as $row) {
                $col_count = count($row);
                $kode = '';
                $produk = '';
                $harga_str = '';

                // Parse sesuai format
                if ($col_count >= 6 && empty(trim($row[0]))) {
                    $kode = isset($row[2]) ? trim($row[2]) : '';
                    $produk = isset($row[3]) ? trim($row[3]) : '';
                    $harga_str = isset($row[4]) ? trim($row[4]) : '';
                } elseif ($col_count >= 4) {
                    $kode = isset($row[0]) ? trim($row[0]) : '';
                    $produk = isset($row[1]) ? trim($row[1]) : '';
                    $harga_str = isset($row[2]) ? trim($row[2]) : '';
                } else {
                    $kode = isset($row[0]) ? trim($row[0]) : '';
                    $produk = isset($row[1]) ? trim($row[1]) : '';
                    $harga_str = isset($row[2]) ? trim($row[2]) : '';
                }

                if (!empty($produk) && !empty($harga_str) && preg_match('/[\d,.-]/', $harga_str)) {
                    $valid_count++;
                }
            }
        }

        echo '<div class="alert alert-' . ($valid_count > 0 ? 'success' : 'warning') . '">';
        echo 'Baris valid: <strong>' . $valid_count . '</strong> dari ' . $total_rows_all_sheets . ' baris<br>';
        echo '</div>';

        // Serialize dan encode data untuk form
        $serialized_data = serialize($sheets_data);
        $encoded_data = base64_encode($serialized_data);
        $data_size = strlen($encoded_data);

        // Warning jika data terlalu besar (POST limit biasanya 8MB)
        if ($data_size > 5000000) { // 5MB
            echo '<div class="alert alert-warning">';
            echo '<strong>⚠ Peringatan:</strong> Data cukup besar (' . number_format($data_size / 1024 / 1024, 2) . ' MB).<br>';
            echo 'Jika terjadi error, coba split file menjadi beberapa bagian yang lebih kecil.';
            echo '</div>';
        }

        echo '<form method="POST" enctype="multipart/form-data" id="importForm">';
        echo '<input type="hidden" name="sheets_data" value="' . htmlspecialchars($encoded_data, ENT_QUOTES, 'UTF-8') . '">';
        echo '<input type="hidden" name="is_excel_multitab" value="' . ($is_excel_multitab ? '1' : '0') . '">';
        echo '<input type="hidden" name="data_size" value="' . $data_size . '">';
        echo '<button type="submit" name="confirm_import" class="btn btn-success" id="confirmBtn">Ya, Import Data (' . $valid_count . ' produk dari ' . count($sheets_data) . ' sheet)</button>';
        echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Batal</a>';
        echo '</form>';
        echo '<script>
        document.getElementById("confirmBtn").addEventListener("click", function(e) {
            var form = document.getElementById("importForm");
            var dataSize = ' . $data_size . ';
            if (dataSize > 8000000) {
                if (!confirm("Data sangat besar (" + (dataSize / 1024 / 1024).toFixed(2) + " MB). Lanjutkan?")) {
                    e.preventDefault();
                    return false;
                }
            }
            this.disabled = true;
            this.innerHTML = "<i class=\"fa fa-spinner fa-spin\"></i> Memproses...";
        });
        </script>';
        echo '</div></div></div>';
        include_once('../footer.php');
        exit;
    }
}

// HANDLE CONFIRM IMPORT (STEP 2) - Proses import setelah preview
if (isset($_POST['confirm_import']) && isset($_POST['sheets_data'])) {
    // Flush output buffer untuk memastikan tidak ada output sebelumnya
    if (ob_get_level() > 0) {
        ob_clean();
    }

    // Debug logging
    @error_log("Import Excel: Confirm import detected - sheets_data length: " . (isset($_POST['sheets_data']) ? strlen($_POST['sheets_data']) : 'N/A'));

    // Proses import
    // Validasi data yang dikirim
    if (!isset($_POST['sheets_data']) || empty($_POST['sheets_data'])) {
        echo '<div class="alert alert-danger">';
        echo '<strong>Error: Data tidak ditemukan!</strong><br><br>';
        echo 'Data import tidak ditemukan. Silakan upload file lagi.<br>';
        echo '<small>Kemungkinan data terlalu besar atau terjadi error saat submit form.</small>';
        echo '</div>';
        echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Kembali</a>';
        echo '</div></div></div>';
        include_once('../footer.php');
        exit;
    }

    $sheets_data_decoded = base64_decode($_POST['sheets_data']);
    if ($sheets_data_decoded === false) {
        echo '<div class="alert alert-danger">';
        echo '<strong>Error: Gagal decode data!</strong><br><br>';
        echo 'Data import tidak valid. Silakan upload file lagi.';
        echo '</div>';
        echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Kembali</a>';
        echo '</div></div></div>';
        include_once('../footer.php');
        exit;
    }

    $sheets_data = @unserialize($sheets_data_decoded);
    if ($sheets_data === false) {
        echo '<div class="alert alert-danger">';
        echo '<strong>Error: Gagal unserialize data!</strong><br><br>';
        echo 'Data import rusak atau tidak valid. Silakan upload file lagi.<br>';
        echo '<small>Jika data sangat besar, coba split menjadi beberapa file yang lebih kecil.</small>';
        echo '</div>';
        echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Kembali</a>';
        echo '</div></div></div>';
        include_once('../footer.php');
        exit;
    }

    if (!is_array($sheets_data) || empty($sheets_data)) {
        echo '<div class="alert alert-danger">';
        echo '<strong>Error: Data kosong atau tidak valid!</strong><br><br>';
        echo 'Data import kosong. Silakan upload file lagi.';
        echo '</div>';
        echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Kembali</a>';
        echo '</div></div></div>';
        include_once('../footer.php');
        exit;
    }

    $is_excel_multitab = isset($_POST['is_excel_multitab']) && $_POST['is_excel_multitab'] == '1';

    $success_count = 0;
    $skip_count = 0;
    $error_count = 0;
    $errors = [];

    // Nonaktifkan foreign key check sementara untuk menghindari constraint error
    $koneksi->query("SET FOREIGN_KEY_CHECKS = 0");
    @error_log("Import Excel: Foreign key checks disabled for import");

    // Cache kode yang sudah ada
    $existing_codes = [];
    $codes_query = $koneksi->query("SELECT kode FROM tb_produk_orderkuota");
    if ($codes_query) {
        while ($code_row = $codes_query->fetch_assoc()) {
            $existing_codes[$code_row['kode']] = true;
        }
    }

    // Validasi koneksi database
    if (!isset($koneksi) || !$koneksi) {
        echo '<div class="alert alert-danger">';
        echo '<strong>Error: Koneksi database tidak tersedia!</strong><br><br>';
        echo 'Tidak dapat terhubung ke database. Silakan cek konfigurasi database.';
        echo '</div>';
        echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Kembali</a>';
        echo '</div></div></div>';
        include_once('../footer.php');
        exit;
    }

    echo '<div class="alert alert-info">';
    echo '<strong>Memulai import dari ' . count($sheets_data) . ' sheet...</strong><br>';
    echo 'Total sheet: ' . implode(', ', array_keys($sheets_data));
    echo '</div>';
    echo '<div class="table-responsive"><pre style="background:#f8f9fa;padding:10px;border-radius:4px;max-height:300px;overflow:auto;">';
    @error_log("Import Excel: Starting import process with " . count($sheets_data) . " sheets");
    flush();
    ob_flush();

    $row_counter = 0;

    // Loop setiap sheet (kategori) - nama sheet = kategori
    try {
        foreach ($sheets_data as $sheet_name_key => $rows) {
            // Kategori dari nama sheet
            $kategori = trim($sheet_name_key);
            $kategori = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $kategori);
            $kategori = preg_replace('/\s+/', ' ', $kategori);
            $kategori = trim($kategori);
            if (empty($kategori)) {
                $kategori = 'UMUM';
            }

            // Pastikan kategori tidak terlalu panjang
            if (strlen($kategori) > 100) {
                $kategori = substr($kategori, 0, 100);
                $kategori = trim($kategori);
            }

            @error_log("Import Excel: Processing sheet '$kategori' with " . count($rows) . " rows");

            $header_skipped = false;
            $sheet_row_index = 0;

            // Loop setiap baris dalam sheet
            foreach ($rows as $row) {
            $row_counter++;
            $sheet_row_index++;

            // Pastikan $row adalah array
            if (!is_array($row)) {
                $skip_count++;
                continue;
            }

            $cols = array_map('trim', $row);
            $col_count = count($cols);

            // Skip header baris pertama per sheet jika mengandung kata kunci
            if (!$header_skipped && $sheet_row_index == 1) {
                $first_row_lower = strtolower(implode(' ', $cols));
                $header_keywords = 0;
                if (strpos($first_row_lower, 'kode') !== false) $header_keywords++;
                if (strpos($first_row_lower, 'produk') !== false) $header_keywords++;
                if (strpos($first_row_lower, 'harga') !== false) $header_keywords++;
                if (strpos($first_row_lower, 'keterangan') !== false) $header_keywords++;
                if (strpos($first_row_lower, 'status') !== false) $header_keywords++;

                if ($header_keywords >= 2) {
                    $header_skipped = true;
                    continue;
                }
            }

            // Skip baris kosong
            $all_empty = true;
            foreach ($cols as $col) {
                if (!empty(trim($col))) {
                    $all_empty = false;
                    break;
                }
            }
            if ($all_empty) {
                continue;
            }

            // Parse data berdasarkan format kolom
            $kode = '';
            $produk_nama = '';
            $harga_str = '';
            $status_val = 'aktif';

            if ($col_count >= 6 && empty(trim($cols[0]))) {
                // Format orderkuota: skip kolom 0 dan 1
                $kode = isset($cols[2]) ? trim($cols[2]) : '';
                $produk_nama = isset($cols[3]) ? trim($cols[3]) : '';
                $harga_str = isset($cols[4]) ? trim($cols[4]) : '';
                $status_val = isset($cols[5]) ? strtolower(trim($cols[5])) : 'aktif';
            } elseif ($col_count >= 4) {
                // Format 4 kolom: Kode, Produk, Harga, Status
                $kode = isset($cols[0]) ? trim($cols[0]) : '';
                $produk_nama = isset($cols[1]) ? trim($cols[1]) : '';
                $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                $status_val = isset($cols[3]) ? strtolower(trim($cols[3])) : 'aktif';
            } elseif ($col_count == 3) {
                // Format 3 kolom: Kode, Produk, Harga
                $kode = isset($cols[0]) ? trim($cols[0]) : '';
                $produk_nama = isset($cols[1]) ? trim($cols[1]) : '';
                $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                $status_val = 'aktif';
            } else {
                // Format default
                $kode = isset($cols[0]) ? trim($cols[0]) : '';
                $produk_nama = isset($cols[1]) ? trim($cols[1]) : '';
                $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                $status_val = isset($cols[3]) ? strtolower(trim($cols[3])) : 'aktif';
            }

            // Skip jika tidak ada produk atau harga
            if (empty($produk_nama) || empty($harga_str)) {
                $skip_count++;
                continue;
            }

            // Parse harga
            $harga_clean = preg_replace('/[^0-9]/', '', $harga_str);
            $harga = floatval($harga_clean);

            if ($harga <= 0) {
                $skip_count++;
                continue;
            }

            // Parse status
            $status = (in_array($status_val, ['1', 'aktif', 'yes', 'y', 'true', 'open']) ? 1 : 0);

            // Generate kode jika belum ada
            if (empty($kode)) {
                $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($produk_nama, 0, 20)));
                if (empty($kode)) {
                    $kode = 'PROD' . str_pad($row_counter, 6, '0', STR_PAD_LEFT);
                }
            }

            // Pastikan kode unik
            $original_kode = $kode;
            $kode_counter = 1;
            while (isset($existing_codes[$kode])) {
                $kode = $original_kode . '_' . $kode_counter;
                $kode_counter++;
                if ($kode_counter > 1000) {
                    $kode = $original_kode . '_' . time() . '_' . rand(1000, 9999);
                    break;
                }
            }
            $existing_codes[$kode] = true;

            // Escape untuk database
            $kode_escaped = mysqli_real_escape_string($koneksi, $kode);
            $produk_escaped = mysqli_real_escape_string($koneksi, $produk_nama);
            $kategori_escaped = mysqli_real_escape_string($koneksi, $kategori);
            $harga_escaped = floatval($harga);

            // Mapping kategori ke id_bayar (jenis bayar)
            $id_bayar = getIdBayarByKategori($kategori, $kategori_mapping);

            // Validasi id_bayar sebelum digunakan (untuk menghindari foreign key constraint error)
            if ($id_bayar !== null) {
                $id_bayar = intval($id_bayar);
                $check_id_bayar = $koneksi->query("SELECT id_bayar FROM tb_jenisbayar WHERE id_bayar = $id_bayar");
                if (!$check_id_bayar || $check_id_bayar->num_rows == 0) {
                    // id_bayar tidak ditemukan di database, set ke NULL
                    @error_log("Import Excel: ⚠️ Row $row_counter - id_bayar $id_bayar tidak ditemukan di tb_jenisbayar, menggunakan NULL");
                    $id_bayar = null;
                }
            }

            // Cek apakah produk sudah ada
            $check_query = "SELECT id_produk FROM tb_produk_orderkuota WHERE kode = '$kode_escaped'";
            $check_result = $koneksi->query($check_query);

            if ($check_result && $check_result->num_rows > 0) {
                // Update
                $id_bayar_sql = $id_bayar ? intval($id_bayar) : 'NULL';
                $update_query = "UPDATE tb_produk_orderkuota
                            SET produk = '$produk_escaped', kategori = '$kategori_escaped',
                                harga = $harga_escaped, status = $status, id_bayar = $id_bayar_sql,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE kode = '$kode_escaped'";

                if ($koneksi->query($update_query)) {
                    $success_count++;
                } else {
                    $error_detail = $koneksi->error;

                    // Jika error karena foreign key constraint, set id_bayar ke NULL dan coba lagi
                    if (strpos($error_detail, 'foreign key') !== false || strpos($error_detail, 'Cannot add or update') !== false || strpos($error_detail, 'fk_produk_jenisbayar') !== false) {
                        @error_log("Import Excel: ⚠️ Foreign key constraint error detected on UPDATE, retrying with id_bayar = NULL");
                        $id_bayar_sql = 'NULL';
                        $update_query_retry = "UPDATE tb_produk_orderkuota
                                    SET produk = '$produk_escaped', kategori = '$kategori_escaped',
                                        harga = $harga_escaped, status = $status, id_bayar = $id_bayar_sql,
                                        updated_at = CURRENT_TIMESTAMP
                                    WHERE kode = '$kode_escaped'";

                        if ($koneksi->query($update_query_retry)) {
                            $success_count++;
                            @error_log("Import Excel: SUCCESS (Retry UPDATE with NULL id_bayar) Row $row_counter - Kode: $kode, Produk: $produk_nama");
                        } else {
                            $error_count++;
                            $errors[] = "Baris $row_counter (Sheet: $kategori): " . $koneksi->error;
                        }
                    } else {
                        $error_count++;
                        $errors[] = "Baris $row_counter (Sheet: $kategori): " . $error_detail;
                    }
                }
            } else {
                // Insert
                $id_bayar_sql = $id_bayar ? intval($id_bayar) : 'NULL';
                $insert_query = "INSERT INTO tb_produk_orderkuota
                            (kode, produk, kategori, harga, status, id_bayar)
                            VALUES ('$kode_escaped', '$produk_escaped', '$kategori_escaped', $harga_escaped, $status, $id_bayar_sql)";

                if ($koneksi->query($insert_query)) {
                    $success_count++;
                } else {
                    $error_detail = $koneksi->error;

                    // Jika error karena foreign key constraint, set id_bayar ke NULL dan coba lagi
                    if (strpos($error_detail, 'foreign key') !== false || strpos($error_detail, 'Cannot add or update') !== false || strpos($error_detail, 'fk_produk_jenisbayar') !== false) {
                        @error_log("Import Excel: ⚠️ Foreign key constraint error detected, retrying with id_bayar = NULL");
                        $id_bayar_sql = 'NULL';
                        $insert_query_retry = "INSERT INTO tb_produk_orderkuota
                                    (kode, produk, kategori, harga, status, id_bayar)
                                    VALUES ('$kode_escaped', '$produk_escaped', '$kategori_escaped', $harga_escaped, $status, $id_bayar_sql)";

                        if ($koneksi->query($insert_query_retry)) {
                            $success_count++;
                            @error_log("Import Excel: SUCCESS (Retry with NULL id_bayar) Row $row_counter - Kode: $kode, Produk: $produk_nama");
                        } else {
                            $error_count++;
                            $errors[] = "Baris $row_counter (Sheet: $kategori): " . $koneksi->error;
                        }
                    } else {
                        $error_count++;
                        $errors[] = "Baris $row_counter (Sheet: $kategori): " . $error_detail;
                    }
                }
            }

                if ($row_counter % 50 == 0) {
                    echo "Progress: $row_counter records processed...\n";
                    flush();
                    ob_flush();
                }
            }
        }
    } catch (Exception $e) {
        @error_log("Import Excel: Exception caught: " . $e->getMessage());
        @error_log("Import Excel: Stack trace: " . $e->getTraceAsString());
        $error_count++;
        $errors[] = "Error: " . $e->getMessage();
        echo "\n\nERROR: " . htmlspecialchars($e->getMessage()) . "\n";
    } catch (Error $e) {
        @error_log("Import Excel: Fatal Error caught: " . $e->getMessage());
        @error_log("Import Excel: Stack trace: " . $e->getTraceAsString());
        $error_count++;
        $errors[] = "Fatal Error: " . $e->getMessage();
        echo "\n\nFATAL ERROR: " . htmlspecialchars($e->getMessage()) . "\n";
    }

    echo '</pre></div>';

    // Aktifkan kembali foreign key check
    if (isset($koneksi) && $koneksi) {
        $koneksi->query("SET FOREIGN_KEY_CHECKS = 1");
        @error_log("Import Excel: Foreign key checks re-enabled");
    }

    // Tampilkan hasil akhir
    if ($row_counter == 0 && $success_count == 0 && $error_count == 0 && $skip_count == 0) {
        echo '<div class="alert alert-warning">';
        echo '<strong>⚠ Tidak ada data yang diproses!</strong><br><br>';
        echo 'Tidak ada baris data yang berhasil diproses. Kemungkinan:<br>';
        echo '- Semua baris di-skip sebagai header<br>';
        echo '- Semua baris kosong<br>';
        echo '- Format data tidak sesuai<br><br>';
        echo 'Silakan periksa file Excel Anda dan pastikan format benar.';
        echo '</div>';
    } elseif ($error_count == 0 && $success_count > 0) {
        echo '<div class="alert alert-success">';
        echo '<strong>✅ Import Berhasil!</strong><br>';
        if ($is_excel_multitab) {
            echo "Total sheet: <strong>" . count($sheets_data) . "</strong> (" . implode(', ', array_keys($sheets_data)) . ")<br>";
        }
        echo "Total baris diproses: <strong>" . $row_counter . "</strong><br>";
        echo "Berhasil diimport: <strong>" . number_format($success_count) . "</strong> produk<br>";
        if ($skip_count > 0) {
            echo "Dilewati (skip): <strong>" . number_format($skip_count) . "</strong> baris<br>";
        }
        echo '</div>';
    } elseif ($success_count > 0 || $error_count > 0) {
        echo '<div class="alert alert-warning">';
        echo '<strong>⚠ Import Selesai dengan Beberapa Error!</strong><br>';
        if ($is_excel_multitab) {
            echo "Total sheet: <strong>" . count($sheets_data) . "</strong> (" . implode(', ', array_keys($sheets_data)) . ")<br>";
        }
        echo "Total baris diproses: <strong>" . $row_counter . "</strong><br>";
        echo "Berhasil diimport: <strong>" . number_format($success_count) . "</strong> produk<br>";
        if ($skip_count > 0) {
            echo "Dilewati (skip): <strong>" . number_format($skip_count) . "</strong> baris<br>";
        }
        if ($error_count > 0) {
            echo "Error: <strong>" . number_format($error_count) . "</strong> baris<br>";
        }

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
    echo '</div></div></div>';
    include_once('../footer.php');
    exit;
}
?>

<div class="alert alert-info">
<strong>📋 Cara Import dari Excel/CSV:</strong><br><br>
<strong>Format File yang Didukung:</strong><br>
- <strong>Excel:</strong> .xls, .xlsx (mendukung multi-tab/sheet!)<br>
- <strong>CSV:</strong> .csv (Comma delimited)<br><br>

<strong>✨ EXCEL MULTI-TAB (RECOMMENDED):</strong><br>
- <strong>Setiap sheet/tab = 1 kategori produk</strong><br>
- Nama sheet akan menjadi nama kategori produk<br>
- Format per sheet: <code>Kode | Produk/Keterangan | Harga | Status</code><br>
- Mendukung format 3, 4, atau 6+ kolom (otomatis terdeteksi)<br>
- Baris header akan otomatis di-skip<br>
- Contoh: Sheet "KUOTA SMARTFREN" akan menghasilkan kategori "KUOTA SMARTFREN"<br><br>

<strong>Format Data yang Diharapkan:</strong><br>
<strong>Untuk Excel Multi-Tab:</strong><br>
- Format per sheet: <code>Kode | Produk/Keterangan | Harga | Status</code><br>
- Format 6+ kolom: <code>(kosong) | (Masa Aktif) | Kode | Keterangan | Harga | Status</code> (akan otomatis di-skip 2 kolom pertama)<br>
- Baris pertama adalah header (akan di-skip otomatis)<br><br>

<strong>Untuk CSV:</strong><br>
- Format: <code>Kategori, Produk, Harga</code><br>
- Jika kategori kosong di suatu baris, akan menggunakan kategori dari baris sebelumnya<br><br>

<strong>Contoh Format Excel Multi-Tab:</strong><br>
<strong>Sheet 1: "KUOTA SMARTFREN"</strong><br>
<pre style="background:#fff;padding:10px;border:1px solid #ddd;margin-top:10px;">
Kode | Keterangan | Harga | Status
SMART30 | Smart 30GB All + 60GB | 135650 | Aktif
SMART20 | Smart 20GB + 40GB | 103400 | Aktif
</pre>
<strong>Sheet 2: "TOKEN PLN"</strong><br>
<pre style="background:#fff;padding:10px;border:1px solid #ddd;margin-top:10px;">
Kode | Keterangan | Harga | Status
PLN20 | Token PLN 20.000 | 23000 | Aktif
PLN50 | Token PLN 50.000 | 55000 | Aktif
</pre>
<br>
<strong>Catatan Penting:</strong><br>
- ✨ <strong>Excel Multi-Tab: Setiap sheet = 1 kategori (RECOMMENDED untuk organisasi yang lebih baik)</strong><br>
- Kode produk akan dibuat otomatis jika kosong<br>
- <strong>Jenis Bayar (id_bayar) akan otomatis di-mapping berdasarkan kategori</strong><br>
- File Excel (.xls/.xlsx) sekarang bisa langsung diimport tanpa perlu convert ke CSV!<br>
<?php if (!$phpspreadsheet_available): ?>
<br>
<strong style="color:#dc3545;">⚠ PERINGATAN:</strong> PhpSpreadsheet belum terinstall. Untuk mengimport Excel, jalankan:<br>
<code style="background:#f8f9fa;padding:5px;border-radius:3px;">composer require phpoffice/phpspreadsheet</code><br>
Saat ini hanya file CSV yang bisa diimport.
<?php endif; ?>
</div>

<form method="POST" enctype="multipart/form-data" style="margin-top:20px;">
<div class="form-group">
<label style="display:block;margin-bottom:5px;font-weight:bold;">Pilih File Excel/CSV:</label>
<input type="file" name="file_import" accept=".csv,.xls,.xlsx" required class="form-control" style="max-width:400px;">
<small class="form-text text-muted" style="margin-top:5px;">
Format yang didukung: CSV, XLS, XLSX
<?php if ($phpspreadsheet_available): ?>
<br><strong style="color:#28a745;">✓ PhpSpreadsheet tersedia - File Excel dapat langsung diimport!</strong>
<?php else: ?>
<br><strong style="color:#dc3545;">⚠ PhpSpreadsheet belum terinstall - Hanya CSV yang bisa diimport</strong>
<?php endif; ?>
</small>
</div>
<button type="submit" name="import_data" class="btn btn-success">
    <i class="fa fa-upload"></i> Upload & Preview File
</button>
<a href="<?=base_url('jenisbayar/jenis_bayar.php')?>" class="btn btn-secondary">Kembali</a>
</form>

            </div>
        </div>
    </div>

<?php include_once('../footer.php'); ?>


