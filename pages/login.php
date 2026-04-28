<?php
$pageTitle = 'Login';
require_once '../includes/config.php';

if (isLoggedIn()) redirect('index.php');

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $conn = getConnection();
        $em   = $conn->real_escape_string($email);
        $result = $conn->query("SELECT * FROM users WHERE email='$em' LIMIT 1");

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name']  = $user['last_name'];
                $_SESSION['email']      = $user['email'];
                $_SESSION['role']       = $user['role'];
                $conn->close();
                $_SESSION['flash_success'] = 'Welcome back, ' . $user['first_name'] . '!';
                redirect('index.php');
            } else {
                $error = 'Incorrect password. Please try again.';
            }
        } else {
            $error = 'No account found with that email address.';
        }
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body class="login-body">

<!-- Left Branding Panel -->
<div class="auth-left">
    <div class="auth-left-logo">
        <div class="auth-left-mark">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        </div>
        <span class="auth-left-name">McNeese Bookstore</span>
    </div>

    <div class="auth-left-body">
        <h2>Your campus store,<br><em>always open</em></h2>
        <p>Access textbooks, office supplies, and more — available 24/7 for McNeese State University students.</p>
    </div>

    <div class="auth-left-footer">MCNEESE STATE UNIVERSITY &mdash; CSCI 413</div>
</div>

<!-- Right Form Panel -->
<div class="auth-right">
    <div class="auth-form-wrap">

        <div class="auth-heading">
            <h1>Sign in</h1>
            <p>Don't have an account? <a href="<?= SITE_URL ?>/pages/register.php">Create one</a></p>
        </div>

        <?php if($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if(isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['flash_success'] ?></div>
        <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <form method="POST" action="" class="login-form">
            <div class="form-group">
                <label for="email">Email address <span>*</span></label>
                <input type="email" id="email" name="email"
                    value="<?= htmlspecialchars($email) ?>"
                    placeholder="yourname@mcneese.edu"
                    autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password <span>*</span></label>
                <input type="password" id="password" name="password"
                    placeholder="Enter your password"
                    autocomplete="current-password" required>
            </div>

            <div class="form-extras">
                <label class="remember-label">
                    <input type="checkbox" name="remember">
                    Remember me
                </label>
                <a href="#" class="forgot-link">Forgot password?</a>
            </div>

            <button type="submit" class="btn-submit">Sign in</button>
        </form>

        <div class="login-divider">
            New to McNeese Bookstore? <a href="<?= SITE_URL ?>/pages/register.php">Create an account</a>
        </div>

        <a href="<?= SITE_URL ?>/index.php" class="back-link">
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
            Back to home
        </a>
    </div>
</div>

<script src="<?= SITE_URL ?>/js/main.js"></script>
</body>
</html>