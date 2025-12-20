<?php
include_once('../header.php');
// $id = @$_GET['id'];
// $sql = $koneksi->query("SELECT * FROM tb_saldo WHERE id_saldo ='$id'");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Saldo</title>
</head>

<body>
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Total Saldo</h4>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                            <li class="breadcrumb-item text-muted active" aria-current="page"> Total Saldo</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">Data Total Saldo</h4>
                        <div class="float-right">

                        </div><br><br>
                        <div class="table-responsive">
                            <table id="zero_config" class="table table-striped table-bordered no-wrap">
                                <thead>
                                    <tr>
                                        <th style="width: 5px;">No</th>
                                        <th>Saldo Masuk</th>
                                        <th>Saldo Keluar</th>
                                        <th style="text-align: center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
										$no = 1;
										$sql = $koneksi->query("SELECT * FROM tb_saldo");
										while($data = $sql->fetch_assoc()) {


										?>
                                    <tr>
                                        <td><?=$no++.'.'?></td>
                                        <td>Rp. <?=number_format($data['saldo'], 0, ",", ".");?></td>
                                        <td>Rp. <?=number_format($data['saldo'], 0, ",", ".");?></td>
                                        <td align="center">
                                            <a data-toggle="modal"
                                                data-target="#modaledit<?=$data['id_total_saldo']; ?>"><button
                                                    class="btn btn-warning btn-sm"><i class="fa fa-edit"></i> Edit</button></a>
                                            <a href="hapus_saldo.php?id=<?=$data['id_total_saldo']; ?>"
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
</body>

</html>
<?php

include_once('../footer.php');
?>
