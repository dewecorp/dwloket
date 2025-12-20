<?php
include_once('../header.php');
include_once('../config/config.php');
require_once '../libs/orderkuota_api.php';
require_once '../libs/produk_helper.php';

// Get transaksi data dari ref_id atau id_transaksi
$ref_id = $_GET['ref_id'] ?? '';
$transaksi_id = $_GET['id'] ?? 0;
$transaksi = null;

// Cek apakah tabel produk ada
$produk_table_exists = false;
$check_produk_table = $koneksi->query("SHOW TABLES LIKE 'tb_produk_orderkuota'");
if ($check_produk_table && $check_produk_table->num_rows > 0) {
    $produk_table_exists = true;
}

if ($ref_id) {
    if ($produk_table_exists) {
        $check_column = $koneksi->query("SHOW COLUMNS FROM transaksi LIKE 'selected_produk_id'");
        $has_selected_produk_id = ($check_column && $check_column->num_rows > 0);

        if ($has_selected_produk_id) {
            $transaksi_query = $koneksi->query("SELECT t.*,
                                                       COALESCE(
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_produk = t.selected_produk_id AND p.status = 1 LIMIT 1),
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND CAST(p.harga AS UNSIGNED) = CAST(t.harga AS UNSIGNED) AND p.status = 1 LIMIT 1),
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND p.status = 1 LIMIT 1),
                                                           j.jenis_bayar,
                                                           '-'
                                                       ) as produk_nama
                                                FROM transaksi t
                                                LEFT JOIN tb_jenisbayar j ON t.id_bayar = j.id_bayar
                                                WHERE t.ket LIKE '%Ref: " . mysqli_real_escape_string($koneksi, $ref_id) . "%'
                                                ORDER BY t.id_transaksi DESC LIMIT 1");
        } else {
            $transaksi_query = $koneksi->query("SELECT t.*,
                                                       COALESCE(
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND CAST(p.harga AS UNSIGNED) = CAST(t.harga AS UNSIGNED) AND p.status = 1 LIMIT 1),
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND p.status = 1 LIMIT 1),
                                                           j.jenis_bayar,
                                                           '-'
                                                       ) as produk_nama
                                                FROM transaksi t
                                                LEFT JOIN tb_jenisbayar j ON t.id_bayar = j.id_bayar
                                                WHERE t.ket LIKE '%Ref: " . mysqli_real_escape_string($koneksi, $ref_id) . "%'
                                                ORDER BY t.id_transaksi DESC LIMIT 1");
        }
    } else {
        $transaksi_query = $koneksi->query("SELECT t.*, j.jenis_bayar
                                            FROM transaksi t
                                            LEFT JOIN tb_jenisbayar j ON t.id_bayar = j.id_bayar
                                            WHERE t.ket LIKE '%Ref: " . mysqli_real_escape_string($koneksi, $ref_id) . "%'
                                            ORDER BY t.id_transaksi DESC LIMIT 1");
    }
} elseif ($transaksi_id) {
    if ($produk_table_exists) {
        $check_column = $koneksi->query("SHOW COLUMNS FROM transaksi LIKE 'selected_produk_id'");
        $has_selected_produk_id = ($check_column && $check_column->num_rows > 0);

        if ($has_selected_produk_id) {
            $transaksi_query = $koneksi->query("SELECT t.*,
                                                       COALESCE(
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_produk = t.selected_produk_id AND p.status = 1 LIMIT 1),
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND CAST(p.harga AS UNSIGNED) = CAST(t.harga AS UNSIGNED) AND p.status = 1 LIMIT 1),
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND p.status = 1 LIMIT 1),
                                                           j.jenis_bayar,
                                                           '-'
                                                       ) as produk_nama
                                                FROM transaksi t
                                                LEFT JOIN tb_jenisbayar j ON t.id_bayar = j.id_bayar
                                                WHERE t.id_transaksi = " . (int)$transaksi_id);
        } else {
            $transaksi_query = $koneksi->query("SELECT t.*,
                                                       COALESCE(
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND CAST(p.harga AS UNSIGNED) = CAST(t.harga AS UNSIGNED) AND p.status = 1 LIMIT 1),
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND p.status = 1 LIMIT 1),
                                                           j.jenis_bayar,
                                                           '-'
                                                       ) as produk_nama
                                                FROM transaksi t
                                                LEFT JOIN tb_jenisbayar j ON t.id_bayar = j.id_bayar
                                                WHERE t.id_transaksi = " . (int)$transaksi_id);
        }
    } else {
        $transaksi_query = $koneksi->query("SELECT t.*, j.jenis_bayar
                                            FROM transaksi t
                                            LEFT JOIN tb_jenisbayar j ON t.id_bayar = j.id_bayar
                                            WHERE t.id_transaksi = " . (int)$transaksi_id);
    }
} else {
    // Jika tidak ada ref_id atau id, ambil transaksi terakhir
    if ($produk_table_exists) {
        $check_column = $koneksi->query("SHOW COLUMNS FROM transaksi LIKE 'selected_produk_id'");
        $has_selected_produk_id = ($check_column && $check_column->num_rows > 0);

        if ($has_selected_produk_id) {
            $transaksi_query = $koneksi->query("SELECT t.*,
                                                       COALESCE(
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_produk = t.selected_produk_id AND p.status = 1 LIMIT 1),
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND CAST(p.harga AS UNSIGNED) = CAST(t.harga AS UNSIGNED) AND p.status = 1 LIMIT 1),
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND p.status = 1 LIMIT 1),
                                                           j.jenis_bayar,
                                                           '-'
                                                       ) as produk_nama
                                                FROM transaksi t
                                                LEFT JOIN tb_jenisbayar j ON t.id_bayar = j.id_bayar
                                                WHERE t.ket LIKE '%OrderKuota%'
                                                ORDER BY t.id_transaksi DESC LIMIT 1");
        } else {
            $transaksi_query = $koneksi->query("SELECT t.*,
                                                       COALESCE(
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND CAST(p.harga AS UNSIGNED) = CAST(t.harga AS UNSIGNED) AND p.status = 1 LIMIT 1),
                                                           (SELECT p.produk FROM tb_produk_orderkuota p WHERE p.id_bayar = t.id_bayar AND p.status = 1 LIMIT 1),
                                                           j.jenis_bayar,
                                                           '-'
                                                       ) as produk_nama
                                                FROM transaksi t
                                                LEFT JOIN tb_jenisbayar j ON t.id_bayar = j.id_bayar
                                                WHERE t.ket LIKE '%OrderKuota%'
                                                ORDER BY t.id_transaksi DESC LIMIT 1");
        }
    } else {
        $transaksi_query = $koneksi->query("SELECT t.*, j.jenis_bayar
                                            FROM transaksi t
                                            LEFT JOIN tb_jenisbayar j ON t.id_bayar = j.id_bayar
                                            WHERE t.ket LIKE '%OrderKuota%'
                                            ORDER BY t.id_transaksi DESC LIMIT 1");
    }
}

