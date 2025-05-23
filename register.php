<?php
require_once 'config.php';

if (is_user_logged_in()) {
    redirect('index.php');
}

$first_name = $last_name = $email = $contact_number = $password = $confirm_password = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = sanitize_input($_POST['first_name']);
    $last_name = sanitize_input($_POST['last_name']);
    $email = sanitize_input($_POST['email']);
    $contact_number = sanitize_input($_POST['contact_number']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($first_name)) {
        $errors[] = "First name is required.";
    }
    if (empty($last_name)) {
        $errors[] = "Last name is required.";
    }
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $sql_check_email = "SELECT UserID FROM users WHERE Email = ?";
        if ($stmt_check_email = mysqli_prepare($conn, $sql_check_email)) {
            mysqli_stmt_bind_param($stmt_check_email, "s", $param_email);
            $param_email = $email;
            if (mysqli_stmt_execute($stmt_check_email)) {
                mysqli_stmt_store_result($stmt_check_email);
                if (mysqli_stmt_num_rows($stmt_check_email) == 1) {
                    $errors[] = "This email address is already registered.";
                }
            } else {
                $errors[] = "Oops! Something went wrong. Please try again later. (Email check failed)";
            }
            mysqli_stmt_close($stmt_check_email);
        } else {
            $errors[] = "Database error. Please try again later. (Prepare failed)";
        }
    }

    if (empty($errors)) {
        $sql_insert_user = "INSERT INTO users (FirstName, LastName, Email, PasswordHash, ContactNumber) VALUES (?, ?, ?, ?, ?)";
        if ($stmt_insert_user = mysqli_prepare($conn, $sql_insert_user)) {
            mysqli_stmt_bind_param($stmt_insert_user, "sssss", $param_first_name, $param_last_name, $param_email, $param_password_hash, $param_contact_number);

            $param_first_name = $first_name;
            $param_last_name = $last_name;
            $param_email = $email;
            $param_password_hash = password_hash($password, PASSWORD_DEFAULT);
            $param_contact_number = $contact_number;

            if (mysqli_stmt_execute($stmt_insert_user)) {
                display_message("Registration successful! You can now login.", "success");
                redirect('login.php');
            } else {
                display_message("Something went wrong. Please try again later. (Registration failed)", "error");
            }
            mysqli_stmt_close($stmt_insert_user);
        } else {
             display_message("Database error. Please try again later. (Prepare insert failed)", "error");
        }
    }

    if (!empty($errors)) {
        $error_message_html = "<ul>";
        foreach ($errors as $error) {
            $error_message_html .= "<li>" . htmlspecialchars($error) . "</li>";
        }
        $error_message_html .= "</ul>";
        $_SESSION['form_errors'] = $error_message_html;
        $_SESSION['form_data'] = $_POST;
        redirect('register.php');
    }
}

$form_errors_html = '';
if (isset($_SESSION['form_errors'])) {
    $form_errors_html = $_SESSION['form_errors'];
    unset($_SESSION['form_errors']);
}
$form_data = [];
if (isset($_SESSION['form_data'])) {
    $form_data = $_SESSION['form_data'];
    foreach ($form_data as $key => $value) {
        if ($key !== 'password' && $key !== 'confirm_password') {
            $form_data[$key] = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        } else {
            $form_data[$key] = '';
        }
    }
    unset($_SESSION['form_data']);
}

require_once 'header.php';
?>

<h2>User Registration</h2>

<?php
if (!empty($form_errors_html)) {
    echo "<div class='message-error'>" . $form_errors_html . "</div>";
}
?>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <div class="form-group">
        <label for="first_name">First Name:</label>
        <input type="text" name="first_name" id="first_name" value="<?php echo $form_data['first_name'] ?? ''; ?>" required>
    </div>
    <div class="form-group">
        <label for="last_name">Last Name:</label>
        <input type="text" name="last_name" id="last_name" value="<?php echo $form_data['last_name'] ?? ''; ?>" required>
    </div>
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" value="<?php echo $form_data['email'] ?? ''; ?>" required>
    </div>
    <div class="form-group">
        <label for="contact_number">Contact Number (Optional):</label>
        <input type="text" name="contact_number" id="contact_number" value="<?php echo $form_data['contact_number'] ?? ''; ?>">
    </div>
    <div class="form-group">
        <label for="password">Password:</label>
        <input type="password" name="password" id="password" required>
        <small>Password must be at least 6 characters long.</small>
    </div>
    <div class="form-group">
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" name="confirm_password" id="confirm_password" required>
    </div>
    <div class="form-group">
        <button type="submit" class="btn">Register</button>
    </div>
</form>
<p>Already have an account? <a href="login.php">Login here</a>.</p>

<?php
require_once 'footer.php';
mysqli_close($conn);
?>