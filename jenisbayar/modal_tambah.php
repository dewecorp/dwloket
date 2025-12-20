<div id="modaltambah" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">
                    <i class="fa fa-plus-circle"></i> INPUT JENIS BAYAR
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="jenis">Jenis Pembayaran</label>
                        <input type="text" name="jenis" class="form-control" placeholder="Jenis Pembayaran" autofocus
                        required>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="submit" name="simpan" class="btn btn-success btn-sm" value="Simpan">
                    <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
if(@$_POST['simpan']) {
    $jenis = @$_POST['jenis'];
    $sql = $koneksi->query("INSERT INTO tb_jenisbayar (jenis_bayar) VALUES ('$jenis')");
    if ($sql) {
        // Script SweetAlert dan redirect setelah sukses
        // SweetAlert library sudah dimuat di footer.php
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        position: 'top-center',
        icon: 'success',
        title: '<?=htmlspecialchars($jenis, ENT_QUOTES)?>',
        text: 'Berhasil Ditambahkan',
        showConfirmButton: true,
        confirmButtonColor: '#28a745',
        timer: 3000,
        timerProgressBar: true
    }).then(function() {
        window.location.href = '<?=base_url('jenisbayar/jenis_bayar.php')?>';
    });

    // Fallback redirect setelah 3.5 detik
    setTimeout(function() {
        window.location.href = '<?=base_url('jenisbayar/jenis_bayar.php')?>';
    }, 3500);
});
</script>
<?php
    } else {
        // Jika gagal insert
        // SweetAlert library sudah dimuat di footer.php
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Gagal menambahkan jenis pembayaran. Silakan coba lagi.',
        confirmButtonColor: '#dc3545'
    });
});
</script>
<?php
    }
}
?>
