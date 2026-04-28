<?php
$pageTitle = 'Checkout';
require_once '../includes/config.php';
if (!isLoggedIn()) { redirect('pages/login.php'); }

$conn = getConnection();
$uid  = (int)$_SESSION['user_id'];

// US states list
$usStates = [
    'AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas',
    'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware',
    'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho',
    'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas',
    'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland',
    'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi',
    'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada',
    'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York',
    'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma',
    'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina',
    'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah',
    'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia',
    'WI' => 'Wisconsin', 'WY' => 'Wyoming', 'DC' => 'District of Columbia'
];

// Fetch cart — include cover_image, author, and current stock
$cartItems = [];
$total = 0;
$res = $conn->query("SELECT c.quantity, b.id as book_id, b.title, b.author, b.price, b.cover_image, b.stock FROM cart c JOIN books b ON c.book_id=b.id WHERE c.user_id=$uid");
while ($r = $res->fetch_assoc()) {
    $r['subtotal'] = $r['price'] * $r['quantity'];
    $total += $r['subtotal'];
    $cartItems[] = $r;
}

if (empty($cartItems)) {
    $conn->close();
    redirect('pages/cart.php');
}

$user = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
$shippingCost = $total > 50 ? 0 : 5.99;
$tax = $total * 0.085;
$grandTotal = $total + $shippingCost + $tax;

