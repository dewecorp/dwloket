<?php
include_once('../header.php');
include_once('../config/config.php');

// Get filter parameters
$filter_status = $_GET['status'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_jenis_bayar = $_GET['jenis_bayar'] ?? '';
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

if ($filter_jenis_bayar) {
    $where_conditions[] = "transaksi.id_bayar = " . (int)$filter_jenis_bayar;
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
$sql = $koneksi->query("SELECT transaksi.*, tb_jenisbayar.jenis_bayar
                        FROM transaksi
                        JOIN tb_jenisbayar ON transaksi.id_bayar = tb_jenisbayar.id_bayar
                        WHERE $where_clause
                        ORDER BY transaksi.tgl DESC, transaksi.id_transaksi DESC
                        LIMIT $per_page OFFSET $offset");

// Get jenis bayar untuk filter dropdown
$jenis_bayar_list = [];
$jenis_bayar_query = $koneksi->query("SELECT * FROM tb_jenisbayar ORDER BY jenis_bayar ASC");
if ($jenis_bayar_query) {
    while ($row = $jenis_bayar_query->fetch_assoc()) {
        $jenis_bayar_list[] = $row;
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
        <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-<?=($_SESSION['success_success'] ?? false) ? 'success' : 'danger'?> alert-dismissible fade show" role="alert">
            <i class="fa fa-<?=($_SESSION['success_success'] ?? false) ? 'check-circle' : 'exclamation-triangle'?>"></i>
            <?=htmlspecialchars($_SESSION['success_message'])?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php
        unset($_SESSION['success_message']);
        unset($_SESSION['success_success']);
        endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-<?=($_SESSION['error_success'] ?? false) ? 'success' : 'danger'?> alert-dismissible fade show" role="alert">
            <i class="fa fa-<?=($_SESSION['error_success'] ?? false) ? 'check-circle' : 'exclamation-triangle'?>"></i>
            <?=htmlspecialchars($_SESSION['error_message'])?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php
        unset($_SESSION['error_message']);
        unset($_SESSION['error_success']);
        endif; ?>
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
                        <label>Jenis Bayar</label>
                        <select name="jenis_bayar" class="form-control">
                            <option value="">Semua Jenis</option>
                            <?php foreach ($jenis_bayar_list as $jb): ?>
                            <option value="<?=$jb['id_bayar']?>" <?=$filter_jenis_bayar == $jb['id_bayar'] ? 'selected' : ''?>>
                                <?=htmlspecialchars($jb['jenis_bayar'])?>
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
        </div>

        <!-- Statistik Filtered -->
        <?php if ($filter_date_from || $filter_date_to || $filter_status || $filter_jenis_bayar || $search): ?>
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
                                <a href="<?=base_url('export_excel.php?page=transaksi&status=' . urlencode($filter_status) . '&date_from=' . urlencode($filter_date_from) . '&date_to=' . urlencode($filter_date_to) . '&jenis_bayar=' . urlencode($filter_jenis_bayar) . '&search=' . urlencode($search))?>" class="btn btn-sm btn-success ml-2">
                                    <i class="fa fa-file-excel"></i> Excel
                                </a>
                                <a href="<?=base_url('export_pdf.php?page=transaksi&status=' . urlencode($filter_status) . '&date_from=' . urlencode($filter_date_from) . '&date_to=' . urlencode($filter_date_to) . '&jenis_bayar=' . urlencode($filter_jenis_bayar) . '&search=' . urlencode($search))?>" target="_blank" class="btn btn-sm btn-danger ml-2">
                                    <i class="fa fa-file-pdf"></i> PDF
                                </a>
                                <div id="bulk-actions" style="display: none; margin-left: 0.5rem;">
                                    <button type="button" class="btn btn-sm btn-warning" onclick="editMultiple()">
                                        <i class="fa fa-edit"></i> Edit Terpilih
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger ml-2" onclick="hapusMultiple()">
                                        <i class="fa fa-trash"></i> Hapus Terpilih
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive mt-3">
                            <table class="table modern-table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 30px;">
                                            <input type="checkbox" id="selectAll" onchange="toggleSelectAll(this)">
                                        </th>
                                        <th style="width: 5px;">No</th>
                                        <th>Tanggal</th>
                                        <th>No. ID/PEL</th>
                                        <th>Nama Pelanggan</th>
                                        <th>Jenis Bayar</th>
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
                                            <input type="checkbox" class="transaksi-checkbox" value="<?=$data['id_transaksi']?>" onchange="updateBulkActions()">
                                        </td>
                                        <td><?=$no++;?></td>
                                        <td><?=date('d/m/Y', strtotime($tgl));?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <code class="mr-2"><?=$data['idpel'];?></code>
                                                <button class="btn btn-xs btn-outline-primary"
                                                        onclick="copyIdPel('<?=htmlspecialchars($data['idpel'], ENT_QUOTES)?>', this)"
                                                        data-toggle="tooltip"
                                                        data-placement="top"
                                                        title="Copy ID/PEL untuk pembayaran manual"
                                                        id="copyBtn_<?=$data['id_transaksi']?>">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                                <span class="copy-feedback ml-2" id="copyFeedback_<?=$data['id_transaksi']?>" style="display: none; color: #28a745; font-size: 0.75rem; font-weight: 500;">
                                                    <i class="fa fa-check"></i> Sudah Disalin
                                                </span>
                                            </div>
                                        </td>
                                        <td><?=htmlspecialchars($data['nama']);?></td>
                                        <td><?=htmlspecialchars($data['jenis_bayar']);?></td>
                                        <td><strong>Rp <?=number_format($data['harga'], 0, ",", ".");?></strong></td>
                                        <td><?=$status?></td>
                                        <td><small><?=htmlspecialchars(mb_substr($data['ket'], 0, 50)) . (mb_strlen($data['ket']) > 50 ? '...' : '');?></small></td>
                                        <td align="center">
                                            <div class="btn-group" role="group">
                                                <a href="detail_transaksi.php?id=<?=$data['id_transaksi'];?>" class="btn btn-sm btn-info" data-toggle="tooltip" data-placement="top" title="Detail Transaksi">
                                                    <i class="fa fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?=$data['id_transaksi']; ?>" class="btn btn-sm btn-warning" data-toggle="tooltip" data-placement="top" title="Edit Transaksi">
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

    <!-- Modal Edit Multiple -->
    <div class="modal fade" id="modalEditMultiple" tabindex="-1" role="dialog" aria-labelledby="modalEditMultipleLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width: 95%; width: 95%;">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                    <h4 class="modal-title" id="modalEditMultipleLabel">
                        <i class="fa fa-edit"></i> Edit Multiple Transaksi
                        <span class="badge badge-light ml-2" id="editMultipleCount">0 transaksi</span>
                    </h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="editMultipleModalBody">
                    <div class="text-center p-4">
                        <i class="fa fa-spinner fa-spin fa-2x"></i>
                        <br><br>Memuat data...
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fa fa-times"></i> Batal
                    </button>
                    <button type="button" class="btn btn-success" onclick="submitEditMultiple()">
                        <i class="fa fa-save"></i> Simpan Perubahan
                    </button>
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

        // Function to copy ID/PEL to clipboard
        function copyIdPel(idpel, buttonElement) {
            // Get feedback element
            var buttonId = buttonElement ? buttonElement.id : '';
            var transaksiId = buttonId ? buttonId.replace('copyBtn_', '') : '';
            var feedbackElement = transaksiId ? document.getElementById('copyFeedback_' + transaksiId) : null;

            // Copy function
            function doCopy() {
                // Try modern clipboard API first
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(idpel).then(function() {
                        showCopySuccess(buttonElement, feedbackElement);
                    }).catch(function(err) {
                        console.error('Clipboard API failed:', err);
                        // Fallback to old method
                        fallbackCopy(idpel, buttonElement, feedbackElement);
                    });
                } else {
                    // Fallback for older browsers
                    fallbackCopy(idpel, buttonElement, feedbackElement);
                }
            }

            // Execute copy
            doCopy();
        }

        // Show copy success feedback
        function showCopySuccess(buttonElement, feedbackElement) {
            if (!buttonElement) return;

            // Save original tooltip title before removing
            var originalTitle = buttonElement.getAttribute('title') || buttonElement.getAttribute('data-original-title') || 'Copy ID/PEL untuk pembayaran manual';

            // Disable/hide tooltip saat copy berhasil
            if (typeof $ !== 'undefined' && $(buttonElement).data('bs.tooltip')) {
                $(buttonElement).tooltip('hide');
                $(buttonElement).tooltip('disable');
            }

            // Remove tooltip attributes to prevent tooltip from showing
            buttonElement.removeAttribute('data-toggle');
            buttonElement.removeAttribute('title');
            buttonElement.removeAttribute('data-placement');
            buttonElement.setAttribute('data-tooltip-disabled', 'true');

            // Change button appearance
            buttonElement.classList.remove('btn-outline-primary');
            buttonElement.classList.add('btn-success');
            var originalHTML = buttonElement.innerHTML;
            buttonElement.innerHTML = '<i class="fa fa-check"></i>';

            // Show feedback text
            if (feedbackElement) {
                feedbackElement.style.display = 'inline';
            }

            // Tidak perlu SweetAlert - cukup feedback visual di tombol dan teks
            // Feedback visual sudah cukup jelas

            // Reset button after 2 seconds
            setTimeout(function() {
                if (buttonElement) {
                    buttonElement.classList.remove('btn-success');
                    buttonElement.classList.add('btn-outline-primary');
                    buttonElement.innerHTML = originalHTML;

                    // Restore tooltip attributes
                    buttonElement.setAttribute('data-toggle', 'tooltip');
                    buttonElement.setAttribute('data-placement', 'top');
                    buttonElement.setAttribute('title', originalTitle);
                    buttonElement.removeAttribute('data-tooltip-disabled');

                    // Re-initialize tooltip
                    if (typeof $ !== 'undefined') {
                        $(buttonElement).tooltip('dispose'); // Remove old tooltip instance
                        $(buttonElement).tooltip(); // Re-initialize tooltip
                    }
                }
                if (feedbackElement) {
                    feedbackElement.style.display = 'none';
                }
            }, 2000);
        }

        // Fallback copy method for older browsers
        function fallbackCopy(idpel, buttonElement, feedbackElement) {
            const textArea = document.createElement('textarea');
            textArea.value = idpel;
            textArea.style.position = 'fixed';
            textArea.style.top = '0';
            textArea.style.left = '0';
            textArea.style.width = '2em';
            textArea.style.height = '2em';
            textArea.style.padding = '0';
            textArea.style.border = 'none';
            textArea.style.outline = 'none';
            textArea.style.boxShadow = 'none';
            textArea.style.background = 'transparent';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    showCopySuccess(buttonElement, feedbackElement);
                } else {
                    // Tampilkan SweetAlert jika gagal
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Gagal menyalin. Silakan salin manual: ' + idpel,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                }
            } catch (err) {
                console.error('Copy failed:', err);
                // Tampilkan SweetAlert jika gagal
                Swal.fire({
                    icon: 'error',
                    title: 'Gagal!',
                    text: 'Gagal menyalin. Silakan salin manual: ' + idpel,
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'OK'
                });
            }

            document.body.removeChild(textArea);
        }

        // Initialize tooltips
        $(document).ready(function() {
            $('[data-toggle="tooltip"]').tooltip();
        });

        // Toggle select all checkbox
        function toggleSelectAll(checkbox) {
            const checkboxes = document.querySelectorAll('.transaksi-checkbox');
            checkboxes.forEach(function(cb) {
                cb.checked = checkbox.checked;
            });
            updateBulkActions();
        }

        // Update bulk actions visibility
        function updateBulkActions() {
            const checkboxes = document.querySelectorAll('.transaksi-checkbox:checked');
            const bulkActions = document.getElementById('bulk-actions');

            if (checkboxes.length > 0) {
                bulkActions.style.display = 'block';
            } else {
                bulkActions.style.display = 'none';
            }

            // Update select all checkbox
            const allCheckboxes = document.querySelectorAll('.transaksi-checkbox');
            const selectAll = document.getElementById('selectAll');
            if (allCheckboxes.length > 0) {
                selectAll.checked = checkboxes.length === allCheckboxes.length;
            }
        }

        // Edit multiple transaksi
        function editMultiple() {
            const checkboxes = document.querySelectorAll('.transaksi-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);

            if (ids.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Peringatan!',
                    text: 'Pilih minimal satu transaksi untuk diedit',
                    confirmButtonColor: '#ffc107',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Load data transaksi ke modal
            loadTransaksiForEdit(ids);
        }

        // Load transaksi data untuk edit multiple
        function loadTransaksiForEdit(ids) {
            const modalBody = document.getElementById('editMultipleModalBody');
            modalBody.innerHTML = '<div class="text-center p-4"><i class="fa fa-spinner fa-spin fa-2x"></i><br><br>Memuat data transaksi...</div>';

            $('#modalEditMultiple').modal('show');

            // Fetch data transaksi
            fetch('<?=base_url('transaksi/get_transaksi_multiple.php')?>?ids=' + ids.join(','))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.transaksi.length > 0) {
                        renderEditMultipleForm(data.transaksi, data.jenis_bayar_list);
                    } else {
                        modalBody.innerHTML = '<div class="alert alert-danger">Gagal memuat data transaksi</div>';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    modalBody.innerHTML = '<div class="alert alert-danger">Terjadi kesalahan saat memuat data</div>';
                });
        }

        // Render form edit multiple
        function renderEditMultipleForm(transaksiList, jenisBayarList) {
            const modalBody = document.getElementById('editMultipleModalBody');
            let html = '<form id="formEditMultiple" method="POST" action="<?=base_url('transaksi/update_multiple.php')?>">';
            html += '<input type="hidden" name="update_multiple" value="1">';
            html += '<div class="table-responsive" style="max-height: 400px; overflow-y: auto;">';
            html += '<table class="table table-bordered table-sm">';
            html += '<thead class="thead-light" style="position: sticky; top: 0; z-index: 10;">';
            html += '<tr>';
            html += '<th style="width: 50px;">No</th>';
            html += '<th style="width: 130px;">Tanggal</th>';
            html += '<th style="width: 120px;">ID Pelanggan</th>';
            html += '<th style="width: 150px;">Nama</th>';
            html += '<th style="width: 170px;">Jenis Bayar</th>';
            html += '<th style="width: 140px;">Harga</th>';
            html += '<th style="width: 130px;">Status</th>';
            html += '<th style="width: 300px; min-width: 300px;">Keterangan</th>';
            html += '</tr>';
            html += '</thead>';
            html += '<tbody>';

            // Helper function untuk escape HTML
            function escapeHtml(text) {
                if (!text) return '';
                const map = {
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;'
                };
                return String(text).replace(/[&<>"']/g, function(m) { return map[m]; });
            }

            transaksiList.forEach(function(trans, index) {
                const idTrans = escapeHtml(trans.id_transaksi);
                const tgl = escapeHtml(trans.tgl || '');
                const idpel = escapeHtml(trans.idpel || '');
                const nama = escapeHtml(trans.nama || '');
                const harga = escapeHtml(trans.harga || 0);
                const ket = escapeHtml(trans.ket || '');

                html += '<tr>';
                html += '<td style="vertical-align: middle;">' + (index + 1) + '</td>';
                html += '<td style="vertical-align: middle;"><input type="date" name="transaksi[' + idTrans + '][tgl]" value="' + tgl + '" class="form-control form-control-sm" style="height: 38px; padding: 6px 12px;" required></td>';
                html += '<td style="vertical-align: middle;"><input type="text" name="transaksi[' + idTrans + '][idpel]" value="' + idpel + '" class="form-control form-control-sm" style="height: 38px; padding: 6px 12px;" required></td>';
                html += '<td style="vertical-align: middle;"><input type="text" name="transaksi[' + idTrans + '][nama]" value="' + nama + '" class="form-control form-control-sm" style="height: 38px; padding: 6px 12px;" required></td>';
                html += '<td style="vertical-align: middle; min-width: 180px;"><select name="transaksi[' + idTrans + '][id_bayar]" class="form-control form-control-sm" style="height: 38px; padding: 6px 12px; min-width: 180px;">';
                html += '<option value="">Pilih Jenis</option>';
                jenisBayarList.forEach(function(jenis) {
                    const selected = (trans.id_bayar == jenis.id_bayar) ? 'selected' : '';
                    html += '<option value="' + escapeHtml(jenis.id_bayar) + '" ' + selected + '>' + escapeHtml(jenis.jenis_bayar) + '</option>';
                });
                html += '</select></td>';
                html += '<td style="vertical-align: middle; min-width: 150px;"><div class="input-group input-group-sm"><div class="input-group-prepend"><span class="input-group-text" style="height: 38px; padding: 6px 12px;">Rp</span></div>';
                html += '<input type="number" name="transaksi[' + idTrans + '][harga]" value="' + harga + '" class="form-control" style="height: 38px; padding: 6px 12px;" step="1" min="0" required></div></td>';
                html += '<td style="vertical-align: middle; min-width: 140px;"><select name="transaksi[' + idTrans + '][status]" class="form-control form-control-sm" style="height: 38px; padding: 6px 12px; min-width: 140px;">';
                html += '<option value="Lunas" ' + (trans.status == 'Lunas' ? 'selected' : '') + '>Lunas</option>';
                html += '<option value="Belum" ' + (trans.status == 'Belum' ? 'selected' : '') + '>Belum Bayar</option>';
                html += '</select></td>';
                html += '<td style="vertical-align: middle; min-width: 300px;"><input type="text" name="transaksi[' + idTrans + '][ket]" value="' + ket + '" class="form-control form-control-sm" style="height: 38px; padding: 6px 12px; width: 100%; min-width: 300px;"></td>';
                html += '</tr>';
            });

            html += '</tbody>';
            html += '</table>';
            html += '</div>';
            html += '</form>';

            modalBody.innerHTML = html;

            // Update count badge
            document.getElementById('editMultipleCount').textContent = transaksiList.length + ' transaksi';
        }

        // Submit edit multiple form
        function submitEditMultiple() {
            const form = document.getElementById('formEditMultiple');
            if (!form) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Form tidak ditemukan',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Validate form
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            // Show loading
            Swal.fire({
                title: 'Menyimpan...',
                text: 'Mohon tunggu',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Submit form via AJAX
            const formData = new FormData(form);

            fetch('<?=base_url('transaksi/update_multiple.php')?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                Swal.close();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        confirmButtonColor: '#28a745',
                        confirmButtonText: 'OK'
                    }).then(() => {
                        $('#modalEditMultiple').modal('hide');
                        location.reload();
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: data.message,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Terjadi kesalahan saat menyimpan data',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'OK'
                });
            });
        }

        // Hapus multiple transaksi
        function hapusMultiple() {
            const checkboxes = document.querySelectorAll('.transaksi-checkbox:checked');
            const ids = Array.from(checkboxes).map(cb => cb.value);

            if (ids.length === 0) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Peringatan!',
                    text: 'Pilih minimal satu transaksi untuk dihapus',
                    confirmButtonColor: '#ffc107',
                    confirmButtonText: 'OK'
                });
                return;
            }

            Swal.fire({
                icon: 'warning',
                title: 'Yakin Hapus?',
                text: 'Anda akan menghapus ' + ids.length + ' transaksi. Tindakan ini tidak dapat dibatalkan!',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit form untuk hapus multiple
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = '<?=base_url('transaksi/hapus_multiple.php')?>';

                    ids.forEach(function(id) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = id;
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }
    </script>

    <?php
        include_once('../footer.php');
    ?>
</body>

</html>
