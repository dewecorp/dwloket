<?php
/**
 * Debug CSV Import - Script untuk debug import CSV
 * Gunakan script ini untuk melihat detail proses import
 */

include_once('../header.php');
include_once('../config/config.php');

echo "<h2>Debug CSV Import</h2>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; }
    .error { color: red; }
    .warning { color: orange; }
    pre { background: #f5f5f5; padding: 10px; border: 1px solid #ddd; overflow-x: auto; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
</style>";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['debug_csv'])) {
    $file = $_FILES['debug_csv'];

    echo "<h3>File Info:</h3>";
    echo "<table>";
    echo "<tr><th>Property</th><th>Value</th></tr>";
    echo "<tr><td>Name</td><td>" . htmlspecialchars($file['name']) . "</td></tr>";
    echo "<tr><td>Size</td><td>" . number_format($file['size'] / 1024, 2) . " KB</td></tr>";
    echo "<tr><td>Type</td><td>" . htmlspecialchars($file['type']) . "</td></tr>";
    echo "<tr><td>Error Code</td><td>" . $file['error'] . "</td></tr>";
    echo "<tr><td>Tmp Name</td><td>" . htmlspecialchars($file['tmp_name']) . "</td></tr>";
    echo "<tr><td>File Exists</td><td>" . (file_exists($file['tmp_name']) ? 'YES' : 'NO') . "</td></tr>";
    echo "</table>";

    if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
        echo "<h3>Step 1: Reading File</h3>";
        $content = file_get_contents($file['tmp_name']);
        echo "<p>Raw content length: " . strlen($content) . " bytes</p>";
        echo "<p>First 500 characters:</p>";
        echo "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "</pre>";

        echo "<h3>Step 2: Parsing CSV</h3>";
        $rows = [];
        $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'CP1252'];
        $success_read = false;

        foreach ($encodings as $encoding) {
            echo "<h4>Trying encoding: $encoding</h4>";
            $content = @file_get_contents($file['tmp_name']);
            if ($content === false) {
                echo "<p class='error'>Failed to read file</p>";
                continue;
            }

            $content_utf8 = @mb_convert_encoding($content, 'UTF-8', $encoding);
            if ($content_utf8 === false) {
                echo "<p class='error'>Failed to convert encoding</p>";
                continue;
            }

            $temp_file = sys_get_temp_dir() . '/debug_parse_' . time() . '_' . rand(1000,9999) . '.csv';
            @file_put_contents($temp_file, $content_utf8);

            $handle = @fopen($temp_file, 'r');
            if ($handle === false) {
                echo "<p class='error'>Failed to open temp file</p>";
                continue;
            }

            // Deteksi delimiter
            $sample = @fgets($handle);
            if ($sample === false) {
                echo "<p class='error'>Failed to read sample line</p>";
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

            echo "<p>Delimiter detected: " . ($delimiter == "\t" ? "TAB" : ($delimiter == ";" ? "SEMICOLON" : "COMMA")) . "</p>";

            // Skip BOM
            $first_char = @fread($handle, 3);
            if ($first_char !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $rows_temp = [];
            $line_num = 0;

            while (($row = @fgetcsv($handle, 0, $delimiter)) !== false) {
                $line_num++;

                // Skip header
                if ($line_num == 1) {
                    $first_row_lower = strtolower(implode(' ', $row));
                    $header_keywords = ['kategori', 'produk', 'harga', 'kode', 'status'];
                    $header_count = 0;
                    foreach ($header_keywords as $keyword) {
                        if (strpos($first_row_lower, $keyword) !== false) {
                            $header_count++;
                        }
                    }
                    if ($header_count >= 3) {
                        echo "<p class='warning'>Line $line_num skipped as header (found $header_count keywords)</p>";
                        continue;
                    }
                }

                // Skip instruksi
                $row_combined = strtolower(implode(' ', $row));
                if (strpos($row_combined, 'catatan') !== false ||
                    strpos($row_combined, 'format') !== false ||
                    strpos($row_combined, 'baris ini') !== false) {
                    echo "<p class='warning'>Line $line_num skipped as instruction</p>";
                    continue;
                }

                // Filter
                $row_filtered = array_map(function($cell) {
                    $cell = trim($cell);
                    $cell = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $cell);
                    return $cell;
                }, $row);

                $has_data = false;
                foreach ($row_filtered as $cell) {
                    if (!empty(trim($cell))) {
                        $has_data = true;
                        break;
                    }
                }

                if ($has_data) {
                    $rows_temp[] = $row_filtered;
                    echo "<p class='success'>Line $line_num added: " . json_encode($row_filtered) . "</p>";
                } else {
                    echo "<p class='warning'>Line $line_num skipped (empty)</p>";
                }
            }

            fclose($handle);
            @unlink($temp_file);

            if (count($rows_temp) > 0) {
                $rows = $rows_temp;
                $success_read = true;
                echo "<p class='success'><strong>Successfully read with encoding: $encoding</strong></p>";
                echo "<p>Total rows: " . count($rows) . "</p>";
                break;
            } else {
                echo "<p class='error'>No data found with encoding: $encoding</p>";
            }
        }

        if ($success_read && !empty($rows)) {
            echo "<h3>Step 3: Parsed Data (First 10 rows)</h3>";
            echo "<table>";
            echo "<tr><th>Row</th><th>Kode</th><th>Produk</th><th>Harga</th><th>Status</th></tr>";

            foreach (array_slice($rows, 0, 10) as $idx => $row) {
                $kode = isset($row[0]) ? trim($row[0]) : '';
                $produk = isset($row[1]) ? trim($row[1]) : '';
                $harga = isset($row[2]) ? trim($row[2]) : '';
                $status = isset($row[3]) ? trim($row[3]) : '';

                echo "<tr>";
                echo "<td>" . ($idx + 1) . "</td>";
                echo "<td>" . htmlspecialchars($kode) . "</td>";
                echo "<td>" . htmlspecialchars($produk) . "</td>";
                echo "<td>" . htmlspecialchars($harga) . "</td>";
                echo "<td>" . htmlspecialchars($status) . "</td>";
                echo "</tr>";
            }
            echo "</table>";

            echo "<h3>Step 4: Test Insert (First 3 rows)</h3>";
            $koneksi->query("SET FOREIGN_KEY_CHECKS = 0");

            $test_success = 0;
            $test_error = 0;

            foreach (array_slice($rows, 0, 3) as $idx => $row) {
                $kode = isset($row[0]) ? trim($row[0]) : '';
                $produk = isset($row[1]) ? trim($row[1]) : '';
                $harga_str = isset($row[2]) ? trim($row[2]) : '';
                $status_val = isset($row[3]) ? strtolower(trim($row[3])) : 'aktif';

                if (empty($kode)) $kode = 'TEST' . ($idx + 1);
                if (empty($produk)) $produk = 'Test Produk ' . ($idx + 1);

                // Parse harga
                $harga_clean = preg_replace('/[^0-9]/', '', $harga_str);
                $harga = floatval($harga_clean);
                if ($harga <= 0) $harga = 1000;

                // Parse status
                $status = (in_array($status_val, ['1', 'aktif', 'yes', 'y', 'true']) ? 1 : 0);

                $kode_esc = mysqli_real_escape_string($koneksi, $kode);
                $produk_esc = mysqli_real_escape_string($koneksi, $produk);

                $query = "INSERT INTO tb_produk_orderkuota (kode, produk, kategori, harga, status, id_bayar)
                          VALUES ('$kode_esc', '$produk_esc', 'UMUM', $harga, $status, NULL)
                          ON DUPLICATE KEY UPDATE
                          produk = VALUES(produk),
                          updated_at = CURRENT_TIMESTAMP";

                echo "<h4>Row " . ($idx + 1) . ":</h4>";
                echo "<p>Kode: '$kode' → '$kode_esc'</p>";
                echo "<p>Produk: '$produk' → '$produk_esc'</p>";
                echo "<p>Harga: '$harga_str' → $harga</p>";
                echo "<p>Status: '$status_val' → $status</p>";
                echo "<p>Query: <pre>" . htmlspecialchars($query) . "</pre></p>";

                if ($koneksi->query($query)) {
                    echo "<p class='success'>✓ SUCCESS - Inserted/Updated</p>";
                    $test_success++;
                } else {
                    echo "<p class='error'>✗ ERROR - " . $koneksi->error . "</p>";
                    $test_error++;
                }
                echo "<hr>";
            }

            $koneksi->query("SET FOREIGN_KEY_CHECKS = 1");

            echo "<h3>Test Results:</h3>";
            echo "<p class='success'>Success: $test_success</p>";
            echo "<p class='error'>Error: $test_error</p>";
        } else {
            echo "<h3 class='error'>❌ Failed to read CSV file!</h3>";
            echo "<p>No data was successfully parsed from the file.</p>";
        }
    } else {
        echo "<h3 class='error'>❌ Upload Error!</h3>";
        echo "<p>Error code: " . $file['error'] . "</p>";
    }
} else {
    echo "<form method='POST' enctype='multipart/form-data'>";
    echo "<h3>Upload CSV File untuk Debug:</h3>";
    echo "<input type='file' name='debug_csv' accept='.csv' required><br><br>";
    echo "<button type='submit' class='btn btn-primary'>Debug CSV Import</button>";
    echo "</form>";
}

echo "<br><a href='jenis_bayar.php' class='btn btn-secondary'>Kembali ke Produk & Harga</a>";
include_once('../footer.php');
?>




