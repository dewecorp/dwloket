<?php
require_once "config/config.php";
// Perbaikan: Gunakan $_SESSION['level'] yang sesuai dengan login.php, bukan $_SESSION['user']
if(isset($_SESSION['level']) && !empty($_SESSION['level'])) { ?>
<script>
window.location = "<?=base_url('home')?>";
</script>
<?php
} else { ?>
<script>
window.location = "<?=base_url('auth/login.php')?>";
</script>
<?php
}
?>
