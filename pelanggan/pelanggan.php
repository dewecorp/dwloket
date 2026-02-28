<?php
// Include config dulu untuk koneksi database
include_once('../config/config.php');

// Start session jika belum
if (!isset($_SESSION)) {
    session_start();
}

// Handle edit pelanggan tunggal - HARUS DIPROSES SEBELUM OUTPUT APAPUN
if(isset($_POST['edit']) && isset($koneksi)) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $nama = isset($_POST['nama']) ? mysqli_real_escape_string($koneksi, $_POST['nama']) : '';
    $idpel = isset($_POST['idpel']) ? mysqli_real_escape_string($koneksi, $_POST['idpel']) : '';

    if($id > 0 && !empty($nama) && !empty($idpel)) {
        $stmt_update = $koneksi->prepare("UPDATE pelanggan SET nama=?, no_idpel=? WHERE id_pelanggan=?");
        $stmt_update->bind_param("ssi", $nama, $idpel, $id);

        if($stmt_update->execute()) {
            // Log aktivitas
            require_once '../libs/log_activity.php';
            @log_activity('update', 'pelanggan', 'Mengedit pelanggan: ' . $nama);

            $_SESSION['edit_message'] = 'Pelanggan berhasil diedit';
            $_SESSION['edit_success'] = true;
        } else {
            $_SESSION['edit_message'] = 'Error: ' . $koneksi->error;
            $_SESSION['edit_success'] = false;
        }
        $stmt_update->close();
    } else {
        $_SESSION['edit_message'] = 'Data tidak valid. Nama dan ID/PEL harus diisi.';
        $_SESSION['edit_success'] = false;
    }

    header('Location: ' . base_url('pelanggan/pelanggan.php'));
    exit;
}

$page_title = 'Pelanggan';
include_once('../header.php');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pelanggan</title>
</head>

