<?php
include_once('../header.php');
include_once('../config/config.php');
require_once '../libs/produk_helper.php';

// Ambil semua kategori produk
$all_kategori = getAllKategori();

// Fungsi untuk menentukan icon dan warna berdasarkan jenis pembayaran
// Fungsi ini otomatis akan handle jenis pembayaran baru yang ditambahkan
function getJenisBayarStyle($jenis_bayar) {
    $jenis_bayar_lower = strtolower(trim($jenis_bayar));

    // Mapping icon dan warna berdasarkan keyword
    $mapping = [
        // PLN / Listrik
        'token pln' => ['icon' => 'fa-bolt', 'color' => 'warning'],
        'pln' => ['icon' => 'fa-bolt', 'color' => 'warning'],
        'listrik' => ['icon' => 'fa-bolt', 'color' => 'warning'],

        // Pulsa
        'pulsa telkomsel' => ['icon' => 'fa-phone', 'color' => 'primary'],
        'pulsa xl' => ['icon' => 'fa-phone', 'color' => 'info'],
        'pulsa axis' => ['icon' => 'fa-phone', 'color' => 'danger'],
        'pulsa indosat' => ['icon' => 'fa-phone', 'color' => 'warning'],
        'pulsa tri' => ['icon' => 'fa-phone', 'color' => 'success'],
        'pulsa smartfren' => ['icon' => 'fa-phone', 'color' => 'secondary'],
        'pulsa' => ['icon' => 'fa-phone', 'color' => 'primary'],

        // Data Internet
        'data internet' => ['icon' => 'fa-wifi', 'color' => 'info'],
        'paket data' => ['icon' => 'fa-wifi', 'color' => 'info'],
        'data' => ['icon' => 'fa-wifi', 'color' => 'info'],
        'internet' => ['icon' => 'fa-wifi', 'color' => 'info'],

        // BPJS
        'bpjs kesehatan' => ['icon' => 'fa-heart', 'color' => 'danger'],
        'bpjs ketenagakerjaan' => ['icon' => 'fa-briefcase', 'color' => 'danger'],
        'bpjs' => ['icon' => 'fa-heart', 'color' => 'danger'],

        // PDAM / Air
        'pdam' => ['icon' => 'fa-tint', 'color' => 'info'],
        'air' => ['icon' => 'fa-tint', 'color' => 'info'],

        // Internet Rumah
        'indihome' => ['icon' => 'fa-home', 'color' => 'primary'],
        'wifi id' => ['icon' => 'fa-wifi', 'color' => 'info'],

        // E-Wallet / Payment
        'shopee pay' => ['icon' => 'fa-shopping-bag', 'color' => 'warning'],
        'shopee' => ['icon' => 'fa-shopping-bag', 'color' => 'warning'],
        'grab ovo' => ['icon' => 'fa-motorcycle', 'color' => 'success'],
        'grab' => ['icon' => 'fa-motorcycle', 'color' => 'success'],
        'ovo' => ['icon' => 'fa-motorcycle', 'color' => 'success'],
        'e-mandiri' => ['icon' => 'fa-university', 'color' => 'primary'],
        'mandiri' => ['icon' => 'fa-university', 'color' => 'primary'],
        'brizzi' => ['icon' => 'fa-credit-card', 'color' => 'info'],
        'e-toll' => ['icon' => 'fa-road', 'color' => 'warning'],
        'toll' => ['icon' => 'fa-road', 'color' => 'warning'],
        'transfer uang' => ['icon' => 'fa-exchange-alt', 'color' => 'success'],
        'transfer' => ['icon' => 'fa-exchange-alt', 'color' => 'success'],
        'e-money' => ['icon' => 'fa-wallet', 'color' => 'primary'],
        'wallet' => ['icon' => 'fa-wallet', 'color' => 'primary'],
        'voucher game' => ['icon' => 'fa-gamepad', 'color' => 'danger'],
        'voucher' => ['icon' => 'fa-gamepad', 'color' => 'danger'],
        'game' => ['icon' => 'fa-gamepad', 'color' => 'danger'],
    ];

    // Cek exact match terlebih dahulu
    if (isset($mapping[$jenis_bayar_lower])) {
        return $mapping[$jenis_bayar_lower];
    }

    // Cek partial match (lebih fleksibel untuk jenis baru)
    foreach ($mapping as $key => $value) {
        // Cek jika keyword ada di nama jenis bayar atau sebaliknya
        if (strpos($jenis_bayar_lower, $key) !== false || strpos($key, $jenis_bayar_lower) !== false) {
            return $value;
        }
    }

    // Default untuk jenis pembayaran baru yang belum ada di mapping
    // Otomatis akan muncul dengan icon dan warna default
    return ['icon' => 'fa-money-bill-wave', 'color' => 'secondary'];
}

