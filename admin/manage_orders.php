<?php
require_once __DIR__ . '/../config.php';
require_admin_login('index.php');
require_once 'admin_header.php';

$errors = [];
$success_msg = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_order_status'])) {
    $order_id_to_update = (int)$_POST['order_id'];
    $new_status = sanitize_input($_POST['order_status']);
    $tracking_number = isset($_POST['tracking_number']) ? sanitize_input($_POST['tracking_number']) : null;

    if (empty($new_status)) {
        $errors[] = "Order status cannot be empty.";
    }
    $allowed_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Refunded (Simulated)'];
    if (!in_array($new_status, $allowed_statuses)) {
        $errors[] = "Invalid order status selected.";
    }

    if (empty($errors)) {
        $sql_update = "UPDATE orders SET OrderStatus = ?";
        $params_types = "s";
        $params_values = [$new_status];

        if ($new_status === 'Shipped' && !empty($tracking_number)) {
            $sql_update .= ", TrackingNumber = ?";
            $params_types .= "s";
            $params_values[] = $tracking_number;
        } elseif ($new_status !== 'Shipped') {
            $sql_update .= ", TrackingNumber = NULL";
        }
        
        $sql_update .= " WHERE OrderID = ?";
        $params_types .= "i";
        $params_values[] = $order_id_to_update;
        
        $stmt_update = mysqli_prepare($conn, $sql_update);
        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, $params_types, ...$params_values);
            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['admin_message'] = "<div class='message-success'>Order #{$order_id_to_update} status updated to '{$new_status}'.</div>";
            } else {
                $errors[] = "Failed to update order status: " . mysqli_stmt_error($stmt_update);
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $errors[] = "Database error preparing update statement: " . mysqli_error($conn);
        }
    }
    if (!empty($errors)) {
        $error_msg_html = "<ul>";
        foreach($errors as $err) $error_msg_html .= "<li>{$err}</li>";
        $error_msg_html .= "</ul>";
        $_SESSION['admin_message'] = "<div class='message-error'>{$error_msg_html}</div>";
    }
    $redirect_url ='admin/manage_orders.php';
    if(isset($_POST['filter_status_redirect']) && !empty($_POST['filter_status_redirect'])) {
        $redirect_url .= '?filter_status=' . urlencode($_POST['filter_status_redirect']);
    }
    redirect($redirect_url);
}

$orders = [];
$filter_status = isset($_GET['filter_status']) ? sanitize_input($_GET['filter_status']) : '';
$view_order_id = isset($_GET['view_order_id']) ? (int)$_GET['view_order_id'] : 0;

$sql = "SELECT o.OrderID, o.OrderDate, o.TotalAmount, o.OrderStatus, o.PaymentMethod, o.TrackingNumber,
               u.UserID, u.FirstName, u.LastName, u.Email as UserEmail,
               a.Street, a.City, a.State, a.ZipCode, a.Country
        FROM orders o
        JOIN users u ON o.UserID = u.UserID
        JOIN addresses a ON o.ShippingAddressID = a.AddressID ";

if (!empty($filter_status)) {
    $sql .= " WHERE o.OrderStatus = ? ";
} elseif ($view_order_id > 0) {
    $sql .= " WHERE o.OrderID = ? ";
}

$sql .= " ORDER BY o.OrderDate DESC";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    if (!empty($filter_status)) {
        mysqli_stmt_bind_param($stmt, "s", $filter_status);
    } elseif ($view_order_id > 0) {
        mysqli_stmt_bind_param($stmt, "i", $view_order_id);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['admin_message'] = "<div class='message-error'>Error fetching orders: " . mysqli_error($conn) . "</div>";
}

$order_statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled', 'Refunded (Simulated)'];

?>

<h1>Manage Orders</h1>

<?php
echo get_admin_session_message();
?>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="GET" style="margin-bottom: 20px; background-color: #fff; padding: 15px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
    <div class="form-group" style="display: inline-block; margin-right: 10px;">
        <label for="filter_status">Filter by Status:</label>
        <select name="filter_status" id="filter_status" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <?php foreach ($order_statuses as $status): ?>
                <option value="<?php echo $status; ?>" <?php echo ($filter_status == $status ? 'selected' : ''); ?>>
                    <?php echo htmlspecialchars($status); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if (!empty($filter_status) || $view_order_id > 0): ?>
        <a href="manage_orders.php" class="btn btn-secondary">Clear Filter/View</a>
    <?php endif; ?>
</form>


