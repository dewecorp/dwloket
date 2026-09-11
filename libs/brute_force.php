<?php
/**
 * Brute Force Protection Library
 * Menyediakan Rate Limiting (per IP) dan Account Lockout (per username)
 */

// ============================================================
// RATE LIMITING (berbasis file, per IP)
// ============================================================

/**
 * Cek apakah IP masih diizinkan melakukan percobaan login.
 * Mengembalikan true jika boleh lanjut, false jika harus diblokir.
 */
function checkRateLimit(string $ip, int $maxAttempts = 10, int $windowSeconds = 900): bool {
    $dir = sys_get_temp_dir() . '/dwloket_ratelimit/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $file = $dir . md5('ip_' . $ip) . '.json';
    $now  = time();
    $data = ['attempts' => [], 'blocked_until' => 0];

    if (file_exists($file)) {
        $raw = file_get_contents($file);
        if ($raw !== false) {
            $data = json_decode($raw, true) ?? $data;
        }
    }

    // Masih dalam masa blokir
    if (isset($data['blocked_until']) && $data['blocked_until'] > $now) {
        $sisa = ceil(($data['blocked_until'] - $now) / 60);
        return false;
    }

    // Hapus attempt yang sudah di luar window waktu
    $data['attempts'] = array_values(
        array_filter($data['attempts'], fn($t) => ($now - $t) < $windowSeconds)
    );

    if (count($data['attempts']) >= $maxAttempts) {
        $data['blocked_until'] = $now + $windowSeconds;
        file_put_contents($file, json_encode($data), LOCK_EX);
        return false;
    }

    return true;
}

/**
 * Catat satu percobaan login gagal untuk IP tertentu.
 */
function recordRateLimitAttempt(string $ip, int $maxAttempts = 10, int $windowSeconds = 900): void {
    $dir  = sys_get_temp_dir() . '/dwloket_ratelimit/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $file = $dir . md5('ip_' . $ip) . '.json';
    $now  = time();
    $data = ['attempts' => [], 'blocked_until' => 0];

    if (file_exists($file)) {
        $raw = file_get_contents($file);
        if ($raw !== false) {
            $data = json_decode($raw, true) ?? $data;
        }
    }

    // Hapus attempt lama di luar window
    $data['attempts'] = array_values(
        array_filter($data['attempts'], fn($t) => ($now - $t) < $windowSeconds)
    );

    $data['attempts'][] = $now;

    // Blokir jika sudah mencapai batas
    if (count($data['attempts']) >= $maxAttempts) {
        $data['blocked_until'] = $now + $windowSeconds;
    }

    file_put_contents($file, json_encode($data), LOCK_EX);
}

/**
 * Reset rate limit untuk IP tertentu (dipanggil setelah login berhasil).
 */
function resetRateLimit(string $ip): void {
    $file = sys_get_temp_dir() . '/dwloket_ratelimit/' . md5('ip_' . $ip) . '.json';
    if (file_exists($file)) {
        unlink($file);
    }
}

/**
 * Kembalikan sisa waktu blokir (dalam menit) untuk sebuah IP.
 * Mengembalikan 0 jika tidak sedang diblokir.
 */
function getRateLimitRemaining(string $ip): int {
    $file = sys_get_temp_dir() . '/dwloket_ratelimit/' . md5('ip_' . $ip) . '.json';
    if (!file_exists($file)) return 0;

    $raw  = file_get_contents($file);
    $data = $raw !== false ? (json_decode($raw, true) ?? []) : [];

    if (!empty($data['blocked_until']) && $data['blocked_until'] > time()) {
        return (int) ceil(($data['blocked_until'] - time()) / 60);
    }

    return 0;
}

// ============================================================
// ACCOUNT LOCKOUT (berbasis database, per username)
// ============================================================

/**
 * Cek apakah akun user sedang terkunci.
 * Otomatis reset jika masa kunci sudah habis.
 * Mengembalikan true jika TERKUNCI, false jika boleh login.
 */
function isAccountLocked(mysqli $koneksi, string $username): bool {
    $stmt = $koneksi->prepare(
        "SELECT failed_attempts, locked_until FROM tb_user WHERE username = ?"
    );
    if (!$stmt) return false;

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $stmt->close();

    if (!$user) return false;

    if (!empty($user['locked_until'])) {
        if (strtotime($user['locked_until']) > time()) {
            return true; // masih terkunci
        }
        // Masa kunci sudah habis — reset otomatis
        resetAccountLockout($koneksi, $username);
    }

    return false;
}

/**
 * Catat satu percobaan login gagal untuk username.
 * Kunci akun secara otomatis jika mencapai batas maksimal.
 */
function recordFailedLogin(mysqli $koneksi, string $username, int $maxAttempts = 5, int $lockMinutes = 15): void {
    // Ambil jumlah percobaan saat ini
    $stmt = $koneksi->prepare(
        "SELECT failed_attempts FROM tb_user WHERE username = ?"
    );
    if (!$stmt) return;

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result   = $stmt->get_result();
    $user     = $result->fetch_assoc();
    $stmt->close();

    if (!$user) return;

    $newAttempts = (int)$user['failed_attempts'] + 1;

    if ($newAttempts >= $maxAttempts) {
        // Kunci akun
        $lockedUntil = date('Y-m-d H:i:s', time() + ($lockMinutes * 60));
        $stmt2 = $koneksi->prepare(
            "UPDATE tb_user SET failed_attempts = ?, locked_until = ? WHERE username = ?"
        );
        if ($stmt2) {
            $stmt2->bind_param("iss", $newAttempts, $lockedUntil, $username);
            $stmt2->execute();
            $stmt2->close();
        }
    } else {
        $stmt2 = $koneksi->prepare(
            "UPDATE tb_user SET failed_attempts = ? WHERE username = ?"
        );
        if ($stmt2) {
            $stmt2->bind_param("is", $newAttempts, $username);
            $stmt2->execute();
            $stmt2->close();
        }
    }
}

/**
 * Reset failed_attempts dan locked_until setelah login berhasil.
 */
function resetAccountLockout(mysqli $koneksi, string $username): void {
    $stmt = $koneksi->prepare(
        "UPDATE tb_user SET failed_attempts = 0, locked_until = NULL WHERE username = ?"
    );
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Kembalikan sisa waktu kunci akun (dalam menit).
 * Mengembalikan 0 jika tidak sedang terkunci.
 */
function getAccountLockRemaining(mysqli $koneksi, string $username): int {
    $stmt = $koneksi->prepare(
        "SELECT locked_until FROM tb_user WHERE username = ?"
    );
    if (!$stmt) return 0;

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user   = $result->fetch_assoc();
    $stmt->close();

    if (!$user || empty($user['locked_until'])) return 0;

    $remaining = strtotime($user['locked_until']) - time();
    return $remaining > 0 ? (int) ceil($remaining / 60) : 0;
}
