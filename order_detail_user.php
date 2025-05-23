<?php
require_once 'header.php';
require_user_login();

if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    display_message("No order specified.", "error");
    redirect('order_history.php');
}

$order_id_get = (int)$_GET['order_id'];
$user_id = $_SESSION['user_id'];

$order_details = null;
$order_items_details = [];
$shipping_address_details = null;

$sql_order = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.OrderStatus, o.PaymentMethod, o.PaymentStatus, o.TrackingNumber,
                     a.Street, a.City, a.State, a.ZipCode, a.Country
              FROM orders o
              JOIN addresses a ON o.ShippingAddressID = a.AddressID
              WHERE o.OrderID = ? AND o.UserID = ?";
$stmt_order = mysqli_prepare($conn, $sql_order);

if ($stmt_order) {
    mysqli_stmt_bind_param($stmt_order, "ii", $order_id_get, $user_id);
    mysqli_stmt_execute($stmt_order);
    $result_order = mysqli_stmt_get_result($stmt_order);
    $order_details = mysqli_fetch_assoc($result_order);
    mysqli_stmt_close($stmt_order);

    if ($order_details) {
        $sql_items = "SELECT p.ProductName, p.MainImageURL, oi.Quantity, oi.PriceAtPurchase, oi.Subtotal, p.ProductID
                      FROM order_items oi
                      JOIN products p ON oi.ProductID = p.ProductID
                      WHERE oi.OrderID = ?";
        $stmt_items = mysqli_prepare($conn, $sql_items);
        if ($stmt_items) {
            mysqli_stmt_bind_param($stmt_items, "i", $order_id_get);
            mysqli_stmt_execute($stmt_items);
            $result_items = mysqli_stmt_get_result($stmt_items);
            while ($row = mysqli_fetch_assoc($result_items)) {
                $order_items_details[] = $row;
            }
            mysqli_stmt_close($stmt_items);
        }
    } else {
        display_message("Order not found or you do not have permission to view it.", "error");
        redirect('order_history.php');
    }
} else {
    display_message("Error fetching order details: " . mysqli_error($conn), "error");
    redirect('order_history.php');
}

?>

<h2>Order Details #<?php echo htmlspecialchars($order_details['OrderID']); ?></h2>

<div style="display: flex; flex-wrap: wrap; gap: 30px;">
    <div style="flex: 1; min-width: 280px; background-color: #f9f9f9; padding:15px; border-radius:5px;">
        <h4>Order Information</h4>
        <p><strong>Order Date:</strong> <?php echo date("F j, Y, g:i a", strtotime($order_details['OrderDate'])); ?></p>
        <p><strong>Total Amount:</strong> ₹<?php echo number_format($order_details['TotalAmount'], 2); ?></p>
        <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order_details['PaymentMethod']); ?></p>
        <p><strong>Payment Status:</strong> <?php echo htmlspecialchars($order_details['PaymentStatus']); ?></p>
        <p><strong>Order Status:</strong> <?php echo htmlspecialchars($order_details['OrderStatus']); ?></p>
        <?php if (!empty($order_details['TrackingNumber'])): ?>
            <p><strong>Tracking Number:</strong> <?php echo htmlspecialchars($order_details['TrackingNumber']); ?></p>
        <?php endif; ?>
    </div>

    <div style="flex: 1; min-width: 280px; background-color: #f9f9f9; padding:15px; border-radius:5px;">
        <h4>Shipping Address</h4>
        <p>
            <?php echo htmlspecialchars($order_details['Street']); ?><br>
            <?php echo htmlspecialchars($order_details['City']); ?>, <?php echo htmlspecialchars($order_details['State']); ?> - <?php echo htmlspecialchars($order_details['ZipCode']); ?><br>
            <?php echo htmlspecialchars($order_details['Country']); ?>
        </p>
    </div>
</div>

<h3 style="margin-top:30px;">Items in this Order</h3>
<?php if (!empty($order_items_details)): ?>
    <table class="cart-table" style="margin-top:10px;">
        <thead>
            <tr>
                <th>Product</th>
                <th>Name</th>
                <th>Quantity</th>
                <th>Price Paid</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order_items_details as $item): ?>
            <tr>
                <td>
                    <a href="product_detail.php?id=<?php echo $item['ProductID']; ?>">
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($item['MainImageURL']); ?>" alt="<?php echo htmlspecialchars($item['ProductName']); ?>" style="width:60px; height:60px; object-fit:cover;">
                    </a>
                </td>
                <td>
                     <a href="product_detail.php?id=<?php echo $item['ProductID']; ?>" style="text-decoration:none; color:#333;">
                        <?php echo htmlspecialchars($item['ProductName']); ?>
                    </a>
                </td>
                <td><?php echo $item['Quantity']; ?></td>
                <td>₹<?php echo number_format($item['PriceAtPurchase'], 2); ?></td>
                <td>₹<?php echo number_format($item['Subtotal'], 2); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p class="message-info">No items found for this order. This should not happen.</p>
<?php endif; ?>

<p style="margin-top: 30px;">
    <a href="order_history.php" class="btn btn-secondary">&laquo; Back to Order History</a>
</p>

<?php
mysqli_close($conn);
require_once 'footer.php';
?>
