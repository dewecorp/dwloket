<?php
// Bersihkan semua output buffer SEBELUM apapun
while (ob_get_level()) {
    ob_end_clean();
}

include_once('../config/config.php');
$id = @$_GET['id'];

// Validasi ID
if (empty($id) || !is_numeric($id)) {
    header('Location: ' . base_url('transaksi'));
    exit;
}

// Ambil data transaksi sebelum dihapus (untuk kembalikan saldo jika status Lunas)
$data_transaksi = null;
$data_query = $koneksi->query("SELECT status, harga FROM transaksi WHERE id_transaksi='$id' LIMIT 1");
if ($data_query && $data_query->num_rows > 0) {
    $data_transaksi = $data_query->fetch_assoc();
}

// Hapus data
$delete_sql = $koneksi->query("DELETE FROM transaksi WHERE id_transaksi='$id'");

if ($delete_sql) {
    // Log aktivitas hanya jika berhasil
    require_once '../libs/log_activity.php';
    @log_activity('delete', 'transaksi', 'Menghapus transaksi ID: ' . $id);

    // Kembalikan saldo jika transaksi yang dihapus statusnya Lunas
    if ($data_transaksi) {
        require_once '../libs/saldo_helper.php';
        $status = $data_transaksi['status'] ?? 'Belum';
        $harga = floatval($data_transaksi['harga'] ?? 0);
        proses_saldo_hapus_transaksi($koneksi, $id, $status, $harga);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hapus Transaksi</title>
    <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
</head>
<body>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($delete_sql): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Transaksi berhasil dihapus',
        confirmButtonColor: '#28a745',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
    }).then(() => {
        window.location.href="<?=base_url('transaksi')?>";
    });

    // Fallback redirect setelah 2.5 detik
    setTimeout(function() {
        window.location.href="<?=base_url('transaksi')?>";
    }, 2500);
    <?php else: ?>
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Gagal menghapus data. Data mungkin sudah tidak ada.',
        confirmButtonColor: '#dc3545'
    }).then(() => {
        window.location.href="<?=base_url('transaksi')?>";
    });

    // Fallback redirect setelah 2.5 detik
    setTimeout(function() {
        window.location.href="<?=base_url('transaksi')?>";
    }, 2500);
    <?php endif; ?>
});
</script>
</body>
</html>
