<?php
include_once('../header.php');
include_once('../config/config.php');
require_once '../libs/orderkuota_api.php';
require_once '../libs/log_activity.php';
require_once '../libs/produk_helper.php';

// Handle cek status
$status_result = null;
if (isset($_GET['cek_status']) && isset($_GET['ref_id'])) {
    $api = new OrderKuotaAPI();
    $status_result = $api->checkStatus($_GET['ref_id']);
    $status_result = $api->formatResponse($status_result);
}

// Handle retry payment
if (isset($_GET['retry']) && isset($_GET['ref_id'])) {
    // Get transaksi data
    $ref_id = $_GET['ref_id'];
    $transaksi_query = $koneksi->query("SELECT * FROM transaksi WHERE ket LIKE '%Ref: $ref_id%' LIMIT 1");

    if ($transaksi_query && $transaksi_query->num_rows > 0) {
        $transaksi = $transaksi_query->fetch_assoc();

        // Extract product code dari keterangan atau gunakan default
        preg_match('/OrderKuota: ([^-]+)/', $transaksi['ket'], $matches);
        $product_name = $matches[1] ?? '';

        // Retry payment
        $retry_result = pay_via_orderkuota('PLN', $transaksi['idpel'], $ref_id . '_RETRY');

        if ($retry_result['success']) {
            header('Location: ' . base_url('orderkuota/history.php?retry_success=1&ref_id=' . $ref_id));
            exit;
        } else {
            $error_message = $retry_result['message'];
        }
    }
}

// Get filter
$filter_status = $_GET['status'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = ["ket LIKE '%OrderKuota%'"];

if ($filter_status) {
    $where_conditions[] = "status = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'";
}

if ($filter_date_from) {
    $where_conditions[] = "tgl >= '" . mysqli_real_escape_string($koneksi, $filter_date_from) . "'";
}

if ($filter_date_to) {
    $where_conditions[] = "tgl <= '" . mysqli_real_escape_string($koneksi, $filter_date_to) . "'";
}

if ($search) {
    $search_escaped = mysqli_real_escape_string($koneksi, $search);
    $where_conditions[] = "(nama LIKE '%$search_escaped%' OR idpel LIKE '%$search_escaped%' OR ket LIKE '%$search_escaped%')";
}

$where_clause = implode(' AND ', $where_conditions);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Get total count untuk pagination
$count_query = $koneksi->query("SELECT COUNT(*) as total FROM transaksi WHERE $where_clause");
$total_count = $count_query ? $count_query->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_count / $per_page);

// Get history transaksi dengan pagination - join dengan produk
$history_transaksi = [];
// Cek apakah tabel produk ada
$produk_table_exists = false;
$check_produk_table = $koneksi->query("SHOW TABLES LIKE 'tb_produk_orderkuota'");
if ($check_produk_table && $check_produk_table->num_rows > 0) {
    $produk_table_exists = true;
}

