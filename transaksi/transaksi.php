<?php
$page_title = 'Transaksi';
include_once('../header.php');
include_once('../config/config.php');
require_once '../libs/saldo_helper.php';

// Get filter parameters
$filter_status = $_GET['status'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_produk = $_GET['produk'] ?? '';
$search = $_GET['search'] ?? '';

// Build query conditions
$where_conditions = ["1=1"]; // Base condition

if ($filter_status) {
    $where_conditions[] = "transaksi.status = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'";
}

if ($filter_date_from) {
    $where_conditions[] = "transaksi.tgl >= '" . mysqli_real_escape_string($koneksi, $filter_date_from) . "'";
}

if ($filter_date_to) {
    $where_conditions[] = "transaksi.tgl <= '" . mysqli_real_escape_string($koneksi, $filter_date_to) . "'";
}

if ($filter_produk) {
    $filter_produk_escaped = mysqli_real_escape_string($koneksi, $filter_produk);
    $where_conditions[] = "transaksi.produk = '$filter_produk_escaped'";
}

if ($search) {
    $search_escaped = mysqli_real_escape_string($koneksi, $search);
    $where_conditions[] = "(transaksi.nama LIKE '%$search_escaped%' OR transaksi.idpel LIKE '%$search_escaped%' OR transaksi.ket LIKE '%$search_escaped%')";
}

$where_clause = implode(' AND ', $where_conditions);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 25; // Default 25 entries
if (!in_array($per_page, [10, 25, 50, 100])) {
    $per_page = 25; // Fallback to 25 if invalid
}
$offset = ($page - 1) * $per_page;

// Get total count
$count_query = $koneksi->query("SELECT COUNT(*) as total FROM transaksi WHERE $where_clause");
$total_count = $count_query ? $count_query->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_count / $per_page);

