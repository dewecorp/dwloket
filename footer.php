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
<!--This page JavaScript - Chart scripts hanya dimuat jika diperlukan -->
<script>
// Cek apakah ada elemen dashboard di halaman sebelum memuat chart scripts
(function() {
    var hasDashboardElements = document.getElementById('campaign-v2') ||
                                document.querySelector('.net-income') ||
                                document.getElementById('visitbylocate') ||
                                document.querySelector('.stats') ||
                                document.getElementById('chart-transaksi') ||
                                document.getElementById('chart-pendapatan');

    if (hasDashboardElements) {
        // Load chart scripts hanya jika elemen dashboard ada
        var scripts = [
            '<?=base_url()?>/files/assets/extra-libs/c3/d3.min.js',
            '<?=base_url()?>/files/assets/extra-libs/c3/c3.min.js',
            '<?=base_url()?>/files/assets/libs/chartist/dist/chartist.min.js',
            '<?=base_url()?>/files/assets/libs/chartist-plugin-tooltips/dist/chartist-plugin-tooltip.min.js',
            '<?=base_url()?>/files/assets/extra-libs/jvector/jquery-jvectormap-2.0.2.min.js',
            '<?=base_url()?>/files/assets/extra-libs/jvector/jquery-jvectormap-world-mill-en.js'
        ];

        var loadedCount = 0;
        scripts.forEach(function(src, index) {
            var script = document.createElement('script');
            script.src = src;
            script.async = false;
            script.onerror = function() {
                console.warn('Failed to load script: ' + src);
            };
            script.onload = function() {
                loadedCount++;
                // Load dashboard script setelah semua chart scripts dimuat
                if (loadedCount === scripts.length && typeof jQuery !== 'undefined' && typeof Chartist !== 'undefined') {
                    var dashboardScript = document.createElement('script');
                    dashboardScript.src = '<?=base_url()?>/files/dist/js/pages/dashboards/dashboard1.min.js';
                    dashboardScript.onerror = function() {
                        console.warn('Dashboard script failed to load');
                    };
                    document.body.appendChild(dashboardScript);
                }
            };
            document.body.appendChild(script);
        });
    }
})();
</script>
<script>
$(document).ready(function() {
    $('[data-toggle="tooltip"]').tooltip();
});
</script>
<script src="<?=base_url()?>/files/assets/extra-libs/datatables.net/js/jquery.dataTables.min.js"></script>
<script src="<?=base_url()?>/files/dist/js/pages/datatable/datatable-basic.init.js"></script>

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

// Fungsi update sistem dengan CSRF token
<?php
if (!isset($_SESSION['update_token'])) {
	$_SESSION['update_token'] = bin2hex(random_bytes(32));
}
?>
const UPDATE_CSRF_TOKEN = '<?=$_SESSION['update_token']?>';
function updateSistem() {
	Swal.fire({
		title: 'Update Sistem',
		text: 'Apakah Anda yakin ingin memperbarui sistem? Semua perubahan lokal akan ditimpa.',
		icon: 'question',
		showCancelButton: true,
		confirmButtonColor: '#28a745',
		cancelButtonColor: '#3085d6',
		confirmButtonText: 'Ya, Update!',
		cancelButtonText: 'Batal',
		reverseButtons: true
	}).then((result) => {
		if (!result.isConfirmed) return;
		Swal.fire({
			title: 'Memperbarui Sistem',
			text: 'Sedang mengunduh dan memasang pembaruan. Harap tunggu...',
			icon: 'info',
			allowOutsideClick: false,
			allowEscapeKey: false,
			showConfirmButton: false,
			didOpen: () => { Swal.showLoading(); }
		});
		$.ajax({
			url: '<?=base_url('update/update_sistem.php')?>',
			method: 'POST',
			data: { _token: UPDATE_CSRF_TOKEN },
			dataType: 'json',
			success: function(res) {
				if (res.success) {
					Swal.fire({ icon: 'success', title: 'Berhasil!', text: res.message, confirmButtonColor: '#28a745', timer: 5000, timerProgressBar: true });
				} else {
					Swal.fire({ icon: 'error', title: 'Gagal!', text: res.message, confirmButtonColor: '#dc3545' });
				}
			},
			error: function(xhr, status, error) {
				Swal.fire({ icon: 'error', title: 'Gagal!', text: 'Terjadi kesalahan: ' + (error || 'Tidak dapat terhubung ke server'), confirmButtonColor: '#dc3545' });
			}
		});
	});
	return false;
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
