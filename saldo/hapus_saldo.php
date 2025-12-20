<?php
// Bersihkan semua output buffer SEBELUM apapun
while (ob_get_level()) {
    ob_end_clean();
}

include_once('../config/config.php');
$id = @$_GET['id'];

// Validasi ID
if (empty($id) || !is_numeric($id)) {
    header('Location: ' . base_url('saldo'));
    exit;
}

// Hapus data
$delete_sql = $koneksi->query("DELETE FROM tb_saldo WHERE id_saldo ='$id'");

if ($delete_sql) {
    // Log aktivitas hanya jika berhasil
    require_once '../libs/log_activity.php';
    @log_activity('delete', 'saldo', 'Menghapus saldo ID: ' . $id);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hapus Saldo</title>
    <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
</head>
<body>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($delete_sql): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Saldo Berhasil Dihapus',
        confirmButtonColor: '#28a745',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
    }).then(() => {
        window.location.href="<?=base_url('saldo')?>";
    });

    // Fallback redirect setelah 2.5 detik
    setTimeout(function() {
        window.location.href="<?=base_url('saldo')?>";
    }, 2500);
    <?php else: ?>
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Gagal menghapus data. Data mungkin sudah tidak ada.',
        confirmButtonColor: '#dc3545'
    }).then(() => {
        window.location.href="<?=base_url('saldo')?>";
    });

    // Fallback redirect setelah 2.5 detik
    setTimeout(function() {
        window.location.href="<?=base_url('saldo')?>";
    }, 2500);
    <?php endif; ?>
});
</script>
</body>
</html>
