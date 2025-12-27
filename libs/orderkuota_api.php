<?php
/**
 * OrderKuota.com API Integration
 * Integrasi untuk melakukan pembayaran langsung tanpa membuka aplikasi orderkuota
 */

// Load configuration
if (file_exists(__DIR__ . '/../config/orderkuota_config.php')) {
    require_once __DIR__ . '/../config/orderkuota_config.php';
}

class OrderKuotaAPI {
    private $api_url;
    private $api_key;
    private $api_secret;
    private $api_token;
    private $user_id;
    private $password;
    private $callback_url;
    private $timeout;

    public function __construct($api_key = null, $api_secret = null, $api_url = null) {
        // Load dari config file jika ada
        if (file_exists(__DIR__ . '/../config/orderkuota_config.php')) {
            require_once __DIR__ . '/../config/orderkuota_config.php';
        }

        // Set API URL
        if ($api_url) {
            $this->api_url = $api_url;
        } else {
            $this->api_url = defined('ORDERKUOTA_API_URL') ? ORDERKUOTA_API_URL : 'https://orderkuota.com/api/';
        }

        // Set API Key
        if ($api_key) {
            $this->api_key = $api_key;
        } else {
            $this->api_key = defined('ORDERKUOTA_API_KEY') ? ORDERKUOTA_API_KEY : '';
        }

        // Set API Secret
        if ($api_secret) {
            $this->api_secret = trim($api_secret);
        } else {
            $this->api_secret = defined('ORDERKUOTA_API_SECRET') ? trim(ORDERKUOTA_API_SECRET) : '';
        }

        // Set API Token (jika menggunakan token-based auth)
        $this->api_token = defined('ORDERKUOTA_API_TOKEN') ? trim(ORDERKUOTA_API_TOKEN) : '';

        // Set User ID dan Password (untuk OkeConnect H2H API)
        $this->user_id = defined('ORDERKUOTA_USER_ID') ? trim(ORDERKUOTA_USER_ID) : '';
        $this->password = defined('ORDERKUOTA_PASSWORD') ? trim(ORDERKUOTA_PASSWORD) : '';

        // Set Callback URL
        $this->callback_url = defined('ORDERKUOTA_CALLBACK_URL') ? ORDERKUOTA_CALLBACK_URL : '';

        // Set Timeout
        $this->timeout = defined('ORDERKUOTA_TIMEOUT') ? ORDERKUOTA_TIMEOUT : 30;

        // Validasi - sekarang token juga bisa digunakan sebagai alternatif
        // Logging hanya jika ORDERKUOTA_ENABLE_LOG aktif
        if (empty($this->api_token) && (empty($this->api_key) || empty($this->api_secret))) {
            if (defined('ORDERKUOTA_ENABLE_LOG') && ORDERKUOTA_ENABLE_LOG) {
            }
        }
    }

    /**
     * Get API configuration status
     */
    public function getConfigStatus() {
        $auth_method = 'none';
        if (!empty($this->user_id) && !empty($this->password)) {
            $auth_method = 'userid+password';
        } elseif (!empty($this->api_token)) {
            $auth_method = 'token';
        } elseif (!empty($this->api_key) && !empty($this->api_secret)) {
            $auth_method = 'key+signature';
        }

        return [
            'api_url' => $this->api_url,
            'user_id' => $this->user_id ?? '',
            'user_id_set' => !empty($this->user_id),
            'password_set' => !empty($this->password),
            'api_key_set' => !empty($this->api_key),
            'api_secret_set' => !empty($this->api_secret),
            'api_token_set' => !empty($this->api_token),
            'callback_url' => $this->callback_url ?? '',
            'api_key_length' => strlen($this->api_key ?? ''),
            'api_secret_length' => strlen($this->api_secret ?? ''),
            'api_token_length' => strlen($this->api_token ?? ''),
            'auth_method' => $auth_method,
            'timeout' => $this->timeout
        ];
    }

    /**
     * Test koneksi ke API
     */
    public function testConnection() {
        if (empty($this->user_id) && empty($this->api_token) && (empty($this->api_key) || empty($this->api_secret))) {
            return [
                'success' => false,
                'message' => 'User ID + Password, API Token, atau (API Key dan Secret) belum dikonfigurasi'
            ];
        }

        // Test dengan cek saldo (endpoint yang ringan)
        return $this->checkBalance();
    }

