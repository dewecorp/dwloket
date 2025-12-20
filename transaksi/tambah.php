<?php
include_once('../header.php');
include_once('../config/config.php');
require_once '../libs/produk_helper.php';

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

// Ambil data jenis pembayaran secara dinamis dari database
// Setiap kali halaman dimuat, akan mengambil data terbaru dari database
// Jadi jika ada jenis pembayaran baru yang ditambahkan, otomatis akan muncul di grid
$sql_jenis = $koneksi->query("SELECT * FROM tb_jenisbayar ORDER BY jenis_bayar ASC");
$jenis_bayar_list = [];
if ($sql_jenis) {
    while ($row = $sql_jenis->fetch_assoc()) {
        // Ambil jumlah produk untuk setiap jenis bayar
        $row['jumlah_produk'] = count(getProdukByIdBayar($row['id_bayar'], true));
        $jenis_bayar_list[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Tambah Transaksi</title>
        <style>
        .jenis-bayar-card {
            cursor: pointer !important;
            transition: all 0.3s;
            position: relative;
            z-index: 100 !important;
            pointer-events: auto !important;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        .jenis-bayar-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .jenis-bayar-card * {
            pointer-events: none !important;
        }
        .jenis-bayar-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: -1;
            pointer-events: none;
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
        .produk-item-card {
            transition: all 0.3s;
        }
        .produk-item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
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
                                <li class="breadcrumb-item text-muted active" aria-current="page">Tambah Transaksi</li>
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
                            <i class="fa fa-plus"></i> Tambah Transaksi
                        </h4>
                    </div>
                    <div class="modern-card-body">
                        <form action="#" method="POST">
                            <!-- Jenis Pembayaran Grid - Paling Atas -->
                            <div class="modern-card mb-4">
                                <div class="modern-card-header">
                                    <h4>
                                        <i class="fa fa-credit-card"></i> Pilih Jenis Pembayaran <span class="text-danger">*</span>
                                    </h4>
                                </div>
                                <div class="modern-card-body">
                                    <input type="hidden" name="jenis" id="jenis" required>
                                    <div class="row">
                                        <?php foreach ($jenis_bayar_list as $jenis):
                                            $style = getJenisBayarStyle($jenis['jenis_bayar']);
                                        ?>
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="card jenis-bayar-card border-<?=$style['color']?>"
                                                 data-id-bayar="<?=$jenis['id_bayar']?>"
                                                 data-jenis-bayar="<?=htmlspecialchars($jenis['jenis_bayar'])?>"
                                                 style="cursor: pointer !important; user-select: none; position: relative; z-index: 100;"
                                                 role="button"
                                                 tabindex="0">
                                                <div class="card-body text-center">
                                                    <i class="fa <?=$style['icon']?> fa-3x text-<?=$style['color']?> mb-2"></i>
                                                    <h6 class="mb-0"><?=htmlspecialchars($jenis['jenis_bayar'])?></h6>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Pembayaran -->
                            <div class="modern-card">
                                <div class="modern-card-header">
                                    <h4>
                                        <i class="fa fa-edit"></i> Form Pembayaran
                                    </h4>
                                </div>
                                <div class="modern-card-body">
                                    <div class="row">
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label for="tgl" class="form-label">
                                                    <i class="fa fa-calendar-alt text-primary"></i> Tanggal Transaksi
                                                </label>
                                                <input type="date" name="tgl" value="<?=date('Y-m-d')?>" class="form-control" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="idpel" class="form-label">
                                                    <i class="fa fa-user text-info"></i> ID Pelanggan
                                                </label>
                                                <div class="input-group">
                                                    <input type="hidden" name="id_pelanggan" id="id_pelanggan">
                                                    <input type="text" name="idpel" id="idpel" placeholder="ID Pelanggan" class="form-control" readonly>
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
                                                <input type="text" name="nama" id="nama" placeholder="Nama Pelanggan" class="form-control" readonly>
                                            </div>
                                            <div class="form-group">
                                                <label for="ket" class="form-label">
                                                    <i class="fa fa-comment text-warning"></i> Keterangan
                                                </label>
                                                <input type="text" name="ket" class="form-control" placeholder="Isi Keterangan Transaksi">
                                            </div>
                                        </div>
                                        <div class="col-lg-6">
                                            <div class="form-group">
                                                <label for="harga" class="form-label">
                                                    <i class="fa fa-money-bill-wave text-success"></i> Harga <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">Rp</span>
                                                    </div>
                                                    <input type="number" name="harga" id="harga" class="form-control" placeholder="0" required>
                                                </div>
                                                <small class="form-text text-muted">
                                                    Atau pilih produk dari daftar di bawah untuk mengisi harga otomatis
                                                </small>
                                            </div>

                                            <!-- Daftar Produk (akan muncul setelah jenis bayar dipilih) -->
                                            <div id="produk-list-container" style="display: none;">
                                                <div class="form-group">
                                                    <label class="form-label">
                                                        <i class="fa fa-box text-info"></i> Pilih Produk (Opsional)
                                                    </label>
                                                    <div id="produk-list" class="row"></div>
                                                    <small class="form-text text-muted">
                                                        Klik produk untuk mengisi harga secara otomatis
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="status" class="form-label">
                                                    <i class="fa fa-info-circle text-primary"></i> Status
                                                </label>
                                                <select class="form-control" name="status" id="status">
                                                    <option value="">Pilih Status</option>
                                                    <option value="Lunas">Lunas</option>
                                                    <option value="Belum">Belum Bayar</option>
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
                                        <a href="<?=base_url('transaksi/transaksi.php')?>" class="btn btn-warning btn-modern mr-2">
                                            <i class="fa fa-arrow-left"></i> Kembali
                                        </a>
                                        <button type="submit" name="simpan" class="btn btn-success btn-modern">
                                            <i class="fa fa-save"></i> Simpan Transaksi
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
                <?php
                if (@$_POST['simpan']) {
                $tgl    = @$_POST['tgl'];
                $idpel  = @$_POST['idpel'];
                $nama   = @$_POST['nama'];
                $jenis  = @$_POST['jenis'];
                $harga  = @$_POST['harga'];
                $status = @$_POST['status'];
                $ket    = @$_POST['ket'];

                $sql = $koneksi->query("INSERT INTO transaksi (tgl, idpel, nama, id_bayar, harga, status, ket ) VALUES ('$tgl', '$idpel', '$nama', '$jenis', '$harga', '$status', '$ket')");

                if ($sql) {
					// Log aktivitas
					require_once '../libs/log_activity.php';
					@log_activity('create', 'transaksi', 'Menambah transaksi: ' . $nama . ' (ID: ' . $idpel . ')');
                ?>
                <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
                <script>
				// Tunggu hingga DOM dan SweetAlert ready
				document.addEventListener('DOMContentLoaded', function() {
					// Tunggu sedikit untuk memastikan SweetAlert ter-load
					setTimeout(function() {
						if (typeof Swal !== 'undefined') {
							Swal.fire({
								icon: 'success',
								title: 'Berhasil!',
								text: 'Transaksi Berhasil Ditambahkan',
								confirmButtonColor: '#28a745',
								timer: 2000,
								timerProgressBar: true,
								showConfirmButton: true,
								confirmButtonText: 'OK'
							}).then((result) => {
								// Redirect ke halaman transaksi
								window.location.href = "<?=base_url('transaksi/transaksi.php')?>";
							});

							// Fallback redirect setelah 2.5 detik
							setTimeout(function() {
								window.location.href = "<?=base_url('transaksi/transaksi.php')?>";
							}, 2500);
						} else {
							// Fallback jika SweetAlert tidak tersedia
							alert('Transaksi Berhasil Ditambahkan!');
							window.location.href = "<?=base_url('transaksi/transaksi.php')?>";
						}
					}, 100);
				});
                </script>
                <?php
                } else {
                ?>
                <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
                <script>
				document.addEventListener('DOMContentLoaded', function() {
					setTimeout(function() {
						if (typeof Swal !== 'undefined') {
							Swal.fire({
								icon: 'error',
								title: 'Gagal!',
								text: 'Terjadi kesalahan saat menambahkan transaksi',
								confirmButtonColor: '#dc3545',
								confirmButtonText: 'OK'
							});
						} else {
							alert('Terjadi kesalahan saat menambahkan transaksi');
						}
					}, 100);
				});
                </script>
                <?php
                }
                }
                ?>
            </div>
        </div>

        <script>
        // Handle selection jenis pembayaran
        document.addEventListener('DOMContentLoaded', function() {
            const jenisBayarCards = document.querySelectorAll('.jenis-bayar-card');
            const jenisInput = document.getElementById('jenis');
            const produkListContainer = document.getElementById('produk-list-container');
            const produkListDiv = document.getElementById('produk-list');
            const hargaInput = document.getElementById('harga');

            jenisBayarCards.forEach(function(card) {
                card.addEventListener('click', function() {
                    // Remove selected class from all cards
                    jenisBayarCards.forEach(function(c) {
                        c.classList.remove('selected');
                    });

                    // Add selected class to clicked card
                    this.classList.add('selected');

                    // Set hidden input value
                    const idBayar = this.getAttribute('data-id-bayar');
                    jenisInput.value = idBayar;

                    // Load produk untuk jenis bayar yang dipilih
                    loadProdukByJenisBayar(idBayar);

                    // Scroll card into view if needed
                    this.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                });
            });

            // Fungsi untuk load produk berdasarkan jenis bayar
            function loadProdukByJenisBayar(idBayar) {
                if (!idBayar) {
                    produkListContainer.style.display = 'none';
                    return;
                }

                // Show loading
                produkListDiv.innerHTML = '<div class="col-12"><div class="text-center p-3"><i class="fa fa-spinner fa-spin"></i> Memuat produk...</div></div>';
                produkListContainer.style.display = 'block';

                // Load produk via AJAX
                fetch('<?=base_url('transaksi/get_produk.php')?>?id_bayar=' + idBayar)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.produk.length > 0) {
                            let html = '';
                            data.produk.forEach(function(produk) {
                                html += `
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <div class="card produk-item-card border"
                                             style="cursor: pointer; transition: all 0.3s;"
                                             data-kode="${produk.kode}"
                                             data-harga="${produk.harga}"
                                             onclick="selectProduk('${produk.kode}', ${produk.harga})">
                                            <div class="card-body p-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <small class="badge badge-info">${produk.kode}</small>
                                                    <strong class="text-success">Rp ${parseInt(produk.harga).toLocaleString('id-ID')}</strong>
                                                </div>
                                                <small class="d-block text-truncate mt-1" title="${produk.keterangan}">${produk.keterangan}</small>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            produkListDiv.innerHTML = html;
                        } else {
                            produkListDiv.innerHTML = '<div class="col-12"><div class="alert alert-info text-center p-2 mb-0">Tidak ada produk tersedia untuk jenis pembayaran ini</div></div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading produk:', error);
                        produkListDiv.innerHTML = '<div class="col-12"><div class="alert alert-warning text-center p-2 mb-0">Gagal memuat produk</div></div>';
                    });
            }

            // Fungsi global untuk select produk
            window.selectProduk = function(kode, harga) {
                hargaInput.value = harga;

                // Highlight selected produk
                document.querySelectorAll('.produk-item-card').forEach(function(card) {
                    card.classList.remove('border-primary');
                    card.style.backgroundColor = '';
                });
                event.currentTarget.classList.add('border-primary');
                event.currentTarget.style.backgroundColor = '#f0f8ff';

                // Scroll to harga input
                hargaInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
                hargaInput.focus();
            };
        });
        </script>
    </body>
</html>
<?php
include"modal_item.php";
include_once('../footer.php');
?>




