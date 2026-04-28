<?php
require_once __DIR__ . '/config.php';
$cartCount = 0;
$conn = getConnection();
if (isLoggedIn()) {
    $uid = $_SESSION['user_id'];
    $r = $conn->query("SELECT SUM(quantity) as cnt FROM cart WHERE user_id=$uid");
    $row = $r->fetch_assoc();
    $cartCount = $row['cnt'] ?? 0;
}
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? $pageTitle . ' | ' . SITE_NAME : SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>

<body>

    <header class="site-header">
        <div class="header-inner">

            <a href="<?= SITE_URL ?>/index.php" class="logo">
                <div class="logo-mark">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" />
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" />
                    </svg>
                </div>
                <div class="logo-text">
                    <span class="logo-main">McNeese</span>
                    <span class="logo-sub">Bookstore</span>
                </div>
            </a>

            <nav class="main-nav">
                <a href="<?= SITE_URL ?>/index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">Home</a>
                <a href="<?= SITE_URL ?>/pages/books.php" class="<?= $currentPage === 'books.php' ? 'active' : '' ?>">Products</a>
                <a href="<?= SITE_URL ?>/pages/search.php" class="<?= $currentPage === 'search.php' ? 'active' : '' ?>">Search</a>
                <?php if (isLoggedIn()): ?>
                    <a href="<?= SITE_URL ?>/pages/orders.php" class="<?= $currentPage === 'orders.php' ? 'active' : '' ?>">Orders</a>
                <?php endif; ?>
                <?php if (isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="<?= SITE_URL ?>/pages/admin/orders.php" class="nav-admin-link <?= strpos($_SERVER['PHP_SELF'], '/admin/') !== false ? 'active' : '' ?>">
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:-1px;margin-right:3px;"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        Admin
                    </a>
                <?php endif; ?>
            </nav>

            <div class="header-actions">
                <?php if (isLoggedIn()): ?>
                    <a href="<?= SITE_URL ?>/pages/cart.php" class="icon-btn" title="Cart">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="9" cy="21" r="1" />
                            <circle cx="20" cy="21" r="1" />
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                        </svg>
                        <?php if ($cartCount > 0): ?>
                            <span class="cart-badge"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </a>

                    <div class="user-menu" id="userMenu">
                        <div class="user-trigger">
                            <div class="user-avatar"><?= strtoupper(substr($_SESSION['first_name'], 0, 1)) ?></div>
                            <span class="user-name-text"><?= htmlspecialchars($_SESSION['first_name']) ?></span>
                            <span class="user-chevron">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="6 9 12 15 18 9" />
                                </svg>
                            </span>
                        </div>
                        <div class="dropdown">
                            <a href="<?= SITE_URL ?>/pages/profile.php">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                My Profile
                            </a>
                            <a href="<?= SITE_URL ?>/pages/orders.php">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                                    <polyline points="14 2 14 8 20 8" />
                                    <line x1="16" y1="13" x2="8" y2="13" />
                                    <line x1="16" y1="17" x2="8" y2="17" />
                                    <polyline points="10 9 9 9 8 9" />
                                </svg>
                                My Orders
                            </a>

                            <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                            <a href="<?= SITE_URL ?>/pages/admin/orders.php">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                </svg>
                                Admin Dashboard
                            </a>
                            <?php endif; ?>

                            <a href="<?= SITE_URL ?>/logout.php" class="logout-link">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <polyline points="16 17 21 12 16 7" />
                                    <line x1="21" y1="12" x2="9" y2="12" />
                                </svg>
                                Sign Out
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <a href="<?= SITE_URL ?>/pages/login.php" class="btn-ghost">Sign in</a>
                    <a href="<?= SITE_URL ?>/pages/register.php" class="btn-primary">Get started</a>
                <?php endif; ?>
            </div>

            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menu">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="3" y1="12" x2="21" y2="12" />
                    <line x1="3" y1="6" x2="21" y2="6" />
                    <line x1="3" y1="18" x2="21" y2="18" />
                </svg>
            </button>
        </div>

        <nav class="mobile-nav" id="mobileNav">
            <a href="<?= SITE_URL ?>/index.php">Home</a>
            <a href="<?= SITE_URL ?>/pages/books.php">Books</a>
            <a href="<?= SITE_URL ?>/pages/search.php">Search</a>

            <?php if (isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/pages/cart.php">Cart <?= $cartCount > 0 ? "($cartCount)" : '' ?></a>
                <a href="<?= SITE_URL ?>/pages/orders.php">My Orders</a>
                <a href="<?= SITE_URL ?>/pages/profile.php">Profile</a>
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                <a href="<?= SITE_URL ?>/pages/admin/orders.php">Admin Dashboard</a>
                <?php endif; ?>
                <a href="<?= SITE_URL ?>/logout.php" class="logout-link">Sign Out</a>
                
            <?php else: ?>
                <a href="<?= SITE_URL ?>/pages/login.php">Sign in</a>
                <a href="<?= SITE_URL ?>/pages/register.php">Get started</a>
            <?php endif; ?>
        </nav>
    </header>

    <main class="main-content">