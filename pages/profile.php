<?php
// profile.php
// user profile / account settings page
// Built by Kushal and Rojal 
//  Kushal: profile structure + DB integration
// Rojal: CSS layout, alignment, responsive tweaks
// Edit-mode toggle added later by Kushal
// Kushal: shows user info + lets them update details + change password

$pageTitle = 'My Profile';
require_once '../includes/header.php';

// Kushal: redirect anyone who isnt logged in
if (!isLoggedIn()) redirect('pages/login.php');

$uid     = (int)$_SESSION['user_id'];
$success = '';
$error   = '';
$editing = false;  // Kushal: tracks if we show edit form vs view-only

// Handle profile actions (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Update profile (name, phone, address)
    if ($action === 'update_profile') {
        $editing = true;  // stay in edit mode if validation fails
        $fn   = sanitize($_POST['first_name'] ?? '');
        $ln   = sanitize($_POST['last_name'] ?? '');
        $ph   = sanitize($_POST['phone'] ?? '');
        $addr = sanitize($_POST['address'] ?? '');

        if (empty($fn) || empty($ln)) {
            $error = 'First and last name are required.';
        } else {
            // Kushal: escapew every field before SQL update
            $fn_e   = $conn->real_escape_string($fn);
            $ln_e   = $conn->real_escape_string($ln);
            $ph_e   = $conn->real_escape_string($ph);
            $addr_e = $conn->real_escape_string($addr);
            $conn->query("UPDATE users SET first_name='$fn_e', last_name='$ln_e', phone='$ph_e', address='$addr_e' WHERE id=$uid");

            // Kushal: refresh session values so nav header shows new name....
            $_SESSION['first_name'] = $fn;
            $_SESSION['last_name']  = $ln;

            $success = 'Profile updated successfully.';
            $editing = false;
        }
    }

    // Change password.//
    // Kushal: requires current password to confirm identity
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new_pw  = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_new'] ?? '';
        $userRow = $conn->query("SELECT password FROM users WHERE id=$uid")->fetch_assoc();

        if (!password_verify($current, $userRow['password']))
            $error = 'Current password is incorrect.';
        elseif (strlen($new_pw) < 8)
            $error = 'New password must be at least 8 characters.';
        elseif ($new_pw !== $confirm)
            $error = 'New passwords do not match.';
        else {
            // Kushal: hash new password with PASSWORD_DEFAULT (bcrypt)
            $hashed = password_hash($new_pw, PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password='$hashed' WHERE id=$uid");
            $success = 'Password changed successfully.';
        }
    }
}

// Kushal: suapport ?edit=1 to jump straight into edit mode
//         used by Edit Profile button
if (isset($_GET['edit']) && $_GET['edit'] === '1') {
    $editing = true;
}

// Fetch current user record + order count for sidebar
$user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();

// Kushal: only count placed orders, ignore cancelled ones
$orderCount = $conn->query("SELECT COUNT(*) as cnt FROM orders WHERE user_id=$uid AND status != 'cancelled'")->fetch_assoc()['cnt'];
?>

<!--Page Header -->
<div class="page-header">
    <h1>My Profile</h1>
    <p>Manage your account information and preferences</p>
</div>

