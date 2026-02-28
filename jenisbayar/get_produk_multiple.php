<?php
include_once('../config/config.php');

header('Content-Type: application/json');

// Start session jika belum
if (!isset($_SESSION)) {
    session_start();
}

// Ambil IDs dari query string
$ids = isset($_GET['ids']) ? $_GET['ids'] : '';
$id_array = !empty($ids) ? array_map('intval', explode(',', $ids)) : [];

if (empty($id_array)) {
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada produk yang dipilih'
    ]);
    exit;
}

// Ambil data produk yang dipilih
$ids_str = implode(',', $id_array);
$query = "SELECT * FROM tb_produk_orderkuota WHERE id_produk IN ($ids_str) ORDER BY kategori, kode";
$result = $koneksi->query($query);
$produk_list = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $produk_list[] = [
            'id_produk' => intval($row['id_produk']),
            'kode' => $row['kode'],
            'produk' => $row['produk'],
            'kategori' => $row['kategori'],
            'harga' => intval($row['harga']),
            'status' => intval($row['status'])
        ];
    }
}

echo json_encode([
    'success' => true,
    'produk' => $produk_list,
    'count' => count($produk_list)
]);
?>

