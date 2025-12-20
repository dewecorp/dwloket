<?php
/**
 * Halaman untuk menambah produk langsung dari jenis bayar
 * Lebih praktis karena tidak perlu import file
 */

// Handle form tambah produk SEBELUM include header untuk menghindari blank page
if (isset($_POST['tambah_produk'])) {
    // Bersihkan semua output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();

    include_once('../config/config.php');

    $kode = mysqli_real_escape_string($koneksi, $_POST['kode'] ?? '');
    $keterangan = mysqli_real_escape_string($koneksi, $_POST['keterangan'] ?? '');
    $produk = mysqli_real_escape_string($koneksi, $_POST['produk'] ?? '');
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori'] ?? '');
    $id_bayar = intval($_POST['id_bayar'] ?? 0);
    $harga = floatval($_POST['harga'] ?? 0);
    $status = intval($_POST['status'] ?? 1);

    $success = false;
    $message = '';

    if (empty($kode)) {
        $message = "Kode produk harus diisi";
        $success = false;
    } elseif (empty($produk)) {
        $message = "Nama produk harus diisi";
        $success = false;
    } elseif (empty($kategori)) {
        $message = "Kategori harus diisi";
        $success = false;
    } elseif ($harga <= 0) {
        $message = "Harga harus lebih dari 0";
        $success = false;
    } else {
        // Cek apakah kode sudah ada
        $check_query = "SELECT id_produk FROM tb_produk_orderkuota WHERE kode = '$kode'";
        $check_result = $koneksi->query($check_query);

        if ($check_result && $check_result->num_rows > 0) {
            $message = "Kode produk sudah ada: $kode";
            $success = false;
        } else {
            $id_bayar_sql = $id_bayar > 0 ? $id_bayar : 'NULL';
            $insert_query = "INSERT INTO tb_produk_orderkuota
                            (kode, keterangan, produk, kategori, harga, status, id_bayar)
                            VALUES ('$kode', '$keterangan', '$produk', '$kategori', $harga, $status, $id_bayar_sql)";

            if ($koneksi->query($insert_query)) {
                $message = "Produk berhasil ditambahkan";
                $success = true;
            } else {
                $message = "Error: " . $koneksi->error;
                $success = false;
            }
        }
    }

    ob_end_clean();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Tambah Produk</title>
        <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
    </head>
    <body>
    <script type="text/javascript">
    Swal.fire({
        icon: '<?=$success ? 'success' : 'error'?>',
        title: '<?=$success ? 'Berhasil!' : 'Gagal!'?>',
        text: '<?=addslashes($message)?>',
        confirmButtonColor: '<?=$success ? '#28a745' : '#dc3545'?>',
        <?php if ($success): ?>
        timer: 2000,
        timerProgressBar: true,
        showConfirmButton: false
        <?php else: ?>
        confirmButtonText: 'OK'
        <?php endif; ?>
    }).then(() => {
        window.location.href = "<?=base_url('jenisbayar/jenis_bayar.php')?>";
    });
    <?php if ($success): ?>
    setTimeout(function() {
        window.location.href = "<?=base_url('jenisbayar/jenis_bayar.php')?>";
    }, 2500);
    <?php endif; ?>
    </script>
    </body>
    </html>
    <?php
    exit;
}

include_once('../header.php');
include_once('../config/config.php');

// Cek apakah tabel produk sudah ada
$table_exists = false;
$check_table = $koneksi->query("SHOW TABLES LIKE 'tb_produk_orderkuota'");
if ($check_table && $check_table->num_rows > 0) {
    $table_exists = true;
}