    /**
     * Cek saldo orderkuota
     */
    public function checkBalance() {
        // Validasi API credentials - bisa userid+password, token, atau key+secret
        if (empty($this->user_id) && empty($this->api_token) && (empty($this->api_key) || empty($this->api_secret))) {
            return [
                'success' => false,
                'message' => 'User ID + Password, API Token, atau (API Key dan Secret) belum dikonfigurasi. Silakan isi di halaman Admin > OrderKuota Config',
                'error_code' => 'CONFIG_MISSING'
            ];
        }

        // OkeConnect H2H API biasanya menggunakan endpoint /cek-saldo atau langsung di /trx dengan action
        // Coba beberapa kemungkinan endpoint
        // 1. Coba dengan action=cek_saldo atau action=balance
        $params = ['action' => 'cek_saldo'];
        $result = $this->makeRequest('', 'POST', $params);
        if ($result['success']) {
            return $result;
        }

        // 2. Coba dengan action=balance
        $params = ['action' => 'balance'];
        $result = $this->makeRequest('', 'POST', $params);
        if ($result['success']) {
            return $result;
        }

        // 3. Coba endpoint balance (GET method)
        $result = $this->makeRequest('balance', 'GET');
        if ($result['success']) {
            return $result;
        }

        // Return hasil terakhir (akan berisi error message)
        return $result;
    }

    /**
     * Cek harga produk
     *
     * @param string $product_code - Kode produk (PLN, PULSA, dll)
     * @param string $target - Nomor tujuan
     */
    public function checkPrice($product_code, $target) {
        // Validasi API credentials
        if (empty($this->api_key) || empty($this->api_secret)) {
            return [
                'success' => false,
                'message' => 'API Key atau API Secret belum dikonfigurasi. Silakan isi di halaman Admin > OrderKuota Config',
                'error_code' => 'CONFIG_MISSING'
            ];
        }

        // Validasi parameter
        if (empty($product_code)) {
            return [
                'success' => false,
                'message' => 'Kode produk tidak boleh kosong',
                'error_code' => 'INVALID_PRODUCT'
            ];
        }

        if (empty($target)) {
            return [
                'success' => false,
                'message' => 'Nomor tujuan tidak boleh kosong',
                'error_code' => 'INVALID_TARGET'
            ];
        }

        $params = [
            'product_code' => $product_code,
            'target' => $target
        ];
        return $this->makeRequest('price', 'GET', $params);
    }

    /**
     * Melakukan pembayaran/transaksi
     *
     * @param string $product_code - Kode produk
     * @param string $target - Nomor tujuan
     * @param string $ref_id - Reference ID (unik untuk setiap transaksi)
     */
    public function pay($product_code, $target, $ref_id = null) {
        // Validasi API credentials - bisa token atau key+secret
        if (empty($this->api_token) && (empty($this->api_key) || empty($this->api_secret))) {
            return [
                'success' => false,
                'message' => 'API Token atau (API Key dan Secret) belum dikonfigurasi. Silakan isi di halaman Admin > OrderKuota Config',
                'error_code' => 'CONFIG_MISSING'
            ];
        }

        // Validasi parameter
        if (empty($product_code)) {
            return [
                'success' => false,
                'message' => 'Kode produk tidak boleh kosong',
                'error_code' => 'INVALID_PRODUCT'
            ];
        }

        if (empty($target)) {
            return [
                'success' => false,
                'message' => 'Nomor tujuan tidak boleh kosong',
                'error_code' => 'INVALID_TARGET'
            ];
        }

        if (!$ref_id) {
            $ref_id = 'DWLOKET_' . time() . '_' . rand(1000, 9999);
        }

        $params = [
            'product_code' => $product_code,
            'target' => $target,
            'ref_id' => $ref_id
        ];

        // Tambahkan callback URL jika tersedia
        if (!empty($this->callback_url)) {
            $params['callback_url'] = $this->callback_url;
        }

        return $this->makeRequest('pay', 'POST', $params);
    }

