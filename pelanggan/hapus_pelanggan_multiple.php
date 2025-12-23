<?php
include_once('../config/config.php');

// Start session jika belum
if (!isset($_SESSION)) {
    session_start();
}

$deleted_count = 0;
$error_count = 0;
$errors = [];

if (isset($_POST['id_pelanggan']) && is_array($_POST['id_pelanggan'])) {
    foreach ($_POST['id_pelanggan'] as $id_pelanggan) {
        $id_pelanggan = intval($id_pelanggan);

        if ($id_pelanggan > 0) {
            // Ambil data sebelum hapus untuk log
            $sql_data = $koneksi->query("SELECT * FROM pelanggan WHERE id_pelanggan='$id_pelanggan'");
            $nama_pelanggan = '';
            if ($sql_data && $sql_data->num_rows > 0) {
                $data = $sql_data->fetch_assoc();
                $nama_pelanggan = isset($data['nama']) ? $data['nama'] : 'ID: ' . $id_pelanggan;
            }

            $delete_query = "DELETE FROM pelanggan WHERE id_pelanggan = $id_pelanggan";

            if ($koneksi->query($delete_query)) {
                $deleted_count++;
                // Log aktivitas
                if (!empty($nama_pelanggan)) {
                    require_once '../libs/log_activity.php';
                    @log_activity('delete', 'pelanggan', 'Menghapus pelanggan: ' . $nama_pelanggan);
                }
            } else {
                $error_count++;
                $errors[] = "Gagal menghapus pelanggan ID $id_pelanggan: " . $koneksi->error;
            }
        }
    }

    if ($deleted_count > 0) {
        $_SESSION['hapus_message'] = "Berhasil menghapus $deleted_count pelanggan" . ($error_count > 0 ? " (gagal: $error_count)" : "");
        $_SESSION['hapus_success'] = true;
    } else {
        $_SESSION['hapus_message'] = "Gagal menghapus pelanggan. " . implode("; ", $errors);
        $_SESSION['hapus_success'] = false;
    }
} else {
    $_SESSION['hapus_message'] = 'Tidak ada pelanggan yang dipilih untuk dihapus';
    $_SESSION['hapus_success'] = false;
}

header('Location: ' . base_url('pelanggan/pelanggan.php'));
exit;
?>

