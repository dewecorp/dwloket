<?php
include_once('../header.php');
include_once('../config/config.php');
$id = @$_GET['id'];
$sql = $koneksi->query("SELECT * FROM tb_user WHERE id_user ='$id'");
while ($data = $sql->fetch_assoc()) {
$level = ($data['level'] == 'admin')? "Admin" : "User";
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Profil User</title>
    </head>
    <body>
        <div class="page-breadcrumb">
            <div class="row">
                <div class="col-7 align-self-center">
                    <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">User</h4>
                    <div class="d-flex align-items-center">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb m-0 p-0">
                                <li class="breadcrumb-item"><a href="index.php" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item text-muted active" aria-current="page">Profil User</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-sm-12">
                    <div class="card">
                        <div class="card-body">
                            <h4 class="card-title">Profil User</h4><br>
                            <div class="table-responsive">
                                <table align="center" style="font-size: 14px;">
                                    <tr>
                                        <td colspan="3" align="center">
                                            <img src="<?=base_url()?>/files/assets/images/<?=$data['foto']; ?>" alt="foto" width="200"
                                            height="250" class="rounded-circle">
                                            <hr>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" align="center">
                                            <b><?=$data['nama'];?></b>
                                            <hr>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td align="left" style="width: 20px;">Username</td>
                                        <td align="center" style="width: 10px;">:</td>
                                        <td align="left"><?=$data['username'];?></td>
                                    </tr>
                                    <tr>
                                        <td align="left" style="width: 20px;">Password</td>
                                        <td align="center" style="width: 10px;">:</td>
                                        <td align="left"><?=$data['password'];?></td>
                                    </tr>
                                    <tr>
                                        <td align="left" style="width: 20px;">Email</td>
                                        <td align="center" style="width: 10px;">:</td>
                                        <td align="left"><?=$data['email'];?></td>
                                    </tr>
                                    <tr>
                                        <td align="left" style="width: 20px;">Level</td>
                                        <td align="center" style="width: 10px;">:</td>
                                        <td align="left"><?=$level?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        }
        include_once('../footer.php');
        ?>
    </body>
</html>