if ($produk_table_exists) {
    // Cek apakah kolom selected_produk_id ada
    $check_column = $koneksi->query("SHOW COLUMNS FROM transaksi LIKE 'selected_produk_id'");
    $has_selected_produk_id = ($check_column && $check_column->num_rows > 0);

    if ($has_selected_produk_id) {
        // Query dengan selected_produk_id
        $history_query = $koneksi->query("SELECT transaksi.*,
                                               COALESCE(
                                                   (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_produk = transaksi.selected_produk_id AND p.status = 1 LIMIT 1),
                                                   (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = transaksi.id_bayar AND CAST(p.harga AS UNSIGNED) = CAST(transaksi.harga AS UNSIGNED) AND p.status = 1 LIMIT 1),
                                                   (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = transaksi.id_bayar AND p.status = 1 LIMIT 1),
                                                   '-'
                                               ) as produk_nama
                                        FROM transaksi
                                        WHERE $where_clause
                                        ORDER BY tgl DESC, id_transaksi DESC
                                        LIMIT $per_page OFFSET $offset");
    } else {
        // Query tanpa selected_produk_id
        $history_query = $koneksi->query("SELECT transaksi.*,
                                               COALESCE(
                                                   (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = transaksi.id_bayar AND CAST(p.harga AS UNSIGNED) = CAST(transaksi.harga AS UNSIGNED) AND p.status = 1 LIMIT 1),
                                                   (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = transaksi.id_bayar AND p.status = 1 LIMIT 1),
                                                   '-'
                                               ) as produk_nama
                                        FROM transaksi
                                        WHERE $where_clause
                                        ORDER BY tgl DESC, id_transaksi DESC
                                        LIMIT $per_page OFFSET $offset");
    }
} else {
    // Fallback jika tabel produk tidak ada
    $history_query = $koneksi->query("SELECT * FROM transaksi WHERE $where_clause ORDER BY tgl DESC, id_transaksi DESC LIMIT $per_page OFFSET $offset");
}

if ($history_query) {
    while ($row = $history_query->fetch_assoc()) {
        // Extract ref_id dari keterangan
        preg_match('/Ref: ([A-Z0-9_]+)/', $row['ket'], $matches);
        $row['ref_id'] = $matches[1] ?? '';

        // Gunakan produk_nama dari query jika ada, jika tidak extract dari keterangan
        if (!empty($row['produk_nama']) && $row['produk_nama'] != '-') {
            $row['product_name'] = $row['produk_nama'];
        } else {
            preg_match('/OrderKuota: ([^-]+)/', $row['ket'], $product_matches);
            $row['product_name'] = trim($product_matches[1] ?? '');
        }

        // Extract token jika ada (untuk PLN prabayar)
        if (preg_match('/Token:\s*([0-9]+)/i', $row['ket'], $token_matches)) {
            $row['token'] = $token_matches[1];
        } else {
            $row['token'] = '';
        }

        $history_transaksi[] = $row;
    }
}

// Statistik Total
$total_query = $koneksi->query("SELECT COUNT(*) as total, SUM(CAST(harga AS UNSIGNED)) as nominal FROM transaksi WHERE ket LIKE '%OrderKuota%'");
$total_stats = $total_query->fetch_assoc();

// Statistik Filtered (berdasarkan filter yang dipilih)
$filtered_query = $koneksi->query("SELECT COUNT(*) as total, SUM(CAST(harga AS UNSIGNED)) as nominal FROM transaksi WHERE $where_clause");
$filtered_stats = $filtered_query->fetch_assoc();

// Statistik Hari Ini
$today_query = $koneksi->query("SELECT COUNT(*) as total, SUM(CAST(harga AS UNSIGNED)) as nominal FROM transaksi WHERE ket LIKE '%OrderKuota%' AND tgl = CURDATE()");
$today_stats = $today_query->fetch_assoc();

// Statistik Bulan Ini
$month_query = $koneksi->query("SELECT COUNT(*) as total, SUM(CAST(harga AS UNSIGNED)) as nominal FROM transaksi WHERE ket LIKE '%OrderKuota%' AND MONTH(tgl) = MONTH(CURDATE()) AND YEAR(tgl) = YEAR(CURDATE())");
$month_stats = $month_query->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<style>
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }
        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }
        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }
        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }
        .text-xs {
            font-size: 0.7rem;
        }
        .text-gray-300 {
            color: #dddfeb !important;
        }
        .text-gray-800 {
            color: #5a5c69 !important;
        }
        .shadow {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        }
    </style>