if ($transaksi_query && $transaksi_query->num_rows > 0) {
    $transaksi = $transaksi_query->fetch_assoc();

    // Extract data dari keterangan
    preg_match('/Ref: ([A-Z0-9_]+)/', $transaksi['ket'], $matches);
    $transaksi['ref_id'] = $matches[1] ?? '';

    // Gunakan produk_nama dari query jika ada
    if (!empty($transaksi['produk_nama']) && $transaksi['produk_nama'] != '-') {
        $transaksi['product_name'] = $transaksi['produk_nama'];
    } else {
        preg_match('/OrderKuota: ([^-]+)/', $transaksi['ket'], $product_matches);
        $transaksi['product_name'] = trim($product_matches[1] ?? '');
    }

    // Extract token jika ada
    $token = '';
    if (preg_match('/Token:\s*([0-9]+)/i', $transaksi['ket'], $token_matches)) {
        $token = $token_matches[1];
    }
    $transaksi['token'] = $token;
}

if (!$transaksi) {
    die('Transaksi tidak ditemukan');
}

// Get company info (jika ada di config)
$company_name = 'DW LOKET JEPARA';
$company_address = '';
$company_phone = '';
$company_email = '';

// Cek apakah ada config untuk company info
if (file_exists(__DIR__ . '/../config/company_config.php')) {
    include __DIR__ . '/../config/company_config.php';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Struk - <?=$transaksi['ref_id']?></title>
    <style>
        /* Print Styles - Support untuk semua jenis printer */
        @media print {
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: 'Courier New', 'Monaco', 'Consolas', monospace;
                font-size: 12pt;
                line-height: 1.4;
                color: #000;
                background: #fff;
                width: 80mm; /* Standard thermal printer width */
                margin: 0 auto;
                padding: 5mm;
            }

            /* Hide non-printable elements */
            .no-print {
                display: none !important;
            }

            /* Receipt container */
            .receipt {
                width: 100%;
                max-width: 80mm;
                margin: 0 auto;
                padding: 0;
            }

            /* Header */
            .receipt-header {
                text-align: center;
                border-bottom: 1px dashed #000;
                padding-bottom: 10px;
                margin-bottom: 10px;
            }

            .company-name {
                font-size: 16pt;
                font-weight: bold;
                margin-bottom: 5px;
                text-transform: uppercase;
            }

            .company-info {
                font-size: 9pt;
                line-height: 1.3;
            }

            /* Receipt body */
            .receipt-body {
                margin: 10px 0;
            }

            .receipt-line {
                margin: 5px 0;
                font-size: 11pt;
            }

            .receipt-line.label {
                font-weight: bold;
            }

            .receipt-line.value {
                text-align: right;
            }

            .receipt-divider {
                border-top: 1px dashed #000;
                margin: 10px 0;
            }

            /* Token display untuk PLN */
            .token-box {
                border: 2px solid #000;
                padding: 10px;
                margin: 10px 0;
                text-align: center;
                background: #f0f0f0;
            }

            .token-label {
                font-size: 10pt;
                font-weight: bold;
                margin-bottom: 5px;
            }

            .token-code {
                font-size: 18pt;
                font-weight: bold;
                letter-spacing: 3px;
                font-family: 'Courier New', monospace;
            }

            /* Footer */
            .receipt-footer {
                text-align: center;
                margin-top: 15px;
                padding-top: 10px;
                border-top: 1px dashed #000;
                font-size: 9pt;
            }

            .receipt-footer .supported-by {
                margin-top: 10px;
                padding-top: 8px;
                border-top: 1px dashed #000;
                font-size: 9pt;
                font-style: italic;
            }

            /* Table untuk detail */
            .receipt-table {
                width: 100%;
                border-collapse: collapse;
                margin: 10px 0;
            }

            .receipt-table td {
                padding: 3px 0;
                font-size: 11pt;
            }

            .receipt-table td:first-child {
                width: 40%;
            }

            .receipt-table td:last-child {
                text-align: right;
            }

            /* Barcode area (jika perlu) */
            .barcode-area {
                text-align: center;
                margin: 10px 0;
            }
        }

        /* Screen styles (untuk preview) */
        @media screen {
            body {
                font-family: Arial, sans-serif;
                background: #f5f5f5;
                padding: 20px;
            }

            .receipt {
                background: #fff;
                width: 80mm;
                margin: 0 auto;
                padding: 20px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
            }

            .print-controls {
                text-align: center;
                margin-bottom: 20px;
            }

            .print-controls button {
                padding: 10px 20px;
                margin: 0 5px;
                font-size: 14px;
                cursor: pointer;
                border: none;
                border-radius: 5px;
            }

            .btn-print {
                background: #28a745;
                color: white;
            }

            .btn-close {
                background: #6c757d;
                color: white;
            }
        }

        /* Support untuk Dot Matrix Printer (lebar 80mm/132 kolom) */
        @media print and (width: 80mm) {
            body {
                width: 80mm;
            }
        }

        /* Support untuk Thermal Printer (58mm) */
        @media print and (width: 58mm) {
            body {
                width: 58mm;
                font-size: 10pt;
            }

            .company-name {
                font-size: 14pt;
            }

            .receipt-line {
                font-size: 10pt;
            }
        }

        /* Support untuk Inkjet/Laser Printer (A4) */
        @media print and (width: 210mm) {
            body {
                width: 210mm;
                max-width: 210mm;
                padding: 10mm;
            }

            .receipt {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <button class="btn-print" onclick="window.print()">
            <i class="fa fa-print"></i> Cetak
        </button>
        <button class="btn-close" onclick="window.close()">
            <i class="fa fa-times"></i> Tutup
        </button>
    </div>

    <div class="receipt">
        <!-- Header -->
        <div class="receipt-header">
            <div class="company-name"><?=htmlspecialchars($company_name)?></div>
            <?php if ($company_address): ?>
            <div class="company-info"><?=htmlspecialchars($company_address)?></div>
            <?php endif; ?>
            <?php if ($company_phone): ?>
            <div class="company-info">Telp: <?=htmlspecialchars($company_phone)?></div>
            <?php endif; ?>
            <?php if ($company_email): ?>
            <div class="company-info"><?=htmlspecialchars($company_email)?></div>
            <?php endif; ?>
        </div>

        <!-- Body -->
        <div class="receipt-body">
            <div class="receipt-divider"></div>

            <table class="receipt-table">
                <tr>
                    <td>No. Transaksi</td>
                    <td>#<?=$transaksi['id_transaksi']?></td>
                </tr>
                <tr>
                    <td>Tanggal</td>
                    <td><?=date('d/m/Y H:i:s', strtotime($transaksi['tgl']))?></td>
                </tr>
                <tr>
                    <td>Ref. ID</td>
                    <td><?=htmlspecialchars($transaksi['ref_id'] ?: 'N/A')?></td>
                </tr>
            </table>

            <div class="receipt-divider"></div>

            <table class="receipt-table">
                <tr>
                    <td>Produk</td>
                    <td><?=htmlspecialchars($transaksi['product_name'] ?: 'N/A')?></td>
                </tr>
                <tr>
                    <td>Nama</td>
                    <td><?=htmlspecialchars($transaksi['nama'] ?: '-')?></td>
                </tr>
                <tr>
                    <td>ID Pelanggan</td>
                    <td><?=htmlspecialchars($transaksi['idpel'])?></td>
                </tr>
                <tr>
                    <td>Produk</td>
                    <td><?=htmlspecialchars($transaksi['product_name'] ?? $transaksi['produk_nama'] ?? 'OrderKuota')?></td>
                </tr>
            </table>

            <div class="receipt-divider"></div>

            <!-- Token PLN jika ada -->
            <?php if ($transaksi['token']): ?>
            <div class="token-box">
                <div class="token-label">KODE TOKEN PLN</div>
                <div class="token-code"><?=htmlspecialchars($transaksi['token'])?></div>
            </div>
            <?php endif; ?>

            <table class="receipt-table">
                <tr>
                    <td><strong>Total</strong></td>
                    <td><strong>Rp <?=number_format($transaksi['harga'], 0, ',', '.')?></strong></td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td><?=$transaksi['status']?></td>
                </tr>
            </table>

            <div class="receipt-divider"></div>
        </div>

        <!-- Footer -->
        <div class="receipt-footer">
            <div>Terima kasih atas kepercayaan Anda</div>
            <div style="margin-top: 5px;">Struk ini adalah bukti pembayaran yang sah</div>
            <div style="margin-top: 10px; font-size: 8pt;">
                <?=date('d/m/Y H:i:s')?>
            </div>
            <div style="margin-top: 10px; padding-top: 8px; border-top: 1px dashed #000;">
                <div style="font-size: 9pt; font-style: italic;">
                    Supported by <strong>OrderKuota</strong>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto print jika parameter auto_print=1
        <?php if (isset($_GET['auto_print']) && $_GET['auto_print'] == '1'): ?>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        <?php endif; ?>

        // Handle setelah print
        window.onafterprint = function() {
            // Jika auto print, tutup window setelah print
            <?php if (isset($_GET['auto_print']) && $_GET['auto_print'] == '1'): ?>
            setTimeout(function() {
                window.close();
            }, 500);
            <?php endif; ?>
        };
    </script>
</body>
</html>





