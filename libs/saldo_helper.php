<?php
/**
 * Helper functions untuk mengelola saldo
 */

/**
 * Dapatkan saldo tertua yang positif (untuk FIFO)
 * @param mysqli $koneksi Koneksi database
 * @return array|null Data saldo tertua atau null jika tidak ada
 */
function get_saldo_tertua($koneksi) {
    if (!$koneksi) {
        return null;
    }

    // Ambil saldo tertua yang positif, diurutkan berdasarkan tanggal dan id_saldo (FIFO)
    $query = "SELECT * FROM tb_saldo WHERE CAST(saldo AS DECIMAL(15,2)) > 0 ORDER BY tgl ASC, id_saldo ASC LIMIT 1";
    $result = $koneksi->query($query);

    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * Hapus saldo tertua secara otomatis jika total saldo menjadi minus
 * Menggunakan metode FIFO (First In First Out) - saldo tertua yang dihapus terlebih dahulu
 * @param mysqli $koneksi Koneksi database
 * @return array ['success' => bool, 'message' => string, 'deleted_count' => int]
 */
function hapus_saldo_tertua_otomatis($koneksi) {
    if (!$koneksi) {
        return ['success' => false, 'message' => 'Koneksi tidak valid', 'deleted_count' => 0];
    }

    $deleted_count = 0;
    $total_saldo = get_total_saldo($koneksi);
    $messages = [];

    // Hapus saldo tertua sampai total saldo tidak minus lagi
    $iteration = 0;
    $max_iterations = 100; // Safety limit untuk mencegah infinite loop

    while ($total_saldo < 0 && $iteration < $max_iterations) {
        $iteration++;

        $saldo_tertua = get_saldo_tertua($koneksi);

        if (!$saldo_tertua) {
            // Tidak ada saldo positif lagi untuk dihapus
            break;
        }

        $id_saldo = $saldo_tertua['id_saldo'];
        $nilai_saldo = floatval($saldo_tertua['saldo']);
        $tgl_saldo = $saldo_tertua['tgl'];

        // Hapus saldo tertua
        $query = "DELETE FROM tb_saldo WHERE id_saldo = ?";
        $stmt = $koneksi->prepare($query);
        $stmt->bind_param("i", $id_saldo);

        if ($stmt->execute()) {
            $deleted_count++;
            $messages[] = "Saldo tertua (ID: $id_saldo, Tgl: $tgl_saldo, Nilai: Rp " . number_format($nilai_saldo, 0, ',', '.') . ") dihapus otomatis";

            // Log aktivitas
            require_once __DIR__ . '/log_activity.php';
            @log_activity('delete', 'saldo', "Saldo tertua otomatis dihapus: ID $id_saldo, Tgl $tgl_saldo, Nilai Rp " . number_format($nilai_saldo, 0, ',', '.'));

            // Hitung ulang total saldo setelah penghapusan
            $total_saldo = get_total_saldo($koneksi);
        } else {
            // Jika gagal menghapus, hentikan loop
            $messages[] = "Gagal menghapus saldo ID: $id_saldo - " . $stmt->error;
            break;
        }

        $stmt->close();
    }

    $message = $deleted_count > 0
        ? "Berhasil menghapus $deleted_count saldo tertua secara otomatis. " . implode('; ', $messages)
        : "Tidak ada saldo yang perlu dihapus";

    return [
        'success' => true,
        'message' => $message,
        'deleted_count' => $deleted_count
    ];
}

/**
 * Kurangi saldo saat transaksi dibuat dengan status Lunas
 * PENTING: Fungsi ini langsung mengurangi dari saldo tertua yang ada (FIFO)
 * Jika saldo tertua habis, record tersebut akan dihapus dan lanjut ke saldo berikutnya
 * TIDAK menambahkan record saldo negatif
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

    // Hitung jumlah saldo positif yang tersedia
    $query_saldo_positif = "SELECT SUM(CAST(saldo AS DECIMAL(15,2))) as total_positif FROM tb_saldo WHERE CAST(saldo AS DECIMAL(15,2)) > 0";
    $result_positif = $koneksi->query($query_saldo_positif);
    $total_saldo_positif = 0;
    if ($result_positif && $result_positif->num_rows > 0) {
        $row_positif = $result_positif->fetch_assoc();
        $total_saldo_positif = floatval($row_positif['total_positif'] ?? 0);
    }

    // Validasi: pastikan ada saldo positif yang bisa digunakan
    if ($total_saldo_positif <= 0) {
        return ['success' => false, 'message' => 'Tidak ada saldo yang tersedia untuk dikurangi'];
    }

    // Validasi: pastikan jumlah yang dikurangi tidak melebihi saldo yang tersedia
    if ($jumlah > $total_saldo_positif) {
        return ['success' => false, 'message' => 'Saldo tidak cukup. Saldo tersedia: Rp ' . number_format($total_saldo_positif, 0, ',', '.')];
    }

    // Log aktivitas
    require_once __DIR__ . '/log_activity.php';
    @log_activity('update', 'saldo', 'Saldo dikurangi: Rp ' . number_format($jumlah, 0, ',', '.') . ' - ' . $keterangan);

    // Kurangi saldo dari saldo tertua (FIFO)
    $sisa_kurang = $jumlah;
    $deleted_count = 0;
    $updated_count = 0;
    $messages = [];
    $iteration = 0;
    $max_iterations = 100; // Safety limit

    while ($sisa_kurang > 0 && $iteration < $max_iterations) {
        $iteration++;

        // Ambil saldo tertua yang positif
        $saldo_tertua = get_saldo_tertua($koneksi);

        if (!$saldo_tertua) {
            // Tidak ada saldo positif lagi
            break;
        }

        $id_saldo = $saldo_tertua['id_saldo'];
        $nilai_saldo = floatval($saldo_tertua['saldo']);
        $tgl_saldo = $saldo_tertua['tgl'];

        if ($sisa_kurang >= $nilai_saldo) {
            // Jika sisa yang dikurangi >= nilai saldo tertua, hapus record saldo tersebut
            $sisa_kurang -= $nilai_saldo;

            $query_delete = "DELETE FROM tb_saldo WHERE id_saldo = ?";
            $stmt_delete = $koneksi->prepare($query_delete);
            $stmt_delete->bind_param("i", $id_saldo);

            if ($stmt_delete->execute()) {
                $deleted_count++;
                $messages[] = "Saldo tertua (ID: $id_saldo, Tgl: $tgl_saldo, Nilai: Rp " . number_format($nilai_saldo, 0, ',', '.') . ") dihapus";
                @log_activity('delete', 'saldo', "Saldo tertua otomatis dihapus: ID $id_saldo, Tgl $tgl_saldo, Nilai Rp " . number_format($nilai_saldo, 0, ',', '.'));
            } else {
                return ['success' => false, 'message' => 'Gagal menghapus saldo ID: ' . $id_saldo . ' - ' . $stmt_delete->error];
            }

            $stmt_delete->close();
        } else {
            // Jika sisa yang dikurangi < nilai saldo tertua, kurangi nilai saldo tersebut
            $saldo_baru = $nilai_saldo - $sisa_kurang;

            $query_update = "UPDATE tb_saldo SET saldo = ? WHERE id_saldo = ?";
            $stmt_update = $koneksi->prepare($query_update);
            $stmt_update->bind_param("di", $saldo_baru, $id_saldo);

            if ($stmt_update->execute()) {
                $updated_count++;
                $messages[] = "Saldo tertua (ID: $id_saldo, Tgl: $tgl_saldo) dikurangi dari Rp " . number_format($nilai_saldo, 0, ',', '.') . " menjadi Rp " . number_format($saldo_baru, 0, ',', '.');
                @log_activity('update', 'saldo', "Saldo dikurangi: ID $id_saldo dari Rp " . number_format($nilai_saldo, 0, ',', '.') . " menjadi Rp " . number_format($saldo_baru, 0, ',', '.'));
                $sisa_kurang = 0; // Sudah habis
            } else {
                return ['success' => false, 'message' => 'Gagal mengupdate saldo ID: ' . $id_saldo . ' - ' . $stmt_update->error];
            }

            $stmt_update->close();
        }
    }

    // Buat pesan hasil
    if ($deleted_count > 0 || $updated_count > 0) {
        $message = 'Saldo berhasil dikurangi sebesar Rp ' . number_format($jumlah, 0, ',', '.');
        if (count($messages) > 0) {
            $message .= '. ' . implode('; ', $messages);
        }
        return ['success' => true, 'message' => $message];
    } else {
        return ['success' => false, 'message' => 'Gagal mengurangi saldo'];
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
 * PENTING: Fungsi ini menghitung SUM dari SEMUA saldo POSITIF saja
 * Karena saldo negatif tidak lagi ditambahkan (langsung mengurangi dari saldo yang ada)
 *
 * @param mysqli $koneksi Koneksi database
 * @return float Total saldo (hanya positif, karena saldo negatif tidak ada)
 */
function get_total_saldo($koneksi) {
    if (!$koneksi) {
        return 0;
    }

    // Jumlahkan SEMUA saldo POSITIF dari semua record
    // Hanya menghitung saldo positif karena saldo negatif tidak lagi ditambahkan
    $query = "SELECT SUM(CAST(saldo AS DECIMAL(15,2))) as total FROM tb_saldo WHERE CAST(saldo AS DECIMAL(15,2)) > 0";
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

