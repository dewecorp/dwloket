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
    $(".select").click(function() {
        document.getElementById("id_pelanggan").value = $(this).attr('data-id');
        document.getElementById("nama").value = $(this).attr('data-nama');
        document.getElementById("idpel").value = $(this).attr('data-idpel');
        // var id_pelanggan = $(this).data('id_pelanggan');
        // var nama = $(this).data('nama');
        // var no_idpel = $(this).data('idpel');
        // $('#id_pelanggan').val(id_pelanggan);
        // $('#nama').val(nama);
        // $('#idpel').val(no_idpel);
        // $('#modal-item').modal('hide').on('hidden.bs.modal', functionThatEndsUpDestroyingTheDOM);
        $('#modalItem').modal('hide');

    })

</script>
