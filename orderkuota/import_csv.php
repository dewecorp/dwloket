<?php
/**
 * Script untuk import data produk dari file CSV ke database
 * CSV bisa diexport dari Excel dengan mudah
 *
 * Usage:
 * 1. Export Excel ke CSV (File > Save As > CSV)
 * 2. Upload file CSV di sini
 * 3. Data akan di-import ke tabel tb_produk_orderkuota
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

?>
<!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<title>Import Produk dari CSV</title>
<style>
body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}
.container{max-width:800px;margin:0 auto;background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);}
h1{color:#333;border-bottom:2px solid #007bff;padding-bottom:10px;}
.success{color:#28a745;background:#d4edda;padding:10px;border-radius:4px;margin:10px 0;}
.error{color:#dc3545;background:#f8d7da;padding:10px;border-radius:4px;margin:10px 0;}
.info{color:#0c5460;background:#d1ecf1;padding:10px;border-radius:4px;margin:10px 0;}
.btn{display:inline-block;padding:10px 20px;background:#007bff;color:white;text-decoration:none;border-radius:4px;margin-top:20px;margin-right:10px;}
.btn:hover{background:#0056b3;} .btn-success{background:#28a745;} .btn-success:hover{background:#218838;}
</style>
</head>
<body>
<div class="container">
<h1>Import Produk dari CSV</h1>

<?php
if (isset($_POST['import_csv']) && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo '<div class="error">Error upload file: ' . $file['error'] . '</div>';
        echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn">Kembali</a>';
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

    // Baca file CSV
    $handle = fopen($file['tmp_name'], 'r');
    if ($handle === false) {
        echo '<div class="error">Gagal membuka file CSV</div>';
        echo '<a href="' . base_url('jenisbayar/jenis_bayar.php') . '" class="btn">Kembali</a>';
        echo '</div></body></html>';
        exit;
    }

    // Skip header (baris pertama)
    $header = fgetcsv($handle, 1000, ',');

    $success_count = 0;
    $skip_count = 0;
    $error_count = 0;
    $errors = [];
    $row_num = 1;

    echo '<div class="info">Memulai import...</div><pre>';

    while (($row = fgetcsv($handle, 1000, ',')) !== false) {
        $row_num++;

        // Format CSV: Kode, Keterangan, Produk, Kategori, Harga, Status
        $kode = isset($row[0]) ? trim($row[0]) : '';
        $keterangan = isset($row[1]) ? trim($row[1]) : '';
        $produk = isset($row[2]) ? trim($row[2]) : '';
        $kategori = isset($row[3]) ? trim($row[3]) : '';

        // Parse harga (hilangkan titik dan koma)
        $harga_str = isset($row[4]) ? trim($row[4]) : '0';
        $harga = floatval(str_replace([',', '.'], '', $harga_str));

        // Parse status
        $status_str = isset($row[5]) ? trim(strtolower($row[5])) : '1';
        $status = ($status_str == 'aktif' || $status_str == '1' || $status_str == 'true') ? 1 : 0;

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
        $check_query = "SELECT id_produk FROM tb_produk_orderkuota WHERE kode = '$kode'";
        $check_result = $koneksi->query($check_query);

        if ($check_result && $check_result->num_rows > 0) {
            // Update
            $id_bayar_sql = $id_bayar ? intval($id_bayar) : 'NULL';
            $update_query = "UPDATE tb_produk_orderkuota
                            SET keterangan = '$keterangan', produk = '$produk', kategori = '$kategori',
                                harga = $harga, status = $status, id_bayar = $id_bayar_sql,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE kode = '$kode'";

            if ($koneksi->query($update_query)) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = "Baris $row_num (kode $kode): " . $koneksi->error;
            }
        } else {
            // Insert
            $id_bayar_sql = $id_bayar ? intval($id_bayar) : 'NULL';
            $insert_query = "INSERT INTO tb_produk_orderkuota
                            (kode, keterangan, produk, kategori, harga, status, id_bayar)
                            VALUES ('$kode', '$keterangan', '$produk', '$kategori', $harga, $status, $id_bayar_sql)";

            if ($koneksi->query($insert_query)) {
                $success_count++;
            } else {
                $error_count++;
                $errors[] = "Baris $row_num (kode $kode): " . $koneksi->error;
            }
        }

        if ($row_num % 100 == 0) {
            echo "Progress: $row_num records processed...\n";
            flush();
            ob_flush();
        }
    }

    fclose($handle);

    echo '</pre>';

    if ($error_count == 0) {
        echo '<div class="success">';
        echo '<strong>Import Berhasil!</strong><br>';
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
?>

<div class="info">
<strong>Cara Export Excel ke CSV:</strong><br>
1. Buka file Excel di Microsoft Excel atau Google Sheets<br>
2. Klik File > Save As (Simpan Sebagai)<br>
3. Pilih format CSV (Comma Delimited) atau CSV UTF-8<br>
4. Simpan file<br>
<br>
<strong>Format CSV yang Diharapkan:</strong><br>
Baris pertama adalah header (akan di-skip)<br>
Kolom: Kode, Keterangan, Produk, Kategori, Harga, Status<br>
Contoh:<br>
<code>SMDC150,Smart 30GB All + 60GB (01-05) 30 Hari,Data Smart Combo,KUOTA SMARTFREN,135650,1</code>
</div>

<form method="POST" enctype="multipart/form-data" style="margin-top:20px;">
<div style="margin-bottom:15px;">
<label style="display:block;margin-bottom:5px;font-weight:bold;">Pilih File CSV:</label>
<input type="file" name="csv_file" accept=".csv" required style="padding:8px;width:100%;max-width:400px;">
</div>
<button type="submit" name="import_csv" class="btn btn-success">Import CSV</button>
<a href="<?=base_url('jenisbayar/jenis_bayar.php')?>" class="btn">Kembali</a>
</form>

</div></body></html>


