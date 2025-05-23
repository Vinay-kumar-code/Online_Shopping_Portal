<?php
?>
    <?php if (is_admin_logged_in()): ?>
       
    <footer class="admin-footer">
        <p>&copy; <?php echo date("Y"); ?> Online Shopping Portal - Admin Panel</p>
    </footer>
    <?php else: ?>
    <footer class="admin-footer admin-footer-login">
        <p>&copy; <?php echo date("Y"); ?> Online Shopping Portal - Admin Login</p>
    </footer>
    <?php endif; ?>
</body>
</html>