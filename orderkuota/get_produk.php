<?php
/**
 * Endpoint AJAX untuk mengambil produk berdasarkan kategori
 * Digunakan oleh halaman orderkuota
 */
header('Content-Type: application/json');
include_once('../config/config.php');
require_once '../libs/produk_helper.php';

$produk_list = [];

// Cek apakah request berdasarkan kategori
if (isset($_GET['kategori']) && !empty($_GET['kategori'])) {
    // Ambil produk berdasarkan kategori
    $kategori = $_GET['kategori'];
    $produk_list = getProdukByKategori(null, $kategori, true);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter kategori tidak ditemukan'
    ]);
    exit;
}

// Format response
$response = [
    'success' => true,
    'produk' => []
];

foreach ($produk_list as $produk) {
    $response['produk'][] = [
        'id_produk' => $produk['id_produk'],
        'kode' => $produk['kode'],
        'keterangan' => $produk['keterangan'] ?? $produk['produk'] ?? '',
        'produk' => $produk['produk'] ?? '',
        'kategori' => $produk['kategori'] ?? '',
        'harga' => floatval($produk['harga']),
        'id_bayar' => $produk['id_bayar'] ?? null,
        'status' => $produk['status'] ?? 1
    ];
}

echo json_encode($response);
exit;
?>

