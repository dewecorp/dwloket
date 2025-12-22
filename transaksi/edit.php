<?php
// Start output buffering di awal untuk mencegah output sebelum redirect
if (!ob_get_level()) {
    ob_start();
}

error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable display errors untuk mencegah output sebelum redirect
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1); // Tetap log errors tapi tidak tampilkan

include_once('../config/config.php');
require_once '../libs/produk_helper.php';

// Pastikan session sudah start
if (!isset($_SESSION)) {
    session_start();
}

// Validasi ID parameter
if (!isset($_GET['id'])) {
    // Bersihkan output buffer sebelum redirect
    if (ob_get_level()) {
        ob_end_clean();
    }
    header('Location: ' . base_url('transaksi/transaksi.php'));
    exit();
}

// PROSES POST UPDATE HARUS SEBELUM HEADER UNTUK REDIRECT
if (isset($_POST['edit'])) {
    // Debug: Log semua POST data
    error_log("=== EDIT POST DATA ===");
    error_log("POST: " . print_r($_POST, true));

    $id     = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $tgl    = isset($_POST['tgl']) ? trim($_POST['tgl']) : '';
    $idpel  = isset($_POST['idpel']) ? trim($_POST['idpel']) : '';
    $nama   = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $jenis  = isset($_POST['jenis']) ? trim($_POST['jenis']) : '';
    $harga  = isset($_POST['harga']) ? trim($_POST['harga']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $ket    = isset($_POST['ket']) ? trim($_POST['ket']) : '';

    error_log("Parsed values - ID: $id, tgl: $tgl, idpel: $idpel, nama: $nama, jenis: $jenis, harga: $harga, status: $status, ket: $ket");

    // Validasi input
    $error_msg = '';
    if (empty($id) || $id <= 0) {
        $error_msg = 'ID transaksi tidak valid.';
    } elseif (empty($tgl)) {
        $error_msg = 'Tanggal transaksi belum diisi.';
    } elseif (empty($idpel)) {
        $error_msg = 'ID pelanggan belum diisi.';
    } elseif (empty($nama)) {
        $error_msg = 'Nama pelanggan belum diisi.';
    } elseif (empty($harga) || floatval($harga) <= 0) {
        $error_msg = 'Harga tidak valid atau belum diisi. Harga: ' . $harga;
    }

    if (empty($error_msg)) {
        // Escape untuk keamanan
        $tgl = mysqli_real_escape_string($koneksi, $tgl);
        $idpel = mysqli_real_escape_string($koneksi, $idpel);
        $nama = mysqli_real_escape_string($koneksi, $nama);
        $jenis = mysqli_real_escape_string($koneksi, $jenis);
        $harga = floatval($harga);
        $status = mysqli_real_escape_string($koneksi, $status);
        $ket = mysqli_real_escape_string($koneksi, $ket);

        // Handle id_bayar - kolom tidak boleh NULL, harus ada nilai valid
        // Pastikan selalu ada nilai, tidak boleh NULL
        $jenis_sql = null; // Initialize variable

        if (!empty($jenis) && $jenis !== '' && $jenis !== '0' && $jenis !== 'null') {
            $jenis_sql = intval($jenis); // Pastikan integer
        }

        // Jika jenis_sql masih null atau kosong, ambil ID jenis bayar pertama sebagai default
        if (empty($jenis_sql) || $jenis_sql <= 0) {
            $default_query = $koneksi->query("SELECT id_bayar FROM tb_jenisbayar ORDER BY id_bayar ASC LIMIT 1");
            if ($default_query && $default_query->num_rows > 0) {
                $default_row = $default_query->fetch_assoc();
                $jenis_sql = intval($default_row['id_bayar']);
            } else {
                // Jika tidak ada jenis bayar sama sekali, error
                $error_msg = 'Tidak ada jenis pembayaran tersedia. Silakan tambahkan jenis pembayaran terlebih dahulu.';
                $post_error_msg = $error_msg;
                error_log("Edit Error: Tidak ada jenis pembayaran tersedia di database");
            }
        }

        // Pastikan jenis_sql sudah didefinisikan dan valid sebelum eksekusi query
        if (empty($error_msg) && (!isset($jenis_sql) || empty($jenis_sql) || $jenis_sql <= 0)) {
            $error_msg = 'ID pembayaran tidak valid. Jenis input: ' . htmlspecialchars($jenis);
            $post_error_msg = $error_msg;
            error_log("Edit Error: ID pembayaran tidak valid. jenis input: $jenis, jenis_sql: " . (isset($jenis_sql) ? $jenis_sql : 'not set'));
        }

        // Jika tidak ada error, eksekusi query UPDATE
        if (empty($error_msg) && isset($jenis_sql) && $jenis_sql > 0) {
            $query = "UPDATE transaksi SET tgl='$tgl', idpel='$idpel', nama='$nama', id_bayar=$jenis_sql, harga=$harga, status='$status', ket='$ket' WHERE id_transaksi=$id";

            error_log("Edit Query: " . $query);
            error_log("Edit Data - ID: $id, jenis_sql: $jenis_sql, harga: $harga, tgl: $tgl, idpel: $idpel, nama: $nama");

            $sql = $koneksi->query($query);

            if ($sql === false) {
                // Query gagal
                $db_error = mysqli_error($koneksi);
                $error_msg = 'Gagal menyimpan perubahan: ' . $db_error;
                $post_error_msg = $error_msg;
                error_log("Edit Database Error: " . $db_error);
                error_log("Edit Failed Query: " . $query);
            } else {
                // Query berhasil (meskipun affected_rows = 0, tetap dianggap berhasil)
                $affected_rows = mysqli_affected_rows($koneksi);

                if ($affected_rows > 0) {
                    error_log("Edit Success: $affected_rows row(s) affected. ID: $id");
                } else {
                    // Cek apakah data benar-benar ada di database
                    $check_query = $koneksi->query("SELECT id_transaksi FROM transaksi WHERE id_transaksi=$id");
                    if ($check_query && $check_query->num_rows > 0) {
                        error_log("Edit Success: Data tidak berubah (nilai sama dengan sebelumnya). ID: $id");
                    } else {
                        error_log("Edit Warning: Data tidak ditemukan di database. ID: $id");
                        $error_msg = 'Data transaksi tidak ditemukan.';
                        $post_error_msg = $error_msg;
                    }
                }

                // Jika tidak ada error, lanjutkan dengan log dan redirect
                if (empty($error_msg)) {
                    // Log aktivitas (gunakan @ untuk suppress error jika file tidak ada)
                    @require_once '../libs/log_activity.php';
                    if (function_exists('log_activity')) {
                        @log_activity('update', 'transaksi', 'Mengedit transaksi ID: ' . $id);
                    }

                    // Set session message
                    $_SESSION['success_message'] = 'Transaksi berhasil diedit';

                    // Bersihkan semua output buffer sebelum redirect
                    while (ob_get_level()) {
                        ob_end_clean();
                    }

                    // Redirect langsung dengan header
                    $redirect_url = base_url('transaksi/transaksi.php');

                    // Pastikan tidak ada output sebelum redirect
                    if (!headers_sent()) {
                        header('Location: ' . $redirect_url);
                        header('HTTP/1.1 302 Found');
                        exit();
                    } else {
                        // Fallback: jika header sudah dikirim, gunakan meta refresh dan JavaScript
                        echo '<!DOCTYPE html><html><head><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($redirect_url) . '"></head><body><script>window.location.href = ' . json_encode($redirect_url) . ';</script><p>Redirecting... <a href="' . htmlspecialchars($redirect_url) . '">Click here if not redirected</a></p></body></html>';
                        exit();
                    }
                }
            }
        }
    }

    // Simpan error untuk ditampilkan setelah header
    if (!empty($error_msg)) {
        $post_error_msg = $error_msg;
        error_log("Edit Validation Error: " . $error_msg);
        // Error akan ditampilkan di halaman yang sama, tidak perlu redirect
    }
}

include_once('../header.php');

// Fungsi untuk menentukan icon dan warna berdasarkan jenis pembayaran
// Fungsi ini otomatis akan handle jenis pembayaran baru yang ditambahkan
function getJenisBayarStyle($jenis_bayar) {
    $jenis_bayar_lower = strtolower(trim($jenis_bayar));

    // Mapping icon dan warna berdasarkan keyword
    $mapping = [
        // PLN / Listrik
        'token pln' => ['icon' => 'fa-bolt', 'color' => 'warning'],
        'pln' => ['icon' => 'fa-bolt', 'color' => 'warning'],
        'listrik' => ['icon' => 'fa-bolt', 'color' => 'warning'],

        // Pulsa
        'pulsa telkomsel' => ['icon' => 'fa-phone', 'color' => 'primary'],
        'pulsa xl' => ['icon' => 'fa-phone', 'color' => 'info'],
        'pulsa axis' => ['icon' => 'fa-phone', 'color' => 'danger'],
        'pulsa indosat' => ['icon' => 'fa-phone', 'color' => 'warning'],
        'pulsa tri' => ['icon' => 'fa-phone', 'color' => 'success'],
        'pulsa smartfren' => ['icon' => 'fa-phone', 'color' => 'secondary'],
        'pulsa' => ['icon' => 'fa-phone', 'color' => 'primary'],

        // Data Internet
        'data internet' => ['icon' => 'fa-wifi', 'color' => 'info'],
        'paket data' => ['icon' => 'fa-wifi', 'color' => 'info'],
        'data' => ['icon' => 'fa-wifi', 'color' => 'info'],
        'internet' => ['icon' => 'fa-wifi', 'color' => 'info'],

        // BPJS
        'bpjs kesehatan' => ['icon' => 'fa-heart', 'color' => 'danger'],
        'bpjs ketenagakerjaan' => ['icon' => 'fa-briefcase', 'color' => 'danger'],
        'bpjs' => ['icon' => 'fa-heart', 'color' => 'danger'],

        // PDAM / Air
        'pdam' => ['icon' => 'fa-tint', 'color' => 'info'],
        'air' => ['icon' => 'fa-tint', 'color' => 'info'],

        // Internet Rumah
        'indihome' => ['icon' => 'fa-home', 'color' => 'primary'],
        'wifi id' => ['icon' => 'fa-wifi', 'color' => 'info'],

        // E-Wallet / Payment
        'shopee pay' => ['icon' => 'fa-shopping-bag', 'color' => 'warning'],
        'shopee' => ['icon' => 'fa-shopping-bag', 'color' => 'warning'],
        'grab ovo' => ['icon' => 'fa-motorcycle', 'color' => 'success'],
        'grab' => ['icon' => 'fa-motorcycle', 'color' => 'success'],
        'ovo' => ['icon' => 'fa-motorcycle', 'color' => 'success'],
        'e-mandiri' => ['icon' => 'fa-university', 'color' => 'primary'],
        'mandiri' => ['icon' => 'fa-university', 'color' => 'primary'],
        'brizzi' => ['icon' => 'fa-credit-card', 'color' => 'info'],
        'e-toll' => ['icon' => 'fa-road', 'color' => 'warning'],
        'toll' => ['icon' => 'fa-road', 'color' => 'warning'],
        'transfer uang' => ['icon' => 'fa-exchange-alt', 'color' => 'success'],
        'transfer' => ['icon' => 'fa-exchange-alt', 'color' => 'success'],
        'e-money' => ['icon' => 'fa-wallet', 'color' => 'primary'],
        'wallet' => ['icon' => 'fa-wallet', 'color' => 'primary'],
        'voucher game' => ['icon' => 'fa-gamepad', 'color' => 'danger'],
        'voucher' => ['icon' => 'fa-gamepad', 'color' => 'danger'],
        'game' => ['icon' => 'fa-gamepad', 'color' => 'danger'],
    ];

    // Cek exact match terlebih dahulu
    if (isset($mapping[$jenis_bayar_lower])) {
        return $mapping[$jenis_bayar_lower];
    }

    // Cek partial match (lebih fleksibel untuk jenis baru)
    foreach ($mapping as $key => $value) {
        // Cek jika keyword ada di nama jenis bayar atau sebaliknya
        if (strpos($jenis_bayar_lower, $key) !== false || strpos($key, $jenis_bayar_lower) !== false) {
            return $value;
        }
    }

    // Default untuk jenis pembayaran baru yang belum ada di mapping
    // Otomatis akan muncul dengan icon dan warna default
    return ['icon' => 'fa-money-bill-wave', 'color' => 'secondary'];
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (empty($id) || $id <= 0) {
    header('Location: ' . base_url('transaksi/transaksi.php'));
    exit();
}

$sql = $koneksi->query("SELECT * FROM transaksi WHERE id_transaksi='$id'");
if (!$sql) {
    error_log("Error fetching transaksi: " . mysqli_error($koneksi));
    header('Location: ' . base_url('transaksi/transaksi.php'));
    exit();
}

$data = $sql->fetch_assoc();
if (!$data) {
    header('Location: ' . base_url('transaksi/transaksi.php'));
    exit();
}

$status = $data['status'];
$tgl = $data['tgl'];
$selected_id_bayar = $data['id_bayar'] ?? '';

// Ambil data kategori produk (untuk grid kategori)
$all_kategori = [];
try {
    if (function_exists('getAllKategori')) {
        $all_kategori = @getAllKategori();
        if (!is_array($all_kategori)) {
            $all_kategori = [];
        }
    }
} catch (Exception $e) {
    error_log("Error in getAllKategori: " . $e->getMessage());
    $all_kategori = [];
}

// Ambil data jenis pembayaran secara dinamis dari database
$sql_jenis = $koneksi->query("SELECT * FROM tb_jenisbayar ORDER BY jenis_bayar ASC");
$jenis_bayar_list = [];
if ($sql_jenis) {
    while ($row = $sql_jenis->fetch_assoc()) {
        $jenis_bayar_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Transaksi</title>
        <style>
        .jenis-bayar-card {
            cursor: pointer !important;
            transition: all 0.3s;
            position: relative;
            z-index: 100 !important;
            pointer-events: auto !important;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        .jenis-bayar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .jenis-bayar-card * {
            pointer-events: none !important;
        }
        .jenis-bayar-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            pointer-events: none;
        }
        .jenis-bayar-card.selected {
            border-color: #28a745 !important;
            background-color: #f0fff4;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        .jenis-bayar-card.selected .card-body {
            background-color: #f0fff4;
        }
        .jenis-bayar-card .card-body {
            text-align: center;
            padding: 1.5rem 1rem;
        }
        .kategori-card {
            cursor: pointer;
            transition: all 0.3s;
        }
        .kategori-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .kategori-card.selected {
            border-color: #28a745 !important;
            background-color: #f0fff4;
        }
        .btn-link {
            text-decoration: none !important;
        }
        .btn-link:hover {
            text-decoration: none !important;
        }
        .btn-link:focus {
            text-decoration: none !important;
            box-shadow: none;
        }
        .btn-link:hover {
            color: #007bff !important;
        }
        .btn-link:not(.collapsed) {
            color: #495057;
            font-weight: 600;
        }
        .card-header {
            background-color: #f8f9fa;
        }
        .card-header h5 {
            margin: 0;
        }
        .produk-grid-item {
            transition: all 0.3s;
            cursor: pointer;
        }
        .produk-grid-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .produk-grid-item.selected {
            border-color: #28a745 !important;
            background-color: #f0fff4;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        }
        .produk-grid-item:active {
            transform: scale(0.98);
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            display: block;
        }
        .form-label i {
            margin-right: 0.5rem;
            width: 20px;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            transition: all 0.3s;
            padding: 0.6rem 0.75rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: 0;
        }
        .input-group-text {
            border-radius: 0;
            background-color: #f8f9fa;
            border-color: #ced4da;
            color: #495057;
            font-weight: 500;
        }
        .input-group-prepend .input-group-text {
            border-radius: 8px 0 0 8px;
        }
        .input-group-append .btn,
        .input-group-append .input-group-text {
            border-radius: 0 8px 8px 0;
            border-left: 0;
        }
        .input-group-append .btn {
            border-left: 1px solid #ced4da;
        }
        </style>
    </head>
    <body>
        <div class="page-breadcrumb">
            <div class="row">
                <div class="col-7 align-self-center">
                    <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Transaksi</h4>
                    <div class="d-flex align-items-center">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb m-0 p-0">
                                <li class="breadcrumb-item"><a href="<?=base_url()?>" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item text-muted active" aria-current="page">Edit Transaksi</li>
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
                                <i class="fa fa-edit"></i> Edit Transaksi
                            </h4>
                        </div>
                        <div class="modern-card-body">
                            <form action="" method="POST" id="formEditTransaksi">
                                <input type="hidden" name="id" value="<?=$data['id_transaksi']?>">

                                <!-- Grid Kategori - Paling Atas -->
                                <div class="modern-card mb-4" id="kategori-section">
                                    <div class="modern-card-header">
                                        <h4>
                                            <i class="fa fa-folder"></i> Pilih Kategori Produk <span class="text-danger">*</span>
                                        </h4>
                                    </div>
                                    <div class="modern-card-body">
                                        <div class="row" id="kategori-grid">
                                            <?php if (!empty($all_kategori)): ?>
                                                <?php foreach ($all_kategori as $kategori): ?>
                                                <div class="col-md-4 col-sm-6 mb-3">
                                                    <div class="card kategori-card border"
                                                         data-kategori="<?=htmlspecialchars($kategori['kategori'])?>"
                                                         style="cursor: pointer;"
                                                         role="button"
                                                         tabindex="0">
                                                        <div class="card-body text-center">
                                                            <i class="fa fa-folder-open fa-3x text-primary mb-2"></i>
                                                            <h6 class="mb-1"><?=htmlspecialchars($kategori['kategori'])?></h6>
                                                            <small class="text-muted"><?=$kategori['jumlah_produk']?> produk</small>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="col-12">
                                                    <div class="alert alert-info text-center">
                                                        <i class="fa fa-info-circle"></i> Belum ada kategori produk. Silakan import produk terlebih dahulu.
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Daftar Produk dalam Accordion (akan muncul setelah kategori dipilih) -->
                                <div class="modern-card mb-4" id="produk-accordion-container" style="display: none;">
                                    <div class="modern-card-header">
                                        <h4>
                                            <i class="fa fa-box"></i> Pilih Produk
                                        </h4>
                                    </div>
                                    <div class="modern-card-body">
                                        <div class="accordion" id="produkAccordion">
                                            <!-- Produk akan dimuat di sini via AJAX -->
                                        </div>
                                    </div>
                                </div>

                                <!-- Jenis Pembayaran (Hidden, akan diisi otomatis dari produk atau manual) -->
                                <input type="hidden" name="jenis" id="jenis" value="<?=$selected_id_bayar?>">

                                <!-- Form Pembayaran -->
                                <div class="modern-card">
                                    <div class="modern-card-header">
                                        <h4>
                                            <i class="fa fa-edit"></i> Form Pembayaran
                                        </h4>
                                    </div>
                                    <div class="modern-card-body">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="tgl" class="form-label">
                                                        <i class="fa fa-calendar-alt text-primary"></i> Tanggal Transaksi
                                                    </label>
                                                    <input type="date" name="tgl" value="<?=$data['tgl'];?>" class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="idpel" class="form-label">
                                                        <i class="fa fa-user text-info"></i> ID Pelanggan
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="hidden" name="id_pelanggan" id="id_pelanggan">
                                                        <input type="text" name="idpel" id="idpel" value="<?=$data['idpel'];?>" class="form-control" readonly>
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-info" data-target="#modalItem" data-toggle="modal" title="Pilih Pelanggan">
                                                                <i class="fa fa-search"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="nama" class="form-label">
                                                        <i class="fa fa-user-circle text-success"></i> Nama Pelanggan
                                                    </label>
                                                    <input type="text" name="nama" id="nama" placeholder="Nama Pelanggan" class="form-control" value="<?=$data['nama'];?>" readonly>
                                                </div>
                                                <div class="form-group">
                                                    <label for="produk" class="form-label">
                                                        <i class="fa fa-box text-info"></i> Produk
                                                    </label>
                                                    <input type="text" name="produk" id="produk" class="form-control" placeholder="Kode produk akan terisi otomatis saat memilih produk" readonly>
                                                    <small class="form-text text-muted">Pilih produk dari kategori di atas untuk mengisi otomatis</small>
                                                </div>
                                                <div class="form-group">
                                                    <label for="ket" class="form-label">
                                                        <i class="fa fa-comment text-warning"></i> Keterangan
                                                    </label>
                                                    <input type="text" name="ket" id="ket" class="form-control" placeholder="Keterangan transaksi (opsional)" value="<?=$data['ket'];?>">
                                                    <small class="form-text text-muted">Field ini dapat dikosongkan</small>
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="harga" class="form-label">
                                                        <i class="fa fa-money-bill-wave text-success"></i> Harga <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">Rp</span>
                                                        </div>
                                                        <input type="number" name="harga" class="form-control" value="<?=$data['harga'];?>" required>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="status" class="form-label">
                                                        <i class="fa fa-info-circle text-primary"></i> Status
                                                    </label>
                                                    <select class="form-control" name="status" id="status">
                                                        <option value="">Pilih Status</option>
                                                        <option value="Lunas" <?php if($status == 'Lunas') {echo "selected";}?>>Lunas</option>
                                                        <option value="Belum" <?php if($status == 'Belum') {echo "selected";}?>>Belum Bayar</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tombol Aksi -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-end align-items-center">
                                            <a href="<?=base_url('transaksi/transaksi.php')?>" class="btn btn-warning btn-modern mr-2">
                                                <i class="fa fa-arrow-left"></i> Kembali
                                            </a>
                                            <button type="submit" name="edit" class="btn btn-success btn-modern">
                                                <i class="fa fa-save"></i> Simpan Perubahan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>

                            <!-- Error Display -->
                            <?php if (isset($post_error_msg) && !empty($post_error_msg)): ?>
                            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert" style="z-index: 9999; position: relative; background-color: #f8d7da; border-color: #f5c6cb;">
                                <i class="fa fa-exclamation-circle"></i> <strong>Error:</strong> <?=htmlspecialchars($post_error_msg)?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    console.error('Edit Error:', <?=json_encode($post_error_msg, JSON_UNESCAPED_UNICODE)?>);
                                    if (typeof Swal !== 'undefined') {
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Gagal!',
                                            text: <?=json_encode($post_error_msg, JSON_UNESCAPED_UNICODE)?>,
                                            confirmButtonColor: '#dc3545',
                                            confirmButtonText: 'OK'
                                        });
                                    } else {
                                        alert(<?=json_encode($post_error_msg, JSON_UNESCAPED_UNICODE)?>);
                                    }
                                });
                            </script>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Element references
            const kategoriCards = document.querySelectorAll('.kategori-card');
            const produkAccordionContainer = document.getElementById('produk-accordion-container');
            const produkAccordion = document.getElementById('produkAccordion');
            const jenisInput = document.getElementById('jenis');
            const hargaInput = document.querySelector('input[name="harga"]');
            const ketInput = document.getElementById('ket');
            const produkInput = document.getElementById('produk');

            let selectedKategori = '';
            let selectedProdukKode = '';

            // Parse data keterangan yang ada untuk memisahkan produk dan keterangan
            // Jika keterangan berformat "KODE - KETERANGAN", pisahkan
            if (ketInput && ketInput.value) {
                const ketValue = ketInput.value.trim();
                // Cek apakah format "KODE - KETERANGAN"
                const match = ketValue.match(/^([A-Z0-9]+)\s*-\s*(.+)$/);
                if (match && produkInput) {
                    // Pisahkan kode produk dan keterangan
                    produkInput.value = match[1];
                    ketInput.value = match[2];
                } else if (produkInput) {
                    // Jika tidak ada format, coba ambil kode produk dari awal (jika ada)
                    // Atau biarkan kosong
                    produkInput.value = '';
                }
            }

            // Handle kategori card clicks
            kategoriCards.forEach(function(card) {
                card.addEventListener('click', function() {
                    const kategori = this.getAttribute('data-kategori');

                    // Remove selected class from all kategori cards
                    kategoriCards.forEach(function(c) {
                        c.classList.remove('selected');
                    });

                    // Add selected class to clicked card
                    this.classList.add('selected');
                    selectedKategori = kategori;
                    selectedProdukKode = '';

                    // Reset accordion dan selected produk saat ganti kategori
                    document.querySelectorAll('.produk-grid-item').forEach(function(card) {
                        card.classList.remove('selected');
                    });

                    // Load produk untuk kategori yang dipilih
                    loadProdukByKategori(kategori);

                    // Scroll to produk accordion
                    setTimeout(function() {
                        if (produkAccordionContainer) {
                            produkAccordionContainer.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }, 300);
                });
            });

            // Fungsi untuk load produk berdasarkan kategori
            function loadProdukByKategori(kategori) {
                if (!kategori) {
                    produkAccordionContainer.style.display = 'none';
                    return;
                }

                produkAccordion.innerHTML = '<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x text-primary"></i><p class="mt-3 text-muted">Memuat produk dari kategori <strong>' + kategori + '</strong>...</p></div>';
                produkAccordionContainer.style.display = 'block';

                fetch('<?=base_url('transaksi/get_produk.php')?>?kategori=' + encodeURIComponent(kategori), {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.produk.length > 0) {
                        let html = '';
                        let accordionIndex = 0;
                        const produkGroups = {};

                        data.produk.forEach(function(produk) {
                            const groupKey = produk.kategori || 'Semua Produk';
                            if (!produkGroups[groupKey]) {
                                produkGroups[groupKey] = [];
                            }
                            produkGroups[groupKey].push(produk);
                        });

                        Object.keys(produkGroups).forEach(function(groupKey) {
                            const produkList = produkGroups[groupKey];
                            const collapseId = 'collapse' + accordionIndex;

                            html += `
                                <div class="card mb-2 border">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">
                                            <button class="btn btn-link ${accordionIndex === 0 ? '' : 'collapsed'}" type="button"
                                                    data-toggle="collapse" data-target="#${collapseId}">
                                                <i class="fa fa-folder-open mr-2 text-primary"></i>
                                                <strong>${groupKey}</strong>
                                                <span class="badge badge-primary ml-2">${produkList.length} produk</span>
                                                <i class="fa fa-chevron-down float-right mt-1"></i>
                                            </button>
                                        </h5>
                                    </div>
                                    <div id="${collapseId}" class="collapse ${accordionIndex === 0 ? 'show' : ''}" data-parent="#produkAccordion">
                                        <div class="card-body p-3">
                                            <div class="row">
                            `;

                            produkList.forEach(function(produk) {
                                const kode = produk.kode || '';
                                const harga = parseFloat(produk.harga) || 0;
                                const keterangan = (produk.keterangan || produk.produk || '').replace(/"/g, '&quot;');
                                const idBayar = produk.id_bayar || null;

                                html += `
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <div class="card produk-grid-item border" data-kode="${kode}" data-harga="${harga}" data-keterangan="${keterangan}" data-id-bayar="${idBayar || ''}" style="cursor: pointer;">
                                            <div class="card-body p-2">
                                                <div class="d-flex justify-content-between align-items-start mb-1">
                                                    <span class="badge badge-info">${kode}</span>
                                                    <strong class="text-success">Rp ${parseInt(harga).toLocaleString('id-ID')}</strong>
                                                </div>
                                                <small class="d-block text-truncate">${(produk.keterangan || produk.produk || 'N/A')}</small>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });

                            html += `</div></div></div></div>`;
                            accordionIndex++;
                        });

                        produkAccordion.innerHTML = html;
                    } else {
                        produkAccordion.innerHTML = '<div class="alert alert-info text-center"><i class="fa fa-info-circle"></i> Tidak ada produk tersedia untuk kategori ini</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading produk:', error);
                    produkAccordion.innerHTML = '<div class="alert alert-danger text-center"><i class="fa fa-exclamation-triangle"></i> Gagal memuat produk. Silakan refresh halaman.</div>';
                });
            }

            // Handle klik produk (event delegation)
            document.body.addEventListener('click', function(e) {
                const produkCard = e.target.closest('.produk-grid-item');
                if (produkCard && produkAccordionContainer && produkAccordionContainer.contains(produkCard)) {
                    const kode = produkCard.getAttribute('data-kode') || '';
                    const harga = parseFloat(produkCard.getAttribute('data-harga')) || 0;
                    const keterangan = produkCard.getAttribute('data-keterangan') || '';
                    const idBayar = produkCard.getAttribute('data-id-bayar');

                    if (kode && harga > 0) {
                        selectedProdukKode = kode;

                        // Isi field harga
                        if (hargaInput) hargaInput.value = harga;

                        // Isi field produk dengan kode produk
                        if (produkInput) {
                            produkInput.value = kode;
                        }

                        // Field keterangan bisa diisi manual atau dibiarkan kosong
                        // Jika ada keterangan dari produk, bisa diisi otomatis (opsional)
                        if (ketInput) {
                            const keteranganClean = keterangan.replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&amp;/g, '&');
                            // Hanya isi jika keterangan kosong atau user ingin update
                            // Biarkan user memilih apakah ingin mengisi keterangan atau tidak
                            if (!ketInput.value || ketInput.value.trim() === '') {
                                // Jika kosong, isi dengan keterangan produk (opsional)
                                ketInput.value = keteranganClean || '';
                            }
                            // Jika sudah ada isi, biarkan user yang memutuskan
                        }

                        // Set id_bayar jika ada
                        if (idBayar && idBayar !== '' && idBayar !== 'null' && jenisInput) {
                            jenisInput.value = idBayar;
                        }

                        // Highlight selected produk
                        document.querySelectorAll('.produk-grid-item').forEach(function(card) {
                            card.classList.remove('selected');
                        });
                        produkCard.classList.add('selected');

                        // Show success notification
                        if (typeof Swal !== 'undefined') {
                            const hargaFormatted = parseInt(harga).toLocaleString('id-ID');
                            Swal.fire({
                                icon: 'success',
                                title: 'Produk Dipilih',
                                html: '<div style="text-align: left; padding: 15px 0;"><div style="font-size: 18px; font-weight: 700; margin-bottom: 10px;">' + kode + '</div><div style="font-size: 22px; color: #28a745; font-weight: 800;">Rp ' + hargaFormatted + '</div></div>',
                                showConfirmButton: true,
                                confirmButtonText: 'OK',
                                confirmButtonColor: '#28a745',
                                timer: 2000,
                                timerProgressBar: true,
                                allowOutsideClick: true,
                                allowEscapeKey: true,
                                width: '420px'
                            });
                        }

                        // Scroll to form
                        setTimeout(function() {
                            if (hargaInput) {
                                hargaInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                hargaInput.focus();
                            }
                        }, 200);
                    }
                }
            });

            // Form submit handler - hanya disable button untuk mencegah double submit
            const formEdit = document.getElementById('formEditTransaksi');
            const submitBtn = formEdit ? formEdit.querySelector('button[type="submit"][name="edit"]') : null;

            if (formEdit && submitBtn) {
                formEdit.addEventListener('submit', function(e) {
                    console.log('=== FORM SUBMIT TRIGGERED ===');

                    // Log semua input values untuk debugging
                    const formData = new FormData(formEdit);
                    console.log('=== FORM DATA ===');
                    const formDataObj = {};
                    for (let [key, value] of formData.entries()) {
                        console.log(key + ': ' + value);
                        formDataObj[key] = value;
                    }

                    // Validasi minimal - hanya cek field yang benar-benar wajib
                    const harga = parseFloat(formDataObj.harga) || 0;
                    if (harga <= 0) {
                        console.error('Error: Harga tidak valid');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Harga Belum Diisi!',
                                text: 'Mohon isi harga transaksi.',
                                confirmButtonColor: '#ffc107'
                            });
                        } else {
                            alert('Error: Harga transaksi harus diisi dan lebih dari 0.');
                        }
                        e.preventDefault();
                        return false;
                    }

                    if (!formDataObj.tgl || !formDataObj.idpel || !formDataObj.nama) {
                        console.error('Error: Field wajib belum diisi');
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Data Belum Lengkap!',
                                text: 'Pastikan semua field wajib sudah diisi (Tanggal, ID Pelanggan, Nama).',
                                confirmButtonColor: '#ffc107'
                            });
                        } else {
                            alert('Error: Pastikan semua field wajib sudah diisi (Tanggal, ID Pelanggan, Nama).');
                        }
                        e.preventDefault();
                        return false;
                    }

                    // Field keterangan dan produk adalah optional, tidak perlu validasi

                    console.log('Form validation passed, submitting...');

                    // Disable button untuk mencegah double submit
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Menyimpan...';

                    // Allow form to submit - jangan preventDefault jika validasi berhasil
                    return true;
                });
            }
        });

        // Suppress chart-related errors on non-dashboard pages
        // These errors occur because chart scripts are loaded but elements don't exist
        window.addEventListener('error', function(e) {
            // Suppress errors from chartist and jvectormap if they're trying to access non-existent elements
            if (e.filename && (
                e.filename.includes('chartist') ||
                e.filename.includes('jvectormap') ||
                e.message.includes('querySelector') ||
                e.message.includes('NaN') && e.message.includes('transform')
            )) {
                // Only suppress if it's a chart-related error on a non-dashboard page
                var isDashboardPage = document.getElementById('campaign-v2') ||
                                     document.getElementById('chart-transaksi') ||
                                     document.getElementById('chart-pendapatan');
                if (!isDashboardPage) {
                    e.preventDefault();
                    console.warn('Chart script error suppressed (non-dashboard page):', e.message);
                    return true;
                }
            }
        }, true);
        </script>
    </body>
</html>
<?php
include_once('modal_item.php');
include_once('../footer.php');
?>
