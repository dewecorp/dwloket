<?php
include_once('../header.php');
include_once('../config/config.php');
require_once '../libs/orderkuota_api.php';
require_once '../libs/log_activity.php';

// Get filter
$filter_status = $_GET['status'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = ["1=1"];

if ($filter_status) {
    $where_conditions[] = "status = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'";
}

if ($filter_date_from) {
    $where_conditions[] = "DATE(tgl) >= '" . mysqli_real_escape_string($koneksi, $filter_date_from) . "'";
}

if ($filter_date_to) {
    $where_conditions[] = "DATE(tgl) <= '" . mysqli_real_escape_string($koneksi, $filter_date_to) . "'";
}

if ($search) {
    $search_escaped = mysqli_real_escape_string($koneksi, $search);
    $where_conditions[] = "(ref_id LIKE '%$search_escaped%' OR transaction_id LIKE '%$search_escaped%' OR keterangan LIKE '%$search_escaped%')";
}

$where_clause = implode(' AND ', $where_conditions);

// Get history deposit
$deposit_history = [];
$history_query = $koneksi->query("SELECT * FROM orderkuota_deposit WHERE $where_clause ORDER BY tgl DESC, id_deposit DESC LIMIT 100");
if ($history_query) {
    while ($row = $history_query->fetch_assoc()) {
        $deposit_history[] = $row;
    }
} else {
    // Jika tabel belum ada, buat tabel
    $create_table_query = "CREATE TABLE IF NOT EXISTS orderkuota_deposit (
        id_deposit INT(11) AUTO_INCREMENT PRIMARY KEY,
        tgl DATETIME NOT NULL,
        amount DECIMAL(15,2) NOT NULL,
        payment_method VARCHAR(50),
        ref_id VARCHAR(100),
        transaction_id VARCHAR(100),
        status VARCHAR(50),
        keterangan TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_tgl (tgl),
        INDEX idx_ref_id (ref_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $koneksi->query($create_table_query);
}

// Statistik deposit
$stats = [
    'total_deposit' => 0,
    'total_nominal' => 0,
    'deposit_bulan_ini' => 0,
    'nominal_bulan_ini' => 0,
];

$stats_query = $koneksi->query("SELECT COUNT(*) as total, SUM(amount) as nominal FROM orderkuota_deposit WHERE status = 'Berhasil'");
if ($stats_query) {
    $stats_row = $stats_query->fetch_assoc();
    $stats['total_deposit'] = $stats_row['total'] ?? 0;
    $stats['total_nominal'] = $stats_row['nominal'] ?? 0;
}

$stats_month = $koneksi->query("SELECT COUNT(*) as total, SUM(amount) as nominal FROM orderkuota_deposit WHERE status = 'Berhasil' AND MONTH(tgl) = MONTH(CURRENT_DATE()) AND YEAR(tgl) = YEAR(CURRENT_DATE())");
if ($stats_month) {
    $stats_month_row = $stats_month->fetch_assoc();
    $stats['deposit_bulan_ini'] = $stats_month_row['total'] ?? 0;
    $stats['nominal_bulan_ini'] = $stats_month_row['nominal'] ?? 0;
}
?>

<body>
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">History Deposit OrderKuota</h4>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                            <li class="breadcrumb-item"><a href="<?=base_url('orderkuota')?>" class="text-muted">OrderKuota</a></li>
                            <li class="breadcrumb-item"><a href="<?=base_url('orderkuota/deposit.php')?>" class="text-muted">Deposit</a></li>
                            <li class="breadcrumb-item text-muted active" aria-current="page">History</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Statistik -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fa fa-wallet" style="color: white !important; font-size: 28px !important;"></i>
                    </div>
                    <div class="stat-value"><?=$stats['total_deposit']?></div>
                    <div class="stat-label">Total Deposit</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #1cc88a 0%, #17a673 100%);">
                        <i class="fa fa-dollar-sign" style="color: white !important; font-size: 28px !important;"></i>
                    </div>
                    <div class="stat-value">Rp <?=number_format($stats['total_nominal'], 0, ',', '.')?></div>
                    <div class="stat-label">Total Nominal</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #36b9cc 0%, #2c9faf 100%);">
                        <i class="fa fa-calendar-alt" style="color: white !important; font-size: 28px !important;"></i>
                    </div>
                    <div class="stat-value"><?=$stats['deposit_bulan_ini']?></div>
                    <div class="stat-label">Bulan Ini</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-modern">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #f6c23e 0%, #dda20a 100%);">
                        <i class="fa fa-money-bill-wave" style="color: white !important; font-size: 28px !important;"></i>
                    </div>
                    <div class="stat-value">Rp <?=number_format($stats['nominal_bulan_ini'], 0, ',', '.')?></div>
                    <div class="stat-label">Nominal Bulan Ini</div>
                </div>
            </div>
        </div>

        <!-- Filter -->
        <div class="filter-box-modern">
            <form method="GET" class="row">
                    <div class="col-md-2">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="">Semua Status</option>
                            <option value="Berhasil" <?=$filter_status == 'Berhasil' ? 'selected' : ''?>>Berhasil</option>
                            <option value="Pending" <?=$filter_status == 'Pending' ? 'selected' : ''?>>Pending</option>
                            <option value="Gagal" <?=$filter_status == 'Gagal' ? 'selected' : ''?>>Gagal</option>
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
                            <input type="text" name="search" class="form-control" placeholder="Ref ID/Transaction ID" value="<?=htmlspecialchars($search)?>">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-info">
                                    <i class="fa fa-search"></i>
                                </button>
                                <a href="<?=base_url('orderkuota/deposit_history.php')?>" class="btn btn-secondary">
                                    <i class="fa fa-refresh"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <div>
                            <a href="<?=base_url('orderkuota/deposit.php')?>" class="btn btn-primary btn-block">
                                <i class="fa fa-plus"></i> Tambah Deposit
                            </a>
                        </div>
                    </div>
                </form>
        </div>

        <!-- Tabel History -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">
                        <i class="fa fa-history"></i> Daftar Deposit
                        <span class="badge badge-primary ml-2"><?=count($deposit_history)?> Deposit</span>
                    </h4>
                    <div>
                        <a href="<?=base_url('export_excel.php?page=deposit_history&' . http_build_query($_GET))?>"
                           class="btn btn-sm btn-success mr-2">
                            <i class="fa fa-file-excel"></i> Excel
                        </a>
                        <a href="<?=base_url('export_pdf.php?page=deposit_history&' . http_build_query($_GET))?>"
                           target="_blank"
                           class="btn btn-sm btn-danger">
                            <i class="fa fa-file-pdf"></i> PDF
                        </a>
                    </div>
                </div>

                <?php if (empty($deposit_history)): ?>
                <div class="alert alert-info text-center">
                    <i class="fa fa-info-circle"></i> Belum ada history deposit
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal</th>
                                <th>Jumlah</th>
                                <th>Metode Pembayaran</th>
                                <th>Reference ID</th>
                                <th>Transaction ID</th>
                                <th>Status</th>
                                <th>Keterangan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($deposit_history as $hist): ?>
                            <tr>
                                <td><?=$no++?></td>
                                <td><?=date('d/m/Y H:i:s', strtotime($hist['tgl']))?></td>
                                <td>
                                    <strong>Rp <?=number_format($hist['amount'], 0, ',', '.')?></strong>
                                </td>
                                <td>
                                    <?php
                                    $method_labels = [
                                        // Transfer Bank
                                        'transfer_bca' => 'Transfer Bank - BCA',
                                        'transfer_mandiri' => 'Transfer Bank - Mandiri',
                                        'transfer_bni' => 'Transfer Bank - BNI',
                                        'transfer_bri' => 'Transfer Bank - BRI',
                                        'transfer_cimb' => 'Transfer Bank - CIMB Niaga',
                                        'transfer_danamon' => 'Transfer Bank - Danamon',
                                        'transfer_permata' => 'Transfer Bank - Permata',
                                        'transfer_other' => 'Transfer Bank - Lainnya',
                                        'transfer' => 'Transfer Bank',
                                        // Virtual Account
                                        'va_bca' => 'Virtual Account - BCA',
                                        'va_mandiri' => 'Virtual Account - Mandiri',
                                        'va_bni' => 'Virtual Account - BNI',
                                        'va_bri' => 'Virtual Account - BRI',
                                        'va_permata' => 'Virtual Account - Permata',
                                        'virtual_account' => 'Virtual Account',
                                        // E-Wallet
                                        'ewallet_dana' => 'DANA',
                                        'ewallet_ovo' => 'OVO',
                                        'ewallet_gopay' => 'GoPay',
                                        'ewallet_shopeepay' => 'ShopeePay',
                                        'ewallet_linkaja' => 'LinkAja',
                                        'ewallet_jenius' => 'Jenius',
                                        'e_wallet' => 'E-Wallet',
                                        // QRIS
                                        'qris' => 'QRIS',
                                        // Gerai Retail
                                        'retail_alfamart' => 'Alfamart',
                                        'retail_indomaret' => 'Indomaret',
                                        'retail_alfamidi' => 'Alfamidi',
                                        // Lainnya
                                        'credit_card' => 'Credit Card',
                                        'debit_card' => 'Debit Card'
                                    ];
                                    echo $method_labels[$hist['payment_method']] ?? ucfirst(str_replace('_', ' ', $hist['payment_method']));
                                    ?>
                                </td>
                                <td>
                                    <code><?=htmlspecialchars($hist['ref_id'] ?: 'N/A')?></code>
                                </td>
                                <td>
                                    <code><?=htmlspecialchars($hist['transaction_id'] ?: '-')?></code>
                                </td>
                                <td>
                                    <span class="badge badge-<?=$hist['status'] == 'Berhasil' ? 'success' : ($hist['status'] == 'Pending' ? 'warning' : 'danger')?>">
                                        <?=$hist['status']?>
                                    </span>
                                </td>
                                <td>
                                    <?=htmlspecialchars($hist['keterangan'] ?: '-')?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php
    include_once('../footer.php');
    ?>





