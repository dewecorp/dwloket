<?php
/**
 * Halaman utama OrderKuota - Lengkap dengan Daftar Produk, Form Pembayaran, dan Riwayat
 */
include_once('../header.php');
include_once('../config/config.php');
require_once '../libs/orderkuota_api.php';
require_once '../libs/log_activity.php';
require_once '../libs/produk_helper.php';

// Handle pembayaran
$payment_result = null;
$payment_message = '';

if (isset($_POST['pay'])) {
    $product_code = $_POST['product_code'] ?? '';
    $target = $_POST['target'] ?? '';
    $harga = floatval($_POST['harga'] ?? 0);

    if (empty($product_code) || empty($target)) {
        $payment_message = 'Mohon lengkapi semua field yang wajib diisi!';
        $payment_result = ['success' => false, 'message' => $payment_message];
    } elseif ($harga <= 0) {
        $payment_message = 'Harga tidak valid. Silakan cek harga terlebih dahulu.';
        $payment_result = ['success' => false, 'message' => $payment_message];
    } else {
        // Generate reference ID
        $ref_id = 'OK_' . date('YmdHis') . '_' . rand(1000, 9999);

        // Lakukan pembayaran
        $payment_result = pay_via_orderkuota($product_code, $target, $ref_id);

        if ($payment_result['success']) {
            // Simpan ke database transaksi
            $tgl = date('Y-m-d H:i:s');
            $nama = 'OrderKuota: ' . $product_code . ' - Ref: ' . ($payment_result['data']['ref_id'] ?? $ref_id);
            $idpel = $target;

            // Ambil id_bayar berdasarkan produk (jika ada mapping)
            $id_bayar = null;
            $jenis_query = $koneksi->query("SELECT id_bayar FROM tb_jenisbayar WHERE jenis_bayar LIKE '%" . mysqli_real_escape_string($koneksi, $product_code) . "%' LIMIT 1");
            if ($jenis_query && $jenis_query->num_rows > 0) {
                $id_bayar = $jenis_query->fetch_assoc()['id_bayar'];
            }

            // Tambahkan keterangan lengkap
            $ket = "OrderKuota: $product_code - Ref: " . ($payment_result['data']['ref_id'] ?? $ref_id);
            if (isset($payment_result['data']['token']) && !empty($payment_result['data']['token'])) {
                $ket .= " - Token: " . $payment_result['data']['token'];
            }

            $insert_query = "INSERT INTO transaksi (tgl, idpel, nama, id_bayar, harga, status, ket)
                            VALUES ('$tgl', '" . mysqli_real_escape_string($koneksi, $idpel) . "',
                                    '" . mysqli_real_escape_string($koneksi, $nama) . "',
                                    " . ($id_bayar ? $id_bayar : 'NULL') . ",
                                    $harga, 'Lunas',
                                    '" . mysqli_real_escape_string($koneksi, $ket) . "')";

            if ($koneksi->query($insert_query)) {
                $transaction_id = $koneksi->insert_id;

                // Log aktivitas
                log_activity('payment', 'orderkuota', "Pembayaran OrderKuota berhasil - Produk: $product_code, Target: $target, Harga: Rp " . number_format($harga, 0, ',', '.'));

                // Redirect ke detail transaksi
                header('Location: ' . base_url('orderkuota/detail.php?id=' . $transaction_id));
                exit;
            } else {
                $payment_message = 'Error menyimpan transaksi: ' . $koneksi->error;
                $payment_result = ['success' => false, 'message' => $payment_message];
            }
        } else {
            $payment_message = $payment_result['message'] ?? 'Pembayaran gagal';
        }
    }
}

// Handle cek harga
$price_result = null;
if (isset($_POST['cek_harga'])) {
    $product_code = $_POST['product_code'] ?? '';
    $target = $_POST['target'] ?? '';

    if (empty($product_code) || empty($target)) {
        $price_result = ['success' => false, 'message' => 'Mohon lengkapi kode produk dan nomor tujuan'];
    } else {
        $price_result = check_price_orderkuota($product_code, $target);
    }
}

// Handle cek saldo
$balance_result = null;
if (isset($_GET['cek_saldo'])) {
    $balance_result = check_balance_orderkuota();
}

// Ambil semua kategori produk
$all_kategori = getAllKategori();

