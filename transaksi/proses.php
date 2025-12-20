<?php
// Pastikan tidak ada output sebelum ini
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

include_once('../config/config.php');

if (isset($_POST['simpan'])) {
    $tgl = mysqli_real_escape_string($koneksi, $_POST['tgl']);
    $idpel = mysqli_real_escape_string($koneksi, $_POST['idpel']);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $jenis = intval($_POST['jenis']);
    $harga = intval($_POST['harga']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);
    $ket = mysqli_real_escape_string($koneksi, $_POST['ket'] ?? '');
    $selected_produk_id = isset($_POST['selected_produk_id']) ? intval($_POST['selected_produk_id']) : 0;

    // Cek apakah kolom selected_produk_id ada
    $check_column = $koneksi->query("SHOW COLUMNS FROM transaksi LIKE 'selected_produk_id'");
    $has_selected_produk_id = ($check_column && $check_column->num_rows > 0);

    if ($has_selected_produk_id && $selected_produk_id > 0) {
        $sql = $koneksi->query("INSERT INTO transaksi (tgl, idpel, nama, id_bayar, harga, status, ket, selected_produk_id) VALUES ('$tgl', '$idpel', '$nama', $jenis, '$harga', '$status', '$ket', $selected_produk_id)");
    } else {
        $sql = $koneksi->query("INSERT INTO transaksi (tgl, idpel, nama, id_bayar, harga, status, ket) VALUES ('$tgl', '$idpel', '$nama', $jenis, '$harga', '$status', '$ket')");
    }

    ob_end_clean();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Simpan Transaksi</title>
        <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
    </head>
    <body>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($sql): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Transaksi berhasil ditambahkan!',
            confirmButtonColor: '#28a745',
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => {
            window.location.href = "<?=base_url('transaksi/transaksi.php')?>";
        });
        setTimeout(function() {
            window.location.href = "<?=base_url('transaksi/transaksi.php')?>";
        }, 2500);
        <?php else: ?>
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: 'Gagal menyimpan: <?=addslashes($koneksi->error)?>',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = "<?=base_url('transaksi/tambah.php')?>";
        });
        <?php endif; ?>
    });
    </script>
    </body>
    </html>
    <?php
    exit;
}

if (isset($_POST['edit'])) {
    $id = intval($_POST['id']);
    $tgl = mysqli_real_escape_string($koneksi, $_POST['tgl']);
    $idpel = mysqli_real_escape_string($koneksi, $_POST['idpel']);
    $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
    $jenis = intval($_POST['jenis']);
    $harga = intval($_POST['harga']);
    $status = mysqli_real_escape_string($koneksi, $_POST['status']);
    $ket = mysqli_real_escape_string($koneksi, $_POST['ket'] ?? '');
    $selected_produk_id = isset($_POST['selected_produk_id']) ? intval($_POST['selected_produk_id']) : 0;

    // Cek apakah kolom selected_produk_id ada
    $check_column = $koneksi->query("SHOW COLUMNS FROM transaksi LIKE 'selected_produk_id'");
    $has_selected_produk_id = ($check_column && $check_column->num_rows > 0);

    if ($has_selected_produk_id && $selected_produk_id > 0) {
        $sql = $koneksi->query("UPDATE transaksi SET tgl='$tgl', idpel='$idpel', nama='$nama', id_bayar=$jenis, harga='$harga', status='$status', ket='$ket', selected_produk_id=$selected_produk_id WHERE id_transaksi=$id");
    } else {
        $sql = $koneksi->query("UPDATE transaksi SET tgl='$tgl', idpel='$idpel', nama='$nama', id_bayar=$jenis, harga='$harga', status='$status', ket='$ket' WHERE id_transaksi=$id");
    }

    ob_end_clean();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Edit Transaksi</title>
        <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
    </head>
    <body>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        <?php if ($sql): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Transaksi berhasil diedit!',
            confirmButtonColor: '#28a745',
            timer: 2000,
            timerProgressBar: true,
            showConfirmButton: false
        }).then(() => {
            window.location.href = "<?=base_url('transaksi/transaksi.php')?>";
        });
        setTimeout(function() {
            window.location.href = "<?=base_url('transaksi/transaksi.php')?>";
        }, 2500);
        <?php else: ?>
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: 'Gagal mengupdate: <?=addslashes($koneksi->error)?>',
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'OK'
        }).then(() => {
            window.location.href = "<?=base_url('transaksi/edit.php?id=' . $id)?>";
        });
        <?php endif; ?>
    });
    </script>
    </body>
    </html>
    <?php
    exit;
}

ob_end_clean();
header('Location: ' . base_url('transaksi/tambah.php'));
exit;