$id = @$_GET['id'];
if (empty($id)) {
    header('Location: ' . base_url('transaksi/transaksi.php'));
    exit;
}

$sql = $koneksi->query("SELECT * FROM transaksi WHERE id_transaksi='$id'");
if (!$sql || $sql->num_rows == 0) {
    header('Location: ' . base_url('transaksi/transaksi.php'));
    exit;
}

$data = $sql->fetch_assoc();
$status = $data['status'] ?? '';
$tgl = $data['tgl'] ?? '';
$selected_id_bayar = $data['id_bayar'] ?? '';
$selected_produk_id_from_db = isset($data['selected_produk_id']) ? intval($data['selected_produk_id']) : 0;
$harga_transaksi = isset($data['harga']) ? intval($data['harga']) : 0;

require_once '../libs/produk_helper.php';

// Ambil informasi produk dengan prioritas:
// 1. selected_produk_id (jika ada)
// 2. Match berdasarkan id_bayar + harga yang sama
// 3. Match berdasarkan id_bayar + harga terdekat
// 4. Produk pertama dengan id_bayar yang sama
$produk_info = null;

// Prioritas 1: Gunakan selected_produk_id jika ada
if ($selected_produk_id_from_db > 0) {
    $produk_info = getProdukById($selected_produk_id_from_db);
}

// Prioritas 2: Jika tidak ada selected_produk_id, cari berdasarkan id_bayar + harga yang sama
if (!$produk_info && $selected_id_bayar && $harga_transaksi > 0) {
    $produk_list_by_bayar = getProdukByIdBayar($selected_id_bayar, true);
    if (!empty($produk_list_by_bayar)) {
        // Cari produk dengan harga yang sama persis
        foreach ($produk_list_by_bayar as $produk) {
            if (intval($produk['harga']) == $harga_transaksi) {
                $produk_info = $produk;
                break;
            }
        }

        // Jika tidak ada yang sama persis, cari yang paling dekat
        if (!$produk_info) {
            $closest_produk = null;
            $min_diff = PHP_INT_MAX;
            foreach ($produk_list_by_bayar as $produk) {
                $diff = abs(intval($produk['harga']) - $harga_transaksi);
                if ($diff < $min_diff) {
                    $min_diff = $diff;
                    $closest_produk = $produk;
                }
            }
            if ($closest_produk) {
                $produk_info = $closest_produk;
            }
        }
    }
}

// Prioritas 3: Jika masih tidak ada, coba dari keterangan (backward compatibility)
if (!$produk_info && !empty($data['ket'])) {
    preg_match('/\b([A-Z0-9]{3,20})\b/', $data['ket'], $matches);
    if (!empty($matches[1])) {
        $produk_info = getProdukByKode($matches[1]);
    }
}

// Prioritas 4: Ambil produk pertama dengan id_bayar yang sama (fallback terakhir)
if (!$produk_info && $selected_id_bayar) {
    $produk_list_by_bayar = getProdukByIdBayar($selected_id_bayar, true);
    if (!empty($produk_list_by_bayar)) {
        $produk_info = $produk_list_by_bayar[0];
    }
}

