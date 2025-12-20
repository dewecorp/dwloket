<?php
// Bersihkan semua output buffer SEBELUM apapun
while (ob_get_level()) {
    ob_end_clean();
}

include_once('../config/config.php');

// Start session jika belum
if (!isset($_SESSION)) {
    session_start();
}

$id_produk = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id_produk > 0) {
    $delete_query = "DELETE FROM tb_produk_orderkuota WHERE id_produk = $id_produk";
    $delete_result = $koneksi->query($delete_query);
} else {
    $delete_result = false;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hapus Produk</title>
    <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
</head>
<body>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($delete_result): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Produk berhasil dihapus',
        confirmButtonColor: '#28a745',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
    }).then(() => {
        window.location.href = "<?=base_url('jenisbayar/jenis_bayar.php')?>";
    });
    setTimeout(function() {
        window.location.href = "<?=base_url('jenisbayar/jenis_bayar.php')?>";
    }, 2500);
    <?php else: ?>
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Gagal menghapus produk. <?=addslashes($koneksi->error ?? 'Data mungkin sudah tidak ada.')?>',
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'OK'
    }).then(() => {
        window.location.href = "<?=base_url('jenisbayar/jenis_bayar.php')?>";
    });
    <?php endif; ?>
});
</script>
</body>
</html>
<?php
exit;
?>


