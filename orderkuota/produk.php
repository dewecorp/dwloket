<?php
/**
 * Halaman untuk menampilkan daftar produk dan harga dari database
 * Dapat diakses dari orderkuota/index.php atau langsung
 */
include_once('../header.php');
include_once('../config/config.php');
require_once '../libs/produk_helper.php';

// Ambil parameter filter
$id_bayar = isset($_GET['id_bayar']) ? intval($_GET['id_bayar']) : null;
$kategori = isset($_GET['kategori']) ? $_GET['kategori'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Ambil data produk
if (!empty($search)) {
    $produk_list = searchProduk($search);
    $filter_title = "Hasil pencarian: \"$search\"";
} elseif ($id_bayar) {
    $produk_list = getProdukByIdBayar($id_bayar);
    // Ambil nama jenis bayar
    $jenis_query = $koneksi->query("SELECT jenis_bayar FROM tb_jenisbayar WHERE id_bayar = $id_bayar");
    $jenis_row = $jenis_query ? $jenis_query->fetch_assoc() : null;
    $filter_title = "Produk: " . ($jenis_row ? htmlspecialchars($jenis_row['jenis_bayar']) : 'Unknown');
} elseif ($kategori) {
    $produk_list = getProdukByKategori(null, $kategori);
    $filter_title = "Kategori: " . htmlspecialchars($kategori);
} else {
    // Ambil semua kategori untuk ditampilkan
    $all_kategori = getAllKategori();
}

// Ambil semua jenis bayar untuk filter
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
    <title>Daftar Produk OrderKuota</title>
    <style>
        .produk-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        .produk-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .produk-card.selected {
            border-color: #28a745 !important;
            background-color: #f0fff4;
        }
        .price-badge {
            font-size: 1.2rem;
            font-weight: bold;
            color: #28a745;
        }
        .kategori-badge {
            font-size: 0.85rem;
        }
    </style>
</head>
<body>
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Daftar Produk & Harga</h4>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                            <li class="breadcrumb-item"><a href="<?=base_url('orderkuota')?>" class="text-muted">OrderKuota</a></li>
                            <li class="breadcrumb-item text-muted active" aria-current="page">Produk & Harga</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <!-- Filter Section -->
        <div class="modern-card mb-4">
            <div class="modern-card-header">
                <h4><i class="fa fa-filter"></i> Filter Produk</h4>
            </div>
            <div class="modern-card-body">
                <form method="GET" action="">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Jenis Pembayaran</label>
                                <select name="id_bayar" class="form-control" onchange="this.form.submit()">
                                    <option value="">-- Semua Jenis --</option>
                                    <?php foreach ($jenis_bayar_list as $jenis): ?>
                                    <option value="<?=$jenis['id_bayar']?>" <?=($id_bayar == $jenis['id_bayar']) ? 'selected' : ''?>>
                                        <?=htmlspecialchars($jenis['jenis_bayar'])?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Kategori</label>
                                <select name="kategori" class="form-control" onchange="this.form.submit()">
                                    <option value="">-- Semua Kategori --</option>
                                    <?php
                                    $kategori_list = getAllKategori();
                                    foreach ($kategori_list as $kat):
                                    ?>
                                    <option value="<?=htmlspecialchars($kat['kategori'])?>" <?=($kategori == $kat['kategori']) ? 'selected' : ''?>>
                                        <?=htmlspecialchars($kat['kategori'])?> (<?=$kat['jumlah_produk']?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Cari Produk</label>
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="Cari kode, nama, atau kategori..." value="<?=htmlspecialchars($search)?>">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i></button>
                                        <?php if ($search || $id_bayar || $kategori): ?>
                                        <a href="?" class="btn btn-secondary"><i class="fa fa-times"></i></a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products List -->
        <?php if (isset($produk_list)): ?>
        <div class="modern-card">
            <div class="modern-card-header">
                <h4>
                    <i class="fa fa-box"></i> <?=$filter_title ?? 'Daftar Produk'?>
                    <span class="badge badge-primary ml-2"><?=count($produk_list)?> produk</span>
                </h4>
            </div>
            <div class="modern-card-body">
                <?php if (empty($produk_list)): ?>
                <div class="alert alert-info text-center">
                    <i class="fa fa-info-circle"></i> Tidak ada produk ditemukan
                </div>
                <?php else: ?>
                <div class="row">
                    <?php
                    $current_kategori = '';
                    foreach ($produk_list as $produk):
                        // Group by kategori jika tidak filter kategori spesifik
                        if (empty($kategori) && $produk['kategori'] != $current_kategori):
                            if ($current_kategori != ''):
                                echo '</div></div>'; // Close previous kategori group
                            endif;
                            $current_kategori = $produk['kategori'];
                    ?>
                    <div class="col-12 mb-4">
                        <h5 class="mb-3">
                            <span class="badge badge-secondary kategori-badge"><?=htmlspecialchars($produk['kategori'])?></span>
                        </h5>
                        <div class="row">
                    <?php endif; ?>
                            <div class="col-md-4 col-lg-3 mb-3">
                                <div class="card produk-card border" data-kode="<?=htmlspecialchars($produk['kode'])?>" data-harga="<?=$produk['harga']?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge badge-info"><?=htmlspecialchars($produk['kode'])?></span>
                                            <?php if ($produk['status'] == 0): ?>
                                            <span class="badge badge-warning">Tidak Aktif</span>
                                            <?php endif; ?>
                                        </div>
                                        <h6 class="card-title mb-2"><?=htmlspecialchars($produk['produk'])?></h6>
                                        <p class="card-text small text-muted mb-2"><?=htmlspecialchars($produk['keterangan'])?></p>
                                        <div class="price-badge">Rp <?=number_format($produk['harga'], 0, ',', '.')?></div>
                                        <?php if ($produk['jenis_bayar']): ?>
                                        <small class="text-muted d-block mt-1">
                                            <i class="fa fa-tag"></i> <?=htmlspecialchars($produk['jenis_bayar'])?>
                                        </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                    <?php endforeach; ?>
                    <?php if (!empty($current_kategori)): ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- Show Categories -->
        <div class="row">
            <?php foreach ($all_kategori as $kat): ?>
            <div class="col-md-4 col-lg-3 mb-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h5><?=htmlspecialchars($kat['kategori'])?></h5>
                        <p class="text-muted"><?=$kat['jumlah_produk']?> produk</p>
                        <a href="?kategori=<?=urlencode($kat['kategori'])?>" class="btn btn-primary btn-sm">
                            <i class="fa fa-eye"></i> Lihat Produk
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
    // Handle produk selection untuk copy ke clipboard atau action lain
    document.querySelectorAll('.produk-card').forEach(function(card) {
        card.addEventListener('click', function() {
            const kode = this.getAttribute('data-kode');
            const harga = this.getAttribute('data-harga');

            // Copy kode ke clipboard
            if (navigator.clipboard) {
                navigator.clipboard.writeText(kode).then(function() {
                    // Remove previous selected
                    document.querySelectorAll('.produk-card').forEach(c => c.classList.remove('selected'));
                    // Add selected to clicked card
                    card.classList.add('selected');

                    // Show notification
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Kode Produk Disalin!',
                            text: 'Kode: ' + kode,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
            }
        });
    });
    </script>

    <?php include_once('../footer.php'); ?>
</body>
</html>


