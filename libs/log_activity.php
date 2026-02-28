<?php
/**
 * Activity Logging System
 * Sistem logging aktivitas user untuk tracking dan audit
 */

/**
 * Log activity ke database
 *
 * @param string $action - Action yang dilakukan (create, update, delete, login, dll)
 * @param string $module - Module/area aplikasi (pelanggan, transaksi, saldo, dll)
 * @param string $description - Deskripsi detail aktivitas
 * @param int|null $user_id - ID user (optional, akan diambil dari session jika tidak diisi)
 * @return bool - True jika berhasil, False jika gagal
 */
function log_activity($action, $module, $description, $user_id = null) {
    global $koneksi;

    // Pastikan koneksi database ada
    if (!isset($koneksi) || !$koneksi) {
        return false;
    }

    try {
        // Ambil user info dari session jika user_id tidak diberikan
        if ($user_id === null) {
            $user_id = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;
            $username = isset($_SESSION['username']) ? $_SESSION['username'] : 'system';
            $nama_user = isset($_SESSION['nama']) ? $_SESSION['nama'] : 'System';
        } else {
            // Ambil info user dari database jika user_id diberikan
            $user_query = $koneksi->query("SELECT username, nama FROM tb_user WHERE id_user = " . (int)$user_id);
            if ($user_query && $user_query->num_rows > 0) {
                $user_data = $user_query->fetch_assoc();
                $username = $user_data['username'];
                $nama_user = $user_data['nama'];
            } else {
                $username = 'unknown';
                $nama_user = 'Unknown User';
            }
        }

        // Ambil IP address
        $ip_address = null;
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip_address = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip_address = $_SERVER['REMOTE_ADDR'];
        }

        // Ambil user agent
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : null;

        // Escape string untuk keamanan
        $action = mysqli_real_escape_string($koneksi, $action);
        $module = mysqli_real_escape_string($koneksi, $module);
        $description = mysqli_real_escape_string($koneksi, $description);
        $username = mysqli_real_escape_string($koneksi, $username);
        $nama_user = mysqli_real_escape_string($koneksi, $nama_user);
        $ip_address = $ip_address ? mysqli_real_escape_string($koneksi, $ip_address) : 'NULL';
        $user_agent = $user_agent ? mysqli_real_escape_string($koneksi, $user_agent) : 'NULL';

        // Query insert
        $sql = "INSERT INTO admin_activity_logs
                (user_id, username, nama_user, action, module, description, ip_address, user_agent, created_at)
                VALUES
                (" . (int)$user_id . ",
                 '" . $username . "',
                 '" . $nama_user . "',
                 '" . $action . "',
                 '" . $module . "',
                 '" . $description . "',
                 " . ($ip_address !== 'NULL' ? "'" . $ip_address . "'" : "NULL") . ",
                 " . ($user_agent !== 'NULL' ? "'" . $user_agent . "'" : "NULL") . ",
                 NOW())";

        $result = $koneksi->query($sql);

        if (!$result) {
            return false;
        }

        return true;

    } catch (Exception $e) {
        return false;
    }
}

/**
 * Cleanup old activities (older than 24 hours)
 *
 * @return int - Number of deleted records
 */
function cleanup_old_activities() {
    global $koneksi;

    if (!isset($koneksi) || !$koneksi) {
        return 0;
    }

    try {
        // Hapus aktivitas yang lebih dari 24 jam
        $sql = "DELETE FROM admin_activity_logs
                WHERE created_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)";

        $result = $koneksi->query($sql);

        if ($result) {
            return $koneksi->affected_rows;
        }

        return 0;

    } catch (Exception $e) {
        return 0;
    }
}

/**
 * Get recent activities
 *
 * @param int $limit - Jumlah aktivitas yang diambil (default: 10)
 * @return array - Array of activity logs
 */
function get_recent_activities($limit = 10) {
    global $koneksi;

    if (!isset($koneksi) || !$koneksi) {
        return [];
    }

    try {
        // Cleanup old activities sebelum mengambil data baru
        cleanup_old_activities();

        $limit = (int)$limit;
        $sql = "SELECT * FROM admin_activity_logs
                ORDER BY created_at DESC
                LIMIT " . $limit;

        $result = $koneksi->query($sql);
        $activities = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $activities[] = $row;
            }
        }

        return $activities;

    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get activity count by module
 *
 * @param string $module - Module name (optional)
 * @return array - Array dengan statistik aktivitas
 */
function get_activity_stats($module = null) {
    global $koneksi;

    if (!isset($koneksi) || !$koneksi) {
        return [];
    }

    try {
        $where = $module ? "WHERE module = '" . mysqli_real_escape_string($koneksi, $module) . "'" : "";

        $sql = "SELECT
                    module,
                    action,
                    COUNT(*) as count
                FROM admin_activity_logs
                " . $where . "
                GROUP BY module, action
                ORDER BY count DESC";

        $result = $koneksi->query($sql);
        $stats = [];

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $stats[] = $row;
            }
        }

        return $stats;

    } catch (Exception $e) {
        return [];
    }
}
