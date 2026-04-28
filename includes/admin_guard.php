<?php
/**
 * ADMIN ACCESS GUARD
 * ------------------
 * Include this file at the top of EVERY admin page, immediately after config.php.
 *
 * It enforces three rules in order:
 *   1. User must be logged in.         -> else redirect to login
 *   2. Session role must be 'admin'.   -> else redirect to homepage (404-style hide)
 *   3. Database role must be 'admin'.  -> else kill session and redirect
 *
 * Rule #3 is important: if an admin's role is demoted in the database,
 * their stale session token shouldn't keep letting them in. We re-verify
 * every request.
 */

// If config wasn't loaded yet, load it
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/config.php';
}

// 1. Must be logged in
if (!isLoggedIn()) {
    $_SESSION['flash_success'] = 'Please sign in to continue.';
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit();
}

// 2. Session must claim admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Redirect silently to homepage — don't reveal the admin area exists
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// 3. Re-verify against the database (defends against stale sessions after demotion)
$adminGuardConn = getConnection();
$adminGuardUid  = (int)$_SESSION['user_id'];
$roleCheck = $adminGuardConn->query("SELECT role FROM users WHERE id = $adminGuardUid LIMIT 1");

if (!$roleCheck || $roleCheck->num_rows !== 1) {
    $adminGuardConn->close();
    session_unset();
    session_destroy();
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit();
}

$adminGuardRow = $roleCheck->fetch_assoc();
if ($adminGuardRow['role'] !== 'admin') {
    // Session says admin but DB says otherwise — kill session
    $adminGuardConn->close();
    session_unset();
    session_destroy();
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

$adminGuardConn->close();
// Access granted — page continues
?>
