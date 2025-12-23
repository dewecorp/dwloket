<?php
include_once('../config/config.php');

// Start session jika belum
if (!isset($_SESSION)) {
    session_start();
}

$deleted_count = 0;
$error_count = 0;
$errors = [];

if (isset($_POST['id_saldo']) && is_array($_POST['id_saldo'])) {
    foreach ($_POST['id_saldo'] as $id_saldo) {
        $id_saldo = intval($id_saldo);

        if ($id_saldo > 0) {
            $delete_query = "DELETE FROM tb_saldo WHERE id_saldo = $id_saldo";

            if ($koneksi->query($delete_query)) {
                $deleted_count++;
                // Log aktivitas jika ada
                if (file_exists('../libs/log_activity.php')) {
                    require_once '../libs/log_activity.php';
                    @log_activity('delete', 'saldo', 'Menghapus saldo ID: ' . $id_saldo);
                }
            } else {
                $error_count++;
                $errors[] = "Gagal menghapus saldo ID $id_saldo: " . $koneksi->error;
            }
        }
    }

    if ($deleted_count > 0) {
        $_SESSION['hapus_message'] = "Berhasil menghapus $deleted_count saldo" . ($error_count > 0 ? " (gagal: $error_count)" : "");
        $_SESSION['hapus_success'] = true;
    } else {
        $_SESSION['hapus_message'] = "Gagal menghapus saldo. " . implode("; ", $errors);
        $_SESSION['hapus_success'] = false;
    }
} else {
    $_SESSION['hapus_message'] = 'Tidak ada saldo yang dipilih untuk dihapus';
    $_SESSION['hapus_success'] = false;
}

header('Location: ' . base_url('saldo/saldo.php'));
exit;
?>

