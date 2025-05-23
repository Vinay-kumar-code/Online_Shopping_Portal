<?php
require_once 'header.php';

require_user_login();

if (!isset($_SESSION['last_order_id'])) {
    display_message("No recent order found. View your order history for details.", "info");
    redirect('order_history.php');
}

$last_order_id = $_SESSION['last_order_id'];
unset($_SESSION['last_order_id']); 

$order_details = null;
$order_items_details = [];
$sql_order = "SELECT OrderID, OrderDate, TotalAmount, PaymentMethod, OrderStatus 
              FROM orders 
              WHERE OrderID = ? AND UserID = ?";
$stmt_order = mysqli_prepare($conn, $sql_order);
if ($stmt_order) {
    mysqli_stmt_bind_param($stmt_order, "ii", $last_order_id, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt_order);
    $result_order = mysqli_stmt_get_result($stmt_order);
    $order_details = mysqli_fetch_assoc($result_order);
    mysqli_stmt_close($stmt_order);

    if ($order_details) {
        $sql_items = "SELECT p.ProductName, oi.Quantity, oi.PriceAtPurchase, oi.Subtotal 
                      FROM order_items oi
                      JOIN products p ON oi.ProductID = p.ProductID
                      WHERE oi.OrderID = ?";
        $stmt_items = mysqli_prepare($conn, $sql_items);
        if ($stmt_items) {
            mysqli_stmt_bind_param($stmt_items, "i", $last_order_id);
            mysqli_stmt_execute($stmt_items);
            $result_items = mysqli_stmt_get_result($stmt_items);
            while ($row = mysqli_fetch_assoc($result_items)) {
                $order_items_details[] = $row;
            }
            mysqli_stmt_close($stmt_items);
        }
    }
}

?>
<h2>Order Placed Successfully!</h2>

<?php if ($order_details): ?>
    <div class="message-success">
        <p>Thank you for your order, <?php echo htmlspecialchars($_SESSION['user_first_name']); ?>!</p>
        <p>Your Order ID is: <strong>#<?php echo htmlspecialchars($order_details['OrderID']); ?></strong></p>
        <p>Order Date: <?php echo date("F j, Y, g:i a", strtotime($order_details['OrderDate'])); ?></p>
        <p>Total Amount: ₹<?php echo number_format($order_details['TotalAmount'], 2); ?></p>
        <p>Payment Method: <?php echo htmlspecialchars($order_details['PaymentMethod']); ?></p>
        <p>Current Status: <?php echo htmlspecialchars($order_details['OrderStatus']); ?></p>
        <p>A confirmation (simulated) has been sent to your email.</p>
    </div>

    <?php if (!empty($order_items_details)): ?>
        <h3>Order Summary:</h3>
        <table class="cart-table" style="margin-bottom: 20px;">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Quantity</th>
                    <th>Price Paid</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order_items_details as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                    <td><?php echo $item['Quantity']; ?></td>
                    <td>₹<?php echo number_format($item['PriceAtPurchase'], 2); ?></td>
                    <td>₹<?php echo number_format($item['Subtotal'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

<?php else: ?>
    <p class="message-info">Thank you for your order! You can view your order details in your <a href="order_history.php">order history</a>.</p>
<?php endif; ?>

<p style="margin-top: 20px;">
    <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
    <a href="order_history.php" class="btn">View Order History</a>
</p>

<?php
mysqli_close($conn);
require_once 'footer.php';
?>
