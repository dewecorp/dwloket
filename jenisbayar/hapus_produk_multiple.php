<?php
// Handle hapus multiple produk SEBELUM include config untuk menghindari blank page
if (isset($_POST['id_produk']) && is_array($_POST['id_produk'])) {
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

    $deleted_count = 0;
    $error_count = 0;
    $errors = [];
    foreach ($_POST['id_produk'] as $id_produk) {
        $id_produk = intval($id_produk);

        if ($id_produk > 0) {
            $delete_query = "DELETE FROM tb_produk_orderkuota WHERE id_produk = $id_produk";

            if ($koneksi->query($delete_query)) {
                $deleted_count++;
            } else {
                $error_count++;
                $errors[] = "Gagal menghapus produk ID $id_produk: " . $koneksi->error;
            }
        }
    }

    $success = false;
    $message = '';

    if ($deleted_count > 0) {
        $message = "Berhasil menghapus $deleted_count produk" . ($error_count > 0 ? " (gagal: $error_count)" : "");
        $success = true;
    } else {
        $message = "Gagal menghapus produk. " . implode("; ", $errors);
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
    <title>Hapus Multiple Produk</title>
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

