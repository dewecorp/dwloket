<?php
while (ob_get_level()) {
    ob_end_clean();
}

include_once('../config/config.php');

if (!isset($_SESSION)) {
    session_start();
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    $_SESSION['hapus_message'] = 'ID transaksi tidak valid';
    $_SESSION['hapus_success'] = false;
    header('Location: ' . base_url('transaksi/transaksi.php'));
    exit;
}

$data_transaksi = null;
$stmt_check = $koneksi->prepare("SELECT status, harga FROM transaksi WHERE id_transaksi = ? LIMIT 1");
$stmt_check->bind_param("i", $id);
$stmt_check->execute();
$data_query = $stmt_check->get_result();

if ($data_query && $data_query->num_rows > 0) {
    $data_transaksi = $data_query->fetch_assoc();
}

$stmt_del = $koneksi->prepare("DELETE FROM transaksi WHERE id_transaksi = ?");
$stmt_del->bind_param("i", $id);
$delete_sql = $stmt_del->execute();

if ($delete_sql) {
    require_once '../libs/log_activity.php';
    @log_activity('delete', 'transaksi', 'Menghapus transaksi ID: ' . $id);

    if ($data_transaksi) {
        require_once '../libs/saldo_helper.php';
        $status = $data_transaksi['status'] ?? 'Belum';
        $harga = floatval($data_transaksi['harga'] ?? 0);
        proses_saldo_hapus_transaksi($koneksi, $id, $status, $harga);
    }

    $_SESSION['hapus_message'] = 'Transaksi berhasil dihapus';
    $_SESSION['hapus_success'] = true;
} else {
    $_SESSION['hapus_message'] = 'Gagal menghapus data. Data mungkin sudah tidak ada.';
    $_SESSION['hapus_success'] = false;
}

header('Location: ' . base_url('transaksi/transaksi.php'));
exit;
