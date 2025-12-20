<?php
$sql = $koneksi->query("SELECT * FROM pelanggan");
while($data = $sql->fetch_assoc()){
?>
<div id="modaledit<?=$data['id_pelanggan'];?>" class="modal fade" tabindex="-1" role="dialog"
    aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">
                    <i class="fa fa-user-edit"></i> EDIT DATA PELANGGAN
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?=$data['id_pelanggan'];?>">
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" name="nama" class="form-control" placeholder="Nama Pelanggan"
                        value="<?=$data['nama'];?>">
                    </div>
                    <div class="form-group">
                        <label for="nama">No. ID/PEL</label>
                        <input type="text" name="idpel" class="form-control" placeholder="No. ID/PEL"
                        value="<?=$data['no_idpel'];?>">
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
$nama  = @$_POST['nama'];
$idpel = @$_POST['idpel'];
$koneksi->query("UPDATE  pelanggan SET nama='$nama', no_idpel='$idpel' WHERE id_pelanggan='$id'");
?>
<script>
Swal.fire({
position: 'top-center',
icon: 'success',
title: '<?=$nama;?>',
text: 'Berhasil Diedit',
showConfirmButton: true,
timer: 3000
}, 10);
window.setTimeout(function() {
document.location.href = '<?=base_url('
pelanggan')?>';
}, 1500);
</script>
<?php
}
}
?>
