<?php
/**
 * Debug Import - Script untuk debugging import CSV/Excel
 */

include_once('../header.php');
include_once('../config/config.php');

echo "<h2>Debug Import CSV/Excel</h2>";

// Test 1: Cek apakah vendor/autoload.php ada
echo "<h3>1. Cek Library PhpSpreadsheet</h3>";
$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload_path)) {
    echo "✓ vendor/autoload.php ditemukan<br>";
    require_once $autoload_path;

    if (class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        echo "✓ PhpSpreadsheet class tersedia<br>";
    } else {
        echo "✗ PhpSpreadsheet class TIDAK tersedia<br>";
    }
} else {
    echo "✗ vendor/autoload.php TIDAK ditemukan<br>";
}

// Test 2: Cek koneksi database
echo "<h3>2. Cek Koneksi Database</h3>";
if ($koneksi) {
    echo "✓ Koneksi database OK<br>";

    // Cek tabel
    $check_table = $koneksi->query("SHOW TABLES LIKE 'tb_produk_orderkuota'");
    if ($check_table && $check_table->num_rows > 0) {
        echo "✓ Tabel tb_produk_orderkuota ada<br>";

        // Cek struktur
        $desc = $koneksi->query("DESCRIBE tb_produk_orderkuota");
        if ($desc) {
            echo "✓ Struktur tabel OK<br>";
            echo "<pre>";
            while ($row = $desc->fetch_assoc()) {
                echo $row['Field'] . " - " . $row['Type'] . "\n";
            }
            echo "</pre>";
        }
    } else {
        echo "✗ Tabel tb_produk_orderkuota TIDAK ada<br>";
    }
} else {
    echo "✗ Koneksi database GAGAL<br>";
}

// Test 3: Test insert sederhana
echo "<h3>3. Test Insert Sederhana</h3>";
$koneksi->query("SET FOREIGN_KEY_CHECKS = 0");

$test_kode = 'TEST_' . time();
$test_query = "INSERT INTO tb_produk_orderkuota (kode, produk, kategori, harga, status, id_bayar)
              VALUES ('$test_kode', 'Test Produk Debug', 'UMUM', 10000, 1, NULL)
              ON DUPLICATE KEY UPDATE
              produk = VALUES(produk),
              updated_at = CURRENT_TIMESTAMP";

if ($koneksi->query($test_query)) {
    echo "✓ Insert test berhasil - Kode: $test_kode<br>";

    // Verifikasi
    $verify = $koneksi->query("SELECT * FROM tb_produk_orderkuota WHERE kode = '$test_kode'");
    if ($verify && $verify->num_rows > 0) {
        echo "✓ Data ditemukan di database<br>";
    } else {
        echo "✗ Data TIDAK ditemukan di database (insert gagal)<br>";
    }
} else {
    echo "✗ Insert test GAGAL: " . $koneksi->error . "<br>";
}

$koneksi->query("SET FOREIGN_KEY_CHECKS = 1");

// Test 4: Simulasi parsing CSV sederhana
echo "<h3>4. Test Parsing CSV Sederhana</h3>";
$test_csv = "Kode,Produk,Harga,Status\nT5,Telkomsel 5.000,5500,Aktif\nT10,Telkomsel 10.000,10838,Aktif";
$temp_file = sys_get_temp_dir() . '/test_import_' . time() . '.csv';
file_put_contents($temp_file, $test_csv);

$handle = fopen($temp_file, 'r');
$rows = [];
$line_num = 0;

while (($row = fgetcsv($handle, 0, ',')) !== false) {
    $line_num++;

    // Skip header
    if ($line_num == 1) {
        $first_row_lower = strtolower(implode(' ', $row));
        if (strpos($first_row_lower, 'kode') !== false ||
            strpos($first_row_lower, 'produk') !== false) {
            echo "Baris $line_num di-skip sebagai header: " . implode(', ', $row) . "<br>";
            continue;
        }
    }

    $rows[] = $row;
    echo "Baris $line_num: " . json_encode($row) . "<br>";
}

fclose($handle);
@unlink($temp_file);

echo "<br>Total baris data (setelah skip header): " . count($rows) . "<br>";

// Test 5: Test parsing harga dari berbagai format
echo "<h3>5. Test Parsing Harga</h3>";
$harga_tests = [
    '5500' => 5500,
    '10.838' => 10838,
    '10,838' => 10838,
    'Rp 10.838' => 10838,
    'IDR 10838' => 10838,
    '10000' => 10000,
];

