<?php
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Online Shopping Portal</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>style.css">
    <style>
        body, html { height: 100%; margin: 0; }
        body { display: flex; flex-direction: column; }
        .admin-container-wrapper { flex: 1 0 auto; display: flex; } 
        .admin-login-page-wrapper { flex: 1 0 auto; display: flex; flex-direction: column; justify-content: center; }

        .admin-login-form {
            width: 100%;
            max-width: 400px;
            margin: 20px auto;
            padding: 30px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .admin-login-form h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        .admin-dashboard-stats {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }
        .stat-card {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.08);
            flex: 1;
            min-width: 200px;
            text-align: center;
        }
        .stat-card h4 {
            margin-top: 0;
            color: #2c3e50; 
        }
        .stat-card p {
            font-size: 2em;
            font-weight: bold;
            color: #0779e4; 
            margin-bottom: 0;
        }
        .admin-sidebar {
            width: 250px;
            background: #2c3e50;
            color: #ecf0f1;
            padding: 20px;
            min-height: 100%;
        }
        .admin-sidebar h2 { text-align: center; margin-bottom: 30px; color: #fff; }
        .admin-sidebar ul { list-style: none; padding: 0; }
        .admin-sidebar ul li a { display: block; padding: 12px 15px; color: #ecf0f1; text-decoration: none; border-radius: 4px; margin-bottom: 5px; transition: background-color 0.3s ease; }
        .admin-sidebar ul li a:hover,
        .admin-sidebar ul li a.active { background-color: #34495e; color: #fff; }
        .admin-main-content { flex-grow: 1; padding: 20px; background-color: #ecf0f1; }

        footer.admin-footer {
            text-align:center; 
            padding:15px; 
            background-color:#2c3e50; 
            color:#ecf0f1;
            flex-shrink: 0;
        }
        footer.admin-footer-login {
             background-color:#f4f4f4; color:#333; margin-top: auto;
        }
    </style>
</head>
<body>
    <?php if (is_admin_logged_in()): ?>
    <div class="admin-container-wrapper">
        <aside class="admin-sidebar">
            <h2>Admin Menu</h2>
            <ul>
                <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''); ?>">Dashboard</a></li>
                <li><a href="<?php echo BASE_URL; ?>admin/manage_products.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'manage_products.php' ? 'active' : ''); ?>">Products</a></li>
                <li><a href="<?php echo BASE_URL; ?>admin/manage_categories.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'manage_categories.php' ? 'active' : ''); ?>">Categories</a></li>
                <li><a href="<?php echo BASE_URL; ?>admin/manage_orders.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'manage_orders.php' ? 'active' : ''); ?>">Orders</a></li>
                <li><a href="<?php echo BASE_URL; ?>admin/logout.php">Logout (<?php echo htmlspecialchars($_SESSION['admin_username'] ?? 'Admin'); ?>)</a></li>
            </ul>
            <p style="text-align:center; margin-top:20px;"><a href="<?php echo BASE_URL; ?>" target="_blank" style="color:#fff; font-size:0.9em;">View Live Site &rarr;</a></p>
        </aside>
        <main class="admin-main-content">
            <?php
            echo get_admin_session_message();
            ?>
    <?php else: ?>
    <?php endif; ?>