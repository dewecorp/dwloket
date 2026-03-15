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
                            <style>
                                #zero_config th, #zero_config td { vertical-align: middle !important; }
                                #zero_config td { padding: 12px 14px !important; }
                                #zero_config th { white-space: nowrap; }
                                #zero_config .col-no { width: 60px; }
                                #zero_config .col-foto { width: 90px; }
                                #zero_config .col-level { width: 90px; }
                                #zero_config .col-aksi { width: 200px; }
                                #zero_config .user-photo {
                                    width: 56px;
                                    height: 56px;
                                    border-radius: 10px;
                                    object-fit: cover;
                                    display: block;
                                    background: #f1f3f5;
                                }
                                #zero_config .cell-clip {
                                    max-width: 280px;
                                    white-space: nowrap;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                }
                                #zero_config .password-cell {
                                    max-width: 220px;
                                    white-space: nowrap;
                                    overflow: hidden;
                                    text-overflow: ellipsis;
                                    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                                    font-size: 0.85rem;
                                }

                                #modaltambah .modal-body,
                                [id^="modaledit"] .modal-body {
                                    padding: 1.5rem;
                                }

                                #modaltambah .modal-body .form-control,
                                [id^="modaledit"] .modal-body .form-control {
                                    min-height: 44px;
                                    height: 44px;
                                    padding: 8px 12px;
                                    line-height: 1.5;
                                }

                                #modaltambah .modal-body select.form-control,
                                [id^="modaledit"] .modal-body select.form-control {
                                    padding-right: 2.25rem;
                                }

                                #modaltambah .modal-body input[type="file"].form-control,
                                [id^="modaledit"] .modal-body input[type="file"].form-control {
                                    height: auto;
                                    min-height: 44px;
                                    padding: 7px 12px;
                                }

                                #modaltambah .modal-body .form-group,
                                [id^="modaledit"] .modal-body .form-group {
                                    margin-bottom: 1rem;
                                }

                                #modaltambah .modal-body label,
                                [id^="modaledit"] .modal-body label {
                                    margin-bottom: 0.35rem;
                                    font-weight: 600;
                                }
                            </style>
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
                                <table id="zero_config" class="table modern-table">
                                    <thead>
                                        <tr>
                                            <th class="col-no">No</th>
                                            <th>Username</th>
                                            <th>Password</th>
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th class="col-foto">Foto</th>
                                            <th class="col-level">Level</th>
                                            <th class="col-aksi" style="text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        $sql = $koneksi->query("SELECT * FROM tb_user ORDER BY nama ASC");
                                        while($data = $sql->fetch_assoc()) {
                                        $level = ($data['level'] == 'admin')? "Admin" : "User";
                                        $password_raw = (string)($data['password'] ?? '');
                                        $is_hashed = (strpos($password_raw, '$2y$') === 0) || (strpos($password_raw, '$2a$') === 0) || (strpos($password_raw, '$2b$') === 0) || (strpos($password_raw, '$argon2') === 0);
                                        $password_display = $is_hashed ? 'terenkripsi' : $password_raw;
                                        $foto = !empty($data['foto']) ? $data['foto'] : 'default.png';
                                        ?>
                                        <tr>
                                            <td><?=$no++.'.'?></td>
                                            <td class="cell-clip"><?=htmlspecialchars($data['username']);?></td>
                                            <td class="password-cell"><?=htmlspecialchars($password_display);?></td>
                                            <td class="cell-clip"><?=htmlspecialchars($data['nama']);?></td>
                                            <td class="cell-clip"><?=htmlspecialchars($data['email']);?></td>
                                            <td>
                                                <img src="<?=base_url()?>/files/assets/images/<?=htmlspecialchars($foto);?>" alt="foto" class="user-photo">
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
