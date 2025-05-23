<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Shopping Portal</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>style.css">
</head>
<body>
    <header>
        <div class="container">
            <div id="branding">
                <h1><a href="<?php echo BASE_URL; ?>index.php">Online Shopping Portal</a></h1>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>products.php">Products</a></li>
                    <li><a href="<?php echo BASE_URL; ?>cart.php">Cart
                        <?php
                        $cart_item_count = 0;
                        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                            foreach ($_SESSION['cart'] as $item) {
                                $cart_item_count += $item['quantity'];
                            }
                        }
                        if ($cart_item_count > 0) {
                            echo " <span class='cart-count'>($cart_item_count)</span>";
                        }
                        ?>
                    </a></li>
                    <?php if (is_user_logged_in()): ?>
                        <li><a href="<?php echo BASE_URL; ?>profile.php">My Profile</a></li>
                        <li><a href="<?php echo BASE_URL; ?>order_history.php">Order History</a></li>
                        <li><a href="<?php echo BASE_URL; ?>logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>login.php">Login</a></li>
                        <li><a href="<?php echo BASE_URL; ?>register.php">Register</a></li>
                    <?php endif; ?>
                     <li><a href="<?php echo BASE_URL; ?>admin/index.php">Admin</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <div class="container main-content">
        <?php
        echo get_session_message();
        ?>