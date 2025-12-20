<?php
include_once('../header.php');
include_once('../config/config.php');

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
$sql = $koneksi->query("SELECT * FROM transaksi WHERE id_transaksi='$id'");
$data = $sql->fetch_assoc();
$status = $data['status'];
$tgl = $data['tgl'];
$selected_id_bayar = $data['id_bayar'] ?? '';

// Ambil informasi produk jika ada kode produk di keterangan atau cari berdasarkan id_bayar
$produk_info = null;
if (!empty($data['ket'])) {
    // Coba extract kode produk dari keterangan
    preg_match('/\b([A-Z0-9]{3,20})\b/', $data['ket'], $matches);
    if (!empty($matches[1])) {
        require_once '../libs/produk_helper.php';
        $produk_info = getProdukByKode($matches[1]);
    }
}
// Jika tidak ditemukan dari keterangan, cari produk berdasarkan id_bayar
if (!$produk_info && $selected_id_bayar) {
    require_once '../libs/produk_helper.php';
    $produk_list_by_bayar = getProdukByIdBayar($selected_id_bayar, true);
    if (!empty($produk_list_by_bayar)) {
        $produk_info = $produk_list_by_bayar[0]; // Ambil produk pertama
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
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Edit Transaksi</title>
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
                    <div class="modern-card">
                        <div class="modern-card-header">
                            <h4>
                                <i class="fa fa-edit"></i> Edit Transaksi
                            </h4>
                        </div>
                        <div class="modern-card-body">
                            <form action="#" method="POST">
                                <input type="hidden" name="id" value="<?=$data['id_transaksi']?>">
                                <input type="hidden" name="jenis" id="jenis" value="<?=$selected_id_bayar?>" required>

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
                                                    <input type="text" name="produk" id="produk" class="form-control" value="<?=htmlspecialchars($produk_info ? ($produk_info['produk'] ?: $produk_info['keterangan'] ?: $produk_info['kode']) : '')?>" readonly>
                                                    <small class="form-text text-muted">Informasi produk terkait transaksi</small>
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
                                            <a href="<?=base_url('transaksi/transaksi.php')?>" class="btn btn-warning btn-modern mr-2">
                                                <i class="fa fa-arrow-left"></i> Kembali
                                            </a>
                                            <button type="submit" name="edit" class="btn btn-success btn-modern">
                                                <i class="fa fa-save"></i> Simpan Perubahan
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php
                    if(@$_POST['edit']) {
                    $id     = @$_POST['id'];
                    $tgl    = @$_POST['tgl'];
                    $idpel  = @$_POST['idpel'];
                    $nama   = @$_POST['nama'];
                    $jenis  = @$_POST['jenis'];
                    $harga  = @$_POST['harga'];
                    $status = @$_POST['status'];
                    $ket    = @$_POST['ket'];

                    $sql = $koneksi->query("UPDATE transaksi SET tgl='$tgl', idpel='$idpel', nama='$nama', id_bayar='$jenis', harga='$harga', status='$status', ket='$ket' WHERE id_transaksi='$id'");
                    if ($sql) {
						// Log aktivitas
						require_once '../libs/log_activity.php';
						@log_activity('update', 'transaksi', 'Mengedit transaksi ID: ' . $id);
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
									text: 'Transaksi Berhasil Diedit',
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
								alert('Transaksi Berhasil Diedit!');
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
									text: 'Terjadi kesalahan saat mengedit transaksi',
									confirmButtonColor: '#dc3545',
									confirmButtonText: 'OK'
								});
							} else {
								alert('Terjadi kesalahan saat mengedit transaksi');
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
        </div>

        <script>
        // No need for kategori selection in edit mode
        </script>
    </body>
</html>
<?php
include_once('modal_item.php');
include_once('../footer.php');
?>
