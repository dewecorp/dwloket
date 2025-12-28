<?php
$page_title = 'Dashboard';
// config/config.php sudah di-include di header.php, tidak perlu di-include lagi
include_once('../header.php');
require_once '../libs/log_activity.php';
require_once '../libs/saldo_helper.php';

$id = @$_GET['id'];
$data_pelanggan = $koneksi->query("SELECT * FROM pelanggan");
$jumlah_pelanggan = $data_pelanggan->num_rows;
$data_transaksi = $koneksi->query("SELECT * FROM transaksi");
$jumlah_transaksi = $data_transaksi->num_rows;
$data_jenisbayar = $koneksi->query("SELECT * FROM tb_jenisbayar");
$jumlah_jenisbayar = $data_jenisbayar->num_rows;

// Hitung total saldo menggunakan fungsi helper (SUM semua saldo termasuk yang negatif)
$total_saldo = get_total_saldo($koneksi);

// Data untuk grafik transaksi per bulan (12 bulan terakhir / 1 tahun)
$chart_labels = [];
$chart_data = [];
$chart_pendapatan = [];

for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_label = date('M Y', strtotime("-$i months"));
    $chart_labels[] = $month_label;

    // Hitung jumlah transaksi per bulan
    $transaksi_query = $koneksi->query("SELECT COUNT(*) as jumlah FROM transaksi WHERE DATE_FORMAT(tgl, '%Y-%m') = '$month'");
    $transaksi_data = $transaksi_query->fetch_assoc();
    $chart_data[] = (int)$transaksi_data['jumlah'];

    // Hitung total pendapatan per bulan (menggunakan CAST karena harga adalah varchar)
    $pendapatan_query = $koneksi->query("SELECT SUM(CAST(harga AS UNSIGNED)) as total FROM transaksi WHERE DATE_FORMAT(tgl, '%Y-%m') = '$month' AND status = 'Lunas'");
    $pendapatan_data = $pendapatan_query->fetch_assoc();
    $chart_pendapatan[] = (float)($pendapatan_data['total'] ?: 0);
}

// Data aktivitas terbaru
$recent_activities = get_recent_activities(10);
$total_activities = count($recent_activities);

