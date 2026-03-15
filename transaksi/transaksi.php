<?php
ob_start(); // Memulai output buffering di baris paling pertama
$page_title = 'Transaksi';

// Simpan logika PHP di atas sebelum include header.php yang berisi output HTML
include_once('../config/config.php');
require_once '../libs/saldo_helper.php';
require_once '../libs/produk_helper.php';

// --- LOGIKA TAMBAH TRANSAKSI (DARI TAMBAH.PHP) ---
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
        $error_msg = 'Harga tidak valid atau belum diisi.';
    }

    if (empty($error_msg)) {
        $harga = floatval($harga);
        $default_query = $koneksi->query("SELECT id_bayar FROM tb_jenisbayar ORDER BY id_bayar ASC LIMIT 1");
        $id_bayar_default = 1;
        if ($default_query && $default_query->num_rows > 0) {
            $default_row = $default_query->fetch_assoc();
            $id_bayar_default = intval($default_row['id_bayar']);
        }

        $check_column = $koneksi->query("SHOW COLUMNS FROM transaksi LIKE 'produk'");
        $has_produk_column = ($check_column && $check_column->num_rows > 0);
        $produk_val = !empty($produk) ? $produk : null;
        $ket_val = !empty($ket) ? $ket : '';

        if ($has_produk_column) {
            $query = "INSERT INTO transaksi (tgl, idpel, nama, produk, id_bayar, harga, status, ket) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param("ssssidss", $tgl, $idpel, $nama, $produk_val, $id_bayar_default, $harga, $status, $ket_val);
        } else {
            $query = "INSERT INTO transaksi (tgl, idpel, nama, id_bayar, harga, status, ket) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $koneksi->prepare($query);
            $stmt->bind_param("sssidss", $tgl, $idpel, $nama, $id_bayar_default, $harga, $status, $ket_val);
        }

        if ($stmt->execute()) {
            $id_transaksi = $koneksi->insert_id;
            require_once '../libs/log_activity.php';
            @log_activity('create', 'transaksi', 'Menambah transaksi: ' . $nama . ' (ID: ' . $idpel . ')');

            $ket_saldo = 'Transaksi: ' . $nama . ' (ID: ' . $idpel . ')';
            if (!empty($produk)) $ket_saldo .= ' - ' . $produk;
            $saldo_result = proses_saldo_transaksi($koneksi, $status, $harga, $ket_saldo, $id_transaksi);

            if ($saldo_result['success']) {
                $_SESSION['success_message'] = 'Transaksi berhasil ditambahkan';
            } else {
                $_SESSION['success_message'] = 'Transaksi berhasil ditambahkan. ' . $saldo_result['message'];
                $_SESSION['success_type'] = 'warning';
            }

            // REDIRECT HARUS SEBELUM ADA OUTPUT HTML
            header('Location: ' . base_url('transaksi/detail_transaksi.php?id=' . $id_transaksi));
            ob_end_clean(); // Bersihkan buffer sebelum exit
            exit();
        } else {
            $post_error_msg = 'Gagal menyimpan transaksi: ' . mysqli_error($koneksi);
        }
    } else {
        $post_error_msg = $error_msg;
    }
}

// Ambil data kategori produk secara dinamis
$all_kategori = getAllKategori();
// --- END LOGIKA TAMBAH TRANSAKSI ---

