<?php
require_once __DIR__ . '/../config.php';
require_admin_login('index.php');
require_once 'admin_header.php';

$errors = [];
$success_msg = '';

$action = $_GET['action'] ?? 'view';
$edit_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category_to_edit = null;

function fetch_all_categories($conn, $parent_id = null, $prefix = '') {
    $categories_list = [];
    $sql = "SELECT CategoryID, CategoryName, ParentCategoryID FROM categories WHERE ";
    if ($parent_id === null) {
        $sql .= "ParentCategoryID IS NULL ";
    } else {
        $sql .= "ParentCategoryID = ? ";
    }
    $sql .= "ORDER BY CategoryName ASC";

    $stmt = mysqli_prepare($conn, $sql);
    if ($parent_id !== null) {
        mysqli_stmt_bind_param($stmt, "i", $parent_id);
    }

    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        while ($row = mysqli_fetch_assoc($result)) {
            $row['PrefixedName'] = $prefix . $row['CategoryName'];
            $categories_list[] = $row;
            $sub_categories = fetch_all_categories($conn, $row['CategoryID'], $prefix . "&nbsp;&nbsp;&nbsp;&nbsp;");
            $categories_list = array_merge($categories_list, $sub_categories);
        }
    }
    mysqli_stmt_close($stmt);
    return $categories_list;
}

$all_categories_for_dropdown = fetch_all_categories($conn);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['save_category'])) {
        $category_id_form = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $category_name = sanitize_input($_POST['category_name']);
        $parent_category_id = !empty($_POST['parent_category_id']) ? (int)$_POST['parent_category_id'] : null;

        if (empty($category_name)) {
            $errors[] = "Category name is required.";
        }
        if ($parent_category_id !== null && $parent_category_id == $category_id_form && $category_id_form != 0) {
            $errors[] = "A category cannot be its own parent.";
        }


        if (empty($errors)) {
            if ($category_id_form > 0) {
                $sql = "UPDATE categories SET CategoryName = ?, ParentCategoryID = ? WHERE CategoryID = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "sii", $category_name, $parent_category_id, $category_id_form);
                $action_word = 'updated';
            } else {
                $sql = "INSERT INTO categories (CategoryName, ParentCategoryID) VALUES (?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "si", $category_name, $parent_category_id);
                $action_word = 'added';
            }

            if ($stmt && mysqli_stmt_execute($stmt)) {
                $_SESSION['admin_message'] = "<div class='message-success'>Category '{$category_name}' {$action_word} successfully!</div>";
                redirect('admin/manage_categories.php');
            } else {
                $errors[] = "Failed to {$action_word} category: " . ($stmt ? mysqli_stmt_error($stmt) : mysqli_error($conn));
            }
            if ($stmt) mysqli_stmt_close($stmt);
        }
    }
    elseif (isset($_POST['delete_category'])) {
        $category_id_to_delete = (int)$_POST['category_id_delete'];

        $sql_check_sub = "SELECT COUNT(*) as sub_count FROM categories WHERE ParentCategoryID = ?";
        $stmt_check_sub = mysqli_prepare($conn, $sql_check_sub);
        mysqli_stmt_bind_param($stmt_check_sub, "i", $category_id_to_delete);
        mysqli_stmt_execute($stmt_check_sub);
        $res_sub = mysqli_stmt_get_result($stmt_check_sub);
        $sub_count = mysqli_fetch_assoc($res_sub)['sub_count'];
        mysqli_stmt_close($stmt_check_sub);

        if ($sub_count > 0) {
            $errors[] = "Cannot delete category: It has subcategories. Please delete or reassign subcategories first.";
        }

        $sql_check_prod = "SELECT COUNT(*) as prod_count FROM products WHERE CategoryID = ?";
        $stmt_check_prod = mysqli_prepare($conn, $sql_check_prod);
        mysqli_stmt_bind_param($stmt_check_prod, "i", $category_id_to_delete);
        mysqli_stmt_execute($stmt_check_prod);
        $res_prod = mysqli_stmt_get_result($stmt_check_prod);
        $prod_count = mysqli_fetch_assoc($res_prod)['prod_count'];
        mysqli_stmt_close($stmt_check_prod);

        if ($prod_count > 0) {
            $errors[] = "Cannot delete category: It has products assigned to it. Please reassign products first.";
        }

        if (empty($errors)) {
            $sql_delete = "DELETE FROM categories WHERE CategoryID = ?";
            $stmt_delete = mysqli_prepare($conn, $sql_delete);
            mysqli_stmt_bind_param($stmt_delete, "i", $category_id_to_delete);
            if (mysqli_stmt_execute($stmt_delete)) {
                if (mysqli_stmt_affected_rows($stmt_delete) > 0) {
                    $_SESSION['admin_message'] = "<div class='message-success'>Category deleted successfully.</div>";
                } else {
                    $errors[] = "Category not found or already deleted.";
                }
            } else {
                $errors[] = "Error deleting category: " . mysqli_stmt_error($stmt_delete);
            }
            mysqli_stmt_close($stmt_delete);
        }
        
        if (!empty($errors)) {
            $error_msg_html = "<ul>";
            foreach($errors as $err) $error_msg_html .= "<li>{$err}</li>";
            $error_msg_html .= "</ul>";
            $_SESSION['admin_message'] = "<div class='message-error'>{$error_msg_html}</div>";
        }
        redirect('admin/manage_categories.php');
    }
}

