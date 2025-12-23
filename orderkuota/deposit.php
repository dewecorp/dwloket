<?php
$page_title = 'Deposit OrderKuota';
include_once('../header.php');
include_once('../config/config.php');
require_once '../libs/orderkuota_api.php';
require_once '../libs/log_activity.php';

// Handle deposit
$deposit_result = null;
if (isset($_POST['deposit'])) {
    $amount = $_POST['amount'] ?? 0;
    $payment_method = $_POST['payment_method'] ?? 'transfer';
    $keterangan = $_POST['keterangan'] ?? '';

    // Validasi input
    if (empty($amount) || $amount <= 0) {
        $deposit_result = [
            'success' => false,
            'message' => 'Jumlah deposit tidak valid'
        ];
    } elseif ($amount < 10000) {
        $deposit_result = [
            'success' => false,
            'message' => 'Minimal deposit adalah Rp 10.000'
        ];
    } else {
        // Cek konfigurasi API
        $api = new OrderKuotaAPI();
        $config_status = $api->getConfigStatus();

        if (!$config_status['api_key_set'] || !$config_status['api_secret_set']) {
            $deposit_result = [
                'success' => false,
                'message' => 'API Key atau API Secret belum dikonfigurasi. Silakan isi di halaman Admin > OrderKuota Config terlebih dahulu.',
                'error_code' => 'CONFIG_MISSING'
            ];
        } else {
            // Generate reference ID
            $ref_id = 'DEPOSIT_' . date('YmdHis') . '_' . rand(1000, 9999);

            // Lakukan deposit
            $deposit_result = deposit_orderkuota($amount, $payment_method, $ref_id);

            // Jika berhasil, simpan ke database
            if ($deposit_result['success']) {
                $tgl = date('Y-m-d H:i:s');
                $status = 'Berhasil';
                $ref_id_from_api = $deposit_result['data']['ref_id'] ?? $ref_id;
                $transaction_id = $deposit_result['data']['transaction_id'] ?? '';

                // Simpan ke tabel deposit (buat tabel jika belum ada)
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

                // Insert deposit record
                $insert_query = "INSERT INTO orderkuota_deposit (tgl, amount, payment_method, ref_id, transaction_id, status, keterangan)
                                VALUES ('$tgl', '$amount', '" . mysqli_real_escape_string($koneksi, $payment_method) . "',
                                        '" . mysqli_real_escape_string($koneksi, $ref_id_from_api) . "',
                                        '" . mysqli_real_escape_string($koneksi, $transaction_id) . "',
                                        '$status',
                                        '" . mysqli_real_escape_string($koneksi, $keterangan) . "')";

                if ($koneksi->query($insert_query)) {
                    $deposit_id = $koneksi->insert_id;

                    // Log aktivitas
                    log_activity('deposit', 'orderkuota', "Deposit OrderKuota berhasil - Jumlah: Rp " . number_format($amount, 0, ',', '.') . " (Ref: $ref_id_from_api)");

                    // Redirect dengan success
                    header('Location: ' . base_url('orderkuota/deposit.php?success=1&amount=' . $amount . '&ref_id=' . urlencode($ref_id_from_api)));
                    exit;
                } else {
                    $deposit_result['db_error'] = $koneksi->error;
                }
            }
        }
    }
}

// Get saldo saat ini
$balance_result = null;
if (isset($_GET['cek_saldo']) || !isset($_GET['success'])) {
    $balance_result = check_balance_orderkuota();
}