<?php if ($view_order_id > 0 && count($orders) === 1): ?>
    <?php $order = $orders[0]; ?>
    <div style="background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <h3>Order Details #<?php echo htmlspecialchars($order['OrderID']); ?></h3>
        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
            <div style="flex:1; min-width:250px;">
                <p><strong>Customer:</strong> <?php echo htmlspecialchars($order['FirstName'] . ' ' . $order['LastName']); ?> (<?php echo htmlspecialchars($order['UserEmail']); ?>)</p>
                <p><strong>Order Date:</strong> <?php echo date("M j, Y, g:i a", strtotime($order['OrderDate'])); ?></p>
                <p><strong>Total Amount:</strong> ₹<?php echo number_format($order['TotalAmount'], 2); ?></p>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($order['PaymentMethod']); ?></p>
            </div>
            <div style="flex:1; min-width:250px;">
                <p><strong>Shipping Address:</strong><br>
                    <?php echo htmlspecialchars($order['Street']); ?><br>
                    <?php echo htmlspecialchars($order['City']); ?>, <?php echo htmlspecialchars($order['State']); ?> - <?php echo htmlspecialchars($order['ZipCode']); ?><br>
                    <?php echo htmlspecialchars($order['Country']); ?>
                </p>
            </div>
        </div>

        <h4 style="margin-top:20px;">Items Ordered:</h4>
        <?php
        $order_items = [];
        $sql_items = "SELECT oi.ProductID, p.ProductName, oi.Quantity, oi.PriceAtPurchase, oi.Subtotal 
                      FROM order_items oi JOIN products p ON oi.ProductID = p.ProductID 
                      WHERE oi.OrderID = ?";
        $stmt_items = mysqli_prepare($conn, $sql_items);
        mysqli_stmt_bind_param($stmt_items, "i", $order['OrderID']);
        mysqli_stmt_execute($stmt_items);
        $res_items = mysqli_stmt_get_result($stmt_items);
        while($item_row = mysqli_fetch_assoc($res_items)) $order_items[] = $item_row;
        mysqli_stmt_close($stmt_items);
        ?>
        <table class="admin-table" style="font-size:0.9em;">
            <thead><tr><th>Product ID</th><th>Name</th><th>Qty</th><th>Price Paid</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php foreach($order_items as $item): ?>
                <tr>
                    <td><?php echo $item['ProductID']; ?></td>
                    <td><?php echo htmlspecialchars($item['ProductName']); ?></td>
                    <td><?php echo $item['Quantity']; ?></td>
                    <td>₹<?php echo number_format($item['PriceAtPurchase'], 2); ?></td>
                    <td>₹<?php echo number_format($item['Subtotal'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h4 style="margin-top:20px;">Update Order Status:</h4>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <input type="hidden" name="order_id" value="<?php echo $order['OrderID']; ?>">
            <input type="hidden" name="filter_status_redirect" value="<?php echo htmlspecialchars($filter_status); ?>"> <div class="form-group">
                <label for="order_status_update_<?php echo $order['OrderID']; ?>">Status:</label>
                <select name="order_status" id="order_status_update_<?php echo $order['OrderID']; ?>">
                    <?php foreach ($order_statuses as $status_option): ?>
                        <option value="<?php echo $status_option; ?>" <?php echo ($order['OrderStatus'] == $status_option ? 'selected' : ''); ?>>
                            <?php echo htmlspecialchars($status_option); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group" id="tracking_number_div_<?php echo $order['OrderID']; ?>" style="<?php echo ($order['OrderStatus'] == 'Shipped' || (isset($_POST['order_status']) && $_POST['order_status'] == 'Shipped') ? '' : 'display:none;'); ?>">
                <label for="tracking_number_<?php echo $order['OrderID']; ?>">Tracking Number:</label>
                <input type="text" name="tracking_number" id="tracking_number_<?php echo $order['OrderID']; ?>" value="<?php echo htmlspecialchars($order['TrackingNumber'] ?? ''); ?>">
            </div>
            <button type="submit" name="update_order_status" class="btn">Update Status</button>
        </form>
        <script>
            document.getElementById('order_status_update_<?php echo $order['OrderID']; ?>').addEventListener('change', function() {
                var trackingDiv = document.getElementById('tracking_number_div_<?php echo $order['OrderID']; ?>');
                if (this.value === 'Shipped') {
                    trackingDiv.style.display = 'block';
                } else {
                    trackingDiv.style.display = 'none';
                    document.getElementById('tracking_number_<?php echo $order['OrderID']; ?>').value = ''; 
                }
            });
        </script>
    </div>
<?php elseif (empty($orders)): ?>
    <p class="message-info">No orders found matching your criteria.</p>
<?php else:  ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Total</th>
                <th>Status</th>
                <th>Tracking #</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>#<?php echo $order['OrderID']; ?></td>
                    <td><?php echo htmlspecialchars($order['FirstName'] . ' ' . $order['LastName']); ?><br><small><?php echo htmlspecialchars($order['UserEmail']); ?></small></td>
                    <td><?php echo date("M j, Y, g:i a", strtotime($order['OrderDate'])); ?></td>
                    <td>₹<?php echo number_format($order['TotalAmount'], 2); ?></td>
                    <td><?php echo htmlspecialchars($order['OrderStatus']); ?></td>
                    <td><?php echo htmlspecialchars($order['TrackingNumber'] ?? 'N/A'); ?></td>
                    <td class="actions">
                        <a href="manage_orders.php?view_order_id=<?php echo $order['OrderID']; ?><?php echo !empty($filter_status) ? '&filter_status='.urlencode($filter_status) : ''; ?>" class="btn btn-secondary" style="font-size:0.8em; padding:3px 8px;">View/Update</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php
require_once 'admin_footer.php';
mysqli_close($conn);
?>
