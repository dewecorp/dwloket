<?php
include_once('../header.php');
?>
<body>
        <div class="page-breadcrumb">
            <div class="row">
                <div class="col-7 align-self-center">
                    <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Saldo</h4>
                    <div class="d-flex align-items-center">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb m-0 p-0">
                                <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item text-muted active" aria-current="page">Saldo</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="modern-card">
                        <div class="modern-card-header">
                            <h4>
                                <i class="fa fa-credit-card"></i> Data Saldo
                            </h4>
                        </div>
                        <div class="modern-card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                                <div class="mb-2 mb-md-0"></div>
                                <div class="d-flex align-items-center">
                                    <a href="<?=base_url('export_excel.php?page=saldo')?>" class="btn btn-success btn-sm mr-2">
                                        <i class="fa fa-file-excel"></i> Excel
                                    </a>
                                    <a href="<?=base_url('export_pdf.php?page=saldo')?>" target="_blank" class="btn btn-danger btn-sm mr-2">
                                        <i class="fa fa-file-pdf"></i> PDF
                                    </a>
                                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                                    data-target="#modaltambah"><i class="fa fa-plus"></i> Tambah</button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table id="zero_config" class="table modern-table no-wrap">
                                    <thead>
                                        <tr>
                                            <th style="width: 5px;">No</th>
                                            <th>Tanggal Deposit</th>
                                            <th>Jumlah Saldo</th>
                                            <th style="text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        $sql = $koneksi->query("SELECT * FROM tb_saldo ORDER BY tgl DESC");
                                        while($data = $sql->fetch_assoc()) {
                                        $tgl = $data['tgl'];

                                        ?>
                                        <tr>
                                            <td><?=$no++.'.'?></td>
                                            <td><?=date('d/m/Y', strtotime($tgl));?></td>
                                            <td>Rp. <?=number_format($data['saldo'], 0, ",", ".");?></td>
                                            <td align="center">
                                                <a data-toggle="modal"
                                                    data-target="#modaledit<?=$data['id_saldo']; ?>"><button
                                                    class="btn btn-warning btn-sm"><i class="fa fa-edit"></i> Edit</button></a>
                                                <a href="hapus_saldo.php?id=<?=$data['id_saldo']; ?>"
                                                    onclick="return swalConfirmDelete(this.href, 'Yakin Hapus Saldo?', 'Data saldo ini akan dihapus secara permanen!')"
                                                    class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Hapus</a>
                                            </td>
                                        </tr>
                                        <?php
                                        }?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        include_once('../footer.php');
        ?>
    </body>
</html>
<?php
include"modal_tambah.php";
include"modal_edit.php";
?>
