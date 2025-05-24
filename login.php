<?php
require_once 'config.php';

if (is_user_logged_in()) {
    redirect('index.php');
}

$email = $password = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize_input($_POST['email']);
    $password_input = $_POST['password'];

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    if (empty($password_input)) {
        $errors[] = "Password is required.";
    }

    if (empty($errors)) {
        $sql = "SELECT UserID, FirstName, PasswordHash FROM users WHERE Email = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            $param_email = $email;

            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);

                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $user_id, $first_name, $hashed_password_from_db);
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password_input, $hashed_password_from_db)) {
                            session_regenerate_id(true);

                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['user_email'] = $email;
                            $_SESSION['user_first_name'] = $first_name;

                            display_message("Login successful! Welcome back, " . htmlspecialchars($first_name) . ".", "success");
                            redirect('index.php');
                        } else {
                            $errors[] = "Invalid email or password.";
                        }
                    }
                } else {
                    $errors[] = "Invalid email or password.";
                }
            } else {
                $errors[] = "Oops! Something went wrong. Please try again later. (Execute failed)";
            }
            mysqli_stmt_close($stmt);
        } else {
             $errors[] = "Database error. Please try again later. (Prepare failed)";
        }
    }

    if (!empty($errors)) {
        $error_message_html = "<ul>";
        foreach ($errors as $error) {
            $error_message_html .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $error_message_html .= "</ul>";
        $_SESSION['form_errors'] = $error_message_html;
        $_SESSION['form_data'] = ['email' => $email];
        redirect('login.php');
    }
}

$form_errors_html = '';
if (isset($_SESSION['form_errors'])) {
    $form_errors_html = $_SESSION['form_errors'];
    unset($_SESSION['form_errors']);
}
$form_data_email = '';
if (isset($_SESSION['form_data']['email'])) {
    $form_data_email = htmlspecialchars($_SESSION['form_data']['email'], ENT_QUOTES, 'UTF-8');
    unset($_SESSION['form_data']);
}

require_once 'header.php';
?>

<h2>User Login</h2>

<?php
if (!empty($form_errors_html)) {
    echo "<div class='message-error'>" . $form_errors_html . "</div>";
}
?>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" value="<?php echo $form_data_email; ?>" required>
    </div>
    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
    </div>
    <div class="form-group">
        <button type="submit" class="btn">Login</button>
    </div>
</form>
<p>Don't have an account? <a href="register.php">Register here</a>.</p>
<p><a href="register.php">Forgot Password?</a></p> 

<?php
require_once 'footer.php';
mysqli_close($conn);
?>
