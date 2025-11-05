<!-- Common Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../pages/js/notifications.js"></script>

<!-- Handle PHP Session Messages -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    <?php if(isset($_SESSION['success_msg'])): ?>
        showSuccess('<?php echo addslashes($_SESSION['success_msg']); ?>');
        <?php unset($_SESSION['success_msg']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_msg'])): ?>
        showError('<?php echo addslashes($_SESSION['error_msg']); ?>');
        <?php unset($_SESSION['error_msg']); ?>
    <?php endif; ?>
});
</script>