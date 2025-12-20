<?php
/**
 * Test Upload CSV - Script untuk test upload dan parsing CSV
 */

include_once('../header.php');
include_once('../config/config.php');

echo "<h2>Test Upload & Parse CSV</h2>";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['test_csv'])) {
    $file = $_FILES['test_csv'];

    echo "<h3>File Info:</h3>";
    echo "Name: " . htmlspecialchars($file['name']) . "<br>";
    echo "Size: " . number_format($file['size'] / 1024, 2) . " KB<br>";
    echo "Type: " . htmlspecialchars($file['type']) . "<br>";
    echo "Error: " . $file['error'] . "<br>";
    echo "Tmp Name: " . htmlspecialchars($file['tmp_name']) . "<br>";
    echo "<br>";

    if ($file['error'] === UPLOAD_ERR_OK && is_uploaded_file($file['tmp_name'])) {
        echo "<h3>File Content (Raw):</h3>";
        $content = file_get_contents($file['tmp_name']);
        echo "<pre>" . htmlspecialchars(substr($content, 0, 1000)) . "</pre>";
        echo "<br>";

        echo "<h3>Parsing CSV:</h3>";
        $rows = [];
        $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'CP1252'];
        $success_read = false;

        foreach ($encodings as $encoding) {
            $content = @file_get_contents($file['tmp_name']);
            if ($content === false) continue;

            $content_utf8 = @mb_convert_encoding($content, 'UTF-8', $encoding);
            if ($content_utf8 === false) continue;

            $temp_file = sys_get_temp_dir() . '/test_parse_' . time() . '_' . rand(1000,9999) . '.csv';
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

            echo "Encoding: $encoding, Delimiter: " . ($delimiter == "\t" ? "TAB" : ($delimiter == ";" ? "SEMICOLON" : "COMMA")) . "<br>";

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
                        echo "Baris $line_num di-skip sebagai header (found $header_count keywords): " . implode(', ', $row) . "<br>";
                        continue;
                    } else {
                        echo "Baris $line_num TIDAK di-skip sebagai header (found $header_count keywords)<br>";
                    }
                }

                // Skip instruksi
                $row_combined = strtolower(implode(' ', $row));
                if (strpos($row_combined, 'catatan') !== false ||
                    strpos($row_combined, 'format') !== false ||
                    strpos($row_combined, 'baris ini') !== false) {
                    echo "Baris $line_num di-skip sebagai instruksi<br>";
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
                    echo "Baris $line_num ditambahkan: " . json_encode($row_filtered) . "<br>";
                } else {
                    echo "Baris $line_num di-skip karena kosong<br>";
                }
            }

            fclose($handle);
            @unlink($temp_file);

            if (count($rows_temp) > 0) {
                $rows = $rows_temp;
                $success_read = true;
                echo "<br><strong>Berhasil membaca dengan encoding: $encoding</strong><br>";
                echo "Total rows: " . count($rows) . "<br>";
                break;
            }
        }

        if ($success_read && !empty($rows)) {
            echo "<h3>Parsed Data (First 10 rows):</h3>";
            echo "<table border='1' cellpadding='5'>";
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

            echo "<br><h3>Test Insert:</h3>";
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

                if ($koneksi->query($query)) {
                    echo "✓ Row " . ($idx + 1) . " berhasil diinsert - Kode: $kode<br>";
                    $test_success++;
                } else {
                    echo "✗ Row " . ($idx + 1) . " gagal - " . $koneksi->error . "<br>";
                    $test_error++;
                }
            }

            $koneksi->query("SET FOREIGN_KEY_CHECKS = 1");

            echo "<br><strong>Hasil Test Insert:</strong> Success: $test_success, Error: $test_error<br>";
        } else {
            echo "<h3>❌ Gagal membaca file CSV!</h3>";
            echo "Tidak ada data yang berhasil di-parse dari file.<br>";
        }
    } else {
        echo "<h3>❌ Upload Error!</h3>";
        echo "Error code: " . $file['error'] . "<br>";
    }
} else {
    echo "<form method='POST' enctype='multipart/form-data'>";
    echo "<h3>Upload File CSV untuk Test:</h3>";
    echo "<input type='file' name='test_csv' accept='.csv' required><br><br>";
    echo "<button type='submit' class='btn btn-primary'>Test Parse CSV</button>";
    echo "</form>";
}

echo "<br><a href='jenis_bayar.php' class='btn btn-secondary'>Kembali ke Produk & Harga</a>";
include_once('../footer.php');
?>




