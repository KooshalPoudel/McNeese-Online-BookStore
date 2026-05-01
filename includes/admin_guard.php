<?php
/**
 * ADMIN ACCESS GUARD
 * Include at top of every admin page after config.php
 *
 * Checks 3 things in order:
 *   1. user logged in -> else go to login
 *   2. session role is admin -> else go home (hide admin exists)
 *   3. DB role is still admin -> else kill session
 *
 * Rule 3 matters: if admin gets demoted in DB, old session shouldn't
 * still work. Re-check every request.
 *
 * Author: Kushal 
 */

// Kushal: load config if not already loaded
if (!defined('SITE_URL')) {
    require_once __DIR__ . '/config.php';
}

// 1. Must be logged in
// Kushal: not logged in=bounce to login with flash msg
if (!isLoggedIn()) {
    $_SESSION['flash_success'] = 'Please sign in to continue.';
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit();
}

// 2. Session musst say admin
// Kushal: silent redirect to home so non-admins dont know admin pages exist
// Rojal: smart, regular users wont even try /admin url
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}

// 3. Re-check against database
// Kushal: defends against demoted admin keeping old session
// Alok: yeah otherwise old sessions stay admin forever, this is important
$adminGuardConn = getConnection();
$adminGuardUid  = (int)$_SESSION['user_id'];
$roleCheck = $adminGuardConn->query("SELECT role FROM users WHERE id = $adminGuardUid LIMIT 1");

// user got deleted, kill session
if (!$roleCheck || $roleCheck->num_rows !== 1) {
    $adminGuardConn->close();
    session_unset();
    session_destroy();
    header('Location: ' . SITE_URL . '/pages/login.php');
    exit();
}

// check DB role matches admin
$adminGuardRow = $roleCheck->fetch_assoc();
if ($adminGuardRow['role'] !== 'admin') {
    // Kushal: session said admin but DB says no, kill it
    $adminGuardConn->close();
    session_unset();
    session_destroy();
    header('Location: ' . SITE_URL . '/index.php');
    exit();
}
$adminGuardConn->close();

// access granted, page continues
?>