<?php
// Generate modal untuk setiap pelanggan
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
                        value="<?=htmlspecialchars($data['nama']);?>" required>
                    </div>
                    <div class="form-group">
                        <label for="idpel">No. ID/PEL</label>
                        <input type="text" name="idpel" class="form-control" placeholder="No. ID/PEL"
                        value="<?=htmlspecialchars($data['no_idpel']);?>" required>
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
}
?>