foreach ($harga_tests as $harga_str => $expected) {
    // Simulasi parsing harga seperti di import
    $harga_clean = preg_replace('/[^0-9]/', '', $harga_str);
    $harga_parsed = floatval($harga_clean);

    if ($harga_parsed == $expected) {
        echo "✓ '$harga_str' → $harga_parsed (expected: $expected)<br>";
    } else {
        echo "✗ '$harga_str' → $harga_parsed (expected: $expected)<br>";
    }
}

// Test 6: Test full import simulation
echo "<h3>6. Test Full Import Simulation</h3>";
$koneksi->query("SET FOREIGN_KEY_CHECKS = 0");

$test_csv_data = [
    ['T5', 'Telkomsel 5.000', '5500', 'Aktif'],
    ['T10', 'Telkomsel 10.000', '10838', 'Aktif'],
];

$sim_success = 0;
$sim_skip = 0;
$sim_error = 0;

foreach ($test_csv_data as $idx => $row) {
    $kode = trim($row[0]);
    $produk = trim($row[1]);
    $harga_str = trim($row[2]);
    $status_val = strtolower(trim($row[3]));

    // Parse harga
    $harga_clean = preg_replace('/[^0-9]/', '', $harga_str);
    $harga = floatval($harga_clean);

    // Parse status
    $status = (in_array($status_val, ['1', 'aktif', 'yes', 'y', 'true']) ? 1 : 0);

    // Validasi
    if (empty($kode) || empty($produk) || $harga <= 0) {
        echo "⚠️ Row " . ($idx + 1) . " di-skip - kode: '$kode', produk: '$produk', harga: $harga<br>";
        $sim_skip++;
        continue;
    }

    // Escape
    $kode_esc = mysqli_real_escape_string($koneksi, $kode);
    $produk_esc = mysqli_real_escape_string($koneksi, $produk);
    $kategori = 'UMUM';
    $kategori_esc = mysqli_real_escape_string($koneksi, $kategori);

    // Insert
    $query = "INSERT INTO tb_produk_orderkuota (kode, produk, kategori, harga, status, id_bayar)
              VALUES ('$kode_esc', '$produk_esc', '$kategori_esc', $harga, $status, NULL)
              ON DUPLICATE KEY UPDATE
              produk = VALUES(produk),
              kategori = VALUES(kategori),
              harga = VALUES(harga),
              status = VALUES(status),
              updated_at = CURRENT_TIMESTAMP";

    if ($koneksi->query($query)) {
        echo "✓ Row " . ($idx + 1) . " berhasil diimport - Kode: $kode<br>";
        $sim_success++;
    } else {
        echo "✗ Row " . ($idx + 1) . " gagal - " . $koneksi->error . "<br>";
        $sim_error++;
    }
}

$koneksi->query("SET FOREIGN_KEY_CHECKS = 1");

echo "<br><strong>Hasil Simulasi:</strong> Success: $sim_success, Skip: $sim_skip, Error: $sim_error<br>";

// Test 7: Cek error log terakhir
echo "<h3>7. Cek Error Log (10 baris terakhir dengan 'Import')</h3>";
$log_file = ini_get('error_log');
if ($log_file && file_exists($log_file)) {
    $lines = file($log_file);
    $last_lines = array_slice($lines, -50); // Ambil 50 baris terakhir
    $import_lines = [];
    foreach ($last_lines as $line) {
        if (stripos($line, 'Import CSV') !== false || stripos($line, 'Import Excel') !== false) {
            $import_lines[] = $line;
        }
    }

    if (count($import_lines) > 0) {
        echo "<pre>";
        foreach (array_slice($import_lines, -10) as $line) {
            echo htmlspecialchars($line);
        }
        echo "</pre>";
    } else {
        echo "Tidak ada log import ditemukan dalam 50 baris terakhir<br>";
    }
} else {
    echo "Log file tidak ditemukan atau tidak dikonfigurasi<br>";
    echo "Log file path: " . ($log_file ? $log_file : 'not set') . "<br>";
}

echo "<br><a href='jenis_bayar.php' class='btn btn-primary'>Kembali ke Produk & Harga</a>";
include_once('../footer.php');
?>




