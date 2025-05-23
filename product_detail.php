<?php
require_once 'header.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    display_message("No product specified.", "error");
    redirect('products.php');
}

$product_id = (int)$_GET['id'];

$sql = "SELECT p.ProductID, p.ProductName, p.Description, p.MRP, p.SellingPrice, 
               p.StockQuantity, p.MainImageURL, c.CategoryName, c.CategoryID
        FROM products p
        JOIN categories c ON p.CategoryID = c.CategoryID
        WHERE p.ProductID = ? AND p.IsActive = TRUE";

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $product = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$product) {
        display_message("Product not found or is inactive.", "error");
        redirect('products.php');
    }
} else {
    display_message("Error fetching product details: " . mysqli_error($conn), "error");
    redirect('products.php');
}

?>

<div class="product-detail-layout">
    <div class="product-detail-image">
        <img src="<?php echo UPLOAD_URL . htmlspecialchars($product['MainImageURL']); ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
    </div>

    <div class="product-detail-info">
        <h1><?php echo htmlspecialchars($product['ProductName']); ?></h1>
        <p class="category">Category: <a href="<?php echo BASE_URL; ?>products.php?category_id=<?php echo $product['CategoryID']; ?>"><?php echo htmlspecialchars($product['CategoryName']); ?></a></p>
        
        <p class="price">
            ₹<?php echo number_format($product['SellingPrice'], 2); ?>
            <?php if ($product['MRP'] > $product['SellingPrice']): ?>
                <span class="mrp">₹<?php echo number_format($product['MRP'], 2); ?></span>
                <?php 
                $discount = (($product['MRP'] - $product['SellingPrice']) / $product['MRP']) * 100;
                echo " <span style='color:green; font-size:0.9em;'>(" . round($discount) . "% off)</span>";
                ?>
            <?php endif; ?>
        </p>

        <p class="stock <?php echo ($product['StockQuantity'] > 0) ? 'in-stock' : 'out-of-stock'; ?>">
            <?php echo ($product['StockQuantity'] > 0) ? 'In Stock (' . $product['StockQuantity'] . ' available)' : 'Out of Stock'; ?>
        </p>
        
        <div class="description" style="margin-top: 20px; margin-bottom: 20px;">
            <h4>Product Description:</h4>
            <p><?php echo nl2br(htmlspecialchars($product['Description'])); ?></p>
        </div>

        <?php if ($product['StockQuantity'] > 0): ?>
            <form action="<?php echo BASE_URL; ?>cart_actions.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                <div class="form-group" style="max-width: 150px;">
                    <label for="quantity">Quantity:</label>
                    <input type="number" name="quantity" id="quantity" value="1" min="1" max="<?php echo $product['StockQuantity']; ?>" class="form-control">
                </div>
                <button type="submit" name="add_to_cart" class="btn">Add to Cart</button>
            </form>
        <?php else: ?>
            <p class="message-info">This product is currently out of stock.</p>
        <?php endif; ?>
    </div>
</div>

<?php
if(isset($conn)) mysqli_close($conn);
require_once 'footer.php';
?>