// Per-field errors
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName  = sanitize($_POST['full_name'] ?? '');
    $phone     = sanitize($_POST['phone'] ?? '');
    $street    = sanitize($_POST['street'] ?? '');
    $apt       = sanitize($_POST['apt'] ?? '');
    $city      = sanitize($_POST['city'] ?? '');
    $state     = sanitize($_POST['state'] ?? '');
    $zip       = sanitize($_POST['zip'] ?? '');
    $cardNum   = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $cardExp   = trim($_POST['card_expiry'] ?? '');
    $cardCvc   = trim($_POST['card_cvc'] ?? '');

    // Shipping validation
    if (empty($fullName)) {
        $errors['full_name'] = 'Full name is required.';
    } elseif (strlen($fullName) < 3) {
        $errors['full_name'] = 'Please enter your full name.';
    }

    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required.';
    } elseif (!preg_match('/^[\d\s\-\+\(\)]{10,20}$/', $phone)) {
        $errors['phone'] = 'Please enter a valid phone number.';
    }

    if (empty($street)) {
        $errors['street'] = 'Street address is required.';
    }

    if (empty($city)) {
        $errors['city'] = 'City is required.';
    }

    if (empty($state) || !isset($usStates[$state])) {
        $errors['state'] = 'Please select a state.';
    }

    if (empty($zip)) {
        $errors['zip'] = 'ZIP code is required.';
    } elseif (!preg_match('/^\d{5}(-\d{4})?$/', $zip)) {
        $errors['zip'] = 'ZIP code must be 5 digits (or 5+4 format).';
    }

    // Card number
    if (empty($cardNum)) {
        $errors['card_number'] = 'Card number is required.';
    } elseif (!ctype_digit($cardNum)) {
        $errors['card_number'] = 'Card number must contain digits only.';
    } elseif (strlen($cardNum) < 13) {
        $errors['card_number'] = 'Card number must be at least 13 digits.';
    } elseif (strlen($cardNum) > 19) {
        $errors['card_number'] = 'Card number must be at most 19 digits.';
    }

    // Expiry
    if (empty($cardExp)) {
        $errors['card_expiry'] = 'Expiry date is required.';
    } elseif (!preg_match('/^(\d{1,2})\/(\d{2})$/', $cardExp, $m)) {
        $errors['card_expiry'] = 'Expiry must be in MM/YY format (e.g. 12/27).';
    } else {
        $expMonth = (int)$m[1];
        $expYear  = 2000 + (int)$m[2];
        $currentYear  = (int)date('Y');
        $currentMonth = (int)date('n');

        if ($expMonth < 1 || $expMonth > 12) {
            $errors['card_expiry'] = 'Invalid month — must be between 01 and 12.';
        } elseif ($expYear < $currentYear) {
            $errors['card_expiry'] = 'Card has expired — year is in the past.';
        } elseif ($expYear === $currentYear && $expMonth < $currentMonth) {
            $errors['card_expiry'] = 'Card has expired — month is in the past.';
        }
    }

    // CVC
    if (empty($cardCvc)) {
        $errors['card_cvc'] = 'CVC is required.';
    } elseif (!ctype_digit($cardCvc)) {
        $errors['card_cvc'] = 'CVC must contain digits only.';
    } elseif (strlen($cardCvc) < 3) {
        $errors['card_cvc'] = 'CVC must be at least 3 digits.';
    } elseif (strlen($cardCvc) > 4) {
        $errors['card_cvc'] = 'CVC must be at most 4 digits.';
    }

    // Check stock availability before placing order
    if (empty($errors)) {
        foreach ($cartItems as $item) {
            if ($item['quantity'] > $item['stock']) {
                $errors['stock'] = 'Not enough stock for "' . htmlspecialchars($item['title']) . '". Only ' . $item['stock'] . ' left. Please update your cart.';
                break;
            }
        }
    }

    // If no errors, place order
    if (empty($errors)) {
        // Escape for SQL
        $fn_e     = $conn->real_escape_string($fullName);
        $ph_e     = $conn->real_escape_string($phone);
        $st_e     = $conn->real_escape_string($street);
        $apt_e    = $conn->real_escape_string($apt);
        $city_e   = $conn->real_escape_string($city);
        $state_e  = $conn->real_escape_string($state);
        $zip_e    = $conn->real_escape_string($zip);

        // Generate a unique, human-friendly order number
        // Format: MC-YYYY-XXXXXX  (e.g. MC-2026-A7B2X9)
        do {
            $random = strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
            $orderNumber = 'MC-' . date('Y') . '-' . $random;
            $dupeCheck = $conn->query("SELECT id FROM orders WHERE order_number='$orderNumber' LIMIT 1");
        } while ($dupeCheck && $dupeCheck->num_rows > 0);
 
        $on_e = $conn->real_escape_string($orderNumber);
 
        // Insert into orders table (now includes order_number)
        $sql = "INSERT INTO orders
            (order_number, user_id, full_name, phone, street, apt, city, state, zip, subtotal, shipping_cost, tax, total, status)
            VALUES
            ('$on_e', $uid, '$fn_e', '$ph_e', '$st_e', '$apt_e', '$city_e', '$state_e', '$zip_e',
             $total, $shippingCost, $tax, $grandTotal, 'pending')";
        $conn->query($sql);
        $orderId = $conn->insert_id;

        // Insert order items and reduce stock
        foreach ($cartItems as $item) {
            $bid   = (int)$item['book_id'];
            $qty   = (int)$item['quantity'];
            $price = (float)$item['price'];
            $title_e  = $conn->real_escape_string($item['title']);
            $author_e = $conn->real_escape_string($item['author'] ?? '');
            $cover_e  = $conn->real_escape_string($item['cover_image'] ?? '');

            // Save order item with book snapshot
            $conn->query("INSERT INTO order_items
                (order_id, book_id, title, author, cover_image, quantity, price)
                VALUES
                ($orderId, $bid, '$title_e', '$author_e', '$cover_e', $qty, $price)");

            // Reduce stock in books table
            $conn->query("UPDATE books SET stock = stock - $qty WHERE id = $bid AND stock >= $qty");
        }

        // Clear cart
        $conn->query("DELETE FROM cart WHERE user_id=$uid");
        $conn->close();

        $_SESSION['flash_success'] = 'Order ' . $orderNumber . ' placed successfully!';
        redirect('pages/orders.php');
    }
}

// Pre-fill helper
function val($key) {
    return htmlspecialchars($_POST[$key] ?? '');
}

