<?php
// Bersihkan semua output buffer SEBELUM apapun
while (ob_get_level()) {
    ob_end_clean();
}

include_once('../config/config.php');
$id = @$_GET['id'];

// Validasi ID
if (empty($id) || !is_numeric($id)) {
    header('Location: ' . base_url('jenisbayar/jenis_bayar.php'));
    exit;
}

// Ambil data sebelum hapus untuk log
$delete_sql = false;
$jenis_bayar = '';

$stmt = $koneksi->prepare("SELECT * FROM tb_jenisbayar WHERE id_bayar = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$sql = $stmt->get_result();

if ($sql && $sql->num_rows > 0) {
    $data = $sql->fetch_assoc();
    $jenis_bayar = isset($data['jenis_bayar']) ? $data['jenis_bayar'] : 'ID: ' . $id;

    // Hapus data
    $stmt_del = $koneksi->prepare("DELETE FROM tb_jenisbayar WHERE id_bayar = ?");
    $stmt_del->bind_param("i", $id);
    $delete_sql = $stmt_del->execute();

    if ($delete_sql) {
        // Log aktivitas hanya jika berhasil
        require_once '../libs/log_activity.php';
        @log_activity('delete', 'jenisbayar', 'Menghapus jenis pembayaran: ' . $jenis_bayar);
    }
} else {
    // Data tidak ditemukan
    $jenis_bayar = 'Data tidak ditemukan';
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hapus Jenis Bayar</title>
    <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
</head>
<body>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($delete_sql): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '<?=htmlspecialchars($jenis_bayar, ENT_QUOTES)?> Berhasil Dihapus',
        confirmButtonColor: '#28a745',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
    }).then(() => {
        window.location.href="<?=base_url('jenisbayar/jenis_bayar.php')?>";
    });

    // Fallback redirect setelah 2.5 detik
    setTimeout(function() {
        window.location.href="<?=base_url('jenisbayar/jenis_bayar.php')?>";
    }, 2500);
    <?php else: ?>
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Gagal menghapus data. Data mungkin sudah tidak ada.',
        confirmButtonColor: '#dc3545'
    }).then(() => {
        window.location.href="<?=base_url('jenisbayar/jenis_bayar.php')?>";
    });

    // Fallback redirect setelah 2.5 detik
    setTimeout(function() {
        window.location.href="<?=base_url('jenisbayar/jenis_bayar.php')?>";
    }, 2500);
    <?php endif; ?>
});
</script>
</body>
</html>
