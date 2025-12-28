<?php
$page_title = 'Jenis Pembayaran';
include_once('../header.php');
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

// Cek apakah tabel produk sudah ada
$table_exists = false;
$check_table = $koneksi->query("SHOW TABLES LIKE 'tb_produk_orderkuota'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
    require_once '../libs/produk_helper.php';
}

// Handle Tambah Produk (harus dilakukan sebelum membaca session message)
if (isset($_POST['tambah_produk_modal'])) {
    $kode = mysqli_real_escape_string($koneksi, $_POST['kode'] ?? '');
    $produk = mysqli_real_escape_string($koneksi, $_POST['produk'] ?? '');
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori'] ?? '');
    $harga = floatval($_POST['harga'] ?? 0);
    $status = intval($_POST['status'] ?? 1);

    if (empty($kode) || empty($produk) || empty($kategori) || $harga <= 0) {
        $_SESSION['tambah_message'] = 'Mohon lengkapi semua field yang wajib diisi!';
        $_SESSION['tambah_success'] = false;
    } else {
        // Cek apakah kode sudah ada
        $check_query = "SELECT id_produk FROM tb_produk_orderkuota WHERE kode = '$kode'";
        $check_result = $koneksi->query($check_query);

        if ($check_result && $check_result->num_rows > 0) {
            $_SESSION['tambah_message'] = "Kode produk sudah ada: $kode";
            $_SESSION['tambah_success'] = false;
        } else {
            $insert_query = "INSERT INTO tb_produk_orderkuota
                            (kode, produk, kategori, harga, status, id_bayar)
                            VALUES ('$kode', '$produk', '$kategori', $harga, $status, NULL)";

            if ($koneksi->query($insert_query)) {
                $_SESSION['tambah_message'] = 'Produk berhasil ditambahkan';
                $_SESSION['tambah_success'] = true;
            } else {
                $_SESSION['tambah_message'] = 'Error: ' . $koneksi->error;
                $_SESSION['tambah_success'] = false;
            }
        }
    }
    header('Location: ' . base_url('jenisbayar/jenis_bayar.php'));
    exit;
}

// Inisialisasi variabel pesan
$import_message = '';
$import_success = false;
$import_success_count = 0;
$import_skip_count = 0;
$import_error_count = 0;
$hapus_message = '';
$hapus_success = false;
$update_message = '';
$update_success = false;
$update_message_html = false;
$tambah_message = '';
$tambah_success = false;

// Tampilkan pesan import jika ada (dan clear session SETELAH diambil agar tidak muncul lagi setelah reload)
// Ambil jika ada session meskipun kosong (bisa jadi ada count)
// PASTIKAN session_start sudah dipanggil
if (!isset($_SESSION)) {
    @session_start();
}

// Debug: log session sebelum diambil

if (isset($_SESSION['import_message']) || isset($_SESSION['import_success_count']) || isset($_SESSION['import_success'])) {
    $import_message = isset($_SESSION['import_message']) ? $_SESSION['import_message'] : '';
    $import_success = isset($_SESSION['import_success']) ? (bool)$_SESSION['import_success'] : false;
    $import_success_count = isset($_SESSION['import_success_count']) ? intval($_SESSION['import_success_count']) : 0;
    $import_skip_count = isset($_SESSION['import_skip_count']) ? intval($_SESSION['import_skip_count']) : 0;
    $import_error_count = isset($_SESSION['import_error_count']) ? intval($_SESSION['import_error_count']) : 0;

    // Debug: log nilai yang diambil

    // Clear session SETELAH diambil agar tidak muncul lagi setelah reload
    unset($_SESSION['import_message']);
    unset($_SESSION['import_success']);
    unset($_SESSION['import_success_count']);
    unset($_SESSION['import_skip_count']);
    unset($_SESSION['import_error_count']);
}

if (isset($_SESSION['fix_harga_message'])) {
    $fix_harga_message = $_SESSION['fix_harga_message'];
    $fix_harga_success = isset($_SESSION['fix_harga_success']) ? $_SESSION['fix_harga_success'] : false;
}

// Tampilkan pesan hapus jika ada
if (isset($_SESSION['hapus_message'])) {
    $hapus_message = $_SESSION['hapus_message'];
    $hapus_success = $_SESSION['hapus_success'];
    unset($_SESSION['hapus_message']);
    unset($_SESSION['hapus_success']);
}

// Tampilkan pesan update jika ada
if (isset($_SESSION['update_message'])) {
    $update_message = $_SESSION['update_message'];
    $update_success = $_SESSION['update_success'];
    $update_message_html = isset($_SESSION['update_message_html']) ? $_SESSION['update_message_html'] : false;
    unset($_SESSION['update_message']);
    unset($_SESSION['update_success']);
    unset($_SESSION['update_message_html']);
}

// Tampilkan pesan tambah jika ada
if (isset($_SESSION['tambah_message'])) {
    $tambah_message = $_SESSION['tambah_message'];
    $tambah_success = $_SESSION['tambah_success'];
    unset($_SESSION['tambah_message']);
    unset($_SESSION['tambah_success']);
}

// Fungsi untuk membaca Excel dengan multiple sheets (nama sheet = kategori)
function readExcelWithSheets($file_path) {
    // Cek apakah PhpSpreadsheet tersedia
    $phpspreadsheet_path = __DIR__ . '/../vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php';
    $autoload_path = __DIR__ . '/../vendor/autoload.php';

    if (file_exists($phpspreadsheet_path) || file_exists($autoload_path)) {
        if (file_exists($autoload_path)) {
            require_once $autoload_path;
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

            $sheets_data = [];

            // Loop setiap sheet berdasarkan nama sheet untuk memastikan semua diproses
            foreach ($sheetNames as $sheetIndex => $sheetName) {
                // Ambil worksheet berdasarkan index
                $worksheet = $spreadsheet->getSheet($sheetIndex);

                // Gunakan nama dari array sheetNames untuk konsistensi
                $sheet_name_raw = $sheetName;
                $sheet_name = trim($sheet_name_raw);

                // Debug: log nama sheet yang sedang diproses

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
                } else {
                }
            }

            // Debug: log total sheet yang berhasil dibaca
            $sheet_names_list = implode(", ", array_keys($sheets_data));

            return ['success' => true, 'sheets' => $sheets_data];
        } catch (\PhpOffice\PhpSpreadsheet\Reader\Exception $e) {
            return ['success' => false, 'message' => 'Error membaca file Excel: ' . $e->getMessage() . '. Pastikan file Excel tidak korup dan format benar.'];
        } catch (\PhpOffice\PhpSpreadsheet\Exception $e) {
            return ['success' => false, 'message' => 'Error memproses file Excel: ' . $e->getMessage() . '. Pastikan file Excel valid.'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error membaca Excel: ' . $e->getMessage()];
        } catch (Error $e) {
            return ['success' => false, 'message' => 'Fatal error membaca Excel: ' . $e->getMessage()];
        }
    }

    return ['success' => false, 'message' => 'PhpSpreadsheet library tidak tersedia. Silakan install dengan: composer require phpoffice/phpspreadsheet'];
}

// Handle Import Excel dari folder orderkuota
if (isset($_POST['import_from_orderkuota']) && isset($_SESSION['level']) && $_SESSION['level'] == 'admin') {
    @set_time_limit(600); // 10 menit
    @ini_set('memory_limit', '512M');

    $orderkuota_folder = __DIR__ . '/../orderkuota/';
    $excel_files = [];

    // Cari semua file Excel di folder orderkuota
    if (is_dir($orderkuota_folder)) {
        $files = scandir($orderkuota_folder);
        foreach ($files as $file) {
            if (preg_match('/\.(xls|xlsx)$/i', $file)) {
                $file_path = $orderkuota_folder . $file;
                // Validasi file ada dan bisa dibaca
                if (file_exists($file_path) && is_readable($file_path) && filesize($file_path) > 0) {
                    $excel_files[] = $file_path;
                } else {
                }
            }
        }

        // Sort files: prioritaskan "Daftar Harga_ok.xls" atau file dengan nama "ok" di awal
        usort($excel_files, function($a, $b) {
            $a_name = basename($a);
            $b_name = basename($b);
            $a_has_ok = (stripos($a_name, 'ok') !== false || stripos($a_name, 'harga_ok') !== false);
            $b_has_ok = (stripos($b_name, 'ok') !== false || stripos($b_name, 'harga_ok') !== false);

            if ($a_has_ok && !$b_has_ok) return -1;
            if (!$a_has_ok && $b_has_ok) return 1;
            return strcasecmp($a_name, $b_name);
        });
    }

    if (empty($excel_files)) {
        $_SESSION['import_message'] = "Tidak ada file Excel ditemukan di folder orderkuota atau file tidak valid.";
        $_SESSION['import_success'] = false;
        $_SESSION['import_success_count'] = 0;
        $_SESSION['import_skip_count'] = 0;
        $_SESSION['import_error_count'] = 0;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }

    $total_success = 0;
    $total_skip = 0;
    $total_error = 0;
    $processed_files = [];
    $errors = [];

    // Cache kode yang sudah ada
    $existing_codes = [];
    $codes_query = $koneksi->query("SELECT kode FROM tb_produk_orderkuota");
    if ($codes_query) {
        while ($code_row = $codes_query->fetch_assoc()) {
            $existing_codes[$code_row['kode']] = true;
        }
    }

    // Proses setiap file Excel
    foreach ($excel_files as $excel_file) {
        $filename = basename($excel_file);

        // Validasi file sebelum diproses
        if (!file_exists($excel_file)) {
            $errors[] = "File $filename: File tidak ditemukan";
            $total_error++;
            continue;
        }

        if (!is_readable($excel_file)) {
            $errors[] = "File $filename: File tidak bisa dibaca (permission denied)";
            $total_error++;
            continue;
        }

        if (filesize($excel_file) == 0) {
            $errors[] = "File $filename: File kosong (0 bytes)";
            $total_error++;
            continue;
        }

        $excel_result = readExcelWithSheets($excel_file);

        if (!$excel_result['success']) {
            $errors[] = "File $filename: " . $excel_result['message'];
            $total_error++;
            continue;
        }

        $sheets_data = $excel_result['sheets'];

        if (empty($sheets_data) || count($sheets_data) == 0) {
            $errors[] = "File $filename: Tidak memiliki data sheet yang valid.";
            continue;
        }

        $file_success = 0;
        $file_skip = 0;
        $file_error = 0;

        // Loop setiap sheet (kategori) - nama sheet = kategori
        foreach ($sheets_data as $sheet_name_key => $rows) {
            $kategori = trim($sheet_name_key);
            $kategori = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $kategori);
            $kategori = preg_replace('/\s+/', ' ', $kategori);
            $kategori = trim($kategori);

            if (empty($kategori)) {
                $kategori = 'UMUM';
            }

            if (strlen($kategori) > 100) {
                $kategori = substr($kategori, 0, 100);
                $kategori = trim($kategori);
            }

            $header_skipped = false;
            $row_index = 0;

            foreach ($rows as $row) {
                $row_index++;
                $cols = array_map('trim', $row);
                $col_count = count($cols);

                // Skip header
                if (!$header_skipped && $row_index == 1) {
                    $first_row_lower = strtolower(implode(' ', $cols));
                    if (strpos($first_row_lower, 'kode') !== false ||
                        strpos($first_row_lower, 'produk') !== false ||
                        strpos($first_row_lower, 'harga') !== false ||
                        strpos($first_row_lower, 'keterangan') !== false) {
                        $header_skipped = true;
                        continue;
                    }
                }

                if ($col_count < 2) {
                    $file_skip++;
                    continue;
                }

                $kode = '';
                $produk_nama = '';
                $harga_str = '';
                $harga = 0;
                $status = 1;

                // Deteksi format kolom
                $col0 = trim($cols[0]);
                $col1 = isset($cols[1]) ? trim($cols[1]) : '';
                $col2 = isset($cols[2]) ? trim($cols[2]) : '';

                $is_kategori_format = false;
                if ($col_count >= 3 && strlen($col0) > 15 && !preg_match('/^[A-Z0-9\-]{1,20}$/i', $col0)) {
                    $col2_clean = preg_replace('/[^0-9]/', '', $col2);
                    if (strlen($col2_clean) >= 3 && !empty($col1) && !empty($col2)) {
                        $is_kategori_format = true;
                    }
                }

                if ($col_count >= 6) {
                    $kode = trim($cols[0]);
                    $produk_nama = !empty(trim($cols[2])) ? trim($cols[2]) : trim($cols[1]);
                    $harga_str = isset($cols[4]) ? trim($cols[4]) : '';
                    if (isset($cols[5])) {
                        $status_val = strtolower(trim($cols[5]));
                        $status = (in_array($status_val, ['1', 'aktif', 'yes', 'y', 'true']) ? 1 : 0);
                    }
                } elseif ($is_kategori_format && $col_count >= 3) {
                    $produk_nama = trim($cols[1]);
                    $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                } elseif ($col_count >= 4) {
                    if (preg_match('/^[A-Z0-9\-]+$/i', $cols[0]) && strlen($cols[0]) <= 20) {
                        $kode = trim($cols[0]);
                        $produk_nama = !empty(trim($cols[1])) ? trim($cols[1]) : trim($cols[0]);
                        $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                        if (isset($cols[3])) {
                            $status_val = strtolower(trim($cols[3]));
                            $status = (in_array($status_val, ['1', 'aktif', 'yes', 'y', 'true']) ? 1 : 0);
                        }
                    } else {
                        $produk_nama = trim($cols[0]);
                        $harga_str = isset($cols[1]) ? trim($cols[1]) : '';
                    }
                } elseif ($col_count >= 3) {
                    $col0_clean = preg_replace('/[^A-Z0-9]/i', '', $col0);
                    if (preg_match('/^[A-Z0-9\-]+$/i', $col0) && strlen($col0) <= 20 && strlen($col0_clean) == strlen($col0)) {
                        $kode = trim($cols[0]);
                        $produk_nama = !empty(trim($cols[1])) ? trim($cols[1]) : trim($cols[0]);
                        $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                    } else {
                        $col2_clean = preg_replace('/[^0-9]/', '', $col2);
                        if (strlen($col2_clean) >= 3) {
                            $produk_nama = trim($cols[1]);
                            $harga_str = trim($cols[2]);
                        } else {
                            $produk_nama = trim($cols[0]);
                            $harga_str = isset($cols[1]) ? trim($cols[1]) : '';
                        }
                    }
                } elseif ($col_count >= 2) {
                    $produk_nama = trim($cols[0]);
                    $harga_str = isset($cols[1]) ? trim($cols[1]) : '';
                }

                // Cari harga jika belum ditemukan
                if (empty($harga_str)) {
                    for ($i = $col_count - 1; $i >= 0; $i--) {
                        $test_value = trim($cols[$i]);
                        if (empty($test_value)) continue;
                        $test_value_clean = preg_replace('/^(Rp|IDR|USD|\$|\s)+/i', '', $test_value);
                        $test_value_clean = trim($test_value_clean);
                        preg_match_all('/\d/', $test_value_clean, $digit_matches);
                        $digit_count = count($digit_matches[0]);
                        if ($digit_count >= 4) {
                            $test_clean = preg_replace('/[^0-9]/', '', $test_value_clean);
                            $test_num = floatval($test_clean);
                            if ($test_num >= 1000) {
                                $harga_str = $test_value_clean;
                                break;
                            }
                        }
                    }
                }

                // Parse harga
                if (!empty($harga_str)) {
                    $harga_temp = preg_replace('/^(Rp|IDR|USD|\$|\s)+/i', '', $harga_str);
                    $harga_temp = trim($harga_temp);

                    $dot_count = substr_count($harga_temp, '.');
                    $comma_count = substr_count($harga_temp, ',');

                    if ($dot_count > 0 || $comma_count > 0) {
                        $parts_dot = explode('.', $harga_temp);
                        $parts_comma = explode(',', $harga_temp);

                        $is_dot_thousand = false;
                        $is_comma_thousand = false;

                        if ($dot_count > 0) {
                            $all_3_digits = true;
                            foreach ($parts_dot as $idx => $part) {
                                if ($idx > 0 && strlen($part) != 3 && strlen($part) > 0) {
                                    $all_3_digits = false;
                                    break;
                                }
                            }
                            $is_dot_thousand = ($dot_count > 0 && $all_3_digits);
                        }

                        if ($comma_count > 0 && !$is_dot_thousand) {
                            $all_3_digits = true;
                            foreach ($parts_comma as $idx => $part) {
                                if ($idx > 0 && strlen($part) != 3 && strlen($part) > 0) {
                                    $all_3_digits = false;
                                    break;
                                }
                            }
                            $is_comma_thousand = ($comma_count > 0 && $all_3_digits);
                        }

                        if ($is_dot_thousand) {
                            $digit_only = str_replace('.', '', $harga_temp);
                        } elseif ($is_comma_thousand) {
                            $digit_only = str_replace(',', '', $harga_temp);
                        } else {
                            if ($dot_count > 0 && strlen(end($parts_dot)) <= 2) {
                                $digit_only = str_replace('.', '', $harga_temp);
                                $digit_only = str_replace(',', '', $digit_only);
                            } elseif ($comma_count > 0 && strlen(end($parts_comma)) <= 2) {
                                $digit_only = str_replace(',', '', $harga_temp);
                                $digit_only = str_replace('.', '', $digit_only);
                            } else {
                                $digit_only = preg_replace('/[^0-9]/', '', $harga_temp);
                            }
                        }
                    } else {
                        $digit_only = preg_replace('/[^0-9]/', '', $harga_temp);
                    }

                    if (!empty($digit_only)) {
                        $harga = floatval($digit_only);
                    }
                }

                // Validasi
                if (empty($produk_nama)) {
                    if (!empty($kode)) {
                        $produk_nama = $kode;
                    } else {
                        $file_skip++;
                        continue;
                    }
                }

                if ($harga <= 0) {
                    $file_skip++;
                    continue;
                }

                // Generate kode jika belum ada
                if (empty($kode)) {
                    $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($produk_nama, 0, 20)));
                    if (empty($kode)) {
                        $kode = 'PROD' . str_pad($file_success + $file_skip + 1, 6, '0', STR_PAD_LEFT);
                    }
                }

                // Pastikan kode unik
                $original_kode = $kode;
                $kode_counter = 1;
                while (isset($existing_codes[$kode])) {
                    $kode = $original_kode . '_' . $kode_counter;
                    $kode_counter++;
                }
                $existing_codes[$kode] = true;

                // Escape untuk database
                $kode_escaped = $koneksi->real_escape_string($kode);
                $produk_escaped = $koneksi->real_escape_string($produk_nama);
                $kategori_escaped = $koneksi->real_escape_string($kategori);
                $harga_escaped = floatval($harga);
                $id_bayar = getIdBayarByKategori($kategori, $kategori_mapping);
                $id_bayar_sql = $id_bayar ? intval($id_bayar) : 'NULL';

                // Insert atau Update
                $insert_query = "INSERT INTO tb_produk_orderkuota
                                (kode, produk, kategori, harga, status, id_bayar)
                                VALUES ('$kode_escaped', '$produk_escaped', '$kategori_escaped', $harga_escaped, $status, $id_bayar_sql)
                                ON DUPLICATE KEY UPDATE
                                produk = VALUES(produk),
                                kategori = VALUES(kategori),
                                harga = VALUES(harga),
                                status = VALUES(status),
                                id_bayar = VALUES(id_bayar),
                                updated_at = CURRENT_TIMESTAMP";

                if ($koneksi->query($insert_query)) {
                    $file_success++;
                } else {
                    $file_error++;
                    $errors[] = "File $filename, Sheet $kategori, Baris $row_index: " . $koneksi->error;
                }
            }
        }

        $total_success += $file_success;
        $total_skip += $file_skip;
        $total_error += $file_error;

        $processed_files[] = "$filename: $file_success berhasil, $file_skip skip, $file_error error";
    }

    // Set session message
    $import_message = "Import dari folder orderkuota selesai!\n";
    $import_message .= "✅ Berhasil diimport: $total_success produk\n";
    if ($total_skip > 0) {
        $import_message .= "⚠️ Dilewati (skip): $total_skip produk\n";
    }
    if ($total_error > 0) {
        $import_message .= "❌ Gagal diimport: $total_error produk\n";
    }
    if (!empty($processed_files)) {
        $import_message .= "\nFile yang diproses:\n" . implode("\n", $processed_files);
    }
    if (!empty($errors) && count($errors) <= 10) {
        $import_message .= "\n\nError detail:\n" . implode("\n", array_slice($errors, 0, 10));
    }

    $_SESSION['import_message'] = $import_message;
    $_SESSION['import_success'] = ($total_success > 0);
    $_SESSION['import_success_count'] = (int)$total_success;
    $_SESSION['import_skip_count'] = (int)$total_skip;
    $_SESSION['import_error_count'] = (int)$total_error;

    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Fix Harga - perbaiki harga yang kelebihan 2 digit di belakang
