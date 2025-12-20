<?php
include_once('../config/config.php');
require_once '../libs/orderkuota_api.php';
require_once '../libs/produk_helper.php';

// Hanya admin yang bisa export
if (!isset($_SESSION['level']) || $_SESSION['level'] != 'admin') {
    header('Location: ' . base_url('home'));
    exit;
}

// Get filter
$filter_status = $_GET['status'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';

// Build query
$where_conditions = ["ket LIKE '%OrderKuota%'"];

if ($filter_status) {
    $where_conditions[] = "status = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'";
}

if ($filter_date_from) {
    $where_conditions[] = "tgl >= '" . mysqli_real_escape_string($koneksi, $filter_date_from) . "'";
}

if ($filter_date_to) {
    $where_conditions[] = "tgl <= '" . mysqli_real_escape_string($koneksi, $filter_date_to) . "'";
}

$where_clause = implode(' AND ', $where_conditions);

// Get data - join dengan produk
$produk_table_exists = false;
$check_produk_table = $koneksi->query("SHOW TABLES LIKE 'tb_produk_orderkuota'");
if ($check_produk_table && $check_produk_table->num_rows > 0) {
    $produk_table_exists = true;
}

if ($produk_table_exists) {
    $check_column = $koneksi->query("SHOW COLUMNS FROM transaksi LIKE 'selected_produk_id'");
    $has_selected_produk_id = ($check_column && $check_column->num_rows > 0);

    if ($has_selected_produk_id) {
        $query = $koneksi->query("SELECT t.*,
                                       COALESCE(
                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_produk = t.selected_produk_id AND p.status = 1 LIMIT 1),
                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND CAST(p.harga AS UNSIGNED) = CAST(t.harga AS UNSIGNED) AND p.status = 1 LIMIT 1),
                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND p.status = 1 LIMIT 1),
                                           j.jenis_bayar,
                                           '-'
                                       ) as produk_nama
                                  FROM transaksi t
                                  LEFT JOIN tb_jenisbayar j ON t.id_bayar = j.id_bayar
                                  WHERE $where_clause
                                  ORDER BY t.tgl DESC, t.id_transaksi DESC");
    } else {
        $query = $koneksi->query("SELECT t.*,
                                       COALESCE(
                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND CAST(p.harga AS UNSIGNED) = CAST(t.harga AS UNSIGNED) AND p.status = 1 LIMIT 1),
                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND p.status = 1 LIMIT 1),
                                           j.jenis_bayar,
                                           '-'
                                       ) as produk_nama
                                  FROM transaksi t
                                  LEFT JOIN tb_jenisbayar j ON t.id_bayar = j.id_bayar
                                  WHERE $where_clause
                                  ORDER BY t.tgl DESC, t.id_transaksi DESC");
    }
} else {
    $query = $koneksi->query("SELECT t.*, j.jenis_bayar
                              FROM transaksi t
                              LEFT JOIN tb_jenisbayar j ON t.id_bayar = j.id_bayar
                              WHERE $where_clause
                              ORDER BY t.tgl DESC, t.id_transaksi DESC");
}

// Set headers untuk download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="laporan_orderkuota_' . date('Y-m-d') . '.csv"');

// Output BOM untuk Excel UTF-8
echo "\xEF\xBB\xBF";

// Create output stream
$output = fopen('php://output', 'w');

// Header CSV
fputcsv($output, [
    'No',
    'Tanggal',
    'ID Transaksi',
    'Produk',
    'Nama Pelanggan',
    'ID Pelanggan',
    'Produk',
    'Harga',
    'Status',
    'Reference ID',
    'Keterangan'
], ';');

// Data
$no = 1;
while ($row = $query->fetch_assoc()) {
    // Extract ref_id
    preg_match('/Ref: ([A-Z0-9_]+)/', $row['ket'], $matches);
    $ref_id = $matches[1] ?? '';

    // Gunakan produk_nama dari query jika ada
    if (!empty($row['produk_nama']) && $row['produk_nama'] != '-') {
        $product_name = $row['produk_nama'];
    } else {
        preg_match('/OrderKuota: ([^-]+)/', $row['ket'], $product_matches);
        $product_name = trim($product_matches[1] ?? '');
    }

    fputcsv($output, [
        $no++,
        date('d/m/Y H:i', strtotime($row['tgl'])),
        $row['id_transaksi'],
        $product_name,
        $row['nama'],
        $row['idpel'],
        $product_name, // Produk (menggantikan jenis_bayar)
        $row['harga'],
        $row['status'],
        $ref_id,
        $row['ket']
    ], ';');
}

fclose($output);
exit;





