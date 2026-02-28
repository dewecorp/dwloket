<?php
// Bersihkan semua output buffer SEBELUM apapun
while (ob_get_level()) {
    ob_end_clean();
}

include_once('../config/config.php');
$id = @$_GET['id'];

// Validasi ID
if (empty($id) || !is_numeric($id)) {
    header('Location: ' . base_url('user'));
    exit;
}

// Ambil data sebelum hapus untuk log
$delete_sql = false;
$nama_user = '';

$stmt_select = $koneksi->prepare("SELECT * FROM tb_user WHERE id_user = ?");
$stmt_select->bind_param("i", $id);
$stmt_select->execute();
$sql = $stmt_select->get_result();

if ($sql && $sql->num_rows > 0) {
    $data = $sql->fetch_assoc();
    $nama_user = isset($data['nama']) ? $data['nama'] : 'ID: ' . $id;

    // Hapus data
    $stmt_delete = $koneksi->prepare("DELETE FROM tb_user WHERE id_user = ?");
    $stmt_delete->bind_param("i", $id);
    $delete_sql = $stmt_delete->execute();
    $stmt_delete->close();

    if ($delete_sql) {
        // Log aktivitas hanya jika berhasil
        require_once '../libs/log_activity.php';
        @log_activity('delete', 'user', 'Menghapus user: ' . $nama_user);
    }
} else {
    // Data tidak ditemukan
    $data = ['nama' => 'Data tidak ditemukan'];
    $nama_user = 'Data tidak ditemukan';
    $delete_sql = false;
}
$stmt_select->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Hapus User</title>
    <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
</head>
<body>
<script type="text/javascript">
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($delete_sql): ?>
    Swal.fire({
        icon: 'success',
        title: 'Berhasil!',
        text: '<?=htmlspecialchars($nama_user, ENT_QUOTES)?> Berhasil Dihapus',
        confirmButtonColor: '#28a745',
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
    }).then(() => {
        window.location.href="<?=base_url('user')?>";
    });

    // Fallback redirect setelah 2.5 detik
    setTimeout(function() {
        window.location.href="<?=base_url('user')?>";
    }, 2500);
    <?php else: ?>
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Gagal menghapus data. Data mungkin sudah tidak ada.',
        confirmButtonColor: '#dc3545'
    }).then(() => {
        window.location.href="<?=base_url('user')?>";
    });

    // Fallback redirect setelah 2.5 detik
    setTimeout(function() {
        window.location.href="<?=base_url('user')?>";
    }, 2500);
    <?php endif; ?>
});
</script>
</body>
</html>
