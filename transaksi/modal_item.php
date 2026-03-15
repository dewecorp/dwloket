<div class="modal fade" id="modalItem">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">
                    <i class="fa fa-users"></i> PILIH PELANGGAN
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body table-responsive">
                <table class="table modern-table" id="zero_config" style="width: 100%;">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Nama Pelanggan</th>
                            <th>No. ID/PEL</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        $sql = $koneksi->query("SELECT * FROM pelanggan ORDER BY nama ASC");
                        while ($data = $sql->fetch_assoc()) {
                        ?>
                        <tr>
                            <td style="width: 5%"><?=$no++."."?></td>
                            <td><?=$data['nama']; ?></td>
                            <td><?=$data['no_idpel']; ?></td>
                            <td class="text-center">
                                <button class="btn btn-primary btn-sm select" data-id="<?=$data['id_pelanggan']; ?>" data-nama="<?=$data['nama']; ?>" data-idpel="<?=$data['no_idpel']; ?>">
                                    <i class="fa fa-check"></i> Pilih
                                </button>
                            </td>
                        </tr>
                        <?php
                        } ?>
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $(document).on('click', '.select', function() {
            var id = $(this).attr('data-id');
            var nama = $(this).attr('data-nama');
            var idpel = $(this).attr('data-idpel');
            
            $('#id_pelanggan').val(id);
            $('#nama').val(nama);
            $('#idpel').val(idpel);
            
            $('#modalItem').modal('hide');
        });
    });
</script>
