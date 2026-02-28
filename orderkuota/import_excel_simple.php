<?php
/**
 * Script untuk import data produk dari file Excel ke database
 * Format Excel: Kategori Produk | Produk | Harga
 *
 * Usage:
 * 1. Upload file Excel di sini
 * 2. Data akan di-import ke tabel tb_produk_orderkuota
 */

header('Content-Type: text/html; charset=utf-8');
include_once('../config/koneksi.php');
include_once('../config/config.php');

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

function readExcelAsCSV($file_path) {
    // Coba gunakan PHP untuk membaca Excel sebagai CSV
    // Atau menggunakan library sederhana

    // Baca file sebagai CSV (jika file adalah CSV atau bisa dibaca sebagai CSV)
    $handle = @fopen($file_path, 'r');
    if ($handle) {
        $data = [];
        while (($row = fgetcsv($handle, 1000, "\t")) !== false) {
            $data[] = $row;
        }
        fclose($handle);
        return ['success' => true, 'data' => $data];
    }

    return ['success' => false, 'message' => 'Gagal membaca file'];
}

?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>Import Produk dari Excel</title>
<style>
body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}
.container{max-width:900px;margin:0 auto;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}
h1{color:#333;border-bottom:2px solid #007bff;padding-bottom:10px;}
.success{color:#28a745;background:#d4edda;padding:10px;border-radius:4px;margin:10px 0;}
.error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:4px;margin:10px 0;}
.info{color:#0c5460;background:#d1ecf1;padding:10px;border-radius:4px;margin:10px 0;}
.warning{color:#856404;background:#fff3cd;padding:10px;border-radius:4px;margin:10px 0;}
.btn{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:4px;margin-top:20px;margin-right:10px;border:none;cursor:pointer;}
.btn:hover{background:#0056b3;} .btn-success{background:#28a745;} .btn-success:hover{background:#218838;}
table{border-collapse:collapse;width:100%;margin:20px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#007bff;color:white;}
</style>
</head>
<body>
<div class="container">
<h1>Import Produk dari Excel</h1>

<?php
if (isset($_POST['import_excel']) && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="error">Error upload file: ' . $file['error'] . '</div>';
        echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn">Kembali</a>';
        echo '</div></body></html>';
        exit;
    }

    // Validasi ekstensi - hanya terima CSV
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'csv') {
        echo '<div class="error">';
        echo '<strong>Format file tidak didukung!</strong><br><br>';
        echo 'File yang diupload: <strong>' . htmlspecialchars($file['name']) . '</strong><br><br>';
        echo 'Hanya file CSV yang didukung. Jika file Anda adalah Excel (.xls/.xlsx), silakan:<br>';
        echo '1. Buka file di Excel<br>';
        echo '2. Klik File > Save As > CSV (Comma delimited)<br>';
        echo '3. Upload file CSV yang baru<br>';
        echo '</div>';
        echo '<a href="' . base_url('orderkuota/import_excel_simple.php') . '" class="btn">Kembali</a>';
        echo '</div></body></html>';
        exit;
    }

    // Deteksi apakah file adalah Excel biner (bukan CSV)
    $file_content = file_get_contents($file['tmp_name'], false, null, 0, 8);
    // Excel file signature: .xls = D0 CF 11 E0, .xlsx = 50 4B 03 04 (ZIP format)
    if (substr($file_content, 0, 4) === "\xD0\xCF\x11\xE0" ||
        substr($file_content, 0, 2) === "PK" ||
        strpos($file_content, "\x00") !== false) {
        echo '<div class="error">';
        echo '<strong>File yang diupload adalah file Excel biner, bukan CSV!</strong><br><br>';
        echo 'File Excel tidak dapat dibaca langsung. Silakan:<br>';
        echo '1. Buka file di Microsoft Excel atau Google Sheets<br>';
        echo '2. Klik <strong>File > Save As</strong> (Simpan Sebagai)<br>';
        echo '3. Pilih format <strong>CSV (Comma delimited) (*.csv)</strong><br>';
        echo '4. Simpan file<br>';
        echo '5. Upload file CSV yang baru saja disimpan<br>';
        echo '</div>';
        echo '<a href="' . base_url('orderkuota/import_excel_simple.php') . '" class="btn">Kembali & Upload CSV</a>';
        echo '</div></body></html>';
        exit;
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

    // Jika file Excel (.xls/.xlsx), minta user export ke CSV dulu
    if (in_array($ext, ['xls', 'xlsx'])) {
        echo '<div class="error">';
        echo '<strong>File Excel tidak dapat dibaca langsung!</strong><br><br>';
        echo '<strong>Solusi:</strong><br>';
        echo '1. Buka file Excel di Microsoft Excel atau Google Sheets<br>';
        echo '2. Klik <strong>File > Save As (Simpan Sebagai)</strong><br>';
        echo '3. Pilih format <strong>CSV (Comma delimited) (*.csv)</strong> atau <strong>CSV UTF-8</strong><br>';
        echo '4. Simpan file<br>';
        echo '5. Upload file CSV yang baru saja disimpan<br>';
        echo '</div>';
        echo '<a href="' . base_url('orderkuota/import_excel_simple.php') . '" class="btn">Kembali & Upload CSV</a>';
        echo '</div></body></html>';
        exit;
    }

    // Baca file CSV dengan berbagai encoding
    $rows = [];
    $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'CP1252'];

    foreach ($encodings as $encoding) {
        $content = file_get_contents($file['tmp_name']);
        $content_utf8 = mb_convert_encoding($content, 'UTF-8', $encoding);

        // Simpan ke temporary file dengan encoding UTF-8
        $temp_file = sys_get_temp_dir() . '/import_' . time() . '.csv';
        file_put_contents($temp_file, $content_utf8);

        $handle = @fopen($temp_file, 'r');
        if ($handle === false) {
            continue;
        }

        $rows_temp = [];
        $delimiter = ',';

        // Deteksi delimiter dari beberapa baris pertama
        $sample_lines = [];
        for ($i = 0; $i < 5; $i++) {
            $line = fgets($handle);
            if ($line !== false) {
                $sample_lines[] = $line;
            }
        }
        rewind($handle);

        // Hitung delimiter yang paling banyak muncul
        $delimiter_counts = [',' => 0, "\t" => 0, ';' => 0];
        foreach ($sample_lines as $line) {
            $delimiter_counts[','] += substr_count($line, ',');
            $delimiter_counts["\t"] += substr_count($line, "\t");
            $delimiter_counts[';'] += substr_count($line, ';');
        }

        $delimiter = array_search(max($delimiter_counts), $delimiter_counts);

        // Baca baris dengan skip BOM jika ada
        $first_line = fgets($handle);
        if (substr($first_line, 0, 3) === "\xEF\xBB\xBF") {
            $first_line = substr($first_line, 3);
        }
        rewind($handle);

        $line_num = 0;
        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line_num++;

            // Skip baris pertama jika adalah header
            if ($line_num == 1) {
                // Cek apakah ini header (berisi kata "kategori", "produk", "harga", dll)
                $first_row_lower = strtolower(implode(' ', $row));
                if (strpos($first_row_lower, 'kategori') !== false ||
                    strpos($first_row_lower, 'produk') !== false ||
                    strpos($first_row_lower, 'harga') !== false) {
                    continue; // Skip header
                }
            }

                // Filter baris kosong dan bersihkan data
            $row_filtered = [];
            foreach ($row as $cell) {
                // Hapus BOM dan karakter kontrol
                $cell = trim($cell);
                $cell = preg_replace('/[\x00-\x1F\x7F]/', '', $cell); // Hapus karakter kontrol
                $row_filtered[] = $cell;
            }

            // Hanya tambahkan jika ada data yang tidak kosong
            $has_data = false;
            foreach ($row_filtered as $cell) {
                if ($cell !== '' && $cell !== null) {
                    $has_data = true;
                    break;
                }
            }

            if ($has_data) {
                $rows_temp[] = $row_filtered;
            }
        }
        fclose($handle);
        @unlink($temp_file);

        // Jika berhasil membaca dengan banyak data, gunakan encoding ini
        if (count($rows_temp) > 0) {
            $rows = $rows_temp;
            break;
        }
    }

    if (empty($rows)) {
        echo '<div class="error">';
        echo '<strong>File kosong atau tidak dapat dibaca!</strong><br><br>';
        echo '<strong>Kemungkinan penyebab:</strong><br>';
        echo '1. File adalah Excel biner (.xls/.xlsx) - Export ke CSV terlebih dahulu<br>';
        echo '2. Format file tidak sesuai (bukan CSV)<br>';
        echo '3. Encoding file tidak didukung<br>';
        echo '</div>';
        echo '<div class="info">';
        echo '<strong>Solusi:</strong><br>';
        echo '1. Jika file Excel, buka di Excel > File > Save As > CSV (Comma delimited)<br>';
        echo '2. Pastikan format CSV: Kategori, Produk, Harga (3 kolom dipisahkan koma)<br>';
        echo '3. Coba save dengan encoding UTF-8<br>';
        echo '</div>';
        echo '<a href="' . base_url('orderkuota/import_excel_simple.php') . '" class="btn">Kembali</a>';
        echo '</div></body></html>';
        exit;
    }

    // Tampilkan preview data dengan informasi lebih detail
    echo '<div class="success">';
    echo '<strong>✓ File CSV berhasil dibaca!</strong><br>';
    echo 'Total baris data: <strong>' . count($rows) . '</strong><br>';
    echo '</div>';

    echo '<div class="info"><strong>Preview Data (10 baris pertama):</strong></div>';
    echo '<table>';
    echo '<tr><th>No</th><th>Kategori</th><th>Produk</th><th>Harga</th><th>Jumlah Kolom</th></tr>';
    for ($i = 0; $i < min(10, count($rows)); $i++) {
        echo '<tr>';
        echo '<td>' . ($i + 1) . '</td>';
        echo '<td>' . htmlspecialchars($rows[$i][0] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($rows[$i][1] ?? '') . '</td>';
        echo '<td>' . htmlspecialchars($rows[$i][2] ?? '') . '</td>';
        echo '<td>' . count($rows[$i]) . '</td>';
        echo '</tr>';
    }
    echo '</table>';

    // Validasi format data
    $valid_rows = 0;
    foreach ($rows as $row) {
        $kategori = isset($row[0]) ? trim($row[0]) : '';
        $produk = isset($row[1]) ? trim($row[1]) : '';
        $harga = isset($row[2]) ? trim($row[2]) : '';

        if (!empty($produk) && !empty($harga) && is_numeric(str_replace([',', '.'], '', $harga))) {
            $valid_rows++;
        }
    }

    if ($valid_rows == 0) {
        echo '<div class="error">';
        echo '<strong>Format data tidak valid!</strong><br>';
        echo 'Tidak ditemukan baris dengan format: Kategori | Produk | Harga<br>';
        echo 'Pastikan CSV memiliki 3 kolom: Kategori, Produk, Harga<br>';
        echo '</div>';
        echo '<a href="' . base_url('orderkuota/import_excel_simple.php') . '" class="btn">Kembali</a>';
        echo '</div></body></html>';
        exit;
    }

    echo '<div class="warning">';
    echo 'Baris valid ditemukan: <strong>' . $valid_rows . '</strong> dari ' . count($rows) . ' baris<br>';
    echo '</div>';

    // Konfirmasi import
    if (!isset($_POST['confirm_import'])) {
        echo '<form method="POST" enctype="multipart/form-data">';
        echo '<input type="hidden" name="excel_file_name" value="' . htmlspecialchars($file['name']) . '">';
        echo '<input type="hidden" name="rows_data" value="' . base64_encode(serialize($rows)) . '">';
        echo '<button type="submit" name="confirm_import" class="btn btn-success">Ya, Import Data</button>';
        echo '<a href="' . base_url('orderkuota/import_excel_simple.php') . '" class="btn">Batal</a>';
        echo '</form>';
        echo '</div></body></html>';
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

    echo '<div class="info">Memulai import...</div><pre>';

    foreach ($rows as $index => $row) {
        $row_counter++;

        // Format Excel: Kolom 0=Kategori Produk, Kolom 1=Produk, Kolom 2=Harga
        $kategori = isset($row[0]) ? trim($row[0]) : '';
        $produk_nama = isset($row[1]) ? trim($row[1]) : '';
        $harga_str = isset($row[2]) ? trim($row[2]) : '';

        // Jika kategori tidak kosong, update kategori saat ini
        if (!empty($kategori)) {
            $current_kategori = $kategori;
        }

        // Skip jika tidak ada produk atau harga
        if (empty($produk_nama) || empty($harga_str)) {
            // Jika ini kategori header, skip
            if (!empty($kategori) && empty($produk_nama)) {
                continue;
            }
            $skip_count++;
            continue;
        }

        // Parse harga (hilangkan karakter non-numeric kecuali titik/koma untuk desimal)
        $harga_clean = preg_replace('/[^0-9,.-]/', '', $harga_str);
        $harga_clean = str_replace([',', '.'], '', $harga_clean); // Hilangkan separator
        $harga = floatval($harga_clean);

        if ($harga <= 0) {
            $skip_count++;
            continue;
        }

        // Generate kode produk dari nama produk (ambil huruf kapital dan angka)
        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($produk_nama, 0, 20)));
        if (empty($kode)) {
            $kode = 'PROD' . str_pad($row_counter, 6, '0', STR_PAD_LEFT);
        }

        // Jika kode sudah ada, tambahkan suffix
        $original_kode = $kode;
        $kode_counter = 1;
        while (true) {
            $check = $koneksi->query("SELECT id_produk FROM tb_produk_orderkuota WHERE kode = '$kode'");
            if (!$check || $check->num_rows == 0) {
                break;
            }
            $kode = $original_kode . '_' . $kode_counter;
            $kode_counter++;
        }

        $keterangan = $produk_nama;
        $produk = $produk_nama;
        $kategori_final = !empty($current_kategori) ? $current_kategori : 'UMUM';
        $status = 1;

        $kode = mysqli_real_escape_string($koneksi, $kode);
        $keterangan = mysqli_real_escape_string($koneksi, $keterangan);
        $produk = mysqli_real_escape_string($koneksi, $produk);
        $kategori_final = mysqli_real_escape_string($koneksi, $kategori_final);
        $id_bayar = getIdBayarByKategori($kategori_final, $kategori_mapping);

        // Cek apakah produk sudah ada (berdasarkan kode atau nama produk)
        $check_query = "SELECT id_produk FROM tb_produk_orderkuota WHERE kode = '$kode' OR (produk = '$produk' AND kategori = '$kategori_final')";
        $check_result = $koneksi->query($check_query);

        if ($check_result && $check_result->num_rows > 0) {
            // Update
            $id_bayar_sql = $id_bayar ? intval($id_bayar) : 'NULL';
            $update_query = "UPDATE tb_produk_orderkuota
                            SET keterangan = '$keterangan', produk = '$produk', kategori = '$kategori_final',
                                harga = $harga, status = $status, id_bayar = $id_bayar_sql,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE kode = '$kode' OR (produk = '$produk' AND kategori = '$kategori_final')";

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
                            (kode, keterangan, produk, kategori, harga, status, id_bayar)
                            VALUES ('$kode', '$keterangan', '$produk', '$kategori_final', $harga, $status, $id_bayar_sql)";

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

    echo '</pre>';

    if ($error_count == 0) {
        echo '<div class="success">';
        echo '<strong>Import Berhasil!</strong><br>';
        echo "Total baris: <strong>" . number_format(count($rows)) . "</strong><br>";
        echo "Berhasil: <strong>" . number_format($success_count) . "</strong><br>";
        if ($skip_count > 0) {
            echo "Skip: <strong>" . number_format($skip_count) . "</strong><br>";
        }
        echo '</div>';
    } else {
        echo '<div class="error">';
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
    echo '</div></body></html>';
    exit;
}

// Tampilkan form upload
$excel_files = [];
$dir = __DIR__;
$files = scandir($dir);
foreach ($files as $file) {
    if (preg_match('/\.(xls|xlsx)$/i', $file)) {
        $excel_files[] = $file;
    }
}
?>

<div class="info">
<strong>Cara Import dari Excel:</strong><br>
<strong>LANGKAH 1: Export Excel ke CSV</strong><br>
1. Buka file Excel di Microsoft Excel atau Google Sheets<br>
2. Klik <strong>File > Save As</strong> (Simpan Sebagai)<br>
3. Pilih format <strong>CSV (Comma delimited) (*.csv)</strong><br>
4. Simpan file<br>
<br>
<strong>LANGKAH 2: Upload CSV di sini</strong><br>
Format CSV yang diharapkan:<br>
- Baris 1: Header (opsional, akan di-skip otomatis)<br>
- Kolom 1: <strong>Kategori Produk</strong> (contoh: KUOTA SMARTFREN, TOKEN PLN, dll)<br>
- Kolom 2: <strong>Nama Produk</strong> (contoh: Smart 30GB All + 60GB)<br>
- Kolom 3: <strong>Harga</strong> (contoh: 135650 atau 135.650)<br>
<br>
<strong>Contoh format CSV:</strong><br>
<code style="display:block;background:#f5f5f5;padding:10px;margin-top:5px;">
KUOTA SMARTFREN,Smart 30GB All + 60GB,135650<br>
,Smart 20GB + 40GB,103400<br>
TOKEN PLN,Token PLN 20.000,23000<br>
</code>
<br>
<strong>Catatan:</strong><br>
- Jika kategori kosong di suatu baris, akan menggunakan kategori dari baris sebelumnya<br>
- Kode produk akan dibuat otomatis dari nama produk<br>
- File harus dalam format CSV (bukan .xls/.xlsx langsung)
</div>

<?php if (!empty($excel_files)): ?>
<div class="warning">
<strong>File Excel yang Ditemukan di Folder:</strong><br>
<?php foreach ($excel_files as $file): ?>
- <?=htmlspecialchars($file)?><br>
<?php endforeach; ?>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" style="margin-top:20px;">
<div style="margin-bottom:15px;">
<label style="display:block;margin-bottom:5px;font-weight:bold;">Pilih File CSV (harus CSV, bukan Excel langsung):</label>
<input type="file" name="excel_file" accept=".csv" required style="padding:8px;width:100%;max-width:400px;">
<small style="color:#dc3545;display:block;margin-top:5px;">
<strong>PENTING:</strong> File harus CSV! Jika punya file Excel (.xls/.xlsx), export ke CSV terlebih dahulu.
</small>
</div>
<button type="submit" name="import_excel" class="btn btn-success">Upload & Preview</button>
<a href="<?=base_url('jenisbayar/jenis_bayar.php')?>" class="btn">Kembali</a>
</form>

</div></body></html>


