<div id="modaltambah" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">
                    <i class="fa fa-plus-circle"></i> INPUT DATA SALDO
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12">

                            <div class="form-group">
                                <label for="tgl">Tanggal Deposit</label>
                                <input type="date" name="tgl" class="form-control" value="<?=date('Y-m-d');?>" autofocus
                                required>
                            </div>
                            <div class="form-group">
                                <label for="saldo">Jumlah Saldo</label>
                                <input type="number" name="saldo" class="form-control" placeholder="Jumlah Saldo"
                                required>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="submit" name="tambah" class="btn btn-success btn-sm" value="Simpan">
                    <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
if (@$_POST['tambah']) {
$tgl   = $_POST['tgl'];
$saldo = $_POST['saldo'];
$sql = $koneksi->query("INSERT INTO tb_saldo (tgl, saldo) VALUES ('$tgl', '$saldo')");
if ($sql) {
?>
<script>
Swal.fire({
position: 'top-center',
icon: 'success',
title: 'Sukses',
text: 'Saldo Berhasil Ditambahkan',
showConfirmButton: true,
timer: 3000
},10);
window.setTimeout(function(){
document.location.href='<?=base_url('saldo')?>';
} ,1500);
</script>
<?php
} else {
?>
<script type="text/javascript">
Swal.fire({
position: 'top-center',
icon: 'error',
title: 'Maaf',
text: 'Saldo Gagal Ditambahkan',
showConfirmButton: true,
timer: 3000
},10);
window.setTimeout(function(){
document.location.href='<?=base_url('saldo')?>';
} ,1500);
</script>
<?php
}
}
?>
