<?php
/**
 * SweetAlert Helper Functions
 * Helper untuk memudahkan penggunaan SweetAlert di seluruh aplikasi
 */

/**
 * Generate JavaScript untuk SweetAlert confirm delete
 */
function swalConfirmDelete($url, $title = 'Yakin Hapus?', $text = 'Data yang dihapus tidak dapat dikembalikan!') {
    return "swalConfirmDelete('" . addslashes($url) . "', '" . addslashes($title) . "', '" . addslashes($text) . "')";
}

/**
 * Generate JavaScript untuk SweetAlert success
 */
function swalSuccess($message, $title = 'Berhasil!') {
    return "Swal.fire({
        icon: 'success',
        title: '" . addslashes($title) . "',
        text: '" . addslashes($message) . "',
        confirmButtonColor: '#28a745',
        timer: 3000
    });";
}

/**
 * Generate JavaScript untuk SweetAlert error
 */
function swalError($message, $title = 'Error!') {
    return "Swal.fire({
        icon: 'error',
        title: '" . addslashes($title) . "',
        text: '" . addslashes($message) . "',
        confirmButtonColor: '#dc3545'
    });";
}

/**
 * Generate JavaScript untuk SweetAlert warning
 */
function swalWarning($message, $title = 'Peringatan!') {
    return "Swal.fire({
        icon: 'warning',
        title: '" . addslashes($title) . "',
        text: '" . addslashes($message) . "',
        confirmButtonColor: '#ffc107'
    });";
}

/**
 * Generate JavaScript untuk SweetAlert info
 */
function swalInfo($message, $title = 'Info') {
    return "Swal.fire({
        icon: 'info',
        title: '" . addslashes($title) . "',
        text: '" . addslashes($message) . "',
        confirmButtonColor: '#17a2b8'
    });";
}

?>