// Get filter parameters
$filter_status = $_GET['status'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_produk = $_GET['produk'] ?? '';
$search = $_GET['search'] ?? '';

// Build query conditions
$where_conditions = ["1=1"];

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
$per_page = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
if (!in_array($per_page, [10, 25, 50, 100])) {
    $per_page = 10;
}
$offset = ($page - 1) * $per_page;

// Get total count
$count_query = $koneksi->query("SELECT COUNT(*) as total FROM transaksi WHERE $where_clause");
$total_count = $count_query ? $count_query->fetch_assoc()['total'] : 0;
$total_pages = ceil($total_count / $per_page);

// Get transaksi data with pagination
$sql = $koneksi->query("SELECT transaksi.*, tb_jenisbayar.jenis_bayar,
                               COALESCE(transaksi.produk, tb_jenisbayar.jenis_bayar) as produk_display
                        FROM transaksi
                        JOIN tb_jenisbayar ON transaksi.id_bayar = tb_jenisbayar.id_bayar
                        WHERE $where_clause
                        ORDER BY transaksi.tgl DESC, transaksi.id_transaksi DESC
                        LIMIT $per_page OFFSET $offset");

// Get produk untuk filter dropdown
$produk_list = [];
$produk_query = $koneksi->query("SELECT DISTINCT produk FROM tb_produk_orderkuota WHERE produk IS NOT NULL AND produk != '' AND status = 1 ORDER BY produk ASC");
if ($produk_query) {
    while ($row = $produk_query->fetch_assoc()) {
        if (!empty($row['produk'])) {
            $produk_list[] = $row['produk'];
        }
    }
}

// Statistik
$total_query = $koneksi->query("SELECT COUNT(*) as total, SUM(CAST(harga AS UNSIGNED)) as nominal FROM transaksi");
$total_stats = $total_query->fetch_assoc();
$filtered_query = $koneksi->query("SELECT COUNT(*) as total, SUM(CAST(harga AS UNSIGNED)) as nominal FROM transaksi WHERE $where_clause");
$filtered_stats = $filtered_query->fetch_assoc();
$today_query = $koneksi->query("SELECT COUNT(*) as total, SUM(CAST(harga AS UNSIGNED)) as nominal FROM transaksi WHERE tgl = CURDATE()");
$today_stats = $today_query->fetch_assoc();
$month_query = $koneksi->query("SELECT COUNT(*) as total, SUM(CAST(harga AS UNSIGNED)) as nominal FROM transaksi WHERE MONTH(tgl) = MONTH(CURDATE()) AND YEAR(tgl) = YEAR(CURDATE())");
$month_stats = $month_query->fetch_assoc();

// SEKARANG BARU INCLUDE HEADER (OUTPUT HTML MULAI DI SINI)
include_once('../header.php');
?>

<style>
    /* CSS internal untuk halaman transaksi */
    .filter-box-modern select.filter-dropdown,
    .filter-box-modern .filter-dropdown {
        font-size: 0.8rem !important;
        min-height: 42px !important;
        height: 42px !important;
        line-height: 1.5 !important;
        padding: 8px 12px !important;
    }

    .stat-card-bs {
        border: 0;
        border-left: 4px solid #e9ecef;
        border-radius: 10px;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
        height: 100%;
    }
    .stat-card-bs .card-body {
        padding: 1.1rem 1.25rem;
    }
    .stat-card-bs__label {
        font-size: 0.75rem;
        font-weight: 700;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin-bottom: 0.25rem;
    }
    .stat-card-bs__value {
        font-size: 1.6rem;
        font-weight: 700;
        color: #212529;
        line-height: 1.2;
        margin: 0;
    }
    .stat-card-bs__sub {
        display: block;
        margin-top: 0.35rem;
        font-size: 0.8rem;
        color: #6c757d;
        font-weight: 600;
    }
    .stat-card-bs__icon {
        font-size: 2rem;
        color: #000;
        opacity: 0.35;
        line-height: 1;
        margin-left: 1rem;
    }
    .stat-card-bs--primary { border-left-color: #4e73df; }
    .stat-card-bs--success { border-left-color: #1cc88a; }
    .stat-card-bs--info { border-left-color: #36b9cc; }
    .stat-card-bs--warning { border-left-color: #f6c23e; }
    .stat-card-bs--primary .stat-card-bs__label { color: #4e73df; }
    .stat-card-bs--success .stat-card-bs__label { color: #1cc88a; }
    .stat-card-bs--info .stat-card-bs__label { color: #36b9cc; }
    .stat-card-bs--warning .stat-card-bs__label { color: #f6c23e; }

    /* Kategori & Produk Grid Styles */
    .kategori-card, .produk-grid-item {
        cursor: pointer;
        transition: all 0.3s;
    }
    .kategori-card:hover, .produk-grid-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    .kategori-card.selected, .produk-grid-item.selected {
        border-color: #28a745 !important;
        background-color: #f0fff4;
    }

    /* Peningkatan tinggi baris tabel modal edit */
    #tableEditMultiple td, #tableEditMultiple th {
        padding: 12px 8px !important;
        vertical-align: middle !important;
    }
    #tableEditMultiple th {
        background-color: #f8f9fa;
        font-weight: 600;
        font-size: 0.8rem;
    }
    #tableEditMultiple .form-control-sm {
        height: 38px !important;
        padding: 8px !important;
        font-size: 0.85rem !important;
        border-radius: 5px !important;
    }
</style>

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
    <!-- Statistik Widgets -->
    <div class="row mb-4">
        <?php
        $total_saldo = get_total_saldo($koneksi);
        $saldo_color = $total_saldo < 0 ? '#dc3545' : ($total_saldo < 100000 ? '#ffc107' : '#28a745');
        $saldo_status = $total_saldo < 0 ? 'Saldo Negatif!' : ($total_saldo < 100000 ? 'Saldo Rendah' : 'Saldo Aktif');
        ?>
        <div class="col-md-3 mb-3">
            <div class="card stat-card-bs shadow-sm <?=$total_saldo < 0 ? 'stat-card-bs--warning' : 'stat-card-bs--success'?>">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-card-bs__label">Saldo Akhir</div>
                        <div class="stat-card-bs__value">Rp <?=number_format($total_saldo, 0, ',', '.')?></div>
                        <span class="stat-card-bs__sub">
                            <i class="fa fa-<?=$total_saldo < 0 ? 'exclamation-triangle' : ($total_saldo < 100000 ? 'exclamation-circle' : 'check-circle')?>"></i>
                            <?=$saldo_status?>
                        </span>
                    </div>
                    <div class="stat-card-bs__icon">
                        <i class="fa fa-wallet"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card-bs stat-card-bs--primary shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-card-bs__label">Total Transaksi</div>
                        <div class="stat-card-bs__value"><?=number_format($total_stats['total'] ?? 0, 0, ',', '.')?></div>
                    </div>
                    <div class="stat-card-bs__icon">
                        <i class="fa fa-shopping-cart"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card-bs stat-card-bs--success shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-card-bs__label">Total Nominal</div>
                        <div class="stat-card-bs__value">Rp <?=number_format($total_stats['nominal'] ?? 0, 0, ',', '.')?></div>
                    </div>
                    <div class="stat-card-bs__icon">
                        <i class="fa fa-dollar-sign"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card-bs stat-card-bs--info shadow-sm">
                <div class="card-body d-flex align-items-center justify-content-between">
                    <div>
                        <div class="stat-card-bs__label">Hari Ini</div>
                        <div class="stat-card-bs__value"><?=number_format($today_stats['total'] ?? 0, 0, ',', '.')?></div>
                    </div>
                    <div class="stat-card-bs__icon">
                        <i class="fa fa-calendar"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Tambah Transaksi -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="modern-card">
                <div class="modern-card-header">
                    <h4><i class="fa fa-plus"></i> Tambah Transaksi</h4>
                </div>
                <div class="modern-card-body">
                    <form action="" method="POST" id="formTambahTransaksi">
                        <!-- Kategori Grid -->
                        <div class="row mb-4" id="kategori-grid">
                            <?php if (!empty($all_kategori)): ?>
                                <?php foreach ($all_kategori as $kategori): ?>
                                <div class="col-md-2 col-6 mb-3">
                                    <div class="card kategori-card border text-center p-2" data-kategori="<?=htmlspecialchars($kategori['kategori'])?>">
                                        <i class="fa fa-folder-open text-primary mb-1"></i>
                                        <div style="font-size: 0.8rem; font-weight: 600;"><?=htmlspecialchars($kategori['kategori'])?></div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- Produk Accordion (AJAX) -->
                        <div id="produk-accordion-container" style="display: none;" class="mb-4">
                            <div class="modern-card border p-3">
                                <h6 class="mb-3">Pilih Produk</h6>
                                <div id="produkAccordion" class="row"></div>
                            </div>
                        </div>

                        <!-- Main Form Fields -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Tanggal</label>
                                    <input type="date" name="tgl" value="<?=date('Y-m-d')?>" class="form-control" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label>ID Pelanggan</label>
                                    <div class="input-group">
                                        <input type="hidden" name="id_pelanggan" id="id_pelanggan">
                                        <input type="text" name="idpel" id="idpel" class="form-control" readonly required>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-info" data-target="#modalItem" data-toggle="modal"><i class="fa fa-search"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group mb-3">
                                    <label>Nama Pelanggan</label>
                                    <input type="text" name="nama" id="nama" class="form-control" readonly required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label>Produk</label>
                                    <input type="text" name="produk" id="produk" class="form-control" readonly>
                                </div>
                                <div class="form-group mb-3">
                                    <label>Harga (Rp)</label>
                                    <input type="number" name="harga" id="harga" class="form-control" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label>Status</label>
                                    <select name="status" class="form-control">
                                        <option value="Lunas">Lunas</option>
                                        <option value="Belum">Belum Bayar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="form-group mb-3">
                                    <label>Keterangan</label>
                                    <input type="text" name="ket" id="ket" class="form-control">
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <button type="button" class="btn btn-secondary" onclick="resetForm()">Reset</button>
                            <button type="submit" name="simpan" class="btn btn-success">Simpan Transaksi</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Table -->
    <div class="row">
        <div class="col-12">
            <div class="modern-card">
                <div class="modern-card-header">
                    <h4><i class="fa fa-list"></i> Data Transaksi</h4>
                </div>
                <div class="modern-card-body">
                    <!-- Filter Form -->
                    <form method="GET" class="row mb-4" id="filterForm">
                        <div class="col-md-2 mb-2">
                            <label class="small font-weight-bold">Status</label>
                            <select name="status" class="form-control filter-dropdown auto-submit">
                                <option value="">Semua Status</option>
                                <option value="Lunas" <?=$filter_status == 'Lunas' ? 'selected' : ''?>>Lunas</option>
                                <option value="Belum" <?=$filter_status == 'Belum' ? 'selected' : ''?>>Belum Bayar</option>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="small font-weight-bold">Produk</label>
                            <select name="produk" class="form-control filter-dropdown auto-submit">
                                <option value="">Semua Produk</option>
                                <?php foreach ($produk_list as $p_item): ?>
                                <option value="<?=htmlspecialchars($p_item)?>" <?=$filter_produk == $p_item ? 'selected' : ''?>>
                                    <?=htmlspecialchars($p_item)?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="small font-weight-bold">Dari Tanggal</label>
                            <input type="date" name="date_from" class="form-control filter-dropdown auto-submit" value="<?=$filter_date_from?>">
                        </div>
                        <div class="col-md-2 mb-2">
                            <label class="small font-weight-bold">Sampai Tanggal</label>
                            <input type="date" name="date_to" class="form-control filter-dropdown auto-submit" value="<?=$filter_date_to?>">
                        </div>
                        <div class="col-md-4 mb-2">
                            <label class="small font-weight-bold">Cari Nama/ID/Ket</label>
                            <input type="text" name="search" class="form-control auto-submit" placeholder="Ketik untuk mencari..." value="<?=htmlspecialchars($search)?>">
                        </div>
                        <!-- Hidden field untuk menjaga per_page saat filter berubah -->
                        <input type="hidden" name="per_page" value="<?=$per_page?>">
                    </form>

                    <div id="bulkActionContainer" class="mb-3" style="display: none;">
                        <div class="alert alert-info d-flex justify-content-between align-items-center p-2 mb-0">
                            <div>
                                <i class="fa fa-check-square"></i> <span id="selectedCount">0</span> transaksi terpilih
                            </div>
                            <div class="d-flex" style="gap: 5px;">
                                <button type="button" class="btn btn-success btn-sm" id="btnBulkEdit">
                                    <i class="fa fa-edit"></i> Edit Terpilih
                                </button>
                                <button type="button" class="btn btn-danger btn-sm" id="btnBulkDelete">
                                    <i class="fa fa-trash"></i> Hapus Terpilih
                                </button>
                            </div>
                        </div>
                    </div>

                    <form action="hapus_transaksi_multiple.php" method="POST" id="formMultiple">
                    <!-- Table -->
                    <div class="table-responsive">
                        <table class="table modern-table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="checkAll"></th>
                                    <th>No</th>
                                    <th>Tanggal</th>
                                    <th>ID/PEL</th>
                                    <th>Nama</th>
                                    <th>Produk</th>
                                    <th>Harga</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($sql && $sql->num_rows > 0): $no = $offset + 1; while($data = $sql->fetch_assoc()): ?>
                                <tr>
                                    <td><input type="checkbox" name="id_transaksi[]" value="<?=$data['id_transaksi']?>" class="checkItem"></td>
                                    <td><?=$no++?></td>
                                    <td><?=date('d/m/Y', strtotime($data['tgl']))?></td>
                                    <td><code><?=$data['idpel']?></code></td>
                                    <td><?=htmlspecialchars($data['nama'])?></td>
                                    <td><?=htmlspecialchars($data['produk_display'])?></td>
                                    <td>Rp <?=number_format($data['harga'], 0, ',', '.')?></td>
                                    <td>
                                        <span class="badge badge-pill badge-<?=$data['status'] == 'Lunas' ? 'success' : 'danger'?>">
                                            <?=$data['status']?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="detail_transaksi.php?id=<?=$data['id_transaksi']?>" class="btn btn-sm btn-info" title="Detail"><i class="fa fa-eye"></i></a>
                                        <a href="edit.php?id=<?=$data['id_transaksi']?>" class="btn btn-sm btn-success" title="Edit"><i class="fa fa-edit"></i></a>
                                        <button type="button" class="btn btn-sm btn-danger btn-hapus-single" data-id="<?=$data['id_transaksi']?>" title="Hapus"><i class="fa fa-trash"></i></button>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                <tr><td colspan="9" class="text-center">Tidak ada data</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    </form>

                    <!-- Pagination & Entries Control -->
                    <div class="row mt-4 align-items-center">
                        <div class="col-md-4">
                            <form action="" method="GET" class="d-flex align-items-center">
                                <!-- Sertakan parameter filter lain agar tidak hilang saat ganti per_page -->
                                <?php foreach ($_GET as $key => $value): if($key != 'per_page' && $key != 'page'): ?>
                                    <input type="hidden" name="<?=htmlspecialchars($key)?>" value="<?=htmlspecialchars($value)?>">
                                <?php endif; endforeach; ?>

                                <span class="mr-2">Show</span>
                                <select name="per_page" class="form-control" style="width: 80px;" onchange="this.form.submit()">
                                    <option value="10" <?=$per_page == 10 ? 'selected' : ''?>>10</option>
                                    <option value="25" <?=$per_page == 25 ? 'selected' : ''?>>25</option>
                                    <option value="50" <?=$per_page == 50 ? 'selected' : ''?>>50</option>
                                    <option value="100" <?=$per_page == 100 ? 'selected' : ''?>>100</option>
                                </select>
                                <span class="ml-2">entries</span>
                            </form>
                        </div>
                        <div class="col-md-4 text-center">
                            <small class="text-muted">Showing <?=($offset + 1)?> to <?=min($offset + $per_page, $total_count)?> of <?=$total_count?> entries</small>
                        </div>
                        <div class="col-md-4">
                            <?php if ($total_pages > 1): ?>
                            <nav aria-label="Page navigation">
                                <ul class="pagination justify-content-end mb-0">
                                    <li class="page-item <?=$page <= 1 ? 'disabled' : ''?>">
                                        <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page' => $page - 1]))?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                    <?php
                                    $start_page = max(1, $page - 2);
                                    $end_page = min($total_pages, $page + 2);
                                    for ($i = $start_page; $i <= $end_page; $i++):
                                    ?>
                                    <li class="page-item <?=$i == $page ? 'active' : ''?>">
                                        <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page' => $i]))?>"><?=$i?></a>
                                    </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?=$page >= $total_pages ? 'disabled' : ''?>">
                                        <a class="page-link" href="?<?=http_build_query(array_merge($_GET, ['page' => $page + 1]))?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                </ul>
                            </nav>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Edit Multiple -->
<div class="modal fade" id="modalEditMultiple" tabindex="-1" role="dialog" aria-labelledby="modalEditMultipleLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditMultipleLabel"><i class="fa fa-edit"></i> Edit Transaksi Terpilih</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formUpdateMultiple">
                <div class="modal-body">
                    <div id="loadingEditMultiple" class="text-center p-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat data...</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered" id="tableEditMultiple" style="display: none;">
                            <thead>
                                <tr>
                                    <th>Tgl</th>
                                     <th>ID Pelanggan</th>
                                     <th>Nama</th>
                                     <th>Jenis Bayar</th>
                                     <th>Harga</th>
                                     <th>Status</th>
                                     <th>Keterangan</th>
                                 </tr>
                             </thead>
                            <tbody id="tbodyEditMultiple"></tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-success" id="btnSangatUnikSimpan">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
jQuery(function($) {
    console.log("Transaksi page ready");

    // Handler Utama Simpan Data Multiple (Sudah Terverifikasi OK)
    $(document).on('click', '#btnSangatUnikSimpan', function() {
        var form = $('#formUpdateMultiple');
        var btn = $(this);

        btn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        $.ajax({
            url: 'update_multiple_ajax.php',
            type: 'POST',
            data: form.serialize(),
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: res.message,
                        timer: 2000,
                        showConfirmButton: false
                    }).then(() => {
                        $('#modalEditMultiple').modal('hide');
                        window.location.reload();
                    });
                } else {
                    Swal.fire('Gagal', res.message, 'error');
                    btn.prop('disabled', false).html('Simpan Perubahan');
                }
            },
            error: function(xhr) {
                Swal.fire('Error Sistem', 'Terjadi kesalahan saat menghubungi server.', 'error');
                btn.prop('disabled', false).html('Simpan Perubahan');
            }
        });
    });

    const kategoriCards = $('.kategori-card');
    const produkAccordionContainer = $('#produk-accordion-container');
    const produkAccordion = $('#produkAccordion');

    $(document).on('click', '.kategori-card', function() {
        $('.kategori-card').removeClass('selected');
        $(this).addClass('selected');
        const kategori = $(this).data('kategori');
        loadProduk(kategori);
    });

    function loadProduk(kategori) {
        produkAccordionContainer.show();
        produkAccordion.html('<div class="col-12 text-center">Memuat...</div>');
        $.getJSON('<?=base_url('transaksi/get_produk.php')?>', { kategori: kategori }, function(data) {
            if (data.success) {
                let html = '';
                data.produk.forEach(p => {
                    html += `
                        <div class="col-md-3 col-6 mb-2">
                            <div class="card produk-grid-item border p-2" data-kode="${p.kode}" data-harga="${p.harga}" data-produk="${p.produk || p.keterangan}">
                                <div style="font-size: 0.75rem; font-weight: 700;">${p.kode}</div>
                                <div class="text-success" style="font-size: 0.7rem;">Rp ${parseInt(p.harga).toLocaleString('id-ID')}</div>
                            </div>
                        </div>`;
                });
                produkAccordion.html(html);
            }
        });
    }

    $(document).on('click', '.produk-grid-item', function() {
        $('.produk-grid-item').removeClass('selected');
        $(this).addClass('selected');
        $('#harga').val($(this).data('harga'));
        $('#produk').val($(this).data('produk'));
        $('#ket').val($(this).data('kode') + ' - ' + $(this).data('produk'));
    });

    // Auto-submit filter
    $(document).on('change', '.auto-submit', function() {
        if ($(this).attr('name') !== 'per_page') {
            $('#filterForm').submit();
        }
    });

    let filterTimeout;
    $(document).on('keyup', 'input.auto-submit', function() {
        clearTimeout(filterTimeout);
        filterTimeout = setTimeout(() => {
            $('#filterForm').submit();
        }, 800);
    });

    // Checkbox logic
    $(document).on('click', '#checkAll', function() {
        $('.checkItem').prop('checked', this.checked);
        updateBulkActionVisibility();
    });

    $(document).on('change', '.checkItem', function() {
        updateBulkActionVisibility();
    });

    function updateBulkActionVisibility() {
        const checkedCount = $('.checkItem:checked').length;
        if (checkedCount > 0) {
            $('#bulkActionContainer').show();
            $('#selectedCount').text(checkedCount);
        } else {
            $('#bulkActionContainer').hide();
        }
    }

    // Bulk Edit
    $(document).on('click', '#btnBulkEdit', function() {
        const selectedIds = $('.checkItem:checked').map(function() { return this.value; }).get();
        if (selectedIds.length > 0) {
            $('#modalEditMultiple').modal('show');
            $('#loadingEditMultiple').show();
            $('#tableEditMultiple').hide();
            $('#btnSangatUnikSimpan').prop('disabled', true);

            $.getJSON('get_transaksi_multiple.php', { ids: selectedIds.join(',') }, function(data) {
                $('#loadingEditMultiple').hide();
                $('#tableEditMultiple').show();
                $('#btnSangatUnikSimpan').prop('disabled', false);

                if (data.success) {
                    let html = '';
                    data.data.forEach(t => {
                        html += `
                            <tr>
                                <td><input type="date" name="transaksi[${t.id_transaksi}][tgl]" value="${t.tgl}" class="form-control form-control-sm" required></td>
                                <td><input type="text" name="transaksi[${t.id_transaksi}][idpel]" value="${t.idpel}" class="form-control form-control-sm" required></td>
                                <td><input type="text" name="transaksi[${t.id_transaksi}][nama]" value="${t.nama}" class="form-control form-control-sm" required></td>
                                <td>
                                    <select name="transaksi[${t.id_transaksi}][id_bayar]" class="form-control form-control-sm">
                                        <option value="">Pilih Jenis</option>
                                        ${data.jenis_bayar.map(jb => `<option value="${jb.id_bayar}" ${t.id_bayar == jb.id_bayar ? 'selected' : ''}>${jb.jenis_bayar}</option>`).join('')}
                                    </select>
                                </td>
                                <td><input type="number" name="transaksi[${t.id_transaksi}][harga]" value="${parseInt(t.harga)}" class="form-control form-control-sm" required></td>
                                <td>
                                    <select name="transaksi[${t.id_transaksi}][status]" class="form-control form-control-sm">
                                        <option value="Lunas" ${t.status === 'Lunas' ? 'selected' : ''}>Lunas</option>
                                        <option value="Belum" ${t.status === 'Belum' ? 'selected' : ''}>Belum Bayar</option>
                                    </select>
                                </td>
                                <td><input type="text" name="transaksi[${t.id_transaksi}][ket]" value="${t.ket || ''}" class="form-control form-control-sm"></td>
                            </tr>`;
                    });
                    $('#tbodyEditMultiple').html(html);
                } else {
                    Swal.fire('Error', 'Gagal memuat data: ' + data.message, 'error');
                }
            });
        }
    });

    // Single Delete
    $(document).on('click', '.btn-hapus-single', function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Hapus Transaksi?',
            text: "Data akan dihapus permanen!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, Hapus'
        }).then((result) => {
            if (result.isConfirmed) window.location.href = 'hapus.php?id=' + id;
        });
    });

    // Bulk Delete
    $(document).on('click', '#btnBulkDelete', function() {
        const selectedCount = $('.checkItem:checked').length;
        if (selectedCount > 0) {
            Swal.fire({
                title: 'Hapus ' + selectedCount + ' Transaksi?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus'
            }).then((result) => {
                if (result.isConfirmed) $('#formMultiple').attr('action', 'hapus_transaksi_multiple.php').submit();
            });
        }
    });

    setTimeout(() => {
        const target = $('#formTambahTransaksi');
        if (target.length) $('html, body').animate({ scrollTop: target.offset().top - 80 }, 500);
    }, 300);
});