// Default full name / phone from user profile
$defaultFullName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
$defaultPhone = $user['phone'] ?? '';

require_once '../includes/header.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1>Checkout</h1>
    <p>Review your order and enter shipping details</p>
</div>

<div class="section">

    <?php if(isset($errors['stock'])): ?>
    <div class="alert alert-error"><?= $errors['stock'] ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="checkoutForm" novalidate>
    <div class="checkout-layout">
        <div>
            <!-- Shipping Information -->
            <div class="checkout-panel">
                <div class="checkout-panel-header">
                    <div class="checkout-panel-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    </div>
                    <div>
                        <div class="checkout-panel-title">Shipping Information</div>
                        <div class="checkout-panel-subtitle">Where should we deliver your order?</div>
                    </div>
                </div>
                <div class="checkout-panel-body">

                    <div class="form-group">
                        <label for="full_name">Full name (First and Last name) <span>*</span></label>
                        <input type="text" name="full_name" id="full_name"
                            value="<?= isset($_POST['full_name']) ? val('full_name') : htmlspecialchars($defaultFullName) ?>"
                            class="<?= isset($errors['full_name']) ? 'error' : '' ?>"
                            autocomplete="name">
                        <?php if(isset($errors['full_name'])): ?>
                            <span class="field-error"><?= htmlspecialchars($errors['full_name']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone number <span>*</span></label>
                        <input type="tel" name="phone" id="phone"
                            value="<?= isset($_POST['phone']) ? val('phone') : htmlspecialchars($defaultPhone) ?>"
                            class="<?= isset($errors['phone']) ? 'error' : '' ?>"
                            placeholder="(337) 555-0000"
                            autocomplete="tel">
                        <?php if(isset($errors['phone'])): ?>
                            <span class="field-error"><?= htmlspecialchars($errors['phone']) ?></span>
                        <?php else: ?>
                            <span class="field-hint">May be used to assist delivery</span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label for="street">Address <span>*</span></label>
                        <input type="text" name="street" id="street"
                            value="<?= val('street') ?>"
                            class="<?= isset($errors['street']) ? 'error' : '' ?>"
                            placeholder="Street address or P.O. Box"
                            autocomplete="address-line1">
                        <?php if(isset($errors['street'])): ?>
                            <span class="field-error"><?= htmlspecialchars($errors['street']) ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <input type="text" name="apt" id="apt"
                            value="<?= val('apt') ?>"
                            placeholder="Apt, suite, unit, building, floor, etc."
                            autocomplete="address-line2">
                    </div>

                    <div class="form-row form-row-3">
                        <div class="form-group">
                            <label for="city">City <span>*</span></label>
                            <input type="text" name="city" id="city"
                                value="<?= val('city') ?>"
                                class="<?= isset($errors['city']) ? 'error' : '' ?>"
                                autocomplete="address-level2">
                            <?php if(isset($errors['city'])): ?>
                                <span class="field-error"><?= htmlspecialchars($errors['city']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="state">State <span>*</span></label>
                            <select name="state" id="state"
                                class="<?= isset($errors['state']) ? 'error' : '' ?>"
                                autocomplete="address-level1">
                                <option value="">Select</option>
                                <?php foreach($usStates as $code => $name): ?>
                                    <option value="<?= $code ?>" <?= ($_POST['state'] ?? '') === $code ? 'selected' : '' ?>>
                                        <?= $name ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if(isset($errors['state'])): ?>
                                <span class="field-error"><?= htmlspecialchars($errors['state']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="zip">ZIP Code <span>*</span></label>
                            <input type="text" name="zip" id="zip"
                                value="<?= val('zip') ?>"
                                class="<?= isset($errors['zip']) ? 'error' : '' ?>"
                                placeholder="70609"
                                maxlength="10"
                                inputmode="numeric"
                                autocomplete="postal-code">
                            <?php if(isset($errors['zip'])): ?>
                                <span class="field-error"><?= htmlspecialchars($errors['zip']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Payment -->
            <div class="checkout-panel">
                <div class="checkout-panel-header">
                    <div class="checkout-panel-icon">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                    </div>
                    <div>
                        <div class="checkout-panel-title">Payment</div>
                        <div class="checkout-panel-subtitle">Enter your payment details</div>
                    </div>
                </div>
                <div class="checkout-panel-body">
                    <div class="payment-warning">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        <div>
                            <strong>Do not enter real credit card details.</strong>
                            This is a class project and is <u>not secure</u>. Your information is not encrypted or processed by a real payment system. Use fake test values only (e.g. card number <code>4242 4242 4242 4242</code>).
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="card_number">Card Number <span>*</span></label>
                        <input type="text" name="card_number" id="card_number"
                            value="<?= val('card_number') ?>"
                            class="<?= isset($errors['card_number']) ? 'error' : '' ?>"
                            placeholder="4242 4242 4242 4242"
                            inputmode="numeric"
                            maxlength="23"
                            autocomplete="off">
                        <?php if(isset($errors['card_number'])): ?>
                            <span class="field-error"><?= htmlspecialchars($errors['card_number']) ?></span>
                        <?php else: ?>
                            <span class="field-hint">13-19 digits.</span>
                        <?php endif; ?>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="card_expiry">Expiry (MM/YY) <span>*</span></label>
                            <input type="text" name="card_expiry" id="card_expiry"
                                value="<?= val('card_expiry') ?>"
                                class="<?= isset($errors['card_expiry']) ? 'error' : '' ?>"
                                placeholder="12/27"
                                inputmode="numeric"
                                maxlength="5"
                                autocomplete="off">
                            <?php if(isset($errors['card_expiry'])): ?>
                                <span class="field-error"><?= htmlspecialchars($errors['card_expiry']) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="card_cvc">CVC <span>*</span></label>
                            <input type="text" name="card_cvc" id="card_cvc"
                                value="<?= val('card_cvc') ?>"
                                class="<?= isset($errors['card_cvc']) ? 'error' : '' ?>"
                                placeholder="123"
                                inputmode="numeric"
                                maxlength="4"
                                autocomplete="off">
                            <?php if(isset($errors['card_cvc'])): ?>
                                <span class="field-error"><?= htmlspecialchars($errors['card_cvc']) ?></span>
                            <?php else: ?>
                                <span class="field-hint">3 or 4 digits.</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <button type="submit" class="btn-place-order">
                        Place Order — $<?= number_format($grandTotal, 2) ?>
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="checkout-summary">
            <div class="checkout-summary-header">Order Summary</div>
            <div class="checkout-summary-body">
                <?php foreach($cartItems as $item): ?>
                <div class="checkout-item-row">
                    <span class="checkout-item-name"><?= htmlspecialchars($item['title']) ?></span>
                    <span class="checkout-item-qty">&times; <?= $item['quantity'] ?></span>
                    <span class="checkout-item-price">$<?= number_format($item['subtotal'], 2) ?></span>
                </div>
                <?php endforeach; ?>
                <hr class="checkout-items-divider">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="summary-row-value">$<?= number_format($total, 2) ?></span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span class="summary-row-value">
                        <?php if($shippingCost == 0): ?>
                            <span class="free-shipping">Free</span>
                        <?php else: ?>
                            $<?= number_format($shippingCost, 2) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="summary-row">
                    <span>Tax (8.5%)</span>
                    <span class="summary-row-value">$<?= number_format($tax, 2) ?></span>
                </div>
                <hr class="summary-divider">
                <div class="summary-total">
                    <span>Total</span>
                    <span>$<?= number_format($grandTotal, 2) ?></span>
                </div>
                <div class="secure-note">
                    <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    Class project demo &mdash; not a real payment
                </div>
            </div>
        </div>
    </div>
    </form>
</div>

<?php
if (isset($conn) && $conn) $conn->close();
require_once '../includes/footer.php';
?>
