<?php
include_once('../header.php');
include_once('../config/config.php');

// Start session jika belum
if (!isset($_SESSION)) {
    session_start();
}

// Ambil IDs dari query string
$ids = isset($_GET['ids']) ? $_GET['ids'] : '';
$id_array = !empty($ids) ? array_map('intval', explode(',', $ids)) : [];

if (empty($id_array)) {
    $_SESSION['edit_message'] = 'Tidak ada produk yang dipilih untuk diedit';
    $_SESSION['edit_success'] = false;
    header('Location: ' . base_url('jenisbayar/jenis_bayar.php'));
    exit;
}

// Ambil data produk yang dipilih
$ids_str = implode(',', $id_array);
$query = "SELECT * FROM tb_produk_orderkuota WHERE id_produk IN ($ids_str) ORDER BY kategori, kode";
$result = $koneksi->query($query);
$produk_list = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $produk_list[] = $row;
    }
}

// Handle update multiple
if (isset($_POST['update_multiple'])) {
    $updated_count = 0;
    $error_count = 0;

    foreach ($_POST['produk'] as $id_produk => $data) {
        $id_produk = intval($id_produk);
        $kode = mysqli_real_escape_string($koneksi, $data['kode'] ?? '');
        $produk = mysqli_real_escape_string($koneksi, $data['produk'] ?? '');
        $kategori = mysqli_real_escape_string($koneksi, $data['kategori'] ?? '');
        $harga = floatval($data['harga'] ?? 0);
        $status = intval($data['status'] ?? 1);

        if ($id_produk > 0 && !empty($produk) && !empty($kategori) && $harga > 0) {
            $update_query = "UPDATE tb_produk_orderkuota
                            SET produk = '$produk',
                                kategori = '$kategori',
                                harga = $harga,
                                status = $status,
                                updated_at = CURRENT_TIMESTAMP
                            WHERE id_produk = $id_produk";

            if ($koneksi->query($update_query)) {
                $updated_count++;
            } else {
                $error_count++;
            }
        } else {
            $error_count++;
        }
    }

    if ($updated_count > 0) {
        $_SESSION['update_message'] = "Berhasil mengupdate $updated_count produk" . ($error_count > 0 ? " (gagal: $error_count)" : "");
        $_SESSION['update_success'] = true;
    } else {
        $_SESSION['update_message'] = "Gagal mengupdate produk";
        $_SESSION['update_success'] = false;
    }

    header('Location: ' . base_url('jenisbayar/jenis_bayar.php'));
    exit;
}
?>

<div class="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title">
                            <i class="fa fa-edit"></i> Edit Multiple Produk
                            <span class="badge badge-primary ml-2"><?=count($produk_list)?> produk</span>
                        </h4>

                        <form method="POST" action="">
                            <input type="hidden" name="update_multiple" value="1">

                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Kode</th>
                                            <th>Produk</th>
                                            <th>Kategori</th>
                                            <th>Harga</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($produk_list as $produk): ?>
                                        <tr>
                                            <td>
                                                <input type="text" name="produk[<?=$produk['id_produk']?>][kode]"
                                                       class="form-control form-control-sm"
                                                       value="<?=htmlspecialchars($produk['kode'])?>" readonly>
                                            </td>
                                            <td>
                                                <input type="text" name="produk[<?=$produk['id_produk']?>][produk]"
                                                       class="form-control form-control-sm"
                                                       value="<?=htmlspecialchars($produk['produk'])?>" required>
                                            </td>
                                            <td>
                                                <input type="text" name="produk[<?=$produk['id_produk']?>][kategori]"
                                                       class="form-control form-control-sm"
                                                       value="<?=htmlspecialchars($produk['kategori'])?>" required>
                                            </td>
                                            <td>
                                                <input type="number" name="produk[<?=$produk['id_produk']?>][harga]"
                                                       class="form-control form-control-sm"
                                                       value="<?=intval($produk['harga'])?>" min="0" step="1" required>
                                            </td>
                                            <td>
                                                <select name="produk[<?=$produk['id_produk']?>][status]" class="form-control form-control-sm">
                                                    <option value="1" <?=$produk['status'] == 1 ? 'selected' : ''?>>Aktif</option>
                                                    <option value="0" <?=$produk['status'] == 0 ? 'selected' : ''?>>Tidak Aktif</option>
                                                </select>
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
                                <a href="<?=base_url('jenisbayar/jenis_bayar.php')?>" class="btn btn-secondary">
                                    <i class="fa fa-times"></i> Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once('../footer.php'); ?>