// Total pendapatan bulan ini (menggunakan CAST karena harga adalah varchar)
$bulan_ini = date('Y-m');
$pendapatan_bulan_ini_query = $koneksi->query("SELECT SUM(CAST(harga AS UNSIGNED)) as total FROM transaksi WHERE DATE_FORMAT(tgl, '%Y-%m') = '$bulan_ini' AND status = 'Lunas'");
$pendapatan_bulan_ini = $pendapatan_bulan_ini_query->fetch_assoc();
$total_pendapatan_bulan_ini = $pendapatan_bulan_ini['total'] ?: 0;
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
        <title>Dashboard</title>
        <!-- Custom CSS -->
        <link href="<?=base_url()?>/files/assets/extra-libs/c3/c3.min.css" rel="stylesheet">
        <link href="<?=base_url()?>/files/assets/libs/chartist/dist/chartist.min.css" rel="stylesheet">
        <link href="<?=base_url()?>/files/assets/extra-libs/jvector/jquery-jvectormap-2.0.2.css" rel="stylesheet" />
        <!-- Custom CSS -->
        <link href="<?=base_url()?>/files/dist/css/style.min.css" rel="stylesheet">
        <style>
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }
        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }
        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }
        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }
        .text-xs {
            font-size: 0.7rem;
        }
        .text-gray-300 {
            color: #dddfeb !important;
        }
        .text-gray-800 {
            color: #5a5c69 !important;
        }
        .shadow {
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15) !important;
        }
        /* Styling untuk grafik - Perbaikan maksimal untuk mencegah overflow */
        #chart-transaksi,
        #chart-pendapatan {
            position: relative;
            overflow: hidden !important;
            width: 100% !important;
            max-width: 100% !important;
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        #chart-transaksi .c3,
        #chart-pendapatan .c3 {
            overflow: hidden !important;
            width: 100% !important;
            max-width: 100% !important;
        }
        #chart-transaksi svg,
        #chart-pendapatan svg {
            overflow: visible !important;
            max-width: 100% !important;
            width: 100% !important;
            height: auto !important;
            display: block;
            margin: 0;
            padding: 0;
        }
        .c3 svg {
            font-family: 'Arial', sans-serif;
            overflow: hidden !important;
        }
        .c3-axis-x .tick text,
        .c3-axis-y .tick text {
            font-size: 9px;
            fill: #5a5c69;
        }
        .c3-axis-x .tick text {
            text-anchor: end;
        }
        .c3-legend-item text {
            font-size: 10px;
            fill: #5a5c69;
        }
        .c3-tooltip {
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        .c3-bar {
            stroke-width: 0;
        }
        .c3-line {
            stroke-width: 2.5px;
        }
        .c3-circle {
            r: 3;
        }
        /* Pastikan container grafik tidak overflow */
        .card-body {
            overflow: hidden !important;
            position: relative;
            padding: 1rem;
        }
        .card {
            overflow: hidden !important;
        }
        /* Pastikan grafik container tidak melebihi parent */
        .c3-chart {
            overflow: hidden !important;
        }
        .c3-chart-arcs,
        .c3-chart-bars,
        .c3-chart-lines {
            overflow: hidden !important;
        }
        /* Pastikan semua elemen dalam container */
        .c3-axis-x {
            overflow: visible;
        }
        .c3-axis-x .domain {
            overflow: visible;
        }
        .c3-legend {
            overflow: visible;
        }
        /* Fix untuk card yang berisi grafik */
        .card.shadow .card-body {
            padding: 1rem;
        }
        /* Styling untuk timeline aktivitas */
        .activity-timeline-container {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e0 #f7fafc;
        }
        .activity-timeline-container::-webkit-scrollbar {
            width: 8px;
        }
        .activity-timeline-container::-webkit-scrollbar-track {
            background: #f7fafc;
            border-radius: 4px;
        }
        .activity-timeline-container::-webkit-scrollbar-thumb {
            background: #cbd5e0;
            border-radius: 4px;
        }
        .activity-timeline-container::-webkit-scrollbar-thumb:hover {
            background: #a0aec0;
        }
        .timeline {
            list-style: none;
            padding: 0;
            margin: 0;
            position: relative;
        }
        .timeline::before {
            content: '';
            position: absolute;
            left: 20px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }
        .timeline-item {
            position: relative;
            padding-left: 50px;
        }
        .timeline-marker {
            position: absolute;
            left: 8px;
            top: 8px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .timeline-marker i {
            font-size: 12px;
        }
        .timeline-content {
            position: relative;
        }
        .border-left-primary {
            border-left: 0.25rem solid #4e73df !important;
        }
        .border-left-success {
            border-left: 0.25rem solid #1cc88a !important;
        }
        .border-left-warning {
            border-left: 0.25rem solid #f6c23e !important;
        }
        .border-left-danger {
            border-left: 0.25rem solid #e74a3b !important;
        }
        .border-left-info {
            border-left: 0.25rem solid #36b9cc !important;
        }
        .bg-primary {
            background-color: #4e73df !important;
        }
        .bg-success {
            background-color: #1cc88a !important;
        }
        .bg-warning {
            background-color: #f6c23e !important;
        }
        .bg-danger {
            background-color: #e74a3b !important;
        }
        .bg-info {
            background-color: #36b9cc !important;
        }
        </style>
    </head>
    <body>
        <div class="container-fluid">
            <!-- Statistik -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="stat-card-modern">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fa fa-users" style="color: white !important; font-size: 28px !important;"></i>
                        </div>
                        <div class="stat-value"><?=$jumlah_pelanggan?></div>
                        <div class="stat-label">Total Pelanggan</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card-modern">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #1cc88a 0%, #17a673 100%);">
                            <i class="fa fa-dollar-sign" style="color: white !important; font-size: 28px !important;"></i>
                        </div>
                        <div class="stat-value"><?=$jumlah_jenisbayar?></div>
                        <div class="stat-label">Jenis Pembayaran</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card-modern">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #36b9cc 0%, #2c9faf 100%);">
                            <i class="fa fa-shopping-cart" style="color: white !important; font-size: 28px !important;"></i>
                        </div>
                        <div class="stat-value"><?=$jumlah_transaksi?></div>
                        <div class="stat-label">Total Transaksi</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card-modern" style="border-left: 4px solid <?=$total_saldo < 0 ? '#dc3545' : ($total_saldo < 100000 ? '#ffc107' : '#28a745')?>;">
                        <div class="stat-icon" style="background: linear-gradient(135deg, <?=$total_saldo < 0 ? '#dc3545' : ($total_saldo < 100000 ? '#ffc107' : '#28a745')?> 0%, <?=$total_saldo < 0 ? '#c82333' : ($total_saldo < 100000 ? '#dda20a' : '#17a673')?> 100%);">
                            <i class="fa fa-wallet" style="color: white !important; font-size: 28px !important;"></i>
                        </div>
                        <div class="stat-value" style="color: <?=$total_saldo < 0 ? '#dc3545' : ($total_saldo < 100000 ? '#ffc107' : '#28a745')?>; font-weight: 700;">
                            Rp <?=number_format($total_saldo, 0, ',', '.')?>
                        </div>
                        <div class="stat-label">Saldo Akhir</div>
                        <small class="<?=$total_saldo < 0 ? 'text-danger' : ($total_saldo < 100000 ? 'text-warning' : 'text-success')?>" style="font-weight: 600;">
                            <i class="fa fa-<?=$total_saldo < 0 ? 'exclamation-triangle' : ($total_saldo < 100000 ? 'exclamation-circle' : 'check-circle')?>"></i>
                            <?=$total_saldo < 0 ? 'Saldo Negatif!' : ($total_saldo < 100000 ? 'Saldo Rendah' : 'Saldo Aktif')?>
                        </small>
                    </div>
                </div>
            </div>
            <!-- Grafik Transaksi dan Pendapatan -->
            <div class="row mb-4">
                <div class="col-lg-8">
                    <div class="modern-card">
                        <div class="modern-card-header">
                            <h4>
                                <i class="fa fa-chart-bar"></i> Grafik Transaksi (1 Tahun Terakhir)
                            </h4>
                        </div>
                        <div class="modern-card-body">
                            <div id="chart-transaksi" style="height: 350px; overflow: hidden !important; width: 100% !important; max-width: 100% !important; position: relative; margin: 0; padding: 0;"></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="modern-card">
                        <div class="modern-card-header">
                            <h4>
                                <i class="fa fa-chart-line"></i> Pendapatan Bulan Ini
                            </h4>
                        </div>
                        <div class="modern-card-body">
                            <div class="text-center mb-3">
                                <h2 class="text-success font-weight-bold mb-0">Rp <?=number_format($total_pendapatan_bulan_ini, 0, ",", ".")?></h2>
                                <small class="text-muted">Total pendapatan bulan <?=date('F Y')?></small>
                            </div>
                            <div id="chart-pendapatan" style="height: 280px; overflow: hidden;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Aktivitas Terbaru -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="modern-card">
                        <div class="modern-card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h4>
                                    <i class="fa fa-history"></i> Aktivitas Terbaru
                                </h4>
                                <span class="badge badge-light badge-pill" style="font-size: 0.9rem;">
                                    <i class="fa fa-list"></i> <?=$total_activities?> Aktivitas
                                </span>
                            </div>
                        </div>
                        <div class="modern-card-body">
                            <div class="activity-timeline-container" style="max-height: 500px; overflow-y: auto; padding-right: 10px;">
                                <?php if (empty($recent_activities)): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> Belum ada aktivitas yang tercatat
                                </div>
                                <?php else: ?>
                                <ul class="timeline">
                                    <?php foreach ($recent_activities as $index => $activity):
                                        $icon = 'fa-circle';
                                        $color = 'primary';
                                        $badge_color = 'primary';

                                        switch($activity['action']) {
                                            case 'create':
                                            case 'tambah':
                                                $icon = 'fa-plus-circle';
                                                $color = 'success';
                                                $badge_color = 'success';
                                                break;
                                            case 'update':
                                            case 'edit':
                                                $icon = 'fa-edit';
                                                $color = 'warning';
                                                $badge_color = 'warning';
                                                break;
                                            case 'delete':
                                            case 'hapus':
                                                $icon = 'fa-trash';
                                                $color = 'danger';
                                                $badge_color = 'danger';
                                                break;
                                            case 'login':
                                                $icon = 'fa-sign-in-alt';
                                                $color = 'info';
                                                $badge_color = 'info';
                                                break;
                                            case 'backup':
                                            case 'restore':
                                                $icon = 'fa-database';
                                                $color = 'primary';
                                                $badge_color = 'primary';
                                                break;
                                        }

                                        $time_ago = '';
                                        $created = strtotime($activity['created_at']);
                                        $now = time();
                                        $diff = $now - $created;

                                        if ($diff < 60) {
                                            $time_ago = 'Baru saja';
                                        } elseif ($diff < 3600) {
                                            $time_ago = floor($diff / 60) . ' menit yang lalu';
                                        } elseif ($diff < 86400) {
                                            $time_ago = floor($diff / 3600) . ' jam yang lalu';
                                        } elseif ($diff < 604800) {
                                            $time_ago = floor($diff / 86400) . ' hari yang lalu';
                                        } else {
                                            $time_ago = date('d/m/Y H:i', $created);
                                        }
                                    ?>
                                    <li class="timeline-item mb-4">
                                        <div class="timeline-marker bg-<?=$badge_color?>">
                                            <i class="fa <?=$icon?> text-white"></i>
                                        </div>
                                        <div class="timeline-content">
                                            <div class="card border-left-<?=$badge_color?> shadow-sm">
                                                <div class="card-body p-3">
                                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                                        <h6 class="mb-0 font-weight-bold">
                                                            <?=htmlspecialchars($activity['nama_user'])?>
                                                            <span class="badge badge-<?=$badge_color?> ml-2"><?=ucfirst($activity['action'])?></span>
                                                        </h6>
                                                        <small class="text-muted">
                                                            <i class="fa fa-clock"></i> <?=$time_ago?>
                                                        </small>
                                                    </div>
                                                    <p class="mb-1 text-muted small">
                                                        <i class="fa fa-folder"></i> <strong><?=ucfirst($activity['module'])?></strong>
                                                    </p>
                                                    <p class="mb-0 small"><?=htmlspecialchars($activity['description'])?></p>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                    <?php endforeach; ?>
                                </ul>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Welcome Card -->
            <div class="row">
                <div class="col-lg-12">
                    <div class="modern-card">
                        <div class="modern-card-body" style="padding: 4rem 3rem;">
                            <div class="text-center">
                                <div class="logo-icon mb-4">
                                  <!--   <img src="<?=base_url()?>/files/assets/images/dwloket_logo.png" alt="logo" width="250px" height="70px"> -->
                                </div>
                                <h1 class="display-4 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; font-weight: 700; line-height: 1.2;"><b>DW LOKET JEPARA</b></h1>
                                <p class="lead mb-0" style="color: #5a5c69; font-size: 1.15rem; line-height: 1.9; max-width: 950px; margin: 0 auto; font-weight: 500;"><b>Loket Resmi Pembayaran PLN, Token PLN Pra Bayar, Pulsa Pra Bayar, Pulsa Pasca Bayar, Kuota Data, BPJS Kesehatan, PDAM, E-Money, Multifinance, Voucher Game, Dan Lain-lain.</b></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        include_once('../footer.php');
        ?>

        <!-- Chart Scripts -->
        <script src="<?=base_url()?>/files/assets/libs/chartist/dist/chartist.min.js"></script>
        <script src="<?=base_url()?>/files/assets/extra-libs/c3/d3.min.js"></script>
        <script src="<?=base_url()?>/files/assets/extra-libs/c3/c3.min.js"></script>

        <script>
        // Fungsi untuk mendapatkan lebar container yang tepat
        function getContainerWidth(selector) {
            var element = document.querySelector(selector);
            if (element) {
                var style = window.getComputedStyle(element);
                var paddingLeft = parseFloat(style.paddingLeft) || 0;
                var paddingRight = parseFloat(style.paddingRight) || 0;
                return element.clientWidth - paddingLeft - paddingRight;
            }
            return 0;
        }

        // Grafik Transaksi per Bulan
        var chartTransaksi;
        function initChartTransaksi() {
            var container = document.getElementById('chart-transaksi');
            if (!container) return;

            var containerWidth = getContainerWidth('#chart-transaksi');
            if (containerWidth <= 0) {
                setTimeout(initChartTransaksi, 100);
                return;
            }

            chartTransaksi = c3.generate({
                bindto: '#chart-transaksi',
                data: {
                    columns: [
                        ['Transaksi', <?=implode(',', $chart_data)?>]
                    ],
                    type: 'bar',
                    colors: {
                        'Transaksi': '#4e73df'
                    },
                    labels: false
                },
                axis: {
                    x: {
                        type: 'category',
                        categories: [<?=implode(',', array_map(function($label) { return "'" . $label . "'"; }, $chart_labels))?>],
                        tick: {
                            rotate: -45,
                            multiline: false,
                            culling: false
                        }
                    },
                    y: {
                        label: {
                            text: 'Jumlah Transaksi',
                            position: 'outer-middle'
                        },
                        tick: {
                            format: function (d) {
                                return d;
                            }
                        }
                    }
                },
                bar: {
                    width: {
                        ratio: 0.6
                    }
                },
                grid: {
                    y: {
                        show: true
                    }
                },
                tooltip: {
                    format: {
                        value: function (value) {
                            return value + ' transaksi';
                        }
                    }
                },
                padding: {
                    top: 10,
                    right: 5,
                    bottom: 70,
                    left: 50
                },
                legend: {
                    show: true,
                    position: 'inset',
                    inset: {
                        anchor: 'top-right',
                        x: 0,
                        y: 0
                    }
                },
                size: {
                    width: containerWidth
                },
                onresize: function() {
                    var newWidth = getContainerWidth('#chart-transaksi');
                    if (newWidth > 0 && this.api) {
                        this.api.resize({
                            width: newWidth
                        });
                    }
                },
                onrendered: function() {
                    var svg = d3.select('#chart-transaksi svg');
                    var containerWidth = getContainerWidth('#chart-transaksi');
                    if (svg.node() && containerWidth > 0) {
                        svg.attr('width', containerWidth);
                        svg.style('width', containerWidth + 'px');
                        svg.style('max-width', '100%');
                        svg.style('overflow', 'visible');
                    }
                }
            });
        }

        // Inisialisasi setelah DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initChartTransaksi);
        } else {
            setTimeout(initChartTransaksi, 100);
        }

        // Handle window resize
        var resizeTimer;
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (chartTransaksi && chartTransaksi.api) {
                    var newWidth = getContainerWidth('#chart-transaksi');
                    if (newWidth > 0) {
                        chartTransaksi.api.resize({
                            width: newWidth
                        });
                    }
                }
            }, 250);
        });

        // Grafik Pendapatan per Bulan
        var chartPendapatan;
        function initChartPendapatan() {
            var container = document.getElementById('chart-pendapatan');
            if (!container) return;

            var containerWidth = getContainerWidth('#chart-pendapatan');
            if (containerWidth <= 0) {
                setTimeout(initChartPendapatan, 100);
                return;
            }

            chartPendapatan = c3.generate({
                bindto: '#chart-pendapatan',
                data: {
                    columns: [
                        ['Pendapatan', <?=implode(',', $chart_pendapatan)?>]
                    ],
                    type: 'area',
                    colors: {
                        'Pendapatan': '#1cc88a'
                    },
                    labels: false
                },
                axis: {
                    x: {
                        type: 'category',
                        categories: [<?=implode(',', array_map(function($label) { return "'" . substr($label, 0, 3) . "'"; }, $chart_labels))?>],
                        tick: {
                            rotate: -45,
                            multiline: false,
                            culling: false
                        }
                    },
                    y: {
                        tick: {
                            format: function (d) {
                                if (d >= 1000000) {
                                    return (d / 1000000).toFixed(1) + 'M';
                                } else if (d >= 1000) {
                                    return (d / 1000).toFixed(1) + 'K';
                                }
                                return d;
                            }
                        }
                    }
                },
                grid: {
                    y: {
                        show: true
                    }
                },
                tooltip: {
                    format: {
                        value: function (value) {
                            return 'Rp ' + value.toLocaleString('id-ID');
                        }
                    }
                },
                point: {
                    show: true,
                    r: 3
                },
                area: {
                    zerobased: true
                },
                padding: {
                    top: 10,
                    right: 5,
                    bottom: 70,
                    left: 40
                },
                legend: {
                    show: false
                },
                size: {
                    width: containerWidth
                },
                onresize: function() {
                    var newWidth = getContainerWidth('#chart-pendapatan');
                    if (newWidth > 0 && this.api) {
                        this.api.resize({
                            width: newWidth
                        });
                    }
                },
                onrendered: function() {
                    var svg = d3.select('#chart-pendapatan svg');
                    var containerWidth = getContainerWidth('#chart-pendapatan');
                    if (svg.node() && containerWidth > 0) {
                        svg.attr('width', containerWidth);
                        svg.style('width', containerWidth + 'px');
                        svg.style('max-width', '100%');
                        svg.style('overflow', 'visible');
                    }
                }
            });
        }

        // Inisialisasi setelah DOM ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initChartPendapatan);
        } else {
            setTimeout(initChartPendapatan, 100);
        }

        // Handle window resize untuk grafik pendapatan
        window.addEventListener('resize', function() {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(function() {
                if (chartPendapatan && chartPendapatan.api) {
                    var newWidth = getContainerWidth('#chart-pendapatan');
                    if (newWidth > 0) {
                        chartPendapatan.api.resize({
                            width: newWidth
                        });
                    }
                }
            }, 250);
        });
        </script>
    </body>
</html>
