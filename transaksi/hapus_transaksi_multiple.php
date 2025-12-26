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
    require_once '../libs/saldo_helper.php';

    foreach ($_POST['id_transaksi'] as $id_transaksi) {
        $id_transaksi = intval($id_transaksi);

        if ($id_transaksi > 0) {
            // Ambil data transaksi sebelum dihapus (untuk kembalikan saldo jika status Lunas)
            $data_transaksi = null;
            $data_query = $koneksi->query("SELECT status, harga FROM transaksi WHERE id_transaksi = $id_transaksi LIMIT 1");
            if ($data_query && $data_query->num_rows > 0) {
                $data_transaksi = $data_query->fetch_assoc();
            }

            $delete_query = "DELETE FROM transaksi WHERE id_transaksi = $id_transaksi";

            if ($koneksi->query($delete_query)) {
                $deleted_count++;
                // Log aktivitas
                require_once '../libs/log_activity.php';
                @log_activity('delete', 'transaksi', 'Menghapus transaksi ID: ' . $id_transaksi);

                // Kembalikan saldo jika transaksi yang dihapus statusnya Lunas
                if ($data_transaksi) {
                    $status = $data_transaksi['status'] ?? 'Belum';
                    $harga = floatval($data_transaksi['harga'] ?? 0);
                    proses_saldo_hapus_transaksi($koneksi, $id_transaksi, $status, $harga);
                }
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

