<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once('../config/config.php');
require_once '../libs/log_activity.php';

$response = ['success' => false, 'message' => 'Unknown error'];

try {
    if (isset($_POST['transaksi']) && is_array($_POST['transaksi'])) {
        $updated_count = 0;
        $error_count = 0;
        $errors = [];

        foreach ($_POST['transaksi'] as $id_transaksi => $data) {
            $id_transaksi = intval($id_transaksi);
            $tgl = mysqli_real_escape_string($koneksi, $data['tgl'] ?? '');
            $idpel = mysqli_real_escape_string($koneksi, $data['idpel'] ?? '');
            $nama = mysqli_real_escape_string($koneksi, $data['nama'] ?? '');
            $id_bayar = intval($data['id_bayar'] ?? 0);
            $harga = floatval($data['harga'] ?? 0);
            $status = mysqli_real_escape_string($koneksi, $data['status'] ?? '');
            $ket = mysqli_real_escape_string($koneksi, $data['ket'] ?? '');

            if ($id_transaksi > 0 && !empty($tgl) && !empty($idpel) && !empty($nama)) {
                $id_bayar_sql = $id_bayar > 0 ? $id_bayar : 'NULL';
                $update_query = "UPDATE transaksi
                                SET tgl = '$tgl',
                                    idpel = '$idpel',
                                    nama = '$nama',
                                    id_bayar = $id_bayar_sql,
                                    harga = $harga,
                                    status = '$status',
                                    ket = '$ket'
                                WHERE id_transaksi = $id_transaksi";

                if ($koneksi->query($update_query)) {
                    $updated_count++;
                    @log_activity('update', 'transaksi', 'Mengedit transaksi ID: ' . $id_transaksi);
                } else {
                    $error_count++;
                    $errors[] = "ID $id_transaksi: " . $koneksi->error;
                }
            }
        }

        if ($updated_count > 0) {
            $msg = "Berhasil update $updated_count data.";
            if ($error_count > 0) $msg .= " Gagal $error_count data.";
            $response = ['success' => true, 'message' => $msg];
        } else {
            $response = ['success' => false, 'message' => 'Tidak ada data yang diupdate. ' . implode(", ", $errors)];
        }
    } else {
        $response = ['success' => false, 'message' => 'Data tidak ditemukan dalam request.'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
}

$output = ob_get_clean();
header('Content-Type: application/json');
echo json_encode($response);
exit;
?>
