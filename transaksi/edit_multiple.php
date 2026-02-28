<?php
include_once('../header.php');
include_once('../config/config.php');
require_once '../libs/log_activity.php';

// Start session jika belum
if (!isset($_SESSION)) {
    session_start();
}

// Ambil IDs dari query string
$ids = isset($_GET['ids']) ? $_GET['ids'] : '';
$id_array = !empty($ids) ? array_map('intval', explode(',', $ids)) : [];

if (empty($id_array)) {
    echo '<script>
        alert("Tidak ada transaksi yang dipilih untuk diedit");
        window.location.href = "' . base_url('transaksi/transaksi.php') . '";
    </script>';
    exit;
}

// Ambil data transaksi yang dipilih
$ids_str = implode(',', $id_array);
$query = "SELECT transaksi.*, tb_jenisbayar.jenis_bayar
          FROM transaksi
          LEFT JOIN tb_jenisbayar ON transaksi.id_bayar = tb_jenisbayar.id_bayar
          WHERE transaksi.id_transaksi IN ($ids_str)
          ORDER BY transaksi.tgl DESC, transaksi.id_transaksi DESC";
$result = $koneksi->query($query);
$transaksi_list = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $transaksi_list[] = $row;
    }
}

// Ambil data jenis pembayaran untuk dropdown
$sql_jenis = $koneksi->query("SELECT * FROM tb_jenisbayar ORDER BY jenis_bayar ASC");
$jenis_bayar_list = [];
if ($sql_jenis) {
    while ($row = $sql_jenis->fetch_assoc()) {
        $jenis_bayar_list[] = $row;
    }
}