if ($action === 'edit' && $edit_id > 0) {
    $sql_edit = "SELECT CategoryID, CategoryName, ParentCategoryID FROM categories WHERE CategoryID = ?";
    $stmt_edit = mysqli_prepare($conn, $sql_edit);
    if ($stmt_edit) {
        mysqli_stmt_bind_param($stmt_edit, "i", $edit_id);
        mysqli_stmt_execute($stmt_edit);
        $result_edit = mysqli_stmt_get_result($stmt_edit);
        $category_to_edit = mysqli_fetch_assoc($result_edit);
        mysqli_stmt_close($stmt_edit);
        if (!$category_to_edit) {
            $_SESSION['admin_message'] = "<div class='message-error'>Category not found for editing.</div>";
            redirect('admin/manage_categories.php');
        }
    }
}

$display_categories = fetch_all_categories($conn);

?>

<h1>Manage Categories</h1>

<?php
echo get_admin_session_message();

if (!empty($errors) && $_SERVER["REQUEST_METHOD"] != "POST") {
    echo "<div class='message-error'><ul>";
    foreach ($errors as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul></div>";
}
?>

<div style="background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px;">
    <h3><?php echo ($action === 'edit' && $category_to_edit) ? 'Edit Category: ' . htmlspecialchars($category_to_edit['CategoryName']) : 'Add New Category'; ?></h3>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?><?php echo ($action === 'edit' && $edit_id) ? '?action=edit&id='.$edit_id : ''; ?>" method="POST">
        <?php if ($action === 'edit' && $category_to_edit): ?>
            <input type="hidden" name="category_id" value="<?php echo $category_to_edit['CategoryID']; ?>">
        <?php endif; ?>
        <div class="form-group">
            <label for="category_name">Category Name:</label>
            <input type="text" name="category_name" id="category_name" value="<?php echo htmlspecialchars($category_to_edit['CategoryName'] ?? ''); ?>" required>
        </div>
        <div class="form-group">
            <label for="parent_category_id">Parent Category (Optional - for subcategory):</label>
            <select name="parent_category_id" id="parent_category_id">
                <option value="">-- None (Top Level Category) --</option>
                <?php foreach ($all_categories_for_dropdown as $cat_opt): ?>
                    <?php
                    if ($action === 'edit' && $category_to_edit && $cat_opt['CategoryID'] == $category_to_edit['CategoryID']) continue;
                    ?>
                    <option value="<?php echo $cat_opt['CategoryID']; ?>" 
                        <?php echo (($category_to_edit['ParentCategoryID'] ?? null) == $cat_opt['CategoryID'] ? 'selected' : ''); ?>>
                        <?php echo htmlspecialchars($cat_opt['PrefixedName']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <button type="submit" name="save_category" class="btn">
                <?php echo ($action === 'edit' && $category_to_edit) ? 'Update Category' : 'Add Category'; ?>
            </button>
            <?php if ($action === 'edit' && $category_to_edit): ?>
                <a href="manage_categories.php" class="btn btn-secondary">Cancel Edit</a>
            <?php endif; ?>
        </div>
    </form>
</div>


<h3>Existing Categories</h3>
<?php if (empty($display_categories)): ?>
    <p class="message-info">No categories found. Add your first category using the form above!</p>
<?php else: ?>
    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Category Name</th>
                <th>Parent ID</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($display_categories as $category): ?>
                <tr>
                    <td><?php echo $category['CategoryID']; ?></td>
                    <td><?php echo $category['PrefixedName'];  ?></td>
                    <td><?php echo $category['ParentCategoryID'] ?? 'N/A (Top)'; ?></td>
                    <td class="actions">
                        <a href="manage_categories.php?action=edit&id=<?php echo $category['CategoryID']; ?>" class="edit-link" title="Edit">&#9998;</a>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this category? This action cannot be undone and may fail if products or subcategories are linked.');">
                            <input type="hidden" name="category_id_delete" value="<?php echo $category['CategoryID']; ?>">
                            <button type="submit" name="delete_category" class="delete-link" title="Delete" style="border:none; background:none; cursor:pointer; padding:0; color:red;">&#10006;</button>
                        </form>
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
