<?php
require_once "config/config.php";

// Perbaikan: Cek session dengan benar untuk mengatasi masalah redirect setelah idle lama
// Masalah sebelumnya: isset($_SESSION['level']) == "" selalu true ketika session expired
// Solusi: Gunakan !isset() atau empty() untuk cek yang benar
// Perbaikan redirect: Gunakan absolute URL untuk menghindari masalah saat pindah halaman dari subfolder

// Cek session - hanya redirect jika benar-benar tidak ada session
// Session refresh sudah ditangani di config.php
if (!isset($_SESSION['level']) || empty($_SESSION['level'])) {
	header("Location: " . base_url('auth/login.php'));
	exit();
}

// Jika session valid, ambil data user
// Perbaikan keamanan: Gunakan prepared statement untuk mencegah SQL Injection
$id = isset($_SESSION['id_user']) ? (int)$_SESSION['id_user'] : 0;
$tampil = null;

if ($id > 0) {
	$stmt = $koneksi->prepare("SELECT * FROM tb_user WHERE id_user = ?");
	$stmt->bind_param("i", $id);
	$stmt->execute();
	$result = $stmt->get_result();
	$tampil = $result->fetch_assoc();
	$stmt->close();

	// Jika user tidak ditemukan atau data tidak valid, redirect ke login
	if (!$tampil || empty($tampil['id_user'])) {
		session_destroy();
		header("Location: " . base_url('auth/login.php'));
		exit();
	}
} else {
	// Jika id tidak valid, redirect ke login
	header("Location: " . base_url('auth/login.php'));
	exit();
}
?>
<!DOCTYPE html>
<html dir="ltr" lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!-- Tell the browser to be responsive to screen width -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">
    <!-- Favicon icon -->
    <link rel="icon" type="image/png" sizes="16x16" href="<?=base_url()?>/files/assets/images/dwloket_icon.png">
    <title><?= isset($page_title) && !empty($page_title) ? htmlspecialchars($page_title) . ' - ' : '' ?>DW LOKET JEPARA <?= date('Y') ?></title>
    <!-- Custom CSS -->
    <link href="<?=base_url()?>/files/assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="<?=base_url()?>/files/assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="<?=base_url()?>/files/assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="<?=base_url()?>/files/assets/extra-libs/datatables.net-bs4/css/dataTables.bootstrap4.css"
        rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?=base_url()?>/files/dist/css/style.min.css?v=<?=time()?>" rel="stylesheet">
    <link href="<?=base_url()?>/files/dist/css/modern-style.css?v=<?=time()?>" rel="stylesheet">
    <script src="<?=base_url()?>/files/assets/libs/jquery/dist/jquery.min.js"></script>
    <link href="<?=base_url()?>/files/dist/css/sweetalert2.min.css" rel="stylesheet">
    <style>
        /* Global font size reduction - Override semua CSS */
        html {
            font-size: 14px !important;
        }

        body {
            font-size: 0.875rem !important;
            color: #000000 !important;
        }

        /* Ubah semua warna font menjadi hitam untuk teks utama */
        p, span, div, label,
        td, th, li,
        .card-body, .card-body p,
        .modal-body, .modal-body p,
        .page-wrapper, .page-wrapper p,
        .table td, .table th,
        .form-control, .form-select,
        input:not([type="button"]):not([type="submit"]):not([type="reset"]),
        select, textarea {
            color: #000000 !important;
        }

        /* Heading juga hitam */
        h1, h2, h3, h4, h5, h6,
        .h1, .h2, .h3, .h4, .h5, .h6 {
            color: #000000 !important;
        }

        /* Kecuali elemen khusus yang harus tetap warnanya */
        .text-white, .text-white *,
        .btn, .btn *, button, button *,
        .badge, .badge *,
        .alert, .alert *,
        .alert-success, .alert-success *,
        .alert-danger, .alert-danger *,
        .alert-warning, .alert-warning *,
        .alert-info, .alert-info *,
        a, a:visited,
        .nav-link, .sidebar-link,
        .modal-header, .modal-header *,
        .card-header, .card-header *,
        .navbar-brand, .navbar-brand *,
        .text-primary, .text-primary *,
        .text-success, .text-success *,
        .text-danger, .text-danger *,
        .text-warning, .text-warning *,
        .text-info, .text-info *,
        .status-success-modern, .status-danger-modern,
        .status-warning-modern, .status-info-modern {
            color: inherit !important;
        }

        /* Heading sizes dan warna */
        h1, .h1 {
            font-size: 1.875rem !important;
            color: #000000 !important;
        }
        h2, .h2 {
            font-size: 1.625rem !important;
            color: #000000 !important;
        }
        h3, .h3 {
            font-size: 1.125rem !important;
            color: #000000 !important;
        }
        h4, .h4 {
            font-size: 1rem !important;
            color: #000000 !important;
        }
        h5, .h5 {
            font-size: 0.875rem !important;
            color: #000000 !important;
        }
        h6, .h6 {
            font-size: 0.75rem !important;
            color: #000000 !important;
        }

        /* Tabel - ukuran dan warna */
        .table, .table td, .table th,
        table, table td, table th,
        thead th, tbody td {
            font-size: 0.875rem !important;
            color: #000000 !important;
        }

        /* Tombol - Pastikan tombol hitam memiliki teks putih */
        .btn, button, .button,
        input[type="button"],
        input[type="submit"] {
            font-size: 0.875rem !important;
        }

        /* Tombol secondary (hitam) harus memiliki teks putih */
        .btn-secondary, .btn-secondary *,
        .btn-secondary i, .btn-secondary span,
        .btn-secondary a, a.btn-secondary,
        .btn-dark, .btn-dark *,
        .btn-dark i, .btn-dark span,
        button.btn-secondary, button.btn-secondary *,
        button.btn-dark, button.btn-dark *,
        a.btn-secondary, a.btn-secondary *,
        a.btn-secondary i, a.btn-secondary span {
            color: #ffffff !important;
        }

        /* Pastikan semua link button secondary memiliki teks putih */
        a.btn-secondary:visited,
        a.btn-secondary:link,
        a.btn-secondary:hover,
        a.btn-secondary:active {
            color: #ffffff !important;
        }

        /* Badge kategori - pastikan badge info memiliki warna yang jelas */
        .badge-info {
            background-color: #17a2b8 !important;
            color: #ffffff !important;
        }

        /* Badge secondary untuk kategori diubah ke info */
        .badge-secondary.kategori-badge-modal,
        td .badge-secondary,
        .table td .badge-secondary {
            background-color: #17a2b8 !important;
            color: #ffffff !important;
        }

        /* Form - ukuran dan warna */
        .form-control, .form-select,
        input:not([type="button"]):not([type="submit"]):not([type="reset"]),
        select, textarea,
        .input-group-text {
            font-size: 0.875rem !important;
            color: #000000 !important;
        }

        .form-label, label {
            font-size: 0.875rem !important;
            color: #000000 !important;
        }

        /* Navigasi */
        .nav-link, .sidebar-link,
        .navbar-nav .nav-link,
        .sidebar-nav .sidebar-link,
        .navbar-nav li a {
            font-size: 0.875rem !important;
        }

        /* Card & Modal */
        .card-title { font-size: 1.1rem !important; }
        .card-body, .card-body * { font-size: 0.875rem !important; }
        .modal-body, .modal-body * { font-size: 0.875rem !important; }
        .modal-title { font-size: 0.95rem !important; }

        /* Badge & Alert */
        .badge, .alert { font-size: 0.875rem !important; }

        /* Text umum - ukuran dan warna */
        p {
            font-size: 0.875rem !important;
            color: #000000 !important;
        }
        span:not(.fa):not(.fas):not(.far):not(.fab):not(.fal) {
            font-size: 0.875rem !important;
            color: #000000 !important;
        }
        div:not(.fa):not(.fas):not(.far):not(.fab) {
            font-size: 0.875rem !important;
            color: #000000 !important;
        }

        small, .small { font-size: 0.75rem !important; }

        /* Page wrapper dan konten utama */
        .page-wrapper {
            font-size: 0.875rem !important;
        }

        /* Override untuk semua elemen dalam page */
        .page-wrapper p,
        .page-wrapper span:not(.fa),
        .page-wrapper div:not(.fa),
        .page-wrapper td,
        .page-wrapper th {
            font-size: 0.875rem !important;
            color: #000000 !important;
        }

        /* Card body dan modal body */
        .card-body p, .card-body span, .card-body td, .card-body th,
        .modal-body p, .modal-body span, .modal-body td, .modal-body th {
            color: #000000 !important;
        }

        /* Responsive header styles - prevent logo and date overlap */
        .navbar-brand .dark-logo {
            max-width: 100%;
            height: auto;
        }

        /* Ensure navbar content doesn't overlap */
        .navbar-collapse {
            flex-wrap: wrap;
        }

        .navbar-nav.float-left {
            min-width: 0;
            flex: 1 1 auto;
            margin-right: 1rem;
        }

        /* Logo responsive sizing */
        @media (max-width: 991px) {
            .navbar-brand .dark-logo {
                width: 150px !important;
                height: auto;
            }
        }

        @media (max-width: 767px) {
            .navbar-brand .dark-logo {
                width: 120px !important;
                height: auto;
            }

            /* Styling untuk tanggal di mobile */
            .navbar-collapse .text-muted {
                font-size: 0.85rem;
                font-weight: 500;
                color: #6c757d !important;
            }
        }

        @media (max-width: 576px) {
            .navbar-brand .dark-logo {
                width: 100px !important;
                height: auto;
            }
        }
    </style>

