<?php
$page_title = 'Saldo';
include_once('../header.php');
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
    </head>
    <body>
        <div class="page-breadcrumb">
            <div class="row">
                <div class="col-7 align-self-center">
                    <h4 class="page-title text-truncate text-dark font-weight-medium mb-1">Saldo</h4>
                    <div class="d-flex align-items-center">
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb m-0 p-0">
                                <li class="breadcrumb-item"><a href="<?=base_url('home')?>" class="text-muted">Home</a></li>
                                <li class="breadcrumb-item text-muted active" aria-current="page">Saldo</li>
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
                                <i class="fa fa-credit-card"></i> Data Saldo
                            </h4>
                        </div>
                        <div class="modern-card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap">
                                <div class="mb-2 mb-md-0">
                                    <small class="text-muted" id="selectedCount">Terpilih: <strong>0</strong></small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <a href="<?=base_url('export_excel.php?page=saldo')?>" class="btn btn-success btn-sm mr-2">
                                        <i class="fa fa-file-excel"></i> Excel
                                    </a>
                                    <a href="<?=base_url('export_pdf.php?page=saldo')?>" target="_blank" class="btn btn-danger btn-sm mr-2">
                                        <i class="fa fa-file-pdf"></i> PDF
                                    </a>
                                    <button type="button" class="btn btn-sm btn-danger mr-2" id="btnHapusMultiple" disabled onclick="hapusMultiple()">
                                        <i class="fa fa-trash"></i> Hapus Terpilih
                                    </button>
                                    <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
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
                                            <th>Tanggal Deposit</th>
                                            <th>Jumlah Saldo</th>
                                            <th style="text-align: center;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $no = 1;
                                        // Hanya tampilkan saldo positif (saldo negatif tidak lagi dibuat, hanya untuk membersihkan data lama)
                                        $sql = $koneksi->query("SELECT * FROM tb_saldo WHERE CAST(saldo AS DECIMAL(15,2)) > 0 ORDER BY tgl DESC");
                                        while($data = $sql->fetch_assoc()) {
                                        $tgl = $data['tgl'];

                                        ?>
                                        <tr>
                                            <td>
                                                <input type="checkbox" class="checkbox-saldo" value="<?=$data['id_saldo']?>" data-tanggal="<?=date('d/m/Y', strtotime($tgl))?>" data-saldo="Rp. <?=number_format($data['saldo'], 0, ",", ".");?>">
                                            </td>
                                            <td><?=$no++.'.'?></td>
                                            <td><?=date('d/m/Y', strtotime($tgl));?></td>
                                            <td>Rp. <?=number_format($data['saldo'], 0, ",", ".");?></td>
                                            <td align="center">
                                                <a data-toggle="modal"
                                                    data-target="#modaledit<?=$data['id_saldo']; ?>"><button
                                                    class="btn btn-warning btn-sm"><i class="fa fa-edit"></i> Edit</button></a>
                                                <a href="hapus_saldo.php?id=<?=$data['id_saldo']; ?>"
                                                    onclick="return swalConfirmDelete(this.href, 'Yakin Hapus Saldo?', 'Data saldo ini akan dihapus secara permanen!')"
                                                    class="btn btn-danger btn-sm"><i class="fa fa-trash"></i> Hapus</a>
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
        include_once('../footer.php');
        ?>

        <script>
            // Fungsi untuk handle checkbox selection dengan dukungan paginasi DataTables
            $(document).ready(function() {
                // Set untuk menyimpan ID yang dipilih (persisten di semua paginasi)
                // Simpan di window scope agar bisa diakses dari fungsi lain
                window.selectedSaldoIds = window.selectedSaldoIds || new Set();
                const selectedIds = window.selectedSaldoIds;

                const table = $('#zero_config').DataTable();
                const btnHapusMultiple = $('#btnHapusMultiple');
                const selectedCount = $('#selectedCount');

                // Fungsi untuk update button state
                function updateButtonState() {
                    const count = selectedIds.size;
                    selectedCount.html('Terpilih: <strong>' + count + '</strong>');
                    btnHapusMultiple.prop('disabled', count === 0);
                }

                // Fungsi untuk update select all state
                function updateSelectAllState() {
                    const selectAll = $('#selectAll');
                    if (selectAll.length === 0) return;

                    // Hitung checkbox yang terlihat di halaman saat ini
                    const visibleCheckboxes = $('.checkbox-saldo:visible');
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
                    $('.checkbox-saldo').each(function() {
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
                    $('.checkbox-saldo:visible').each(function() {
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
                $(document).on('change', '.checkbox-saldo', function() {
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
                // Ambil selectedIds dari window scope
                const selectedIds = window.selectedSaldoIds || new Set();

                if (selectedIds.size === 0) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Tidak ada yang dipilih',
                            text: 'Silakan pilih saldo yang akan dihapus terlebih dahulu.'
                        });
                    } else {
                        alert('Silakan pilih saldo yang akan dihapus terlebih dahulu.');
                    }
                    return;
                }

                const ids = Array.from(selectedIds);
                // Ambil detail dari checkbox yang terlihat atau dari data attribute
                const details = [];
                $('.checkbox-saldo').each(function() {
                    if (selectedIds.has($(this).val())) {
                        const tanggal = $(this).data('tanggal') || '';
                        const saldo = $(this).data('saldo') || '';
                        details.push(tanggal + ' - ' + saldo);
                    }
                });

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Yakin Hapus?',
                        html: 'Anda akan menghapus <strong>' + ids.length + '</strong> saldo:<br><small>' + details.slice(0, 5).join('<br>') + (details.length > 5 ? '<br>...' : '') + '</small><br><br>Data yang dihapus tidak dapat dikembalikan!',
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
                            form.action = 'hapus_saldo_multiple.php';

                            ids.forEach(id => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'id_saldo[]';
                                input.value = id;
                                form.appendChild(input);
                            });

                            document.body.appendChild(form);
                            form.submit();
                        }
                    });
                } else {
                    if (confirm('Yakin ingin menghapus ' + ids.length + ' saldo yang dipilih?')) {
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = 'hapus_saldo_multiple.php';

                        ids.forEach(id => {
                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'id_saldo[]';
                            input.value = id;
                            form.appendChild(input);
                        });

                        document.body.appendChild(form);
                        form.submit();
                    }
                }
            }
        </script>

        <?php
        // Tampilkan pesan hapus multiple jika ada
        if (isset($_SESSION['hapus_message'])) {
            $hapus_message = $_SESSION['hapus_message'];
            $hapus_success = isset($_SESSION['hapus_success']) ? $_SESSION['hapus_success'] : false;
            unset($_SESSION['hapus_message']);
            unset($_SESSION['hapus_success']);
            ?>
            <script>
                $(document).ready(function() {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: '<?=$hapus_success ? 'success' : 'error'?>',
                            title: '<?=$hapus_success ? 'Berhasil' : 'Gagal'?>',
                            text: '<?=addslashes($hapus_message)?>',
                            timer: 3000,
                            timerProgressBar: true,
                            showConfirmButton: false
                        });
                    } else {
                        alert('<?=addslashes($hapus_message)?>');
                    }
                });
            </script>
            <?php
        }
        ?>
    </body>
</html>
<?php
include"modal_tambah.php";
include"modal_edit.php";
?>
