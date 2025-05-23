<?php
require_once 'header.php';

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$search_term = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';

$page_title = "Our Products";
$sql = "SELECT p.ProductID, p.ProductName, p.SellingPrice, p.MRP, p.MainImageURL, c.CategoryName
        FROM products p
        JOIN categories c ON p.CategoryID = c.CategoryID
        WHERE p.IsActive = TRUE";

if ($category_id > 0) {
    $cat_sql = "SELECT CategoryName FROM categories WHERE CategoryID = ?";
    if ($cat_stmt = mysqli_prepare($conn, $cat_sql)) {
        mysqli_stmt_bind_param($cat_stmt, "i", $category_id);
        mysqli_stmt_execute($cat_stmt);
        mysqli_stmt_bind_result($cat_stmt, $category_name_title);
        if (mysqli_stmt_fetch($cat_stmt)) {
            $page_title = "Products in " . htmlspecialchars($category_name_title);
        }
        mysqli_stmt_close($cat_stmt);
    }
    $sql .= " AND (p.CategoryID = ? OR p.CategoryID IN (SELECT CategoryID FROM categories WHERE ParentCategoryID = ?))";
}

if (!empty($search_term)) {
    $page_title = "Search Results for \"" . htmlspecialchars($search_term) . "\"";
    $sql .= " AND (p.ProductName LIKE ? OR p.Description LIKE ? OR c.CategoryName LIKE ?)";
}

$sql .= " ORDER BY p.DateAdded DESC";

$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    if ($category_id > 0 && !empty($search_term)) {
        $search_like = "%" . $search_term . "%";
        mysqli_stmt_bind_param($stmt, "iisss", $category_id, $category_id, $search_like, $search_like, $search_like);
    } elseif ($category_id > 0) {
        mysqli_stmt_bind_param($stmt, "ii", $category_id, $category_id);
    } elseif (!empty($search_term)) {
        $search_like = "%" . $search_term . "%";
        mysqli_stmt_bind_param($stmt, "sss", $search_like, $search_like, $search_like);
    }

    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
} else {
    echo "<p class='message-error'>Error preparing product query: " . mysqli_error($conn) . "</p>";
    $result = false;
}

$categories_sql = "SELECT CategoryID, CategoryName FROM categories WHERE ParentCategoryID IS NULL ORDER BY CategoryName ASC";
$categories_result = mysqli_query($conn, $categories_sql);

?>

<h2><?php echo $page_title; ?></h2>

<form action="<?php echo BASE_URL; ?>products.php" method="GET" class="search-form" style="margin-bottom: 20px; display: flex; gap: 10px;">
    <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search_term); ?>" style="flex-grow: 1; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
    <button type="submit" class="btn">Search</button>
</form>

<div style="display: flex; gap: 20px;">
    <?php if ($categories_result && mysqli_num_rows($categories_result) > 0): ?>
    <aside style="width: 200px; border-right: 1px solid #eee; padding-right: 20px;">
        <h4>Categories</h4>
        <ul style="list-style: none; padding: 0;">
            <li><a href="<?php echo BASE_URL; ?>products.php" style="text-decoration: none; color: #333; display:block; padding: 5px 0;">All Products</a></li>
            <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                <li>
                    <a href="<?php echo BASE_URL; ?>products.php?category_id=<?php echo $category['CategoryID']; ?>" style="text-decoration: none; color: #0779e4; display:block; padding: 5px 0;">
                        <?php echo htmlspecialchars($category['CategoryName']); ?>
                    </a>
                </li>
            <?php endwhile; ?>
        </ul>
    </aside>
    <?php endif; ?>

    <section class="product-grid" style="flex-grow: 1;">
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
            while ($product = mysqli_fetch_assoc($result)) {
                ?>
                <div class="product-card">
                    <a href="<?php echo BASE_URL; ?>product_detail.php?id=<?php echo $product['ProductID']; ?>" style="text-decoration:none; color:inherit;">
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($product['MainImageURL']); ?>" alt="<?php echo htmlspecialchars($product['ProductName']); ?>">
                        <h3><?php echo htmlspecialchars($product['ProductName']); ?></h3>
                        <p class="category" style="font-size:0.9em; color:#777;"><?php echo htmlspecialchars($product['CategoryName']); ?></p>
                        <p class="price">
                            ₹<?php echo number_format($product['SellingPrice'], 2); ?>
                            <?php if ($product['MRP'] > $product['SellingPrice']): ?>
                                <span class="mrp">₹<?php echo number_format($product['MRP'], 2); ?></span>
                            <?php endif; ?>
                        </p>
                    </a>
                    <form action="<?php echo BASE_URL; ?>cart_actions.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" name="add_to_cart" class="btn">Add to Cart</button>
                    </form>
                </div>
                <?php
            }
        } else {
            if (empty($search_term) && $category_id == 0) {
                 echo "<p>No products found. Please check back later or contact support if you believe this is an error.</p>";
            } elseif (!empty($search_term)) {
                 echo "<p>No products found matching your search term: \"" . htmlspecialchars($search_term) . "\". Try a different search or browse categories.</p>";
            } else {
                 echo "<p>No products found in this category. Try browsing other categories or all products.</p>";
            }
        }
        if ($stmt) {
            mysqli_stmt_close($stmt);
        }
        ?>
    </section>
</div>

<?php
if(isset($conn)) mysqli_close($conn);
require_once 'footer.php';
?>