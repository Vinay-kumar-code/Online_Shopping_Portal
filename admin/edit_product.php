<?php
require_once __DIR__ . '/../config.php';
require_admin_login('index.php');
require_once 'admin_header.php';

if (!isset($_GET['id']) || empty((int)$_GET['id'])) {
    $_SESSION['admin_message'] = "<div class='message-error'>No product ID specified for editing.</div>";
    redirect(BASE_URL . 'admin/manage_products.php');
}
$product_id_to_edit = (int)$_GET['id'];

$product = null;
$sql_fetch = "SELECT * FROM products WHERE ProductID = ?";
$stmt_fetch = mysqli_prepare($conn, $sql_fetch);
if ($stmt_fetch) {
    mysqli_stmt_bind_param($stmt_fetch, "i", $product_id_to_edit);
    mysqli_stmt_execute($stmt_fetch);
    $result_fetch = mysqli_stmt_get_result($stmt_fetch);
    $product = mysqli_fetch_assoc($result_fetch);
    mysqli_stmt_close($stmt_fetch);
}

if (!$product) {
    $_SESSION['admin_message'] = "<div class='message-error'>Product not found with ID {$product_id_to_edit}.</div>";
    redirect(BASE_URL . 'admin/manage_products.php');
}

$categories = [];
$sql_cat = "SELECT CategoryID, CategoryName FROM categories ORDER BY CategoryName ASC";
$res_cat = mysqli_query($conn, $sql_cat);
if ($res_cat) {
    while ($row_cat = mysqli_fetch_assoc($res_cat)) {
        $categories[] = $row_cat;
    }
}

$product_name = $product['ProductName'];
$description = $product['Description'];
$mrp = $product['MRP'];
$selling_price = $product['SellingPrice'];
$stock_quantity = $product['StockQuantity'];
$category_id = $product['CategoryID'];
$current_main_image_url = $product['MainImageURL'];
$is_active = $product['IsActive'];
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $product_name = sanitize_input($_POST['product_name']);
    $description = sanitize_input($_POST['description']);
    $mrp = filter_var($_POST['mrp'], FILTER_VALIDATE_FLOAT);
    $selling_price = filter_var($_POST['selling_price'], FILTER_VALIDATE_FLOAT);
    $stock_quantity = filter_var($_POST['stock_quantity'], FILTER_VALIDATE_INT);
    $category_id = (int)$_POST['category_id'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($product_name)) $errors[] = "Product name is required.";
    if ($mrp === false || $mrp < 0) $errors[] = "Valid MRP is required.";
    if ($selling_price === false || $selling_price < 0) $errors[] = "Valid selling price is required.";
    if ($selling_price > $mrp) $errors[] = "Selling price cannot be greater than MRP.";
    if ($stock_quantity === false || $stock_quantity < 0) $errors[] = "Valid stock quantity is required.";
    if (empty($category_id)) $errors[] = "Category is required.";

    $new_uploaded_image_name = $current_main_image_url;
    if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] == UPLOAD_ERR_OK) {
        $file_info = $_FILES['main_image'];
        $file_name = $file_info['name'];
        $file_tmp_name = $file_info['tmp_name'];
        $file_size = $file_info['size'];
        $file_error = $file_info['error'];

        $file_ext_parts = explode('.', $file_name);
        $file_ext = strtolower(end($file_ext_parts));
        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (in_array($file_ext, $allowed_exts)) {
            if ($file_error === 0) {
                if ($file_size <= 2000000) { 
                    $new_file_name_unique = uniqid('prod_', true) . '.' . $file_ext;
                    $file_destination = UPLOAD_DIR . $new_file_name_unique;

                    if (move_uploaded_file($file_tmp_name, $file_destination)) {
                        if ($current_main_image_url && $current_main_image_url != 'default_product.png' && file_exists(UPLOAD_DIR . $current_main_image_url)) {
                            unlink(UPLOAD_DIR . $current_main_image_url);
                        }
                        $new_uploaded_image_name = $new_file_name_unique;
                    } else {
                        $errors[] = "Failed to move uploaded image.";
                    }
                } else {
                    $errors[] = "Image file too large (Max 2MB).";
                }
            } else {
                $errors[] = "Error uploading image: code " . $file_error;
            }
        } else {
            $errors[] = "Invalid image file type.";
        }
    } elseif (isset($_FILES['main_image']) && $_FILES['main_image']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors[] = "There was an error with the image upload (Code: " . $_FILES['main_image']['error'] . ").";
    }


    if (empty($errors)) {
        $sql_update = "UPDATE products SET 
                        ProductName = ?, Description = ?, MRP = ?, SellingPrice = ?, 
                        StockQuantity = ?, CategoryID = ?, MainImageURL = ?, IsActive = ?
                       WHERE ProductID = ?";
        $stmt_update = mysqli_prepare($conn, $sql_update);
        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, "sssddiisii", 
                $product_name, $description, $sku, $mrp, $selling_price, 
                $stock_quantity, $category_id, $new_uploaded_image_name, $is_active,
                $product_id_to_edit
            );
            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['admin_message'] = "<div class='message-success'>Product '{$product_name}' updated successfully!</div>";
                redirect('admin/manage_products.php');
            } else {
                $errors[] = "Failed to update product: " . mysqli_stmt_error($stmt_update);
            }
            mysqli_stmt_close($stmt_update);
        } else {
            $errors[] = "Database error preparing update statement: " . mysqli_error($conn);
        }
    }
}
?>

