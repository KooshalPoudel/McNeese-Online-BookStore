<?php
// register.php
// account creation page
// Backend & DB insert: Kushal 
// Form HTML/CSS: Alok and Kelsang
// Live JS validation: Rojal (handled in main.js)

$pageTitle = 'Create Account';
require_once '../includes/config.php';

// Kushal: dont let already logged-in users see register page
if (isLoggedIn()) redirect('index.php');

// Kushal: this one per-field errors so we can highlight exact problem
$errors = [];
$formData = [
    'first_name' => '',
    'last_name'  => '',
    'email'      => '',
    'student_id' => '',
    'phone'      => '',
    'address'    => ''
];

//Process registration submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    //collecting input
    $formData['first_name'] = sanitize($_POST['first_name'] ?? '');
    $formData['last_name']  = sanitize($_POST['last_name'] ?? '');
    $formData['email']      = sanitize($_POST['email'] ?? '');
    $formData['student_id'] = sanitize($_POST['student_id'] ?? '');
    $formData['phone']      = sanitize($_POST['phone'] ?? '');
    $formData['address']    = sanitize($_POST['address'] ?? '');
    $password               = $_POST['password'] ?? '';
    $confirm_password       = $_POST['confirm_password'] ?? '';
    $terms                  = isset($_POST['terms']);

    // name validation
    if (empty($formData['first_name']) || strlen($formData['first_name']) < 2) {
        $errors['first_name'] = 'First name must be at least 2 characters.';
    }

    if (empty($formData['last_name'])) {
        $errors['last_name'] = 'Last name is required.';
    }

    // Email validation (Kushal)
    // Kushal: must be valid email AND end with @mcneese.edu
    // Rojal: i also check same thing in main.js to fail faster
    //        but server still validates
    if (empty($formData['email'])) {
        $errors['email'] = 'McNeese email is required.';
    } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address.';
    } elseif (!preg_match('/@mcneese\.edu$/i', $formData['email'])) {
        $errors['email'] = 'Email must be a valid @mcneese.edu address.';
    }

    if (empty($formData['student_id'])) {
        $errors['student_id'] = 'Student ID is required.';
    }

    // Password validation (Kushal)
    // Kushal: min 8 chars, must have uppercase, must have number
    //         keep these rules same as strength bar in main.js
    // Rojal: yeah strength bar uses same rules + extra bonus checks
    if (empty($password) || strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors['password'] = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors['password'] = 'Password must contain at least one number.';
    }

    if ($password !== $confirm_password) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!$terms) {
        $errors['terms'] = 'You must agree to the Terms of Service and Privacy Policy.';
    }

    // Check for duplicates in DB (Kushal)
    // Kushal: only run these queries if email/student_id arent already invalid
    if (empty($errors['email']) && empty($errors['student_id'])) {
        $conn = getConnection();
        $em  = $conn->real_escape_string($formData['email']);
        $sid = $conn->real_escape_string($formData['student_id']);

        // duplicate email check
        $emailCheck = $conn->query("SELECT id FROM users WHERE email = '$em'");
        if ($emailCheck && $emailCheck->num_rows > 0) {
            $errors['email'] = 'An account with this email already exists.';
        }

        // duplicate student id check
        $studentCheck = $conn->query("SELECT id FROM users WHERE student_id = '$sid'");
        if ($studentCheck && $studentCheck->num_rows > 0) {
            $errors['student_id'] = 'This Student ID is already registered.';
        }

        $conn->close();
    }

    // If everything passes, create account
    if (empty($errors)) {
        $conn = getConnection();

        //Kushal:escape every field individually before insert
        $fn   = $conn->real_escape_string($formData['first_name']);
        $ln   = $conn->real_escape_string($formData['last_name']);
        $em   = $conn->real_escape_string($formData['email']);
        $sid  = $conn->real_escape_string($formData['student_id']);
        $ph   = $conn->real_escape_string($formData['phone']);
        $addr = $conn->real_escape_string($formData['address']);

        // kushal: hash password with PHP default bcrypt
        //         never store plain text
        $pw   = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (first_name, last_name, email, student_id, phone, address, password)
                VALUES ('$fn', '$ln', '$em', '$sid', '$ph', '$addr', '$pw')";

        if ($conn->query($sql)) {
            $conn->close();
            // success, set flash msg and bounce to login
            $_SESSION['flash_success'] = 'Account created successfully. Please sign in.';
            redirect('pages/login.php');
        } else {
            $errors['general'] = 'Registration failed. Please try again.';
            $conn->close();
        }
    }
}
?>
<!-- Form layout by Alok and Kelsang -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | <?= SITE_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= SITE_URL ?>/css/style.css">
</head>
<body>

