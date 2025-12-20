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

    $sql = $koneksi->query("INSERT INTO transaksi (tgl, idpel, nama, id_bayar, harga, status, ket) VALUES ('$tgl', '$idpel', '$nama', $jenis, '$harga', '$status', '$ket')");

    if ($sql) {
        $_SESSION['success_message'] = 'Transaksi berhasil ditambahkan!';
        $_SESSION['success_success'] = true;
    } else {
        $_SESSION['error_message'] = 'Gagal menyimpan: ' . $koneksi->error;
        $_SESSION['error_success'] = false;
    }

    ob_end_clean();
    header('Location: ' . base_url('transaksi/transaksi.php'));
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

    $sql = $koneksi->query("UPDATE transaksi SET tgl='$tgl', idpel='$idpel', nama='$nama', id_bayar=$jenis, harga='$harga', status='$status', ket='$ket' WHERE id_transaksi=$id");

    if ($sql) {
        $_SESSION['success_message'] = 'Transaksi berhasil diedit!';
        $_SESSION['success_success'] = true;
    } else {
        $_SESSION['error_message'] = 'Gagal mengupdate: ' . $koneksi->error;
        $_SESSION['error_success'] = false;
    }

    ob_end_clean();
    header('Location: ' . base_url('transaksi/transaksi.php'));
    exit;
}

ob_end_clean();
header('Location: ' . base_url('transaksi/tambah.php'));
exit;
