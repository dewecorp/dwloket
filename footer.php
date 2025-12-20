<!-- footer -->
<!-- ============================================================== -->
<footer class="footer text-center text-muted">
    All Rights Reserved by DW LOKET JEPARA @ <?=date('Y'); ?>
</footer>
<!-- ============================================================== -->
<!-- End footer -->
<!-- ============================================================== -->
</div>
</div>
<script src="<?=base_url()?>/files/assets/libs/popper.js/dist/umd/popper.min.js"></script>
<script src="<?=base_url()?>/files/assets/libs/bootstrap/dist/js/bootstrap.min.js"></script>
<script src="<?=base_url()?>/files/dist/js/app-style-switcher.js"></script>
<script src="<?=base_url()?>/files/dist/js/feather.min.js"></script>
<script src="<?=base_url()?>/files/assets/libs/perfect-scrollbar/dist/perfect-scrollbar.jquery.min.js"></script>
<script src="<?=base_url()?>/files/dist/js/sidebarmenu.js"></script>
<!--Custom JavaScript -->
<script src="<?=base_url()?>/files/dist/js/custom.min.js"></script>
<!--This page JavaScript - hanya di-load jika diperlukan -->
<?php
// Deteksi halaman dengan aman
$script_name = $_SERVER['SCRIPT_NAME'] ?? '';
$request_uri = $_SERVER['REQUEST_URI'] ?? '';
$is_dashboard = (strpos($script_name, 'home/index.php') !== false || strpos($request_uri, '/home') !== false || $request_uri == '/' || $request_uri == '/home' || $request_uri == '/home/');
$needs_datatable = (strpos($script_name, 'transaksi') !== false ||
                    strpos($script_name, 'pelanggan') !== false ||
                    strpos($script_name, 'user') !== false ||
                    strpos($script_name, 'jenisbayar') !== false ||
                    strpos($script_name, 'orderkuota') !== false ||
                    strpos($request_uri, 'transaksi') !== false ||
                    strpos($request_uri, 'pelanggan') !== false ||
                    strpos($request_uri, 'user') !== false ||
                    strpos($request_uri, 'jenisbayar') !== false ||
                    strpos($request_uri, 'orderkuota') !== false);

// Script chart hanya untuk dashboard
if ($is_dashboard): ?>
<script src="<?=base_url()?>/files/assets/extra-libs/c3/d3.min.js"></script>
<script src="<?=base_url()?>/files/assets/extra-libs/c3/c3.min.js"></script>
<script src="<?=base_url()?>/files/assets/libs/chartist/dist/chartist.min.js"></script>
<script src="<?=base_url()?>/files/assets/libs/chartist-plugin-tooltips/dist/chartist-plugin-tooltip.min.js"></script>
<script src="<?=base_url()?>/files/assets/extra-libs/jvector/jquery-jvectormap-2.0.2.min.js"></script>
<script src="<?=base_url()?>/files/assets/extra-libs/jvector/jquery-jvectormap-world-mill-en.js"></script>
<script src="<?=base_url()?>/files/dist/js/pages/dashboards/dashboard1.min.js"></script>
<?php endif; ?>

<?php if ($needs_datatable): ?>
<!-- DataTable scripts - hanya untuk halaman yang membutuhkan -->
<script src="<?=base_url()?>/files/assets/extra-libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="<?=base_url()?>/files/dist/js/pages/datatable/datatable-basic.init.js"></script>
<?php endif; ?>

<!-- <script src="<?=base_url()?>/files/dist/js/sweetalert2@11.js"></script> -->
<script src="<?=base_url()?>/files/dist/js/sweetalert2.all.min.js"></script>

<!-- SweetAlert Helper Functions -->
<script>
// Fungsi untuk confirm delete dengan SweetAlert
function swalConfirmDelete(url, title, text) {
    Swal.fire({
        title: title || 'Yakin Hapus?',
        text: text || 'Data yang dihapus tidak dapat dikembalikan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
    return false;
}

// Fungsi untuk show success message
function swalSuccess(message, title) {
    Swal.fire({
        icon: 'success',
        title: title || 'Berhasil!',
        text: message,
        confirmButtonColor: '#28a745',
        timer: 3000,
        timerProgressBar: true
    });
}

// Fungsi untuk show error message
function swalError(message, title) {
    Swal.fire({
        icon: 'error',
        title: title || 'Error!',
        text: message,
        confirmButtonColor: '#dc3545'
    });
}

// Fungsi untuk show warning message
function swalWarning(message, title) {
    Swal.fire({
        icon: 'warning',
        title: title || 'Peringatan!',
        text: message,
        confirmButtonColor: '#ffc107'
    });
}

// Fungsi untuk show info message
function swalInfo(message, title) {
    Swal.fire({
        icon: 'info',
        title: title || 'Info',
        text: message,
        confirmButtonColor: '#17a2b8'
    });
}

// Fungsi untuk confirm logout dengan SweetAlert
function confirmLogout() {
    Swal.fire({
        title: 'Yakin Logout?',
        text: 'Anda akan keluar dari sistem. Pastikan semua pekerjaan sudah disimpan!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Ya, Logout!',
        cancelButtonText: 'Batal',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = "<?=base_url('auth/logout.php')?>";
        }
    });
    return false;
}

</script>
</body>

</html>