function resetForm() {
    $('#formTambahTransaksi')[0].reset();
    $('#produk-accordion-container').hide();
    $('.kategori-card').removeClass('selected');
}
</script>

<?php include_once('modal_item.php'); ?>
<?php include_once('../footer.php'); ?>

<?php
 // Tampilkan pesan sukses/error menggunakan SweetAlert jika ada
 if (isset($_SESSION['success_message'])) {
     $type = $_SESSION['success_type'] ?? 'success';
     $msg = $_SESSION['success_message'];
     echo "<script>
         document.addEventListener('DOMContentLoaded', function() {
             if (typeof Swal !== 'undefined') {
                 Swal.fire({ icon: '$type', title: '" . ($type == 'success' ? 'Berhasil' : 'Peringatan') . "', text: '$msg', timer: 3000, showConfirmButton: false });
             } else {
                 alert('$msg');
             }
         });
     </script>";
     unset($_SESSION['success_message'], $_SESSION['success_type']);
 }
 if (isset($_SESSION['hapus_message'])) {
     $type = $_SESSION['hapus_success'] ? 'success' : 'error';
     $msg = $_SESSION['hapus_message'];
     echo "<script>
         document.addEventListener('DOMContentLoaded', function() {
             if (typeof Swal !== 'undefined') {
                 Swal.fire({ icon: '$type', title: '" . ($type == 'success' ? 'Berhasil' : 'Gagal') . "', text: '$msg', timer: 3000, showConfirmButton: false });
             } else {
                 alert('$msg');
             }
         });
     </script>";
     unset($_SESSION['hapus_message'], $_SESSION['hapus_success']);
 }
 if (isset($post_error_msg)) {
     echo "<script>
         document.addEventListener('DOMContentLoaded', function() {
             if (typeof Swal !== 'undefined') {
                 Swal.fire({ icon: 'error', title: 'Gagal', text: '" . addslashes($post_error_msg) . "' });
             } else {
                 alert('" . addslashes($post_error_msg) . "');
             }
         });
     </script>";
 }
 ?>

<?php ob_end_flush(); ?>
