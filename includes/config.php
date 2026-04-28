<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'mcneese_bookstore');

define('SITE_NAME', 'McNeese Online Bookstore');
define('SITE_URL', 'http://localhost/mcneese_bookstore');

// Session timeout in seconds (3 minutes)
define('SESSION_TIMEOUT', 180);

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("<div style='color:red;padding:20px;font-family:sans-serif;'>
            <h3>Database Connection Failed</h3>
            <p>Error: " . $conn->connect_error . "</p>
        </div>");
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout check
if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT) {
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['flash_success'] = 'Your session has expired. Please sign in again.';
        header("Location: " . SITE_URL . "/pages/login.php");
        exit();
    }
}
$_SESSION['last_activity'] = time();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: " . SITE_URL . "/" . $url);
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}
?>