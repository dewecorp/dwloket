<?php
include_once('../header.php');
?>
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
                            <div class="mb-2 mb-md-0"></div>
                            <div class="d-flex align-items-center">
                                <a href="<?=base_url('export_excel.php?page=pelanggan')?>" class="btn btn-success btn-sm mr-2">
                                    <i class="fa fa-file-excel"></i> Excel
                                </a>
                                <a href="<?=base_url('export_pdf.php?page=pelanggan')?>" target="_blank" class="btn btn-danger btn-sm mr-2">
                                    <i class="fa fa-file-pdf"></i> PDF
                                </a>
                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal"
                                    data-target="#modaltambah"><i class="fa fa-plus"></i> Tambah</button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table id="zero_config" class="table modern-table no-wrap">
                                <thead>
                                    <tr>
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
                                        <td><?=$no++.'.'?></td>
                                        <td><?=$data['nama'];?></td>
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
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
});

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

    document.body.removeChild(textArea);
}
</script>
