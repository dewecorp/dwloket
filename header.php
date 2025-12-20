<?php
require_once "config/config.php";
$id = @$_SESSION['id_user'];
$sql = $koneksi->query("SELECT * FROM tb_user WHERE id_user ='$id'");
$tampil = $sql->fetch_assoc();
if (isset($_SESSION['level']) == "") {
	header("location:auth/login.php");
} else {
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
    <title>DW LOKET JEPARA <?= date('Y') ?></title>
    <!-- Custom CSS -->
    <link href="<?=base_url()?>/files/assets/extra-libs/c3/c3.min.css" rel="stylesheet">
    <link href="<?=base_url()?>/files/assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
    <link href="<?=base_url()?>/files/assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
    <link href="<?=base_url()?>/files/assets/extra-libs/datatables.net-bs4/css/dataTables.bootstrap4.css"
        rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?=base_url()?>/files/dist/css/style.min.css" rel="stylesheet">
    <link href="<?=base_url()?>/files/dist/css/modern-style.css" rel="stylesheet">
    <script src="<?=base_url()?>/files/assets/libs/jquery/dist/jquery.min.js"></script>
    <link href="<?=base_url()?>/files/dist/css/sweetalert2.min.css" rel="stylesheet">

</head>

<body>
    <!-- ============================================================== -->
    <!-- Preloader - style you can find in spinners.css -->
    <!-- ============================================================== -->
    <div class="preloader">
        <div class="lds-ripple">
            <div class="lds-pos"></div>
            <div class="lds-pos"></div>
        </div>
    </div>
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
                    <ul class="navbar-nav float-left mr-auto ml-3 pl-1">
                        <?php $date = date('Y-m-d'); echo format_hari_tanggal($date) ?>
                    </ul>

                    <ul class="navbar-nav float-right">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="javascript:void(0)" data-toggle="dropdown"
                                aria-haspopup="true" aria-expanded="false">
                                <img src="<?=base_url()?>/files/assets/images/<?=$tampil['foto']; ?>" alt="user"
                                    class="rounded-circle" width="50" height="50">
                                <span class="ml-2 d-none d-lg-inline-block"><span>Hai,</span> <span
                                        class="text-dark"><?=$tampil['nama']; ?></span> <i data-feather="chevron-down"
                                        class="svg-icon"></i></span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-right user-dd animated flipInY">
                                <a class="dropdown-item" href="../user/profil_user.php?id=<?=$tampil['id_user'];?>"><i
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
                            <a class="sidebar-link sidebar-link" href="<?=base_url('orderkuota/history.php')?>"
                                aria-expanded="false"><i data-feather="clock" class="feather-icon"></i><span
                                    class="hide-menu">Riwayat Pembayaran</span>
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
            <?php
		}
		?>