<h1>Edit Product: <?php echo htmlspecialchars($product['ProductName']); ?></h1>

<?php
if (!empty($errors)) {
    echo "<div class='message-error'><ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul></div>";
}
?>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>?id=<?php echo $product_id_to_edit; ?>" method="POST" enctype="multipart/form-data">
    <div class="form-group">
        <label for="product_name">Product Name:</label>
        <input type="text" name="product_name" id="product_name" value="<?php echo htmlspecialchars($product_name); ?>" required>
    </div>
    <div class="form-group">
        <label for="description">Description:</label>
        <textarea name="description" id="description" rows="5"><?php echo htmlspecialchars($description); ?></textarea>
    </div>
    <div class="form-group">
        <label for="mrp">MRP (₹):</label>
        <input type="number" name="mrp" id="mrp" step="0.01" min="0" value="<?php echo htmlspecialchars($mrp); ?>" required>
    </div>
    <div class="form-group">
        <label for="selling_price">Selling Price (₹):</label>
        <input type="number" name="selling_price" id="selling_price" step="0.01" min="0" value="<?php echo htmlspecialchars($selling_price); ?>" required>
    </div>
    <div class="form-group">
        <label for="stock_quantity">Stock Quantity:</label>
        <input type="number" name="stock_quantity" id="stock_quantity" min="0" value="<?php echo htmlspecialchars($stock_quantity); ?>" required>
    </div>
    <div class="form-group">
        <label for="category_id">Category:</label>
        <select name="category_id" id="category_id" required>
            <option value="">-- Select Category --</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo $cat['CategoryID']; ?>" <?php echo ($category_id == $cat['CategoryID'] ? 'selected' : ''); ?>>
                    <?php echo htmlspecialchars($cat['CategoryName']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="form-group">
        <label for="main_image">Main Product Image (leave blank to keep current):</label>
        <input type="file" name="main_image" id="main_image" accept="image/jpeg, image/png, image/gif, image/webp">
        <small>Current Image: <?php echo htmlspecialchars($current_main_image_url); ?></small><br>
        <?php if ($current_main_image_url && $current_main_image_url != 'default_product.png'): ?>
            <img src="<?php echo UPLOAD_URL . htmlspecialchars($current_main_image_url); ?>" alt="Current Image" style="max-width: 100px; max-height: 100px; margin-top: 5px;">
        <?php endif; ?>
    </div>
    <div class="form-group">
        <label for="is_active">
            <input type="checkbox" name="is_active" id="is_active" value="1" <?php echo ($is_active ? 'checked' : ''); ?>>
            Product is Active
        </label>
    </div>
    <div class="form-group">
        <button type="submit" class="btn">Update Product</button>
        <a href="manage_products.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<?php
require_once 'admin_footer.php';
mysqli_close($conn);
?>