// Get filter untuk riwayat pembayaran
$filter_status = $_GET['status'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$search_history = $_GET['search_history'] ?? '';

// Build query untuk riwayat
$where_conditions = ["ket LIKE '%OrderKuota%'"];

if ($filter_status) {
    $where_conditions[] = "status = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'";
}

if ($filter_date_from) {
    $where_conditions[] = "DATE(tgl) >= '" . mysqli_real_escape_string($koneksi, $filter_date_from) . "'";
}

if ($filter_date_to) {
    $where_conditions[] = "DATE(tgl) <= '" . mysqli_real_escape_string($koneksi, $filter_date_to) . "'";
}

if ($search_history) {
    $search_escaped = mysqli_real_escape_string($koneksi, $search_history);
    $where_conditions[] = "(nama LIKE '%$search_escaped%' OR idpel LIKE '%$search_escaped%' OR ket LIKE '%$search_escaped%')";
}

$where_clause = implode(' AND ', $where_conditions);

// Get riwayat transaksi (limit 20 terakhir)
$history_transaksi = [];
$history_query = $koneksi->query("SELECT * FROM transaksi WHERE $where_clause ORDER BY tgl DESC, id_transaksi DESC LIMIT 20");
if ($history_query) {
    while ($row = $history_query->fetch_assoc()) {
        // Extract ref_id dari keterangan
        preg_match('/Ref: ([A-Z0-9_]+)/', $row['ket'], $matches);
        $row['ref_id'] = $matches[1] ?? '';

        // Extract product name
        preg_match('/OrderKuota: ([^-]+)/', $row['ket'], $product_matches);
        $row['product_name'] = trim($product_matches[1] ?? '');

        $history_transaksi[] = $row;
    }
}
?>

<style>
.kategori-card {
    transition: all 0.3s;
}
.kategori-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    border-color: #007bff !important;
}
.kategori-card.active {
    border-color: #28a745 !important;
    background-color: #f0fff4;
}
.produk-item-card {
    transition: all 0.3s;
}
.produk-item-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
}
</style>

