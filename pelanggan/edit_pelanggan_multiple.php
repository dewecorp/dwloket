<?php
// Error reporting disabled untuk production
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// Include config dulu untuk koneksi database
include_once('../config/config.php');

// Start session jika belum
if (!isset($_SESSION)) {
    session_start();
}

// Handle update multiple - HARUS DIPROSES SEBELUM OUTPUT APAPUN
if (isset($_POST['update_multiple'])) {
    $updated_count = 0;
    $error_count = 0;
    $errors = [];

    foreach ($_POST['pelanggan'] as $id_pelanggan => $data) {
        $id_pelanggan = intval($id_pelanggan);
        $nama = mysqli_real_escape_string($koneksi, $data['nama'] ?? '');
        $no_idpel = mysqli_real_escape_string($koneksi, $data['no_idpel'] ?? '');

        if ($id_pelanggan > 0 && !empty($nama) && !empty($no_idpel)) {
            $update_query = "UPDATE pelanggan
                            SET nama = '$nama',
                                no_idpel = '$no_idpel'
                            WHERE id_pelanggan = $id_pelanggan";

            if ($koneksi->query($update_query)) {
                $updated_count++;
                // Log aktivitas
                require_once '../libs/log_activity.php';
                @log_activity('update', 'pelanggan', 'Mengedit pelanggan: ' . $nama);
            } else {
                $error_count++;
                $errors[] = "Gagal mengupdate pelanggan ID $id_pelanggan: " . $koneksi->error;
            }
        } else {
            $error_count++;
            $errors[] = "Data pelanggan ID $id_pelanggan tidak valid (nama atau ID/PEL kosong)";
        }
    }

    if ($updated_count > 0) {
        $_SESSION['update_message'] = "Berhasil mengupdate $updated_count pelanggan" . ($error_count > 0 ? " (gagal: $error_count)" : "");
        $_SESSION['update_success'] = true;
    } else {
        $_SESSION['update_message'] = "Gagal mengupdate pelanggan. " . implode("; ", $errors);
        $_SESSION['update_success'] = false;
    }

    header('Location: ' . base_url('pelanggan/pelanggan.php'));
    exit;
}

// Ambil IDs dari query string
$ids = isset($_GET['ids']) ? $_GET['ids'] : '';
$id_array = !empty($ids) ? array_map('intval', explode(',', $ids)) : [];

if (empty($id_array)) {
    $_SESSION['edit_message'] = 'Tidak ada pelanggan yang dipilih untuk diedit';
    $_SESSION['edit_success'] = false;
    header('Location: ' . base_url('pelanggan/pelanggan.php'));
    exit;
}

// Ambil data pelanggan yang dipilih
$ids_str = implode(',', $id_array);
$query = "SELECT * FROM pelanggan WHERE id_pelanggan IN ($ids_str) ORDER BY nama ASC";
$result = $koneksi->query($query);
$pelanggan_list = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $pelanggan_list[] = $row;
    }
} else {
    // Jika query gagal, redirect dengan error
    $_SESSION['edit_message'] = 'Error mengambil data pelanggan: ' . $koneksi->error;
    $_SESSION['edit_success'] = false;
    header('Location: ' . base_url('pelanggan/pelanggan.php'));
    exit;
}

// Jika tidak ada data yang ditemukan
if (empty($pelanggan_list)) {
    $_SESSION['edit_message'] = 'Tidak ada data pelanggan yang ditemukan';
    $_SESSION['edit_success'] = false;
    header('Location: ' . base_url('pelanggan/pelanggan.php'));
    exit;
}

// Setelah semua validasi dan query berhasil, baru include header
include_once('../header.php');
?>

<div class="page-breadcrumb">
    <div class="row">
        <div class="col-7 align-self-center">
            <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Edit Multiple Pelanggan</h4>
            <div class="d-flex align-items-center">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb m-0 p-0">
                        <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                        <li class="breadcrumb-item"><a href="<?=base_url('pelanggan/pelanggan.php')?>" class="text-muted">Pelanggan</a></li>
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
                        <i class="fa fa-edit"></i> Edit Multiple Pelanggan
                        <span class="badge badge-primary ml-2"><?=count($pelanggan_list)?> pelanggan</span>
                    </h4>
                </div>
                <div class="modern-card-body">
                    <form method="POST" action="">
                        <input type="hidden" name="update_multiple" value="1">

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th style="width: 30px;">No</th>
                                        <th>Nama Pelanggan</th>
                                        <th>No ID/PEL</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $no = 1; foreach ($pelanggan_list as $pelanggan): ?>
                                    <tr>
                                        <td><?=$no++?></td>
                                        <td>
                                            <input type="text" name="pelanggan[<?=$pelanggan['id_pelanggan']?>][nama]"
                                                   class="form-control"
                                                   value="<?=htmlspecialchars($pelanggan['nama'])?>" required>
                                        </td>
                                        <td>
                                            <input type="text" name="pelanggan[<?=$pelanggan['id_pelanggan']?>][no_idpel]"
                                                   class="form-control"
                                                   value="<?=htmlspecialchars($pelanggan['no_idpel'])?>" required>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-success">
                                <i class="fa fa-save"></i> Simpan Semua Perubahan
                            </button>
                            <a href="<?=base_url('pelanggan/pelanggan.php')?>" class="btn btn-secondary">
                                <i class="fa fa-times"></i> Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('../footer.php'); ?>