    /**
     * Cek status transaksi
     *
     * @param string $ref_id - Reference ID transaksi
     */
    public function checkStatus($ref_id) {
        $params = [
            'ref_id' => $ref_id
        ];
        return $this->makeRequest('status', 'GET', $params);
    }

    /**
     * Get daftar produk
     */
    public function getProducts() {
        return $this->makeRequest('products', 'GET');
    }

    /**
     * Tambah deposit/topup saldo OrderKuota
     *
     * @param float $amount - Jumlah deposit
     * @param string $payment_method - Metode pembayaran (optional)
     * @param string $ref_id - Reference ID (optional)
     * @return array - Response dari API
     */
    public function deposit($amount, $payment_method = null, $ref_id = null) {
        // Validasi API credentials - bisa token atau key+secret
        if (empty($this->api_token) && (empty($this->api_key) || empty($this->api_secret))) {
            return [
                'success' => false,
                'message' => 'API Token atau (API Key dan Secret) belum dikonfigurasi. Silakan isi di halaman Admin > OrderKuota Config',
                'error_code' => 'CONFIG_MISSING'
            ];
        }

        // Validasi parameter
        if (empty($amount) || $amount <= 0) {
            return [
                'success' => false,
                'message' => 'Jumlah deposit tidak valid. Minimal deposit adalah Rp 10.000',
                'error_code' => 'INVALID_AMOUNT'
            ];
        }

        if ($amount < 10000) {
            return [
                'success' => false,
                'message' => 'Minimal deposit adalah Rp 10.000',
                'error_code' => 'MIN_AMOUNT'
            ];
        }

        if (!$ref_id) {
            $ref_id = 'DEPOSIT_' . date('YmdHis') . '_' . rand(1000, 9999);
        }

        $params = [
            'amount' => $amount,
            'ref_id' => $ref_id
        ];

        if ($payment_method) {
            $params['payment_method'] = $payment_method;
        }

        // Tambahkan callback URL jika tersedia
        if (!empty($this->callback_url)) {
            $params['callback_url'] = $this->callback_url;
        }

        return $this->makeRequest('deposit', 'POST', $params);
    }

    /**
     * Get history deposit
     *
     * @param int $limit - Jumlah data (default: 50)
     * @param int $offset - Offset (default: 0)
     * @return array - Response dari API
     */
    public function getDepositHistory($limit = 50, $offset = 0) {
        // Validasi API credentials
        if (empty($this->api_key) || empty($this->api_secret)) {
            return [
                'success' => false,
                'message' => 'API Key atau API Secret belum dikonfigurasi. Silakan isi di halaman Admin > OrderKuota Config',
                'error_code' => 'CONFIG_MISSING'
            ];
        }

        $params = [
            'limit' => $limit,
            'offset' => $offset
        ];

        return $this->makeRequest('deposit/history', 'GET', $params);
    }