// Handle update multiple
if (isset($_POST['update_multiple'])) {
    $updated_count = 0;
    $error_count = 0;
    $errors = [];

    foreach ($_POST['transaksi'] as $id_transaksi => $data) {
        $id_transaksi = intval($id_transaksi);
        $tgl = mysqli_real_escape_string($koneksi, $data['tgl'] ?? '');
        $idpel = mysqli_real_escape_string($koneksi, $data['idpel'] ?? '');
        $nama = mysqli_real_escape_string($koneksi, $data['nama'] ?? '');
        $id_bayar = intval($data['id_bayar'] ?? 0);
        $harga = floatval($data['harga'] ?? 0);
        $status = mysqli_real_escape_string($koneksi, $data['status'] ?? '');
        $ket = mysqli_real_escape_string($koneksi, $data['ket'] ?? '');

        if ($id_transaksi > 0 && !empty($tgl) && !empty($idpel) && !empty($nama) && $harga > 0) {
            $id_bayar_sql = $id_bayar > 0 ? $id_bayar : 'NULL';
            $update_query = "UPDATE transaksi
                            SET tgl = '$tgl',
                                idpel = '$idpel',
                                nama = '$nama',
                                id_bayar = $id_bayar_sql,
                                harga = $harga,
                                status = '$status',
                                ket = '$ket'
                            WHERE id_transaksi = $id_transaksi";

            if ($koneksi->query($update_query)) {
                $updated_count++;
                @log_activity('update', 'transaksi', 'Mengedit transaksi ID: ' . $id_transaksi);
            } else {
                $error_count++;
                $errors[] = "Gagal mengupdate transaksi ID $id_transaksi: " . $koneksi->error;
            }
        } else {
            $error_count++;
            $errors[] = "Data transaksi ID $id_transaksi tidak valid";
        }
    }

    if ($updated_count > 0) {
        $message = "Berhasil mengupdate $updated_count transaksi" . ($error_count > 0 ? " (gagal: $error_count)" : "");
        echo '<script src="' . base_url() . '/files/dist/js/sweetalert2.all.min.js"></script>';
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(function() {
                    if (typeof Swal !== "undefined") {
                        Swal.fire({
                            icon: "success",
                            title: "Berhasil!",
                            text: "' . $message . '",
                            confirmButtonColor: "#28a745",
                            confirmButtonText: "OK"
                        }).then((result) => {
                            window.location.href = "' . base_url('transaksi/transaksi.php') . '";
                        });
                    } else {
                        alert("' . $message . '");
                        window.location.href = "' . base_url('transaksi/transaksi.php') . '";
                    }
                }, 100);
            });
        </script>';
    } else {
        $message = "Gagal mengupdate transaksi. " . implode("; ", $errors);
        echo '<script src="' . base_url() . '/files/dist/js/sweetalert2.all.min.js"></script>';
        echo '<script>
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(function() {
                    if (typeof Swal !== "undefined") {
                        Swal.fire({
                            icon: "error",
                            title: "Gagal!",
                            text: "' . addslashes($message) . '",
                            confirmButtonColor: "#dc3545",
                            confirmButtonText: "OK"
                        });
                    } else {
                        alert("' . addslashes($message) . '");
                    }
                }, 100);
            });
        </script>';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Multiple Transaksi</title>
    </head>
    <body>
        <div class="page-breadcrumb">
            <div class="row">
                <div class="col-7 align-self-center">
                    <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Transaksi</h4>
                    <div class="d-flex align-items-center">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb m-0 p-0">
                                <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item"><a href="<?=base_url('transaksi/transaksi.php')?>" class="text-muted">Transaksi</a></li>
                                <li class="breadcrumb-item text-muted active" aria-current="page">Edit Multiple</li>
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
                                <i class="fa fa-edit"></i> Edit Multiple Transaksi
                                <span class="badge badge-light ml-2"><?=count($transaksi_list)?> transaksi</span>
                            </h4>
                        </div>
                        <div class="modern-card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="update_multiple" value="1">

                                <div class="table-responsive">
                                    <table class="table modern-table table-bordered">
                                        <thead>
                                            <tr>
                                                <th style="width: 5px;">No</th>
                                                <th>Tanggal</th>
                                                <th>ID Pelanggan</th>
                                                <th>Nama Pelanggan</th>
                                                <th>Jenis Bayar</th>
                                                <th>Harga</th>
                                                <th>Status</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($transaksi_list)): ?>
                                            <tr>
                                                <td colspan="8" class="text-center">
                                                    <div class="alert alert-warning">
                                                        <i class="fa fa-exclamation-triangle"></i> Tidak ada transaksi yang ditemukan
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php else: ?>
                                            <?php foreach ($transaksi_list as $index => $trans): ?>
                                            <tr>
                                                <td><?=$index + 1?></td>
                                                <td>
                                                    <input type="date" name="transaksi[<?=$trans['id_transaksi']?>][tgl]"
                                                           value="<?=date('Y-m-d', strtotime($trans['tgl']))?>"
                                                           class="form-control form-control-sm" required>
                                                </td>
                                                <td>
                                                    <input type="text" name="transaksi[<?=$trans['id_transaksi']?>][idpel]"
                                                           value="<?=htmlspecialchars($trans['idpel'])?>"
                                                           class="form-control form-control-sm" required>
                                                </td>
                                                <td>
                                                    <input type="text" name="transaksi[<?=$trans['id_transaksi']?>][nama]"
                                                           value="<?=htmlspecialchars($trans['nama'])?>"
                                                           class="form-control form-control-sm" required>
                                                </td>
                                                <td>
                                                    <select name="transaksi[<?=$trans['id_transaksi']?>][id_bayar]"
                                                            class="form-control form-control-sm">
                                                        <option value="">Pilih Jenis</option>
                                                        <?php foreach ($jenis_bayar_list as $jenis): ?>
                                                        <option value="<?=$jenis['id_bayar']?>"
                                                                <?=($trans['id_bayar'] == $jenis['id_bayar']) ? 'selected' : ''?>>
                                                            <?=htmlspecialchars($jenis['jenis_bayar'])?>
                                                        </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">Rp</span>
                                                        </div>
                                                        <input type="number" name="transaksi[<?=$trans['id_transaksi']?>][harga]"
                                                               value="<?=intval($trans['harga'])?>"
                                                               class="form-control" step="1" min="0" required>
                                                    </div>
                                                </td>
                                                <td>
                                                    <select name="transaksi[<?=$trans['id_transaksi']?>][status]"
                                                            class="form-control form-control-sm">
                                                        <option value="Lunas" <?=($trans['status'] == 'Lunas') ? 'selected' : ''?>>Lunas</option>
                                                        <option value="Belum" <?=($trans['status'] == 'Belum') ? 'selected' : ''?>>Belum Bayar</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="text" name="transaksi[<?=$trans['id_transaksi']?>][ket]"
                                                           value="<?=htmlspecialchars($trans['ket'])?>"
                                                           class="form-control form-control-sm">
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-end align-items-center">
                                            <a href="<?=base_url('transaksi/transaksi.php')?>" class="btn btn-warning btn-modern mr-2">
                                                <i class="fa fa-arrow-left"></i> Kembali
                                            </a>
                                            <button type="submit" class="btn btn-success btn-modern">
                                                <i class="fa fa-save"></i> Simpan Perubahan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
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