if (isset($_POST['fix_harga_all']) && isset($_SESSION['level']) && $_SESSION['level'] == 'admin') {
    $fixed_count = 0;
    // Ambil semua produk dengan harga >= 100.000 (kemungkinan memiliki kelebihan 2 digit)
    $query = "SELECT id_produk, harga, kode, produk FROM tb_produk_orderkuota WHERE harga >= 100000";
    $result = $koneksi->query($query);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $harga_lama = floatval($row['harga']);
            $harga_baru = $harga_lama / 100;

            // Jika hasil pembagian masuk akal (antara 1.000 sampai 10 juta), perbaiki
            if ($harga_baru >= 1000 && $harga_baru <= 10000000) {
                $id_produk = intval($row['id_produk']);
                $harga_fixed = round($harga_baru, 2);
                $update_query = "UPDATE tb_produk_orderkuota SET harga = $harga_fixed WHERE id_produk = $id_produk";
                if ($koneksi->query($update_query)) {
                    $fixed_count++;
                }
            }
        }
    }

    $_SESSION['fix_harga_message'] = "Berhasil memperbaiki $fixed_count produk dengan harga yang memiliki kelebihan 2 digit";
    $_SESSION['fix_harga_success'] = true;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

if (isset($_POST['import_csv']) && isset($_FILES['file_csv'])) {
    // Set timeout dan memory limit
    @set_time_limit(300); // 5 menit
    @ini_set('memory_limit', '256M');

    // Start output buffering
    if (ob_get_level() == 0) {
        ob_start();
    }

    try {
        $file = $_FILES['file_csv'];

        // Validasi upload file dengan pesan error yang lebih jelas
        if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
            $upload_errors = [
                UPLOAD_ERR_INI_SIZE => 'File terlalu besar (melebihi upload_max_filesize di php.ini)',
                UPLOAD_ERR_FORM_SIZE => 'File terlalu besar (melebihi MAX_FILE_SIZE di form)',
                UPLOAD_ERR_PARTIAL => 'File hanya ter-upload sebagian',
                UPLOAD_ERR_NO_FILE => 'Tidak ada file yang di-upload',
                UPLOAD_ERR_NO_TMP_DIR => 'Folder temporary tidak ditemukan',
                UPLOAD_ERR_CANT_WRITE => 'Gagal menulis file ke disk',
                UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh extension PHP'
            ];
            $error_code = isset($file['error']) ? $file['error'] : 'UNKNOWN';
            $error_msg = isset($upload_errors[$error_code]) ? $upload_errors[$error_code] : "Error upload file dengan kode: $error_code";
            throw new Exception($error_msg);
        }

        if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new Exception("File tidak valid atau tidak ter-upload dengan benar");
        }

        // Validasi ukuran file (maksimal 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception("Ukuran file terlalu besar. Maksimal 10MB");
        }

        if ($file['size'] == 0) {
            throw new Exception("File kosong atau tidak valid");
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        // Support CSV dan Excel files
        if (!in_array($ext, ['csv', 'xlsx', 'xls'])) {
            throw new Exception("File harus berformat CSV atau Excel (.xlsx, .xls)!");
        }

        // Jika file Excel, baca dengan multiple sheets (nama sheet = kategori)
        if (in_array($ext, ['xlsx', 'xls'])) {
            $excel_result = readExcelWithSheets($file['tmp_name']);

            if (!$excel_result['success']) {
                throw new Exception($excel_result['message']);
            }

            $sheets_data = $excel_result['sheets'];

            // Debug: log hasil pembacaan Excel dengan detail

            // Log detail setiap sheet
            foreach ($sheets_data as $sheet_name => $sheet_rows) {
                if (count($sheet_rows) > 0) {
                }
            }

            // Validasi: pastikan ada data sheet
            if (empty($sheets_data) || count($sheets_data) == 0) {
                throw new Exception("File Excel tidak memiliki data. Pastikan file memiliki sheet dengan data yang valid.\n\nTips:\n- Buka file Excel dan pastikan ada data di dalamnya\n- Pastikan sheet tidak kosong\n- Jika file dikonversi dari CSV, pastikan data sudah terisi dengan benar");
            }

            // Validasi: pastikan ada minimal 1 sheet dengan data
            $has_data = false;
            $empty_sheets = [];
            foreach ($sheets_data as $sheet_name => $sheet_rows) {
                if (count($sheet_rows) > 0) {
                    $has_data = true;
                    break;
                } else {
                    $empty_sheets[] = $sheet_name;
                }
            }
            if (!$has_data) {
                $empty_sheets_list = implode(", ", $empty_sheets);
                throw new Exception("Semua sheet di file Excel kosong. Pastikan file memiliki data yang valid.\n\nSheet yang ditemukan: " . (count($empty_sheets) > 0 ? $empty_sheets_list : "Tidak ada") . "\n\nTips:\n- Pastikan ada data di dalam sheet (setelah header)\n- Jika file dikonversi dari CSV, pastikan semua data sudah terisi\n- Format yang didukung: Kode, Produk, Harga, Status");
            }

            $success_count = 0;
            $skip_count = 0;
            $error_count = 0;

            // Cache kode yang sudah ada
            $existing_codes = [];
            $codes_query = $koneksi->query("SELECT kode FROM tb_produk_orderkuota");
            if ($codes_query) {
                while ($code_row = $codes_query->fetch_assoc()) {
                    $existing_codes[$code_row['kode']] = true;
                }
            }

            // Loop setiap sheet (kategori) - nama sheet = kategori
            $total_rows_all_sheets = 0;
            foreach ($sheets_data as $sheet_name_key => $rows) {
                $total_rows_all_sheets += count($rows);

                // Debug: log sample data dari beberapa baris pertama
                if (count($rows) > 0) {
                    $sample_count = min(10, count($rows));
                    for ($s = 0; $s < $sample_count; $s++) {
                    }
                } else {
                }
                // Ambil kategori dari nama sheet - pastikan bersih dan valid
                // Nama sheet sudah dibersihkan di fungsi readExcelWithSheets, tapi kita bersihkan lagi untuk memastikan
                $kategori = trim($sheet_name_key);

                // Bersihkan karakter khusus yang tidak valid untuk kategori
                $kategori = preg_replace('/[^\p{L}\p{N}\s\-_]/u', '', $kategori); // Hanya huruf, angka, spasi, dash, underscore
                $kategori = preg_replace('/\s+/', ' ', $kategori); // Normalisasi multiple spasi menjadi satu spasi
                $kategori = trim($kategori);

                // Jika setelah dibersihkan masih kosong, gunakan fallback
                if (empty($kategori)) {
                    $kategori = 'UMUM';
                }

                // Pastikan kategori tidak terlalu panjang (maksimal 100 karakter sesuai schema)
                if (strlen($kategori) > 100) {
                    $kategori = substr($kategori, 0, 100);
                    $kategori = trim($kategori);
                }

                // PENTING: $kategori sekarang sudah di-set dari nama sheet, JANGAN diubah lagi di dalam loop baris

                // Skip header baris pertama (jika ada)
                $header_skipped = false;
                $row_index = 0;

                // PENTING: $kategori sudah di-set dari nama sheet di awal loop, JANGAN di-overwrite di dalam loop ini
                foreach ($rows as $row) {
                    $row_index++;

                    // Pastikan $row adalah array
                    if (!is_array($row)) {
                        $skip_count++;
                        continue;
                    }

                    $cols = array_map('trim', $row);
                    $col_count = count($cols);

                    // Debug: log setiap baris untuk semua baris (untuk debugging)

                    // Skip header baris pertama jika mengandung kata kunci (lebih spesifik)
                    // Untuk file Excel yang dikonversi dari CSV, header biasanya di baris pertama
                    if (!$header_skipped && $row_index == 1) {
                        $first_row_lower = strtolower(implode(' ', $cols));
                        // Hanya skip jika benar-benar header (mengandung beberapa kata kunci sekaligus)
                        $header_keywords = 0;
                        if (strpos($first_row_lower, 'kode') !== false) $header_keywords++;
                        if (strpos($first_row_lower, 'produk') !== false) $header_keywords++;
                        if (strpos($first_row_lower, 'harga') !== false) $header_keywords++;
                        if (strpos($first_row_lower, 'keterangan') !== false) $header_keywords++;
                        if (strpos($first_row_lower, 'status') !== false) $header_keywords++;

                        // Hanya skip jika minimal 2 kata kunci header ditemukan
                        // Atau jika kolom pertama adalah "Kode" dan ada minimal 3 kolom (kemungkinan besar header)
                        $is_likely_header = ($header_keywords >= 2) ||
                                           (strpos($first_row_lower, 'kode') !== false && $col_count >= 3 &&
                                            (strpos($first_row_lower, 'produk') !== false || strpos($first_row_lower, 'harga') !== false));

                        if ($is_likely_header) {
                            $header_skipped = true;
                            continue;
                        }
                    }

                    // Minimum harus ada 1 kolom (untuk lebih fleksibel)
                    if ($col_count < 1) {
                        $skip_count++;
                        continue;
                    }

                    // Skip baris yang benar-benar kosong (semua kolom kosong)
                    $all_empty = true;
                    foreach ($cols as $col) {
                        if (!empty(trim($col))) {
                            $all_empty = false;
                            break;
                        }
                    }
                    if ($all_empty) {
                        continue; // Skip tanpa increment skip_count karena ini baris kosong
                    }


                    $kode = '';
                    $produk_nama = '';
                    $harga_str = '';
                    $harga = 0;
                    $status = 1;

                    // CATATAN PENTING:
                    // $kategori TIDAK di-set di sini karena sudah diambil dari nama sheet di awal loop foreach
                    // Untuk Excel import, kategori HARUS dari nama sheet, BUKAN dari kolom Excel

                    // Deteksi format kolom - Support beberapa format:
                    // Format 1 (6 kolom): Kode, Keterangan, Produk, Kategori, Harga, Status (sesuai orderkuota/import_excel.php)
                    // Format 2 (4 kolom): Kode, Keterangan, Harga, [Status]
                    // Format 3a (3 kolom dengan kode): Kode, Keterangan, Harga
                    // Format 3b (3 kolom kategori produk harga): Kategori, Produk, Harga (format import_excel_simple.php)
                    // Format 4 (2 kolom): Produk/Deskripsi, Harga

                    // Cek apakah kolom pertama adalah kategori (bukan kode)
                    // Jika kolom pertama berisi teks panjang/tidak seperti kode, mungkin itu kategori
                    $col0 = trim($cols[0]);
                    $col1 = isset($cols[1]) ? trim($cols[1]) : '';
                    $col2 = isset($cols[2]) ? trim($cols[2]) : '';

                    $is_kategori_format = false; // Format: Kategori, Produk, Harga
                    if ($col_count >= 3 && strlen($col0) > 15 && !preg_match('/^[A-Z0-9\-]{1,20}$/i', $col0)) {
                        // Kolom pertama panjang dan tidak seperti kode, kemungkinan kategori
                        // Cek apakah kolom kedua dan ketiga seperti produk dan harga
                        if (!empty($col1) && !empty($col2)) {
                            // Cek apakah kolom ketiga seperti angka (harga)
                            $col2_clean = preg_replace('/[^0-9]/', '', $col2);
                            if (strlen($col2_clean) >= 3) {
                                $is_kategori_format = true;
                            }
                        }
                    }

                    // PRIORITAS: Format 4 kolom (Kode, Keterangan/Produk, Harga, Status) - cek dulu sebelum format lain
                    // Format ini paling umum untuk file Excel yang dikonversi dari CSV
                    if ($col_count == 4) {
                        // Format 4 kolom: Kode, Keterangan/Produk, Harga, Status
                        $kode = trim($cols[0]);
                        $produk_nama = !empty(trim($cols[1])) ? trim($cols[1]) : (!empty(trim($cols[0])) ? trim($cols[0]) : '');
                        $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                        // Status di kolom 3 (index 3)
                        if (isset($cols[3])) {
                            $status_val = strtolower(trim($cols[3]));
                            $status = (in_array($status_val, ['1', 'aktif', 'yes', 'y', 'true']) ? 1 : 0);
                        }
                    } elseif ($col_count == 3) {
                        // Format 3 kolom: Kode, Produk, Harga (tanpa status, default aktif)
                        $kode = trim($cols[0]);
                        $produk_nama = !empty(trim($cols[1])) ? trim($cols[1]) : (!empty(trim($cols[0])) ? trim($cols[0]) : '');
                        $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                        $status = 1; // Default aktif
                    } elseif ($col_count >= 6) {
                        // Format lengkap 6 kolom: Kode, Keterangan, Produk, Kategori, Harga, Status
                        // (Kategori dari sheet tetap digunakan, kolom kategori di Excel diabaikan)
                        $kode = trim($cols[0]);
                        // Coba ambil produk dari kolom 2, jika kosong dari kolom 1
                        $produk_nama = !empty(trim($cols[2])) ? trim($cols[2]) : (!empty(trim($cols[1])) ? trim($cols[1]) : trim($cols[0]));
                        // Coba ambil harga dari kolom 4, jika kosong cari dari kolom lain
                        $harga_str = isset($cols[4]) ? trim($cols[4]) : '';
                        if (empty($harga_str) && isset($cols[3])) {
                            $harga_str = trim($cols[3]);
                        }
                        if (isset($cols[5])) {
                            $status_val = strtolower(trim($cols[5]));
                            $status = (in_array($status_val, ['1', 'aktif', 'yes', 'y', 'true']) ? 1 : 0);
                        }
                    } elseif ($is_kategori_format && $col_count >= 3) {
                        // Format: Kategori, Produk, Harga (sesuai import_excel_simple.php)
                        // Jika kolom pertama berisi kategori, gunakan itu (tapi akan di-overwrite dengan nama sheet)
                        $produk_nama = trim($cols[1]);
                        $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                        // Kode akan di-generate nanti
                    } elseif ($col_count > 4 && $col_count < 6) {
                        // Format 5 kolom atau format alternatif 4 kolom
                        // Cek apakah kolom pertama seperti kode (alphanumeric, pendek)
                        $col0_clean = preg_replace('/[^A-Z0-9]/i', '', trim($cols[0]));
                        $is_kode_format = (strlen($col0_clean) <= 20 && strlen($col0_clean) > 0);

                        // Cek apakah kolom ketiga seperti harga (mengandung angka)
                        $col2_has_number = false;
                        if (isset($cols[2])) {
                            preg_match_all('/\d/', trim($cols[2]), $col2_digits);
                            $col2_has_number = (count($col2_digits[0]) >= 3);
                        }

                        if ($is_kode_format && $col2_has_number) {
                            // Format: Kode, Keterangan/Produk, Harga, Status, ...
                            $kode = trim($cols[0]);
                            $produk_nama = !empty(trim($cols[1])) ? trim($cols[1]) : trim($cols[0]);
                            $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                            // Status di kolom 3 (index 3)
                            if (isset($cols[3])) {
                                $status_val = strtolower(trim($cols[3]));
                                $status = (in_array($status_val, ['1', 'aktif', 'yes', 'y', 'true']) ? 1 : 0);
                            }
                        } else {
                            // Format alternatif: Produk/Deskripsi, Harga, ...
                            $produk_nama = trim($cols[0]);
                            $harga_str = isset($cols[1]) ? trim($cols[1]) : '';
                        }
                    } elseif ($col_count >= 3) {
                        // Cek apakah format Kode, Keterangan, Harga atau Kategori, Produk, Harga
                        $col0_clean = preg_replace('/[^A-Z0-9]/i', '', $col0);
                        if (preg_match('/^[A-Z0-9\-]+$/i', $col0) && strlen($col0) <= 20 && strlen($col0_clean) == strlen($col0)) {
                            // Format: Kode, Keterangan, Harga (kolom 0 adalah kode pendek)
                            $kode = trim($cols[0]);
                            $produk_nama = !empty(trim($cols[1])) ? trim($cols[1]) : trim($cols[0]);
                            $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                        } else {
                            // Format: Kategori, Produk, Harga (kolom 0 mungkin kategori yang panjang)
                            // Atau: Produk, Harga, Lainnya
                            // Cek apakah kolom 2 seperti angka (harga)
                            $col2_clean = preg_replace('/[^0-9]/', '', $col2);
                            if (strlen($col2_clean) >= 3) {
                                // Kolom 2 adalah harga, berarti format: Kategori/Produk, Produk/Harga, Harga
                                // Untuk amannya, anggap kolom 1 = Produk, kolom 2 = Harga
                                $produk_nama = trim($cols[1]);
                                $harga_str = trim($cols[2]);
                            } else {
                                // Format: Produk/Deskripsi, Harga, ...
                                $produk_nama = trim($cols[0]);
                                $harga_str = isset($cols[1]) ? trim($cols[1]) : '';
                            }
                        }
                    } elseif ($col_count >= 2) {
                        // Format minimal: Produk/Deskripsi, Harga
                        $produk_nama = trim($cols[0]);
                        $harga_str = isset($cols[1]) ? trim($cols[1]) : '';
                    } elseif ($col_count >= 1) {
                        // Format 1 kolom: mungkin produk dengan harga di dalamnya atau hanya produk
                        $produk_nama = trim($cols[0]);
                        // Coba cari harga di dalam kolom pertama (jika ada angka)
                        if (preg_match('/(\d[\d.,]*\d|\d+)/', $produk_nama, $matches)) {
                            $harga_str = $matches[1];
                        }
                    }

                    // Cari harga dari semua kolom jika belum ditemukan (dari kanan ke kiri, karena harga biasanya di kanan)
                    if (empty($harga_str)) {
                        for ($i = $col_count - 1; $i >= 0; $i--) {
                            $test_value = trim($cols[$i]);
                            if (empty($test_value)) continue;

                            // Hapus prefix currency
                            $test_value_clean = preg_replace('/^(Rp|IDR|USD|\$|\s)+/i', '', $test_value);
                            $test_value_clean = trim($test_value_clean);

                            // Extract digit
                            preg_match_all('/\d/', $test_value_clean, $digit_matches);
                            $digit_count = count($digit_matches[0]);

                            // Terima minimal 3 digit untuk lebih fleksibel
                            if ($digit_count >= 3) {
                                // Parse dengan deteksi pemisah ribuan
                                $test_dot = substr_count($test_value_clean, '.');
                                $test_comma = substr_count($test_value_clean, ',');

                                $test_clean = $test_value_clean;
                                if ($test_dot > 0 || $test_comma > 0) {
                                    $test_parts = $test_dot > 0 ? explode('.', $test_value_clean) : explode(',', $test_value_clean);
                                    $is_thousand = true;
                                    foreach ($test_parts as $idx => $part) {
                                        if ($idx > 0 && strlen($part) != 3 && strlen($part) > 0) {
                                            $is_thousand = false;
                                            break;
                                        }
                                    }
                                    if ($is_thousand) {
                                        $test_clean = preg_replace('/[^0-9]/', '', $test_value_clean);
                                    } else {
                                        $test_clean = preg_replace('/[^0-9]/', '', $test_value_clean);
                                    }
                                } else {
                                    $test_clean = preg_replace('/[^0-9]/', '', $test_value_clean);
                                }

                                $test_num = floatval($test_clean);
                                // Terima harga minimal 10 untuk lebih fleksibel (untuk testing, bisa dinaikkan nanti)
                                if ($test_num >= 10) {
                                    $harga_str = $test_value_clean;
                                    // Jika produk_nama masih kosong dan ini bukan kolom harga, gunakan sebagai produk
                                    if (empty($produk_nama) && $i > 0) {
                                        $produk_nama = trim($cols[$i - 1]);
                                    }
                                    break;
                                }
                            }
                        }
                    }

                    // Jika masih belum ada harga_str, coba sekali lagi dengan parsing yang lebih agresif
                    if (empty($harga_str)) {
                        for ($i = 0; $i < $col_count; $i++) {
                            $test_value = trim($cols[$i]);
                            if (empty($test_value)) continue;

                            // Hapus semua karakter non-digit kecuali titik dan koma
                            $test_clean = preg_replace('/[^0-9.,]/', '', $test_value);
                            if (empty($test_clean)) continue;

                            // Cek apakah ada angka minimal 3 digit
                            preg_match_all('/\d/', $test_clean, $digit_matches);
                            $digit_count = count($digit_matches[0]);

                            if ($digit_count >= 3) {
                                // Hapus semua pemisah dan ambil angka saja
                                $test_clean = preg_replace('/[^0-9]/', '', $test_clean);
                                $test_num = floatval($test_clean);

                                // Terima harga minimal 10 (untuk testing, bisa dinaikkan nanti)
                                if ($test_num >= 10) {
                                    $harga_str = $test_value;
                                    break;
                                }
                            }
                        }
                    }

                    // Parse harga - handle pemisah ribuan dengan benar (perbaikan untuk menghindari kelebihan 2 nol)
                    if (!empty($harga_str)) {
                        // Debug: log harga_str sebelum parsing

                        $harga_temp = preg_replace('/^(Rp|IDR|USD|\$|\s)+/i', '', $harga_str);
                        $harga_temp = trim($harga_temp);

                        // Jika harga_str adalah angka langsung (tanpa pemisah), langsung parse
                        if (is_numeric($harga_temp)) {
                            $harga = floatval($harga_temp);
                        } else {
                            // Parse dengan deteksi pemisah ribuan
                            // Deteksi format: apakah menggunakan titik atau koma sebagai pemisah ribuan
                            // Format Indonesia: 61.000 (titik = ribuan) atau 61,000 (koma = ribuan)

                            $dot_count = substr_count($harga_temp, '.');
                            $comma_count = substr_count($harga_temp, ',');

                            if ($dot_count > 0 || $comma_count > 0) {
                                // Ada pemisah, perlu deteksi format
                                $parts_dot = explode('.', $harga_temp);
                                $parts_comma = explode(',', $harga_temp);

                                // Deteksi berdasarkan pola pemisah ribuan Indonesia
                                // Format Indonesia: 89.918 atau 89.918.000 (titik = pemisah ribuan, setiap 3 digit)
                                // Jika ada lebih dari 1 bagian dan setiap bagian (kecuali mungkin yang terakhir) panjangnya 3 digit, itu pemisah ribuan

                                $is_dot_thousand = false;
                                $is_comma_thousand = false;

                                // Cek apakah titik adalah pemisah ribuan (setiap bagian 3 digit, kecuali mungkin yang pertama)
                                if ($dot_count > 0) {
                                    $all_3_digits = true;
                                    foreach ($parts_dot as $idx => $part) {
                                        // Bagian pertama bisa 1-3 digit, bagian lainnya harus 3 digit untuk pemisah ribuan
                                        if ($idx > 0 && strlen($part) != 3 && strlen($part) > 0) {
                                            $all_3_digits = false;
                                            break;
                                        }
                                    }
                                    $is_dot_thousand = ($dot_count > 0 && $all_3_digits);
                                }

                                // Cek apakah koma adalah pemisah ribuan
                                if ($comma_count > 0 && !$is_dot_thousand) {
                                    $all_3_digits = true;
                                    foreach ($parts_comma as $idx => $part) {
                                        if ($idx > 0 && strlen($part) != 3 && strlen($part) > 0) {
                                            $all_3_digits = false;
                                            break;
                                        }
                                    }
                                    $is_comma_thousand = ($comma_count > 0 && $all_3_digits);
                                }

                                // Jika titik/koma adalah pemisah ribuan, hapus semua pemisah
                                if ($is_dot_thousand) {
                                    $digit_only = str_replace('.', '', $harga_temp);
                                } else if ($is_comma_thousand) {
                                    $digit_only = str_replace(',', '', $harga_temp);
                                } else {
                                    // Jika tidak sesuai pola pemisah ribuan, mungkin desimal atau format lain
                                    // Cek bagian terakhir (mungkin desimal dengan 2 digit)
                                    if ($dot_count > 0 && strlen(end($parts_dot)) <= 2) {
                                        // Titik terakhir mungkin desimal, hapus semua titik lalu koma
                                        $digit_only = str_replace('.', '', $harga_temp);
                                        $digit_only = str_replace(',', '', $digit_only);
                                    } else if ($comma_count > 0 && strlen(end($parts_comma)) <= 2) {
                                        // Koma terakhir mungkin desimal, hapus semua koma lalu titik
                                        $digit_only = str_replace(',', '', $harga_temp);
                                        $digit_only = str_replace('.', '', $digit_only);
                                    } else {
                                        // Hapus semua pemisah (fallback)
                                        $digit_only = preg_replace('/[^0-9]/', '', $harga_temp);
                                    }
                                }
                            } else {
                                // Tidak ada pemisah, ambil semua digit
                                $digit_only = preg_replace('/[^0-9]/', '', $harga_temp);
                            }

                            if (!empty($digit_only)) {
                                $harga = floatval($digit_only);
                            }
                        }
                    }

                    // Debug: log data sebelum validasi untuk SEMUA baris (untuk debugging)

                    // Validasi - pastikan ada minimal produk_nama (bisa dari kode atau kolom manapun)
                    if (empty($produk_nama)) {
                        // Jika produk_nama kosong, coba ambil dari berbagai sumber
                        if (!empty($kode)) {
                            $produk_nama = $kode;
                        } else if (!empty($col0) && !is_numeric($col0)) {
                            // Coba ambil dari kolom pertama jika bukan angka
                            $produk_nama = $col0;
                        } else if (!empty($col1) && !is_numeric($col1)) {
                            // Coba ambil dari kolom kedua jika bukan angka
                            $produk_nama = $col1;
                        } else {
                            // Coba ambil dari kolom manapun yang berisi teks (bukan angka)
                            for ($i = 0; $i < $col_count; $i++) {
                                $test_col = trim($cols[$i]);
                                if (!empty($test_col) && !is_numeric($test_col) && strlen($test_col) > 2) {
                                    $produk_nama = $test_col;
                                    break;
                                }
                            }

                            // Jika masih kosong, gunakan kolom pertama meskipun angka
                            if (empty($produk_nama) && !empty($col0)) {
                                $produk_nama = $col0;
                            }

                            // Jika masih kosong, gunakan kode auto-generate
                            if (empty($produk_nama)) {
                                $produk_nama = 'PRODUK_' . ($success_count + $skip_count + 1);
                            }
                        }
                    }

                    // Pastikan produk_nama tidak kosong - jika masih kosong, gunakan default
                    if (empty(trim($produk_nama))) {
                        // Coba ambil dari kolom pertama yang ada isinya
                        for ($i = 0; $i < $col_count; $i++) {
                            $test_col = trim($cols[$i]);
                            if (!empty($test_col)) {
                                $produk_nama = $test_col;
                                break;
                            }
                        }
                        // Jika masih kosong, gunakan default
                        if (empty(trim($produk_nama))) {
                            $produk_nama = 'PRODUK_' . $kategori . '_' . $row_index;
                        }
                    }


                    // Validasi harga - jika masih 0, coba parse ulang dari semua kolom
                    if ($harga <= 0 && !empty($harga_str)) {
                        // Coba parse ulang harga dengan parsing yang lebih baik
                        $harga_temp = preg_replace('/^(Rp|IDR|USD|\$|\s)+/i', '', $harga_str);
                        $harga_temp = trim($harga_temp);

                        // Deteksi format harga (ribuan dengan titik/koma)
                        $dot_count = substr_count($harga_temp, '.');
                        $comma_count = substr_count($harga_temp, ',');

                        if ($dot_count > 0 || $comma_count > 0) {
                            $parts_dot = explode('.', $harga_temp);
                            $parts_comma = explode(',', $harga_temp);

                            $is_dot_thousand = false;
                            $is_comma_thousand = false;

                            if ($dot_count > 0) {
                                $all_3_digits = true;
                                foreach ($parts_dot as $idx => $part) {
                                    if ($idx > 0 && strlen($part) != 3 && strlen($part) > 0) {
                                        $all_3_digits = false;
                                        break;
                                    }
                                }
                                $is_dot_thousand = ($dot_count > 0 && $all_3_digits);
                            }

                            if ($comma_count > 0 && !$is_dot_thousand) {
                                $all_3_digits = true;
                                foreach ($parts_comma as $idx => $part) {
                                    if ($idx > 0 && strlen($part) != 3 && strlen($part) > 0) {
                                        $all_3_digits = false;
                                        break;
                                    }
                                }
                                $is_comma_thousand = ($comma_count > 0 && $all_3_digits);
                            }

                            if ($is_dot_thousand) {
                                $digit_only = str_replace('.', '', $harga_temp);
                            } elseif ($is_comma_thousand) {
                                $digit_only = str_replace(',', '', $harga_temp);
                            } else {
                                // Hapus semua pemisah
                                $digit_only = preg_replace('/[^0-9]/', '', $harga_temp);
                            }
                        } else {
                            $digit_only = preg_replace('/[^0-9]/', '', $harga_temp);
                        }

                        if (!empty($digit_only)) {
                            $harga = floatval($digit_only);
                        }
                    }

                    // Jika harga masih 0, cari dari semua kolom (dari kanan ke kiri)
                    if ($harga <= 0) {
                        // Cari angka dari semua kolom
                        for ($i = $col_count - 1; $i >= 0; $i--) {
                            $test_val = trim($cols[$i]);
                            if (empty($test_val)) continue;

                            // Hapus prefix currency
                            $test_clean = preg_replace('/^(Rp|IDR|USD|\$|\s)+/i', '', $test_val);
                            $test_clean = trim($test_clean);

                            // Cek apakah ada angka (minimal 3 digit)
                            preg_match_all('/\d/', $test_clean, $digit_matches);
                            $digit_count = count($digit_matches[0]);

                            if ($digit_count >= 3) {
                                // Parse dengan deteksi pemisah ribuan
                                $dot_count = substr_count($test_clean, '.');
                                $comma_count = substr_count($test_clean, ',');

                                if ($dot_count > 0 || $comma_count > 0) {
                                    $parts_dot = explode('.', $test_clean);
                                    $parts_comma = explode(',', $test_clean);

                                    $is_dot_thousand = false;
                                    if ($dot_count > 0) {
                                        $all_3_digits = true;
                                        foreach ($parts_dot as $idx => $part) {
                                            if ($idx > 0 && strlen($part) != 3 && strlen($part) > 0) {
                                                $all_3_digits = false;
                                                break;
                                            }
                                        }
                                        $is_dot_thousand = ($dot_count > 0 && $all_3_digits);
                                    }

                                    if ($is_dot_thousand) {
                                        $test_clean = str_replace('.', '', $test_clean);
                                    } else {
                                        $test_clean = preg_replace('/[^0-9]/', '', $test_clean);
                                    }
                                } else {
                                    $test_clean = preg_replace('/[^0-9]/', '', $test_clean);
                                }

                                $test_num = floatval($test_clean);

                                // Terima harga minimal 10 (untuk testing, bisa dinaikkan nanti)
                                if ($test_num >= 10) {
                                    $harga = $test_num;
                                    break;
                                }
                            }
                        }
                    }

                    // Validasi harga - jika masih 0, coba sekali lagi dengan parsing yang lebih agresif
                    if ($harga <= 0) {
                        // Coba sekali lagi dengan parsing yang lebih agresif dari semua kolom
                        for ($i = 0; $i < $col_count; $i++) {
                            $test_val = trim($cols[$i]);
                            if (empty($test_val)) continue;

                            // Hapus semua karakter non-digit kecuali titik dan koma
                            $test_clean = preg_replace('/[^0-9.,]/', '', $test_val);
                            if (empty($test_clean)) continue;

                            // Cek apakah ada angka
                            preg_match_all('/\d/', $test_clean, $digit_matches);
                            $digit_count = count($digit_matches[0]);

                            if ($digit_count >= 3) {
                                // Hapus semua pemisah dan ambil angka saja
                                $test_clean = preg_replace('/[^0-9]/', '', $test_clean);
                                $test_num = floatval($test_clean);

                                // Terima harga minimal 10 (untuk testing, bisa dinaikkan nanti)
                                if ($test_num >= 10) {
                                    $harga = $test_num;
                                    break;
                                }
                            }
                        }
                    }

                    // Jika masih belum ada harga, coba sekali lagi dengan parsing yang sangat agresif dari semua kolom
                    if ($harga <= 0) {
                        for ($i = 0; $i < $col_count; $i++) {
                            $test_val = trim($cols[$i]);
                            if (empty($test_val)) continue;

                            // Hapus semua karakter non-digit
                            $test_clean = preg_replace('/[^0-9]/', '', $test_val);
                            if (empty($test_clean)) continue;

                            // Cek apakah ada angka minimal 3 digit
                            if (strlen($test_clean) >= 3) {
                                $test_num = floatval($test_clean);

                                // Terima harga minimal 10 (untuk testing, bisa dinaikkan nanti)
                                if ($test_num >= 10) {
                                    $harga = $test_num;
                                    break;
                                }
                            }
                        }
                    }

                    // Validasi harga - jika masih 0, gunakan default harga
                    if ($harga <= 0) {
                        // Jika masih tidak ada harga, gunakan nilai default minimal (1000)
                        // Ini untuk memastikan data tetap bisa masuk meskipun harga tidak terdeteksi
                        $harga = 1000;
                    }

                    // Pastikan harga minimal 1 (bukan 0)
                    if ($harga <= 0) {
                        $harga = 1000;
                    }

                    // Generate kode jika belum ada
                    if (empty($kode)) {
                        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($produk_nama, 0, 20)));
                        if (empty($kode)) {
                            $kode = 'PROD' . str_pad($success_count + $skip_count + 1, 6, '0', STR_PAD_LEFT);
                        }
                    }

                    // Pastikan kode unik - cek apakah sudah ada di existing_codes atau database
                    $original_kode = $kode;
                    $kode_counter = 1;
                    while (isset($existing_codes[$kode])) {
                        $kode = $original_kode . '_' . $kode_counter;
                        $kode_counter++;
                        // Batasi maksimal 1000 iterasi untuk menghindari infinite loop
                        if ($kode_counter > 1000) {
                            $kode = $original_kode . '_' . time() . '_' . rand(1000, 9999);
                            break;
                        }
                    }
                    // Tambahkan kode ke existing_codes
                    $existing_codes[$kode] = true;

                    // Pastikan semua nilai valid sebelum escape
                    $produk_nama = trim($produk_nama);
                    if (empty($produk_nama)) {
                        // Coba sekali lagi ambil dari kolom pertama
                        if (!empty($cols[0])) {
                            $produk_nama = trim($cols[0]);
                        } else {
                            $produk_nama = 'PRODUK_' . $kategori . '_' . $row_index;
                        }
                    }
                    if ($harga <= 0) {
                        $harga = 1000; // Default harga
                    }

                    // Escape untuk database
                    // PENTING: Untuk Excel import, kategori HARUS dari nama sheet, jangan ambil dari kolom Excel
                    $kode_escaped = $koneksi->real_escape_string($kode);
                    $produk_escaped = $koneksi->real_escape_string($produk_nama);
                    // Pastikan kategori menggunakan nilai dari nama sheet (sudah di-set di awal loop)
                    $kategori_escaped = $koneksi->real_escape_string($kategori);
                    $harga_escaped = floatval($harga);

                    // Final validation - pastikan tidak ada yang kosong
                    if (empty($produk_escaped)) {
                        $produk_escaped = $koneksi->real_escape_string('PRODUK_' . $kategori . '_' . $row_index);
                    }
                    if ($harga_escaped <= 0) {
                        $harga_escaped = 1000;
                    }

                    // Dapatkan id_bayar berdasarkan kategori
                    $id_bayar = getIdBayarByKategori($kategori, $kategori_mapping);
                    $id_bayar_sql = $id_bayar ? intval($id_bayar) : 'NULL';

                    $insert_query = "INSERT INTO tb_produk_orderkuota
                                    (kode, produk, kategori, harga, status, id_bayar)
                                    VALUES ('$kode_escaped', '$produk_escaped', '$kategori_escaped', $harga_escaped, $status, $id_bayar_sql)
                                    ON DUPLICATE KEY UPDATE
                                    produk = VALUES(produk),
                                    kategori = VALUES(kategori),
                                    harga = VALUES(harga),
                                    status = VALUES(status),
                                    id_bayar = VALUES(id_bayar),
                                    updated_at = CURRENT_TIMESTAMP";

                    // Debug: log sebelum insert untuk SEMUA baris (untuk debugging)

                    // Validasi final sebelum insert - JANGAN SKIP, gunakan default jika kosong
                    if (empty(trim($produk_nama))) {
                        // Coba sekali lagi ambil dari kolom pertama
                        if (!empty($cols[0])) {
                            $produk_nama = trim($cols[0]);
                        } else {
                            $produk_nama = 'PRODUK_' . $kategori . '_' . $row_index;
                        }
                    }

                    if ($harga <= 0) {
                        $harga = 1000; // Default harga
                    }

                    // Pastikan semua nilai valid sebelum insert
                    $produk_nama = trim($produk_nama);
                    if (empty($produk_nama)) {
                        $produk_nama = 'PRODUK_' . $kategori . '_' . $row_index;
                    }
                    if ($harga <= 0) {
                        $harga = 1000;
                    }

                    // Test koneksi database
                    if (!$koneksi) {
                        $error_count++;
                        continue; // Skip row instead of throwing exception
                    }

                    if (isset($koneksi->connect_error) && $koneksi->connect_error) {
                        $error_count++;
                        continue; // Skip row instead of throwing exception
                    }

                    // Execute query
                    $query_result = $koneksi->query($insert_query);

                    if ($query_result === true) {
                        $success_count++;
                    } else {
                        $error_count++;
                        $error_detail = $koneksi->error;

                        // Jika error karena duplicate key, coba dengan kode yang berbeda
                        if (strpos($error_detail, 'Duplicate entry') !== false || strpos($error_detail, 'unique_kode') !== false) {
                            // Generate kode baru dan coba lagi sekali
                            $kode_new = $original_kode . '_' . time() . '_' . rand(1000, 9999);
                            $kode_new_escaped = $koneksi->real_escape_string($kode_new);
                            $existing_codes[$kode_new] = true;

                            $insert_query_retry = "INSERT INTO tb_produk_orderkuota
                                                    (kode, produk, kategori, harga, status, id_bayar)
                                                    VALUES ('$kode_new_escaped', '$produk_escaped', '$kategori_escaped', $harga_escaped, $status, $id_bayar_sql)
                                                    ON DUPLICATE KEY UPDATE
                                                    produk = VALUES(produk),
                                                    kategori = VALUES(kategori),
                                                    harga = VALUES(harga),
                                                    status = VALUES(status),
                                                    id_bayar = VALUES(id_bayar),
                                                    updated_at = CURRENT_TIMESTAMP";

                            if ($koneksi->query($insert_query_retry)) {
                                $success_count++;
                                $error_count--; // Kurangi error count karena berhasil di retry
                            }
                        }
                    }
                }
            }

            $import_success = ($success_count > 0);

            // Debug: Log hasil akhir import Excel

            // Log detail per sheet
            foreach ($sheets_data as $sheet_name => $rows) {
            }

            // Log ratio
            if ($total_rows_all_sheets > 0) {
                $success_ratio = ($success_count / $total_rows_all_sheets) * 100;
                $skip_ratio = ($skip_count / $total_rows_all_sheets) * 100;
            }

            // Jika tidak ada data yang masuk, log detail untuk debugging
            if ($success_count == 0) {
            }

            // Buat pesan detail dengan informasi lebih lengkap
            $import_message = "Import Excel selesai!\n";
            $import_message .= "📊 Total baris di semua sheet: $total_rows_all_sheets\n";
            $import_message .= "✅ Berhasil diimport: $success_count produk\n";
            if ($skip_count > 0) {
                $import_message .= "⚠️ Dilewati (skip): $skip_count produk\n";
            }
            if ($error_count > 0) {
                $import_message .= "❌ Gagal diimport: $error_count produk\n";
            }

            // Jika tidak ada data yang masuk, tambahkan pesan warning yang lebih detail
            if ($success_count == 0) {
                $import_message .= "\n\n⚠️ PERINGATAN: Tidak ada data yang berhasil diimport!\n\n";
                $import_message .= "Kemungkinan penyebab:\n";
                $import_message .= "1. Format file tidak sesuai (harus: Kode, Produk, Harga, Status)\n";
                $import_message .= "2. Semua baris di-skip karena header tidak terdeteksi dengan benar\n";
                $import_message .= "3. Data kosong atau tidak valid\n";
                $import_message .= "4. Error saat insert ke database\n\n";
                $import_message .= "💡 Tips:\n";
                $import_message .= "- Pastikan file Excel memiliki minimal 1 sheet dengan data\n";
                $import_message .= "- Pastikan kolom pertama adalah Kode, kolom kedua Produk, kolom ketiga Harga, kolom keempat Status\n";
                $import_message .= "- Header (baris pertama) akan otomatis di-skip jika mengandung kata 'Kode', 'Produk', 'Harga', atau 'Status'\n";
                $import_message .= "- Cek log error PHP untuk detail lebih lanjut\n";
                $import_message .= "📊 Statistik:\n";
                $import_message .= "- Total baris di file: $total_rows_all_sheets\n";
                $import_message .= "- Baris dilewati (skip): $skip_count\n";
                $import_message .= "- Baris error: $error_count\n\n";
                $import_message .= "Kemungkinan penyebab:\n";
                $import_message .= "1. Format file Excel tidak sesuai (harus 4 kolom: Kode, Produk, Harga, Status)\n";
                $import_message .= "2. Semua baris di-skip karena tidak memenuhi kriteria\n";
                $import_message .= "3. Ada error saat insert ke database\n";
                $import_message .= "4. File Excel kosong atau corrupt\n\n";
                $import_message .= "Solusi:\n";
                $import_message .= "- Periksa log error PHP untuk detail masalah (cari 'Import Excel:' di error log)\n";
                $import_message .= "- Pastikan file Excel memiliki data di sheet (bukan hanya header)\n";
                $import_message .= "- Pastikan format: Kolom 1=Kode, Kolom 2=Produk, Kolom 3=Harga, Kolom 4=Status\n";
                $import_message .= "- Pastikan harga dalam format angka (contoh: 10000 atau 10.000)\n";
                $import_message .= "- Pastikan minimal ada 1 kolom dengan data\n";
                $import_message .= "- Coba dengan file Excel yang lebih kecil (5-10 baris) untuk testing\n";
            }

            // Tambahkan info sheet yang diproses
            if (count($sheets_data) > 0) {
                $import_message .= "\nSheet yang diproses: " . implode(", ", array_keys($sheets_data));
            }

            if ($success_count == 0 && $skip_count == 0 && $error_count == 0) {
                $import_message = "Tidak ada data yang diimport. Pastikan file Excel memiliki data yang valid di sheet.\n\n";
                $import_message .= "Tips:\n";
                $import_message .= "- Pastikan file Excel memiliki sheet dengan data\n";
                $import_message .= "- Pastikan setiap baris memiliki minimal 2 kolom (Produk dan Harga)\n";
                $import_message .= "- Pastikan harga dalam format angka (contoh: 10000 atau 10.000)\n";
                $import_message .= "- Nama sheet akan digunakan sebagai kategori produk";
                $import_success = false;
            } else if ($success_count == 0) {
                $import_message = "Tidak ada produk yang berhasil diimport.\n\n";
                $import_message .= "Detail:\n";
                if ($skip_count > 0) {
                    $import_message .= "- $skip_count produk dilewati (data tidak valid)\n";
                }
                if ($error_count > 0) {
                    $import_message .= "- $error_count produk error (cek log untuk detail)\n";
                }
                $import_message .= "\nTips: Pastikan setiap baris memiliki Produk dan Harga yang valid.";
                $import_success = false;
            }

            // Redirect dengan session
            if (!isset($_SESSION)) {
                @session_start();
            }

            // Pastikan pesan error ditampilkan dengan benar
            if (!$import_success && $success_count == 0) {
                if (strpos($import_message, '❌') === false && strpos($import_message, 'PERINGATAN') === false && strpos($import_message, 'Gagal') === false) {
                    $import_message = "❌ Import Gagal!\n\n" . $import_message;
                }
            }

            // Pastikan semua count di-set dengan benar
            $_SESSION['import_message'] = $import_message;
            $_SESSION['import_success'] = (bool)$import_success;
            $_SESSION['import_success_count'] = (int)$success_count;
            $_SESSION['import_skip_count'] = (int)$skip_count;
            $_SESSION['import_error_count'] = (int)$error_count;


            header("Location: " . base_url('jenisbayar/jenis_bayar.php'));
            exit;
        }

        // Limit ukuran file (10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception("Ukuran file terlalu besar! Maksimal 10MB");
        }

        // Baca CSV (untuk file CSV, bukan Excel)
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext == 'csv') {
            // Baca CSV
            $rows = [];
            $encodings = ['UTF-8', 'ISO-8859-1', 'Windows-1252', 'CP1252'];
            $success_read = false;

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
                        if (strpos($first_row_lower, 'kategori') !== false ||
                            strpos($first_row_lower, 'produk') !== false ||
                            strpos($first_row_lower, 'harga') !== false ||
                            strpos($first_row_lower, 'kode') !== false) {
                            continue;
                        }
                    }

                    // Filter
                    $row_filtered = array_map(function($cell) {
                        return trim(preg_replace('/[\x00-\x1F\x7F]/', '', $cell));
                    }, $row);

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

            if ($success_read && !empty($rows)) {
                // Limit jumlah baris yang diproses (max 500 baris per import untuk menghindari timeout)
                $max_rows = 500;
                if (count($rows) > $max_rows) {
                    $rows = array_slice($rows, 0, $max_rows);
                    $import_message_warning = "File terlalu besar, hanya memproses {$max_rows} baris pertama. ";
                } else {
                    $import_message_warning = "";
                }

                $success_count = 0;
                $skip_count = 0;
                $error_count = 0;
                $current_kategori = '';

                // Cache kode yang sudah ada untuk mengurangi query database
                $existing_codes = [];
                $codes_query = $koneksi->query("SELECT kode FROM tb_produk_orderkuota");
                if ($codes_query) {
                    while ($code_row = $codes_query->fetch_assoc()) {
                        $existing_codes[$code_row['kode']] = true;
                    }
                }

                foreach ($rows as $index => $row) {
                    // Deteksi format: fleksibel untuk berbagai format CSV
                    // Format 1: Kategori, Produk, Harga (3 kolom)
                    // Format 2: Kode, Produk/Deskripsi, Harga (3 kolom)
                    // Format 3: Produk, Harga (2 kolom)
                    // Format 4: Kode, Deskripsi, (Harga mungkin di kolom lain atau perlu di-parse dari deskripsi)

                    $kategori = '';
                    $kode = '';
                    $produk_nama = '';
                    $harga_str = '';
                    $harga = 0;

                    // Bersihkan semua kolom
                    $cols = array_map('trim', $row);
                    $col_count = count($cols);

                    // PRIORITAS: Format 4 kolom (Kode, Produk, Harga, Status) - sesuai template
                    if ($col_count == 4) {
                        // Format 4 kolom: Kode, Produk, Harga, Status
                        $kode = trim($cols[0]);
                        $produk_nama = !empty(trim($cols[1])) ? trim($cols[1]) : (!empty(trim($cols[0])) ? trim($cols[0]) : '');
                        $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                        // Status di kolom 3 (index 3)
                        if (isset($cols[3])) {
                            $status_val = strtolower(trim($cols[3]));
                            $status = (in_array($status_val, ['1', 'aktif', 'yes', 'y', 'true']) ? 1 : 0);
                        } else {
                            $status = 1; // Default aktif
                        }
                        $kategori = !empty($current_kategori) ? $current_kategori : 'UMUM';
                    } elseif ($col_count == 3) {
                        // Format 3 kolom: Kode, Produk, Harga (tanpa status, default aktif)
                        $kode = trim($cols[0]);
                        $produk_nama = !empty(trim($cols[1])) ? trim($cols[1]) : (!empty(trim($cols[0])) ? trim($cols[0]) : '');
                        $harga_str = isset($cols[2]) ? trim($cols[2]) : '';
                        $status = 1; // Default aktif
                        $kategori = !empty($current_kategori) ? $current_kategori : 'UMUM';
                    } else {
                        // Cari kolom yang berisi angka (kemungkinan harga) - cari dari kanan ke kiri
                        // Prioritas: kolom terakhir biasanya harga
                        $harga_col_index = -1;
                        $harga_value = 0;
                        $harga_str = '';

                        // Cari dari kanan ke kiri (kolom terakhir biasanya harga)
                        for ($i = $col_count - 1; $i >= 0; $i--) {
                            $test_value = trim($cols[$i]);
                            if (empty($test_value)) continue;

                            // Cek jika berisi angka (murni angka atau angka dengan pemisah ribuan)
                            // Format: 135650, 135.650, 135,650, Rp 135650, IDR 135650, dll

                            // Hapus prefix currency (Rp, IDR, $, dll)
                            $test_value_no_prefix = preg_replace('/^(Rp|IDR|USD|\$|\s)+/i', '', $test_value);
                            $test_value_clean = trim($test_value_no_prefix);

                            // Extract semua digit
                            preg_match_all('/\d/', $test_value_clean, $digit_matches);
                            $digit_count = count($digit_matches[0]);

                            // Jika ada minimal 3 digit (lebih fleksibel), kemungkinan ini harga
                            if ($digit_count >= 3) {
                                // Extract angka dari string (handle pemisah ribuan)
                                // Coba beberapa format: 135650, 135.650, 135,650
                                $test_clean = preg_replace('/[^0-9]/', '', $test_value_clean);
                                $test_num = floatval($test_clean);

                                // Harga biasanya >= 10 (lebih fleksibel)
                                if ($test_num >= 10) {
                                    $harga_col_index = $i;
                                    $harga_value = $test_num;
                                    $harga_str = $test_value_clean;
                                    break;
                                }
                            }
                        }

                        if ($col_count >= 3) {
                            $col0 = $cols[0];
                            $col1 = $cols[1];
                            $col2 = isset($cols[2]) ? $cols[2] : '';

                            // Jika kolom pertama kosong, gunakan kategori sebelumnya
                            if (empty($col0) && !empty($current_kategori)) {
                                $kategori = $current_kategori;
                                // Format: (kosong), Produk/Kode, Deskripsi, [Harga di kolom lain]
                                if (preg_match('/^[A-Z0-9\-]+$/', $col1) && strlen($col1) <= 20) {
                                    // Kolom 1 adalah kode
                                    $kode = $col1;
                                    $produk_nama = $col2;
                                } else {
                                    // Kolom 1 adalah produk
                                    $produk_nama = $col1;
                                }
                                if (empty($harga_str) && isset($cols[3])) {
                                    $harga_str = $cols[3];
                                }
                            } elseif (strlen($col0) > 8 && (strtoupper($col0) === $col0 || preg_match('/^[A-Z\s]+$/', $col0))) {
                                // Format: Kategori, Produk, Harga (kolom pertama panjang, huruf besar, ada spasi)
                                $kategori = $col0;
                                $produk_nama = $col1;
                                if (empty($harga_str)) {
                                    $harga_str = ($harga_col_index >= 0 && isset($cols[$harga_col_index])) ? $cols[$harga_col_index] : $col2;
                                }
                                $current_kategori = $kategori;
                            } elseif (preg_match('/^[A-Z0-9\-]+$/', $col0) && strlen($col0) <= 20) {
                                // Format: Kode, Produk/Deskripsi, [Harga di kolom lain]
                                $kode = $col0;
                                $produk_nama = $col1;
                                if (empty($harga_str)) {
                                    $harga_str = ($harga_col_index >= 0 && isset($cols[$harga_col_index])) ? $cols[$harga_col_index] : (isset($cols[2]) ? $cols[2] : '');
                                }
                                $kategori = !empty($current_kategori) ? $current_kategori : 'UMUM';
                            } else {
                                // Default: anggap format Kategori/Produk, Produk/Deskripsi, Harga
                                // Cek apakah kolom 0 bisa jadi kategori
                                if (strlen($col0) > 5 && strtoupper($col0) === $col0) {
                                    $kategori = $col0;
                                    $produk_nama = $col1;
                                } else {
                                    $produk_nama = $col0;
                                    $kategori = !empty($current_kategori) ? $current_kategori : 'UMUM';
                                }
                                if (empty($harga_str)) {
                                    $harga_str = ($harga_col_index >= 0 && isset($cols[$harga_col_index])) ? $cols[$harga_col_index] : $col2;
                                }
                                if (!empty($kategori) && strlen($kategori) > 5) {
                                    $current_kategori = $kategori;
                                }
                            }
                        } elseif ($col_count >= 2) {
                            // Format: Produk/Kode, Harga atau Produk, Deskripsi
                            $col0_test = $cols[0];
                            $col1_test = $cols[1];

                            // Cek apakah kolom 0 adalah kode (pendek, alphanumeric)
                            if (preg_match('/^[A-Z0-9\-]+$/', $col0_test) && strlen($col0_test) <= 20 && !preg_match('/\s/', $col0_test)) {
                                $kode = $col0_test;
                                $produk_nama = $col1_test;
                            } else {
                                $produk_nama = $col0_test;
                            }

                            // Jika harga belum ditemukan, coba kolom terakhir
                            if (empty($harga_str)) {
                                $harga_str = ($harga_col_index >= 0 && isset($cols[$harga_col_index])) ? $cols[$harga_col_index] : $col1_test;
                            }
                            $kategori = !empty($current_kategori) ? $current_kategori : 'UMUM';
                        } else {
                            $skip_count++;
                            continue;
                        }
                    } // End else dari if ($col_count == 4) elseif ($col_count == 3) - menutup blok else dari line 1926

                    // Jika harga masih tidak ditemukan, coba parse dari kolom terakhir
                    if (empty($harga_str) && $col_count > 0) {
                        $harga_str = $cols[$col_count - 1];
                    }

                    // Pastikan produk_nama tidak kosong - gunakan default jika kosong
                    if (empty($produk_nama)) {
                        // Untuk format 4 kolom dan 3 kolom, produk_nama harus ada
                        if ($col_count == 4 || $col_count == 3) {
                            // Coba ambil dari kolom pertama atau kedua
                            if (!empty($cols[0])) {
                                $produk_nama = trim($cols[0]);
                            } elseif (!empty($cols[1])) {
                                $produk_nama = trim($cols[1]);
                            } else {
                                $produk_nama = 'PRODUK_' . ($index + 1);
                            }
                        } else {
                            // Jika ada kategori, simpan untuk baris berikutnya
                            if (!empty($kategori) && strlen($kategori) > 3) {
                                $current_kategori = $kategori;
                            }
                            $skip_count++;
                            continue;
                        }
                    }

                    // Parse harga jika belum ditemukan atau perlu di-parse ulang
                    if ($harga_value > 0) {
                        // Gunakan harga yang sudah ditemukan dari pencarian kolom
                        $harga = $harga_value;
                    } elseif (!empty($harga_str)) {
                        // Parse harga dari string - handle pemisah ribuan dengan benar (perbaikan untuk menghindari kelebihan 2 nol)
                        $harga_temp = preg_replace('/^(Rp|IDR|USD|\$|\s)+/i', '', $harga_str);
                        $harga_temp = trim($harga_temp);

                        $dot_count = substr_count($harga_temp, '.');
                        $comma_count = substr_count($harga_temp, ',');

                        if ($dot_count > 0 || $comma_count > 0) {
                            $parts_dot = explode('.', $harga_temp);
                            $parts_comma = explode(',', $harga_temp);

                            $is_dot_thousand = false;
                            $is_comma_thousand = false;

                            // Cek apakah titik adalah pemisah ribuan (setiap bagian 3 digit, kecuali yang pertama)
                            if ($dot_count > 0) {
                                $all_3_digits = true;
                                foreach ($parts_dot as $idx => $part) {
                                    if ($idx > 0 && strlen($part) != 3 && strlen($part) > 0) {
                                        $all_3_digits = false;
                                        break;
                                    }
                                }
                                $is_dot_thousand = ($dot_count > 0 && $all_3_digits);
                            }

                            if ($comma_count > 0 && !$is_dot_thousand) {
                                $all_3_digits = true;
                                foreach ($parts_comma as $idx => $part) {
                                    if ($idx > 0 && strlen($part) != 3 && strlen($part) > 0) {
                                        $all_3_digits = false;
                                        break;
                                    }
                                }
                                $is_comma_thousand = ($comma_count > 0 && $all_3_digits);
                            }

                            if ($is_dot_thousand) {
                                $digit_only = str_replace('.', '', $harga_temp);
                            } else if ($is_comma_thousand) {
                                $digit_only = str_replace(',', '', $harga_temp);
                            } else {
                                // Cek apakah desimal
                                if ($dot_count > 0 && strlen(end($parts_dot)) <= 2) {
                                    $digit_only = str_replace('.', '', $harga_temp);
                                    $digit_only = str_replace(',', '', $digit_only);
                                } else if ($comma_count > 0 && strlen(end($parts_comma)) <= 2) {
                                    $digit_only = str_replace(',', '', $harga_temp);
                                    $digit_only = str_replace('.', '', $digit_only);
                                } else {
                                    $digit_only = preg_replace('/[^0-9]/', '', $harga_temp);
                                }
                            }
                        } else {
                            $digit_only = preg_replace('/[^0-9]/', '', $harga_temp);
                        }

                        if (!empty($digit_only)) {
                            $harga = floatval($digit_only);
                        } else {
                            $harga = 0;
                        }
                    } else {
                        $harga = 0;
                    }

                    // Jika harga masih 0, gunakan default 1000 (jangan skip)
                    if ($harga <= 0) {
                        $harga = 1000;
                    }

                    // Generate kode jika belum ada
                    if (empty($kode)) {
                        $kode = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr($produk_nama, 0, 20)));
                        if (empty($kode)) {
                            $kode = 'PROD' . str_pad($index + 1, 6, '0', STR_PAD_LEFT);
                        }
                    }

                    // Pastikan kode unik menggunakan cache (lebih cepat)
                    $original_kode = $kode;
                    $kode_counter = 1;
                    $max_iterations = 10;
                    while ($kode_counter <= $max_iterations && isset($existing_codes[$kode])) {
                        $kode = $original_kode . '_' . $kode_counter;
                        $kode_counter++;
                    }
                    // Tandai kode baru sebagai sudah ada untuk baris berikutnya
                    $existing_codes[$kode] = true;

                    $keterangan = $produk_nama;
                    $produk = $produk_nama;
                    $kategori_final = !empty($kategori) ? $kategori : 'UMUM';
                    $status = 1;

                    // Validasi data sebelum insert
                    if (empty($kode) || empty($produk) || $harga <= 0) {
                        $skip_count++;
                        continue;
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

                    // Escape semua string untuk keamanan (hanya sekali)
                    $kode_escaped = $koneksi->real_escape_string($kode);
                    $produk_escaped = $koneksi->real_escape_string($produk);
                    $kategori_escaped = $koneksi->real_escape_string($kategori_final);
                    $harga_escaped = floatval($harga);

                    // Dapatkan id_bayar berdasarkan kategori
                    $id_bayar = getIdBayarByKategori($kategori_final, $kategori_mapping);
                    $id_bayar_sql = $id_bayar ? intval($id_bayar) : 'NULL';

                    $insert_query = "INSERT INTO tb_produk_orderkuota
                                    (kode, produk, kategori, harga, status, id_bayar)
                                    VALUES ('$kode_escaped', '$produk_escaped', '$kategori_escaped', $harga_escaped, $status, $id_bayar_sql)
                                    ON DUPLICATE KEY UPDATE
                                    produk = VALUES(produk),
                                    kategori = VALUES(kategori),
                                    harga = VALUES(harga),
                                    status = VALUES(status),
                                    id_bayar = VALUES(id_bayar),
                                    updated_at = CURRENT_TIMESTAMP";

                    if ($koneksi->query($insert_query)) {
                        $success_count++;
                    } else {
                        $error_count++;
                        $error_detail = $koneksi->error;

                        // Jika error karena duplicate key, coba dengan kode yang berbeda
                        if (strpos($error_detail, 'Duplicate entry') !== false || strpos($error_detail, 'unique_kode') !== false) {
                            $kode_new = $original_kode . '_' . time() . '_' . rand(1000, 9999);
                            $kode_new_escaped = $koneksi->real_escape_string($kode_new);
                            $existing_codes[$kode_new] = true;

                            $insert_query_retry = "INSERT INTO tb_produk_orderkuota
                                                    (kode, produk, kategori, harga, status, id_bayar)
                                                    VALUES ('$kode_new_escaped', '$produk_escaped', '$kategori_escaped', $harga_escaped, $status, $id_bayar_sql)
                                                    ON DUPLICATE KEY UPDATE
                                                    produk = VALUES(produk),
                                                    kategori = VALUES(kategori),
                                                    harga = VALUES(harga),
                                                    status = VALUES(status),
                                                    id_bayar = VALUES(id_bayar),
                                                    updated_at = CURRENT_TIMESTAMP";

                            if ($koneksi->query($insert_query_retry)) {
                                $success_count++;
                                $error_count--; // Kurangi error count karena berhasil di retry
                            }
                        }
                    }
                } // End foreach ($rows as $index => $row)

                $import_success = ($success_count > 0);

                // Buat pesan detail
                $import_message = $import_message_warning . "Import CSV selesai!\n";
                $import_message .= "✅ Berhasil diimport: $success_count produk\n";
                if ($skip_count > 0) {
                    $import_message .= "⚠️ Dilewati (skip): $skip_count produk\n";
                }
                if ($error_count > 0) {
                    $import_message .= "❌ Gagal diimport: $error_count produk";
                }

                if ($success_count == 0 && $skip_count == 0 && $error_count == 0) {
                    $import_message = "Tidak ada data yang diimport. Pastikan file CSV memiliki data yang valid.\n\n";
                    $import_message .= "Tips:\n";
                    $import_message .= "- Format yang didukung: Kode, Produk, Harga, Status (4 kolom)\n";
                    $import_message .= "- Atau: Kode, Produk, Harga (3 kolom)\n";
                    $import_message .= "- Pastikan header (baris pertama) mengandung kata 'Kode', 'Produk', 'Harga', atau 'Status'\n";
                    $import_message .= "- Pastikan ada minimal 1 baris data setelah header\n";
                    $import_message .= "- Pastikan harga dalam format angka (contoh: 10000 atau 10.000)\n";
                    $import_success = false;
                } elseif ($success_count == 0) {
                    $import_message .= "\n\n⚠️ PERINGATAN: Tidak ada produk yang berhasil diimport!\n\n";
                    $import_message .= "Detail:\n";
                    $import_message .= "- Total baris diproses: " . count($rows) . "\n";
                    if ($skip_count > 0) {
                        $import_message .= "- Baris dilewati (skip): $skip_count\n";
                    }
                    if ($error_count > 0) {
                        $import_message .= "- Baris error: $error_count\n";
                    }
                    $import_message .= "\nKemungkinan penyebab:\n";
                    $import_message .= "1. Format file tidak sesuai (harus: Kode, Produk, Harga, Status)\n";
                    $import_message .= "2. Semua baris di-skip karena data tidak valid\n";
                    $import_message .= "3. Error saat insert ke database\n";
                    $import_message .= "\nSolusi:\n";
                    $import_message .= "- Pastikan format: Kolom 1=Kode, Kolom 2=Produk, Kolom 3=Harga, Kolom 4=Status\n";
                    $import_message .= "- Pastikan harga dalam format angka (contoh: 10000 atau 10.000)\n";
                    $import_message .= "- Cek log error PHP untuk detail lebih lanjut\n";
                    $import_success = false;
                }
            } else {
                // Jika gagal membaca file CSV
                throw new Exception("Gagal membaca file CSV. Pastikan format file benar.");
            }
        } // End if ($ext == 'csv')

    } catch (Exception $e) {
        $error_msg = $e->getMessage();
        $file_name = isset($file['name']) ? $file['name'] : 'unknown';
        $file_size = isset($file['size']) ? number_format($file['size'] / 1024, 2) . ' KB' : 'unknown';


        $import_message = "❌ Error Import: " . $error_msg;
        $import_message .= "\n\nFile: " . htmlspecialchars($file_name);
        $import_message .= "\nUkuran: " . $file_size;
        $import_message .= "\n\nSilakan pastikan:";
        $import_message .= "\n- File format benar (CSV/Excel .xlsx atau .xls)";
        $import_message .= "\n- File tidak korup";
        $import_message .= "\n- Kolom sesuai format yang diminta";
        $import_message .= "\n- Ukuran file maksimal 10MB";
        $import_message .= "\n- Untuk Excel: nama sheet = kategori produk";

        $import_success = false;
        $success_count = isset($success_count) ? $success_count : 0;
        $skip_count = isset($skip_count) ? $skip_count : 0;
        $error_count = isset($error_count) ? $error_count : 1;

    } catch (Error $e) {
        $error_msg = $e->getMessage();
        $import_message = "❌ Fatal Error Import: " . $error_msg . "\n\nDetail: Terjadi kesalahan sistem. Silakan hubungi administrator atau coba lagi.";
        $import_success = false;
        $success_count = isset($success_count) ? $success_count : 0;
        $skip_count = isset($skip_count) ? $skip_count : 0;
        $error_count = isset($error_count) ? $error_count : 1;
    }

    // Clear output buffer
    if (ob_get_level() > 0) {
        ob_end_clean();
    }

    // Redirect untuk refresh data dengan pesan
    if (!isset($_SESSION)) {
        @session_start();
    }

    // Pastikan variabel ter-set
    if (!isset($import_message)) {
        $import_message = "Error: Proses import tidak diketahui hasilnya.";
    }
    if (!isset($import_success)) {
        $import_success = false;
    }
    if (!isset($success_count)) {
        $success_count = 0;
    }
    if (!isset($skip_count)) {
        $skip_count = 0;
    }
    if (!isset($error_count)) {
        $error_count = 0;
    }

    // Pastikan pesan error ditampilkan dengan benar
    if (!$import_success && $success_count == 0) {
        if (strpos($import_message, '❌') === false && strpos($import_message, 'PERINGATAN') === false && strpos($import_message, 'Gagal') === false) {
            $import_message = "❌ Import Gagal!\n\n" . $import_message;
        }
    }

    // Debug: log session values yang akan diset

    // Set session - PASTIKAN selalu di-set meskipun kosong
    $_SESSION['import_message'] = $import_message;
    $_SESSION['import_success'] = (bool)$import_success;
    $_SESSION['import_success_count'] = (int)$success_count;
    $_SESSION['import_skip_count'] = (int)$skip_count;
    $_SESSION['import_error_count'] = (int)$error_count;

    // Debug: verifikasi session terset

    // Debug: log sebelum redirect

    header("Location: " . base_url('jenisbayar/jenis_bayar.php'));
    exit;
}

