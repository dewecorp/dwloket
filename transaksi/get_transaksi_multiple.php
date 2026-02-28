<?php
/**
 * Endpoint AJAX untuk mengambil data transaksi multiple untuk edit
 */
header('Content-Type: application/json');
include_once('../config/config.php');

// Cek apakah request valid
if (!isset($_GET['ids']) || empty($_GET['ids'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter ids tidak ditemukan'
    ]);
    exit;
}

$ids = array_map('intval', explode(',', $_GET['ids']));
$ids = array_filter($ids, function($id) {
    return $id > 0;
});

if (empty($ids)) {
    echo json_encode([
        'success' => false,
        'message' => 'ID transaksi tidak valid'
    ]);
    exit;
}

$ids_str = implode(',', $ids);

// Ambil data transaksi
$query = "SELECT transaksi.*, tb_jenisbayar.jenis_bayar
          FROM transaksi
          LEFT JOIN tb_jenisbayar ON transaksi.id_bayar = tb_jenisbayar.id_bayar
          WHERE transaksi.id_transaksi IN ($ids_str)
          ORDER BY transaksi.tgl DESC, transaksi.id_transaksi DESC";
$result = $koneksi->query($query);
$transaksi_list = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Format tanggal untuk input date
        $row['tgl'] = date('Y-m-d', strtotime($row['tgl']));
        $transaksi_list[] = $row;
    }
}

// Ambil data jenis pembayaran untuk dropdown
$sql_jenis = $koneksi->query("SELECT * FROM tb_jenisbayar ORDER BY jenis_bayar ASC");
$jenis_bayar_list = [];
if ($sql_jenis) {
    while ($row = $sql_jenis->fetch_assoc()) {
        $jenis_bayar_list[] = $row;
    }
}

// Format response
$response = [
    'success' => true,
    'transaksi' => $transaksi_list,
    'jenis_bayar_list' => $jenis_bayar_list
];

echo json_encode($response);
exit;
?>

