<?php
// Bersihkan semua output buffer SEBELUM apapun
while (ob_get_level()) {
    ob_end_clean();
}

include_once('../config/config.php');
$id = @$_GET['id'];

// Validasi ID
if (empty($id) || !is_numeric($id)) {
    header('Location: ' . base_url('pelanggan'));
    exit;
}

// Ambil data sebelum hapus untuk log
$delete_sql = false;
$nama_pelanggan = '';
$sql = $koneksi->query("SELECT * FROM pelanggan WHERE id_pelanggan='$id'");
if ($sql && $sql->num_rows > 0) {
    $data = $sql->fetch_assoc();
    $nama_pelanggan = isset($data['nama']) ? $data['nama'] : 'ID: ' . $id;

    // Hapus data
    $delete_sql = $koneksi->query("DELETE FROM pelanggan WHERE id_pelanggan='$id'");

    if ($delete_sql) {
        // Log aktivitas hanya jika berhasil
        require_once '../libs/log_activity.php';
        @log_activity('delete', 'pelanggan', 'Menghapus pelanggan: ' . $nama_pelanggan);
    }
} else {
    // Data tidak ditemukan
    $data = ['nama' => 'Data tidak ditemukan'];
    $nama_pelanggan = 'Data tidak ditemukan';
    $delete_sql = false;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hapus Pelanggan</title>
    <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
</head>
<body>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($delete_sql): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: 'Pelanggan <?=htmlspecialchars($nama_pelanggan, ENT_QUOTES)?> berhasil dihapus',
        confirmButtonColor: '#28a745',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
    }).then(() => {
        window.location.href = "<?=base_url('pelanggan')?>";
    });

    // Fallback redirect setelah 2.5 detik
    setTimeout(function() {
        window.location.href = "<?=base_url('pelanggan')?>";
    }, 2500);
    <?php else: ?>
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Gagal menghapus data. Data mungkin sudah tidak ada.',
        confirmButtonColor: '#dc3545'
    }).then(() => {
        window.location.href = "<?=base_url('pelanggan')?>";
    });

    // Fallback redirect setelah 2.5 detik
    setTimeout(function() {
        window.location.href = "<?=base_url('pelanggan')?>";
    }, 2500);
    <?php endif; ?>
});
</script>
</body>
</html>
