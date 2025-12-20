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

    // id_bayar tidak digunakan lagi, parameter diabaikan untuk backward compatibility

    if ($kategori !== null && $kategori !== '') {
        $kategori_escaped = mysqli_real_escape_string($koneksi, $kategori);
        $where_conditions[] = "p.kategori LIKE '%$kategori_escaped%'";
    }

    if ($only_active) {
        $where_conditions[] = "p.status = 1";
    }

    $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

    $query = "SELECT p.*
              FROM tb_produk_orderkuota p
              $where_clause
              ORDER BY p.kode ASC, p.kategori ASC, p.produk ASC, p.harga ASC";

    $result = $koneksi->query($query);
    $produk = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $produk[] = $row;
        }
    }

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

    $kode_escaped = mysqli_real_escape_string($koneksi, $kode);
    $query = "SELECT p.*
              FROM tb_produk_orderkuota p
              WHERE p.kode = '$kode_escaped'
              LIMIT 1";

    $result = $koneksi->query($query);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

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
    global $koneksi;

    $id_bayar = intval($id_bayar);
    if ($id_bayar <= 0) {
        return [];
    }

    $where_conditions = ["p.id_bayar = $id_bayar"];

    if ($only_active) {
        $where_conditions[] = "p.status = 1";
    }

    $where_clause = "WHERE " . implode(" AND ", $where_conditions);

    $query = "SELECT p.*
              FROM tb_produk_orderkuota p
              $where_clause
              ORDER BY p.harga ASC, p.kode ASC, p.produk ASC";

    $result = $koneksi->query($query);
    $produk = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $produk[] = $row;
        }
    }

    return $produk;
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

    $keyword_escaped = mysqli_real_escape_string($koneksi, $keyword);
    $status_condition = $only_active ? "AND p.status = 1" : "";

    $query = "SELECT p.*
              FROM tb_produk_orderkuota p
              WHERE (p.kode LIKE '%$keyword_escaped%'
                     OR p.produk LIKE '%$keyword_escaped%'
                     OR p.kategori LIKE '%$keyword_escaped%')
              $status_condition
              ORDER BY p.kode ASC, p.kategori ASC, p.produk ASC, p.harga ASC
              LIMIT 100";

    $result = $koneksi->query($query);
    $produk = [];

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $produk[] = $row;
        }
    }

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

/**
 * Mengambil produk berdasarkan ID produk
 *
 * @param int $id_produk ID produk
 * @return array|null Data produk atau null jika tidak ditemukan
 */
function getProdukById($id_produk) {
    global $koneksi;

    $id_produk = intval($id_produk);
    if ($id_produk <= 0) {
        return null;
    }

    $query = "SELECT p.*
              FROM tb_produk_orderkuota p
              WHERE p.id_produk = $id_produk
              LIMIT 1";

    $result = $koneksi->query($query);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

?>