<!-- REGISTER FORM CARD -->
<!-- Alok: centered card layout, simpler than login split panel -->
<div class="auth-page">
    <div class="auth-card">

        <!-- header with logo -->
        <div class="auth-logo">
            <span>📚</span>
            <h1>Create Account</h1>
            <p>Join McNeese Bookstore and start browsing textbooks and supplies.</p>
        </div>

        <!-- general error banner -->
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= htmlspecialchars($errors['general']) ?></div>
        <?php endif; ?>

        <!-- novalidate so our JS handles errors instead of browser native -->
        <form method="POST" action="" id="registerForm" novalidate>

            <!-- Name row -->
            <div class="form-row">
                <div class="form-group">
                    <label for="first_name">First Name <span>*</span></label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="<?= htmlspecialchars($formData['first_name']) ?>"
                        class="<?= isset($errors['first_name']) ? 'error' : '' ?>"
                        autocomplete="given-name"
                        required
                    >
                    <?php if (isset($errors['first_name'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['first_name']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="last_name">Last Name <span>*</span></label>
                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        value="<?= htmlspecialchars($formData['last_name']) ?>"
                        class="<?= isset($errors['last_name']) ? 'error' : '' ?>"
                        autocomplete="family-name"
                        required
                    >
                    <?php if (isset($errors['last_name'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['last_name']) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Email field with hint that turns into error -->
            <!-- Rojal: my JS in main.js toggles between hint and error class -->
            <div class="form-group">
                <label for="email">McNeese Email Address <span>*</span></label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="<?= htmlspecialchars($formData['email']) ?>"
                    class="<?= isset($errors['email']) ? 'error' : '' ?>"
                    autocomplete="email"
                    placeholder="yourname@mcneese.edu"
                    required
                >
                <?php if (isset($errors['email'])): ?>
                    <span class="field-error" id="emailError"><?= htmlspecialchars($errors['email']) ?></span>
                <?php else: ?>
                    <span class="field-hint" id="emailError">Only @mcneese.edu email addresses are allowed</span>
                <?php endif; ?>
            </div>

            <!-- Student ID + Phone row -->
            <div class="form-row">
                <div class="form-group">
                    <label for="student_id">Student ID <span>*</span></label>
                    <input
                        type="text"
                        id="student_id"
                        name="student_id"
                        value="<?= htmlspecialchars($formData['student_id']) ?>"
                        class="<?= isset($errors['student_id']) ? 'error' : '' ?>"
                        required
                    >
                    <?php if (isset($errors['student_id'])): ?>
                        <span class="field-error"><?= htmlspecialchars($errors['student_id']) ?></span>
                    <?php else: ?>
                        <span class="field-hint">Your McNeese student ID is required</span>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <!-- Alok: phone optional, used for delivery updates -->
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="<?= htmlspecialchars($formData['phone']) ?>"
                        autocomplete="tel"
                    >
                    <span class="field-hint">For order updates</span>
                </div>
            </div>

            <!-- Address (optional) -->
            <div class="form-group">
                <label for="address">Shipping Address</label>
                <textarea
                    id="address"
                    name="address"
                    rows="3"
                    style="resize: vertical;"
                ><?= htmlspecialchars($formData['address']) ?></textarea>
                <span class="field-hint">You can add or change this later</span>
            </div>

            <!-- Password+strength bar -->
            <!-- Rojal:strength bar updated live by main.js as user types -->
            <div class="form-group">
                <label for="password">Password <span>*</span></label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="<?= isset($errors['password']) ? 'error' : '' ?>"
                    autocomplete="new-password"
                    required
                >
                <?php if (isset($errors['password'])): ?>
                    <span class="field-error"><?= htmlspecialchars($errors['password']) ?></span>
                <?php endif; ?>

                <!--Rojal: this bar fills up as password gets stronger-->
                <div class="password-strength">
                    <div class="strength-bar">
                        <div class="strength-fill" id="strengthFill"></div>
                    </div>
                    <span class="strength-text" id="strengthText"></span>
                </div>

                <span class="field-hint">Min. 8 characters, one uppercase letter and one number</span>
            </div>

            <!-- Confirm password (live match check via main.js) -->
            <div class="form-group">
                <label for="confirm_password">Confirm Password <span>*</span></label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    class="<?= isset($errors['confirm_password']) ? 'error' : '' ?>"
                    autocomplete="new-password"
                    required
                >
                <span class="field-error" id="confirmError">
                    <?= isset($errors['confirm_password']) ? htmlspecialchars($errors['confirm_password']) : '' ?>
                </span>
            </div>

            <!-- Terms checkbox -->
            <!-- Alok: terms link placeholder for now, no actual page yet -->
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="terms" id="terms"
                           <?= isset($_POST['terms']) ? 'checked' : '' ?>>
                    I agree to the <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>
                </label>
                <?php if (isset($errors['terms'])): ?>
                    <span class="field-error" id="termsError"><?= htmlspecialchars($errors['terms']) ?></span>
                <?php else: ?>
                    <span class="field-error" id="termsError"></span>
                <?php endif; ?>
            </div>

            <div class="form-submit">
                <button type="submit">Create Account</button>
            </div>
        </form>

        <!-- Divider links -->
        <div class="auth-divider">
            Already have an account?
            <a href="<?= SITE_URL ?>/pages/login.php">Sign in</a>
        </div>

        <div class="auth-divider">
            <a href="<?= SITE_URL ?>/index.php">Back to home</a>
        </div>
    </div>
</div>

<script src="<?= SITE_URL ?>/js/main.js"></script>
</body>
</html>