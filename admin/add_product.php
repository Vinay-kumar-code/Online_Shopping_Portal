<?php
require_once __DIR__ . '/../config.php';
require_admin_login('index.php');
require_once 'admin_header.php';

$categories = [];
$sql_cat = "SELECT CategoryID, CategoryName FROM categories ORDER BY CategoryName ASC";
$res_cat = mysqli_query($conn, $sql_cat);
if ($res_cat) {
    while ($row_cat = mysqli_fetch_assoc($res_cat)) {
        $categories[] = $row_cat;
    }
}

$product_name = $description = $sku = $mrp = $selling_price = $stock_quantity = $category_id = '';
$main_image_url = 'default_product.png';
$is_active = 1;
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

    $uploaded_image_name = $main_image_url;
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
                    $new_file_name = uniqid('prod_', true) . '.' . $file_ext;
                    $file_destination = UPLOAD_DIR . $new_file_name;

                    if (move_uploaded_file($file_tmp_name, $file_destination)) {
                        $uploaded_image_name = $new_file_name;
                    } else {
                        $errors[] = "Failed to move uploaded image to destination.";
                    }
                } else {
                    $errors[] = "Image file is too large (Max 2MB).";
                }
            } else {
                $errors[] = "Error uploading image: error code " . $file_error;
            }
        } else {
            $errors[] = "Invalid image file type. Allowed: " . implode(', ', $allowed_exts);
        }
    } elseif (isset($_FILES['main_image']) && $_FILES['main_image']['error'] != UPLOAD_ERR_NO_FILE) {
        $errors[] = "There was an error with the image upload (Code: " . $_FILES['main_image']['error'] . ").";
    }

    if (empty($errors)) {
        $sql = "INSERT INTO products (ProductName, Description, MRP, SellingPrice, StockQuantity, CategoryID, MainImageURL, IsActive) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssddiisi", 
                $product_name, $description, $mrp, $selling_price, 
                $stock_quantity, $category_id, $uploaded_image_name, $is_active
            );
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['admin_message'] = "<div class='message-success'>Product '{$product_name}' added successfully!</div>";
                redirect('admin/manage_products.php');
            } else {
                $errors[] = "Failed to add product: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Database error preparing statement: " . mysqli_error($conn);
        }
    }
}
?>

<h1>Add New Product</h1>

<?php
if (!empty($errors)) {
    echo "<div class='message-error'><ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul></div>";
}
?>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
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
        <label for="main_image">Main Product Image:</label>
        <input type="file" name="main_image" id="main_image" accept="image/jpeg, image/png, image/gif, image/webp">
        <small>Max 2MB. Allowed types: JPG, PNG, GIF, WEBP.</small>
    </div>
    <div class="form-group">
        <label for="is_active">
            <input type="checkbox" name="is_active" id="is_active" value="1" <?php echo ($is_active ? 'checked' : ''); ?>>
            Product is Active (visible to customers)
        </label>
    </div>
    <div class="form-group">
        <button type="submit" class="btn">Add Product</button>
        <a href="manage_products.php" class="btn btn-secondary">Cancel</a>
    </div>
</form>

<?php
require_once 'admin_footer.php';
mysqli_close($conn);
?>