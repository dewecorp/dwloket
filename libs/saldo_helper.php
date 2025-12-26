<?php
/**
 * Helper functions untuk mengelola saldo
 */

/**
 * Kurangi saldo saat transaksi dibuat dengan status Lunas
 * PENTING: Fungsi ini mengurangi dari TOTAL SALDO (SUM semua saldo), bukan dari record terakhir
 *
 * @param mysqli $koneksi Koneksi database
 * @param float $jumlah Jumlah yang akan dikurangi
 * @param string $keterangan Keterangan transaksi
 * @param int $id_transaksi ID transaksi (opsional)
 * @return array ['success' => bool, 'message' => string]
 */
function kurangi_saldo($koneksi, $jumlah, $keterangan = '', $id_transaksi = null) {
    if (!$koneksi || $jumlah <= 0) {
        return ['success' => false, 'message' => 'Parameter tidak valid'];
    }

    // Cek TOTAL SALDO saat ini (SUM dari semua record saldo, termasuk yang negatif)
    // BUKAN mengambil record terakhir, tapi menghitung total semua saldo
    $total_saldo = get_total_saldo($koneksi);

    // Validasi: pastikan total saldo mencukupi untuk pengurangan
    if ($total_saldo < $jumlah) {
        return ['success' => false, 'message' => 'Saldo tidak mencukupi. Saldo tersedia: Rp ' . number_format($total_saldo, 0, ',', '.')];
    }

    // Tambahkan record saldo keluar dengan nilai negatif
    // Dengan cara ini, ketika get_total_saldo() dipanggil lagi, total akan otomatis berkurang
    // karena SUM akan menghitung semua saldo termasuk yang negatif ini
    $tgl = date('Y-m-d');
    $saldo_keluar = '-' . $jumlah; // Nilai negatif untuk saldo keluar
    $ket = !empty($keterangan) ? mysqli_real_escape_string($koneksi, $keterangan) : 'Transaksi #' . ($id_transaksi ?? '');

    $query = "INSERT INTO tb_saldo (tgl, saldo) VALUES ('$tgl', '$saldo_keluar')";

    if ($koneksi->query($query)) {
        // Log aktivitas
        require_once __DIR__ . '/log_activity.php';
        @log_activity('update', 'saldo', 'Saldo dikurangi: Rp ' . number_format($jumlah, 0, ',', '.') . ' - ' . $ket);

        return ['success' => true, 'message' => 'Saldo berhasil dikurangi'];
    } else {
        return ['success' => false, 'message' => 'Gagal mengurangi saldo: ' . $koneksi->error];
    }
}

/**
 * Tambahkan saldo (untuk mengembalikan saldo saat transaksi dihapus atau status diubah)
 * @param mysqli $koneksi Koneksi database
 * @param float $jumlah Jumlah yang akan ditambahkan
 * @param string $keterangan Keterangan transaksi
 * @param int $id_transaksi ID transaksi (opsional)
 * @return array ['success' => bool, 'message' => string]
 */
function tambah_saldo($koneksi, $jumlah, $keterangan = '', $id_transaksi = null) {
    if (!$koneksi || $jumlah <= 0) {
        return ['success' => false, 'message' => 'Parameter tidak valid'];
    }

    // Tambahkan record saldo masuk
    $tgl = date('Y-m-d');
    $ket = !empty($keterangan) ? mysqli_real_escape_string($koneksi, $keterangan) : 'Pengembalian Transaksi #' . ($id_transaksi ?? '');

    $query = "INSERT INTO tb_saldo (tgl, saldo) VALUES ('$tgl', '$jumlah')";

    if ($koneksi->query($query)) {
        // Log aktivitas
        require_once __DIR__ . '/log_activity.php';
        @log_activity('update', 'saldo', 'Saldo ditambahkan: Rp ' . number_format($jumlah, 0, ',', '.') . ' - ' . $ket);

        return ['success' => true, 'message' => 'Saldo berhasil ditambahkan'];
    } else {
        return ['success' => false, 'message' => 'Gagal menambahkan saldo: ' . $koneksi->error];
    }
}

