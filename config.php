<?php
// Database Configuration and Utility Functions

// --- Error Reporting (Enable for Development) ---
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Session Management ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

//Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');   
define('DB_PASSWORD', '');       
define('DB_NAME', 'online_shopping_portal_bca');

//MySQL connection
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conn === false) {
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

//It correctly identify the root URL of project.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];

$project_root_path_segment = rtrim(str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('\\', '/', __DIR__)), '/');
define('BASE_URL', $protocol . $host . $project_root_path_segment . '/');

define('UPLOAD_DIR', __DIR__ . '/uploads/'); 
define('UPLOAD_URL', BASE_URL . 'uploads/'); 

// Ensure uploads directory exists and is writable
if (!is_dir(UPLOAD_DIR)) {
    // Attempt to create it
    if (!mkdir(UPLOAD_DIR, 0775, true) && !is_dir(UPLOAD_DIR)) { 
        die('Failed to create uploads directory. Please create it manually and ensure it is writable by the web server.');
    }
}

function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data); 
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function redirect($url_from_root) {
    header("Location: " . BASE_URL . $url_from_root);
    exit();
}

function display_message($message, $type = 'info') {
    $class = 'message-info'; 
    if ($type === 'success') {
        $class = 'message-success';
    } elseif ($type === 'error') {
        $class = 'message-error';
    }
    $_SESSION['message'] = "<div class='{$class}'>{$message}</div>";
}

function get_session_message() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return '';
}

function display_admin_message($message, $type = 'info') {
    $class = 'message-info'; // Default
    if ($type === 'success') {
        $class = 'message-success';
    } elseif ($type === 'error') {
        $class = 'message-error';
    }
    $_SESSION['admin_message'] = "<div class='{$class}'>{$message}</div>";
}

function get_admin_session_message() {
    if (isset($_SESSION['admin_message'])) {
        $message = $_SESSION['admin_message'];
        unset($_SESSION['admin_message']);
        return $message;
    }
    return '';
}

function is_user_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin_logged_in() {
    return isset($_SESSION['admin_id']) && isset($_SESSION['admin_username']);
}

function require_user_login($redirect_target_after_login = null) {
    if (!is_user_logged_in()) {
        display_message("You need to login to access this page.", "error");
        $login_url = 'login.php';
        if ($redirect_target_after_login) {
            $login_url .= '?redirect=' . urlencode($redirect_target_after_login);
        }
        redirect($login_url); 
    }
}

function require_admin_login($admin_login_page = 'index.php') {
    if (!is_admin_logged_in()) {
        display_admin_message("You need to login to access the admin panel.", "error");
        redirect('admin/' . $admin_login_page); 
    }
}
?>
