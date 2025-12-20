<?php
/**
 * Endpoint AJAX untuk mengambil produk berdasarkan kategori
 * Digunakan oleh halaman orderkuota/index.php
 */
header('Content-Type: application/json');
include_once('../config/config.php');
require_once '../libs/produk_helper.php';

// Cek apakah request valid
if (!isset($_GET['kategori'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter kategori tidak ditemukan'
    ]);
    exit;
}

$kategori = mysqli_real_escape_string($koneksi, $_GET['kategori']);

if (empty($kategori)) {
    echo json_encode([
        'success' => false,
        'message' => 'Kategori tidak valid'
    ]);
    exit;
}

// Ambil produk berdasarkan kategori
$produk_list = getProdukByKategori(null, $kategori, true);

// Format response
$response = [
    'success' => true,
    'produk' => []
];

foreach ($produk_list as $produk) {
    $response['produk'][] = [
        'id_produk' => $produk['id_produk'],
        'kode' => $produk['kode'],
        'keterangan' => $produk['keterangan'],
        'produk' => $produk['produk'],
        'kategori' => $produk['kategori'],
        'harga' => intval($produk['harga']),
        'status' => $produk['status']
    ];
}

echo json_encode($response);
exit;
?>


