<?php
/**
 * Test Import Excel - Versi Paling Sederhana
 * Untuk debugging masalah import
 */

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Import Excel</h1>";
echo "<pre>";

// Test 1: Cek PhpSpreadsheet
echo "=== TEST 1: Cek PhpSpreadsheet ===\n";
$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
    if (class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        echo "✓ PhpSpreadsheet tersedia\n";
    } else {
        echo "✗ PhpSpreadsheet class tidak ditemukan\n";
    }
} else {
    echo "✗ Autoload file tidak ditemukan: $autoload_path\n";
}

// Test 2: Cek POST dan FILES
echo "\n=== TEST 2: Cek POST dan FILES ===\n";
echo "POST keys: " . implode(', ', array_keys($_POST)) . "\n";
echo "FILES keys: " . implode(', ', array_keys($_FILES)) . "\n";
echo "import_data isset: " . (isset($_POST['import_data']) ? 'YES' : 'NO') . "\n";
echo "file_import isset: " . (isset($_FILES['file_import']) ? 'YES' : 'NO') . "\n";

if (isset($_FILES['file_import'])) {
    $file = $_FILES['file_import'];
    echo "file name: " . ($file['name'] ?? 'N/A') . "\n";
    echo "file error: " . ($file['error'] ?? 'N/A') . "\n";
    echo "file size: " . ($file['size'] ?? 'N/A') . " bytes\n";
    echo "tmp_name: " . ($file['tmp_name'] ?? 'N/A') . "\n";
    echo "tmp_name exists: " . (isset($file['tmp_name']) && file_exists($file['tmp_name']) ? 'YES' : 'NO') . "\n";
}

// Test 3: Cek database
echo "\n=== TEST 3: Cek Database ===\n";
include_once('../config/config.php');
if (isset($koneksi) && $koneksi) {
    echo "✓ Database connection tersedia\n";
    $test_query = $koneksi->query("SELECT 1");
    if ($test_query) {
        echo "✓ Database query berhasil\n";
    } else {
        echo "✗ Database query gagal: " . $koneksi->error . "\n";
    }
} else {
    echo "✗ Database connection tidak tersedia\n";
}

// Test 4: Jika ada file upload, coba baca
if (isset($_POST['test_read']) && isset($_FILES['file_import']) && $_FILES['file_import']['error'] == UPLOAD_ERR_OK) {
    echo "\n=== TEST 4: Coba Baca Excel ===\n";
    $file = $_FILES['file_import'];

    if (file_exists($file['tmp_name'])) {
        echo "File temp ditemukan: " . $file['tmp_name'] . "\n";

        $autoload_path = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoload_path)) {
            require_once $autoload_path;

            try {
                $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                echo "File extension: $file_ext\n";

                if ($file_ext == 'xlsx') {
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
                } elseif ($file_ext == 'xls') {
                    $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
                } else {
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file['tmp_name']);
                }

                $reader->setReadDataOnly(true);
                $spreadsheet = $reader->load($file['tmp_name']);
                $sheetNames = $spreadsheet->getSheetNames();

                echo "✓ File berhasil dibaca!\n";
                echo "Total sheets: " . count($sheetNames) . "\n";
                echo "Sheet names: " . implode(', ', $sheetNames) . "\n";

                // Tampilkan jumlah baris per sheet
                foreach ($sheetNames as $sheetIndex => $sheetName) {
                    $worksheet = $spreadsheet->getSheet($sheetIndex);
                    $rowCount = 0;
                    foreach ($worksheet->getRowIterator() as $row) {
                        $rowCount++;
                        if ($rowCount > 1000) break; // Limit untuk performa
                    }
                    echo "Sheet '$sheetName': ~$rowCount rows\n";
                }

            } catch (Exception $e) {
                echo "✗ Error membaca file: " . $e->getMessage() . "\n";
                echo "Stack trace: " . $e->getTraceAsString() . "\n";
            }
        } else {
            echo "✗ Autoload tidak ditemukan\n";
        }
    } else {
        echo "✗ File temp tidak ditemukan\n";
    }
}

echo "</pre>";

// Form test
?>
<h2>Test Upload File</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="file_import" accept=".xls,.xlsx" required>
    <button type="submit" name="test_read">Test Baca File</button>
</form>