<div class="page-breadcrumb">
    <div class="row">
        <div class="col-7 align-self-center">
            <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">OrderKuota</h4>
            <div class="d-flex align-items-center">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb m-0 p-0">
                        <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                        <li class="breadcrumb-item text-muted active" aria-current="page">OrderKuota</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid">
    <!-- Cek Saldo Section -->
    <div class="modern-card mb-4">
        <div class="modern-card-header">
            <h4><i class="fa fa-wallet"></i> Cek Saldo OrderKuota</h4>
        </div>
        <div class="modern-card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <p class="mb-0">Saldo saat ini di akun OrderKuota Anda</p>
                </div>
                <div class="col-md-4 text-right">
                    <a href="?cek_saldo=1" class="btn btn-info">
                        <i class="fa fa-refresh"></i> Cek Saldo
                    </a>
                </div>
            </div>
            <?php if ($balance_result): ?>
            <div class="mt-3">
                <?php if ($balance_result['success']): ?>
                <div class="alert alert-success">
                    <strong>Saldo Anda: Rp <?=number_format($balance_result['data']['balance'] ?? 0, 0, ',', '.')?></strong>
                </div>
                <?php else: ?>
                <div class="alert alert-danger">
                    <strong>Error:</strong> <?=htmlspecialchars($balance_result['message'] ?? 'Gagal mengambil saldo')?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Grid Kategori Produk -->
    <div class="modern-card mb-4">
        <div class="modern-card-header">
            <h4><i class="fa fa-tags"></i> Pilih Kategori Produk</h4>
        </div>
        <div class="modern-card-body">
            <div class="row" id="kategori-grid">
                <?php if (empty($all_kategori)): ?>
                <div class="col-12">
                    <div class="alert alert-warning text-center">
                        <i class="fa fa-exclamation-triangle"></i> Belum ada kategori produk. Silakan import produk terlebih dahulu.
                    </div>
                </div>
                <?php else: ?>
                <?php foreach ($all_kategori as $kategori): ?>
                <div class="col-md-4 col-sm-6 mb-3">
                    <div class="card kategori-card border-primary"
                         data-kategori="<?=htmlspecialchars($kategori['kategori'])?>"
                         style="cursor: pointer; user-select: none; position: relative; z-index: 100;"
                         role="button"
                         tabindex="0">
                        <div class="card-body text-center">
                            <div class="kategori-icon-wrapper mb-3">
                                <i class="fa fa-box fa-3x text-primary"></i>
                            </div>
                            <h6 class="mb-0"><?=htmlspecialchars($kategori['kategori'])?></h6>
                            <small class="text-muted"><?=$kategori['jumlah_produk']?> produk</small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Form Pembayaran -->
    <div class="modern-card mb-4">
        <div class="modern-card-header">
            <h4><i class="fa fa-credit-card"></i> Form Pembayaran</h4>
        </div>
        <div class="modern-card-body">
            <?php if ($payment_message): ?>
            <div class="alert alert-<?=$payment_result && $payment_result['success'] ? 'success' : 'danger'?>">
                <?=htmlspecialchars($payment_message)?>
            </div>
            <?php endif; ?>

            <form method="POST" action="" id="paymentForm">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Kode Produk <span class="text-danger">*</span></label>
                        <input type="text" name="product_code" id="product_code" class="form-control"
                               placeholder="Masukkan kode produk atau pilih dari daftar produk di atas"
                               value="<?=isset($_POST['product_code']) ? htmlspecialchars($_POST['product_code']) : ''?>" required>
                        <small class="form-text text-muted">Klik produk di atas untuk mengisi otomatis</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nomor Tujuan <span class="text-danger">*</span></label>
                        <input type="text" name="target" id="target" class="form-control"
                               placeholder="Nomor meteran/listrik (PLN) atau nomor HP (Pulsa/Data)"
                               value="<?=isset($_POST['target']) ? htmlspecialchars($_POST['target']) : ''?>" required>
                        <small class="form-text text-muted">Masukkan nomor tujuan sesuai jenis produk</small>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Harga</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Rp</span>
                            </div>
                            <input type="number" name="harga" id="harga" class="form-control"
                                   placeholder="0" min="0" step="1000"
                                   value="<?=isset($_POST['harga']) ? htmlspecialchars($_POST['harga']) : ''?>" required>
                        </div>
                        <small class="form-text text-muted">Klik "Cek Harga" untuk mendapatkan harga otomatis</small>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">&nbsp;</label>
                        <div>
                            <button type="submit" name="cek_harga" class="btn btn-info">
                                <i class="fa fa-search"></i> Cek Harga
                            </button>
                        </div>
                    </div>
                </div>

                <?php if ($price_result): ?>
                <div class="alert alert-<?=$price_result['success'] ? 'success' : 'danger'?>">
                    <?php if ($price_result['success']): ?>
                    <strong>Harga ditemukan!</strong><br>
                    Harga: <strong>Rp <?=number_format($price_result['data']['price'] ?? 0, 0, ',', '.')?></strong>
                    <?php if (isset($price_result['data']['product_name'])): ?>
                    <br>Produk: <?=htmlspecialchars($price_result['data']['product_name'])?>
                    <?php endif; ?>
                    <script>
                    // Auto fill harga
                    document.getElementById('harga').value = '<?=$price_result['data']['price'] ?? 0?>';
                    </script>
                    <?php else: ?>
                    <strong>Error:</strong> <?=htmlspecialchars($price_result['message'] ?? 'Gagal mengambil harga')?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" name="pay" class="btn btn-success btn-lg">
                            <i class="fa fa-credit-card"></i> Bayar Sekarang
                        </button>
                        <button type="reset" class="btn btn-secondary btn-lg ml-2">
                            <i class="fa fa-refresh"></i> Reset Form
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Riwayat Pembayaran -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="modern-card">
                <div class="modern-card-header">
                    <h4>
                        <i class="fa fa-filter"></i> Filter & Pencarian
                    </h4>
                </div>
                <div class="modern-card-body">
                    <form method="GET" action="" class="row" id="filterForm">
                        <div class="col-md-2">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="">Semua Status</option>
                                <option value="Lunas" <?=$filter_status == 'Lunas' ? 'selected' : ''?>>Lunas</option>
                                <option value="Belum" <?=$filter_status == 'Belum' ? 'selected' : ''?>>Belum Bayar</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control" value="<?=htmlspecialchars($filter_date_from)?>">
                        </div>
                        <div class="col-md-2">
                            <label>Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control" value="<?=htmlspecialchars($filter_date_to)?>">
                        </div>
                        <div class="col-md-3">
                            <label>Cari</label>
                            <div class="input-group">
                                <input type="text" name="search_history" class="form-control" placeholder="Nama/ID/Ref ID" value="<?=htmlspecialchars($search_history)?>">
                                <div class="input-group-append">
                                    <button type="submit" class="btn btn-info">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label style="visibility: hidden; margin-bottom: 0.5rem; display: block;">&nbsp;</label>
                            <div class="d-flex" style="gap: 0.5rem;">
                                <a href="<?=base_url('orderkuota/index.php')?>" class="btn btn-secondary" style="flex: 1; height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-refresh"></i> Reset
                                </a>
                                <a href="<?=base_url('orderkuota/history.php')?>" class="btn btn-primary" style="flex: 1; height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                                    <i class="fa fa-list"></i> Lihat Semua
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="modern-card">
                <div class="modern-card-header">
                    <h4>
                        <i class="fa fa-history"></i> Riwayat Pembayaran
                    </h4>
                </div>
                <div class="modern-card-body">
                    <!-- Tabel Riwayat -->
                    <div class="table-responsive">
                        <table class="table modern-table table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Produk</th>
                                    <th>Nomor Tujuan</th>
                                    <th>Harga</th>
                                    <th>Status</th>
                                    <th>Ref ID</th>
                                    <th style="text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($history_transaksi)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="alert alert-info">
                                            <i class="fa fa-info-circle"></i> Belum ada riwayat pembayaran OrderKuota
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($history_transaksi as $trans): ?>
                                <tr>
                                    <td><?=date('d/m/Y H:i', strtotime($trans['tgl']))?></td>
                                    <td><?=htmlspecialchars($trans['product_name'] ?: 'N/A')?></td>
                                    <td><?=htmlspecialchars($trans['idpel'])?></td>
                                    <td><strong>Rp <?=number_format(floatval($trans['harga']), 0, ',', '.')?></strong></td>
                                    <td>
                                        <?php if ($trans['status'] == 'Lunas'): ?>
                                        <span class="badge badge-pill badge-success">Lunas</span>
                                        <?php elseif ($trans['status'] == 'Pending'): ?>
                                        <span class="badge badge-pill badge-warning">Pending</span>
                                        <?php else: ?>
                                        <span class="badge badge-pill badge-danger"><?=htmlspecialchars($trans['status'])?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><small class="text-info"><?=htmlspecialchars($trans['ref_id'] ?: '-')?></small></td>
                                    <td align="center">
                                        <a href="<?=base_url('orderkuota/detail.php?id=' . $trans['id_transaksi'])?>"
                                           class="btn btn-sm btn-info" title="Detail" data-toggle="tooltip" data-placement="top">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Info Section -->
    <div class="modern-card">
        <div class="modern-card-header">
            <h4><i class="fa fa-info-circle"></i> Informasi</h4>
        </div>
        <div class="modern-card-body">
            <ul>
                <li>Pilih kategori produk terlebih dahulu, lalu pilih produk yang diinginkan</li>
                <li>Atau masukkan kode produk secara manual di form pembayaran</li>
                <li>Masukkan nomor tujuan sesuai jenis produk:
                    <ul>
                        <li><strong>PLN:</strong> Nomor meteran/listrik</li>
                        <li><strong>Pulsa/Data:</strong> Nomor HP (contoh: 081234567890)</li>
                    </ul>
                </li>
                <li>Klik <strong>"Cek Harga"</strong> untuk mendapatkan harga otomatis</li>
                <li>Setelah harga terisi, klik <strong>"Bayar Sekarang"</strong> untuk melakukan pembayaran</li>
                <li>Pastikan saldo OrderKuota Anda mencukupi sebelum melakukan pembayaran</li>
                <li>Riwayat pembayaran dapat dilihat di bagian bawah atau melalui menu History</li>
            </ul>
        </div>
    </div>
