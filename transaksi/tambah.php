<?php
$page_title = 'Tambah Transaksi';
include_once('../config/config.php');
require_once '../libs/produk_helper.php';

// Cek dan tambahkan kolom produk jika belum ada
$check_column = $koneksi->query("SHOW COLUMNS FROM transaksi LIKE 'produk'");
if (!$check_column || $check_column->num_rows == 0) {
    // Kolom produk belum ada, tambahkan
    $add_column_query = "ALTER TABLE `transaksi` ADD COLUMN `produk` VARCHAR(255) NULL AFTER `nama`";
    $koneksi->query($add_column_query);
}

if (isset($_POST['simpan'])) {

    $tgl    = isset($_POST['tgl']) ? trim($_POST['tgl']) : '';
    $idpel  = isset($_POST['idpel']) ? trim($_POST['idpel']) : '';
    $nama   = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $produk = isset($_POST['produk']) ? trim($_POST['produk']) : '';
    $harga  = isset($_POST['harga']) ? trim($_POST['harga']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $ket    = isset($_POST['ket']) ? trim($_POST['ket']) : '';

    // Validasi input
    $error_msg = '';
    if (empty($tgl)) {
        $error_msg = 'Tanggal transaksi belum diisi.';
    } elseif (empty($idpel)) {
        $error_msg = 'ID pelanggan belum diisi.';
    } elseif (empty($nama)) {
        $error_msg = 'Nama pelanggan belum diisi.';
    } elseif (empty($harga) || floatval($harga) <= 0) {
        $error_msg = 'Harga tidak valid atau belum diisi. Harga: ' . $harga;
    }

    if (empty($error_msg)) {
        // Persiapkan data untuk Prepared Statement
        $harga = floatval($harga);
        
        // Ambil id_bayar default (karena kolom masih required di database)
        // Menggunakan id_bayar pertama sebagai default
        $default_query = $koneksi->query("SELECT id_bayar FROM tb_jenisbayar ORDER BY id_bayar ASC LIMIT 1");
        $id_bayar_default = 1; // Default fallback
        if ($default_query && $default_query->num_rows > 0) {
            $default_row = $default_query->fetch_assoc();
            $id_bayar_default = intval($default_row['id_bayar']);
        }

        // Cek apakah kolom produk ada di tabel
        $check_column = $koneksi->query("SHOW COLUMNS FROM transaksi LIKE 'produk'");
        $has_produk_column = ($check_column && $check_column->num_rows > 0);

        // Prepare values
        $produk_val = !empty($produk) ? $produk : null;
        $ket_val = !empty($ket) ? $ket : '';

        // Query INSERT dengan Prepared Statement
        if ($has_produk_column) {
            $query = "INSERT INTO transaksi (tgl, idpel, nama, produk, id_bayar, harga, status, ket)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param("ssssidss", $tgl, $idpel, $nama, $produk_val, $id_bayar_default, $harga, $status, $ket_val);
        } else {
            $query = "INSERT INTO transaksi (tgl, idpel, nama, id_bayar, harga, status, ket)
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param("sssidss", $tgl, $idpel, $nama, $id_bayar_default, $harga, $status, $ket_val);
        }

        $sql = $stmt->execute();

        if ($sql) {
            $id_transaksi = $koneksi->insert_id; // Ambil ID transaksi yang baru dibuat

            // Log aktivitas
            require_once '../libs/log_activity.php';
            @log_activity('create', 'transaksi', 'Menambah transaksi: ' . $nama . ' (ID: ' . $idpel . ')');

            // Proses saldo jika status Lunas
            require_once '../libs/saldo_helper.php';
            $ket_saldo = 'Transaksi: ' . $nama . ' (ID: ' . $idpel . ')';
            if (!empty($produk)) {
                $ket_saldo .= ' - ' . $produk;
            }
            $saldo_result = proses_saldo_transaksi($koneksi, $status, $harga, $ket_saldo, $id_transaksi);

            // Set flag sukses untuk ditampilkan SweetAlert di halaman transaksi
            // Session sudah dimulai di config.php
            if ($saldo_result['success']) {
                $_SESSION['success_message'] = 'Transaksi berhasil ditambahkan';
            } else {
                // Jika saldo tidak cukup, tetap simpan transaksi tapi beri peringatan
                $_SESSION['success_message'] = 'Transaksi berhasil ditambahkan. ' . $saldo_result['message'];
                $_SESSION['success_type'] = 'warning';
            }
            $_SESSION['success_type'] = $_SESSION['success_type'] ?? 'tambah';

            // Redirect langsung ke halaman transaksi
            header('Location: ' . base_url('transaksi/transaksi.php'));
            exit();
        } else {
            $db_error = mysqli_error($koneksi);
            $error_msg = 'Gagal menyimpan transaksi: ' . $db_error;
            $post_error_msg = $error_msg;
        }
    } else {
        $post_error_msg = $error_msg;
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

// Ambil data kategori produk secara dinamis dari database
$all_kategori = getAllKategori();

// Ambil data jenis pembayaran secara dinamis dari database (untuk form)
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
        .produk-item-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        .produk-item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            border-color: #007bff !important;
        }
        .produk-item-card.selected {
            border-color: #28a745 !important;
            background-color: #f0fff4;
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
        .swal-toast-popup {
            border-left: 5px solid #28a745 !important;
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.3) !important;
            border-radius: 8px !important;
            background: #ffffff !important;
            min-width: 320px !important;
        }
        .swal-toast-popup .swal2-title {
            font-size: 19px !important;
            font-weight: 800 !important;
            color: #212529 !important;
            margin-bottom: 8px !important;
            letter-spacing: 0.5px !important;
        }
        .swal-toast-popup .swal2-content {
            font-size: 17px !important;
            color: #28a745 !important;
            font-weight: 700 !important;
            margin-top: 4px !important;
            padding-top: 8px !important;
        }
        .swal-toast-popup .swal2-html-container {
            display: block !important;
            visibility: visible !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .swal-toast-popup .swal2-icon {
            width: 3em !important;
            height: 3em !important;
            margin: 0 1em 0 0 !important;
        }
        /* Custom styling untuk toast notification */
        .swal2-toast-custom {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
            border-radius: 8px !important;
        }
        .swal2-toast-custom .swal2-title {
            font-size: 16px !important;
            font-weight: 600 !important;
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }
        .swal2-toast-custom .swal2-html-container {
            margin: 0 !important;
            padding: 0 !important;
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
                                <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item text-muted active" aria-current="page">Tambah Transaksi</li>
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
                    <div class="modern-card-header d-flex justify-content-between align-items-center">
                        <h4>
                            <i class="fa fa-plus"></i> Tambah Transaksi
                        </h4>
                        <a href="<?=base_url('transaksi/transaksi.php')?>" class="btn btn-warning btn-modern">
                            <i class="fa fa-arrow-left"></i> Kembali
                        </a>
                    </div>
                    <div class="modern-card-body">
                        <form action="" method="POST" id="formTambahTransaksi">
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
                                            <div class="form-group mb-3">
                                                <label for="tgl" class="form-label">
                                                    <i class="fa fa-calendar-alt text-primary"></i> Tanggal Transaksi
                                                </label>
                                                <input type="date" name="tgl" value="<?=date('Y-m-d')?>" class="form-control" required>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="idpel" class="form-label">
                                                    <i class="fa fa-user text-info"></i> ID Pelanggan
                                                </label>
                                                <div class="input-group">
                                                    <input type="hidden" name="id_pelanggan" id="id_pelanggan">
                                                    <input type="text" name="idpel" id="idpel" placeholder="ID Pelanggan" class="form-control" readonly>
                                                    <div class="input-group-append">
                                                        <button type="button" class="btn btn-info" data-target="#modalItem" data-toggle="modal" title="Pilih Pelanggan">
                                                            <i class="fa fa-search"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="nama" class="form-label">
                                                    <i class="fa fa-user-circle text-success"></i> Nama Pelanggan
                                                </label>
                                                <input type="text" name="nama" id="nama" placeholder="Nama Pelanggan" class="form-control" readonly>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="produk" class="form-label">
                                                    <i class="fa fa-box text-info"></i> Produk
                                                </label>
                                                <input type="text" name="produk" id="produk" class="form-control" placeholder="Nama produk akan terisi otomatis saat memilih produk" readonly>
                                                <small class="form-text text-muted">Pilih produk dari kategori di atas untuk mengisi otomatis</small>
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group mb-3">
                                                <label for="harga" class="form-label">
                                                    <i class="fa fa-money-bill-wave text-success"></i> Harga <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Rp</span>
                                                    </div>
                                                    <input type="number" name="harga" id="harga" class="form-control" placeholder="0" required>
                                                </div>
                                                <small class="form-text text-muted">Pilih produk dari kategori di atas untuk mengisi harga otomatis</small>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="ket" class="form-label">
                                                    <i class="fa fa-comment text-warning"></i> Keterangan
                                                </label>
                                                <input type="text" name="ket" id="ket" class="form-control" placeholder="Kode produk akan terisi otomatis saat memilih produk, atau isi manual">
                                                <small class="form-text text-muted">Pilih produk dari kategori di atas untuk mengisi otomatis, atau isi manual dengan keterangan lain</small>
                                            </div>
                                            <div class="form-group mb-3">
                                                <label for="status" class="form-label">
                                                    <i class="fa fa-info-circle text-primary"></i> Status
                                                </label>
                                                <select class="form-control" name="status" id="status">
                                                    <option value="">Pilih Status</option>
                                                    <option value="Lunas">Lunas</option>
                                                    <option value="Belum">Belum Bayar</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tombol Reset dan Simpan di dalam box Form Pembayaran -->
                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <div class="d-flex justify-content-end align-items-center flex-wrap" style="gap: 0.5rem;">
                                                <button type="button" class="btn btn-secondary btn-modern" onclick="resetForm()">
                                                    <i class="fa fa-refresh"></i> Reset Form
                                                </button>
                                                <button type="submit" name="simpan" class="btn btn-success btn-modern">
                                                    <i class="fa fa-save"></i> Simpan Transaksi
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                // Tampilkan error jika ada (dari proses POST di atas)
                if (isset($post_error_msg) && !empty($post_error_msg)) {
                ?>
                <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    setTimeout(function() {
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
                    }, 100);
                });
                </script>
                <?php
                }
                ?>
            </div>
        </div>

        <script>
        // Handle selection kategori dan load produk
        document.addEventListener('DOMContentLoaded', function() {
            const kategoriCards = document.querySelectorAll('.kategori-card');
            const produkAccordionContainer = document.getElementById('produk-accordion-container');
            const produkAccordion = document.getElementById('produkAccordion');
            const hargaInput = document.getElementById('harga');
            const ketInput = document.getElementById('ket');
            const produkInput = document.getElementById('produk');
            let selectedKategori = '';
            let selectedProdukKode = '';

            // Fungsi reset form
            window.resetForm = function() {
                // Konfirmasi reset jika ada data yang sudah diisi
                const harga = parseFloat(hargaInput.value) || 0;
                const idpel = document.getElementById('idpel') ? document.getElementById('idpel').value.trim() : '';
                const ket = ketInput ? ketInput.value.trim() : '';

                if (harga > 0 || idpel || ket) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'question',
                            title: 'Reset Form?',
                            text: 'Semua data yang sudah diisi akan dihapus. Lanjutkan?',
                            showCancelButton: true,
                            confirmButtonColor: '#6c757d',
                            cancelButtonColor: '#28a745',
                            confirmButtonText: 'Ya, Reset',
                            cancelButtonText: 'Batal'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                doResetForm();
                            }
                        });
                    } else {
                        if (confirm('Semua data yang sudah diisi akan dihapus. Lanjutkan?')) {
                            doResetForm();
                        }
                    }
                } else {
                    // Langsung reset jika form kosong
                    doResetForm();
                }
            };

            // Fungsi untuk melakukan reset form
            function doResetForm() {
                // Reset semua input form
                const form = document.getElementById('formTambahTransaksi');
                if (form) {
                    // Reset tanggal ke hari ini
                    const tglInput = form.querySelector('input[name="tgl"]');
                    if (tglInput) {
                        tglInput.value = '<?=date('Y-m-d')?>';
                    }

                    // Reset idpel dan nama (readonly, harus via modal)
                    const idpelInput = document.getElementById('idpel');
                    const namaInput = document.getElementById('nama');
                    const idPelangganInput = document.getElementById('id_pelanggan');
                    if (idpelInput) idpelInput.value = '';
                    if (namaInput) namaInput.value = '';
                    if (idPelangganInput) idPelangganInput.value = '';

                    // Reset harga
                    if (hargaInput) hargaInput.value = '';

                    // Reset produk
                    if (produkInput) produkInput.value = '';

                    // Reset keterangan
                    if (ketInput) ketInput.value = '';

                    // Reset status
                    const statusInput = form.querySelector('select[name="status"]');
                    if (statusInput) statusInput.value = '';
                }

                // Reset selected kategori
                kategoriCards.forEach(function(card) {
                    card.classList.remove('selected');
                });
                selectedKategori = '';

                // Reset selected produk
                document.querySelectorAll('.produk-grid-item').forEach(function(card) {
                    card.classList.remove('selected');
                });
                selectedProdukKode = '';

                // Sembunyikan accordion produk
                if (produkAccordionContainer) {
                    produkAccordionContainer.style.display = 'none';
                }
                if (produkAccordion) {
                    produkAccordion.innerHTML = '';
                }

                // Scroll kembali ke paling atas halaman (seperti saat pertama kali buka)
                window.scrollTo({ top: 0, behavior: 'smooth' });

                // Alternatif: scroll ke kategori section jika ingin lebih spesifik
                // setTimeout(function() {
                //     const kategoriSection = document.getElementById('kategori-section');
                //     if (kategoriSection) {
                //         kategoriSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                //     }
                // }, 100);

                // Focus pada grid kategori pertama
                if (kategoriCards.length > 0) {
                    setTimeout(function() {
                        kategoriCards[0].focus();
                    }, 500);
                }
            }

            // Validasi form sebelum submit
            window.validateForm = function() {
                try {

                    // Cek apakah harga sudah diisi
                    const hargaVal = hargaInput ? hargaInput.value : '';
                    const harga = parseFloat(hargaVal) || 0;

                    if (harga <= 0) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Harga Belum Diisi!',
                                text: 'Mohon isi harga transaksi atau pilih produk untuk mengisi harga otomatis.',
                                confirmButtonColor: '#ffc107'
                            });
                        } else {
                            alert('Mohon isi harga transaksi atau pilih produk untuk mengisi harga otomatis.');
                        }
                        if (hargaInput) hargaInput.focus();
                        return false;
                    }

                    // Cek apakah ID pelanggan sudah diisi
                    const idpelInput = document.getElementById('idpel');
                    const idpel = idpelInput ? idpelInput.value.trim() : '';

                    if (!idpel) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'warning',
                                title: 'ID Pelanggan Belum Diisi!',
                                text: 'Mohon pilih pelanggan terlebih dahulu.',
                                confirmButtonColor: '#ffc107'
                            });
                        } else {
                            alert('Mohon pilih pelanggan terlebih dahulu.');
                        }
                        if (idpelInput) {
                            const inputGroup = idpelInput.closest('.input-group');
                            if (inputGroup) {
                                const searchBtn = inputGroup.querySelector('[data-toggle="modal"]');
                                if (searchBtn) searchBtn.click();
                            }
                        }
                        return false;
                    }

                    return true;
                } catch (error) {
                    // Jika ada error, tetap izinkan submit (fallback)
                    return true;
                }
            };

            // HAPUS SEMUA EVENT LISTENER - BIARKAN FORM SUBMIT MURNI
            // Form akan submit langsung tanpa ada JavaScript yang menghalangi

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
                    selectedProdukKode = ''; // Reset selected produk saat ganti kategori

                    // Reset accordion dan selected produk saat ganti kategori
                    selectedProdukKode = '';
                    document.querySelectorAll('.produk-grid-item').forEach(function(card) {
                        card.classList.remove('selected');
                    });

                    // Load produk untuk kategori yang dipilih
                    loadProdukByKategori(kategori);

                    // Scroll to produk accordion dengan delay untuk smooth animation
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

                // Show loading
                produkAccordion.innerHTML = '<div class="text-center p-4"><p class="mt-3 text-muted">Memuat produk dari kategori <strong>' + kategori + '</strong>...</p></div>';
                produkAccordionContainer.style.display = 'block';

                // Load produk via AJAX dengan timeout
                const controller = new AbortController();
                const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 detik timeout

                fetch('<?=base_url('transaksi/get_produk.php')?>?kategori=' + encodeURIComponent(kategori), {
                    signal: controller.signal,
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json'
                    }
                })
                    .then(response => {
                        clearTimeout(timeoutId);
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success && data.produk.length > 0) {
                            // Group produk by sub-kategori atau langsung tampilkan
                            let html = '';
                            let accordionIndex = 0;

                            // Group produk (jika ada sub-kategori dalam kategori)
                            const produkGroups = {};
                            data.produk.forEach(function(produk) {
                                const groupKey = produk.kategori || 'Semua Produk';
                                if (!produkGroups[groupKey]) {
                                    produkGroups[groupKey] = [];
                                }
                                produkGroups[groupKey].push(produk);
                            });

                            // Generate accordion untuk setiap group
                            Object.keys(produkGroups).forEach(function(groupKey) {
                                const produkList = produkGroups[groupKey];
                                const accordionId = 'accordion' + accordionIndex;
                                const collapseId = 'collapse' + accordionIndex;

                                html += `
                                    <div class="card mb-2 border">
                                        <div class="card-header bg-light" id="heading${accordionIndex}">
                                            <h5 class="mb-0">
                                                <button class="btn btn-link ${accordionIndex === 0 ? '' : 'collapsed'}" type="button"
                                                        data-toggle="collapse" data-target="#${collapseId}"
                                                        aria-expanded="${accordionIndex === 0 ? 'true' : 'false'}"
                                                        aria-controls="${collapseId}"
                                                        style="text-decoration: none; color: #495057; width: 100%; text-align: left; padding: 0.75rem 1.25rem;">
                                                    <i class="fa fa-folder-open mr-2 text-primary"></i>
                                                    <strong>${groupKey}</strong>
                                                    <span class="badge badge-primary ml-2">${produkList.length} produk</span>
                                                    <i class="fa fa-chevron-down float-right mt-1" style="transition: transform 0.3s;"></i>
                                                </button>
                                            </h5>
                                        </div>
                                        <div id="${collapseId}"
                                             class="collapse ${accordionIndex === 0 ? 'show' : ''}"
                                             aria-labelledby="heading${accordionIndex}"
                                             data-parent="#produkAccordion">
                                            <div class="card-body p-3">
                                                <div class="row">
                                `;

                                produkList.forEach(function(produk) {
                                    const kode = produk.kode || '';
                                    const harga = parseFloat(produk.harga) || 0;
                                    const keterangan = (produk.keterangan || produk.produk || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                                    const namaProduk = (produk.produk || produk.keterangan || '').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
                                    const idBayar = produk.id_bayar || null;

                                    html += `
                                        <div class="col-md-6 col-lg-4 mb-2">
                                            <div class="card produk-grid-item border"
                                                 data-kode="${kode}"
                                                 data-harga="${harga}"
                                                 data-keterangan="${keterangan}"
                                                 data-produk="${namaProduk}"
                                                 data-id-bayar="${idBayar || ''}"
                                                 style="cursor: pointer;">
                                                <div class="card-body p-2">
                                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                                        <span class="badge badge-info">${kode}</span>
                                                        <strong class="text-success">Rp ${parseInt(harga).toLocaleString('id-ID')}</strong>
                                                    </div>
                                                    <small class="d-block text-truncate" title="${(produk.keterangan || produk.produk || '')}">
                                                        ${(produk.keterangan || produk.produk || 'N/A')}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    `;
                                });

                                html += `
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                `;

                                accordionIndex++;
                            });

                            produkAccordion.innerHTML = html;

                            // Reinitialize Bootstrap collapse untuk accordion yang baru di-load
                            if (typeof $ !== 'undefined' && $.fn.collapse) {
                                // Bootstrap akan auto-handle collapse, tapi kita pastikan icon chevron berubah saat collapse
                                $(produkAccordion).find('[data-toggle="collapse"]').on('click', function() {
                                    const $icon = $(this).find('.fa-chevron-down');
                                    const isExpanded = $(this).attr('aria-expanded') === 'true';
                                    setTimeout(function() {
                                        if ($icon.length) {
                                            $icon.css('transform', isExpanded ? 'rotate(-90deg)' : 'rotate(0deg)');
                                        }
                                    }, 100);
                                });

                                // Update icon pada accordion yang sudah expanded
                                $(produkAccordion).find('.collapse.show').prev().find('.fa-chevron-down').css('transform', 'rotate(-90deg)');
                            }
                        } else {
                            produkAccordion.innerHTML = '<div class="alert alert-info text-center"><i class="fa fa-info-circle"></i> Tidak ada produk tersedia untuk kategori ini</div>';
                        }
                    })
                    .catch(error => {
                        clearTimeout(timeoutId);

                        let errorMessage = 'Gagal memuat produk. ';
                        if (error.name === 'AbortError') {
                            errorMessage += 'Request timeout. Periksa koneksi internet Anda.';
                        } else {
                            errorMessage += 'Silakan refresh halaman atau coba lagi.';
                        }

                        produkAccordion.innerHTML = '<div class="alert alert-danger text-center">' +
                            '<i class="fa fa-exclamation-triangle"></i><br>' +
                            errorMessage + '<br>' +
                            '<button class="btn btn-sm btn-primary mt-2" onclick="location.reload()">Refresh Halaman</button>' +
                            '</div>';
                    });
            }

            // Handle klik produk (menggunakan event delegation untuk menghindari masalah escape)
            // Gunakan document.body untuk event delegation yang lebih stabil (akan catch semua produk card)
            document.body.addEventListener('click', function(e) {
                const produkCard = e.target.closest('.produk-grid-item');
                if (produkCard && produkAccordionContainer.contains(produkCard)) {
                    e.preventDefault();
                    e.stopPropagation();

                    const kode = produkCard.getAttribute('data-kode') || '';
                    const harga = parseFloat(produkCard.getAttribute('data-harga')) || 0;
                    const keterangan = produkCard.getAttribute('data-keterangan') || '';
                    const namaProduk = produkCard.getAttribute('data-produk') || keterangan;
                    const idBayar = produkCard.getAttribute('data-id-bayar');

                    // Validasi data
                    if (!kode || harga <= 0) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'Data produk tidak valid. Silakan coba lagi.',
                                timer: 2000
                            });
                        }
                        return;
                    }

                    // Simpan kode produk yang dipilih
                    selectedProdukKode = kode;

                    // Fill form fields
                    if (hargaInput) {
                        hargaInput.value = harga;
                        // Trigger change event untuk validasi
                        hargaInput.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    if (produkInput) {
                        const namaProdukClean = namaProduk.replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&amp;/g, '&');
                        produkInput.value = namaProdukClean || keterangan.replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&amp;/g, '&') || kode;
                    }
                    if (ketInput) {
                        const keteranganClean = keterangan.replace(/&quot;/g, '"').replace(/&#39;/g, "'").replace(/&amp;/g, '&');
                        const ketValue = kode + (keteranganClean ? ' - ' + keteranganClean : '');
                        ketInput.value = ketValue;
                    }

                    // Highlight selected produk dengan animasi
                    document.querySelectorAll('.produk-grid-item').forEach(function(card) {
                        card.classList.remove('selected');
                    });
                    produkCard.classList.add('selected');

                    // Add visual feedback dengan pulse effect
                    produkCard.style.transform = 'scale(0.95)';
                    setTimeout(function() {
                        produkCard.style.transform = '';
                    }, 200);

                    // Scroll to form dengan smooth behavior
                    setTimeout(function() {
                        if (hargaInput) {
                            hargaInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                            hargaInput.focus();
                            hargaInput.select();
                        }
                    }, 300);

                    // Show success notification menggunakan SweetAlert modal
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
                }
            });
        });
        </script>
    </body>
</html>
<?php
include"modal_item.php";
include_once('../footer.php');
?>




