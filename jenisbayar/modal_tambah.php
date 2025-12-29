<div id="modaltambah" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">
                    <i class="fa fa-plus-circle"></i> INPUT JENIS BAYAR
                </h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            </div>
            <form id="formTambahJenisBayar" method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="jenis">Jenis Pembayaran</label>
                        <input type="text" name="jenis" id="jenis" class="form-control" placeholder="Jenis Pembayaran" autofocus required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="simpan" class="btn btn-success btn-sm">Simpan</button>
                    <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal">Tutup</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Pastikan script dijalankan setelah DOM dan jQuery siap
(function() {
    function initFormTambahJenisBayar() {
        if (typeof jQuery === 'undefined') {
            setTimeout(initFormTambahJenisBayar, 100);
            return;
        }

        jQuery(document).ready(function($) {
            // Gunakan event delegation untuk memastikan handler terpasang
            $(document).on('submit', '#formTambahJenisBayar', function(e) {
        e.preventDefault(); // Mencegah form submit normal

        const form = $(this);
        const formData = form.serialize();
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();

        // Disable button dan ubah teks
        submitBtn.prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Menyimpan...');

        // Kirim data via AJAX
        $.ajax({
            url: '<?=base_url('jenisbayar/jenis_bayar.php')?>',
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            success: function(response) {
                // Reset button
                submitBtn.prop('disabled', false).html(originalText);

                if (response.success) {
                    // Tampilkan alert sukses
                    Swal.fire({
                        position: 'top-center',
                        icon: 'success',
                        title: 'Berhasil!',
                        text: response.message,
                        showConfirmButton: true,
                        confirmButtonColor: '#28a745',
                        timer: 3000,
                        timerProgressBar: true
                    }).then(function() {
                        // Reset form dan tutup modal
                        form[0].reset();
                        $('#modaltambah').modal('hide');

                        // Reload halaman untuk update tabel
                        window.location.reload();
                    });
                } else {
                    // Tampilkan alert error
                    Swal.fire({
                        position: 'top-center',
                        icon: 'error',
                        title: 'Gagal!',
                        text: response.message,
                        showConfirmButton: true,
                        confirmButtonColor: '#dc3545'
                    });
                }
            },
            error: function(xhr, status, error) {
                // Reset button
                submitBtn.prop('disabled', false).html(originalText);

                let errorMessage = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';

                // Coba parse response sebagai JSON jika ada
                if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.message) {
                            errorMessage = response.message;
                        }
                    } catch (e) {
                        // Bukan JSON, gunakan pesan default
                    }
                }

                // Tampilkan alert error
                Swal.fire({
                    position: 'top-center',
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage,
                    showConfirmButton: true,
                    confirmButtonColor: '#dc3545'
                });

                console.error('AJAX Error:', status, error, xhr.responseText);
            }
        });
        });
    }

    // Jalankan setelah DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFormTambahJenisBayar);
    } else {
        initFormTambahJenisBayar();
    }
})();
</script>
