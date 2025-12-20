<?php
require_once 'config/config.php';
require_once 'libs/export_helper.php';

// Get page identifier and filters
$page = $_GET['page'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$filter_jenis_bayar = $_GET['jenis_bayar'] ?? '';
$search = $_GET['search'] ?? '';

// Build where conditions based on page
$where_conditions = [];

switch ($page) {
    case 'transaksi':
        $where_conditions = ["1=1"];
        if ($filter_status) {
            $where_conditions[] = "transaksi.status = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'";
        }
        if ($filter_date_from) {
            $where_conditions[] = "transaksi.tgl >= '" . mysqli_real_escape_string($koneksi, $filter_date_from) . "'";
        }
        if ($filter_date_to) {
            $where_conditions[] = "transaksi.tgl <= '" . mysqli_real_escape_string($koneksi, $filter_date_to) . "'";
        }
        if ($filter_jenis_bayar) {
            $where_conditions[] = "transaksi.id_bayar = " . (int)$filter_jenis_bayar;
        }
        if ($search) {
            $search_escaped = mysqli_real_escape_string($koneksi, $search);
            $where_conditions[] = "(transaksi.nama LIKE '%$search_escaped%' OR transaksi.idpel LIKE '%$search_escaped%' OR transaksi.ket LIKE '%$search_escaped%')";
        }
        $where_clause = implode(' AND ', $where_conditions);

        $query = "SELECT transaksi.*, tb_jenisbayar.jenis_bayar
                  FROM transaksi
                  JOIN tb_jenisbayar ON transaksi.id_bayar = tb_jenisbayar.id_bayar
                  WHERE $where_clause
                  ORDER BY transaksi.tgl DESC, transaksi.id_transaksi DESC";

        $headers = ['No', 'ID Transaksi', 'Tanggal', 'ID Pelanggan', 'Nama Pelanggan', 'Jenis Pembayaran', 'Harga', 'Status', 'Keterangan'];
        $filename = 'transaksi';
        break;

    case 'pelanggan':
        $query = "SELECT * FROM pelanggan ORDER BY nama ASC";
        $headers = ['No', 'Nama Pelanggan', 'No ID/PEL'];
        $filename = 'pelanggan';
        break;

    case 'user':
        $query = "SELECT * FROM user ORDER BY username ASC";
        $headers = ['No', 'Username', 'Nama', 'Email', 'Level'];
        $filename = 'user';
        break;

    case 'saldo':
        $query = "SELECT * FROM tb_saldo ORDER BY tgl DESC";
        $headers = ['No', 'Tanggal Deposit', 'Jumlah Saldo'];
        $filename = 'saldo';
        break;

    case 'jenis_bayar':
        $query = "SELECT * FROM tb_jenisbayar ORDER BY jenis_bayar ASC";
        $headers = ['No', 'Jenis Pembayaran'];
        $filename = 'jenis_bayar';
        break;

    case 'orderkuota_history':
        $where_conditions = ["ket LIKE '%OrderKuota%'"];
        if ($filter_status) {
            $where_conditions[] = "status = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'";
        }
        if ($filter_date_from) {
            $where_conditions[] = "tgl >= '" . mysqli_real_escape_string($koneksi, $filter_date_from) . "'";
        }
        if ($filter_date_to) {
            $where_conditions[] = "tgl <= '" . mysqli_real_escape_string($koneksi, $filter_date_to) . "'";
        }
        if ($search) {
            $search_escaped = mysqli_real_escape_string($koneksi, $search);
            $where_conditions[] = "(nama LIKE '%$search_escaped%' OR idpel LIKE '%$search_escaped%' OR ket LIKE '%$search_escaped%')";
        }
        $where_clause = implode(' AND ', $where_conditions);

        $query = "SELECT * FROM transaksi WHERE $where_clause ORDER BY tgl DESC, id_transaksi DESC";
        $headers = ['No', 'Tanggal', 'ID Pelanggan', 'Nama', 'Jenis Pembayaran', 'Harga', 'Status', 'Reference ID', 'Keterangan'];
        $filename = 'orderkuota_history';
        break;

    case 'deposit_history':
        $where_conditions = ["1=1"];
        if ($filter_status) {
            $where_conditions[] = "status = '" . mysqli_real_escape_string($koneksi, $filter_status) . "'";
        }
        if ($filter_date_from) {
            $where_conditions[] = "created_at >= '" . mysqli_real_escape_string($koneksi, $filter_date_from) . "'";
        }
        if ($filter_date_to) {
            $where_conditions[] = "created_at <= '" . mysqli_real_escape_string($koneksi, $filter_date_to) . "'";
        }
        if ($search) {
            $search_escaped = mysqli_real_escape_string($koneksi, $search);
            $where_conditions[] = "(ref_id LIKE '%$search_escaped%' OR keterangan LIKE '%$search_escaped%')";
        }
        $where_clause = implode(' AND ', $where_conditions);

        $query = "SELECT * FROM orderkuota_deposit WHERE $where_clause ORDER BY created_at DESC";
        $headers = ['No', 'Tanggal', 'Jumlah', 'Metode Pembayaran', 'Status', 'Reference ID', 'Keterangan'];
        $filename = 'deposit_history';
        break;

    default:
        die('Invalid page parameter');
}

// Execute query
$result = $koneksi->query($query);
if (!$result) {
    die('Query error: ' . $koneksi->error);
}

// Prepare data
$data = [];
$no = 1;
while ($row = $result->fetch_assoc()) {
    $row_data = [];

    switch ($page) {
        case 'transaksi':
            $row_data = [
                $no++,
                $row['id_transaksi'],
                date('d/m/Y H:i', strtotime($row['tgl'])),
                $row['idpel'],
                $row['nama'],
                $row['jenis_bayar'],
                number_format($row['harga'], 0, ',', '.'),
                $row['status'],
                $row['ket']
            ];
            break;

        case 'pelanggan':
            $row_data = [
                $no++,
                $row['nama'],
                $row['no_idpel']
            ];
            break;

        case 'user':
            $row_data = [
                $no++,
                $row['username'],
                $row['nama'],
                $row['email'],
                $row['level']
            ];
            break;

        case 'saldo':
            $row_data = [
                $no++,
                date('d/m/Y', strtotime($row['tgl'])),
                number_format($row['saldo'], 0, ',', '.')
            ];
            break;

        case 'jenis_bayar':
            $row_data = [
                $no++,
                $row['jenis_bayar']
            ];
            break;

        case 'orderkuota_history':
            preg_match('/Ref: ([A-Z0-9_]+)/', $row['ket'], $matches);
            $ref_id = $matches[1] ?? '';
            $row_data = [
                $no++,
                date('d/m/Y H:i', strtotime($row['tgl'])),
                $row['idpel'],
                $row['nama'],
                $row['jenis_bayar'] ?? '-',
                number_format($row['harga'], 0, ',', '.'),
                $row['status'],
                $ref_id,
                $row['ket']
            ];
            break;

        case 'deposit_history':
            $row_data = [
                $no++,
                date('d/m/Y H:i', strtotime($row['tgl'])),
                number_format($row['amount'], 0, ',', '.'),
                $row['payment_method'],
                $row['status'],
                $row['ref_id'],
                $row['transaction_id'] ?? '',
                $row['keterangan']
            ];
            break;
    }

    $data[] = $row_data;
}

// Export to Excel
exportToExcel($headers, $data, $filename);





