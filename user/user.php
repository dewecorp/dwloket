<?php
$page_title = 'User';
include_once('../header.php');

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <div class="page-breadcrumb">
            <div class="row">
                <div class="col-7 align-self-center">
                    <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">User</h4>
                    <div class="d-flex align-items-center">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb m-0 p-0">
                                <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item text-muted active" aria-current="page">User</li>
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
                                <i class="fa fa-user"></i> Data User
                            </h4>
                        </div>
                        <div class="modern-card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                                <div class="mb-2 mb-md-0"></div>
                                <div class="d-flex align-items-center">
                                    <a href="<?=base_url('export_excel.php?page=user')?>" class="btn btn-success btn-sm mr-2">
                                        <i class="fa fa-file-excel"></i> Excel
                                    </a>
                                    <a href="<?=base_url('export_pdf.php?page=user')?>" target="_blank" class="btn btn-danger btn-sm mr-2">
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
                                            <th>Username</th>
                                            <th>Password</th>
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th>Foto</th>
                                            <th>Level</th>
                                            <th style="text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        $sql = $koneksi->query("SELECT * FROM tb_user ORDER BY nama ASC");
                                        while($data = $sql->fetch_assoc()) {
                                        $level = ($data['level'] == 'admin')? "Admin" : "User";
                                        ?>
                                        <tr>
                                            <td><?=$no++.'.'?></td>
                                            <td><?=$data['username'];?></td>
                                            <td><?=$data['password'];?></td>
                                            <td><?=$data['nama'];?></td>
                                            <td><?=$data['email'];?></td>
                                            <td>
                                                <img src="<?=base_url()?>/files/assets/images/<?=$data['foto'];?>" alt="foto" width="100">
                                            </td>
                                            <td><?=$level?></td>
                                            <td align="center">
                                                <a data-toggle="modal" data-target="#modaledit<?=$data['id_user']; ?>"><button
                                                    class="btn btn-warning btn-sm"><i class="fa fa-edit"></i> Edit</button></a>
                                                <a href="hapus_user.php?id=<?=$data['id_user']; ?>"
                                                    onclick="return swalConfirmDelete(this.href, 'Yakin Hapus User?', 'Data user ini akan dihapus secara permanen!')"
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
