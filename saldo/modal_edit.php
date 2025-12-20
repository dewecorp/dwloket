<?php
$sql = $koneksi->query("SELECT * FROM tb_saldo");
while($data = $sql->fetch_assoc()){
$tgl = $data['tgl'];
?>
<div id="modaledit<?=$data['id_saldo'];?>" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">
                    <i class="fa fa-edit"></i> EDIT DATA SALDO
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-lg-12">
                            <input type="hidden" name="id" value="<?=$data['id_saldo'];?>">
                            <div class="form-group">
                                <label for="tanggal">Tanggal Deposit</label>
                                <input type="date" name="tgl" class="form-control" value="<?=date('Y-m-d', strtotime($tgl));?>">
                            </div>
                            <div class="form-group">
                                <label for="saldo">Jumlah Saldo</label>
                                <input type="number" name="saldo" class="form-control" value="<?=$data['saldo'];?>">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="submit" name="edit" class="btn btn-success btn-sm" value="Simpan">
                    <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php
if(@$_POST['edit']) {
$id    = @$_POST['id'];
$tgl   = @$_POST['tgl'];
$saldo = @$_POST['saldo'];
$sql = $koneksi->query("UPDATE tb_saldo SET tgl ='$tgl', saldo ='$saldo' WHERE id_saldo ='$id'");
if ($sql)  {
?>
<script>
Swal.fire({
position: 'top-center',
icon: 'success',
title: 'Saldo',
text: 'Berhasil Diedit',
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
}
?>