</head>
<body>
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">History Transaksi OrderKuota</h4>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                            <li class="breadcrumb-item"><a href="<?=base_url('orderkuota')?>" class="text-muted">OrderKuota</a></li>
                            <li class="breadcrumb-item text-muted active" aria-current="page">History</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Success Message -->
        <?php if (isset($_GET['retry_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i>
            <strong>Retry Berhasil!</strong>
            Transaksi telah di-retry.
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Status Check Result -->
        <?php if ($status_result): ?>
        <div class="alert alert-<?=$status_result['success'] ? 'success' : 'warning'?> alert-dismissible fade show" role="alert">
            <i class="fa fa-<?=$status_result['success'] ? 'check-circle' : 'exclamation-triangle'?>"></i>
            <strong>Status Transaksi:</strong>
            <br><?=htmlspecialchars($status_result['message'] ?? 'Unknown status')?>
            <?php if (isset($status_result['data']['status'])): ?>
                <br>Status: <strong><?=htmlspecialchars($status_result['data']['status'])?></strong>
            <?php endif; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fa fa-list" style="color: white !important; font-size: 28px !important;"></i>
                    </div>
                    <div class="stat-value"><?=$total_stats['total'] ?? 0?></div>
                    <div class="stat-label">Total Transaksi</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #1cc88a 0%, #17a673 100%);">
                        <i class="fa fa-dollar-sign" style="color: white !important; font-size: 28px !important;"></i>
                    </div>
                    <div class="stat-value">Rp <?=number_format($total_stats['nominal'] ?? 0, 0, ',', '.')?></div>
                    <div class="stat-label">Total Nominal</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #36b9cc 0%, #2c9faf 100%);">
                        <i class="fa fa-calendar-check" style="color: white !important; font-size: 28px !important;"></i>
                    </div>
                    <div class="stat-value"><?=$today_stats['total'] ?? 0?></div>
                    <div class="stat-label">Hari Ini</div>
                    <small class="text-success">Rp <?=number_format($today_stats['nominal'] ?? 0, 0, ',', '.')?></small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);">
                        <i class="fa fa-calendar-alt" style="color: white !important; font-size: 28px !important;"></i>
                    </div>
                    <div class="stat-value"><?=$month_stats['total'] ?? 0?></div>
                    <div class="stat-label">Bulan Ini</div>
                    <small class="text-success">Rp <?=number_format($month_stats['nominal'] ?? 0, 0, ',', '.')?></small>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="row mb-3">
            <div class="col-12">
                <div class="modern-card">
                    <div class="modern-card-header">
                        <h4>
                            <i class="fa fa-filter"></i> Filter & Pencarian
                        </h4>
                    </div>
                    <div class="modern-card-body">
                        <form method="GET" class="row" id="filterForm">
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
                                <input type="date" name="date_from" class="form-control" value="<?=$filter_date_from?>">
                            </div>
                            <div class="col-md-2">
                                <label>Sampai Tanggal</label>
                                <input type="date" name="date_to" class="form-control" value="<?=$filter_date_to?>">
                            </div>
                            <div class="col-md-3">
                                <label>Cari</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Nama/ID/Ref ID" value="<?=htmlspecialchars($search)?>">
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
                                    <a href="<?=base_url('orderkuota/history.php')?>" class="btn btn-secondary" style="flex: 1; height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="fa fa-refresh"></i> Reset
                                    </a>
                                    <?php if (isset($_SESSION['level']) && $_SESSION['level'] == 'admin'): ?>
                                    <a href="<?=base_url('export_excel.php?page=orderkuota_history&' . http_build_query($_GET))?>" class="btn btn-success" style="flex: 1; height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="fa fa-file-excel"></i> Excel
                                    </a>
                                    <a href="<?=base_url('export_pdf.php?page=orderkuota_history&' . http_build_query($_GET))?>" target="_blank" class="btn btn-danger" style="flex: 1; height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                                        <i class="fa fa-file-pdf"></i> PDF
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>

                        <!-- Statistik Filtered -->
                        <?php if ($filter_date_from || $filter_date_to || $filter_status || $search): ?>
                        <div class="alert alert-info mt-3 mb-0">
                            <i class="fa fa-filter"></i> <strong>Hasil Filter:</strong>
                            Menampilkan <strong><?=$filtered_stats['total'] ?? 0?></strong> transaksi
                            dengan total nominal <strong>Rp <?=number_format($filtered_stats['nominal'] ?? 0, 0, ',', '.')?></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel History -->
        <div class="row">
            <div class="col-12">
                <div class="modern-card">
                    <div class="modern-card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4>
                                <i class="fa fa-history"></i> Daftar Transaksi
                                <span class="badge badge-light ml-2"><?=$total_count?> Total</span>
                                <span class="badge badge-light ml-2">Halaman <?=$page?> dari <?=$total_pages?></span>
                            </h4>
                            <div>
                                <a href="<?=base_url('export_excel.php?page=orderkuota_history&' . http_build_query($_GET))?>"
                                   class="btn btn-sm btn-success mr-2">
                                    <i class="fa fa-file-excel"></i> Excel
                                </a>
                        <a href="<?=base_url('export_pdf.php?page=orderkuota_history&' . http_build_query($_GET))?>"
                           target="_blank"
                           class="btn btn-sm btn-danger mr-2">
                            <i class="fa fa-file-pdf"></i> PDF
                        </a>
                        <a href="<?=base_url('orderkuota/print_receipt.php?ref_id=' . urlencode($history_transaksi[0]['ref_id'] ?? ''))?>"
                           target="_blank"
                           class="btn btn-sm btn-secondary"
                           title="Print Struk Terakhir">
                            <i class="fa fa-print"></i> Print
                        </a>
                    </div>
                </div>
            </div>
            <div class="modern-card-body">

                <?php if (empty($history_transaksi)): ?>
                <div class="alert alert-info text-center">
                    <i class="fa fa-info-circle"></i> Belum ada transaksi OrderKuota
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table modern-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Produk</th>
                                <th>Nama / ID Pelanggan</th>
                                <th>Harga</th>
                                <th>Status</th>
                                <th>Reference ID</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($history_transaksi as $hist): ?>
                            <tr>
                                <td><?=$no++?></td>
                                <td><?=date('d/m/Y H:i', strtotime($hist['tgl']))?></td>
                                <td>
                                    <small><?=htmlspecialchars($hist['product_name'] ?: 'N/A')?></small>
                                </td>
                                <td>
                                    <strong><?=htmlspecialchars($hist['nama'] ?: '-')?></strong>
                                    <br><small class="text-muted"><?=htmlspecialchars($hist['idpel'])?></small>
                                </td>
                                <td>
                                    <strong>Rp <?=number_format($hist['harga'], 0, ',', '.')?></strong>
                                </td>
                                <td>
                                    <span class="badge badge-<?=$hist['status'] == 'Lunas' ? 'success' : 'warning'?>">
                                        <?=$hist['status']?>
                                    </span>
                                </td>
                                <td>
                                    <small class="text-info"><?=htmlspecialchars($hist['ref_id'] ?: 'N/A')?></small>
                                    <?php if (!empty($hist['token'])): ?>
                                    <br><small class="text-warning">
                                        <i class="fa fa-key"></i> Token: <strong><?=htmlspecialchars($hist['token'])?></strong>
                                    </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <a href="<?=base_url('orderkuota/detail.php?id=' . $hist['id_transaksi'])?>"
                                           class="btn btn-sm btn-primary" title="Detail">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                        <a href="<?=base_url('orderkuota/print_receipt.php?id=' . $hist['id_transaksi'])?>"
                                           target="_blank"
                                           class="btn btn-sm btn-secondary" title="Print Struk">
                                            <i class="fa fa-print"></i>
                                        </a>
                                        <?php if ($hist['ref_id']): ?>
                                        <a href="?cek_status=1&ref_id=<?=urlencode($hist['ref_id'])?>"
                                           class="btn btn-sm btn-info" title="Cek Status">
                                            <i class="fa fa-search"></i>
                                        </a>
                                        <?php if ($hist['status'] == 'Belum'): ?>
                                        <a href="?retry=1&ref_id=<?=urlencode($hist['ref_id'])?>"
                                           class="btn btn-sm btn-warning"
                                           onclick="return swalConfirmRetry()"
                                           title="Retry">
                                            <i class="fa fa-refresh"></i>
                                        </a>
                                        <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page' => $page - 1]))?>">
                                <i class="fa fa-chevron-left"></i> Sebelumnya
                            </a>
                        </li>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $page + 2);
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                        <li class="page-item <?=$i == $page ? 'active' : ''?>">
                            <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page' => $i]))?>">
                                <?=$i?>
                            </a>
                        </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page' => $page + 1]))?>">
                                Selanjutnya <i class="fa fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
                <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function swalConfirmRetry() {
        Swal.fire({
            title: 'Yakin Retry Payment?',
            text: 'Transaksi ini akan di-retry melalui OrderKuota',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ffc107',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Retry!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                return true;
            }
            return false;
        });
        return false;
    }
    </script>

    <?php
    include_once('../footer.php');
    ?>