</div>

<!-- Modal Produk -->
<div class="modal fade" id="modalProduk" tabindex="-1" role="dialog" aria-labelledby="modalProdukLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h4 class="modal-title" id="modalProdukLabel">
                    <i class="fa fa-box"></i> Pilih Produk
                    <span id="modalKategoriTitle" class="ml-2"></span>
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                <div id="produk-list-modal" class="row"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fa fa-times"></i> Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Detail Produk -->
<div class="modal fade" id="modalProdukDetail" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fa fa-info-circle"></i> Detail Produk
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="produkDetailContent">
                <div class="text-center">
                    Memuat data...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Search functionality
document.addEventListener('DOMContentLoaded', function() {

    // Handle selection kategori
    const kategoriCards = document.querySelectorAll('.kategori-card');
    kategoriCards.forEach(function(card) {
        card.addEventListener('click', function() {
            // Remove selected class from all cards
            kategoriCards.forEach(function(c) {
                c.classList.remove('selected');
            });

            // Add selected class to clicked card
            this.classList.add('selected');

            // Get kategori
            const selectedKategori = this.getAttribute('data-kategori');

            // Load produk untuk kategori yang dipilih
            loadProdukByKategori(selectedKategori);

            // Buka modal produk
            $('#modalProduk').modal('show');
        });
    });
});

