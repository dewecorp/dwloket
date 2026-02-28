<?php
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
define('ORDERKUOTA_API_URL', 'https://h2h.okeconnect.com/trx');
// OkeConnect menggunakan User ID dan Password untuk autentikasi
define('ORDERKUOTA_USER_ID', 'OK96961');
define('ORDERKUOTA_PASSWORD', 'bismillah25');
// Untuk backward compatibility (tidak digunakan jika User ID dan Password diisi)
define('ORDERKUOTA_API_KEY', 'RnhPMU9WeThGSnVqRWZRZk9K Ujl1N113R2hDMGdxSyt3bFVGY0t KcjllejZIWDBkam9uU2ZEVjFGTX gyUUlkQIhzU2NCQUIJUHMxZ05XT k9ycndocUVnNHNvZEdsTU9Hczk 2NWFkQmNSbWJnZUIUUTRhMXR sNG9taEprWjFFeHU3dmRnQzM5a TBjTmdFcEI4UGF0YIIMenlxOUFSY WJaWnhUZUJwU2QyUEttR1RPeW ZnbXRHUXh2YTFtdEMvNTNJ');
define('ORDERKUOTA_API_SECRET', '7550f5209404008e73e0b266ff56cea9');
// API Token (alternatif autentikasi menggunakan Bearer token)
define('ORDERKUOTA_API_TOKEN', 'eyJhcHAiOiIxODA3MzAiLCJhdXRoIjoiMjAyNTEyMTgiLCJzaWduIjoiQjYyeUJnVTJiOGpWOWVwR0hjT1NUQT09In0=');
// Callback URL untuk menerima notifikasi dari API
define('ORDERKUOTA_CALLBACK_URL', 'https://bukaolshop.net/callback/okeconnect/event');

// Timeout untuk request (dalam detik)
define('ORDERKUOTA_TIMEOUT', 30);

// Enable/Disable logging
define('ORDERKUOTA_ENABLE_LOG', true);

// Default produk jika API tidak mengembalikan daftar produk
$ORDERKUOTA_DEFAULT_PRODUCTS = [
    ['code' => 'PLN', 'name' => 'Token PLN'],
    ['code' => 'PULSA', 'name' => 'Pulsa'],
    ['code' => 'DATA', 'name' => 'Paket Data'],
    ['code' => 'BPJS', 'name' => 'BPJS Kesehatan'],
    ['code' => 'PDAM', 'name' => 'PDAM'],
    ['code' => 'EMONEY', 'name' => 'E-Money'],
    ['code' => 'VOUCHER', 'name' => 'Voucher Game'],
];

?>