<div class="section">

    <!-- success/error banners -->
    <?php if($success): ?>
    <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>
    <?php if($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <!-- Two-column layout: sidebar (left) + panels (right) -->
    <!-- Rojal: tweaked the css so this stacks on mobile -->
    <div class="profile-layout">

        <!-- LEFT: Sidebar with user summary -->
        <div class="profile-sidebar">

            <!-- Avatar circle = user's initials -->
            <!-- Kushal: just first letter of first + last name -->
            <div class="sidebar-top">
                <div class="profile-avatar">
                    <?= strtoupper(substr($user['first_name'],0,1) . substr($user['last_name'],0,1)) ?>
                </div>
                <div class="profile-name">
                    <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>
                </div>
                <div class="profile-email">
                    <?= htmlspecialchars($user['email']) ?>
                </div>
                <span class="profile-role-badge"><?= ucfirst($user['role']) ?></span>
            </div>

            <!-- Sidebar stats (orders / member since / student id) -->
            <!-- Rojal: stat rows align with css flex, looks clean -->
            <div class="sidebar-stats">
                <div class="stat-row">
                    <span class="stat-label">Total orders</span>
                    <span class="stat-value"><?= $orderCount ?></span>
                </div>
                <div class="stat-row">
                    <span class="stat-label">Member since</span>
                    <span class="stat-value">
                        <?= date('M Y', strtotime($user['created_at'])) ?>
                    </span>
                </div>
                <?php if($user['student_id']): ?>
                <div class="stat-row">
                    <span class="stat-label">Student ID</span>
                    <span class="stat-value">
                        <?= htmlspecialchars($user['student_id']) ?>
                    </span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar quick links -->
            <div class="sidebar-links">
                <a href="orders.php" class="sidebar-link">
                    <svg width="15" height="15" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2
                                 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                        <polyline points="14 2 14 8 20 8"/>
                        <line x1="16" y1="13" x2="8" y2="13"/>
                        <line x1="16" y1="17" x2="8" y2="17"/>
                    </svg>
                    My Orders
                </a>
                <a href="cart.php" class="sidebar-link">
                    <svg width="15" height="15" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round"
                         stroke-linejoin="round">
                        <circle cx="9" cy="21" r="1"/>
                        <circle cx="20" cy="21" r="1"/>
                        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2
                                 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                    </svg>
                    Shopping Cart
                </a>
                <a href="<?= SITE_URL ?>/logout.php" class="sidebar-link danger">
                    <svg width="15" height="15" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    Sign Out
                </a>
            </div>
        </div>

        <!-- RIGHT: Panels -->
        <div class="panels">

            <!-- Personal Information panel -->
            <!-- Toggles between VIEW mode (disabled inputs) -->
            <!--and EDIT mode (form with save/cancel)-->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-header-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div>
                        <div class="panel-title">Personal Information</div>
                        <div class="panel-subtitle">
                            <?= $editing ? 'Edit your details below' : 'Your name, contact details, and address' ?>
                        </div>
                    </div>

                    <!-- Edit Profile button only shown in view mode -->
                    <?php if(!$editing): ?>
                    <a href="profile.php?edit=1" class="btn-edit-profile">
                        <svg width="13" height="13" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2
                                     0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3
                                     3L12 15l-4 1 1-4 9.5-9.5z"/>
                        </svg>
                        Edit Profile
                    </a>
                    <?php endif; ?>
                </div>

                <div class="panel-body">
                    <?php if($editing): ?>

                    <!-- EDIT MODE -->
                    <!-- Kushal: form with editable fields -->
                    <!--         email and student_id stay disabled -->
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_profile">
                        <div class="form-row">
                            <div class="form-group">
                                <label>First name <span>*</span></label>
                                <input type="text" name="first_name"
                                       value="<?= htmlspecialchars($user['first_name']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Last name <span>*</span></label>
                                <input type="text" name="last_name"
                                       value="<?= htmlspecialchars($user['last_name']) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email address</label>
                            <!-- Kushal: email locked, cant change without admin -->
                            <input type="email"
                                   value="<?= htmlspecialchars($user['email']) ?>" disabled>
                            <span class="field-hint">
                                Email cannot be changed &mdash; contact admin if needed
                            </span>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Student ID</label>
                                <input type="text"
                                       value="<?= htmlspecialchars($user['student_id'] ?? '') ?>" disabled>
                            </div>
                            <div class="form-group">
                                <label>Phone number</label>
                                <input type="tel" name="phone"
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                                       placeholder="(337) 555-0000">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Shipping address</label>
                            <textarea name="address" rows="3"
                                      placeholder="Your shipping address"
                                      style="resize: vertical;"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-save">Save changes</button>
                            <a href="profile.php" class="btn-cancel">Cancel</a>
                        </div>
                    </form>

                    <?php else: ?>

                    <!-- VIEW MODE -- disabled inputs -->
                    <!-- Rojal: using disabled inputs not plain <p> -->
                    <!--     so layout stays same as edit mode -->
                    <div class="form-row">
                        <div class="form-group">
                            <label>First name</label>
                            <input type="text"
                                   value="<?= htmlspecialchars($user['first_name']) ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Last name</label>
                            <input type="text"
                                   value="<?= htmlspecialchars($user['last_name']) ?>" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email address</label>
                        <input type="email"
                               value="<?= htmlspecialchars($user['email']) ?>" disabled>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Student ID</label>
                            <input type="text"
                                   value="<?= htmlspecialchars($user['student_id'] ?? '') ?>" disabled>
                        </div>
                        <div class="form-group">
                            <label>Phone number</label>
                            <input type="tel"
                                   value="<?= htmlspecialchars($user['phone'] ?? 'Not set') ?>" disabled>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Shipping address</label>
                        <input type="text"
                               value="<?= htmlspecialchars($user['address'] ?? 'Not set') ?>" disabled>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Change Password panel -->
            <!-- Kushal: standard 3-field flow - current / new / confirm -->
            <div class="panel">
                <div class="panel-header">
                    <div class="panel-header-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round"
                             stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                    </div>
                    <div>
                        <div class="panel-title">Change Password</div>
                        <div class="panel-subtitle">Update your login credentials</div>
                    </div>
                </div>
                <div class="panel-body">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="change_password">
                        <div class="form-group">
                            <label>Current password <span>*</span></label>
                            <input type="password" name="current_password"
                                   placeholder="Enter your current password" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>New password <span>*</span></label>
                                <input type="password" name="new_password"
                                       placeholder="Min. 8 characters" required>
                            </div>
                            <div class="form-group">
                                <label>Confirm new password <span>*</span></label>
                                <input type="password" name="confirm_new"
                                       placeholder="Repeat new password" required>
                            </div>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-save">Update password</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>