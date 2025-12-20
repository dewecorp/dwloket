<?php
// Handle update multiple produk SEBELUM include config untuk menghindari blank page
if (isset($_POST['update_multiple']) && isset($_POST['produk']) && is_array($_POST['produk'])) {
    // Bersihkan semua output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();

    include_once('../config/config.php');

    // Start session jika belum
    if (!isset($_SESSION)) {
        session_start();
    }
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

    $success = false;
    $message = '';

    if ($updated_count > 0) {
        $message = "Berhasil mengupdate $updated_count produk" . ($error_count > 0 ? " (gagal: $error_count)" : "");
        $success = true;
    } else {
        $message = "Gagal mengupdate produk. " . implode("; ", $errors);
        $success = false;
    }
} else {
    // Jika tidak ada POST, redirect
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Location: ' . base_url('jenisbayar/jenis_bayar.php'));
    exit;
}

ob_end_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Update Multiple Produk</title>
    <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
</head>
<body>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: '<?=$success ? 'success' : 'error'?>',
        title: '<?=$success ? 'Berhasil!' : 'Gagal!'?>',
        text: '<?=addslashes($message)?>',
        confirmButtonColor: '<?=$success ? '#28a745' : '#dc3545'?>',
        <?php if ($success): ?>
        timer: 3000,
        timerProgressBar: true,
        showConfirmButton: false
        <?php else: ?>
        confirmButtonText: 'OK'
        <?php endif; ?>
    }).then(() => {
        window.location.href = "<?=base_url('jenisbayar/jenis_bayar.php')?>";
    });
    <?php if ($success): ?>
    setTimeout(function() {
        window.location.href = "<?=base_url('jenisbayar/jenis_bayar.php')?>";
    }, 3500);
    <?php endif; ?>
});
</script>
</body>
</html>
<?php
exit;
?>

