<?php
require_once __DIR__ . '/../config.php';

if (is_admin_logged_in()) {
    redirect('admin/dashboard.php');
}

$username_input = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username_input = sanitize_input($_POST['username']);
    $password_input = $_POST['password'];

    if (empty($username_input)) $errors[] = "Username is required.";
    if (empty($password_input)) $errors[] = "Password is required.";

    if (empty($errors)) {
        $sql = "SELECT AdminID, Username, PasswordHash, Role FROM administrators WHERE Username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $username_input_param);
            $username_input_param = $username_input;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $admin_id, $admin_username_db, $hashed_password_from_db, $admin_role);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password_input, $hashed_password_from_db)) {
                            session_regenerate_id(true);
                            $_SESSION['admin_id'] = $admin_id;
                            $_SESSION['admin_username'] = $admin_username_db;
                            $_SESSION['admin_role'] = $admin_role;
                            
                            $update_login_sql = "UPDATE administrators SET LastLogin = CURRENT_TIMESTAMP WHERE AdminID = ?";
                            if ($update_stmt = mysqli_prepare($conn, $update_login_sql)) {
                                mysqli_stmt_bind_param($update_stmt, "i", $admin_id);
                                mysqli_stmt_execute($update_stmt);
                                mysqli_stmt_close($update_stmt);
                            }
                            redirect('admin/dashboard.php');
                        } else {
                            $errors[] = "Invalid username or password.";
                        }
                    }
                } else {
                    $errors[] = "Invalid username or password.";
                }
            } else {
                $errors[] = "Login execution failed. DB Error: " . mysqli_stmt_error($stmt);
            }
            mysqli_stmt_close($stmt);
        } else {
            $errors[] = "Database statement preparation failed. DB Error: " . mysqli_error($conn);
        }
    }
    
    if (!empty($errors)) {
        $_SESSION['admin_login_errors'] = $errors;
        $_SESSION['admin_login_username'] = $username_input;
        redirect('admin/index.php'); 
    }
}

$login_errors_display = [];
if (isset($_SESSION['admin_login_errors'])) {
    $login_errors_display = $_SESSION['admin_login_errors'];
    unset($_SESSION['admin_login_errors']);
}
$form_username_value = '';
if (isset($_SESSION['admin_login_username'])) {
    $form_username_value = htmlspecialchars($_SESSION['admin_login_username'], ENT_QUOTES, 'UTF-8');
    unset($_SESSION['admin_login_username']);
}

require_once 'admin_header.php'; 
?>

<div class="admin-login-form">
    <h2>Admin Portal Login</h2>
    <?php
    if (!empty($login_errors_display)) {
        echo "<div class='message-error'><ul>";
        foreach ($login_errors_display as $error) {
            echo "<li>" . htmlspecialchars($error) . "</li>";
        }
        echo "</ul></div>";
    }
    echo get_admin_session_message();
    ?>
    <form action="<?php echo htmlspecialchars(BASE_URL . 'admin/index.php'); ?>" method="post">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" name="username" id="username" value="<?php echo $form_username_value; ?>" required>
        </div>
        <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" required>
        </div>
        <div class="form-group">
            <button type="submit" class="btn" style="width:100%;">Login</button>
        </div>
    </form>
</div>

<?php
require_once 'admin_footer.php'; 
if ($conn) mysqli_close($conn);
?>