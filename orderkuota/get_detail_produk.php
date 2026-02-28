<?php
header('Content-Type: application/json');
include_once('../config/config.php');
require_once '../libs/produk_helper.php';

$id_produk = isset($_GET['id']) ? intval($_GET['id']) : 0;
$kode = isset($_GET['kode']) ? mysqli_real_escape_string($koneksi, $_GET['kode']) : '';

if (!$id_produk && !$kode) {
    echo json_encode(['success' => false, 'message' => 'Parameter id atau kode tidak ditemukan']);
    exit;
}

$query = '';
if ($id_produk) {
    $stmt = $koneksi->prepare("SELECT * FROM tb_produk_orderkuota WHERE id_produk = ? LIMIT 1");
    $stmt->bind_param("i", $id_produk);
} else {
    $stmt = $koneksi->prepare("SELECT * FROM tb_produk_orderkuota WHERE kode = ? LIMIT 1");
    $stmt->bind_param("s", $kode);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $produk = $result->fetch_assoc();
    // Ensure harga is integer
    $produk['harga'] = intval($produk['harga']);
    echo json_encode(['success' => true, 'produk' => $produk]);
} else {
    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
}
exit;
?>