<body>
    <div class="page-breadcrumb">
        <div class="row">
            <div class="col-7 align-self-center">
                <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Pelanggan</h4>
                <div class="d-flex align-items-center">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb m-0 p-0">
                            <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                            <li class="breadcrumb-item text-muted active" aria-current="page">Pelanggan</li>
                        </ol>
                    </nav>
                </div>
            </div>
        </div>
    </div>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="modern-card">
                    <div class="modern-card-header">
                        <h4>
                            <i class="fa fa-users"></i> Data Pelanggan
                        </h4>
                    </div>
                    <div class="modern-card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                            <div class="mb-2 mb-md-0">
                                <?php
                                    $sql_count = $koneksi->query("SELECT COUNT(*) as total FROM pelanggan");
                                    $count_data = $sql_count->fetch_assoc();
                                    $total_pelanggan = $count_data['total'];
                                ?>
                                <small class="text-muted">Menampilkan: <strong><?=$total_pelanggan?></strong> pelanggan</small>
                                <small class="text-muted ml-2" id="selectedCount">| Terpilih: <strong>0</strong></small>
                            </div>
                            <div class="d-flex align-items-center flex-wrap">
                                <a href="<?=base_url('export_excel.php?page=pelanggan')?>" class="btn btn-success btn-sm mr-2 mb-2">
                                    <i class="fa fa-file-excel"></i> Excel
                                </a>
                                <a href="<?=base_url('export_pdf.php?page=pelanggan')?>" target="_blank" class="btn btn-danger btn-sm mr-2 mb-2">
                                    <i class="fa fa-file-pdf"></i> PDF
                                </a>
                                <button type="button" class="btn btn-danger btn-sm mr-2 mb-2" id="btnHapusMultiple" disabled onclick="hapusMultiple()">
                                    <i class="fa fa-trash"></i> Hapus Terpilih
                                </button>
                                <button type="button" class="btn btn-warning btn-sm mr-2 mb-2" id="btnEditMultiple" disabled onclick="editMultiple()">
                                    <i class="fa fa-edit"></i> Edit Terpilih
                                </button>
                                <button type="button" class="btn btn-primary btn-sm mb-2" data-toggle="modal"
                                    data-target="#modaltambah"><i class="fa fa-plus"></i> Tambah</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="zero_config" class="table modern-table no-wrap">
                                <thead>
                                    <tr>
                                        <th style="width: 30px;">
                                            <input type="checkbox" id="selectAll" title="Pilih Semua">
                                        </th>
                                        <th style="width: 5px;">No</th>
                                        <th>Nama Pelanggan</th>
                                        <th>No ID/PEL</th>
                                        <th style="text-align: center;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        $no = 1;
                                        $sql = $koneksi->query("SELECT * FROM pelanggan ORDER BY nama ASC");
                                        while($data = $sql->fetch_assoc()) {
                                        ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="checkbox-pelanggan" value="<?=$data['id_pelanggan']?>" data-nama="<?=htmlspecialchars($data['nama'], ENT_QUOTES)?>">
                                        </td>
                                        <td><?=$no++.'.'?></td>
                                        <td><?=htmlspecialchars($data['nama']);?></td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <code class="mr-2"><?=htmlspecialchars($data['no_idpel']);?></code>
                                                <button class="btn btn-xs btn-outline-primary"
                                                        onclick="copyIdPel('<?=htmlspecialchars($data['no_idpel'], ENT_QUOTES)?>', this)"
                                                        data-toggle="tooltip"
                                                        data-placement="top"
                                                        title="Copy ID/PEL untuk pembayaran manual"
                                                        id="copyBtn_<?=$data['id_pelanggan']?>">
                                                    <i class="fa fa-copy"></i>
                                                </button>
                                                <span class="copy-feedback ml-2" id="copyFeedback_<?=$data['id_pelanggan']?>" style="display: none; color: #28a745; font-size: 0.75rem; font-weight: 500;">
                                                    <i class="fa fa-check"></i> Sudah Disalin
                                                </span>
                                            </div>
                                        </td>

                                        <td align="center">
                                            <a data-toggle="modal"
                                                data-target="#modaledit<?=$data['id_pelanggan']; ?>"><button
                                                    class="btn btn-warning btn-sm"><i class="fa fa-edit"></i> Edit</button>
                                            </a>
                                            <a href="hapus.php?id=<?=$data['id_pelanggan']; ?>"
                                                onclick="return swalConfirmDelete(this.href, 'Yakin Hapus Pelanggan?', 'Data pelanggan ini akan dihapus secara permanen!')"
                                                class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Hapus
                                            </a>
                                        </td>
                                    </tr>
                                    <?php
                                        }?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    // Tampilkan pesan edit tunggal jika ada
    if (isset($_SESSION['edit_message'])) {
        $edit_message = $_SESSION['edit_message'];
        $edit_success = isset($_SESSION['edit_success']) ? $_SESSION['edit_success'] : false;
        unset($_SESSION['edit_message']);
        unset($_SESSION['edit_success']);
        ?>
        <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: '<?=$edit_success ? 'success' : 'error'?>',
                        title: '<?=$edit_success ? 'Berhasil!' : 'Gagal!'?>',
                        text: <?=json_encode($edit_message, JSON_UNESCAPED_UNICODE)?>,
                        confirmButtonColor: '<?=$edit_success ? '#28a745' : '#dc3545'?>',
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                } else {
                    alert(<?=json_encode($edit_message, JSON_UNESCAPED_UNICODE)?>);
                }
            }, 100);
        });
        </script>
        <?php
    }

    // Tampilkan pesan hapus multiple jika ada
    if (isset($_SESSION['hapus_message'])) {
        $hapus_message = $_SESSION['hapus_message'];
        $hapus_success = isset($_SESSION['hapus_success']) ? $_SESSION['hapus_success'] : false;
        unset($_SESSION['hapus_message']);
        unset($_SESSION['hapus_success']);
        ?>
        <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: '<?=$hapus_success ? 'success' : 'error'?>',
                        title: '<?=$hapus_success ? 'Berhasil!' : 'Gagal!'?>',
                        text: <?=json_encode($hapus_message, JSON_UNESCAPED_UNICODE)?>,
                        confirmButtonColor: '<?=$hapus_success ? '#28a745' : '#dc3545'?>',
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                } else {
                    alert(<?=json_encode($hapus_message, JSON_UNESCAPED_UNICODE)?>);
                }
            }, 100);
        });
        </script>
        <?php
    }

    // Tampilkan pesan update multiple jika ada
    if (isset($_SESSION['update_message'])) {
        $update_message = $_SESSION['update_message'];
        $update_success = isset($_SESSION['update_success']) ? $_SESSION['update_success'] : false;
        unset($_SESSION['update_message']);
        unset($_SESSION['update_success']);
        ?>
        <script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(function() {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: '<?=$update_success ? 'success' : 'error'?>',
                        title: '<?=$update_success ? 'Berhasil!' : 'Gagal!'?>',
                        text: <?=json_encode($update_message, JSON_UNESCAPED_UNICODE)?>,
                        confirmButtonColor: '<?=$update_success ? '#28a745' : '#dc3545'?>',
                        timer: 3000,
                        timerProgressBar: true,
                        showConfirmButton: false
                    });
                } else {
                    alert(<?=json_encode($update_message, JSON_UNESCAPED_UNICODE)?>);
                }
            }, 100);
        });
        </script>
        <?php
    }
    ?>

    <script>
        // Function to copy ID/PEL to clipboard
        function copyIdPel(idpel, buttonElement) {
            // Get feedback element
            var buttonId = buttonElement ? buttonElement.id : '';
            var pelangganId = buttonId ? buttonId.replace('copyBtn_', '') : '';
            var feedbackElement = pelangganId ? document.getElementById('copyFeedback_' + pelangganId) : null;

            // Copy function
            function doCopy() {
                // Try modern clipboard API first
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(idpel).then(function() {
                        showCopySuccess(buttonElement, feedbackElement);
                    }).catch(function(err) {
                        console.error('Clipboard API failed:', err);
                        // Fallback to old method
                        fallbackCopy(idpel, buttonElement, feedbackElement);
                    });
                } else {
                    // Fallback for older browsers
                    fallbackCopy(idpel, buttonElement, feedbackElement);
                }
            }

            // Execute copy
            doCopy();
        }

        // Show copy success feedback
        function showCopySuccess(buttonElement, feedbackElement) {
            if (!buttonElement) return;

            // Save original tooltip title before removing
            var originalTitle = buttonElement.getAttribute('title') || buttonElement.getAttribute('data-original-title') || 'Copy ID/PEL untuk pembayaran manual';

            // Disable/hide tooltip saat copy berhasil
            if (typeof $ !== 'undefined' && $(buttonElement).data('bs.tooltip')) {
                $(buttonElement).tooltip('hide');
                $(buttonElement).tooltip('disable');
            }

            // Remove tooltip attributes to prevent tooltip from showing
            buttonElement.removeAttribute('data-toggle');
            buttonElement.removeAttribute('title');
            buttonElement.removeAttribute('data-placement');
            buttonElement.setAttribute('data-tooltip-disabled', 'true');

            // Change button appearance
            buttonElement.classList.remove('btn-outline-primary');
            buttonElement.classList.add('btn-success');
            var originalHTML = buttonElement.innerHTML;
            buttonElement.innerHTML = '<i class="fa fa-check"></i>';

            // Show feedback text
            if (feedbackElement) {
                feedbackElement.style.display = 'inline';
            }

            // Reset button after 2 seconds
            setTimeout(function() {
                if (buttonElement) {
                    buttonElement.classList.remove('btn-success');
                    buttonElement.classList.add('btn-outline-primary');
                    buttonElement.innerHTML = originalHTML;

                    // Restore tooltip attributes
                    buttonElement.setAttribute('data-toggle', 'tooltip');
                    buttonElement.setAttribute('data-placement', 'top');
                    buttonElement.setAttribute('title', originalTitle);
                    buttonElement.removeAttribute('data-tooltip-disabled');

                    // Re-initialize tooltip
                    if (typeof $ !== 'undefined') {
                        $(buttonElement).tooltip('dispose'); // Remove old tooltip instance
                        $(buttonElement).tooltip(); // Re-initialize tooltip
                    }
                }
                if (feedbackElement) {
                    feedbackElement.style.display = 'none';
                }
            }, 2000);
        }

        // Fallback copy method for older browsers
        function fallbackCopy(idpel, buttonElement, feedbackElement) {
            const textArea = document.createElement('textarea');
            textArea.value = idpel;
            textArea.style.position = 'fixed';
            textArea.style.top = '0';
            textArea.style.left = '0';
            textArea.style.width = '2em';
            textArea.style.height = '2em';
            textArea.style.padding = '0';
            textArea.style.border = 'none';
            textArea.style.outline = 'none';
            textArea.style.boxShadow = 'none';
            textArea.style.background = 'transparent';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();

            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    showCopySuccess(buttonElement, feedbackElement);
                } else {
                    // Tampilkan SweetAlert jika gagal
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: 'Gagal menyalin. Silakan salin manual: ' + idpel,
                            confirmButtonColor: '#dc3545',
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert('Gagal menyalin. Silakan salin manual: ' + idpel);
                    }
                }
            } catch (err) {
                console.error('Copy failed:', err);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: 'Gagal menyalin. Silakan salin manual: ' + idpel,
                        confirmButtonColor: '#dc3545',
                        confirmButtonText: 'OK'
                    });
                } else {
                    alert('Gagal menyalin. Silakan salin manual: ' + idpel);
                }
            }
            document.body.removeChild(textArea);
        }

        // Fungsi untuk handle checkbox selection dengan dukungan paginasi DataTables
        $(document).ready(function() {
            // Set untuk menyimpan ID yang dipilih (persisten di semua paginasi)
            // Simpan di window scope agar bisa diakses dari fungsi lain
            window.selectedPelangganIds = window.selectedPelangganIds || new Set();
            const selectedIds = window.selectedPelangganIds;

            const table = $('#zero_config').DataTable();
            const btnHapusMultiple = $('#btnHapusMultiple');
            const btnEditMultiple = $('#btnEditMultiple');
            const selectedCount = $('#selectedCount');

            // Fungsi untuk update button state
            function updateButtonState() {
                const count = selectedIds.size;
                selectedCount.html('| Terpilih: <strong>' + count + '</strong>');
                btnHapusMultiple.prop('disabled', count === 0);
                btnEditMultiple.prop('disabled', count === 0);
            }

            // Fungsi untuk update select all state
            function updateSelectAllState() {
                const selectAll = $('#selectAll');
                if (selectAll.length === 0) return;

                // Hitung checkbox yang terlihat di halaman saat ini
                const visibleCheckboxes = $('.checkbox-pelanggan:visible');
                const visibleChecked = visibleCheckboxes.filter(function() {
                    return selectedIds.has($(this).val());
                }).length;
                const allVisibleChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.length === visibleChecked;
                const someVisibleChecked = visibleChecked > 0;

                selectAll.prop('checked', allVisibleChecked);
                selectAll.prop('indeterminate', someVisibleChecked && !allVisibleChecked);
            }

            // Restore checkbox states saat DataTables draw (setelah pagination/search)
            table.on('draw', function() {
                // Restore checkbox states dari Set
                $('.checkbox-pelanggan').each(function() {
                    const id = $(this).val();
                    $(this).prop('checked', selectedIds.has(id));
                });
                updateSelectAllState();
                updateButtonState();
            });

            // Select All checkbox
            $(document).on('change', '#selectAll', function() {
                const isChecked = $(this).prop('checked');
                // Update semua checkbox yang terlihat di halaman saat ini
                $('.checkbox-pelanggan:visible').each(function() {
                    const id = $(this).val();
                    $(this).prop('checked', isChecked);
                    if (isChecked) {
                        selectedIds.add(id);
                    } else {
                        selectedIds.delete(id);
                    }
                });
                updateButtonState();
            });

            // Individual checkbox
            $(document).on('change', '.checkbox-pelanggan', function() {
                const id = $(this).val();
                if ($(this).prop('checked')) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
                updateSelectAllState();
                updateButtonState();
            });

            // Initialize
            updateButtonState();
        });

        // Fungsi untuk hapus multiple (menggunakan Set yang persisten)
        function hapusMultiple() {
            // Ambil selectedIds dari closure atau window scope
            const selectedIds = window.selectedPelangganIds || new Set();

            if (selectedIds.size === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Tidak ada yang dipilih',
                        text: 'Silakan pilih pelanggan yang akan dihapus terlebih dahulu.'
                    });
                } else {
                    alert('Silakan pilih pelanggan yang akan dihapus terlebih dahulu.');
                }
                return;
            }

            const ids = Array.from(selectedIds);
            // Ambil nama dari checkbox yang terlihat atau dari data attribute
            const namas = [];
            $('.checkbox-pelanggan').each(function() {
                if (selectedIds.has($(this).val())) {
                    namas.push($(this).data('nama') || 'Pelanggan');
                }
            });

            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Yakin Hapus?',
                    html: 'Anda akan menghapus <strong>' + ids.length + '</strong> pelanggan:<br><small>' + namas.slice(0, 5).join(', ') + (namas.length > 5 ? '...' : '') + '</small><br><br>Data yang dihapus tidak dapat dikembalikan!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus Semua!',
                    cancelButtonText: 'Batal',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Submit form untuk hapus multiple
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'hapus_pelanggan_multiple.php';

                        ids.forEach(id => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'id_pelanggan[]';
                            input.value = id;
                            form.appendChild(input);
                        });

                        document.body.appendChild(form);
                        form.submit();
                    }
                });
            } else {
                if (confirm('Anda akan menghapus ' + ids.length + ' pelanggan. Lanjutkan?')) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = 'hapus_pelanggan_multiple.php';

                    ids.forEach(id => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'id_pelanggan[]';
                        input.value = id;
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                }
            }
        }

        // Fungsi untuk edit multiple (menggunakan Set yang persisten)
        function editMultiple() {
            // Ambil selectedIds dari closure atau window scope
            const selectedIds = window.selectedPelangganIds || new Set();

            if (selectedIds.size === 0) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Tidak ada yang dipilih',
                        text: 'Silakan pilih pelanggan yang akan diedit terlebih dahulu.'
                    });
                } else {
                    alert('Silakan pilih pelanggan yang akan diedit terlebih dahulu.');
                }
                return;
            }

            const ids = Array.from(selectedIds);
            window.location.href = 'edit_pelanggan_multiple.php?ids=' + ids.join(',');
        }
    </script>
    <?php
        include_once('../footer.php');
        ?>
</body>

</html>
<?php
include"modal_tambah.php";
include"modal_edit.php";
?>
<script type="text/javascript">
$(document).ready(function() {
    $('.btn-copy').on("click", function() {
        $("#text-copy").select();
        document.execCommand("copy");
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: 'Telah Disalin...',
            timer: 1500,
            timerProgressBar: true,
            showConfirmButton: false,
            toast: true,
            position: 'top-end'
        });
    })
})
</script>
