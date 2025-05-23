<?php
require_once __DIR__ . '/../config.php';
require_admin_login('index.php');
require_once 'admin_header.php';

$delete_msg = '';
$delete_type = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_product'])) {
    $product_id_to_delete = (int)$_POST['product_id'];

    $sql_delete = "UPDATE products SET IsActive = FALSE WHERE ProductID = ?";
    $stmt_delete = mysqli_prepare($conn, $sql_delete);
    if ($stmt_delete) {
        mysqli_stmt_bind_param($stmt_delete, "i", $product_id_to_delete);
        if (mysqli_stmt_execute($stmt_delete)) {
            if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                $_SESSION['admin_message'] = "<div class='message-success'>Product (ID: {$product_id_to_delete}) deactivated successfully.</div>";
            } else {
                $_SESSION['admin_message'] = "<div class='message-error'>Product not found or already inactive.</div>";
            }
        } else {
            $_SESSION['admin_message'] = "<div class='message-error'>Error deactivating product: " . mysqli_stmt_error($stmt_delete) . "</div>";
        }
        mysqli_stmt_close($stmt_delete);
    } else {
        $_SESSION['admin_message'] = "<div class='message-error'>Error preparing deactivation statement.</div>";
    }
    redirect('admin/manage_products.php');
}

$products = [];
$sql = "SELECT p.ProductID, p.ProductName, p.SellingPrice, p.StockQuantity, p.IsActive, c.CategoryName, p.MainImageURL
        FROM products p
        JOIN categories c ON p.CategoryID = c.CategoryID
        ORDER BY p.DateAdded DESC";
$result = mysqli_query($conn, $sql);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
} else {
    $_SESSION['admin_message'] = "<div class='message-error'>Error fetching products: " . mysqli_error($conn) . "</div>";
}

?>

<h1>Manage Products</h1>
<p><a href="add_product.php" class="btn">Add New Product</a></p>

<?php
echo get_admin_session_message();
?>

<?php if (empty($products)): ?>
    <p class="message-info">No products found. <a href="add_product.php">Add your first product!</a></p>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Image</th>
                <th>ID</th>
                <th>Name</th>
                <th>Category</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($product['MainImageURL'] ? $product['MainImageURL'] : 'default_product.png'); ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>" style="width: 60px; height: 60px; object-fit: cover;">
                    </td>
                    <td><?php echo $product['ProductID']; ?></td>
                    <td><?php echo htmlspecialchars($product['ProductName']); ?></td>
                    <td><?php echo htmlspecialchars($product['CategoryName']); ?></td>
                    <td>₹<?php echo number_format($product['SellingPrice'], 2); ?></td>
                    <td><?php echo $product['StockQuantity']; ?></td>
                    <td><?php echo $product['IsActive'] ? '<span style="color:green;">Active</span>' : '<span style="color:red;">Inactive</span>'; ?></td>
                    <td class="actions">
                        <a href="edit_product.php?id=<?php echo $product['ProductID']; ?>" class="edit-link" title="Edit">&#9998;</a> <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to deactivate this product? Users will not be able to see or purchase it.');">
                            <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                            <button type="submit" name="delete_product" class="delete-link" title="Deactivate" style="border:none; background:none; cursor:pointer; padding:0; color:red;">&#10006;</button> </form>
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