// Ambil data jenis pembayaran secara dinamis dari database
// Setiap kali halaman dimuat, akan mengambil data terbaru dari database
// Jadi jika ada jenis pembayaran baru yang ditambahkan, otomatis akan muncul di grid
$sql_jenis = $koneksi->query("SELECT * FROM tb_jenisbayar ORDER BY jenis_bayar ASC");
$jenis_bayar_list = [];
if ($sql_jenis) {
    while ($row = $sql_jenis->fetch_assoc()) {
        $jenis_bayar_list[] = $row;
    }
}
?>
<style>
        .jenis-bayar-card {
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            z-index: 100;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        .jenis-bayar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .jenis-bayar-card.selected {
            border-color: #28a745 !important;
            background-color: #f0fff4;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        .jenis-bayar-card.selected .card-body {
            background-color: #f0fff4;
        }
        .jenis-bayar-card .card-body {
            text-align: center;
            padding: 1.5rem 1rem;
        }
        .kategori-card {
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            z-index: 100;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
            border-radius: 12px;
            overflow: hidden;
        }
        .kategori-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,123,255,0.15);
            border-color: #007bff !important;
        }
        .kategori-card.selected {
            border-color: #28a745 !important;
            background: linear-gradient(135deg, #f0fff4 0%, #e8f5e9 100%);
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
        }
        .kategori-card.selected .card-body {
            background: transparent;
        }
        .kategori-card .card-body {
            text-align: center;
            padding: 1.5rem;
        }
        .kategori-icon-wrapper {
            position: relative;
            display: inline-block;
        }
        .kategori-icon-wrapper i {
            filter: drop-shadow(0 2px 4px rgba(0,123,255,0.2));
            transition: all 0.3s ease;
        }
        .kategori-card:hover .kategori-icon-wrapper i {
            transform: scale(1.1) rotate(5deg);
            filter: drop-shadow(0 4px 8px rgba(0,123,255,0.3));
        }
        .kategori-card.selected .kategori-icon-wrapper i {
            color: #28a745 !important;
            filter: drop-shadow(0 2px 4px rgba(40,167,69,0.3));
        }
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
            display: block;
        }
        .form-label i {
            margin-right: 0.5rem;
            width: 20px;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #ced4da;
            transition: all 0.3s;
            padding: 0.6rem 0.75rem;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
            outline: 0;
        }
        .input-group-text {
            border-radius: 0;
            background-color: #f8f9fa;
            border-color: #ced4da;
            color: #495057;
            font-weight: 500;
        }
        .input-group-prepend .input-group-text {
            border-radius: 8px 0 0 8px;
        }
        .input-group-append .btn,
        .input-group-append .input-group-text {
            border-radius: 0 8px 8px 0;
            border-left: 0;
        }
        .input-group-append .btn {
            border-left: 1px solid #ced4da;
        }
        </style>
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
                                <li class="breadcrumb-item text-muted active" aria-current="page">Edit Transaksi</li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="modern-card" style="margin-bottom: 10px;">
                        <div class="modern-card-header" style="padding: 10px 15px;">
                            <h4 style="margin: 0; font-size: 1.1rem;">
                                <i class="fa fa-edit"></i> Edit Transaksi
                            </h4>
                        </div>
                        <div class="modern-card-body" style="padding: 12px 15px;">
                            <form action="proses.php" method="POST" id="formTransaksi">
                                <input type="hidden" name="id" value="<?=$data['id_transaksi']?>">
                                <input type="hidden" name="jenis" id="jenis" value="<?=$selected_id_bayar?>" required>
                                <input type="hidden" name="selected_produk_id" id="selected_produk_id" value="<?=$produk_info ? ($produk_info['id_produk'] ?? '') : ''?>">
                                <input type="hidden" name="selected_kategori" id="selected_kategori" value="<?=htmlspecialchars($produk_info ? ($produk_info['kategori'] ?? '') : '')?>">

                                <!-- Grid Kategori Produk -->
                                <div class="modern-card mb-3">
                                    <div class="modern-card-header" style="padding: 10px 15px;">
                                        <h4 style="margin: 0; font-size: 1rem;">
                                            <i class="fa fa-tags"></i> Pilih Kategori Produk (Opsional)
                                        </h4>
                                    </div>
                                    <div class="modern-card-body" style="padding: 12px 15px;">
                                        <div class="row" id="kategori-grid">
                                            <?php if (empty($all_kategori)): ?>
                                            <div class="col-12">
                                                <div class="alert alert-warning text-center">
                                                    <i class="fa fa-exclamation-triangle"></i> Belum ada kategori produk. Silakan import produk terlebih dahulu.
                                                </div>
                                            </div>
                                            <?php else: ?>
                                            <?php foreach ($all_kategori as $kategori): ?>
                                            <div class="col-md-4 col-sm-6 mb-3">
                                                <div class="card kategori-card border-primary <?=($produk_info && isset($produk_info['kategori']) && $produk_info['kategori'] == $kategori['kategori']) ? 'selected' : ''?>"
                                                     data-kategori="<?=htmlspecialchars($kategori['kategori'])?>"
                                                     style="cursor: pointer; user-select: none; position: relative; z-index: 100;"
                                                     role="button"
                                                     tabindex="0">
                                                    <div class="card-body text-center">
                                                        <div class="kategori-icon-wrapper mb-3">
                                                            <i class="fa fa-box fa-3x text-primary"></i>
                                                        </div>
                                                        <h6 class="mb-0"><?=htmlspecialchars($kategori['kategori'])?></h6>
                                                        <small class="text-muted"><?=$kategori['jumlah_produk']?> produk</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Form Pembayaran -->
                                <div class="modern-card" style="margin-top: 10px;">
                                    <div class="modern-card-header" style="padding: 10px 15px;">
                                        <h4 style="margin: 0; font-size: 1.1rem;">
                                            <i class="fa fa-edit"></i> Form Pembayaran
                                        </h4>
                                    </div>
                                    <div class="modern-card-body" style="padding: 12px 15px;">
                                        <div class="row">
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="tgl" class="form-label">
                                                        <i class="fa fa-calendar-alt text-primary"></i> Tanggal Transaksi
                                                    </label>
                                                    <input type="date" name="tgl" value="<?=date('Y-m-d', strtotime($data['tgl']));?>" class="form-control" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="idpel" class="form-label">
                                                        <i class="fa fa-user text-info"></i> ID Pelanggan
                                                    </label>
                                                    <div class="input-group">
                                                        <input type="hidden" name="id_pelanggan" id="id_pelanggan">
                                                        <input type="text" name="idpel" id="idpel" placeholder="ID Pelanggan" value="<?=htmlspecialchars($data['idpel']);?>" class="form-control" readonly>
                                                        <div class="input-group-append">
                                                            <button type="button" class="btn btn-info" data-target="#modalItem" data-toggle="modal" title="Pilih Pelanggan">
                                                                <i class="fa fa-search"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="nama" class="form-label">
                                                        <i class="fa fa-user-circle text-success"></i> Nama Pelanggan
                                                    </label>
                                                    <input type="text" name="nama" id="nama" placeholder="Nama Pelanggan" class="form-control" value="<?=htmlspecialchars($data['nama']);?>" readonly>
                                                </div>
                                                <div class="form-group">
                                                    <label for="ket" class="form-label">
                                                        <i class="fa fa-comment text-warning"></i> Keterangan
                                                    </label>
                                                    <input type="text" name="ket" class="form-control" placeholder="Isi Keterangan Transaksi" value="<?=htmlspecialchars($data['ket']);?>">
                                                </div>
                                            </div>
                                            <div class="col-lg-6">
                                                <div class="form-group">
                                                    <label for="produk" class="form-label">
                                                        <i class="fa fa-box text-info"></i> Produk
                                                    </label>
                                                    <input type="text" name="produk" id="produk" class="form-control" value="<?=htmlspecialchars($produk_info ? ($produk_info['produk'] ?? $produk_info['keterangan'] ?? $produk_info['kode'] ?? '') : '')?>" placeholder="Produk akan terisi otomatis saat memilih produk" readonly>
                                                    <small class="form-text text-muted">Produk dipilih dari daftar kategori di atas</small>
                                                </div>
                                                <div class="form-group">
                                                    <label for="harga" class="form-label">
                                                        <i class="fa fa-money-bill-wave text-success"></i> Harga <span class="text-danger">*</span>
                                                    </label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">Rp</span>
                                                        </div>
                                                        <input type="number" name="harga" id="harga" class="form-control" placeholder="0" value="<?=intval($data['harga']);?>" step="1" min="0" required>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label for="status" class="form-label">
                                                        <i class="fa fa-info-circle text-primary"></i> Status
                                                    </label>
                                                    <select class="form-control" name="status" id="status">
                                                        <option value="">Pilih Status</option>
                                                        <option value="Lunas" <?=($status == 'Lunas') ? 'selected' : ''?>>Lunas</option>
                                                        <option value="Belum" <?=($status == 'Belum') ? 'selected' : ''?>>Belum Bayar</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tombol Aksi -->
                                <div class="row mt-4">
                                    <div class="col-12">
                                        <div class="d-flex justify-content-end align-items-center">
                                            <a href="<?=base_url('transaksi/transaksi.php')?>" class="btn btn-warning btn-modern mr-2" style="cursor: pointer !important;">
                                                <i class="fa fa-arrow-left"></i> Kembali
                                            </a>
                                            <button type="submit" name="edit" class="btn btn-success btn-modern" style="cursor: pointer !important;">
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

        <script>
        let selectedKategori = '<?=htmlspecialchars($produk_info ? ($produk_info['kategori'] ?? '') : '', ENT_QUOTES)?>';
        let selectedProduk = <?=json_encode($produk_info ?: null)?>;

        // Handle selection kategori
        document.addEventListener('DOMContentLoaded', function() {
            const kategoriCards = document.querySelectorAll('.kategori-card');

            kategoriCards.forEach(function(card) {
                card.addEventListener('click', function() {
                    // Remove selected class from all cards
                    kategoriCards.forEach(function(c) {
                        c.classList.remove('selected');
                    });

                    // Add selected class to clicked card
                    this.classList.add('selected');

                    // Get kategori
                    selectedKategori = this.getAttribute('data-kategori');
                    document.getElementById('selected_kategori').value = selectedKategori;

                    // Load produk untuk kategori yang dipilih
                    loadProdukByKategori(selectedKategori);

                    // Buka modal produk
                    $('#modalProduk').modal('show');
                });
            });
        });

        // Fungsi untuk load produk berdasarkan kategori
        function loadProdukByKategori(kategori) {
            const produkListDiv = document.getElementById('produk-list-modal');
            const modalKategoriTitle = document.getElementById('modalKategoriTitle');

            // Set title modal
            if (modalKategoriTitle) {
                modalKategoriTitle.textContent = '- ' + kategori;
            }

            // Show loading
            produkListDiv.innerHTML = '<div class="col-12"><div class="text-center p-3">Memuat produk...</div></div>';

            // Load produk via AJAX
            fetch('get_produk.php?kategori=' + encodeURIComponent(kategori))
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.produk && data.produk.length > 0) {
                        renderProdukList(data.produk, produkListDiv);
                    } else {
                        produkListDiv.innerHTML = '<div class="col-12"><div class="alert alert-info text-center">Tidak ada produk untuk kategori ini</div></div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading produk:', error);
                    produkListDiv.innerHTML = '<div class="col-12"><div class="alert alert-danger text-center">Gagal memuat produk</div></div>';
                });
        }

        // Fungsi untuk render daftar produk
        function renderProdukList(produkList, container) {
            container.innerHTML = '';
            produkList.forEach(function(produk) {
                const produkCard = document.createElement('div');
                produkCard.className = 'col-md-4 col-sm-6 mb-3';
                const produkName = produk.produk || produk.keterangan || produk.kode;
                produkCard.innerHTML = `
                    <div class="card produk-item-card border-info"
                         onclick="selectProduk(${produk.id_produk || 'null'}, '${produk.kode}', ${produk.harga}, ${produk.id_bayar || 'null'}, this)"
                         style="cursor: pointer; transition: all 0.3s;">
                        <div class="card-body">
                            <h6 class="card-title">${produkName}</h6>
                            <p class="card-text mb-1"><strong>Rp ${parseInt(produk.harga).toLocaleString('id-ID')}</strong></p>
                            <small class="text-muted">${produk.kode}</small>
                        </div>
                    </div>
                `;
                container.appendChild(produkCard);
            });
        }

        // Fungsi untuk memilih produk
        window.selectProduk = function(id_produk, kode, harga, id_bayar, cardElement) {
            selectedProduk = { id_produk, kode, harga, id_bayar };

            // Set form values
            document.getElementById('selected_produk_id').value = id_produk || '';
            document.getElementById('harga').value = harga;
            document.getElementById('jenis').value = id_bayar || '';

            // Load detail produk untuk menampilkan nama produk
            if (id_produk) {
                fetch('<?=base_url('orderkuota/get_detail_produk.php')?>?id=' + id_produk)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.produk) {
                            const produkNama = data.produk.produk || data.produk.keterangan || kode;
                            document.getElementById('produk').value = produkNama;
                        } else {
                            document.getElementById('produk').value = kode;
                        }
                    })
                    .catch(error => {
                        console.error('Error loading produk detail:', error);
                        document.getElementById('produk').value = kode;
                    });
            } else {
                document.getElementById('produk').value = kode;
            }

            // Highlight selected produk
            document.querySelectorAll('.produk-item-card').forEach(function(card) {
                card.classList.remove('selected');
            });
            if (cardElement) {
                cardElement.classList.add('selected');
            }

            // Close modal
            $('#modalProduk').modal('hide');

            // Scroll to form
            setTimeout(function() {
                const formSection = document.querySelector('#formTransaksi');
                if (formSection) {
                    formSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    setTimeout(function() {
                        document.getElementById('harga').focus();
                    }, 300);
                }
            }, 300);
        };

        // Fix cursor setelah halaman dimuat
        $(document).ready(function() {
            // Pastikan semua tombol dan link memiliki cursor pointer
            $('button, .btn, a.btn, input[type="button"], input[type="submit"], input[type="reset"]').css('cursor', 'pointer');
            // Pastikan input text memiliki cursor text
            $('input[type="text"], input[type="number"], input[type="date"], textarea, select').css('cursor', 'text');
            // Reset cursor untuk elemen lain
            $('body').css('cursor', 'default');
        });
        </script>

        <!-- Modal Pilih Produk -->
        <div class="modal fade" id="modalProduk" tabindex="-1" role="dialog" aria-labelledby="modalProdukLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h5 class="modal-title" id="modalProdukLabel">
                            <i class="fa fa-box"></i> Pilih Produk <span id="modalKategoriTitle"></span>
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <div id="produk-list-modal" class="row">
                            <!-- Produk akan dimuat di sini via AJAX -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
<?php
include_once('modal_item.php');
include_once('../footer.php');
?>
