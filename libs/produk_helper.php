<?php
/**
 * Helper functions untuk mengelola data produk OrderKuota
 */

/**
 * Mengambil semua produk berdasarkan kategori atau jenis bayar
 *
 * @param int|null $id_bayar ID jenis bayar (opsional)
 * @param string|null $kategori Nama kategori (opsional)
 * @param bool $only_active Hanya ambil produk yang aktif (default: true)
 * @return array Array produk
 */
function getProdukByKategori($id_bayar = null, $kategori = null, $only_active = true) {
    global $koneksi;

    $where_conditions = [];
    $params = [];
    $types = "";

    // id_bayar tidak digunakan lagi, parameter diabaikan untuk backward compatibility

    if ($kategori !== null && $kategori !== '') {
        $where_conditions[] = "p.kategori LIKE ?";
        $params[] = "%" . $kategori . "%";
        $types .= "s";
    }

    if ($only_active) {
        $where_conditions[] = "p.status = 1";
    }

    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    $query = "SELECT p.*
              FROM tb_produk_orderkuota p
              $where_clause
              ORDER BY p.kategori ASC, p.kode ASC, p.produk ASC, p.harga ASC";

    $stmt = $koneksi->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $produk = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $produk[] = $row;
        }
    }
    $stmt->close();

    return $produk;
}

/**
 * Mengambil produk berdasarkan kode
 *
 * @param string $kode Kode produk
 * @return array|null Data produk atau null jika tidak ditemukan
 */
function getProdukByKode($kode) {
    global $koneksi;

    $query = "SELECT p.*
              FROM tb_produk_orderkuota p
              WHERE p.kode = ?
              LIMIT 1";

    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("s", $kode);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row;
    }
    $stmt->close();

    return null;
}

/**
 * Mengambil semua kategori unik
 *
 * @return array Array kategori
 */
function getAllKategori() {
    global $koneksi;

    $query = "SELECT DISTINCT kategori, COUNT(*) as jumlah_produk
              FROM tb_produk_orderkuota
              WHERE status = 1
              GROUP BY kategori
              ORDER BY kategori ASC";

    // Query ini statis, tidak perlu prepare param
    $result = $koneksi->query($query);
    $kategori = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $kategori[] = $row;
        }
    }

    return $kategori;
}

/**
 * Mengambil produk berdasarkan ID jenis bayar
 *
 * @param int $id_bayar ID jenis bayar
 * @param bool $only_active Hanya ambil produk yang aktif
 * @return array Array produk
 */
function getProdukByIdBayar($id_bayar, $only_active = true) {
    return getProdukByKategori($id_bayar, null, $only_active);
}

/**
 * Mencari produk berdasarkan keyword
 *
 * @param string $keyword Keyword untuk pencarian
 * @param bool $only_active Hanya ambil produk yang aktif
 * @return array Array produk
 */
function searchProduk($keyword, $only_active = true) {
    global $koneksi;

    $status_condition = $only_active ? "AND p.status = 1" : "";

    $query = "SELECT p.*
              FROM tb_produk_orderkuota p
              WHERE (p.kode LIKE ?
                     OR p.produk LIKE ?
                     OR p.kategori LIKE ?)
              $status_condition
              ORDER BY p.kategori ASC, p.kode ASC, p.produk ASC, p.harga ASC
              LIMIT 100";

    $stmt = $koneksi->prepare($query);
    $search_term = "%" . $keyword . "%";
    $stmt->bind_param("sss", $search_term, $search_term, $search_term);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $produk = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $produk[] = $row;
        }
    }
    $stmt->close();

    return $produk;
}

/**
 * Mengambil statistik produk
 *
 * @return array Statistik produk
 */
function getProdukStats() {
    global $koneksi;

    $stats = [
        'total' => 0,
        'aktif' => 0,
        'tidak_aktif' => 0,
        'per_kategori' => []
    ];

    // Total produk
    $query_total = "SELECT COUNT(*) as total FROM tb_produk_orderkuota";
    $result_total = $koneksi->query($query_total);
    if ($result_total) {
        $row = $result_total->fetch_assoc();
        $stats['total'] = $row['total'] ?? 0;
    }

    // Produk aktif
    $query_aktif = "SELECT COUNT(*) as aktif FROM tb_produk_orderkuota WHERE status = 1";
    $result_aktif = $koneksi->query($query_aktif);
    if ($result_aktif) {
        $row = $result_aktif->fetch_assoc();
        $stats['aktif'] = $row['aktif'] ?? 0;
    }

    // Produk tidak aktif
    $stats['tidak_aktif'] = $stats['total'] - $stats['aktif'];

    // Per kategori
    $query_kategori = "SELECT kategori, COUNT(*) as jumlah,
                       SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as aktif
                       FROM tb_produk_orderkuota
                       GROUP BY kategori
                       ORDER BY kategori ASC";
    $result_kategori = $koneksi->query($query_kategori);
    if ($result_kategori) {
        while ($row = $result_kategori->fetch_assoc()) {
            $stats['per_kategori'][] = $row;
        }
    }

    return $stats;
}

?>