    /**
     * Make API request
     */
    private function makeRequest($endpoint, $method = 'GET', $params = []) {
        // Pastikan URL dan endpoint di-format dengan benar
        $base_url = rtrim($this->api_url, '/');
        $endpoint_path = ltrim($endpoint, '/');
        $url = $base_url . '/' . $endpoint_path;

        // Setup headers
        $headers = [
            'Content-Type: application/x-www-form-urlencoded',
            'User-Agent: DW-Loket/1.0'
        ];

        // Prioritas: User ID + Password (OkeConnect H2H) > Token > API Key + Secret
        if (!empty($this->user_id) && !empty($this->password)) {
            // OkeConnect H2H menggunakan userid dan password sebagai POST parameter
            $params['userid'] = $this->user_id;
            $params['password'] = $this->password;
        } elseif (!empty($this->api_token)) {
            // Jika menggunakan token, coba beberapa format
            // Coba sebagai Bearer token di header (standar)
            $headers[] = 'Authorization: Bearer ' . $this->api_token;
            // Juga tambahkan sebagai query parameter (beberapa API memerlukan ini)
            $params['token'] = $this->api_token;
        } else {
            // Jika tidak ada token, gunakan API key + signature (metode lama)
            if (!empty($this->api_key)) {
                $params['api_key'] = $this->api_key;
                $params['timestamp'] = time();

                // Generate signature jika ada secret
                if (!empty($this->api_secret)) {
                    $signature = $this->generateSignature($params);
                    $params['signature'] = $signature;
                }
            }
        }

        $ch = curl_init();

        if ($method == 'GET') {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            if (!empty($params)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return [
                'success' => false,
                'message' => 'CURL Error: ' . $error
            ];
        }

        $data = json_decode($response, true);

        if (!$data) {
            // Jika response kosong
            if (empty($response)) {
                return [
                    'success' => false,
                    'message' => 'Empty response from API. HTTP Code: ' . $http_code . '. URL: ' . $url,
                    'raw_response' => $response,
                    'http_code' => $http_code,
                    'url' => $url
                ];
            }

            // Cek apakah response adalah HTML error page (biasanya 404)
            if (strpos($response, '<html') !== false || strpos($response, '<!DOCTYPE') !== false || strpos(strtolower($response), 'file not found') !== false) {
                $error_msg = 'File not found';
                if (preg_match('/<title>(.*?)<\/title>/i', $response, $matches)) {
                    $error_msg = trim($matches[1]);
                } elseif (preg_match('/<h1[^>]*>(.*?)<\/h1>/i', $response, $matches)) {
                    $error_msg = trim(strip_tags($matches[1]));
                }

                return [
                    'success' => false,
                    'message' => 'Endpoint tidak ditemukan (404). URL yang digunakan: ' . $url . '. Error: ' . $error_msg . '. Pastikan endpoint API benar atau hubungi support OrderKuota untuk dokumentasi endpoint yang benar.',
                    'raw_response' => substr($response, 0, 500),
                    'http_code' => $http_code ?: 404,
                    'url' => $url
                ];
            }

            // Coba parse sebagai plain text atau format lain
            if (strpos($response, 'success') !== false || strpos($response, 'error') !== false) {
                return [
                    'success' => false,
                    'message' => 'Response format tidak dikenali: ' . substr($response, 0, 200) . ' (HTTP ' . $http_code . ')',
                    'raw_response' => $response,
                    'http_code' => $http_code,
                    'url' => $url
                ];
            }

            return [
                'success' => false,
                'message' => 'Invalid response from API: ' . substr($response, 0, 200) . ' (HTTP ' . $http_code . '). URL: ' . $url,
                'raw_response' => $response,
                'http_code' => $http_code,
                'url' => $url
            ];
        }

        // Normalize response format
        if (!isset($data['success'])) {
            // Jika response tidak punya field success, cek field lain
            if (isset($data['status']) && $data['status'] == 'success') {
                $data['success'] = true;
            } elseif (isset($data['error'])) {
                $data['success'] = false;
                $data['message'] = $data['error'];
            } else {
                // Assume success jika ada data
                $data['success'] = isset($data['data']) || isset($data['balance']) || isset($data['price']);
            }
        }

        // Jika HTTP code tidak 200, anggap sebagai error
        if ($http_code != 200) {
            $data['success'] = false;
            $data['message'] = 'HTTP Error ' . $http_code . ': ' . ($data['message'] ?? $data['error'] ?? 'Request failed. Endpoint mungkin tidak ditemukan atau autentikasi gagal.');
            $data['http_code'] = $http_code;
            $data['url'] = $url;

            // Untuk HTTP 404, tambahkan pesan yang lebih jelas
            if ($http_code == 404) {
                $data['message'] = 'Endpoint tidak ditemukan (404). URL: ' . $url . '. Pastikan endpoint API benar atau hubungi support OrderKuota.';
            }
        }

        return $data;
    }

    /**
     * Generate signature untuk autentikasi
     */
    private function generateSignature($params) {
        // Sort params by key
        ksort($params);

        // Build query string
        $query_string = http_build_query($params);

        // Add secret
        $query_string .= '&secret=' . $this->api_secret;

        // Generate hash
        return md5($query_string);
    }

    /**
     * Format response untuk digunakan di aplikasi
     */
    public function formatResponse($response) {
        if (isset($response['success']) && $response['success']) {
            // Extract data dari berbagai format response
            $data = $response['data'] ?? [];

            // Jika response langsung berisi data (bukan dalam 'data')
            if (empty($data) && isset($response['balance'])) {
                $data = ['balance' => $response['balance']];
            } elseif (empty($data) && isset($response['price'])) {
                $data = [
                    'price' => $response['price'],
                    'product_name' => $response['product_name'] ?? $response['product'] ?? ''
                ];
            } elseif (empty($data) && isset($response['transaction_id'])) {
                $data = [
                    'transaction_id' => $response['transaction_id'],
                    'ref_id' => $response['ref_id'] ?? '',
                    'status' => $response['status'] ?? 'success',
                    'token' => $response['token'] ?? $response['kode_token'] ?? $response['token_code'] ?? $response['sn'] ?? $response['serial_number'] ?? ''
                ];
            }

            // Jika ada token di response langsung (bukan dalam data)
            if (empty($data['token']) && isset($response['token'])) {
                $data['token'] = $response['token'];
            } elseif (empty($data['token']) && isset($response['kode_token'])) {
                $data['token'] = $response['kode_token'];
            } elseif (empty($data['token']) && isset($response['sn'])) {
                $data['token'] = $response['sn'];
            }

            return [
                'success' => true,
                'data' => $data ?: $response,
                'message' => $response['message'] ?? 'Success'
            ];
        } else {
            return [
                'success' => false,
                'message' => $response['message'] ?? $response['error'] ?? 'Unknown error',
                'error_code' => $response['error_code'] ?? $response['code'] ?? null,
                'raw_response' => $response
            ];
        }
    }
}

/**
 * Helper function untuk melakukan pembayaran via OrderKuota
 *
 * @param string $product_code - Kode produk
 * @param string $target - Nomor tujuan
 * @param string $ref_id - Reference ID (optional)
 * @return array - Response dari API
 */
function pay_via_orderkuota($product_code, $target, $ref_id = null) {
    global $koneksi;

    // Inisialisasi API
    $api = new OrderKuotaAPI();

    // Lakukan pembayaran
    $result = $api->pay($product_code, $target, $ref_id);

    // Log aktivitas
    if (function_exists('log_activity')) {
        $status = $result['success'] ? 'berhasil' : 'gagal';
        $message = $result['success'] ?
            "Pembayaran via OrderKuota berhasil - Produk: $product_code, Target: $target" :
            "Pembayaran via OrderKuota gagal - " . ($result['message'] ?? 'Unknown error');

        log_activity('payment', 'orderkuota', $message);
    }

    return $api->formatResponse($result);
}

/**
 * Helper function untuk cek harga
 */
function check_price_orderkuota($product_code, $target) {
    $api = new OrderKuotaAPI();
    $result = $api->checkPrice($product_code, $target);
    return $api->formatResponse($result);
}

/**
 * Helper function untuk cek saldo
 */
function check_balance_orderkuota() {
    $api = new OrderKuotaAPI();
    $result = $api->checkBalance();
    return $api->formatResponse($result);
}

/**
 * Helper function untuk tambah deposit OrderKuota
 *
 * @param float $amount - Jumlah deposit
 * @param string $payment_method - Metode pembayaran (optional)
 * @param string $ref_id - Reference ID (optional)
 * @return array - Response dari API
 */
function deposit_orderkuota($amount, $payment_method = null, $ref_id = null) {
    global $koneksi;

    // Inisialisasi API
    $api = new OrderKuotaAPI();

    // Lakukan deposit
    $result = $api->deposit($amount, $payment_method, $ref_id);

    // Log aktivitas
    if (function_exists('log_activity')) {
        $status = $result['success'] ? 'berhasil' : 'gagal';
        $message = $result['success'] ?
            "Deposit OrderKuota berhasil - Jumlah: Rp " . number_format($amount, 0, ',', '.') :
            "Deposit OrderKuota gagal - " . ($result['message'] ?? 'Unknown error');

        log_activity('deposit', 'orderkuota', $message);
    }

    return $api->formatResponse($result);
}

/**
 * Helper function untuk get history deposit
 */
function get_deposit_history_orderkuota($limit = 50, $offset = 0) {
    $api = new OrderKuotaAPI();
    $result = $api->getDepositHistory($limit, $offset);
    return $api->formatResponse($result);
}

?>





