<?php
/**
 * Import Produk dari Excel/CSV
 * Format: Kategori | Produk | Harga (3 kolom)
 * Mendukung file Excel (.xls, .xlsx) dan CSV
 *
 * @version 1.0
 * @last_updated 2024
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
 * Fungsi untuk membaca file Excel menggunakan PhpSpreadsheet
 */
function readExcelFile($file_path) {
    global $phpspreadsheet_available;

    if (!$phpspreadsheet_available) {
        return ['success' => false, 'message' => 'PhpSpreadsheet tidak tersedia. Silakan jalankan: composer install'];
    }

    try {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file_path);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($file_path);
        $worksheet = $spreadsheet->getActiveSheet();
        $data = [];

        $highestRow = $worksheet->getHighestRow();
        $highestColumn = $worksheet->getHighestColumn();

        for ($row = 1; $row <= $highestRow; $row++) {
            $rowData = [];
            for ($col = 'A'; $col <= $highestColumn; $col++) {
                $cellValue = $worksheet->getCell($col . $row)->getCalculatedValue();
                $rowData[] = $cellValue !== null ? trim((string)$cellValue) : '';
            }

            // Skip baris kosong
            if (empty(array_filter($rowData))) {
                continue;
            }

            // Skip header jika ada (baris pertama yang mengandung kata kunci)
            if ($row == 1) {
                $first_row_lower = strtolower(implode(' ', $rowData));
                if (strpos($first_row_lower, 'kategori') !== false ||
                    strpos($first_row_lower, 'produk') !== false ||
                    strpos($first_row_lower, 'harga') !== false) {
                    continue; // Skip header
                }
            }

            $data[] = $rowData;
        }

        return ['success' => true, 'data' => $data];
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error membaca Excel: ' . $e->getMessage()];
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
    $kategori_upper = strtoupper(trim($kategori));
    if (isset($mapping[$kategori_upper])) return $mapping[$kategori_upper];
    foreach ($mapping as $key => $id_bayar) {
        if (strpos($kategori_upper, $key) !== false || strpos($key, $kategori_upper) !== false) {
            return $id_bayar;
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
if (isset($_POST['import_data']) && isset($_FILES['file_import'])) {
    $file = $_FILES['file_import'];

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

    $rows = [];
    $success_read = false;

    // Jika file Excel, baca menggunakan PhpSpreadsheet
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

        $result = readExcelFile($file['tmp_name']);
        if ($result['success']) {
            $rows = $result['data'];
            $success_read = true;
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
                $rows = $rows_temp;
                $success_read = true;
                break;
            }
        }
    }

    if (!$success_read || empty($rows)) {
        echo '<div class="alert alert-danger">';
        echo '<strong>Gagal membaca file!</strong><br><br>';
        echo 'Pastikan file memiliki format:<br>';
        echo '<code>Kategori, Produk, Harga</code> (untuk CSV)<br>';
        echo 'atau<br>';
        echo '<code>Kolom A=Kategori, Kolom B=Produk, Kolom C=Harga</code> (untuk Excel)<br><br>';
        echo 'Contoh:<br>';
        echo '<code>KUOTA SMARTFREN,Smart 30GB All + 60GB,135650</code><br>';
        echo '</div>';
        echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Kembali</a>';
        echo '</div></div></div>';
        include_once('../footer.php');
        exit;
    }

    // Tampilkan preview
    echo '<div class="alert alert-success">';
    echo '<strong>✓ File berhasil dibaca!</strong><br>';
    echo 'Total baris: <strong>' . count($rows) . '</strong><br>';
    echo '</div>';

    echo '<div class="alert alert-info"><strong>Preview Data (10 baris pertama):</strong></div>';
    echo '<div class="table-responsive">';
    echo '<table class="table modern-table">';
    echo '<thead><tr><th>No</th><th>Kategori</th><th>Produk</th><th>Harga</th></tr></thead><tbody>';
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        echo '<tr>';
        echo '<td>' . ($i + 1) . '</td>';
        echo '<td>' . htmlspecialchars($rows[$i][0] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($rows[$i][1] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($rows[$i][2] ?? '') . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table></div>';

    // Konfirmasi import
    if (!isset($_POST['confirm_import'])) {
        // Validasi preview
        $valid_count = 0;
        foreach ($rows as $row) {
            $produk = isset($row[1]) ? trim($row[1]) : '';
            $harga = isset($row[2]) ? trim($row[2]) : '';
            if (!empty($produk) && !empty($harga) && preg_match('/[\d,.-]/', $harga)) {
                $valid_count++;
            }
        }

        echo '<div class="alert alert-' . ($valid_count > 0 ? 'success' : 'warning') . '">';
        echo 'Baris valid: <strong>' . $valid_count . '</strong> dari ' . count($rows) . ' baris<br>';
        echo '</div>';

        echo '<form method="POST" enctype="multipart/form-data">';
        echo '<input type="hidden" name="rows_data" value="' . base64_encode(serialize($rows)) . '">';
        echo '<button type="submit" name="confirm_import" class="btn btn-success">Ya, Import Data (' . $valid_count . ' produk)</button>';
        echo '<a href="' . base_url('jenisbayar/import_produk_excel.php') . '" class="btn btn-secondary">Batal</a>';
        echo '</form>';
        echo '</div></div></div>';
        include_once('../footer.php');
        exit;
    }

    // Proses import
    $rows = unserialize(base64_decode($_POST['rows_data']));

    $success_count = 0;
    $skip_count = 0;
    $error_count = 0;
    $errors = [];
    $current_kategori = '';
    $row_counter = 0;

    echo '<div class="alert alert-info">Memulai import...</div>';
    echo '<div class="table-responsive"><pre style="background:#f8f9fa;padding:10px;border-radius:4px;max-height:300px;overflow:auto;">';
    flush();
    ob_flush();

    foreach ($rows as $index => $row) {
        $row_counter++;

        // Format: Kolom 0=Kategori, Kolom 1=Produk, Kolom 2=Harga
        $kategori = isset($row[0]) ? trim($row[0]) : '';
        $produk_nama = isset($row[1]) ? trim($row[1]) : '';
        $harga_str = isset($row[2]) ? trim($row[2]) : '';

        // Update kategori saat ini jika tidak kosong
        if (!empty($kategori)) {
            $current_kategori = $kategori;
        }

        // Skip jika tidak ada produk atau harga
        if (empty($produk_nama) || empty($harga_str)) {
            if (!empty($kategori) && empty($produk_nama)) {
                continue; // Baris kategori header
            }
            $skip_count++;
            continue;
        }

        // Parse harga
        $harga_clean = preg_replace('/[^0-9,.-]/', '', $harga_str);
        $harga_clean = str_replace([',', '.'], '', $harga_clean);
        $harga = floatval($harga_clean);

        if ($harga <= 0) {
            $skip_count++;
            continue;
        }

        // Generate kode produk
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($produk_nama, 0, 20)));
        if (empty($kode)) {
            $kode = 'PROD' . str_pad($row_counter, 6, '0', STR_PAD_LEFT);
        }

        // Pastikan kode unik
        $original_kode = $kode;
        $kode_counter = 1;
        while (true) {
            $check = $koneksi->query("SELECT id_produk FROM tb_produk_orderkuota WHERE kode = '" . mysqli_real_escape_string($koneksi, $kode) . "'");
            if (!$check || $check->num_rows == 0) break;
            $kode = $original_kode . '_' . $kode_counter;
            $kode_counter++;
        }

        $produk = $produk_nama;
        $kategori_final = !empty($current_kategori) ? $current_kategori : 'UMUM';
        $status = 1;

        $kode = mysqli_real_escape_string($koneksi, $kode);
        $produk = mysqli_real_escape_string($koneksi, $produk);
        $kategori_final = mysqli_real_escape_string($koneksi, $kategori_final);

        // Mapping kategori ke id_bayar (jenis bayar)
        $id_bayar = getIdBayarByKategori($kategori_final, $kategori_mapping);

        // Cek apakah produk sudah ada
        $check_query = "SELECT id_produk FROM tb_produk_orderkuota WHERE kode = '$kode'";
        $check_result = $koneksi->query($check_query);

        if ($check_result && $check_result->num_rows > 0) {
            // Update
            $id_bayar_sql = $id_bayar ? intval($id_bayar) : 'NULL';
            $update_query = "UPDATE tb_produk_orderkuota
                            SET produk = '$produk', kategori = '$kategori_final',
                                harga = $harga, status = $status, id_bayar = $id_bayar_sql,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE kode = '$kode'";

            if ($koneksi->query($update_query)) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = "Baris $row_counter: " . $koneksi->error;
            }
        } else {
            // Insert
            $id_bayar_sql = $id_bayar ? intval($id_bayar) : 'NULL';
            $insert_query = "INSERT INTO tb_produk_orderkuota
                            (kode, produk, kategori, harga, status, id_bayar)
                            VALUES ('$kode', '$produk', '$kategori_final', $harga, $status, $id_bayar_sql)";

            if ($koneksi->query($insert_query)) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = "Baris $row_counter: " . $koneksi->error;
            }
        }

        if ($row_counter % 50 == 0) {
            echo "Progress: $row_counter records processed...\n";
            flush();
            ob_flush();
        }
    }

    echo '</pre></div>';

    if ($error_count == 0) {
        echo '<div class="alert alert-success">';
        echo '<strong>Import Berhasil!</strong><br>';
        echo "Total baris: <strong>" . count($rows) . "</strong><br>";
        echo "Berhasil: <strong>" . number_format($success_count) . "</strong><br>";
        if ($skip_count > 0) {
            echo "Skip: <strong>" . number_format($skip_count) . "</strong><br>";
        }
        echo '</div>';
    } else {
        echo '<div class="alert alert-danger">';
        echo '<strong>Import Selesai dengan Error!</strong><br>';
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
    echo '</div></div></div>';
    include_once('../footer.php');
    exit;
}
?>