</head>

<body>
    <!-- ============================================================== -->
    <!-- Main wrapper - style you can find in pages.scss -->
    <!-- ============================================================== -->
    <div id="main-wrapper" data-theme="light" data-layout="vertical" data-navbarbg="skin6" data-sidebartype="full"
        data-sidebar-position="fixed" data-header-position="fixed" data-boxed-layout="full">
        <!-- ============================================================== -->
        <!-- Topbar header - style you can find in pages.scss -->
        <!-- ============================================================== -->
        <header class="topbar" data-navbarbg="skin6">
            <nav class="navbar top-navbar navbar-expand-md">
                <div class="navbar-header" data-logobg="skin6">
                    <!-- This is for the sidebar toggle which is visible on mobile only -->
                    <a class="nav-toggler waves-effect waves-light d-block d-md-none" href="javascript:void(0)"><i
                            class="ti-menu ti-close"></i></a>
                    <!-- ============================================================== -->
                    <!-- Logo -->
                    <!-- ============================================================== -->
                    <div class="navbar-brand">
                        <!-- Logo icon -->
                        <a href="<?=base_url('home')?>">
                            <b class="logo-icon">
                                <!-- Dark Logo icon -->
                                <img src="<?=base_url()?>/files/assets/images/dwloket_logo.png" alt="homepage"
                                    class="dark-logo" width="200" height="50" />
                                <!-- Light Logo icon -->
                            </b>
                        </a>
                    </div>

                    <a class="topbartoggler d-block d-md-none waves-effect waves-light" href="javascript:void(0)"
                        data-toggle="collapse" data-target="#navbarSupportedContent"
                        aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation"><i
                            class="ti-more"></i></a>
                </div>
                <!-- ============================================================== -->
                <!-- End Logo -->
                <!-- ============================================================== -->
                <div class="navbar-collapse collapse" id="navbarSupportedContent">
                    <!-- Tanggal untuk desktop dan tablet -->
                    <ul class="navbar-nav float-left mr-auto ml-3 pl-1 d-none d-md-flex">
                        <?php $date = date('Y-m-d'); echo format_hari_tanggal($date) ?>
                    </ul>

                    <!-- Tanggal untuk mobile (ditampilkan di baris terpisah) -->
                    <div class="d-flex d-md-none w-100 justify-content-center mb-2 px-3">
                        <span class="text-muted small"><?php $date = date('Y-m-d'); echo format_hari_tanggal($date) ?></span>
                    </div>

                    <ul class="navbar-nav float-right">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="javascript:void(0)" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <img src="<?=base_url()?>/files/assets/images/<?=htmlspecialchars($tampil['foto'] ?? 'default.png', ENT_QUOTES, 'UTF-8'); ?>" alt="user"
                                    class="rounded-circle" width="50" height="50">
                                <span class="ml-2 d-none d-lg-inline-block"><span>Hai,</span> <span
                                        class="text-dark"><?=htmlspecialchars($tampil['nama'] ?? 'User', ENT_QUOTES, 'UTF-8'); ?></span> <i data-feather="chevron-down"
                                        class="svg-icon"></i></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right user-dd animated flipInY">
                                <a class="dropdown-item" href="../user/profil_user.php?id=<?=htmlspecialchars($tampil['id_user'] ?? 0, ENT_QUOTES, 'UTF-8');?>"><i
                                        data-feather="user" class="svg-icon mr-2 ml-1"></i>
                                    My Profile</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="javascript:void(0)" onclick="confirmLogout()"><i data-feather="power"
                                        class="svg-icon mr-2 ml-1"></i>
                                    Logout</a>
                            </div>
                        </li>
                        <!-- ============================================================== -->
                        <!-- User profile and search -->
                        <!-- ============================================================== -->
                    </ul>
                </div>

            </nav>
        </header>
        <aside class="left-sidebar" data-sidebarbg="skin6">
            <!-- Sidebar scroll-->
            <div class="scroll-sidebar" data-sidebarbg="skin6">
                <!-- Sidebar navigation-->
                <nav class="sidebar-nav">
                    <ul id="sidebarnav">
                        <li class="sidebar-item">
                            <a class="sidebar-link sidebar-link" href="<?=base_url('home')?>" aria-expanded="false"><i
                                    data-feather="home" class="feather-icon"></i><span
                                    class="hide-menu">Dashboard</span>
                            </a>
                        </li>
                        <li class="list-divider"></li>
                        <li class="nav-small-cap">
                            <span class="hide-menu">MENU UTAMA</span>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link" href="<?=base_url('pelanggan/pelanggan.php')?>"
                                aria-expanded="false"><i data-feather="users" class="feather-icon"></i><span
                                    class="hide-menu">Pelanggan</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link sidebar-link" href="<?=base_url('jenisbayar/jenis_bayar.php')?>"
                                aria-expanded="false"><i data-feather="dollar-sign" class="feather-icon"></i><span
                                    class="hide-menu">Jenis Pembayaran</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link sidebar-link" href="<?=base_url('saldo/saldo.php')?>"
                                aria-expanded="false"><i data-feather="credit-card" class="feather-icon"></i><span
                                    class="hide-menu">Saldo</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link sidebar-link" href="<?=base_url('transaksi/transaksi.php')?>"
                                aria-expanded="false"><i data-feather="shopping-cart" class="feather-icon"></i><span
                                    class="hide-menu">Transaksi</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link sidebar-link" href="<?=base_url('orderkuota/index.php')?>"
                                aria-expanded="false"><i data-feather="smartphone" class="feather-icon"></i><span
                                    class="hide-menu">OrderKuota</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link sidebar-link" href="<?=base_url('orderkuota/deposit.php')?>"
                                aria-expanded="false"><i data-feather="credit-card" class="feather-icon"></i><span
                                    class="hide-menu">Deposit OrderKuota</span>
                            </a>
                        </li>
                        <li class="list-divider"></li>
                        <li class="nav-small-cap">
                            <span class="hide-menu">Extra</span>
                        </li>
                        <?php if (isset($_SESSION['level']) && $_SESSION['level'] == 'admin'): ?>
                        <li class="sidebar-item">
                            <a class="sidebar-link sidebar-link" href="<?=base_url('admin/backup.php')?>"
                                aria-expanded="false"><i data-feather="database" class="feather-icon"></i><span
                                    class="hide-menu">Backup & Restore</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link sidebar-link" href="<?=base_url('admin/orderkuota_config.php')?>"
                                aria-expanded="false"><i data-feather="settings" class="feather-icon"></i><span
                                    class="hide-menu">OrderKuota Config</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <li class="sidebar-item">
                            <a class="sidebar-link sidebar-link" href="<?=base_url('user/user.php')?>"
                                aria-expanded="false"><i data-feather="user" class="feather-icon"></i><span
                                    class="hide-menu">User</span>
                            </a>
                        </li>
                        <li class="sidebar-item">
                            <a class="sidebar-link sidebar-link" href="javascript:void(0)" onclick="confirmLogout()"
                                aria-expanded="false"><i data-feather="log-out" class="feather-icon"></i><span
                                    class="hide-menu">Log Out</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <!-- End Sidebar navigation -->
            </div>
            <!-- End Sidebar scroll-->
        </aside>
        <div class="page-wrapper">
