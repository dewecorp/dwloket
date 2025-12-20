<?php
include_once('../config/config.php');

// Start session jika belum
if (!isset($_SESSION)) {
    session_start();
}

if (isset($_POST['update_multiple']) && isset($_POST['produk']) && is_array($_POST['produk'])) {
    $updated_count = 0;
    $error_count = 0;
    $errors = [];

    foreach ($_POST['produk'] as $id_produk => $data) {
        $id_produk = intval($id_produk);
        $kode = mysqli_real_escape_string($koneksi, $data['kode'] ?? '');
        $produk = mysqli_real_escape_string($koneksi, $data['produk'] ?? '');
        $kategori = mysqli_real_escape_string($koneksi, $data['kategori'] ?? '');
        $harga = floatval($data['harga'] ?? 0);
        $status = intval($data['status'] ?? 1);

        if ($id_produk > 0 && !empty($produk) && !empty($kategori) && $harga > 0) {
            $update_query = "UPDATE tb_produk_orderkuota
                            SET produk = '$produk',
                                kategori = '$kategori',
                                harga = $harga,
                                status = $status,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE id_produk = $id_produk";

            if ($koneksi->query($update_query)) {
                $updated_count++;
            } else {
                $error_count++;
                $errors[] = "Gagal mengupdate produk ID $id_produk: " . $koneksi->error;
            }
        } else {
            $error_count++;
            $errors[] = "Data produk ID $id_produk tidak valid";
        }
    }

    if ($updated_count > 0) {
        $_SESSION['update_message'] = "Berhasil mengupdate $updated_count produk" . ($error_count > 0 ? " (gagal: $error_count)" : "");
        $_SESSION['update_success'] = true;
    } else {
        $_SESSION['update_message'] = "Gagal mengupdate produk. " . implode("; ", $errors);
        $_SESSION['update_success'] = false;
    }
} else {
    $_SESSION['update_message'] = 'Tidak ada produk yang dipilih untuk diupdate';
    $_SESSION['update_success'] = false;
}

header('Location: ' . base_url('jenisbayar/jenis_bayar.php'));
exit;
?>

