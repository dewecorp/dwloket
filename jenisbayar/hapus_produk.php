<?php
include_once('../config/config.php');

// Start session jika belum
if (!isset($_SESSION)) {
    session_start();
}

$id_produk = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_produk > 0) {
    $delete_query = "DELETE FROM tb_produk_orderkuota WHERE id_produk = $id_produk";

    if ($koneksi->query($delete_query)) {
        $_SESSION['hapus_message'] = 'Produk berhasil dihapus';
        $_SESSION['hapus_success'] = true;
    } else {
        $_SESSION['hapus_message'] = 'Gagal menghapus produk: ' . $koneksi->error;
        $_SESSION['hapus_success'] = false;
    }
}

header('Location: ' . base_url('jenisbayar/jenis_bayar.php'));
exit;
?>


