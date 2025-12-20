<?php
include_once('../config/config.php');

// Start session jika belum
if (!isset($_SESSION)) {
    session_start();
}

if (isset($_POST['update_produk'])) {
    $id_produk = intval($_POST['id_produk']);
    $kode = mysqli_real_escape_string($koneksi, $_POST['kode'] ?? '');
    $produk = mysqli_real_escape_string($koneksi, $_POST['produk'] ?? '');
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);
    $status = intval($_POST['status'] ?? 1);

    // Ambil data lama sebelum update
    $old_data_query = "SELECT kode, produk, kategori, harga, status FROM tb_produk_orderkuota WHERE id_produk = $id_produk";
    $old_data_result = $koneksi->query($old_data_query);
    $old_data = $old_data_result ? $old_data_result->fetch_assoc() : null;

    $query = "UPDATE tb_produk_orderkuota
              SET produk = '$produk',
                  kategori = '$kategori',
                  harga = $harga,
                  status = $status,
                  updated_at = CURRENT_TIMESTAMP
              WHERE id_produk = $id_produk";

    if ($koneksi->query($query)) {
        // Buat detail perubahan
        $changes = [];
        if ($old_data) {
            if ($old_data['produk'] != $produk) {
                $changes[] = "Produk: " . htmlspecialchars($old_data['produk']) . " → " . htmlspecialchars($produk);
            }
            if ($old_data['kategori'] != $kategori) {
                $changes[] = "Kategori: " . htmlspecialchars($old_data['kategori']) . " → " . htmlspecialchars($kategori);
            }
            if (intval($old_data['harga']) != $harga) {
                $old_harga_format = number_format(intval($old_data['harga']), 0, ',', '.');
                $new_harga_format = number_format($harga, 0, ',', '.');
                $changes[] = "Harga: Rp $old_harga_format → Rp $new_harga_format";
            }
            if ($old_data['status'] != $status) {
                $old_status = $old_data['status'] == 1 ? 'Aktif' : 'Tidak Aktif';
                $new_status = $status == 1 ? 'Aktif' : 'Tidak Aktif';
                $changes[] = "Status: $old_status → $new_status";
            }
        }

        if (!empty($changes)) {
            $message_html = "Produk <strong>" . htmlspecialchars($kode) . "</strong> berhasil diupdate:<br><ul style='text-align: left; display: inline-block; margin-top: 10px;'><li>" . implode("</li><li>", $changes) . "</li></ul>";
            $_SESSION['update_message'] = $message_html;
            $_SESSION['update_message_html'] = true;
        } else {
            $message_html = "Produk <strong>" . htmlspecialchars($kode) . "</strong> berhasil diupdate (tidak ada perubahan)";
            $_SESSION['update_message'] = $message_html;
            $_SESSION['update_message_html'] = true;
        }
        $_SESSION['update_success'] = true;
    } else {
        $_SESSION['update_message'] = 'Gagal mengupdate data produk: ' . $koneksi->error;
        $_SESSION['update_success'] = false;
    }
}

header('Location: ' . base_url('jenisbayar/jenis_bayar.php'));
exit;
?>


