<?php
require_once 'header.php';
require_user_login();

$user_id = $_SESSION['user_id'];
$orders = [];

$sql = "SELECT OrderID, OrderDate, TotalAmount, OrderStatus, PaymentMethod 
        FROM orders 
        WHERE UserID = ? 
        ORDER BY OrderDate DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    display_message("Error fetching order history: " . mysqli_error($conn), "error");
}
?>

<h2>My Order History</h2>

<?php if (empty($orders)): ?>
    <p class="message-info">You have not placed any orders yet. <a href="products.php">Start shopping!</a></p>
<?php else: ?>
    <table class="cart-table" style="width:100%;"> <thead>
            <tr>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>Total Amount</th>
                <th>Payment Method</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?php echo htmlspecialchars($order['OrderID']); ?></td>
                    <td><?php echo date("M j, Y, g:i a", strtotime($order['OrderDate'])); ?></td>
                    <td>₹<?php echo number_format($order['TotalAmount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($order['PaymentMethod']); ?></td>
                    <td><?php echo htmlspecialchars($order['OrderStatus']); ?></td>
                    <td>
                        <a href="order_detail_user.php?order_id=<?php echo $order['OrderID']; ?>" class="btn btn-secondary" style="font-size:0.9em; padding: 5px 10px;">View Details</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
mysqli_close($conn);
require_once 'footer.php';
?>