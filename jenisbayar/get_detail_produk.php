<?php
include_once('../config/config.php');

header('Content-Type: application/json');

$id_produk = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_produk <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'ID produk tidak valid'
    ]);
    exit;
}

$query = "SELECT * FROM tb_produk_orderkuota WHERE id_produk = $id_produk LIMIT 1";
$result = $koneksi->query($query);

if ($result && $result->num_rows > 0) {
    $produk = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'produk' => $produk
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Produk tidak ditemukan'
    ]);
}
?>


