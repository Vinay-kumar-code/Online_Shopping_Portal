<?php
require_once __DIR__ . '/../config.php';

unset($_SESSION['admin_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_role']);

$_SESSION['admin_message'] = "<div class='message-success'>You have been logged out successfully.</div>";
redirect('admin/index.php');
?>
