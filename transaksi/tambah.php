<?php
include_once('../header.php');
include_once('../config/config.php');
require_once '../libs/produk_helper.php';

// Ambil semua kategori produk
$all_kategori = getAllKategori();

// Ambil data jenis pembayaran untuk mapping
$sql_jenis = $koneksi->query("SELECT * FROM tb_jenisbayar ORDER BY jenis_bayar ASC");
$jenis_bayar_list = [];
if ($sql_jenis) {
    while ($row = $sql_jenis->fetch_assoc()) {
        $jenis_bayar_list[$row['id_bayar']] = $row;
    }
}
?>
<style>
        .kategori-card {
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            z-index: 100;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        .kategori-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .kategori-card.selected {
            border-color: #28a745 !important;
            background-color: #f0fff4;
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }
        .kategori-card.selected .card-body {
            background-color: #f0fff4;
        }
        .kategori-card .card-body {
            text-align: center;
            padding: 1.5rem 1rem;
        }
        .produk-item-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        .produk-item-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
        }
        .produk-item-card.selected {
            border-color: #007bff !important;
            background-color: #f0f8ff;
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
        #form-pembayaran-section {
            display: none;
        }
        </style>
    </head>
    <body>
        <a name="top" id="top"></a>
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
                <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-<?=($_SESSION['error_success'] ?? false) ? 'success' : 'danger'?> alert-dismissible fade show" role="alert" style="margin-bottom: 10px;">
                    <i class="fa fa-<?=($_SESSION['error_success'] ?? false) ? 'check-circle' : 'exclamation-triangle'?>"></i>
                    <?=htmlspecialchars($_SESSION['error_message'])?>
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php
                unset($_SESSION['error_message']);
                unset($_SESSION['error_success']);
                endif; ?>
                <div class="modern-card" style="margin-bottom: 10px;">
                    <div class="modern-card-header" style="padding: 10px 15px;">
                        <h4 style="margin: 0; font-size: 1.1rem;">
                            <i class="fa fa-plus"></i> Tambah Transaksi
                        </h4>
                    </div>
                    <div class="modern-card-body" style="padding: 12px 15px;">
                        <form action="proses.php" method="POST" id="formTransaksi">
                            <!-- Grid Kategori Produk -->
                            <a name="kategori-section" id="kategori-section"></a>
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
                                            <div class="card kategori-card border-primary"
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
                                    <input type="hidden" name="jenis" id="jenis" required>
                                    <input type="hidden" name="selected_produk_id" id="selected_produk_id">
                                    <input type="hidden" name="selected_kategori" id="selected_kategori">

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
                                                <label for="produk" class="form-label">
                                                    <i class="fa fa-box text-info"></i> Produk
                                                </label>
                                                <input type="text" name="produk" id="produk" class="form-control" placeholder="Produk akan terisi otomatis saat memilih produk" readonly>
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
                                                    <input type="number" name="harga" id="harga" class="form-control" placeholder="0" required>
                                                </div>
                                                <small class="form-text text-muted">Harga akan terisi otomatis saat memilih produk</small>
                                            </div>
                                            <div class="form-group">
                                                <label for="status" class="form-label">
                                                    <i class="fa fa-info-circle text-primary"></i> Status <span class="text-danger">*</span>
                                                </label>
                                                <select class="form-control" name="status" id="status" required>
                                                    <option value="">Pilih Status</option>
                                                    <option value="Lunas">Lunas</option>
                                                    <option value="Belum">Belum Bayar</option>
                                                </select>
                                    </div>
                                </div>
                            </div>

                            <!-- Tombol Aksi -->
                            <div class="row mt-4">
                                <div class="col-12">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <button type="button" class="btn btn-secondary btn-modern" onclick="resetForm(); return false;" style="cursor: pointer !important;">
                                                    <i class="fa fa-refresh"></i> Reset Form
                                                </button>
                                                <div>
                                        <a href="<?=base_url('transaksi/transaksi.php')?>" class="btn btn-warning btn-modern mr-2" style="cursor: pointer !important;">
                                            <i class="fa fa-arrow-left"></i> Kembali
                                        </a>
                                        <button type="submit" name="simpan" class="btn btn-success btn-modern" style="cursor: pointer !important;">
                                            <i class="fa fa-save"></i> Simpan Transaksi
                                        </button>
                                                </div>
                                            </div>
                                        </div>
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
				document.addEventListener('DOMContentLoaded', function() {
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
								window.location.href = "<?=base_url('transaksi/transaksi.php')?>";
							});

							setTimeout(function() {
								window.location.href = "<?=base_url('transaksi/transaksi.php')?>";
							}, 2500);
						} else {
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
        let selectedKategori = '';
        let selectedProduk = null;

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
            fetch('<?=base_url('transaksi/get_produk.php')?>?kategori=' + encodeURIComponent(kategori))
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.produk.length > 0) {
                            let html = '';
                            data.produk.forEach(function(produk) {
                            const escapedKode = (produk.kode || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
                            const produkNama = (produk.produk || produk.keterangan || '').replace(/"/g, '&quot;').replace(/'/g, "\\'");
                                html += `
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <div class="card produk-item-card border"
                                         data-produk-id="${produk.id_produk}"
                                         data-kode="${escapedKode}"
                                             data-harga="${produk.harga}"
                                         data-id-bayar="${produk.id_bayar || ''}"
                                         onclick="selectProduk(${produk.id_produk}, '${escapedKode}', ${produk.harga}, ${produk.id_bayar || 'null'}, this)">
                                            <div class="card-body p-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                <small class="badge badge-info">${produk.kode || ''}</small>
                                                <strong class="text-success">Rp ${parseInt(produk.harga || 0).toLocaleString('id-ID')}</strong>
                                            </div>
                                            <small class="d-block text-truncate mt-1" title="${produkNama}">${produkNama || ''}</small>
                                            </div>
                                        </div>
                                    </div>
                                `;
                            });
                            produkListDiv.innerHTML = html;
                        } else {
                        produkListDiv.innerHTML = '<div class="col-12"><div class="alert alert-info text-center p-2 mb-0">Tidak ada produk tersedia untuk kategori ini</div></div>';
                        }
                    })
                    .catch(error => {
                        console.error('Error loading produk:', error);
                        produkListDiv.innerHTML = '<div class="col-12"><div class="alert alert-warning text-center p-2 mb-0">Gagal memuat produk</div></div>';
                    });
            }

            // Fungsi global untuk select produk
        window.selectProduk = function(id_produk, kode, harga, id_bayar, cardElement) {
            selectedProduk = { id_produk, kode, harga, id_bayar };

            // Set form values
            document.getElementById('selected_produk_id').value = id_produk;
            document.getElementById('harga').value = harga;
            document.getElementById('jenis').value = id_bayar || '';

            // Load detail produk untuk menampilkan nama produk
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

                // Highlight selected produk
                document.querySelectorAll('.produk-item-card').forEach(function(card) {
                card.classList.remove('selected');
            });
            if (cardElement) {
                cardElement.classList.add('selected');
            }

            // Tutup modal
            $('#modalProduk').modal('hide');

            // Scroll langsung ke form pembayaran dengan animasi smooth
            // Pastikan kategori tetap terlihat di atas
            setTimeout(function() {
                // Cari form pembayaran berdasarkan header
                const formCards = document.querySelectorAll('.modern-card');
                let formSection = null;

                formCards.forEach(function(card) {
                    const header = card.querySelector('.modern-card-header h4');
                    if (header && header.textContent.includes('Form Pembayaran')) {
                        formSection = card;
                    }
                });

                if (formSection) {
                    // Scroll dengan offset agar kategori tetap terlihat
                    const formOffset = formSection.offsetTop - 20; // 20px offset dari atas
                    window.scrollTo({
                        top: formOffset,
                        behavior: 'smooth'
                    });

                    // Focus ke field harga setelah scroll dimulai
                    setTimeout(function() {
                        const hargaInput = document.getElementById('harga');
                        if (hargaInput) {
                hargaInput.focus();
                            hargaInput.select();
                        }
                    }, 600);
                }
            }, 400);
        };

        // Fungsi reset form (global function)
        window.resetForm = function() {
            // Reset form values
            document.getElementById('formTransaksi').reset();

            // Reset hidden fields
            document.getElementById('selected_produk_id').value = '';
            document.getElementById('selected_kategori').value = '';
            document.getElementById('jenis').value = '';
            document.getElementById('id_pelanggan').value = '';

            // Reset visible fields
            document.getElementById('idpel').value = '';
            document.getElementById('nama').value = '';
            document.getElementById('produk').value = '';
            document.getElementById('harga').value = '';
            document.getElementById('ket').value = '';

            // Reset dropdown
            document.getElementById('status').value = '';

            // Reset tanggal ke hari ini
            const today = new Date().toISOString().split('T')[0];
            document.querySelector('input[name="tgl"]').value = today;

            // Reset selected states
            document.querySelectorAll('.kategori-card').forEach(function(card) {
                card.classList.remove('selected');
            });
            document.querySelectorAll('.produk-item-card').forEach(function(card) {
                card.classList.remove('selected');
            });

            selectedKategori = '';
            selectedProduk = null;

            // Tutup modal produk jika terbuka
            $('#modalProduk').modal('hide');

            // Scroll langsung ke atas penuh - gunakan pendekatan paling sederhana
            // Scroll langsung tanpa delay
            window.scrollTo(0, 0);
            document.documentElement.scrollTop = 0;
            document.body.scrollTop = 0;

            // jQuery scroll langsung (jika tersedia)
            if (typeof $ !== 'undefined') {
                $('html, body').stop(true, true).scrollTop(0);
            }

            // Gunakan location.hash untuk scroll ke anchor top
            if (document.getElementById('top')) {
                location.hash = '#top';
            }

            // Force scroll sekali lagi untuk memastikan
            setTimeout(function() {
                window.scrollTo(0, 0);
                document.documentElement.scrollTop = 0;
                document.body.scrollTop = 0;
                if (typeof $ !== 'undefined') {
                    $('html, body').scrollTop(0);
                }
            }, 10);
        };

        // Event listener untuk tombol reset form - memastikan scroll bekerja
        $(document).ready(function() {
            $('button[onclick*="resetForm"]').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                // Panggil fungsi resetForm
                if (typeof window.resetForm === 'function') {
                    window.resetForm();
                }

                // Scroll langsung setelah fungsi dipanggil - tanpa delay
                window.scrollTo(0, 0);
                document.documentElement.scrollTop = 0;
                document.body.scrollTop = 0;
                if (typeof $ !== 'undefined') {
                    $('html, body').stop(true, true).scrollTop(0);
                }

                return false;
            });
        });

        // Validasi form sebelum submit
        document.getElementById('formTransaksi').addEventListener('submit', function(e) {
            console.log('Form submit event triggered');
            const jenisValue = document.getElementById('jenis').value;
            const statusValue = document.getElementById('status').value;
            const hargaValue = document.getElementById('harga').value;
            const tglValue = document.querySelector('input[name="tgl"]').value;
            const idpelValue = document.getElementById('idpel').value;
            const namaValue = document.getElementById('nama').value;

            // Validasi semua field wajib
            if (!tglValue || tglValue === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Data Tidak Lengkap',
                    text: 'Silakan isi Tanggal Transaksi terlebih dahulu.',
                    confirmButtonColor: '#dc3545'
                });
                return false;
            }

            if (!idpelValue || idpelValue === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Data Tidak Lengkap',
                    text: 'Silakan pilih Pelanggan terlebih dahulu.',
                    confirmButtonColor: '#dc3545'
                });
                return false;
            }

            if (!namaValue || namaValue === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Data Tidak Lengkap',
                    text: 'Nama Pelanggan tidak boleh kosong.',
                    confirmButtonColor: '#dc3545'
                });
                return false;
            }

            if (!jenisValue || jenisValue === '' || parseInt(jenisValue) <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Data Tidak Lengkap',
                    text: 'Silakan pilih Jenis Pembayaran terlebih dahulu.',
                    confirmButtonColor: '#dc3545'
                });
                return false;
            }

            if (!hargaValue || hargaValue === '' || parseFloat(hargaValue) <= 0) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Data Tidak Lengkap',
                    text: 'Silakan isi Harga dengan benar.',
                    confirmButtonColor: '#dc3545'
                });
                return false;
            }

            if (!statusValue || statusValue === '') {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Data Tidak Lengkap',
                    text: 'Silakan pilih Status terlebih dahulu.',
                    confirmButtonColor: '#dc3545'
                });
                return false;
            }

            // Jika semua valid, biarkan form submit normal
            // Jangan preventDefault, biarkan form submit secara normal
            console.log('Form valid, submitting...');
            return true;
        });

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

        <!-- Modal Produk -->
        <div class="modal fade" id="modalProduk" tabindex="-1" role="dialog" aria-labelledby="modalProdukLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <h4 class="modal-title" id="modalProdukLabel">
                            <i class="fa fa-box"></i> Pilih Produk
                            <span id="modalKategoriTitle" class="ml-2"></span>
                        </h4>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close" style="color: white;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body" style="max-height: 70vh; overflow-y: auto;">
                        <div id="produk-list-modal" class="row"></div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">
                            <i class="fa fa-times"></i> Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </body>
</html>
<?php
include"modal_item.php";
include_once('../footer.php');
?>
