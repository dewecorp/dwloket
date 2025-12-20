<div id="modaltambah" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">
                    <i class="fa fa-user-plus"></i> INPUT DATA PELANGGAN
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <form action="" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nama">Nama</label>
                        <input type="text" name="nama" class="form-control" placeholder="Nama Pelanggan" autofocus
                        required>
                    </div>
                    <div class="form-group">
                        <label for="idpel">No. ID/PEL</label>
                        <input type="text" name="idpel" class="form-control" placeholder="No. ID/PEL" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <input type="submit" name="simpan" class="btn btn-success btn-sm" value="Simpan">
                    <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>    <?php
    if(@$_POST['simpan']) {
    $nama = @$_POST['nama'];
    $idpel = @$_POST['idpel'];
    $koneksi->query("INSERT INTO pelanggan (nama, no_idpel) VALUES ('$nama', '$idpel')");
    ?>
    <script>
    Swal.fire({
    position: 'top-center',
    icon: 'success',
    title: '<?=$nama;?>',
    text: 'Berhasil Ditambahkan',
    showConfirmButton: true,
    timer: 3000
    }, 10);
    window.setTimeout(function() {
    document.location.href = '<?=base_url('pelanggan')?>';
    }, 1500);
    </script>
    <?php
    }
    ?>