/**
 * Hitung TOTAL SALDO saat ini
 * PENTING: Fungsi ini menghitung SUM dari SEMUA saldo (termasuk yang positif dan negatif)
 * BUKAN mengambil record terakhir, tapi menghitung total semua saldo
 *
 * Contoh:
 * - Saldo deposit: +1.000.000
 * - Saldo deposit: +500.000
 * - Saldo keluar (transaksi): -200.000
 * - Saldo keluar (transaksi): -100.000
 * Total = 1.000.000 + 500.000 - 200.000 - 100.000 = 1.200.000
 *
 * @param mysqli $koneksi Koneksi database
 * @return float Total saldo (bisa positif atau negatif)
 */
function get_total_saldo($koneksi) {
    if (!$koneksi) {
        return 0;
    }

    // Jumlahkan SEMUA saldo dari semua record (termasuk yang positif dan negatif)
    // Menggunakan SUM untuk menghitung total, bukan mengambil record terakhir
    $query = "SELECT SUM(CAST(saldo AS DECIMAL(15,2))) as total FROM tb_saldo";
    $result = $koneksi->query($query);

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $total = floatval($row['total'] ?? 0);
        // Jika hasil NULL (tidak ada data), return 0
        return is_null($total) ? 0 : $total;
    }

    return 0;
}

/**
 * Proses perubahan saldo saat transaksi dibuat
 * @param mysqli $koneksi Koneksi database
 * @param string $status Status transaksi ('Lunas' atau 'Belum')
 * @param float $harga Harga transaksi
 * @param string $keterangan Keterangan transaksi
 * @param int $id_transaksi ID transaksi
 * @return array ['success' => bool, 'message' => string]
 */
function proses_saldo_transaksi($koneksi, $status, $harga, $keterangan = '', $id_transaksi = null) {
    // Hanya kurangi saldo jika status Lunas
    if ($status === 'Lunas' && $harga > 0) {
        return kurangi_saldo($koneksi, $harga, $keterangan, $id_transaksi);
    }

    return ['success' => true, 'message' => 'Transaksi belum lunas, saldo tidak dikurangi'];
}

/**
 * Proses perubahan saldo saat transaksi diedit
 * @param mysqli $koneksi Koneksi database
 * @param int $id_transaksi ID transaksi
 * @param string $status_lama Status transaksi lama
 * @param string $status_baru Status transaksi baru
 * @param float $harga_lama Harga transaksi lama
 * @param float $harga_baru Harga transaksi baru
 * @param string $keterangan Keterangan transaksi
 * @return array ['success' => bool, 'message' => string]
 */
function proses_saldo_edit_transaksi($koneksi, $id_transaksi, $status_lama, $status_baru, $harga_lama, $harga_baru, $keterangan = '') {
    // Jika transaksi lama status Lunas, kembalikan saldo
    if ($status_lama === 'Lunas' && $harga_lama > 0) {
        $result = tambah_saldo($koneksi, $harga_lama, 'Pengembalian edit transaksi #' . $id_transaksi, $id_transaksi);
        if (!$result['success']) {
            return $result;
        }
    }

    // Jika transaksi baru status Lunas, kurangi saldo
    if ($status_baru === 'Lunas' && $harga_baru > 0) {
        return kurangi_saldo($koneksi, $harga_baru, $keterangan ?: 'Edit transaksi #' . $id_transaksi, $id_transaksi);
    }

    return ['success' => true, 'message' => 'Saldo berhasil disesuaikan'];
}

/**
 * Proses pengembalian saldo saat transaksi dihapus
 * @param mysqli $koneksi Koneksi database
 * @param int $id_transaksi ID transaksi
 * @param string $status Status transaksi
 * @param float $harga Harga transaksi
 * @return array ['success' => bool, 'message' => string]
 */
function proses_saldo_hapus_transaksi($koneksi, $id_transaksi, $status, $harga) {
    // Hanya kembalikan saldo jika status Lunas
    if ($status === 'Lunas' && $harga > 0) {
        return tambah_saldo($koneksi, $harga, 'Pengembalian hapus transaksi #' . $id_transaksi, $id_transaksi);
    }

    return ['success' => true, 'message' => 'Transaksi belum lunas, tidak ada saldo yang dikembalikan'];
}

