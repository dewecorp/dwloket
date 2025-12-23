<?php
include_once('../config/config.php');

// Start session jika belum
if (!isset($_SESSION)) {
    session_start();
}

$deleted_count = 0;
$error_count = 0;
$errors = [];

if (isset($_POST['id_transaksi']) && is_array($_POST['id_transaksi'])) {
    foreach ($_POST['id_transaksi'] as $id_transaksi) {
        $id_transaksi = intval($id_transaksi);

        if ($id_transaksi > 0) {
            $delete_query = "DELETE FROM transaksi WHERE id_transaksi = $id_transaksi";

            if ($koneksi->query($delete_query)) {
                $deleted_count++;
                // Log aktivitas
                require_once '../libs/log_activity.php';
                @log_activity('delete', 'transaksi', 'Menghapus transaksi ID: ' . $id_transaksi);
            } else {
                $error_count++;
                $errors[] = "Gagal menghapus transaksi ID $id_transaksi: " . $koneksi->error;
            }
        }
    }

    if ($deleted_count > 0) {
        $_SESSION['hapus_message'] = "Berhasil menghapus $deleted_count transaksi" . ($error_count > 0 ? " (gagal: $error_count)" : "");
        $_SESSION['hapus_success'] = true;
    } else {
        $_SESSION['hapus_message'] = "Gagal menghapus transaksi. " . implode("; ", $errors);
        $_SESSION['hapus_success'] = false;
    }
} else {
    $_SESSION['hapus_message'] = 'Tidak ada transaksi yang dipilih untuk dihapus';
    $_SESSION['hapus_success'] = false;
}

header('Location: ' . base_url('transaksi/transaksi.php'));
exit;
?>

