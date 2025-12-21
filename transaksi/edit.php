<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

include_once('../config/config.php');
require_once '../libs/produk_helper.php';

// Validasi ID parameter
if (!isset($_GET['id'])) {
    header('Location: ' . base_url('transaksi/transaksi.php'));
    exit();
}

// PROSES POST UPDATE HARUS SEBELUM HEADER UNTUK REDIRECT
if (isset($_POST['edit'])) {

    $id     = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $tgl    = isset($_POST['tgl']) ? trim($_POST['tgl']) : '';
    $idpel  = isset($_POST['idpel']) ? trim($_POST['idpel']) : '';
    $nama   = isset($_POST['nama']) ? trim($_POST['nama']) : '';
    $jenis  = isset($_POST['jenis']) ? trim($_POST['jenis']) : '';
    $harga  = isset($_POST['harga']) ? trim($_POST['harga']) : '';
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $ket    = isset($_POST['ket']) ? trim($_POST['ket']) : '';

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
        if (!empty($jenis) && $jenis !== '' && $jenis !== '0' && $jenis !== 'null') {
            $jenis_sql = intval($jenis); // Pastikan integer
        } else {
            // Jika jenis kosong, ambil ID jenis bayar pertama sebagai default
            $default_query = $koneksi->query("SELECT id_bayar FROM tb_jenisbayar ORDER BY id_bayar ASC LIMIT 1");
            if ($default_query && $default_query->num_rows > 0) {
                $default_row = $default_query->fetch_assoc();
                $jenis_sql = intval($default_row['id_bayar']);
            } else {
                // Jika tidak ada jenis bayar sama sekali, error
                $error_msg = 'Tidak ada jenis pembayaran tersedia. Silakan tambahkan jenis pembayaran terlebih dahulu.';
                $post_error_msg = $error_msg;
            }
        }

        // Pastikan jenis_sql tidak kosong
        if (empty($jenis_sql) || $jenis_sql <= 0) {
            $error_msg = 'ID pembayaran tidak valid.';
            $post_error_msg = $error_msg;
        } else {
            $query = "UPDATE transaksi SET tgl='$tgl', idpel='$idpel', nama='$nama', id_bayar=$jenis_sql, harga=$harga, status='$status', ket='$ket' WHERE id_transaksi=$id";

            $sql = $koneksi->query($query);

            if ($sql) {
                // Log aktivitas
                require_once '../libs/log_activity.php';
                @log_activity('update', 'transaksi', 'Mengedit transaksi ID: ' . $id);

                // Set session message
                if (!isset($_SESSION)) {
                    session_start();
                }
                $_SESSION['success_message'] = 'Transaksi berhasil diedit';

                // Redirect langsung dengan header (sebelum output HTML)
                header('Location: ' . base_url('transaksi/transaksi.php'));
                exit();
            } else {
                $db_error = mysqli_error($koneksi);
                $error_msg = 'Gagal menyimpan perubahan: ' . $db_error;
                $post_error_msg = $error_msg;
            }
        }
    }

    // Simpan error untuk ditampilkan setelah header
    $post_error_msg = $error_msg;
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
                            <form action="<?=$_SERVER['PHP_SELF']?>" method="POST" id="formEditTransaksi">
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
                                                    <label for="ket" class="form-label">
                                                        <i class="fa fa-comment text-warning"></i> Keterangan
                                                    </label>
                                                    <input type="text" name="ket" class="form-control" placeholder="Isi Keterangan Transaksi" value="<?=$data['ket'];?>">
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
                            <div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">
                                <i class="fa fa-exclamation-circle"></i> <?=htmlspecialchars($post_error_msg)?>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
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
            const ketInput = document.querySelector('input[name="ket"]');

            let selectedKategori = '';
            let selectedProdukKode = '';

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

                        if (hargaInput) hargaInput.value = harga;
                        if (ketInput) {
                            const keteranganClean = keterangan.replace(/&quot;/g, '"');
                            ketInput.value = kode + (keteranganClean ? ' - ' + keteranganClean : '');
                        }
                        if (idBayar && idBayar !== '' && idBayar !== 'null' && jenisInput) {
                            jenisInput.value = idBayar;
                        }

                        document.querySelectorAll('.produk-grid-item').forEach(function(card) {
                            card.classList.remove('selected');
                        });
                        produkCard.classList.add('selected');

                        setTimeout(function() {
                            if (hargaInput) {
                                hargaInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                hargaInput.focus();
                            }
                        }, 200);
                    }
                }
            });

            // Form submit handler - hanya disable button, tidak validasi
            const formEdit = document.getElementById('formEditTransaksi');
            const submitBtn = formEdit ? formEdit.querySelector('button[type="submit"][name="edit"]') : null;

            if (formEdit && submitBtn) {
                formEdit.addEventListener('submit', function(e) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Menyimpan...';
                    return true;
                });
            }
        });
        </script>
    </body>
</html>
<?php
include_once('modal_item.php');
include_once('../footer.php');
?>
