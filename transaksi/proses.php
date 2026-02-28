<?php
include_once('../config/config.php');

$id = @$_GET['id'];
// $saldo_sekarang = $koneksi->query("SELECT * FROM tb_saldo WHERE id_saldo ='$id'");
if (@$_POST['simpan']) {
    $tgl = @$_POST['tgl'];
    $idpel = @$_POST['idpel'];
    $nama = @$_POST['nama'];
    $jenis = @$_POST['jenis'];
    $harga = @$_POST['harga'];
    $status = @$_POST['status'];
    
    // Prepared Statement for INSERT
    $stmt = $koneksi->prepare("INSERT INTO transaksi (tgl, idpel, nama, id_bayar, harga, status ) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssds", $tgl, $idpel, $nama, $jenis, $harga, $status);
    $result = $stmt->execute();
    $stmt->close();

    if ($result) {
        // Log aktivitas
        require_once '../libs/log_activity.php';
        @log_activity('create', 'transaksi', 'Menambah transaksi: ' . $nama . ' (ID: ' . $idpel . ')');
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Proses Transaksi</title>
            <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
        </head>
        <body>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Transaksi Berhasil Ditambahkan',
                confirmButtonColor: '#28a745',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => {
                window.location.href = "<?=base_url('transaksi')?>";
            });
        </script>
        </body>
        </html>
        <?php
    } else {
         ?>
        <script>
            alert('Gagal menyimpan transaksi');
            window.location.href = "<?=base_url('transaksi')?>";
        </script>
        <?php
    }

} else if(@$_POST['edit']) {
    $id = @$_POST['id'];
    $tgl = @$_POST['tgl'];
    $idpel = @$_POST['idpel'];
    $nama = @$_POST['nama'];
    $jenis = @$_POST['jenis'];
    $harga = @$_POST['harga'];
    $status = @$_POST['status'];

    // Prepared Statement for UPDATE
    $stmt = $koneksi->prepare("UPDATE transaksi SET tgl=?, idpel=?, nama=?, id_bayar=?, harga=?, status=? WHERE id_transaksi=?");
    $stmt->bind_param("ssssdsi", $tgl, $idpel, $nama, $jenis, $harga, $status, $id);
    $result = $stmt->execute();
    $stmt->close();

    if ($result) {
        // Log aktivitas
        require_once '../libs/log_activity.php';
        @log_activity('update', 'transaksi', 'Mengedit transaksi ID: ' . $id);
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Proses Transaksi</title>
            <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
        </head>
        <body>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Berhasil!',
                text: 'Transaksi Berhasil Diedit',
                confirmButtonColor: '#28a745',
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false
            }).then(() => {
                window.location.href = "<?=base_url('transaksi')?>";
            });
        </script>
        </body>
        </html>
        <?php
    }
}
?>
