<?php
// Handle update produk SEBELUM include config untuk menghindari blank page
if (isset($_POST['update_produk'])) {
    // Bersihkan semua output buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    ob_start();

    include_once('../config/config.php');

    // Start session jika belum
    if (!isset($_SESSION)) {
        session_start();
    }
    $id_produk = intval($_POST['id_produk']);
    $kode = mysqli_real_escape_string($koneksi, $_POST['kode'] ?? '');
    $produk = mysqli_real_escape_string($koneksi, $_POST['produk'] ?? '');
    $kategori = mysqli_real_escape_string($koneksi, $_POST['kategori'] ?? '');
    $harga = intval($_POST['harga'] ?? 0);
    $status = intval($_POST['status'] ?? 1);

    // Ambil data lama sebelum update
    $old_data_query = "SELECT kode, produk, kategori, harga, status FROM tb_produk_orderkuota WHERE id_produk = $id_produk";
    $old_data_result = $koneksi->query($old_data_query);
    $old_data = $old_data_result ? $old_data_result->fetch_assoc() : null;

    $query = "UPDATE tb_produk_orderkuota
              SET produk = '$produk',
                  kategori = '$kategori',
                  harga = $harga,
                  status = $status,
                  updated_at = CURRENT_TIMESTAMP
              WHERE id_produk = $id_produk";

    $success = false;
    $message = '';
    $message_html = false;

    if ($koneksi->query($query)) {
        // Buat detail perubahan
        $changes = [];
        if ($old_data) {
            if ($old_data['produk'] != $produk) {
                $changes[] = "Produk: " . htmlspecialchars($old_data['produk']) . " → " . htmlspecialchars($produk);
            }
            if ($old_data['kategori'] != $kategori) {
                $changes[] = "Kategori: " . htmlspecialchars($old_data['kategori']) . " → " . htmlspecialchars($kategori);
            }
            if (intval($old_data['harga']) != $harga) {
                $old_harga_format = number_format(intval($old_data['harga']), 0, ',', '.');
                $new_harga_format = number_format($harga, 0, ',', '.');
                $changes[] = "Harga: Rp $old_harga_format → Rp $new_harga_format";
            }
            if ($old_data['status'] != $status) {
                $old_status = $old_data['status'] == 1 ? 'Aktif' : 'Tidak Aktif';
                $new_status = $status == 1 ? 'Aktif' : 'Tidak Aktif';
                $changes[] = "Status: $old_status → $new_status";
            }
        }

        if (!empty($changes)) {
            $message = "Produk <strong>" . htmlspecialchars($kode) . "</strong> berhasil diupdate:<br><ul style='text-align: left; display: inline-block; margin-top: 10px;'><li>" . implode("</li><li>", $changes) . "</li></ul>";
            $message_html = true;
        } else {
            $message = "Produk <strong>" . htmlspecialchars($kode) . "</strong> berhasil diupdate (tidak ada perubahan)";
            $message_html = true;
        }
        $success = true;
    } else {
        $message = 'Gagal mengupdate data produk: ' . $koneksi->error;
        $success = false;
    }

    ob_end_clean();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Update Produk</title>
        <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
    </head>
    <body>
    <script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: '<?=$success ? 'success' : 'error'?>',
            title: '<?=$success ? 'Berhasil!' : 'Gagal!'?>',
            <?php if ($message_html): ?>
            html: <?=json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)?>,
            <?php else: ?>
            text: <?=json_encode($message, JSON_UNESCAPED_UNICODE)?>,
            <?php endif; ?>
            confirmButtonColor: '<?=$success ? '#28a745' : '#dc3545'?>',
            width: '600px',
            <?php if ($success): ?>
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false
            <?php else: ?>
            confirmButtonText: 'OK'
            <?php endif; ?>
        }).then(() => {
            window.location.href = "<?=base_url('jenisbayar/jenis_bayar.php')?>";
        });
        <?php if ($success): ?>
        setTimeout(function() {
            window.location.href = "<?=base_url('jenisbayar/jenis_bayar.php')?>";
        }, 3500);
        <?php endif; ?>
    });
    </script>
    </body>
    </html>
    <?php
    exit;
}

// Jika tidak ada POST, redirect
while (ob_get_level()) {
    ob_end_clean();
}
header('Location: ' . base_url('jenisbayar/jenis_bayar.php'));
exit;
?>


