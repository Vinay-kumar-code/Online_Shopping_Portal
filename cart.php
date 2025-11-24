<?php
require_once 'header.php';

$cart_items = isset($_SESSION['cart']) ? $_SESSION['cart'] : array();
$grand_total = 0;
?>

<h2>Your Shopping Cart</h2>

<?php if (empty($cart_items)): ?>
    <p class="message-info">Your shopping cart is empty. <a href="products.php">Continue shopping!</a></p>
<?php else: ?>
    <form action="cart_actions.php" method="POST">
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Subtotal</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                // Fetch all product info in a single query to avoid N+1 problem
                $product_ids = array_keys($cart_items);
                $product_info_map = array();
                
                if (!empty($product_ids)) {
                    $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
                    $product_info_sql = "SELECT ProductID, MainImageURL, StockQuantity FROM products WHERE ProductID IN ($placeholders)";
                    $stmt_info = mysqli_prepare($conn, $product_info_sql);
                    
                    if ($stmt_info) {
                        $types = str_repeat('i', count($product_ids));
                        mysqli_stmt_bind_param($stmt_info, $types, ...$product_ids);
                        mysqli_stmt_execute($stmt_info);
                        $info_result = mysqli_stmt_get_result($stmt_info);
                        
                        while ($prod_info = mysqli_fetch_assoc($info_result)) {
                            $product_info_map[$prod_info['ProductID']] = $prod_info;
                        }
                        mysqli_stmt_close($stmt_info);
                    }
                }
                
                foreach ($cart_items as $item):
                    $product_image_url = 'default_product.png';
                    $current_stock = 0;
                    
                    if (isset($product_info_map[$item['product_id']])) {
                        $product_image_url = $product_info_map[$item['product_id']]['MainImageURL'];
                        $current_stock = $product_info_map[$item['product_id']]['StockQuantity'];
                    }

                    $subtotal = $item['price'] * $item['quantity'];
                    $grand_total += $subtotal;
                    ?>
                    <tr>
                        <td>
                            <a href="product_detail.php?id=<?php echo $item['product_id']; ?>">
                                <img src="<?php echo UPLOAD_URL . htmlspecialchars($product_image_url); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                            </a>
                        </td>
                        <td>
                            <a href="product_detail.php?id=<?php echo $item['product_id']; ?>" style="text-decoration:none; color:#333;">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </a>
                        </td>
                        <td>₹<?php echo number_format($item['price'], 2); ?></td>
                        <td>
                            <form action="cart_actions.php" method="POST" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $current_stock; ?>" style="width: 60px; padding: 5px;">
                                <button type="submit" name="update_cart_item" class="btn btn-secondary" style="padding: 5px 8px; font-size: 0.9em;">Update</button>
                            </form>
                        </td>
                        <td>₹<?php echo number_format($subtotal, 2); ?></td>
                        <td>
                            <form action="cart_actions.php" method="POST" style="display:inline;">
                                <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                <button type="submit" name="remove_cart_item" class="btn btn-danger" style="padding: 5px 8px; font-size: 0.9em;">Remove</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="cart-summary">
            <h3>Cart Summary</h3>
            <p><strong>Grand Total: ₹<?php echo number_format($grand_total, 2); ?></strong></p>
        </div>

        <div class="cart-actions">
            <button type="submit" name="clear_cart" class="btn btn-danger">Clear Cart</button>
            <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
            <?php if (is_user_logged_in()): ?>
                <a href="checkout.php" class="btn">Proceed to Checkout</a> <?php else: ?>
                <p style="margin-top:10px;"><a href="login.php?redirect=cart.php" class="btn">Login to Checkout</a></p>
            <?php endif; ?>
        </div>
    </form>
<?php endif; ?>

<?php
mysqli_close($conn);
require_once 'footer.php';
?>
