<?php
include_once('../config/config.php');
header('Content-Type: application/json');

$ids = isset($_GET['ids']) ? $_GET['ids'] : '';
$id_array = !empty($ids) ? array_map('intval', explode(',', $ids)) : [];

if (empty($id_array)) {
    echo json_encode(['success' => false, 'message' => 'Tidak ada transaksi yang dipilih']);
    exit;
}

$ids_str = implode(',', $id_array);
$query = "SELECT id_transaksi, tgl, idpel, nama, id_bayar, harga, status, ket FROM transaksi WHERE id_transaksi IN ($ids_str) ORDER BY tgl DESC";
$result = $koneksi->query($query);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    // Ambil list jenis bayar
    $jenis_bayar = [];
    $sql_jenis = $koneksi->query("SELECT * FROM tb_jenisbayar ORDER BY jenis_bayar ASC");
    while ($row = $sql_jenis->fetch_assoc()) {
        $jenis_bayar[] = $row;
    }

    echo json_encode(['success' => true, 'data' => $data, 'jenis_bayar' => $jenis_bayar]);
} else {
    echo json_encode(['success' => false, 'message' => $koneksi->error]);
}
?>
