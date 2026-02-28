<?php
include_once('../config/config.php');

// Start session jika belum
if (!isset($_SESSION)) {
    session_start();
}

$deleted_count = 0;
$error_count = 0;
$errors = [];

if (isset($_POST['id_produk']) && is_array($_POST['id_produk'])) {
    foreach ($_POST['id_produk'] as $id_produk) {
        $id_produk = intval($id_produk);

        if ($id_produk > 0) {
            $stmt = $koneksi->prepare("DELETE FROM tb_produk_orderkuota WHERE id_produk = ?");
            $stmt->bind_param("i", $id_produk);

            if ($stmt->execute()) {
                $deleted_count++;
            } else {
                $error_count++;
                $errors[] = "Gagal menghapus produk ID $id_produk: " . $koneksi->error;
            }
            $stmt->close();
        }
    }

    if ($deleted_count > 0) {
        $_SESSION['hapus_message'] = "Berhasil menghapus $deleted_count produk" . ($error_count > 0 ? " (gagal: $error_count)" : "");
        $_SESSION['hapus_success'] = true;
    } else {
        $_SESSION['hapus_message'] = "Gagal menghapus produk. " . implode("; ", $errors);
        $_SESSION['hapus_success'] = false;
    }
} else {
    $_SESSION['hapus_message'] = 'Tidak ada produk yang dipilih untuk dihapus';
    $_SESSION['hapus_success'] = false;
}

header('Location: ' . base_url('jenisbayar/jenis_bayar.php'));
exit;
?>

