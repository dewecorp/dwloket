<?php
/**
 * Endpoint AJAX untuk mengambil produk berdasarkan jenis bayar atau kategori
 * Digunakan oleh halaman tambah transaksi
 */
header('Content-Type: application/json');
include_once('../config/koneksi.php');
require_once '../libs/produk_helper.php';

$produk_list = [];

// Cek apakah request berdasarkan kategori atau id_bayar
if (isset($_GET['kategori']) && !empty($_GET['kategori'])) {
    // Ambil produk berdasarkan kategori
    $kategori = $_GET['kategori'];
    $produk_list = getProdukByKategori(null, $kategori, true);
} elseif (isset($_GET['id_bayar']) && !empty($_GET['id_bayar'])) {
    // Ambil produk berdasarkan jenis bayar (backward compatibility)
    $id_bayar = intval($_GET['id_bayar']);
    if ($id_bayar > 0) {
        $produk_list = getProdukByIdBayar($id_bayar, true);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Parameter kategori atau id_bayar tidak ditemukan'
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