// Buat tabel jika belum ada
if (!$table_exists) {
    $create_table_query = "
    CREATE TABLE IF NOT EXISTS `tb_produk_orderkuota` (
      `id_produk` int(11) NOT NULL AUTO_INCREMENT,
      `kode` varchar(50) NOT NULL,
      `keterangan` text NOT NULL,
      `produk` varchar(255) NOT NULL,
      `kategori` varchar(100) NOT NULL,
      `harga` decimal(15,2) NOT NULL,
      `status` tinyint(1) NOT NULL DEFAULT '1',
      `id_bayar` int(11) DEFAULT NULL,
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id_produk`),
      UNIQUE KEY `unique_kode` (`kode`),
      KEY `idx_kategori` (`kategori`),
      KEY `idx_id_bayar` (`id_bayar`),
      KEY `idx_status` (`status`),
      KEY `idx_harga` (`harga`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    $koneksi->query($create_table_query);
    $table_exists = true;
}

// Handle form tambah produk sudah dipindahkan ke atas (sebelum include header)

// Ambil semua jenis bayar untuk dropdown
$jenis_bayar_query = $koneksi->query("SELECT * FROM tb_jenisbayar ORDER BY jenis_bayar ASC");
$jenis_bayar_list = [];
if ($jenis_bayar_query) {
    while ($row = $jenis_bayar_query->fetch_assoc()) {
        $jenis_bayar_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Produk</title>
</head>
<body>
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Tambah Produk</h4>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                            <li class="breadcrumb-item"><a href="<?=base_url('jenisbayar/jenis_bayar.php')?>" class="text-muted">Produk & Harga</a></li>
                            <li class="breadcrumb-item text-muted active" aria-current="page">Tambah Produk</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-8">
                <div class="modern-card">
                    <div class="modern-card-header">
                        <h4><i class="fa fa-plus-circle"></i> Tambah Produk Baru</h4>
                    </div>
                    <div class="modern-card-body">
                        <?php if (isset($success_msg)): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fa fa-check-circle"></i> <?=htmlspecialchars($success_msg)?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                        <?php endif; ?>

                        <?php if (isset($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fa fa-exclamation-circle"></i> <?=htmlspecialchars($error_msg)?>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                        <?php endif; ?>

                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Kode Produk <span class="text-danger">*</span></label>
                                        <input type="text" name="kode" class="form-control"
                                               value="<?=htmlspecialchars($_POST['kode'] ?? '')?>"
                                               placeholder="Contoh: SMDC150" required>
                                        <small class="form-text text-muted">Kode unik untuk produk</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Jenis Pembayaran</label>
                                        <select name="id_bayar" class="form-control" id="id_bayar" onchange="updateKategori()">
                                            <option value="">-- Pilih Jenis Pembayaran --</option>
                                            <?php foreach ($jenis_bayar_list as $jenis): ?>
                                            <option value="<?=$jenis['id_bayar']?>"
                                                    data-jenis="<?=htmlspecialchars($jenis['jenis_bayar'])?>">
                                                <?=htmlspecialchars($jenis['jenis_bayar'])?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Kategori <span class="text-danger">*</span></label>
                                <input type="text" name="kategori" id="kategori" class="form-control"
                                       value="<?=htmlspecialchars($_POST['kategori'] ?? '')?>"
                                       placeholder="Contoh: KUOTA SMARTFREN, TOKEN PLN" required>
                                <small class="form-text text-muted">Akan terisi otomatis jika memilih jenis pembayaran</small>
                            </div>

                            <div class="form-group">
                                <label>Nama Produk <span class="text-danger">*</span></label>
                                <input type="text" name="produk" class="form-control"
                                       value="<?=htmlspecialchars($_POST['produk'] ?? '')?>"
                                       placeholder="Contoh: Smart 30GB All + 60GB" required>
                            </div>

                            <div class="form-group">
                                <label>Keterangan</label>
                                <textarea name="keterangan" class="form-control" rows="3"
                                          placeholder="Deskripsi detail produk"><?=htmlspecialchars($_POST['keterangan'] ?? '')?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Harga <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text">Rp</span>
                                            </div>
                                            <input type="number" name="harga" class="form-control"
                                                   value="<?=isset($_POST['harga']) ? intval($_POST['harga']) : ''?>"
                                                   placeholder="0" step="1" min="0" required>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Status</label>
                                        <select name="status" class="form-control">
                                            <option value="1" <?=(($_POST['status'] ?? 1) == 1) ? 'selected' : ''?>>Aktif</option>
                                            <option value="0" <?=(($_POST['status'] ?? 0) == 0 && isset($_POST['status'])) ? 'selected' : ''?>>Tidak Aktif</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mt-4">
                                <button type="submit" name="tambah_produk" class="btn btn-success">
                                    <i class="fa fa-save"></i> Simpan Produk
                                </button>
                                <a href="<?=base_url('jenisbayar/jenis_bayar.php')?>" class="btn btn-secondary">
                                    <i class="fa fa-times"></i> Batal
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="modern-card">
                    <div class="modern-card-header">
                        <h4><i class="fa fa-info-circle"></i> Informasi</h4>
                    </div>
                    <div class="modern-card-body">
                        <p><strong>Cara Menambah Produk:</strong></p>
                        <ol>
                            <li>Pilih jenis pembayaran (opsional)</li>
                            <li>Kategori akan terisi otomatis</li>
                            <li>Isi kode produk (unik)</li>
                            <li>Isi nama produk</li>
                            <li>Isi keterangan (opsional)</li>
                            <li>Isi harga</li>
                            <li>Klik Simpan</li>
                        </ol>

                        <hr>

                        <p><strong>Tips:</strong></p>
                        <ul>
                            <li>Kode produk harus unik</li>
                            <li>Kategori bisa diubah manual jika perlu</li>
                            <li>Harga dalam rupiah (tanpa titik/koma)</li>
                        </ul>
                    </div>
                </div>

                <!-- Quick Add dari Jenis Bayar -->
                <div class="modern-card mt-3">
                    <div class="modern-card-header">
                        <h4><i class="fa fa-bolt"></i> Quick Add</h4>
                    </div>
                    <div class="modern-card-body">
                        <p>Klik jenis pembayaran di bawah untuk menambah produk dengan kategori otomatis:</p>
                        <div class="list-group">
                            <?php foreach ($jenis_bayar_list as $jenis):
                                // Generate kategori dari jenis bayar
                                $kategori_auto = strtoupper(str_replace(' ', ' ', $jenis['jenis_bayar']));
                                if (strpos($kategori_auto, 'DATA INTERNET') !== false) {
                                    $kategori_auto = 'KUOTA ' . str_replace('DATA INTERNET ', '', $kategori_auto);
                                } elseif (strpos($kategori_auto, 'PULSA') !== false) {
                                    $kategori_auto = 'PULSA ' . str_replace('PULSA ', '', $kategori_auto);
                                }
                            ?>
                            <a href="#" class="list-group-item list-group-item-action"
                               onclick="quickFill('<?=$jenis['id_bayar']?>', '<?=htmlspecialchars($kategori_auto)?>'); return false;">
                                <strong><?=htmlspecialchars($jenis['jenis_bayar'])?></strong>
                                <small class="text-muted d-block">Kategori: <?=htmlspecialchars($kategori_auto)?></small>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    function updateKategori() {
        const select = document.getElementById('id_bayar');
        const kategoriInput = document.getElementById('kategori');
        const selectedOption = select.options[select.selectedIndex];

        if (selectedOption.value) {
            let kategori = selectedOption.getAttribute('data-jenis').toUpperCase();

            // Generate kategori berdasarkan jenis bayar
            if (kategori.indexOf('DATA INTERNET') !== -1) {
                kategori = 'KUOTA ' + kategori.replace('DATA INTERNET ', '');
            } else if (kategori.indexOf('PULSA') !== -1) {
                kategori = 'PULSA ' + kategori.replace('PULSA ', '');
            } else if (kategori.indexOf('TOKEN') !== -1 || kategori.indexOf('PLN') !== -1) {
                kategori = 'TOKEN PLN';
            } else if (kategori.indexOf('BPJS') !== -1) {
                kategori = kategori;
            } else {
                kategori = kategori;
            }

            kategoriInput.value = kategori;
        }
    }

    function quickFill(id_bayar, kategori) {
        document.getElementById('id_bayar').value = id_bayar;
        document.getElementById('kategori').value = kategori;
        document.getElementById('id_bayar').dispatchEvent(new Event('change'));

        // Scroll ke form
        document.querySelector('form').scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.querySelector('input[name="kode"]').focus();
    }
    </script>

    <?php include_once('../footer.php'); ?>
</body>
</html>