// Ambil parameter filter
$filter_kategori = isset($_GET['kategori']) ? $_GET['kategori'] : null;
$filter_produk = isset($_GET['produk']) ? $_GET['produk'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Inisialisasi variabel
$produk_list = [];
$filter_title = "Semua Produk";
$all_kategori = [];
$all_produk_names = [];

if ($table_exists) {
    // Ambil data produk
    if (!empty($search)) {
        $produk_list = searchProduk($search, false); // Include non-active untuk admin
        $filter_title = "Hasil pencarian: \"$search\"";
    } elseif ($filter_produk) {
        // Filter berdasarkan nama produk
        $produk_escaped = mysqli_real_escape_string($koneksi, $filter_produk);
        $query = "SELECT * FROM tb_produk_orderkuota WHERE produk = '$produk_escaped' ORDER BY kategori ASC, kode ASC, produk ASC, harga ASC";
        $result = $koneksi->query($query);
        $produk_list = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $produk_list[] = $row;
            }
        }
        $filter_title = "Produk: " . htmlspecialchars($filter_produk);
    } elseif ($filter_kategori) {
        $produk_list = getProdukByKategori(null, $filter_kategori, false);
        $filter_title = "Kategori: " . htmlspecialchars($filter_kategori);
    } else {
        // Ambil semua produk (termasuk yang tidak aktif)
        $produk_list = getProdukByKategori(null, null, false);
        $filter_title = "Semua Produk";
    }

    // Ambil semua kategori untuk filter (dari data produk yang ada)
    $all_kategori = getAllKategori();

    // Ambil semua nama produk unik untuk filter
    $produk_names_query = $koneksi->query("SELECT DISTINCT produk FROM tb_produk_orderkuota ORDER BY produk ASC");
    if ($produk_names_query) {
        while ($row = $produk_names_query->fetch_assoc()) {
            $all_produk_names[] = $row['produk'];
        }
    }

    // Ambil statistik produk untuk modal detail (dari semua produk, bukan yang difilter)
    if (function_exists('getProdukStats')) {
        $produk_stats = getProdukStats();
    } else {
        // Query langsung untuk statistik
        $query_total = $koneksi->query("SELECT COUNT(*) as total FROM tb_produk_orderkuota");
        $total = $query_total ? $query_total->fetch_assoc()['total'] : 0;

        $query_aktif = $koneksi->query("SELECT COUNT(*) as aktif FROM tb_produk_orderkuota WHERE status = 1");
        $aktif = $query_aktif ? $query_aktif->fetch_assoc()['aktif'] : 0;

        $query_kategori = $koneksi->query("SELECT kategori, COUNT(*) as jumlah,
                                           SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as aktif
                                           FROM tb_produk_orderkuota
                                           GROUP BY kategori
                                           ORDER BY kategori ASC");

        $per_kategori = [];
        if ($query_kategori) {
            while ($row = $query_kategori->fetch_assoc()) {
                $per_kategori[] = $row;
            }
        }

        $produk_stats = [
            'total' => $total,
            'aktif' => $aktif,
            'tidak_aktif' => $total - $aktif,
            'per_kategori' => $per_kategori
        ];
    }
} else {
    // Jika tabel belum ada, tampilkan pesan
    $produk_list = [];
    $produk_stats = [
        'total' => 0,
        'aktif' => 0,
        'tidak_aktif' => 0,
        'per_kategori' => []
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Produk & Harga</title>
    </head>
    <body>
        <div class="page-breadcrumb">
            <div class="row">
                <div class="col-7 align-self-center">
                    <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Produk & Harga</h4>
                    <div class="d-flex align-items-center">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb m-0 p-0">
                                <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item text-muted active" aria-current="page">Produk & Harga</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="modern-card">
                        <div class="modern-card-header">
                            <h4>
                                <i class="fa fa-box"></i> Data Produk & Harga
                            </h4>
                        </div>
                        <div class="modern-card-body">
                            <?php if (!$table_exists): ?>
                            <div class="alert alert-warning">
                                <i class="fa fa-exclamation-triangle"></i> <strong>Tabel produk belum dibuat!</strong><br>
                                Silakan buat tabel terlebih dahulu dengan menjalankan script import atau SQL berikut:
                                <br><br>
                                <div class="btn-group">
                                    <a href="<?=base_url('orderkuota/import_csv.php')?>" class="btn btn-primary btn-sm" target="_blank">
                                        <i class="fa fa-file-csv"></i> Import dari CSV (Disarankan)
                                    </a>
                                    <button type="button" class="btn btn-primary btn-sm dropdown-toggle dropdown-toggle-split" data-toggle="dropdown">
                                        <span class="sr-only">Toggle Dropdown</span>
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="<?=base_url('jenisbayar/import_produk_excel.php')?>" target="_blank">
                                            <i class="fa fa-file-excel"></i> Import dari Excel/CSV (Banyak Data)
                                        </a>
                                        <a class="dropdown-item" href="<?=base_url('jenisbayar/tambah_produk.php')?>" target="_blank">
                                            <i class="fa fa-plus"></i> Tambah Produk Manual
                                        </a>
                                    </div>
                                </div>
                                <a href="<?=base_url('orderkuota/create_table_produk.sql')?>" class="btn btn-secondary btn-sm" target="_blank">
                                    <i class="fa fa-database"></i> Lihat SQL Create Table
                                </a>
                            </div>
                            <?php else: ?>
                            <!-- Filter Section -->
                            <div class="mb-3">
                                <form method="GET" action="" class="row">
                                    <div class="col-md-5 mb-2">
                                        <label class="small text-muted">Filter Produk</label>
                                        <div class="searchable-select-wrapper" style="position: relative;">
                                            <!-- Hidden select untuk form submission -->
                                            <select name="produk" id="filterProduk" style="display: none;">
                                                <option value="">-- Semua Produk --</option>
                                                <?php foreach ($all_produk_names as $prod_name): ?>
                                                <option value="<?=htmlspecialchars($prod_name)?>" <?=($filter_produk == $prod_name) ? 'selected' : ''?>>
                                                    <?=htmlspecialchars($prod_name)?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <!-- Custom dropdown display -->
                                            <div class="custom-select-display form-control form-control-sm" style="cursor: pointer; position: relative; background: white;">
                                                <span class="select-display-text"><?=!empty($filter_produk) ? htmlspecialchars($filter_produk) : '-- Semua Produk --'?></span>
                                                <span style="float: right; margin-top: 2px;">▼</span>
                                            </div>
                                            <!-- Dropdown menu dengan search -->
                                            <div class="custom-select-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ced4da; border-top: none; z-index: 1000; box-shadow: 0 2px 5px rgba(0,0,0,0.2); max-height: 400px; overflow: hidden;">
                                                <div style="padding: 8px; border-bottom: 1px solid #e0e0e0;">
                                                    <input type="text" id="filterProdukSearch" class="form-control form-control-sm" placeholder="Cari produk..." autocomplete="off" style="width: 100%;">
                                                </div>
                                                <div class="custom-select-options" style="max-height: 350px; overflow-y: auto;">
                                                    <div class="custom-option" data-value="" style="padding: 8px 12px; cursor: pointer; <?=empty($filter_produk) ? 'background-color: #e7f3ff;' : ''?>" onmouseover="this.style.backgroundColor='#f0f0f0'" onmouseout="this.style.backgroundColor='<?=empty($filter_produk) ? '#e7f3ff' : 'white'?>'">
                                                        -- Semua Produk --
                                                    </div>
                                                    <?php foreach ($all_produk_names as $prod_name): ?>
                                                    <div class="custom-option" data-value="<?=htmlspecialchars($prod_name)?>" style="padding: 8px 12px; cursor: pointer; <?=($filter_produk == $prod_name) ? 'background-color: #e7f3ff;' : ''?>" onmouseover="this.style.backgroundColor='#f0f0f0'" onmouseout="this.style.backgroundColor='<?=($filter_produk == $prod_name) ? '#e7f3ff' : 'white'?>'">
                                                        <?=htmlspecialchars($prod_name)?>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="small text-muted">Filter Kategori</label>
                                        <select name="kategori" class="form-control form-control-sm" onchange="this.form.submit()" style="height: auto; min-height: 38px; padding: 6px 12px; font-size: 14px;">
                                            <option value="">-- Semua Kategori --</option>
                                            <?php foreach ($all_kategori as $kat): ?>
                                            <option value="<?=htmlspecialchars($kat['kategori'])?>" <?=($filter_kategori == $kat['kategori']) ? 'selected' : ''?>>
                                                <?=htmlspecialchars($kat['kategori'])?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4 mb-2">
                                        <label class="small text-muted">Pencarian</label>
                                        <div class="input-group input-group-sm">
                                            <input type="text" name="search" class="form-control" placeholder="Cari produk..." value="<?=htmlspecialchars($search)?>">
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-primary" title="Cari">
                                                    <i class="fa fa-search"></i>
                                                </button>
                                                <?php if (!empty($search) || !empty($filter_kategori) || !empty($filter_produk)): ?>
                                                <a href="?" class="btn btn-danger btn-sm" title="Reset Semua Filter & Pencarian">
                                                    <i class="fa fa-times"></i>
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Reset Button - Selalu tampilkan jika ada filter/pencarian aktif -->
                            <?php if (!empty($search) || !empty($filter_kategori) || !empty($filter_produk)): ?>
                            <div class="mb-3">
                                <a href="?" class="btn btn-warning btn-sm">
                                    <i class="fa fa-refresh"></i> Reset Semua Filter & Pencarian
                                </a>
                            </div>
                            <?php endif; ?>

                            <!-- Action Buttons -->
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                                <div class="mb-2 mb-md-0">
                                    <small class="text-muted">Menampilkan: <strong><?=count($produk_list)?></strong> produk</small>
                                    <small class="text-muted ml-2" id="selectedCount">| Terpilih: <strong>0</strong></small>
                                </div>
                                <div class="d-flex align-items-center flex-wrap">
                                    <a href="template_import_produk.php" class="btn btn-info btn-sm mr-2 mb-2" target="_blank">
                                        <i class="fa fa-download"></i> Download Template
                                    </a>
                                    <button type="button" class="btn btn-danger btn-sm mr-2 mb-2" id="btnHapusMultiple" disabled onclick="hapusMultiple()">
                                        <i class="fa fa-trash"></i> Hapus Terpilih
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm mr-2 mb-2" id="btnEditMultiple" disabled onclick="editMultiple()">
                                        <i class="fa fa-edit"></i> Edit Terpilih
                                    </button>
                                    <button type="button" class="btn btn-success btn-sm mr-2 mb-2" data-toggle="modal" data-target="#modalImportProduk">
                                        <i class="fa fa-upload"></i> Import Produk
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm mr-2 mb-2" data-toggle="modal" data-target="#modalTambahProduk">
                                        <i class="fa fa-plus"></i> Tambah Produk
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-sm mb-2" data-toggle="modal" data-target="#modalDetailProduk">
                                        <i class="fa fa-eye"></i> Lihat Detail
                                    </button>
                                </div>
                            </div>
                            <?php endif; ?>
                            <div class="table-responsive">
                                <table id="zero_config" class="table modern-table no-wrap">
                                    <thead>
                                        <tr>
                                            <th style="width: 30px;">
                                                <input type="checkbox" id="selectAll" title="Pilih Semua">
                                            </th>
                                            <th style="width: 5px;">No</th>
                                            <th>Kode</th>
                                            <th>Produk</th>
                                            <th>Kategori</th>
                                            <th>Harga</th>
                                            <th>Status</th>
                                            <th style="text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if (!$table_exists) {
                                            echo '<tr><td colspan="8" class="text-center">Tabel produk belum dibuat. Silakan import data terlebih dahulu.</td></tr>';
                                        } elseif (empty($produk_list)) {
                                            echo '<tr><td colspan="8" class="text-center">Tidak ada data produk</td></tr>';
                                        } else {
                                            $no = 1;
                                            foreach ($produk_list as $produk) {
                                        ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="checkbox-produk" value="<?=$produk['id_produk']?>" data-kode="<?=htmlspecialchars($produk['kode'], ENT_QUOTES)?>">
                                            </td>
                                            <td><?=$no++?></td>
                                            <td><strong><?=htmlspecialchars($produk['kode'])?></strong></td>
                                            <td><?=htmlspecialchars($produk['produk'])?></td>
                                            <td><span class="badge badge-secondary"><?=htmlspecialchars($produk['kategori'])?></span></td>
                                            <td><strong class="text-success">Rp <?=number_format(intval($produk['harga']), 0, ',', '.')?></strong></td>
                                            <td>
                                                <?php if ($produk['status'] == 1): ?>
                                                <span class="badge badge-success">Aktif</span>
                                                <?php else: ?>
                                                <span class="badge badge-warning">Tidak Aktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td align="center">
                                                <a href="#" data-toggle="modal" data-target="#modalEditProduk<?=$produk['id_produk']?>" class="btn btn-warning btn-sm">
                                                    <i class="fa fa-edit"></i> Edit
                                                </a>
                                                <a href="#" onclick="hapusProduk(<?=$produk['id_produk']?>, '<?=htmlspecialchars($produk['kode'], ENT_QUOTES)?>'); return false;" class="btn btn-danger btn-sm">
                                                    <i class="fa fa-trash"></i> Hapus
                                                </a>
                                            </td>
                                        </tr>
                                        <?php
                                            }
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Edit Produk -->
        <?php if ($table_exists && !empty($produk_list)): ?>
        <?php foreach ($produk_list as $produk): ?>
        <div id="modalEditProduk<?=$produk['id_produk']?>" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            <i class="fa fa-edit"></i> Edit Produk
                        </h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <form action="update_produk.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="id_produk" value="<?=$produk['id_produk']?>">
                            <div class="form-group">
                                <label>Kode</label>
                                <input type="text" name="kode" class="form-control" value="<?=htmlspecialchars($produk['kode'])?>" readonly>
                            </div>
                            <div class="form-group">
                                <label>Produk</label>
                                <input type="text" name="produk" class="form-control" value="<?=htmlspecialchars($produk['produk'])?>">
                            </div>
                            <div class="form-group">
                                <label>Kategori</label>
                                <input type="text" name="kategori" class="form-control" value="<?=htmlspecialchars($produk['kategori'])?>">
                            </div>
                            <div class="form-group">
                                <label>Harga</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" name="harga" class="form-control" value="<?=intval($produk['harga'])?>" step="1" min="0" required>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control" style="min-height: 38px; padding: 6px 12px; font-size: 14px;">
                                    <option value="1" <?=($produk['status'] == 1) ? 'selected' : ''?>>Aktif</option>
                                    <option value="0" <?=($produk['status'] == 0) ? 'selected' : ''?>>Tidak Aktif</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="update_produk" class="btn btn-success btn-sm">Simpan</button>
                            <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Tutup</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>

        <!-- Modal Tambah Produk -->
        <div id="modalTambahProduk" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            <i class="fa fa-plus-circle"></i> Tambah Produk Baru
                        </h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <form method="POST" action="" id="formTambahProduk">
                        <div class="modal-body">
                            <input type="hidden" name="tambah_produk_modal" value="1">
                            <div class="form-group">
                                <label>Kode Produk <span class="text-danger">*</span></label>
                                <input type="text" name="kode" class="form-control" placeholder="Contoh: SMDC150" required>
                            </div>
                            <div class="form-group">
                                <label>Kategori <span class="text-danger">*</span></label>
                                <input type="text" name="kategori" class="form-control" placeholder="Contoh: KUOTA SMARTFREN" required>
                            </div>
                            <div class="form-group">
                                <label>Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" name="produk" class="form-control" placeholder="Contoh: Smart 30GB All + 60GB" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Harga <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" name="harga" class="form-control" placeholder="0" step="1" min="0" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="1" selected>Aktif</option>
                                            <option value="0">Tidak Aktif</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" name="tambah_produk_modal" class="btn btn-success btn-sm">
                                <i class="fa fa-save"></i> Simpan Produk
                            </button>
                            <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Tutup</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Edit Multiple Produk -->
        <div id="modalEditMultiple" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg" style="max-width: 90%; max-height: 80vh; margin: 1rem auto;">
                <div class="modal-content" style="max-height: 80vh; display: flex; flex-direction: column;">
                    <div class="modal-header" style="flex-shrink: 0; padding: 10px 15px;">
                        <h5 class="modal-title mb-0" style="font-size: 16px;">
                            <i class="fa fa-edit"></i> Edit Multiple Produk
                            <span class="badge badge-primary ml-2">0 produk</span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" style="padding: 0; margin: 0;">&times;</button>
                    </div>
                    <form method="POST" action="update_produk_multiple.php" id="formEditMultiple" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
                        <input type="hidden" name="update_multiple" value="1">
                        <div class="modal-body" style="flex: 1 1 auto; overflow-y: auto; min-height: 250px; max-height: calc(80vh - 100px); padding: 12px;">
                            <style>
                                #modalEditMultiple .modal-dialog {
                                    max-height: 95vh;
                                    height: auto;
                                }
                                #modalEditMultiple .modal-content {
                                    max-height: 80vh;
                                    display: flex;
                                    flex-direction: column;
                                    height: auto;
                                }
                                #modalEditMultiple .modal-body {
                                    overflow-y: auto !important;
                                    flex: 1 1 auto;
                                    min-height: 250px;
                                    max-height: calc(80vh - 100px);
                                    padding: 12px;
                                }
                                #modalEditMultiple select.form-control-sm {
                                    min-height: 38px;
                                    height: 38px;
                                    padding: 6px 30px 6px 10px;
                                    font-size: 13px;
                                    line-height: 1.4;
                                    position: relative;
                                    z-index: 1;
                                    appearance: auto;
                                    -webkit-appearance: menulist;
                                    -moz-appearance: menulist;
                                    background-color: #fff;
                                    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='10' viewBox='0 0 12 12'%3E%3Cpath fill='%23333' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
                                    background-repeat: no-repeat;
                                    background-position: right 8px center;
                                    background-size: 10px;
                                    border: 1px solid #ced4da;
                                    border-radius: 3px;
                                    cursor: pointer;
                                    width: 100%;
                                }
                                #modalEditMultiple select.form-control-sm:focus {
                                    z-index: 1051;
                                    position: relative;
                                    border-color: #80bdff;
                                    outline: 0;
                                    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
                                }
                                #modalEditMultiple select.form-control-sm:hover {
                                    border-color: #adb5bd;
                                }
                                /* Pastikan dropdown options terlihat saat dibuka - styling untuk option */
                                #modalEditMultiple select.form-control-sm option {
                                    padding: 8px 12px;
                                    min-height: 35px;
                                    line-height: 1.5;
                                    font-size: 13px;
                                    background-color: #fff;
                                    color: #212529;
                                    display: block;
                                }
                                #modalEditMultiple select.form-control-sm option:checked {
                                    background-color: #007bff;
                                    color: #fff;
                                    font-weight: 600;
                                }
                                /* Pastikan select memiliki cukup ruang untuk dropdown */
                                #modalEditMultiple tbody td {
                                    position: relative;
                                    overflow: visible;
                                }
                                #modalEditMultiple tbody td:has(select) {
                                    overflow: visible;
                                    z-index: auto;
                                }
                                /* Saat select focus, pastikan parent td juga visible */
                                #modalEditMultiple tbody tr:has(select:focus) {
                                    position: relative;
                                    z-index: 1050;
                                }
                                #modalEditMultiple tbody tr:has(select:focus) td {
                                    overflow: visible;
                                    position: relative;
                                }
                                #modalEditMultiple .table-responsive {
                                    overflow-x: auto;
                                    overflow-y: visible;
                                }
                                #modalEditMultiple .table {
                                    margin-bottom: 0;
                                }
                                #modalEditMultiple .table thead th {
                                    position: sticky;
                                    top: 0;
                                    background-color: #f8f9fa;
                                    z-index: 10;
                                    border-bottom: 2px solid #dee2e6;
                                }
                                #modalEditMultiple .table tbody tr {
                                    height: auto;
                                }
                                #modalEditMultiple .table tbody td {
                                    vertical-align: middle;
                                    padding: 5px 6px;
                                }
                                #modalEditMultiple .table thead th {
                                    padding: 6px 8px;
                                    font-size: 12px;
                                    font-weight: 600;
                                }
                                #modalEditMultiple .table {
                                    font-size: 12px;
                                    margin-bottom: 0;
                                }
                                #modalEditMultiple input.form-control-sm {
                                    padding: 5px 8px;
                                    font-size: 12px;
                                    height: 32px;
                                    line-height: 1.4;
                                }
                                #modalEditMultiple .table-responsive {
                                    margin-bottom: 0;
                                }
                                /* Pastikan select dropdown tidak terpotong */
                                #modalEditMultiple tbody tr {
                                    position: relative;
                                }
                                #modalEditMultiple tbody tr:has(select:focus) {
                                    z-index: 1050;
                                    position: relative;
                                }
                                /* Tinggi dropdown untuk memastikan options terlihat jelas */
                                #modalEditMultiple select.form-control-sm {
                                    min-height: 40px;
                                    padding: 8px 12px;
                                    font-size: 13px;
                                    line-height: 1.6;
                                    height: auto;
                                }
                            </style>
                            <style>
                                /* CSS global untuk semua select dropdown agar options terlihat jelas */
                                select.form-control,
                                select.form-control-sm {
                                    min-height: 40px;
                                    padding: 8px 12px;
                                    font-size: 14px;
                                    line-height: 1.6;
                                    height: auto;
                                    appearance: auto;
                                    -webkit-appearance: menulist;
                                    -moz-appearance: menulist;
                                }
                                select.form-control option,
                                select.form-control-sm option {
                                    padding: 10px 12px;
                                    min-height: 40px;
                                    line-height: 1.6;
                                    font-size: 14px;
                                }
                                /* Pastikan dropdown tidak terpotong saat dibuka */
                                select.form-control:focus,
                                select.form-control-sm:focus {
                                    z-index: 1050;
                                    position: relative;
                                }
                            </style>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th style="width: 15%;">Kode</th>
                                            <th style="width: 30%;">Produk</th>
                                            <th style="width: 25%;">Kategori</th>
                                            <th style="width: 15%;">Harga</th>
                                            <th style="width: 15%;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <!-- Data akan diisi via JavaScript -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer" style="flex-shrink: 0; padding: 10px 20px;">
                            <button type="submit" class="btn btn-success btn-sm">
                                <i class="fa fa-save"></i> Simpan Semua
                            </button>
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">
                                <i class="fa fa-times"></i> Batal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Import Produk -->
        <div id="modalImportProduk" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 90%; width: 90%; max-height: 95vh; margin: 1rem auto;">
                <div class="modal-content" style="max-height: 95vh; display: flex; flex-direction: column;">
                    <div class="modal-header" style="padding: 18px 20px; flex-shrink: 0;">
                        <h5 class="modal-title mb-0" style="font-size: 20px; font-weight: 600;">
                            <i class="fa fa-file-upload"></i> Import Produk
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" style="padding: 0; margin: 0; font-size: 28px;">&times;</button>
                    </div>
                    <form method="POST" enctype="multipart/form-data" action="" id="formImportProduk" style="display: flex; flex-direction: column; flex: 1; min-height: 0;">
                        <div class="modal-body" style="padding: 20px; flex: 1 1 auto; overflow-y: auto; max-height: calc(95vh - 150px);">
                            <?php if (!empty($import_message)): ?>
                            <div class="alert alert-<?=$import_success ? 'success' : 'danger'?> alert-sm" style="padding: 12px 15px; font-size: 15px; margin-bottom: 15px;">
                                <?=htmlspecialchars($import_message)?>
                            </div>
                            <?php endif; ?>

                            <div id="loadingImport" style="display:none;" class="text-center py-4">
                                <p class="mt-3 text-muted" style="font-size: 16px;">Sedang memproses import, mohon tunggu...</p>
                            </div>

                            <div class="alert alert-info" style="padding: 15px 18px; font-size: 15px; margin-bottom: 20px; line-height: 1.7;">
                                <strong style="font-size: 17px; display: block; margin-bottom: 10px;">Format File yang Didukung:</strong>
                                <div style="margin-bottom: 8px;">
                                    <strong>✅ Excel (.xlsx, .xls):</strong> Nama sheet = kategori, kolom: <code style="font-size: 14px;">Kode, Produk, Harga, Status</code>
                                </div>
                                <div style="margin-bottom: 8px;">
                                    <strong>✅ CSV (.csv):</strong> Kolom: <code style="font-size: 14px;">Kode, Produk, Harga, Status</code>
                                </div>
                                <div style="margin-top: 12px; padding-top: 12px; border-top: 1px solid rgba(0,0,0,0.1);">
                                    <small style="font-size: 14px;">💡 <strong>Tips:</strong> Jika download template CSV, Anda bisa membukanya di Excel, mengisi data, lalu simpan sebagai Excel (.xlsx) - file tersebut bisa langsung diimport!</small>
                                </div>
                                <div style="margin-top: 10px;">
                                    <small style="font-size: 14px;">Maksimal 10MB • Header otomatis di-skip</small>
                                </div>
                                <div style="margin-top: 15px;">
                                    <a href="template_import_produk.php" target="_blank" class="btn btn-outline-primary mt-2" style="font-size: 14px; padding: 8px 16px;">
                                        <i class="fa fa-download"></i> Download Template (CSV/Excel)
                                    </a>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label style="font-size: 16px; margin-bottom: 10px; font-weight: 500;">Pilih File Excel atau CSV</label>
                                <input type="file" name="file_csv" accept=".csv,.xlsx,.xls" class="form-control" id="fileCsvInput" required style="font-size: 16px; padding: 12px 15px; height: auto; min-height: 50px; line-height: 1.5;">
                            </div>
                        </div>
                        <div class="modal-footer" style="padding: 15px 20px; flex-shrink: 0; border-top: 1px solid #dee2e6;">
                            <button type="submit" name="import_csv" class="btn btn-success" id="btnImportProduk" style="font-size: 16px; padding: 10px 20px;">
                                <i class="fa fa-upload"></i> Import File
                            </button>
                            <button type="button" class="btn btn-secondary" data-dismiss="modal" id="btnCancelImport" style="font-size: 16px; padding: 10px 20px;">Tutup</button>
                        </div>
                    </form>
                    <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const formImport = document.getElementById('formImportProduk');
                        const loadingDiv = document.getElementById('loadingImport');
                        const alertInfo = document.querySelector('#modalImportProduk .alert-info');
                        const fileInput = document.getElementById('fileCsvInput');
                        const btnImport = document.getElementById('btnImportProduk');
                        const btnCancel = document.getElementById('btnCancelImport');

                        if (formImport) {
                            formImport.addEventListener('submit', function(e) {
                                if (!fileInput.files || !fileInput.files[0]) {
                                    e.preventDefault();
                                    alert('Silakan pilih file terlebih dahulu!');
                                    return false;
                                }

                                // Tampilkan loading
                                loadingDiv.style.display = 'block';
                                if (alertInfo) alertInfo.style.display = 'none';
                                if (btnImport) {
                                    btnImport.disabled = true;
                                    btnImport.innerHTML = 'Memproses...';
                                }
                                if (btnCancel) btnCancel.disabled = true;

                                return true;
                            });
                        }

                        // Reset form saat modal ditutup
                        $('#modalImportProduk').on('hidden.bs.modal', function () {
                            if (loadingDiv) loadingDiv.style.display = 'none';
                            if (fileInput) fileInput.value = '';
                            if (btnImport) {
                                btnImport.disabled = false;
                                btnImport.innerHTML = '<i class="fa fa-upload"></i> Import File';
                            }
                            if (btnCancel) btnCancel.disabled = false;
                        });
                    });
                    </script>
                </div>
            </div>
        </div>

        <!-- Modal Detail Produk -->
        <div id="modalDetailProduk" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 95%; width: 95%;">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            <i class="fa fa-box"></i> Detail Produk & Harga
                            <?php if ($table_exists && isset($produk_stats)): ?>
                            <span class="badge badge-primary ml-2"><?=number_format($produk_stats['total'])?> produk</span>
                            <?php endif; ?>
                        </h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body" style="max-height: calc(100vh - 120px); overflow-y: auto;">
                        <?php if ($table_exists):
                            // Ambil semua produk untuk ditampilkan di modal
                            $all_produk_detail = getProdukByKategori(null, null, false);
                        ?>
                        <?php if (!empty($all_produk_detail)):
                            $current_kategori = '';
                            $produk_by_kategori = [];

                            // Group produk by kategori
                            foreach ($all_produk_detail as $prod) {
                                $kat = $prod['kategori'];
                                if (!isset($produk_by_kategori[$kat])) {
                                    $produk_by_kategori[$kat] = [];
                                }
                                $produk_by_kategori[$kat][] = $prod;
                            }
                        ?>
                        <style>
                            .produk-card-modal {
                                transition: all 0.3s;
                                cursor: pointer;
                                min-height: 180px;
                                border: 1px solid #dee2e6;
                                word-wrap: break-word;
                                overflow-wrap: break-word;
                            }
                            .produk-card-modal:hover {
                                transform: translateY(-5px);
                                box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                                border-color: #28a745;
                            }
                            .produk-card-modal.selected {
                                border-color: #28a745 !important;
                                background-color: #f0fff4;
                            }
                            .produk-card-modal .card-body {
                                padding: 1rem;
                            }
                            .produk-card-modal .card-title {
                                font-size: 0.95rem;
                                font-weight: 600;
                                line-height: 1.4;
                                min-height: 2.8em;
                                margin-bottom: 0.5rem;
                            }
                            .produk-card-modal .card-text {
                                font-size: 0.85rem;
                                line-height: 1.4;
                                min-height: 2.8em;
                                margin-bottom: 0.75rem;
                            }
                            .price-badge-modal {
                                font-size: 1.3rem;
                                font-weight: bold;
                                color: #28a745;
                                white-space: nowrap;
                                overflow: hidden;
                                text-overflow: ellipsis;
                            }
                            .kategori-section-modal {
                                margin-bottom: 2rem;
                            }
                            .kategori-badge-modal {
                                font-size: 0.9rem;
                                padding: 0.5rem 1rem;
                            }
                            @media (min-width: 1200px) {
                                .modal-dialog[style*="95%"] {
                                    max-width: 1400px !important;
                                }
                            }
                        </style>

                        <?php foreach ($produk_by_kategori as $kat => $produk_list_kat): ?>
                        <div class="kategori-section-modal">
                            <h5 class="mb-3">
                                <span class="badge badge-secondary kategori-badge-modal">
                                    <i class="fa fa-folder"></i> <?=htmlspecialchars($kat)?>
                                    <span class="badge badge-light ml-2"><?=count($produk_list_kat)?></span>
                                </span>
                            </h5>
                            <div class="row">
                                <?php foreach ($produk_list_kat as $prod): ?>
                                <div class="col-md-6 col-lg-4 col-xl-3 mb-3">
                                    <div class="card produk-card-modal"
                                         data-kode="<?=htmlspecialchars($prod['kode'])?>"
                                         data-harga="<?=$prod['harga']?>"
                                         data-produk="<?=htmlspecialchars($prod['produk'])?>"
                                         data-id="<?=$prod['id_produk']?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <span class="badge badge-info"><?=htmlspecialchars($prod['kode'])?></span>
                                                <?php if ($prod['status'] == 0): ?>
                                                <span class="badge badge-warning">Tidak Aktif</span>
                                                <?php else: ?>
                                                <span class="badge badge-success">Aktif</span>
                                                <?php endif; ?>
                                            </div>
                                            <h6 class="card-title" title="<?=htmlspecialchars($prod['produk'])?>"><?=htmlspecialchars($prod['produk'])?></h6>
                                            <?php if (!empty($prod['keterangan'])): ?>
                                            <p class="card-text small text-muted mb-2" title="<?=htmlspecialchars($prod['keterangan'])?>">
                                                <?=htmlspecialchars($prod['keterangan'])?>
                                            </p>
                                            <?php endif; ?>
                                            <div class="price-badge-modal mb-2" title="Rp <?=number_format(intval($prod['harga']), 0, ',', '.')?>">
                                                Rp <?=number_format(intval($prod['harga']), 0, ',', '.')?>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <small class="text-muted">
                                                    <i class="fa fa-tag"></i> <?=htmlspecialchars($kat)?>
                                                </small>
                                                <button class="btn btn-sm btn-primary btn-view-detail"
                                                        data-id="<?=$prod['id_produk']?>"
                                                        onclick="event.stopPropagation(); viewProdukDetail(<?=$prod['id_produk']?>);">
                                                    <i class="fa fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <div class="alert alert-info text-center">
                            <i class="fa fa-info-circle"></i> Belum ada produk yang ditambahkan.
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div class="alert alert-warning text-center">
                            <i class="fa fa-exclamation-triangle"></i> Tabel produk belum dibuat.
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <?php if ($table_exists && isset($produk_stats)): ?>
                        <div class="mr-auto">
                            <small class="text-muted">
                                Total: <strong><?=number_format($produk_stats['total'])?></strong> |
                                Aktif: <strong class="text-success"><?=number_format($produk_stats['aktif'])?></strong> |
                                Tidak Aktif: <strong class="text-warning"><?=number_format($produk_stats['tidak_aktif'])?></strong>
                            </small>
                        </div>
                        <?php endif; ?>
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal Detail Produk Individual -->
        <div id="modalProdukDetail" class="modal fade" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h4 class="modal-title">
                            <i class="fa fa-info-circle"></i> Detail Produk
                        </h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body" id="produkDetailContent">
                        <div class="text-center">
                            <p>Memuat detail produk...</p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        // Handle produk card click di modal detail - gunakan delegated event untuk menghindari multiple binding
        $(document).ready(function() {
            // Hapus event handler sebelumnya jika ada
            $(document).off('click', '#modalDetailProduk .produk-card-modal');

            // Gunakan delegated event handler untuk mencegah multiple binding
            $(document).on('click', '#modalDetailProduk .produk-card-modal', function(e) {
                // Skip jika klik pada button detail atau elemen interaktif lainnya
                if ($(e.target).closest('.btn-view-detail, button, a, input, select, textarea, .btn').length > 0) {
                    return;
                }

                // Stop propagation untuk mencegah event bubbling
                e.stopPropagation();
                e.preventDefault();

                const $card = $(this);

                // Hanya toggle selected state untuk visual feedback (tidak copy kode)
                $('#modalDetailProduk .produk-card-modal').removeClass('selected');
                $card.addClass('selected');

                // Hapus selected state setelah 2 detik untuk feedback visual yang halus
                setTimeout(function() {
                    $card.removeClass('selected');
                }, 2000);

                return false;
            });
        });

        // Function untuk melihat detail produk individual
        function viewProdukDetail(id_produk) {
            // Ambil data produk dari database via AJAX
            const contentDiv = document.getElementById('produkDetailContent');
            contentDiv.innerHTML = '<div class="text-center"><p>Memuat detail produk...</p></div>';

            // Buat request ke server untuk mendapatkan detail produk
            fetch('<?=base_url('jenisbayar/get_detail_produk.php')?>?id=' + id_produk)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const prod = data.produk;
                        const harga = parseInt(prod.harga).toLocaleString('id-ID');
                        const created = prod.created_at ? new Date(prod.created_at).toLocaleString('id-ID') : '-';
                        const updated = prod.updated_at ? new Date(prod.updated_at).toLocaleString('id-ID') : '-';

                        contentDiv.innerHTML = `
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th width="40%">Kode Produk</th>
                                            <td><span class="badge badge-info">${prod.kode}</span></td>
                                        </tr>
                                        <tr>
                                            <th>Nama Produk</th>
                                            <td><strong>${prod.produk}</strong></td>
                                        </tr>
                                        <tr>
                                            <th>Kategori</th>
                                            <td><span class="badge badge-secondary">${prod.kategori}</span></td>
                                        </tr>
                                        <tr>
                                            <th>Harga</th>
                                            <td><h4 class="text-success mb-0">Rp ${harga}</h4></td>
                                        </tr>
                                        <tr>
                                            <th>Status</th>
                                            <td>
                                                <span class="badge badge-${prod.status == 1 ? 'success' : 'warning'}">
                                                    ${prod.status == 1 ? 'Aktif' : 'Tidak Aktif'}
                                                </span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <th>Dibuat</th>
                                            <td>${created}</td>
                                        </tr>
                                        <tr>
                                            <th>Diupdate</th>
                                            <td>${updated}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                            <hr>
                            <div class="text-center">
                                <button class="btn btn-warning btn-sm" onclick="editProduk(${prod.id_produk}); $('#modalProdukDetail').modal('hide');">
                                    <i class="fa fa-edit"></i> Edit Produk
                                </button>
                                <button class="btn btn-danger btn-sm" onclick="hapusProduk(${prod.id_produk}, '${prod.kode.replace(/'/g, "\\'")}'); $('#modalProdukDetail').modal('hide');">
                                    <i class="fa fa-trash"></i> Hapus Produk
                                </button>
                            </div>
                        `;
                    } else {
                        contentDiv.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Gagal memuat detail produk') + '</div>';
                    }
                })
                .catch(error => {
                    contentDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
                });

            // Tampilkan modal detail
            $('#modalProdukDetail').modal('show');
        }

        function editProduk(id_produk) {
            // Tutup modal detail produk terlebih dahulu
            $('#modalProdukDetail').modal('hide');
            $('#modalDetailProduk').modal('hide');

            // Buka modal edit produk
            $('#modalEditProduk' + id_produk).modal('show');
        }

        // Fungsi untuk hapus produk dengan SweetAlert
        function hapusProduk(id_produk, kode) {
            Swal.fire({
                title: 'Yakin Hapus?',
                text: 'Produk dengan kode "' + kode + '" akan dihapus. Data yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'hapus_produk.php?id=' + id_produk;
                }
            });
        }

        // Fungsi untuk handle checkbox selection dengan dukungan paginasi DataTables
        $(document).ready(function() {
            // Set untuk menyimpan ID yang dipilih (persisten di semua paginasi)
            // Simpan di window scope agar bisa diakses dari fungsi lain
            window.selectedProdukIds = window.selectedProdukIds || new Set();
            const selectedIds = window.selectedProdukIds;

            const table = $('#zero_config').DataTable();
            const btnHapusMultiple = $('#btnHapusMultiple');
            const btnEditMultiple = $('#btnEditMultiple');
            const selectedCount = $('#selectedCount');

            // Fungsi untuk update button state
            function updateButtonState() {
                const count = selectedIds.size;
                selectedCount.html('| Terpilih: <strong>' + count + '</strong>');
                btnHapusMultiple.prop('disabled', count === 0);
                btnEditMultiple.prop('disabled', count === 0);
            }

            // Fungsi untuk update select all state
            function updateSelectAllState() {
                const selectAll = $('#selectAll');
                if (selectAll.length === 0) return;

                // Hitung checkbox yang terlihat di halaman saat ini
                const visibleCheckboxes = $('.checkbox-produk:visible');
                const visibleChecked = visibleCheckboxes.filter(function() {
                    return selectedIds.has($(this).val());
                }).length;
                const allVisibleChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.length === visibleChecked;
                const someVisibleChecked = visibleChecked > 0;

                selectAll.prop('checked', allVisibleChecked);
                selectAll.prop('indeterminate', someVisibleChecked && !allVisibleChecked);
            }

            // Restore checkbox states saat DataTables draw (setelah pagination/search)
            table.on('draw', function() {
                // Restore checkbox states dari Set
                $('.checkbox-produk').each(function() {
                    const id = $(this).val();
                    $(this).prop('checked', selectedIds.has(id));
                });
                updateSelectAllState();
                updateButtonState();
            });

            // Select All checkbox
            $(document).on('change', '#selectAll', function() {
                const isChecked = $(this).prop('checked');
                // Update semua checkbox yang terlihat di halaman saat ini
                $('.checkbox-produk:visible').each(function() {
                    const id = $(this).val();
                    $(this).prop('checked', isChecked);
                    if (isChecked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });
                updateButtonState();
            });

            // Individual checkbox
            $(document).on('change', '.checkbox-produk', function() {
                const id = $(this).val();
                if ($(this).prop('checked')) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
                updateSelectAllState();
                updateButtonState();
            });

            // Initialize
            updateButtonState();

            // Handle form submit edit multiple
            const formEditMultiple = document.getElementById('formEditMultiple');
            if (formEditMultiple) {
                formEditMultiple.addEventListener('submit', function(e) {
                    // Form akan submit normal ke update_produk_multiple.php
                    // Tidak perlu preventDefault karena kita ingin form submit normal
                });
            }

            // Handle dropdown select di modal edit multiple agar options terlihat
            $(document).on('mousedown', '#modalEditMultiple select', function() {
                // Saat dropdown akan dibuka, ubah overflow menjadi visible
                const $this = $(this);
                const $row = $this.closest('tr');
                const $td = $this.closest('td');
                const modalBody = $('#modalEditMultiple .modal-body');
                const tableResponsive = $('#modalEditMultiple .table-responsive');

                // Set z-index tinggi untuk row dan td
                $row.css('z-index', '1050');
                $td.css('z-index', '1051');
                $td.css('overflow', 'visible');
                $row.css('position', 'relative');

                // Ubah overflow container
                if (modalBody.length) {
                    modalBody.css('overflow', 'visible');
                }
                if (tableResponsive.length) {
                    tableResponsive.css('overflow', 'visible');
                }
            });

            $(document).on('focus', '#modalEditMultiple select', function() {
                // Saat dropdown focus, pastikan overflow visible
                const $this = $(this);
                const $row = $this.closest('tr');
                const $td = $this.closest('td');
                const modalBody = $('#modalEditMultiple .modal-body');
                const tableResponsive = $('#modalEditMultiple .table-responsive');

                $row.css('z-index', '1050');
                $td.css('z-index', '1051');
                $td.css('overflow', 'visible');

                if (modalBody.length) {
                    modalBody.css('overflow', 'visible');
                }
                if (tableResponsive.length) {
                    tableResponsive.css('overflow', 'visible');
                }
            });

            $(document).on('blur change', '#modalEditMultiple select', function() {
                // Saat dropdown ditutup, kembalikan overflow menjadi auto
                setTimeout(function() {
                    const modalBody = $('#modalEditMultiple .modal-body');
                    const tableResponsive = $('#modalEditMultiple .table-responsive');
                    const $rows = $('#modalEditMultiple tbody tr');
                    const $tds = $('#modalEditMultiple tbody td');

                    // Reset z-index dan overflow
                    $rows.css('z-index', '');
                    $rows.css('position', '');
                    $tds.css('z-index', '');
                    $tds.css('overflow', '');

                    if (modalBody.length) {
                        modalBody.css('overflow-y', 'auto');
                    }
                    if (tableResponsive.length) {
                        tableResponsive.css('overflow-y', 'auto');
                    }
                }, 300);
            });

            // Handle saat modal ditutup, reset overflow
            $('#modalEditMultiple').on('hidden.bs.modal', function() {
                const modalBody = $('#modalEditMultiple .modal-body');
                const tableResponsive = $('#modalEditMultiple .table-responsive');
                const $rows = $('#modalEditMultiple tbody tr');
                const $tds = $('#modalEditMultiple tbody td');

                // Reset semua styling
                $rows.css('z-index', '');
                $rows.css('position', '');
                $tds.css('z-index', '');
                $tds.css('overflow', '');

                if (modalBody.length) {
                    modalBody.css('overflow-y', 'auto');
                }
                if (tableResponsive.length) {
                    tableResponsive.css('overflow-y', 'auto');
                }
            });
        });

        // Fungsi untuk hapus multiple (menggunakan Set yang persisten)
        function hapusMultiple() {
            // Ambil selectedIds dari window scope
            const selectedIds = window.selectedProdukIds || new Set();

            if (selectedIds.size === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak ada yang dipilih',
                    text: 'Silakan pilih produk yang akan dihapus terlebih dahulu.'
                });
                return;
            }

            const ids = Array.from(selectedIds);
            // Ambil kode dari checkbox yang terlihat atau dari data attribute
            const kodes = [];
            $('.checkbox-produk').each(function() {
                if (selectedIds.has($(this).val())) {
                    kodes.push($(this).data('kode') || 'Produk');
                }
            });

            Swal.fire({
                title: 'Yakin Hapus?',
                html: 'Anda akan menghapus <strong>' + ids.length + '</strong> produk:<br><small>' + kodes.slice(0, 5).join(', ') + (kodes.length > 5 ? '...' : '') + '</small><br><br>Data yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus Semua!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit form untuk hapus multiple
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'hapus_produk_multiple.php';

                    ids.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'id_produk[]';
                        input.value = id;
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Fungsi untuk escape HTML
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }

        // Fungsi untuk edit multiple (menggunakan Set yang persisten)
        function editMultiple() {
            // Ambil selectedIds dari window scope
            const selectedIds = window.selectedProdukIds || new Set();

            if (selectedIds.size === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Tidak ada yang dipilih',
                    text: 'Silakan pilih produk yang akan diedit terlebih dahulu.'
                });
                return;
            }

            const ids = Array.from(selectedIds);

            // Show loading
            Swal.fire({
                title: 'Memuat data...',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Load data produk via AJAX
            fetch('get_produk_multiple.php?ids=' + ids.join(','))
                .then(response => response.json())
                .then(data => {
                    Swal.close();

                    if (data.success && data.produk && data.produk.length > 0) {
                        // Isi form di modal
                        const tbody = document.querySelector('#modalEditMultiple tbody');
                        tbody.innerHTML = '';

                        data.produk.forEach(produk => {
                            const row = document.createElement('tr');
                            const kodeEscaped = escapeHtml(produk.kode);
                            const produkEscaped = escapeHtml(produk.produk);
                            const kategoriEscaped = escapeHtml(produk.kategori);
                            const statusSelected1 = produk.status == 1 ? 'selected' : '';
                            const statusSelected0 = produk.status == 0 ? 'selected' : '';

                            row.innerHTML = `
                                <td>
                                    <input type="text" name="produk[${produk.id_produk}][kode]"
                                           class="form-control form-control-sm"
                                           value="${kodeEscaped}" readonly>
                                </td>
                                <td>
                                    <input type="text" name="produk[${produk.id_produk}][produk]"
                                           class="form-control form-control-sm"
                                           value="${produkEscaped}" required>
                                </td>
                                <td>
                                    <input type="text" name="produk[${produk.id_produk}][kategori]"
                                           class="form-control form-control-sm"
                                           value="${kategoriEscaped}" required>
                                </td>
                                <td>
                                    <input type="number" name="produk[${produk.id_produk}][harga]"
                                           class="form-control form-control-sm"
                                           value="${produk.harga}" min="0" step="1" required>
                                </td>
                                <td>
                                    <select name="produk[${produk.id_produk}][status]" class="form-control form-control-sm" style="min-height: 38px; padding: 6px 12px; font-size: 13px;">
                                        <option value="1" ${statusSelected1}>Aktif</option>
                                        <option value="0" ${statusSelected0}>Tidak Aktif</option>
                                    </select>
                                </td>
                            `;
                            tbody.appendChild(row);
                        });

                        // Update badge count
                        const badge = document.querySelector('#modalEditMultiple .badge');
                        if (badge) {
                            badge.textContent = data.produk.length + ' produk';
                        }

                        // Buka modal
                        $('#modalEditMultiple').modal('show');
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Gagal memuat data produk'
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan saat memuat data: ' + error.message
                    });
                });
        }

        // SweetAlert untuk semua notifikasi
        document.addEventListener('DOMContentLoaded', function() {
            <?php
            // Pastikan variabel sudah di-set (menggunakan variabel yang sudah di-set di bagian atas)
            $success_count = isset($import_success_count) ? intval($import_success_count) : 0;
            $skip_count = isset($import_skip_count) ? intval($import_skip_count) : 0;
            $error_count = isset($import_error_count) ? intval($import_error_count) : 0;

            // Pastikan $import_message tersedia
            $import_msg_display = isset($import_message) ? $import_message : '';
            $import_success_display = isset($import_success) ? $import_success : false;

            // Tampilkan alert jika ada import message atau ada count yang tidak nol
            // Sederhanakan kondisi - selalu tampilkan jika ada message atau count
            if (!empty($import_msg_display) || $success_count > 0 || $skip_count > 0 || $error_count > 0):
            ?>
            <?php
                // Jika tidak ada message tapi ada count, buat message default
                if (empty($import_msg_display)) {
                    if ($success_count > 0) {
                        $import_msg_display = "Import berhasil diproses. " . $success_count . " produk berhasil diimport.";
                    } else if ($error_count > 0) {
                        $import_msg_display = "Import gagal. " . $error_count . " produk gagal diimport. Silakan cek error log untuk detail.";
                    } else if ($skip_count > 0) {
                        $import_msg_display = "Import selesai. " . $skip_count . " produk dilewati karena data tidak valid atau duplikat.";
                    } else if ($import_success_display === true) {
                        $import_msg_display = "Import berhasil diproses.";
                    } else {
                        $import_msg_display = "Import gagal diproses. Silakan cek error log untuk detail.";
                    }
                }
                // Tentukan icon dan title berdasarkan hasil
                // Cek jika ada error message atau import_success = false
                if (!$import_success_display || (!empty($import_msg_display) && (strpos($import_msg_display, 'Error') !== false || strpos($import_msg_display, '❌') !== false))) {
                    $alert_icon = 'error';
                    $alert_title = 'Import Gagal!';
                    $alert_color = '#dc3545';
                } else if ($success_count > 0) {
                    $alert_icon = 'success';
                    $alert_title = 'Import Berhasil!';
                    $alert_color = '#28a745';
                } else if ($error_count > 0 || $skip_count > 0) {
                    $alert_icon = 'warning';
                    $alert_title = 'Import Selesai dengan Peringatan';
                    $alert_color = '#ffc107';
                } else {
                    $alert_icon = 'info';
                    $alert_title = 'Import Selesai';
                    $alert_color = '#17a2b8';
                }

                // Buat HTML content untuk detail dengan informasi yang lebih jelas dan profesional
                $html_content = '<div style="text-align: left; padding: 10px 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;">';

                // Jika ada error message dan tidak ada count, tampilkan error message saja
                if (!$import_success_display && !empty($import_msg_display) && $success_count == 0 && $skip_count == 0 && $error_count == 0) {
                    $html_content .= '<div style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); border: none; padding: 25px; margin-bottom: 15px; border-radius: 15px; box-shadow: 0 6px 20px rgba(235, 51, 73, 0.3); color: white; text-align: center;">';
                    $html_content .= '<div style="font-size: 48px; margin-bottom: 15px;">❌</div>';
                    $html_content .= '<h4 style="margin: 0; color: white; font-weight: 700; font-size: 22px; text-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 10px;">Error Import</h4>';
                    $html_content .= '<p style="margin: 0; color: rgba(255,255,255,0.95); font-size: 16px; line-height: 1.6;">' . nl2br(htmlspecialchars($import_msg_display)) . '</p>';
                    $html_content .= '</div>';
                    $html_content .= '</div>';
                } else {
                    // Header summary dengan gradient modern
                    $total_processed = $success_count + $skip_count + $error_count;
                $html_content .= '<div style="text-align: center; margin-bottom: 25px; padding: 25px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 15px; box-shadow: 0 8px 25px rgba(102, 126, 234, 0.35); color: white; position: relative; overflow: hidden;">';
                $html_content .= '<div style="position: absolute; top: -50px; right: -50px; width: 150px; height: 150px; background: rgba(255,255,255,0.1); border-radius: 50%;"></div>';
                $html_content .= '<div style="position: absolute; bottom: -30px; left: -30px; width: 100px; height: 100px; background: rgba(255,255,255,0.08); border-radius: 50%;"></div>';
                $html_content .= '<div style="position: relative; z-index: 1;">';
                $html_content .= '<div style="font-size: 40px; margin-bottom: 10px;">📊</div>';
                $html_content .= '<h4 style="margin: 0; color: white; font-weight: 800; font-size: 24px; text-shadow: 0 2px 6px rgba(0,0,0,0.25); letter-spacing: 0.5px;">Ringkasan Import</h4>';
                $html_content .= '<p style="margin: 12px 0 0 0; color: rgba(255,255,255,0.98); font-size: 17px; font-weight: 600;">Total diproses: <strong style="font-size: 24px; font-weight: 900;">' . number_format($total_processed) . '</strong> produk</p>';
                $html_content .= '</div>';
                $html_content .= '</div>';

                // Informasi utama - jumlah produk yang berhasil diimport (SELALU tampilkan jika > 0)
                if ($success_count > 0) {
                    $html_content .= '<div style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border: none; padding: 20px; margin-bottom: 15px; border-radius: 12px; box-shadow: 0 6px 20px rgba(17, 153, 142, 0.3); color: white;">';
                    $html_content .= '<div style="display: flex; align-items: center; justify-content: space-between;">';
                    $html_content .= '<div style="display: flex; align-items: center; flex: 1;">';
                    $html_content .= '<div style="width: 56px; height: 56px; background: rgba(255,255,255,0.25); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; backdrop-filter: blur(10px);">';
                    $html_content .= '<span style="font-size: 28px;">✅</span>';
                    $html_content .= '</div>';
                    $html_content .= '<div>';
                    $html_content .= '<strong style="color: white; font-size: 20px; display: block; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">Berhasil Diimport</strong>';
                    $html_content .= '<small style="color: rgba(255,255,255,0.9); font-size: 14px;">Produk berhasil disimpan ke database</small>';
                    $html_content .= '</div>';
                    $html_content .= '</div>';
                    $html_content .= '<div style="text-align: right; margin-left: 20px;">';
                    $html_content .= '<strong style="color: white; font-size: 42px; display: block; line-height: 1; font-weight: 800; text-shadow: 0 2px 8px rgba(0,0,0,0.2);">' . number_format($success_count) . '</strong>';
                    $html_content .= '<span style="color: rgba(255,255,255,0.95); font-size: 15px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">produk</span>';
                    $html_content .= '</div>';
                    $html_content .= '</div>';
                    $html_content .= '</div>';
                } else {
                    // Jika tidak ada yang berhasil, tetap tampilkan info
                    $html_content .= '<div style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); border: none; padding: 18px; margin-bottom: 15px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">';
                    $html_content .= '<div style="display: flex; align-items: center;">';
                    $html_content .= '<div style="width: 48px; height: 48px; background: rgba(108, 117, 125, 0.1); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px;">';
                    $html_content .= '<span style="font-size: 24px;">ℹ️</span>';
                    $html_content .= '</div>';
                    $html_content .= '<div>';
                    $html_content .= '<strong style="color: #495057; font-size: 18px; font-weight: 600;">Tidak ada produk yang berhasil diimport</strong>';
                    $html_content .= '</div>';
                    $html_content .= '</div>';
                    $html_content .= '</div>';
                }

                // Informasi produk yang gagal diimport (SELALU tampilkan jika > 0)
                if ($error_count > 0) {
                    $html_content .= '<div style="background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%); border: none; padding: 20px; margin-bottom: 15px; border-radius: 12px; box-shadow: 0 6px 20px rgba(235, 51, 73, 0.3); color: white;">';
                    $html_content .= '<div style="display: flex; align-items: center; justify-content: space-between;">';
                    $html_content .= '<div style="display: flex; align-items: center; flex: 1;">';
                    $html_content .= '<div style="width: 56px; height: 56px; background: rgba(255,255,255,0.25); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; backdrop-filter: blur(10px);">';
                    $html_content .= '<span style="font-size: 28px;">❌</span>';
                    $html_content .= '</div>';
                    $html_content .= '<div>';
                    $html_content .= '<strong style="color: white; font-size: 20px; display: block; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">Gagal Diimport</strong>';
                    $html_content .= '<small style="color: rgba(255,255,255,0.9); font-size: 14px;">Terjadi error saat menyimpan data</small>';
                    $html_content .= '</div>';
                    $html_content .= '</div>';
                    $html_content .= '<div style="text-align: right; margin-left: 20px;">';
                    $html_content .= '<strong style="color: white; font-size: 42px; display: block; line-height: 1; font-weight: 800; text-shadow: 0 2px 8px rgba(0,0,0,0.2);">' . number_format($error_count) . '</strong>';
                    $html_content .= '<span style="color: rgba(255,255,255,0.95); font-size: 15px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">produk</span>';
                    $html_content .= '</div>';
                    $html_content .= '</div>';
                    $html_content .= '</div>';
                }

                // Informasi produk yang dilewati (skip) - SELALU tampilkan jika > 0
                if ($skip_count > 0) {
                    $html_content .= '<div style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border: none; padding: 20px; margin-bottom: 15px; border-radius: 12px; box-shadow: 0 6px 20px rgba(245, 87, 108, 0.3); color: white;">';
                    $html_content .= '<div style="display: flex; align-items: center; justify-content: space-between;">';
                    $html_content .= '<div style="display: flex; align-items: center; flex: 1;">';
                    $html_content .= '<div style="width: 56px; height: 56px; background: rgba(255,255,255,0.25); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; backdrop-filter: blur(10px);">';
                    $html_content .= '<span style="font-size: 28px;">⚠️</span>';
                    $html_content .= '</div>';
                    $html_content .= '<div>';
                    $html_content .= '<strong style="color: white; font-size: 20px; display: block; font-weight: 700; text-shadow: 0 2px 4px rgba(0,0,0,0.1);">Dilewati (Skip)</strong>';
                    $html_content .= '<small style="color: rgba(255,255,255,0.9); font-size: 14px;">Data tidak valid atau duplikat</small>';
                    $html_content .= '</div>';
                    $html_content .= '</div>';
                    $html_content .= '<div style="text-align: right; margin-left: 20px;">';
                    $html_content .= '<strong style="color: white; font-size: 42px; display: block; line-height: 1; font-weight: 800; text-shadow: 0 2px 8px rgba(0,0,0,0.2);">' . number_format($skip_count) . '</strong>';
                    $html_content .= '<span style="color: rgba(255,255,255,0.95); font-size: 15px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">produk</span>';
                    $html_content .= '</div>';
                    $html_content .= '</div>';
                    $html_content .= '</div>';
                }

                // Pesan tambahan jika ada (warning dll)
                if (!empty($import_message) && strlen(trim($import_message)) > 0) {
                    $message_clean = trim($import_message);
                    // Hapus bagian yang sudah ditampilkan di atas
                    $message_clean = str_replace(['Import Excel selesai!', 'Import CSV selesai!', 'Import dari folder orderkuota selesai!'], '', $message_clean);
                    $message_clean = preg_replace('/✅ Berhasil diimport:\s*\d+\s+produk/i', '', $message_clean);
                    $message_clean = preg_replace('/⚠️ Dilewati \(skip\):\s*\d+\s+produk/i', '', $message_clean);
                    $message_clean = preg_replace('/❌ Gagal diimport:\s*\d+\s+produk/i', '', $message_clean);
                    $message_clean = trim(preg_replace('/\n+/', ' ', $message_clean));
                    if (!empty($message_clean)) {
                        $html_content .= '<div style="margin-top: 20px; padding: 15px; background: linear-gradient(135deg, #f6f8fb 0%, #e9ecef 100%); border-radius: 10px; border-left: 4px solid #667eea;">';
                        $html_content .= '<p style="margin: 0; color: #495057; font-size: 14px; line-height: 1.6; font-weight: 500;"><i class="fa fa-info-circle" style="margin-right: 8px; color: #667eea;"></i>' . htmlspecialchars($message_clean) . '</p>';
                        $html_content .= '</div>';
                    }
                }

                // Close container div (hanya jika bukan error message saja)
                if (!(!$import_success_display && !empty($import_msg_display) && $success_count == 0 && $skip_count == 0 && $error_count == 0)) {
                    $html_content .= '</div>';
                }
                } // End else block
            ?>
            Swal.fire({
                position: 'top-center',
                icon: '<?=$alert_icon?>',
                title: '<?=$alert_title?>',
                html: <?=json_encode($html_content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>,
                showConfirmButton: true,
                confirmButtonText: '<i class="fa fa-check-circle"></i> Mengerti',
                confirmButtonColor: '<?=$alert_color?>',
                allowOutsideClick: true,
                allowEscapeKey: true,
                width: '650px',
                padding: '2.5em',
                background: '#ffffff',
                backdrop: 'rgba(0,0,0,0.5)',
                customClass: {
                    popup: 'animated-popup',
                    title: 'swal2-title-modern',
                    confirmButton: 'swal2-confirm-modern'
                },
                buttonsStyling: true,
                showCloseButton: true,
                timer: null,
                timerProgressBar: false
            }).then(function(result) {
                // Session sudah di-clear di bagian atas sebelum alert ditampilkan
                // Jadi cukup redirect ke URL yang sama untuk fresh load
                if (result.isConfirmed || result.isDismissed) {
                    window.location.href = '<?=base_url("jenisbayar/jenis_bayar.php")?>';
                }
            });
            <?php endif; ?>

            <?php if (!empty($hapus_message)): ?>
            Swal.fire({
                position: 'top-center',
                icon: '<?=$hapus_success ? 'success' : 'error'?>',
                title: '<?=$hapus_success ? 'Berhasil!' : 'Gagal!'?>',
                text: '<?=htmlspecialchars($hapus_message, ENT_QUOTES)?>',
                showConfirmButton: true,
                confirmButtonColor: '<?=$hapus_success ? '#28a745' : '#dc3545'?>',
                timer: 3000,
                timerProgressBar: true
            });
            <?php endif; ?>

            <?php if (!empty($update_message)): ?>
            Swal.fire({
                position: 'top-center',
                icon: '<?=$update_success ? 'success' : 'error'?>',
                title: '<?=$update_success ? 'Berhasil!' : 'Gagal!'?>',
                <?php if ($update_message_html): ?>
                html: <?=json_encode($update_message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>,
                <?php else: ?>
                text: <?=json_encode($update_message, JSON_UNESCAPED_UNICODE)?>,
                <?php endif; ?>
                showConfirmButton: true,
                confirmButtonColor: '<?=$update_success ? '#28a745' : '#dc3545'?>',
                timer: 5000,
                timerProgressBar: true,
                width: '600px',
                allowOutsideClick: false
            });
            <?php endif; ?>

            <?php if (!empty($tambah_message)): ?>
            Swal.fire({
                position: 'top-center',
                icon: '<?=$tambah_success ? 'success' : 'error'?>',
                title: '<?=$tambah_success ? 'Berhasil!' : 'Gagal!'?>',
                text: '<?=htmlspecialchars($tambah_message, ENT_QUOTES)?>',
                showConfirmButton: true,
                confirmButtonColor: '<?=$tambah_success ? '#28a745' : '#dc3545'?>',
                timer: 3000,
                timerProgressBar: true
            }).then(function() {
                // Reset form tambah produk jika sukses
                <?php if ($tambah_success): ?>
                const modalTambah = document.getElementById('modalTambahProduk');
                if (modalTambah) {
                    const form = document.getElementById('formTambahProduk');
                    if (form) form.reset();
                    $(modalTambah).modal('hide');
                }
                <?php endif; ?>
            });
            <?php endif; ?>

            // Searchable Select untuk Filter Produk
            (function() {
                const wrapper = document.querySelector('.searchable-select-wrapper');
                if (!wrapper) return;

                const selectElement = document.getElementById('filterProduk');
                const displayElement = wrapper.querySelector('.custom-select-display');
                const displayText = wrapper.querySelector('.select-display-text');
                const dropdown = wrapper.querySelector('.custom-select-dropdown');
                const searchInput = document.getElementById('filterProdukSearch');
                const optionsContainer = wrapper.querySelector('.custom-select-options');
                const allOptions = optionsContainer.querySelectorAll('.custom-option');

                // Toggle dropdown
                displayElement.addEventListener('click', function(e) {
                    e.stopPropagation();
                    const isVisible = dropdown.style.display === 'block';
                    dropdown.style.display = isVisible ? 'none' : 'block';
                    if (!isVisible) {
                        searchInput.focus();
                        searchInput.value = '';
                        filterOptions('');
                    }
                });

                // Fungsi untuk filter options
                function filterOptions(searchText) {
                    const searchLower = searchText.toLowerCase().trim();
                    allOptions.forEach(opt => {
                        const text = opt.textContent.toLowerCase();
                        if (searchLower === '' || text.includes(searchLower)) {
                            opt.style.display = '';
                        } else {
                            opt.style.display = 'none';
                        }
                    });
                }

                // Filter saat mengetik
                searchInput.addEventListener('input', function() {
                    filterOptions(this.value);
                });

                // Handle klik option
                allOptions.forEach(opt => {
                    opt.addEventListener('click', function() {
                        const value = this.getAttribute('data-value');
                        const text = this.textContent.trim();

                        // Update hidden select
                        selectElement.value = value;

                        // Update display
                        displayText.textContent = text;

                        // Update visual selection
                        allOptions.forEach(o => {
                            if (o.getAttribute('data-value') === value) {
                                o.style.backgroundColor = '#e7f3ff';
                            } else {
                                o.style.backgroundColor = 'white';
                            }
                        });

                        // Tutup dropdown
                        dropdown.style.display = 'none';

                        // Submit form
                        const form = selectElement.closest('form');
                        if (form) form.submit();
                    });
                });

                // Tutup dropdown saat klik di luar
                document.addEventListener('click', function(e) {
                    if (!wrapper.contains(e.target)) {
                        dropdown.style.display = 'none';
                    }
                });

                // Handle keyboard di search input
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        dropdown.style.display = 'none';
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        const firstVisible = Array.from(allOptions).find(opt => opt.style.display !== 'none');
                        if (firstVisible) {
                            firstVisible.click();
                        }
                    }
                });
            })();
        });
        </script>

        <?php
        include_once('../footer.php');
        ?>
    </body>
</html>