// Get history deposit dari database
$deposit_history = [];
$history_query = $koneksi->query("SELECT * FROM orderkuota_deposit ORDER BY tgl DESC, id_deposit DESC LIMIT 50");
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Deposit OrderKuota</title>
    <style>
        .deposit-card {
            border: 2px solid #007bff;
            border-radius: 10px;
            transition: all 0.3s;
        }
        .deposit-card:hover {
            box-shadow: 0 5px 15px rgba(0,123,255,0.2);
        }
        .quick-amount {
            cursor: pointer;
            transition: all 0.3s;
        }
        .quick-amount:hover {
            transform: scale(1.05);
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Tambah Deposit OrderKuota</h4>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                            <li class="breadcrumb-item"><a href="<?=base_url('orderkuota')?>" class="text-muted">OrderKuota</a></li>
                            <li class="breadcrumb-item text-muted active" aria-current="page">Deposit</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Success Message -->
        <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle"></i>
            <strong>Deposit Berhasil!</strong>
            <?php if (isset($_GET['amount'])): ?>
                <br>Jumlah: <strong>Rp <?=number_format($_GET['amount'], 0, ',', '.')?></strong>
            <?php endif; ?>
            <?php if (isset($_GET['ref_id'])): ?>
                <br>Reference ID: <strong><?=htmlspecialchars($_GET['ref_id'])?></strong>
            <?php endif; ?>
            <br><br>
            <a href="<?=base_url('orderkuota/deposit.php?cek_saldo=1')?>" class="btn btn-sm btn-info">
                <i class="fa fa-refresh"></i> Cek Saldo Sekarang
            </a>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if ($deposit_result && !$deposit_result['success']): ?>
        <div class="alert alert-modern alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle"></i>
            <div class="alert-modern-content">
                <strong>Deposit Gagal!</strong>
                <br><?=htmlspecialchars($deposit_result['message'] ?? 'Unknown error')?>
                <?php if (isset($deposit_result['error_code']) && $deposit_result['error_code'] == 'CONFIG_MISSING'): ?>
                    <br><br>
                    <a href="<?=base_url('admin/orderkuota_config.php')?>" class="btn btn-sm btn-danger">
                        <i class="fa fa-cog"></i> Konfigurasi API Sekarang
                    </a>
                <?php endif; ?>
            </div>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <!-- Warning jika API belum dikonfigurasi -->
        <?php
        $api = new OrderKuotaAPI();
        $config_status = $api->getConfigStatus();
        if (!$config_status['api_key_set'] || !$config_status['api_secret_set']):
        ?>
        <div class="alert alert-modern alert-warning alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-triangle"></i>
            <div class="alert-modern-content">
                <strong>API Belum Dikonfigurasi!</strong>
                <br>Untuk menggunakan fitur deposit OrderKuota, silakan konfigurasi API Key dan API Secret terlebih dahulu.
                <br><br>
                <a href="<?=base_url('admin/orderkuota_config.php')?>" class="btn btn-sm btn-warning">
                    <i class="fa fa-cog"></i> Konfigurasi API Sekarang
                </a>
            </div>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Form Deposit -->
            <div class="col-lg-8">
                <!-- Saldo Saat Ini -->
                <div class="modern-card mb-3">
                    <div class="modern-card-header">
                        <h4>
                            <i class="fa fa-wallet"></i> Saldo OrderKuota Saat Ini
                        </h4>
                    </div>
                    <div class="modern-card-body">
                        <?php if ($balance_result): ?>
                            <?php if ($balance_result['success']): ?>
                                <h2 class="text-success mb-0">Rp <?=number_format($balance_result['data']['balance'] ?? 0, 0, ',', '.')?></h2>
                            <?php else: ?>
                                <p class="text-danger"><?=htmlspecialchars($balance_result['message'] ?? 'Gagal mengambil saldo')?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted">Klik tombol di bawah untuk cek saldo</p>
                        <?php endif; ?>
                        <a href="?cek_saldo=1" class="btn btn-sm btn-info mt-2">
                            <i class="fa fa-refresh"></i> Refresh Saldo
                        </a>
                    </div>
                </div>

                <!-- Form Deposit -->
                <div class="modern-card deposit-card">
                    <div class="modern-card-header">
                        <h4>
                            <i class="fa fa-plus-circle"></i> Tambah Deposit
                        </h4>
                    </div>
                    <div class="modern-card-body">
                        <form method="POST" id="depositForm" class="form-modern">
                            <!-- Quick Amount -->
                            <div class="form-group">
                                <label>Pilih Jumlah Cepat</label>
                                <div class="row">
                                    <div class="col-6 col-md-3 mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-block quick-amount" onclick="setAmount(50000)">
                                            Rp 50.000
                                        </button>
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-block quick-amount" onclick="setAmount(100000)">
                                            Rp 100.000
                                        </button>
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-block quick-amount" onclick="setAmount(250000)">
                                            Rp 250.000
                                        </button>
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-block quick-amount" onclick="setAmount(500000)">
                                            Rp 500.000
                                        </button>
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-block quick-amount" onclick="setAmount(1000000)">
                                            Rp 1.000.000
                                        </button>
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-block quick-amount" onclick="setAmount(2000000)">
                                            Rp 2.000.000
                                        </button>
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-block quick-amount" onclick="setAmount(5000000)">
                                            Rp 5.000.000
                                        </button>
                                    </div>
                                    <div class="col-6 col-md-3 mb-2">
                                        <button type="button" class="btn btn-outline-primary btn-block quick-amount" onclick="setAmount(10000000)">
                                            Rp 10.000.000
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Jumlah Deposit -->
                            <div class="form-group">
                                <label for="amount">Jumlah Deposit <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">Rp</span>
                                    </div>
                                    <input type="number" class="form-control" name="amount" id="amount"
                                           placeholder="Minimal Rp 10.000" required min="10000" step="1000"
                                           oninput="formatAmount(this)">
                                </div>
                                <small class="form-text text-muted">
                                    Minimal deposit: Rp 10.000
                                </small>
                            </div>

                            <!-- Metode Pembayaran -->
                            <div class="form-group">
                                <label for="payment_method">Metode Pembayaran <span class="text-danger">*</span></label>
                                <select class="form-control" name="payment_method" id="payment_method" required onchange="updatePaymentMethod()">
                                    <optgroup label="Transfer Bank">
                                        <option value="transfer_bca">Transfer Bank - BCA</option>
                                        <option value="transfer_mandiri">Transfer Bank - Mandiri</option>
                                        <option value="transfer_bni">Transfer Bank - BNI</option>
                                        <option value="transfer_bri">Transfer Bank - BRI</option>
                                        <option value="transfer_cimb">Transfer Bank - CIMB Niaga</option>
                                        <option value="transfer_danamon">Transfer Bank - Danamon</option>
                                        <option value="transfer_permata">Transfer Bank - Permata</option>
                                        <option value="transfer_other">Transfer Bank - Lainnya</option>
                                    </optgroup>
                                    <optgroup label="Virtual Account">
                                        <option value="va_bca">Virtual Account - BCA</option>
                                        <option value="va_mandiri">Virtual Account - Mandiri</option>
                                        <option value="va_bni">Virtual Account - BNI</option>
                                        <option value="va_bri">Virtual Account - BRI</option>
                                        <option value="va_permata">Virtual Account - Permata</option>
                                    </optgroup>
                                    <optgroup label="E-Wallet">
                                        <option value="ewallet_dana">DANA</option>
                                        <option value="ewallet_ovo">OVO</option>
                                        <option value="ewallet_gopay">GoPay</option>
                                        <option value="ewallet_shopeepay">ShopeePay</option>
                                        <option value="ewallet_linkaja">LinkAja</option>
                                        <option value="ewallet_jenius">Jenius</option>
                                    </optgroup>
                                    <optgroup label="QRIS">
                                        <option value="qris">QRIS (Semua E-Wallet)</option>
                                    </optgroup>
                                    <optgroup label="Gerai Retail">
                                        <option value="retail_alfamart">Alfamart</option>
                                        <option value="retail_indomaret">Indomaret</option>
                                        <option value="retail_alfamidi">Alfamidi</option>
                                    </optgroup>
                                    <optgroup label="Lainnya">
                                        <option value="credit_card">Credit Card</option>
                                        <option value="debit_card">Debit Card</option>
                                    </optgroup>
                                </select>
                                <small class="form-text text-muted" id="payment_method_hint">
                                    Pilih metode pembayaran yang ingin digunakan
                                </small>
                            </div>

                            <!-- Info Metode Pembayaran -->
                            <div class="alert alert-modern alert-info" id="payment_method_info" style="display: none;">
                                <i class="fa fa-info-circle"></i>
                                <div class="alert-modern-content">
                                    <span id="payment_method_info_text"></span>
                                </div>
                            </div>

                            <!-- Keterangan -->
                            <div class="form-group">
                                <label for="keterangan">Keterangan (Opsional)</label>
                                <textarea class="form-control" name="keterangan" id="keterangan" rows="3"
                                          placeholder="Catatan tambahan untuk deposit ini"></textarea>
                            </div>

                            <!-- Submit -->
                            <button type="submit" name="deposit" class="btn btn-primary btn-sm btn-block">
                                <i class="fa fa-check"></i> Proses Deposit
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Info -->
                <div class="modern-card mt-3">
                    <div class="modern-card-header">
                        <h4>
                            <i class="fa fa-info-circle"></i> Informasi Deposit
                        </h4>
                    </div>
                    <div class="modern-card-body">
                        <ul class="mb-0">
                            <li>Minimal deposit: <strong>Rp 10.000</strong></li>
                            <li>Deposit akan langsung ditambahkan ke saldo OrderKuota Anda</li>
                            <li>Proses deposit biasanya memakan waktu 1-5 menit</li>
                            <li>Anda akan mendapatkan Reference ID untuk tracking</li>
                            <li>History deposit dapat dilihat di bawah</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Statistik -->
                <div class="modern-card">
                    <div class="modern-card-header">
                        <h4>
                            <i class="fa fa-chart-bar"></i> Statistik Deposit
                        </h4>
                    </div>
                    <div class="modern-card-body">
                        <div class="row text-center">
                            <div class="col-6 mb-3">
                                <h4 class="text-primary"><?=$stats['total_deposit']?></h4>
                                <small class="text-muted">Total Deposit</small>
                            </div>
                            <div class="col-6 mb-3">
                                <h4 class="text-success">Rp <?=number_format($stats['total_nominal'], 0, ',', '.')?></h4>
                                <small class="text-muted">Total Nominal</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-info"><?=$stats['deposit_bulan_ini']?></h4>
                                <small class="text-muted">Bulan Ini</small>
                            </div>
                            <div class="col-6">
                                <h4 class="text-warning">Rp <?=number_format($stats['nominal_bulan_ini'], 0, ',', '.')?></h4>
                                <small class="text-muted">Nominal Bulan Ini</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- History Deposit Terbaru -->
                <div class="quick-action-card-modern mt-3">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fa fa-history"></i> History Terbaru
                        </h5>
                        <?php if (empty($deposit_history)): ?>
                            <p class="text-muted text-center">Belum ada history deposit</p>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach (array_slice($deposit_history, 0, 5) as $hist): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>Rp <?=number_format($hist['amount'], 0, ',', '.')?></strong>
                                            <br><small class="text-muted"><?=date('d/m/Y H:i', strtotime($hist['tgl']))?></small>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge badge-<?=$hist['status'] == 'Berhasil' ? 'success' : 'warning'?>">
                                                <?=$hist['status']?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <a href="<?=base_url('orderkuota/deposit_history.php')?>" class="btn btn-sm btn-outline-primary btn-block mt-2">
                                <i class="fa fa-list"></i> Lihat Semua History
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function setAmount(amount) {
        document.getElementById('amount').value = amount;
        document.getElementById('amount').dispatchEvent(new Event('input'));
    }

    function formatAmount(input) {
        const value = input.value;
        if (value < 10000 && value > 0) {
            input.setCustomValidity('Minimal deposit adalah Rp 10.000');
        } else {
            input.setCustomValidity('');
        }
    }

    // Update payment method info
    function updatePaymentMethod() {
        const method = document.getElementById('payment_method').value;
        const infoDiv = document.getElementById('payment_method_info');
        const infoText = document.getElementById('payment_method_info_text');

        const methodInfo = {
            'transfer_bca': 'Transfer ke rekening BCA. Saldo akan masuk setelah transfer dikonfirmasi.',
            'transfer_mandiri': 'Transfer ke rekening Mandiri. Saldo akan masuk setelah transfer dikonfirmasi.',
            'transfer_bni': 'Transfer ke rekening BNI. Saldo akan masuk setelah transfer dikonfirmasi.',
            'transfer_bri': 'Transfer ke rekening BRI. Saldo akan masuk setelah transfer dikonfirmasi.',
            'transfer_cimb': 'Transfer ke rekening CIMB Niaga. Saldo akan masuk setelah transfer dikonfirmasi.',
            'transfer_danamon': 'Transfer ke rekening Danamon. Saldo akan masuk setelah transfer dikonfirmasi.',
            'transfer_permata': 'Transfer ke rekening Permata. Saldo akan masuk setelah transfer dikonfirmasi.',
            'transfer_other': 'Transfer ke rekening bank lainnya. Saldo akan masuk setelah transfer dikonfirmasi.',
            'va_bca': 'Bayar menggunakan Virtual Account BCA. Nomor VA akan diberikan setelah submit.',
            'va_mandiri': 'Bayar menggunakan Virtual Account Mandiri. Nomor VA akan diberikan setelah submit.',
            'va_bni': 'Bayar menggunakan Virtual Account BNI. Nomor VA akan diberikan setelah submit.',
            'va_bri': 'Bayar menggunakan Virtual Account BRI. Nomor VA akan diberikan setelah submit.',
            'va_permata': 'Bayar menggunakan Virtual Account Permata. Nomor VA akan diberikan setelah submit.',
            'ewallet_dana': 'Bayar menggunakan DANA. Redirect ke aplikasi DANA untuk pembayaran.',
            'ewallet_ovo': 'Bayar menggunakan OVO. Redirect ke aplikasi OVO untuk pembayaran.',
            'ewallet_gopay': 'Bayar menggunakan GoPay. Redirect ke aplikasi Gojek untuk pembayaran.',
            'ewallet_shopeepay': 'Bayar menggunakan ShopeePay. Redirect ke aplikasi Shopee untuk pembayaran.',
            'ewallet_linkaja': 'Bayar menggunakan LinkAja. Redirect ke aplikasi LinkAja untuk pembayaran.',
            'ewallet_jenius': 'Bayar menggunakan Jenius. Redirect ke aplikasi Jenius untuk pembayaran.',
            'qris': 'Bayar menggunakan QRIS. Scan QR code dengan aplikasi e-wallet atau mobile banking.',
            'retail_alfamart': 'Bayar tunai di gerai Alfamart terdekat. Bawa kode pembayaran ke kasir.',
            'retail_indomaret': 'Bayar tunai di gerai Indomaret terdekat. Bawa kode pembayaran ke kasir.',
            'retail_alfamidi': 'Bayar tunai di gerai Alfamidi terdekat. Bawa kode pembayaran ke kasir.',
            'credit_card': 'Bayar menggunakan Credit Card. Redirect ke payment gateway.',
            'debit_card': 'Bayar menggunakan Debit Card. Redirect ke payment gateway.'
        };

        if (methodInfo[method]) {
            infoText.textContent = methodInfo[method];
            infoDiv.style.display = 'block';
        } else {
            infoDiv.style.display = 'none';
        }
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        updatePaymentMethod();
    });

    // Handle form submit
    document.getElementById('depositForm').addEventListener('submit', function(e) {
        const amount = parseFloat(document.getElementById('amount').value);

        if (!amount || amount < 10000) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Jumlah Tidak Valid!',
                text: 'Minimal deposit adalah Rp 10.000',
                confirmButtonColor: '#ffc107'
            });
            return false;
        }

        e.preventDefault();

        Swal.fire({
            title: 'Konfirmasi Deposit',
            html: '<div style="text-align: left;">' +
                  '<p><strong>Jumlah Deposit:</strong> Rp ' + parseInt(amount).toLocaleString('id-ID') + '</p>' +
                  '<p><strong>Metode Pembayaran:</strong> ' + document.getElementById('payment_method').options[document.getElementById('payment_method').selectedIndex].text + '</p>' +
                  '</div>',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Ya, Proses Deposit!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Memproses Deposit...',
                    text: 'Mohon tunggu sebentar',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Submit form
                this.submit();
            }
        });
    });
    </script>

    <?php
    include_once('../footer.php');
    ?>





