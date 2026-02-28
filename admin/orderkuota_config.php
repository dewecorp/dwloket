<?php
$page_title = 'OrderKuota Config';
include_once('../header.php');
include_once('../config/config.php');
require_once '../libs/orderkuota_api.php';

// Hanya admin yang bisa akses
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header('Location: ' . base_url('home'));
    exit;
}

$config_file = __DIR__ . '/../config/orderkuota_config.php';
$message = '';
$message_type = '';

// Handle save configuration
if (isset($_POST['save_config'])) {
    $api_url = trim($_POST['api_url'] ?? 'https://h2h.okeconnect.com/trx');
    $user_id = trim($_POST['user_id'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $api_key = trim($_POST['api_key'] ?? '');
    $api_secret = trim($_POST['api_secret'] ?? '');
    $api_token = trim($_POST['api_token'] ?? '');
    $callback_url = trim($_POST['callback_url'] ?? 'https://bukaolshop.net/callback/okeconnect/event');
    $timeout = (int)($_POST['timeout'] ?? 30);

    // Validasi - bisa menggunakan (User ID + Password), token, ATAU (key + secret)
    if (empty($user_id) && empty($api_token) && (empty($api_key) || empty($api_secret))) {
        $message = 'Harus diisi: (User ID + Password) ATAU API Token ATAU (API Key dan API Secret)!';
        $message_type = 'danger';
    } else {
        // Baca template config
        $config_content = "<?php
/**
 * OrderKuota API Configuration
 * Konfigurasi API untuk integrasi OrderKuota.com
 *
 * INSTRUKSI:
 * 1. Isi API_KEY dengan API key dari OrderKuota.com
 * 2. Isi API_SECRET dengan API secret dari OrderKuota.com
 * 3. API_URL biasanya tidak perlu diubah kecuali OrderKuota memberikan URL khusus
 * 4. Pastikan file ini tidak bisa diakses langsung dari browser (sudah ada .htaccess protection)
 */

// API Configuration OkeConnect
define('ORDERKUOTA_API_URL', '" . addslashes($api_url) . "');
// OkeConnect menggunakan User ID dan Password untuk autentikasi
define('ORDERKUOTA_USER_ID', '" . addslashes($user_id ?? '') . "');
define('ORDERKUOTA_PASSWORD', '" . addslashes($password ?? '') . "');
// Untuk backward compatibility (tidak digunakan jika User ID dan Password diisi)
define('ORDERKUOTA_API_KEY', '" . addslashes($api_key) . "');
define('ORDERKUOTA_API_SECRET', '" . addslashes($api_secret) . "');
// API Token (alternatif autentikasi menggunakan Bearer token)
define('ORDERKUOTA_API_TOKEN', '" . addslashes($api_token) . "');
// Callback URL untuk menerima notifikasi dari API
define('ORDERKUOTA_CALLBACK_URL', '" . addslashes($callback_url) . "');

// Timeout untuk request (dalam detik)
define('ORDERKUOTA_TIMEOUT', " . $timeout . ");

// Enable/Disable logging
define('ORDERKUOTA_ENABLE_LOG', true);

// Default produk jika API tidak mengembalikan daftar produk
\$ORDERKUOTA_DEFAULT_PRODUCTS = [
    ['code' => 'PLN', 'name' => 'Token PLN'],
    ['code' => 'PULSA', 'name' => 'Pulsa'],
    ['code' => 'DATA', 'name' => 'Paket Data'],
    ['code' => 'BPJS', 'name' => 'BPJS Kesehatan'],
    ['code' => 'PDAM', 'name' => 'PDAM'],
    ['code' => 'EMONEY', 'name' => 'E-Money'],
    ['code' => 'VOUCHER', 'name' => 'Voucher Game'],
];

?>";

        // Tulis ke file
        if (file_put_contents($config_file, $config_content)) {
            // Hapus cache opcache jika ada
            if (function_exists('opcache_invalidate')) {
                opcache_invalidate($config_file, true);
            }

            // Log aktivitas
            require_once '../libs/log_activity.php';
            log_activity('update', 'orderkuota_config', 'Mengupdate konfigurasi OrderKuota API');

            // Redirect untuk refresh halaman dan load config baru (menghindari cache)
            $redirect_url = $_SERVER['PHP_SELF'] . '?saved=1&t=' . time();
            header('Location: ' . $redirect_url);
            exit;
        } else {
            $message = 'Gagal menyimpan konfigurasi. Pastikan folder config dapat ditulis.';
            $message_type = 'danger';
        }
    }
}

// Tampilkan pesan success jika baru saja save
if (isset($_GET['saved'])) {
    $message = 'Konfigurasi berhasil disimpan!';
    $message_type = 'success';
}

// Reload config file untuk memastikan membaca nilai terbaru
$config_file_path = __DIR__ . '/../config/orderkuota_config.php';
if (file_exists($config_file_path)) {
    // Hapus dari cache jika sudah pernah di-require
    if (function_exists('opcache_invalidate')) {
        opcache_invalidate($config_file_path, true);
    }
    // Reload config jika belum didefine
    if (!defined('ORDERKUOTA_API_URL')) {
        require_once $config_file_path;
    }
}

// Baca konfigurasi saat ini
$current_config = [
    'api_url' => defined('ORDERKUOTA_API_URL') ? ORDERKUOTA_API_URL : 'https://h2h.okeconnect.com/trx',
    'user_id' => defined('ORDERKUOTA_USER_ID') ? ORDERKUOTA_USER_ID : '',
    'password' => defined('ORDERKUOTA_PASSWORD') ? ORDERKUOTA_PASSWORD : '',
    'api_key' => defined('ORDERKUOTA_API_KEY') ? ORDERKUOTA_API_KEY : '',
    'api_secret' => defined('ORDERKUOTA_API_SECRET') ? ORDERKUOTA_API_SECRET : '',
    'api_token' => defined('ORDERKUOTA_API_TOKEN') ? ORDERKUOTA_API_TOKEN : '',
    'callback_url' => defined('ORDERKUOTA_CALLBACK_URL') ? ORDERKUOTA_CALLBACK_URL : 'https://bukaolshop.net/callback/okeconnect/event',
    'timeout' => defined('ORDERKUOTA_TIMEOUT') ? ORDERKUOTA_TIMEOUT : 30,
];

// Test koneksi API
$test_result = null;
if (isset($_GET['test'])) {
    $api = new OrderKuotaAPI();
    $test_result = $api->checkBalance();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfigurasi OrderKuota API</title>
</head>
<body>
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Konfigurasi OrderKuota API</h4>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                            <li class="breadcrumb-item"><a href="<?=base_url('admin/backup.php')?>" class="text-muted">Admin</a></li>
                            <li class="breadcrumb-item text-muted active" aria-current="page">OrderKuota Config</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <?php if ($message): ?>
        <div class="alert alert-<?=$message_type?> alert-dismissible fade show" role="alert">
            <i class="fa fa-<?=$message_type == 'success' ? 'check-circle' : 'exclamation-circle'?>"></i>
            <?=htmlspecialchars($message)?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-8">
                <div class="modern-card">
                    <div class="modern-card-header">
                        <h4>
                            <i class="fa fa-cog"></i> Konfigurasi API
                        </h4>
                    </div>
                    <div class="modern-card-body">
                        <form method="POST" id="configForm" class="form-modern">
                            <!-- API URL -->
                            <div class="form-group">
                                <label for="api_url">API URL <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="api_url" id="api_url"
                                       value="<?=htmlspecialchars($current_config['api_url'])?>"
                                       placeholder="https://h2h.okeconnect.com/trx" required>
                                <small class="form-text text-muted">
                                    URL endpoint API OkeConnect (contoh: https://h2h.okeconnect.com/trx)
                                </small>
                            </div>

                            <!-- User ID (OkeConnect) -->
                            <div class="form-group">
                                <label for="user_id">User ID (OkeConnect)</label>
                                <input type="text" class="form-control" name="user_id" id="user_id"
                                       value="<?=htmlspecialchars($current_config['user_id'] ?? '')?>"
                                       placeholder="OK96961">
                                <small class="form-text text-muted">
                                    User ID dari dashboard OkeConnect (untuk autentikasi H2H API)
                                </small>
                            </div>

                            <!-- Password (OkeConnect) -->
                            <div class="form-group">
                                <label for="password">Password (OkeConnect)</label>
                                <input type="password" class="form-control" name="password" id="password"
                                       value="<?=htmlspecialchars($current_config['password'] ?? '')?>"
                                       placeholder="Masukkan password dari dashboard OkeConnect">
                                <small class="form-text text-muted">
                                    Password dari dashboard OkeConnect (untuk autentikasi H2H API)
                                </small>
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" id="show_password" onchange="togglePassword()">
                                    <label class="form-check-label" for="show_password">Tampilkan Password</label>
                                </div>
                            </div>

                            <!-- API Key (Backward Compatibility) -->
                            <div class="form-group">
                                <label for="api_key">API Key (Opsional - untuk API lain)</label>
                                <input type="text" class="form-control" name="api_key" id="api_key"
                                       value="<?=htmlspecialchars($current_config['api_key'])?>"
                                       placeholder="Masukkan API Key (opsional)">
                                <small class="form-text text-muted">
                                    Untuk API yang menggunakan API Key + Secret (opsional jika sudah menggunakan User ID + Password)
                                </small>
                            </div>

                            <!-- API Secret -->
                            <div class="form-group">
                                <label for="api_secret">API Secret</label>
                                <input type="password" class="form-control" name="api_secret" id="api_secret"
                                       value="<?=htmlspecialchars($current_config['api_secret'])?>"
                                       placeholder="Masukkan API Secret dari OrderKuota.com">
                                <small class="form-text text-muted">
                                    Dapatkan API Secret dari dashboard OrderKuota.com (opsional jika menggunakan Token)
                                </small>
                                <div class="form-check mt-2">
                                    <input type="checkbox" class="form-check-input" id="show_secret" onchange="toggleSecret()">
                                    <label class="form-check-label" for="show_secret">Tampilkan API Secret</label>
                                </div>
                            </div>

                            <!-- API Token -->
                            <div class="form-group">
                                <label for="api_token">API Token (Alternatif)</label>
                                <input type="text" class="form-control" name="api_token" id="api_token"
                                       value="<?=htmlspecialchars($current_config['api_token'] ?? '')?>"
                                       placeholder="Masukkan API Token (Bearer token)">
                                <small class="form-text text-muted">
                                    Token API untuk autentikasi menggunakan Bearer token. Jika diisi, akan digunakan sebagai metode autentikasi utama (alternatif dari API Key + Secret).
                                </small>
                            </div>

                            <!-- Callback URL -->
                            <div class="form-group">
                                <label for="callback_url">Callback URL</label>
                                <input type="text" class="form-control" name="callback_url" id="callback_url"
                                       value="<?=htmlspecialchars($current_config['callback_url'] ?? '')?>"
                                       placeholder="https://bukaolshop.net/callback/okeconnect/event">
                                <small class="form-text text-muted">
                                    URL untuk menerima notifikasi callback dari API setelah transaksi selesai.
                                </small>
                            </div>

                            <!-- Timeout -->
                            <div class="form-group">
                                <label for="timeout">Timeout (detik)</label>
                                <input type="number" class="form-control" name="timeout" id="timeout"
                                       value="<?=$current_config['timeout']?>" min="10" max="120" required>
                                <small class="form-text text-muted">
                                    Waktu maksimal untuk request API (default: 30 detik)
                                </small>
                            </div>

                            <!-- Tombol Simpan -->
                            <div class="form-group">
                                <button type="submit" name="save_config" class="btn btn-success btn-lg">
                                    <i class="fa fa-save"></i> Simpan Konfigurasi
                                </button>
                                <a href="?test=1" class="btn btn-info btn-lg ml-2">
                                    <i class="fa fa-plug"></i> Test Koneksi
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Info dan Test Result -->
            <div class="col-lg-4">
                <!-- Status Konfigurasi -->
                <div class="quick-action-card-modern">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fa fa-info-circle"></i> Status Konfigurasi
                        </h5>
                        <?php
                        $api = new OrderKuotaAPI();
                        $status = $api->getConfigStatus();
                        ?>
                        <ul class="list-unstyled">
                            <li>
                                <strong>API URL:</strong><br>
                                <code><?=htmlspecialchars($status['api_url'])?></code>
                            </li>
                            <li class="mt-2">
                                <strong>User ID:</strong><br>
                                <?php if (!empty($status['user_id'])): ?>
                                    <span class="badge badge-success"><?=htmlspecialchars($status['user_id'])?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Belum Diisi</span>
                                <?php endif; ?>
                            </li>
                            <li class="mt-2">
                                <strong>Password:</strong><br>
                                <?php if ($status['password_set'] ?? false): ?>
                                    <span class="badge badge-success">Sudah Diisi</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Belum Diisi</span>
                                <?php endif; ?>
                            </li>
                            <li class="mt-2">
                                <strong>API Key:</strong><br>
                                <?php if ($status['api_key_set']): ?>
                                    <span class="badge badge-success">Sudah Diisi</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Belum Diisi</span>
                                <?php endif; ?>
                            </li>
                            <li class="mt-2">
                                <strong>API Secret:</strong><br>
                                <?php if ($status['api_secret_set']): ?>
                                    <span class="badge badge-success">Sudah Diisi</span>
                                <?php else: ?>
                                    <span class="badge badge-danger">Belum Diisi</span>
                                <?php endif; ?>
                            </li>
                            <li class="mt-2">
                                <strong>API Token:</strong><br>
                                <?php if ($status['api_token_set']): ?>
                                    <span class="badge badge-success">Sudah Diisi</span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Belum Diisi</span>
                                <?php endif; ?>
                            </li>
                            <li class="mt-2">
                                <strong>Metode Auth:</strong><br>
                                <span class="badge badge-info"><?=htmlspecialchars($status['auth_method'])?></span>
                            </li>
                            <li class="mt-2">
                                <strong>Callback URL:</strong><br>
                                <?php if (!empty($status['callback_url'])): ?>
                                    <code style="font-size: 0.85em;"><?=htmlspecialchars($status['callback_url'])?></code>
                                <?php else: ?>
                                    <span class="badge badge-warning">Belum Diisi</span>
                                <?php endif; ?>
                            </li>
                            <li class="mt-2">
                                <strong>Timeout:</strong> <?=$status['timeout']?> detik
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Test Result -->
                <?php if ($test_result): ?>
                <div class="quick-action-card-modern mt-3">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fa fa-plug"></i> Hasil Test Koneksi
                        </h5>
                        <?php if ($test_result['success']): ?>
                            <div class="alert alert-success">
                                <strong>Koneksi Berhasil!</strong><br>
                                <?php if (isset($test_result['data']['balance'])): ?>
                                    Saldo: Rp <?=number_format($test_result['data']['balance'], 0, ',', '.')?>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger">
                                <strong>Koneksi Gagal!</strong><br>
                                <?=htmlspecialchars($test_result['message'] ?? 'Unknown error')?>
                                <?php if (isset($test_result['http_code'])): ?>
                                    <br><small>HTTP Code: <?=$test_result['http_code']?></small>
                                <?php endif; ?>
                                <?php if (isset($test_result['url'])): ?>
                                    <br><small>URL: <?=htmlspecialchars($test_result['url'])?></small>
                                <?php endif; ?>
                                <?php if (isset($test_result['raw_response']) && !empty($test_result['raw_response'])): ?>
                                    <br><small>Response: <?=htmlspecialchars(substr($test_result['raw_response'], 0, 200))?></small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Panduan -->
                <div class="card mt-3">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="fa fa-question-circle"></i> Panduan Setup API
                        </h5>
                        <ol class="small">
                            <li>Login ke dashboard OrderKuota.com</li>
                            <li>Buka menu API atau Settings</li>
                            <li>Copy API Key dan API Secret</li>
                            <li>Paste ke form di sebelah kiri</li>
                            <li>Klik "Simpan Konfigurasi"</li>
                            <li>Klik "Test Koneksi" untuk memastikan API bekerja</li>
                        </ol>

                        <hr>

                        <h6 class="mt-3"><strong>Checklist Setelah Setup:</strong></h6>
                        <div class="alert alert-info small">
                            <i class="fa fa-check-circle"></i> <strong>Pastikan semua ini berfungsi:</strong>
                            <ul class="mb-0 mt-2">
                                <li>✅ Test Koneksi berhasil (menampilkan saldo)</li>
                                <li>✅ Cek Harga produk berfungsi</li>
                                <li>✅ Cek Saldo menampilkan saldo yang benar</li>
                                <li>✅ Pembayaran test dengan nominal kecil berhasil</li>
                            </ul>
                        </div>

                        <h6 class="mt-3"><strong>Informasi Produk:</strong></h6>
                        <div class="alert alert-warning small">
                            <i class="fa fa-info-circle"></i> <strong>Tentang Daftar Produk:</strong>
                            <ul class="mb-0 mt-2">
                                <li>✅ Sistem akan <strong>otomatis mengambil produk dari API</strong> OrderKuota jika credentials sudah dikonfigurasi</li>
                                <li>✅ Jika API tidak tersedia, sistem akan menggunakan <strong>daftar produk dari config</strong> sebagai fallback</li>
                                <li>✅ Daftar produk di config sudah mencakup produk umum: PLN, Pulsa, Data, BPJS, PDAM, E-Wallet, Voucher Game, dll</li>
                                <li>✅ Jika kode produk OrderKuota berbeda, produk dari API akan otomatis digunakan</li>
                                <li>✅ Untuk mengupdate daftar produk manual, edit file <code>config/orderkuota_config.php</code></li>
                            </ul>
                        </div>

                        <h6 class="mt-3"><strong>Kemungkinan Masalah:</strong></h6>
                        <ul class="small">
                            <li><strong>API Key/Secret salah:</strong> Pastikan tidak ada spasi saat copy-paste</li>
                            <li><strong>Saldo tidak cukup:</strong> Pastikan saldo OrderKuota mencukupi</li>
                            <li><strong>Koneksi timeout:</strong> Periksa koneksi internet atau tingkatkan timeout</li>
                            <li><strong>Kode produk tidak dikenali:</strong> Sistem akan menggunakan produk dari API jika tersedia, atau gunakan kode produk sesuai dokumentasi OrderKuota</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function toggleSecret() {
        const secretInput = document.getElementById('api_secret');
        const showCheckbox = document.getElementById('show_secret');

        if (showCheckbox.checked) {
            secretInput.type = 'text';
        } else {
            secretInput.type = 'password';
        }
    }

    function togglePassword() {
        const passwordInput = document.getElementById('password');
        const showCheckbox = document.getElementById('show_password');

        if (showCheckbox.checked) {
            passwordInput.type = 'text';
        } else {
            passwordInput.type = 'password';
        }
    }

    // Handle form submit
    document.getElementById('configForm').addEventListener('submit', function(e) {
        const api_key = document.getElementById('api_key').value;
        const api_secret = document.getElementById('api_secret').value;

        if (!api_key || !api_secret) {
            e.preventDefault();
            Swal.fire({
                icon: 'warning',
                title: 'Data Belum Lengkap!',
                text: 'API Key dan API Secret harus diisi',
                confirmButtonColor: '#ffc107'
            });
            return false;
        }

        Swal.fire({
            title: 'Menyimpan Konfigurasi...',
            html: 'Mohon tunggu',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });
    });
    </script>

    <?php
    include_once('../footer.php');
    ?>