// Get transaksi data with pagination
// Menggunakan produk jika ada, fallback ke jenis_bayar untuk kompatibilitas
$sql = $koneksi->query("SELECT transaksi.*, tb_jenisbayar.jenis_bayar,
                               COALESCE(transaksi.produk, tb_jenisbayar.jenis_bayar) as produk_display
                        FROM transaksi
                        JOIN tb_jenisbayar ON transaksi.id_bayar = tb_jenisbayar.id_bayar
                        WHERE $where_clause
                        ORDER BY transaksi.tgl DESC, transaksi.id_transaksi DESC
                        LIMIT $per_page OFFSET $offset");

// Get produk untuk filter dropdown (ambil dari tabel tb_produk_orderkuota)
$produk_list = [];
$produk_query = $koneksi->query("SELECT DISTINCT produk FROM tb_produk_orderkuota WHERE produk IS NOT NULL AND produk != '' AND status = 1 ORDER BY produk ASC");
if ($produk_query) {
    while ($row = $produk_query->fetch_assoc()) {
        if (!empty($row['produk'])) {
            $produk_list[] = $row['produk'];
        }
    }
}

// Jika tidak ada dari tabel produk, ambil dari transaksi sebagai fallback
if (empty($produk_list)) {
    $produk_query_fallback = $koneksi->query("SELECT DISTINCT produk FROM transaksi WHERE produk IS NOT NULL AND produk != '' ORDER BY produk ASC");
    if ($produk_query_fallback) {
        while ($row = $produk_query_fallback->fetch_assoc()) {
            if (!empty($row['produk'])) {
                $produk_list[] = $row['produk'];
            }
        }
    }
}

// Statistik
$total_query = $koneksi->query("SELECT COUNT(*) as total, SUM(CAST(harga AS UNSIGNED)) as nominal FROM transaksi");
$total_stats = $total_query->fetch_assoc();

// Statistik filtered
$filtered_query = $koneksi->query("SELECT COUNT(*) as total, SUM(CAST(harga AS UNSIGNED)) as nominal FROM transaksi WHERE $where_clause");
$filtered_stats = $filtered_query->fetch_assoc();

// Statistik hari ini
$today_query = $koneksi->query("SELECT COUNT(*) as total, SUM(CAST(harga AS UNSIGNED)) as nominal FROM transaksi WHERE tgl = CURDATE()");
$today_stats = $today_query->fetch_assoc();

// Statistik bulan ini
$month_query = $koneksi->query("SELECT COUNT(*) as total, SUM(CAST(harga AS UNSIGNED)) as nominal FROM transaksi WHERE MONTH(tgl) = MONTH(CURDATE()) AND YEAR(tgl) = YEAR(CURDATE())");
$month_stats = $month_query->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi</title>
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
        /* Hapus animasi fadeIn yang mungkin menyebabkan masalah */
        .btn-xs {
            padding: 0.2rem 0.4rem;
            font-size: 0.75rem;
            line-height: 1.2;
            border-radius: 0.2rem;
        }
        .copy-feedback {
            white-space: nowrap;
            transition: opacity 0.3s ease-in;
        }
        /* Pastikan tidak ada pseudo-element yang menyebabkan masalah */
        .btn-xs::before,
        .btn-xs::after,
        .copy-feedback::before,
        .copy-feedback::after {
            display: none !important;
            content: none !important;
        }
        /* Pastikan tidak ada background gradient aneh */
        .btn-success {
            background: #28a745 !important;
            border-color: #28a745 !important;
            box-shadow: none !important;
        }
        /* Pastikan tidak ada animasi aneh */
        .btn-xs,
        .copy-feedback {
            animation: none !important;
        }
        /* Pastikan tidak ada transform aneh */
        .btn-xs:active,
        .btn-xs:focus {
            transform: none !important;
            box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
        }
        /* Style untuk widget saldo yang lebih compact */
        .stat-card-modern {
            padding: 0.75rem !important;
        }
        .stat-card-modern .stat-icon {
            width: 40px !important;
            height: 40px !important;
            min-width: 40px !important;
        }
        .stat-card-modern .stat-value {
            font-size: 1.2rem !important;
            line-height: 1.2 !important;
            margin-top: 0.3rem !important;
        }
        .stat-card-modern .stat-label {
            font-size: 0.75rem !important;
            margin-top: 0.2rem !important;
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
                            <li class="breadcrumb-item text-muted active" aria-current="page">Transaksi</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <!-- Statistik -->
        <div class="row mb-4">
            <!-- Widget Saldo - Extra Compact version -->
            <?php
            $total_saldo = get_total_saldo($koneksi);
            $saldo_color = $total_saldo < 0 ? '#dc3545' : ($total_saldo < 100000 ? '#ffc107' : '#28a745');
            $saldo_status = $total_saldo < 0 ? 'Saldo Negatif!' : ($total_saldo < 100000 ? 'Saldo Rendah' : 'Saldo Aktif');
            ?>
            <div class="col-md-3">
                <div class="stat-card-modern" style="border-left: 2px solid <?=$saldo_color?>; padding: 0.75rem !important;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, <?=$saldo_color?> 0%, <?=$saldo_color?>dd 100%); width: 40px; height: 40px; min-width: 40px;">
                        <i class="fa fa-wallet" style="color: white !important; font-size: 18px !important;"></i>
                    </div>
                    <div class="stat-value" style="color: <?=$saldo_color?>; font-weight: 600; font-size: 1.2rem; line-height: 1.2; margin-top: 0.3rem;">
                        Rp <?=number_format($total_saldo, 0, ',', '.')?>
                    </div>
                    <div class="stat-label" style="font-size: 0.75rem; margin-top: 0.2rem;">Saldo Tersedia</div>
                    <small class="<?=$total_saldo < 0 ? 'text-danger' : ($total_saldo < 100000 ? 'text-warning' : 'text-success')?>" style="font-weight: 500; font-size: 0.7rem;">
                        <i class="fa fa-<?=$total_saldo < 0 ? 'exclamation-triangle' : ($total_saldo < 100000 ? 'exclamation-circle' : 'check-circle')?>"></i> <?=$saldo_status?>
                    </small>
                </div>
            </div>
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
                    <div class="stat-label">Transaksi Hari Ini</div>
                    <small class="text-success" style="font-weight: 600;">
                        <i class="fa fa-dollar-sign"></i> Rp <?=number_format($today_stats['nominal'] ?? 0, 0, ',', '.')?>
                    </small>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);">
                        <i class="fa fa-calendar-alt" style="color: white !important; font-size: 28px !important;"></i>
                    </div>
                    <div class="stat-value"><?=$month_stats['total'] ?? 0?></div>
                    <div class="stat-label">Transaksi Bulan Ini</div>
                    <small class="text-success" style="font-weight: 600;">
                        <i class="fa fa-dollar-sign"></i> Rp <?=number_format($month_stats['nominal'] ?? 0, 0, ',', '.')?>
                    </small>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="filter-box-modern">
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
                        <label>Produk</label>
                        <select name="produk" class="form-control">
                            <option value="">Semua Produk</option>
                            <?php foreach ($produk_list as $produk): ?>
                            <option value="<?=htmlspecialchars($produk, ENT_QUOTES)?>" <?=$filter_produk == $produk ? 'selected' : ''?>>
                                <?=htmlspecialchars($produk)?>
                            </option>
                            <?php endforeach; ?>
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
                    <div class="col-md-2">
                        <label>Cari</label>
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Nama/ID/Keterangan" value="<?=htmlspecialchars($search)?>">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-info">
                                    <i class="fa fa-search"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <label style="visibility: hidden; margin-bottom: 0.5rem; display: block;">&nbsp;</label>
                        <div class="d-flex" style="gap: 0.5rem;">
                            <a href="<?=base_url('transaksi/transaksi.php')?>" class="btn btn-secondary" style="flex: 1; height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fa fa-refresh"></i> Reset
                            </a>
                            <a href="<?=base_url('transaksi/tambah.php')?>" class="btn btn-primary" style="flex: 1; height: 38px; display: inline-flex; align-items: center; justify-content: center;">
                                <i class="fa fa-plus"></i> Tambah
                            </a>
                        </div>
                    </div>
                    <!-- Hidden input to preserve per_page when form is submitted -->
                    <input type="hidden" name="per_page" value="<?=$per_page?>">
                </form>
        </div>

        <!-- Statistik Filtered -->
        <?php if ($filter_date_from || $filter_date_to || $filter_status || $filter_produk || $search): ?>
        <div class="alert alert-info">
            <i class="fa fa-filter"></i> <strong>Hasil Filter:</strong>
            Menampilkan <strong><?=$filtered_stats['total'] ?? 0?></strong> transaksi
            dengan total nominal <strong>Rp <?=number_format($filtered_stats['nominal'] ?? 0, 0, ',', '.')?></strong>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="modern-card">
                    <div class="modern-card-header">
                        <h4>
                            <i class="fa fa-list"></i> Data Transaksi
                            <span class="badge badge-light ml-2"><?=$total_count?> Total</span>
                            <span class="badge badge-light ml-2">Halaman <?=$page?> dari <?=$total_pages?></span>
                        </h4>
                    </div>
                    <div class="modern-card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                            <div class="mb-2 mb-md-0">
                            <div class="d-flex align-items-center">
                                <div class="d-flex align-items-center mr-3">
                                    <label class="mb-0 mr-2" style="font-size: 0.9rem; font-weight: 500;">Show:</label>
                                    <select class="form-control form-control-sm" style="width: 70px;" onchange="updatePerPage(this.value)">
                                        <option value="10" <?=$per_page == 10 ? 'selected' : ''?>>10</option>
                                        <option value="25" <?=$per_page == 25 ? 'selected' : ''?>>25</option>
                                        <option value="50" <?=$per_page == 50 ? 'selected' : ''?>>50</option>
                                        <option value="100" <?=$per_page == 100 ? 'selected' : ''?>>100</option>
                                    </select>
                                </div>
                                <a href="<?=base_url('laporan/rekap_transaksi.php')?>" target="_blank" class="btn btn-sm btn-secondary">
                                    <i class="fa fa-print"></i> Cetak
                                </a>
                                <a href="<?=base_url('export_excel.php?page=transaksi&status=' . urlencode($filter_status) . '&date_from=' . urlencode($filter_date_from) . '&date_to=' . urlencode($filter_date_to) . '&produk=' . urlencode($filter_produk) . '&search=' . urlencode($search))?>" class="btn btn-sm btn-success ml-2 mb-2">
                                    <i class="fa fa-file-excel"></i> Excel
                                </a>
                                <a href="<?=base_url('export_pdf.php?page=transaksi&status=' . urlencode($filter_status) . '&date_from=' . urlencode($filter_date_from) . '&date_to=' . urlencode($filter_date_to) . '&produk=' . urlencode($filter_produk) . '&search=' . urlencode($search))?>" target="_blank" class="btn btn-sm btn-danger ml-2 mb-2">
                                    <i class="fa fa-file-pdf"></i> PDF
                                </a>
                                <button type="button" class="btn btn-sm btn-danger ml-2 mb-2" id="btnHapusMultiple" disabled onclick="hapusMultiple()">
                                    <i class="fa fa-trash"></i> Hapus Terpilih
                                </button>
                            </div>
                        </div>
                        <div class="mb-2">
                            <small class="text-muted">Menampilkan: <strong><?=$total_count?></strong> transaksi</small>
                            <small class="text-muted ml-2" id="selectedCount">| Terpilih: <strong>0</strong></small>
                        </div>
                        <div class="table-responsive mt-3">
                            <table class="table modern-table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 30px;">
                                            <input type="checkbox" id="selectAll" title="Pilih Semua">
                                        </th>
                                        <th style="width: 5px;">No</th>
                                        <th>Tanggal</th>
                                        <th>No. ID/PEL</th>
                                        <th>Nama Pelanggan</th>
                                        <th>Produk</th>
                                        <th>Harga</th>
                                        <th>Status</th>
                                        <th style="width: 200px;">Keterangan</th>
                                        <th style="text-align: center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($sql && $sql->num_rows > 0):
                                        $no = ($page - 1) * $per_page + 1;
                                        while($data = $sql->fetch_assoc()):
                                            $tgl = $data['tgl'];
                                            $status = ($data['status'] == 'Lunas')? "<span class='badge badge-pill badge-success'>Lunas</span>" : "<span class='badge badge-pill badge-danger'>Belum Bayar</span>";
                                    ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="checkbox-transaksi" value="<?=$data['id_transaksi']?>" data-nama="<?=htmlspecialchars($data['nama'], ENT_QUOTES)?>">
                                        </td>
                                        <td><?=$no++;?></td>
                                        <td><?=date('d/m/Y', strtotime($tgl));?></td>
                                        <td>
                                            <code><?=$data['idpel'];?></code>
                                        </td>
                                        <td><?=htmlspecialchars($data['nama']);?></td>
                                        <td><?=htmlspecialchars($data['produk_display'] ?? $data['produk'] ?? $data['jenis_bayar'] ?? '-');?></td>
                                        <td><strong>Rp <?=number_format($data['harga'], 0, ",", ".");?></strong></td>
                                        <td><?=$status?></td>
                                        <td><small><?=htmlspecialchars(mb_substr($data['ket'], 0, 50)) . (mb_strlen($data['ket']) > 50 ? '...' : '');?></small></td>
                                        <td align="center">
                                            <div class="btn-group" role="group">
                                                <a href="detail_transaksi.php?id=<?=$data['id_transaksi'];?>" class="btn btn-sm btn-info" data-toggle="tooltip" data-placement="top" title="Detail Transaksi">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="<?=base_url('transaksi/edit.php?id=' . $data['id_transaksi'])?>" class="btn btn-sm btn-warning" data-toggle="tooltip" data-placement="top" title="Edit Transaksi">
                                                    <i class="fa fa-edit"></i>
                                                </a>
                                                <a href="hapus.php?id=<?=$data['id_transaksi']; ?>" onclick="return swalConfirmDelete(this.href, 'Yakin Hapus Transaksi?', 'Transaksi ini akan dihapus secara permanen!')" class="btn btn-sm btn-danger" data-toggle="tooltip" data-placement="top" title="Hapus Transaksi">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                        endwhile;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="10" class="text-center">
                                            <div class="alert alert-info">
                                                <i class="fa fa-info-circle"></i> Tidak ada data transaksi
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endif; ?>
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
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to update per page and submit form
        function updatePerPage(value) {
            // Get all current filter parameters
            var urlParams = new URLSearchParams(window.location.search);
            urlParams.set('per_page', value);
            urlParams.set('page', '1'); // Reset to page 1 when changing per_page

            // Redirect with updated parameters
            window.location.href = '?' + urlParams.toString();
        }

        // Initialize tooltips
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });

        // Tampilkan SweetAlert jika ada success message
        <?php if (isset($_SESSION['success_message']) && !empty($_SESSION['success_message'])): ?>
        $(document).ready(function() {
            setTimeout(function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: '<?=addslashes($_SESSION['success_message'])?>',
                        confirmButtonColor: '#28a745',
                        confirmButtonText: 'OK',
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false,
                        allowOutsideClick: true,
                        allowEscapeKey: true
                    });
                } else {
                    alert('<?=addslashes($_SESSION['success_message'])?>');
                }
            }, 100);
        });
        <?php
            // Hapus session message setelah ditampilkan
            unset($_SESSION['success_message']);
            if (isset($_SESSION['success_type'])) {
                unset($_SESSION['success_type']);
            }
        ?>
        <?php endif; ?>
    </script>

    <?php
    // Tampilkan pesan hapus multiple jika ada
    if (isset($_SESSION['hapus_message'])) {
        $hapus_message = $_SESSION['hapus_message'];
        $hapus_success = isset($_SESSION['hapus_success']) ? $_SESSION['hapus_success'] : false;
        unset($_SESSION['hapus_message']);
        unset($_SESSION['hapus_success']);
        ?>
        <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: '<?=$hapus_success ? 'success' : 'error'?>',
                        title: '<?=$hapus_success ? 'Berhasil!' : 'Gagal!'?>',
                        text: <?=json_encode($hapus_message, JSON_UNESCAPED_UNICODE)?>,
                        confirmButtonColor: '<?=$hapus_success ? '#28a745' : '#dc3545'?>',
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                } else {
                    alert(<?=json_encode($hapus_message, JSON_UNESCAPED_UNICODE)?>);
                }
            }, 100);
        });
        </script>
        <?php
    }
    ?>

    <script>
        // Fungsi untuk handle checkbox selection
        $(document).ready(function() {
            const selectAll = $('#selectAll');
            const checkboxes = $('.checkbox-transaksi');
            const btnHapusMultiple = $('#btnHapusMultiple');
            const selectedCount = $('#selectedCount');

            function updateButtonState() {
                const selected = $('.checkbox-transaksi:checked');
                const count = selected.length;

                selectedCount.html('| Terpilih: <strong>' + count + '</strong>');
                btnHapusMultiple.prop('disabled', count === 0);
            }

            function updateSelectAllState() {
                const allChecked = checkboxes.length > 0 && checkboxes.length === $('.checkbox-transaksi:checked').length;
                const someChecked = $('.checkbox-transaksi:checked').length > 0;
                selectAll.prop('checked', allChecked);
                selectAll.prop('indeterminate', someChecked && !allChecked);
            }

            // Select All checkbox
            selectAll.on('change', function() {
                checkboxes.prop('checked', $(this).prop('checked'));
                updateButtonState();
            });

            // Individual checkbox
            checkboxes.on('change', function() {
                updateSelectAllState();
                updateButtonState();
            });

            // Initialize
            updateButtonState();
        });

        // Fungsi untuk hapus multiple
        function hapusMultiple() {
            const selected = Array.from(document.querySelectorAll('.checkbox-transaksi:checked'));
            if (selected.length === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Tidak ada yang dipilih',
                        text: 'Silakan pilih transaksi yang akan dihapus terlebih dahulu.'
                    });
                } else {
                    alert('Silakan pilih transaksi yang akan dihapus terlebih dahulu.');
                }
                return;
            }

            const ids = selected.map(cb => cb.value);
            const namas = selected.map(cb => cb.getAttribute('data-nama'));

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Yakin Hapus?',
                    html: 'Anda akan menghapus <strong>' + ids.length + '</strong> transaksi:<br><small>' + namas.slice(0, 5).join(', ') + (namas.length > 5 ? '...' : '') + '</small><br><br>Data yang dihapus tidak dapat dikembalikan!',
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
                        form.action = 'hapus_transaksi_multiple.php';

                        ids.forEach(id => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'id_transaksi[]';
                            input.value = id;
                            form.appendChild(input);
                        });

                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            } else {
                if (confirm('Anda akan menghapus ' + ids.length + ' transaksi. Lanjutkan?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'hapus_transaksi_multiple.php';

                    ids.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'id_transaksi[]';
                        input.value = id;
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                }
            }
        }
    </script>

    <?php
        include_once('../footer.php');
    ?>
</body>

</html>