// Fungsi untuk load produk berdasarkan kategori
function loadProdukByKategori(kategori) {
    const produkListDiv = document.getElementById('produk-list-modal');
    const modalKategoriTitle = document.getElementById('modalKategoriTitle');

    // Set title modal
    if (modalKategoriTitle) {
        modalKategoriTitle.textContent = '- ' + kategori;
    }

    // Show loading
    produkListDiv.innerHTML = '<div class="col-12"><div class="text-center p-3">Memuat produk...</div></div>';

    // Load produk via AJAX
    fetch('<?=base_url('orderkuota/get_produk_by_kategori.php')?>?kategori=' + encodeURIComponent(kategori))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.produk.length > 0) {
                let html = '';
                data.produk.forEach(function(produk) {
                    const escapedKode = produk.kode.replace(/'/g, "\\'");
                    html += `
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="card produk-item-card border"
                                 onclick="selectProdukOrderkuota(${produk.id_produk}, '${escapedKode}', ${produk.harga}, ${produk.id_bayar || 'null'}, this)"
                                 style="cursor: pointer;">
                                <div class="card-body p-2">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <small class="badge badge-info">${produk.kode}</small>
                                        <strong class="text-success">Rp ${parseInt(produk.harga).toLocaleString('id-ID')}</strong>
                                    </div>
                                    <small class="d-block text-truncate mt-1" title="${produk.produk}">
                                        ${produk.produk}
                                    </small>
                                    <small class="d-block text-muted mt-1">
                                        <i class="fa fa-tag"></i> ${produk.kategori}
                                    </small>
                                </div>
                            </div>
                        </div>
                    `;
                });
                produkListDiv.innerHTML = html;
            } else {
                produkListDiv.innerHTML = '<div class="col-12"><div class="alert alert-info text-center">Tidak ada produk dalam kategori ini.</div></div>';
            }
        })
        .catch(error => {
            console.error('Error loading produk:', error);
            produkListDiv.innerHTML = '<div class="col-12"><div class="alert alert-danger text-center">Gagal memuat produk.</div></div>';
        });
}

// Fungsi untuk select produk dari modal
window.selectProdukOrderkuota = function(id_produk, kode, harga, id_bayar, cardElement) {
    // Set form values
    const productCodeInput = document.getElementById('product_code');
    const hargaInput = document.getElementById('harga');

    if (productCodeInput) {
        productCodeInput.value = kode;
    }

    if (hargaInput) {
        hargaInput.value = harga;
    }

    // Close modal
    $('#modalProduk').modal('hide');

    // Scroll to form
    const paymentForm = document.getElementById('paymentForm');
    if (paymentForm) {
        paymentForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // Focus on target input
    setTimeout(function() {
        const targetInput = document.getElementById('target');
        if (targetInput) {
            targetInput.focus();
        }
    }, 500);
};


// View produk detail
function viewProdukDetail(id_produk) {
    const contentDiv = document.getElementById('produkDetailContent');
    contentDiv.innerHTML = '<div class="text-center">Memuat data...</div>';

    fetch('<?=base_url('orderkuota/get_detail_produk.php')?>?id=' + id_produk)
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
                                    <th width="40%">Nama Produk</th>
                                    <td>${prod.produk || '-'}</td>
                                </tr>
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
                        <button class="btn btn-success" onclick="selectProductFromModal('${prod.kode}', ${prod.harga}); $('#modalProdukDetail').modal('hide');">
                            <i class="fa fa-check"></i> Pilih Produk Ini
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

    $('#modalProdukDetail').modal('show');
}

// Select product from modal
function selectProductFromModal(kode, harga) {
    document.getElementById('product_code').value = kode;
    document.getElementById('harga').value = harga;

    // Scroll ke form
    document.getElementById('paymentForm').scrollIntoView({ behavior: 'smooth', block: 'nearest' });

    // Optional: Highlight the selected product card
    document.querySelectorAll('.produk-item-card').forEach(function(card) {
        if (card.getAttribute('data-kode') === kode) {
            card.classList.add('selected');
        } else {
            card.classList.remove('selected');
        }
    });
}
</script>

<?php
include_once('../footer.php');
?>


