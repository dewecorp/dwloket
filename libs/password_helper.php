<?php
/**
 * Password Helper Functions
 * Helper untuk hashing dan verifikasi password
 */

/**
 * Hash password menggunakan password_hash()
 * @param string $password Password plain text
 * @return string Hashed password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password menggunakan password_verify()
 * @param string $password Password plain text
 * @param string $hash Hashed password dari database
 * @return bool True jika password cocok, false jika tidak
 */
function verify_password($password, $hash) {
    // Jika hash masih plain text (legacy), bandingkan langsung
    // Ini untuk backward compatibility dengan password lama
    if (strlen($hash) < 60) {
        // Kemungkinan plain text, bandingkan langsung
        return hash_equals($password, $hash);
    }
    // Gunakan password_verify untuk hash baru
    return password_verify($password, $hash);
}

/**
 * Check apakah password perlu di-rehash
 * @param string $hash Hashed password
 * @return bool True jika perlu rehash
 */
function password_needs_rehash($hash) {
    if (strlen($hash) < 60) {
        return true; // Plain text, perlu di-hash
    }
    return password_needs_rehash($hash, PASSWORD_DEFAULT);
}

