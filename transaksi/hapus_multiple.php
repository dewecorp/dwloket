<?php
include_once('../config/config.php');
require_once '../libs/log_activity.php';

// Start session jika belum
if (!isset($_SESSION)) {
    session_start();
}

// Cek apakah request POST dengan ids
if (!isset($_POST['ids']) || !is_array($_POST['ids']) || empty($_POST['ids'])) {
    echo '<script>
        alert("Tidak ada transaksi yang dipilih untuk dihapus");
        window.location.href = "' . base_url('transaksi/transaksi.php') . '";
    </script>';
    exit;
}

$ids = array_map('intval', $_POST['ids']);
$ids = array_filter($ids, function($id) {
    return $id > 0;
});

if (empty($ids)) {
    echo '<script>
        alert("ID transaksi tidak valid");
        window.location.href = "' . base_url('transaksi/transaksi.php') . '";
    </script>';
    exit;
}

$ids_str = implode(',', $ids);
$deleted_count = 0;
$error_count = 0;
$errors = [];

// Hapus setiap transaksi
foreach ($ids as $id_transaksi) {
    // Ambil data transaksi sebelum dihapus untuk log
    $query_select = "SELECT * FROM transaksi WHERE id_transaksi = $id_transaksi";
    $result_select = $koneksi->query($query_select);
    $transaksi_data = $result_select ? $result_select->fetch_assoc() : null;

    // Hapus transaksi
    $query_delete = "DELETE FROM transaksi WHERE id_transaksi = $id_transaksi";

    if ($koneksi->query($query_delete)) {
        $deleted_count++;

        // Log aktivitas
        if ($transaksi_data) {
            @log_activity('delete', 'transaksi', 'Menghapus transaksi ID: ' . $id_transaksi . ' - ' . $transaksi_data['nama'] . ' (ID: ' . $transaksi_data['idpel'] . ')');
        }
    } else {
        $error_count++;
        $errors[] = "Gagal menghapus transaksi ID $id_transaksi: " . $koneksi->error;
    }
}

// Set session message
if ($deleted_count > 0) {
    $message = "Berhasil menghapus $deleted_count transaksi" . ($error_count > 0 ? " (gagal: $error_count)" : "");
    $_SESSION['delete_message'] = $message;
    $_SESSION['delete_success'] = true;
} else {
    $message = "Gagal menghapus transaksi. " . implode("; ", $errors);
    $_SESSION['delete_message'] = $message;
    $_SESSION['delete_success'] = false;
}

// Redirect dengan SweetAlert
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Hapus Multiple Transaksi</title>
    </head>
    <body>
        <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: '<?=$deleted_count > 0 ? 'success' : 'error'?>',
                        title: '<?=$deleted_count > 0 ? 'Berhasil!' : 'Gagal!'?>',
                        text: '<?=addslashes($message)?>',
                        confirmButtonColor: '<?=$deleted_count > 0 ? '#28a745' : '#dc3545'?>',
                        confirmButtonText: 'OK'
                    }).then((result) => {
                        window.location.href = "<?=base_url('transaksi/transaksi.php')?>";
                    });

                    // Fallback redirect
                    setTimeout(function() {
                        window.location.href = "<?=base_url('transaksi/transaksi.php')?>";
                    }, 2500);
                } else {
                    alert('<?=addslashes($message)?>');
                    window.location.href = "<?=base_url('transaksi/transaksi.php')?>";
                }
            }, 100);
        });
        </script>
    </body>
</html>
<?php
exit;
?>

