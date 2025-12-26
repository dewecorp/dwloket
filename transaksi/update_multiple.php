<?php
/**
 * Endpoint untuk update multiple transaksi via AJAX
 */
header('Content-Type: application/json');
include_once('../config/config.php');
require_once '../libs/log_activity.php';

// Cek apakah request POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['update_multiple'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

if (!isset($_POST['transaksi']) || !is_array($_POST['transaksi']) || empty($_POST['transaksi'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Tidak ada transaksi yang dipilih untuk diupdate'
    ]);
    exit;
}

$updated_count = 0;
$error_count = 0;
$errors = [];

require_once '../libs/saldo_helper.php';

foreach ($_POST['transaksi'] as $id_transaksi => $data) {
    $id_transaksi = intval($id_transaksi);
    $tgl = mysqli_real_escape_string($koneksi, $data['tgl'] ?? '');
    $idpel = mysqli_real_escape_string($koneksi, $data['idpel'] ?? '');
    $nama = mysqli_real_escape_string($koneksi, $data['nama'] ?? '');
    $id_bayar = intval($data['id_bayar'] ?? 0);
    $harga = floatval($data['harga'] ?? 0);
    $status = mysqli_real_escape_string($koneksi, $data['status'] ?? '');
    $ket = mysqli_real_escape_string($koneksi, $data['ket'] ?? '');

    if ($id_transaksi > 0 && !empty($tgl) && !empty($idpel) && !empty($nama) && $harga > 0) {
        // Ambil data transaksi lama sebelum update (untuk adjust saldo)
        $data_lama_query = $koneksi->query("SELECT status, harga FROM transaksi WHERE id_transaksi = $id_transaksi LIMIT 1");
        $status_lama = 'Belum';
        $harga_lama = 0;
        if ($data_lama_query && $data_lama_query->num_rows > 0) {
            $data_lama = $data_lama_query->fetch_assoc();
            $status_lama = $data_lama['status'] ?? 'Belum';
            $harga_lama = floatval($data_lama['harga'] ?? 0);
        }

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

            // Proses adjust saldo jika ada perubahan status atau harga
            $ket_saldo = 'Edit multiple transaksi: ' . $nama . ' (ID: ' . $idpel . ')';
            proses_saldo_edit_transaksi($koneksi, $id_transaksi, $status_lama, $status, $harga_lama, $harga, $ket_saldo);
        } else {
            $error_count++;
            $errors[] = "Gagal mengupdate transaksi ID $id_transaksi: " . $koneksi->error;
        }
    } else {
        $error_count++;
        $errors[] = "Data transaksi ID $id_transaksi tidak valid";
    }
}

if ($updated_count > 0) {
    $message = "Berhasil mengupdate $updated_count transaksi" . ($error_count > 0 ? " (gagal: $error_count)" : "");
    echo json_encode([
        'success' => true,
        'message' => $message,
        'updated_count' => $updated_count,
        'error_count' => $error_count
    ]);
} else {
    $message = "Gagal mengupdate transaksi. " . implode("; ", $errors);
    echo json_encode([
        'success' => false,
        'message' => $message,
        'errors' => $errors
    ]);
}
exit;
?>

