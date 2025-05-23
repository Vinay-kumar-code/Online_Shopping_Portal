<?php
require_once __DIR__ . '/../config.php';
require_admin_login('index.php'); 

require_once 'admin_header.php';

$sql_users = "SELECT COUNT(UserID) as total_users FROM users";
$res_users = mysqli_query($conn, $sql_users);
$total_users = ($res_users && mysqli_num_rows($res_users) > 0) ? mysqli_fetch_assoc($res_users)['total_users'] : 0;

$sql_products = "SELECT COUNT(ProductID) as total_products FROM products WHERE IsActive = TRUE";
$res_products = mysqli_query($conn, $sql_products);
$total_products = ($res_products && mysqli_num_rows($res_products) > 0) ? mysqli_fetch_assoc($res_products)['total_products'] : 0;

$sql_orders = "SELECT COUNT(OrderID) as total_orders FROM orders";
$res_orders = mysqli_query($conn, $sql_orders);
$total_orders = ($res_orders && mysqli_num_rows($res_orders) > 0) ? mysqli_fetch_assoc($res_orders)['total_orders'] : 0;

$sql_pending_orders = "SELECT COUNT(OrderID) as pending_orders FROM orders WHERE OrderStatus = 'Pending'";
$res_pending_orders = mysqli_query($conn, $sql_pending_orders);
$pending_orders = ($res_pending_orders && mysqli_num_rows($res_pending_orders) > 0) ? mysqli_fetch_assoc($res_pending_orders)['pending_orders'] : 0;

$recent_orders = [];
$sql_recent = "SELECT o.OrderID, u.FirstName, u.LastName, o.OrderDate, o.TotalAmount, o.OrderStatus 
               FROM orders o
               JOIN users u ON o.UserID = u.UserID
               ORDER BY o.OrderDate DESC LIMIT 5";
$res_recent = mysqli_query($conn, $sql_recent);
if ($res_recent) {
    while ($row = mysqli_fetch_assoc($res_recent)) {
        $recent_orders[] = $row;
    }
}
?>

<h1>Admin Dashboard</h1>
<p>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</p>

<div class="admin-dashboard-stats">
    <div class="stat-card"><h4>Total Customers</h4><p><?php echo $total_users; ?></p></div>
    <div class="stat-card"><h4>Active Products</h4><p><?php echo $total_products; ?></p></div>
    <div class="stat-card"><h4>Total Orders</h4><p><?php echo $total_orders; ?></p></div>
    <div class="stat-card"><h4>Pending Orders</h4><p><?php echo $pending_orders; ?></p></div>
</div>

<h3>Recent Orders</h3>
<?php if (!empty($recent_orders)): ?>
<table class="admin-table">
    <thead>
        <tr><th>Order ID</th><th>Customer</th><th>Date</th><th>Total</th><th>Status</th><th>Action</th></tr>
    </thead>
    <tbody>
        <?php foreach ($recent_orders as $order): ?>
        <tr>
            <td>#<?php echo $order['OrderID']; ?></td>
            <td><?php echo htmlspecialchars($order['FirstName'] . ' ' . $order['LastName']); ?></td>
            <td><?php echo date("M j, Y, g:i a", strtotime($order['OrderDate'])); ?></td>
            <td>₹<?php echo number_format($order['TotalAmount'], 2); ?></td>
            <td><?php echo htmlspecialchars($order['OrderStatus']); ?></td>
            <td><a href="<?php echo BASE_URL; ?>admin/manage_orders.php?view_order_id=<?php echo $order['OrderID']; ?>" class="btn btn-secondary" style="font-size:0.8em; padding:3px 8px;">View</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p class="message-info">No recent orders found.</p>
<?php endif; ?>

<?php
require_once 'admin_footer.php';
if ($conn) mysqli_close($conn);
?>