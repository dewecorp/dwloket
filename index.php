<?php
require_once "config/config.php";
if(isset($_SESSION['user'])) { ?>
<script>
window.location = "<?=base_url('home')?>";
</script>;
<?php
} else { ?>
<script>
window.location = "<?=base_url('auth/login.php')?>";
</script>;
<?php
}
?>