<div class="alert alert-info">
<strong>📋 Cara Import dari Excel/CSV:</strong><br><br>
<strong>Format File yang Didukung:</strong><br>
- <strong>Excel:</strong> .xls, .xlsx (langsung, tidak perlu convert ke CSV)<br>
- <strong>CSV:</strong> .csv (Comma delimited)<br><br>

<strong>Format Data yang Diharapkan:</strong><br>
Baris pertama adalah header (akan di-skip otomatis jika mengandung kata "kategori", "produk", atau "harga")<br>
Format: <code>Kategori | Produk | Harga</code><br><br>

<strong>Untuk Excel:</strong><br>
- Kolom A = Kategori<br>
- Kolom B = Produk<br>
- Kolom C = Harga<br><br>

<strong>Untuk CSV:</strong><br>
- Format: <code>Kategori, Produk, Harga</code><br><br>

<strong>Contoh Format:</strong><br>
<pre style="background:#fff;padding:10px;border:1px solid #ddd;margin-top:10px;">
KUOTA SMARTFREN,Smart 30GB All + 60GB,135650
,Smart 20GB + 40GB,103400
TOKEN PLN,Token PLN 20.000,23000
,Token PLN 50.000,55000
PULSA TELKOMSEL,Pulsa Telkomsel 10000,11000
</pre>
<br>
<strong>Catatan Penting:</strong><br>
- Jika kategori kosong di suatu baris, akan menggunakan kategori dari baris sebelumnya<br>
- Kode produk akan dibuat otomatis dari nama produk<br>
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

