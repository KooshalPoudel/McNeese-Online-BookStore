<?php
// cart.php
// Shopping cart page (Week 6)
// Cart logic & UI: Alok (add/remove/update/clear)
// Stock check / inventory: Kelsang (DB schema work)
// Cart styling / icons: Kelsang (replaced emojis with SVG)
// Testing & debugging: Rojal
// Alok: handles all 4 cart actions: add, remove, update, clear
// Rojal: tested every flow, found a bug with clearing, fixed already

$pageTitle = 'Shopping Cart';
require_once '../includes/config.php';

// Alok: must be logged in to have a cart
if (!isLoggedIn()) redirect('pages/login.php');

$uid = (int)$_SESSION['user_id'];
$conn = getConnection();


// Handle cart actions BEFORE any HTML output
// (we may redirect, and cant redirect after HTML is sent)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action'] ?? '';
    $book_id = (int)($_POST['book_id'] ?? 0);

    // ADD action
    // Alok: adds 1 of book to cart, OR increments if already there
    // Kelsang: also checks stock so we never add more than is available
    if ($action === 'add' && $book_id > 0) {
        $bookRes = $conn->query("SELECT stock, title FROM books WHERE id=$book_id");
        if ($bookRes && $bookRes->num_rows > 0) {
            $book = $bookRes->fetch_assoc();
            $stock = (int)$book['stock'];

            // Check if this book already in user cart
            $check = $conn->query("SELECT id, quantity FROM cart WHERE user_id=$uid AND book_id=$book_id");
            $currentQty = 0;
            $cartRowId  = 0;
            if ($check->num_rows > 0) {
                $row = $check->fetch_assoc();
                $currentQty = (int)$row['quantity'];
                $cartRowId  = (int)$row['id'];
            }

            // Stock checks (Kelsang)
            if ($stock <= 0) {
                // Out of stock, save error in session for inline display
                $_SESSION['stock_error_book_id'] = $book_id;
                $_SESSION['stock_error_msg'] = 'Out of stock';
            } elseif ($currentQty + 1 > $stock) {
                // Already at max stock, show error
                $_SESSION['stock_error_book_id'] = $book_id;
                $_SESSION['stock_error_msg'] = 'Max stock reached (' . $stock . ')';
            } else {
                // OK to add: bump existing qty or insert new row
                if ($cartRowId > 0) {
                    $newQty = $currentQty + 1;
                    $conn->query("UPDATE cart SET quantity=$newQty WHERE id=$cartRowId");
                } else {
                    $conn->query("INSERT INTO cart (user_id, book_id, quantity) VALUES ($uid, $book_id, 1)");
                }
                $_SESSION['cart_msg'] = 'Item added to cart.';
            }
        }
        $conn->close();
        // Alok: PRG pattern (Post / Redirect / Get)
        //       avoids duplicate submits on refresh
        header('Location: ' . SITE_URL . '/pages/cart.php');
        exit();
    }

    // REMOVE action
    if ($action === 'remove' && $book_id > 0) {
        $conn->query("DELETE FROM cart WHERE user_id=$uid AND book_id=$book_id");
        $conn->close();
        header('Location: ' . SITE_URL . '/pages/cart.php');
        exit();
    }

    // UPDATE quantity action
    // Alok: handles + and - buttons
    //       if qty drops to 0, just delete the row
    if ($action === 'update' && $book_id > 0) {
        $qty = (int)($_POST['quantity'] ?? 1);

        if ($qty <= 0) {
            // qty 0 = remove
            $conn->query("DELETE FROM cart WHERE user_id=$uid AND book_id=$book_id");
        } else {
            // verify stock again before updating
            $bookRes = $conn->query("SELECT stock, title FROM books WHERE id=$book_id");
            if ($bookRes && $bookRes->num_rows > 0) {
                $book = $bookRes->fetch_assoc();
                $stock = (int)$book['stock'];

                if ($qty > $stock) {
                    // Set inline error, dont update qty
                    $_SESSION['stock_error_book_id'] = $book_id;
                    $_SESSION['stock_error_msg'] = 'Max stock reached (' . $stock . ')';
                } else {
                    $conn->query("UPDATE cart SET quantity=$qty WHERE user_id=$uid AND book_id=$book_id");
                }
            }
        }
        $conn->close();
        header('Location: ' . SITE_URL . '/pages/cart.php');
        exit();
    }

    // CLEAR cart action (used by Clear cart button)
    // Rojal: this is where i found the bug earlier, looks good now
    if ($action === 'clear') {
        $conn->query("DELETE FROM cart WHERE user_id=$uid");
        $conn->close();
        header('Location: ' . SITE_URL . '/pages/cart.php');
        exit();
    }
}


// Now we can include the header (no more redirects after this)
require_once '../includes/header.php';

// Alok: capture inline stock error from session, then clear it
$stockErrorBookId = $_SESSION['stock_error_book_id'] ?? null;
$stockErrorMsg    = $_SESSION['stock_error_msg'] ?? '';
unset($_SESSION['stock_error_book_id']);
unset($_SESSION['stock_error_msg']);


// Fetch all cart items for this user with book info joined
// Alok: ORDER BY added_at DESC so newest items show first
$cartItems = [];
$total = 0;
$res = $conn->query("
    SELECT c.id as cart_id, c.quantity, b.id as book_id,
           b.title, b.author, b.price, b.category, b.stock, b.cover_image
    FROM cart c JOIN books b ON c.book_id = b.id
    WHERE c.user_id = $uid ORDER BY c.added_at DESC
");
while ($r = $res->fetch_assoc()) {
    $r['subtotal'] = $r['price'] * $r['quantity'];
    $total += $r['subtotal'];
    $cartItems[] = $r;
}


// Order summary calculations
// Alok: free shipping if subtotal > $50, else flat $5.99
// Kushal: tax is 8.5% (Louisiana sales tax)
$shipping   = $total > 50 ? 0 : 5.99;
$tax        = $total * 0.085;
$grandTotal = $total + $shipping + $tax;
?>

<!-- Page Header -->
<div class="page-header">
    <h1>Shopping Cart</h1>
    <p>
        <?= count($cartItems) ?>
        item<?= count($cartItems) !== 1 ? 's' : '' ?> in your cart
    </p>
</div>

<div class="section">

    <!-- flash msg from session (e.g. "Item added to cart.") -->
    <?php if(isset($_SESSION['cart_msg'])): ?>
    <div class="alert alert-success"><?= $_SESSION['cart_msg'] ?></div>
    <?php unset($_SESSION['cart_msg']); ?>
    <?php endif; ?>

    <?php if(empty($cartItems)): ?>

    <!-- Empty cart state -->
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg width="22" height="22" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor"
                 stroke-width="1.5" stroke-linecap="round"
                 stroke-linejoin="round">
                <circle cx="9" cy="21" r="1"/>
                <circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2
                         1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
            </svg>
        </div>
        <h3>Your cart is empty</h3>
        <p>Browse our books and add items to your cart.</p>
        <a href="books.php" class="btn-primary">Browse books</a>
    </div>

    <?php else: ?>

    <!-- Two-column layout: items (left) + summary (right) -->
    <!-- Kelsang: refactored this layout in week 6 to be cleaner -->
    <div class="cart-layout">

        <!-- LEFT: Cart Items List -->
        <div class="cart-panel">
            <div class="cart-panel-header">
                <span class="cart-panel-title">Items</span>

                <!-- Clear cart form (Alok) -->
                <!-- confirm() prevents accidental clear -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="cart-btn-clear"
                            onclick="return confirm('Remove all items from cart?')">
                        Clear cart
                    </button>
                </form>
            </div>

            <!-- Loop through each cart item -->
            <?php foreach($cartItems as $item): ?>
            <?php $hasStockError = ($stockErrorBookId == $item['book_id']); ?>
            <div class="cart-item <?= $hasStockError ? 'has-stock-error' : '' ?>">

                <!-- Cover thumbnail -->
                <div class="cart-item-thumb">
                    <?php if(!empty($item['cover_image'])): ?>
                        <img src="<?= SITE_URL ?>/<?= htmlspecialchars($item['cover_image']) ?>"
                             alt="<?= htmlspecialchars($item['title']) ?>"
                             class="cart-item-img">
                    <?php else: ?>
                        <!-- Fallback icon based on category -->
                        <?php if($item['category'] === 'office_supply'): ?>
                        <svg width="28" height="28" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.5" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M12 19l7-7 3 3-7 7-3-3z"/>
                            <path d="M18 13l-1.5-7.5L2 2l3.5
                                     14.5L13 18l5-5z"/>
                            <path d="M2 2l7.586 7.586"/>
                            <circle cx="11" cy="11" r="2"/>
                        </svg>
                        <?php else: ?>
                        <svg width="28" height="28" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.5" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5
                                     0 0 1 4 19.5v-15A2.5 2.5
                                     0 0 1 6.5 2z"/>
                        </svg>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Title + author -->
                <div class="cart-item-info">
                    <div class="cart-item-title">
                        <?= htmlspecialchars($item['title']) ?>
                    </div>
                    <div class="cart-item-author">
                        <?= htmlspecialchars($item['author']) ?>
                    </div>
                </div>

                <!-- Unit price -->
                <div class="cart-item-price">
                    $<?= number_format($item['price'], 2) ?>
                </div>

                <!-- Quantity controls (- qty +) -->
                <!-- Alok: each button is its own form so we can post different actions -->
                <div class="qty-cell">
                    <div class="qty-controls">

                        <!-- Minus button -->
                        <form method="POST" action="" class="qty-form">
                            <input type="hidden" name="book_id" value="<?= $item['book_id'] ?>">
                            <?php if($item['quantity'] <= 1): ?>
                                <!-- if qty is 1, minus = remove -->
                                <input type="hidden" name="action" value="remove">
                                <button type="submit" class="qty-btn">&minus;</button>
                            <?php else: ?>
                                <input type="hidden" name="action" value="update">
                                <button type="submit" name="quantity"
                                        value="<?= $item['quantity'] - 1 ?>"
                                        class="qty-btn">&minus;</button>
                            <?php endif; ?>
                        </form>

                        <span class="qty-display"><?= $item['quantity'] ?></span>

                        <!-- Plus button -->
                        <!-- Kelsang: even at stock limit we still submit -->
                        <!--          so user gets the inline error -->
                        <form method="POST" action="" class="qty-form-plus">
                            <input type="hidden" name="book_id" value="<?= $item['book_id'] ?>">
                            <input type="hidden" name="action" value="update">
                            <button type="submit" name="quantity"
                                    value="<?= $item['quantity'] + 1 ?>"
                                    class="qty-btn">+</button>
                        </form>
                    </div>

                    <!-- Inline stock error (Kelsang) -->
                    <!-- only shown for the item that triggered it -->
                    <?php if($hasStockError): ?>
                        <div class="qty-error">
                            <svg width="11" height="11" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor"
                                 stroke-width="2.5" stroke-linecap="round"
                                 stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            <?= htmlspecialchars($stockErrorMsg) ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Subtotal column -->
                <div class="cart-item-subtotal">
                    $<?= number_format($item['subtotal'], 2) ?>
                </div>

                <!-- Remove button (trash icon) -->
                <!-- Kelsang: replaced trash emoji with this svg in week 6 -->
                <form method="POST" action="">
                    <input type="hidden" name="book_id" value="<?= $item['book_id'] ?>">
                    <input type="hidden" name="action" value="remove">
                    <button type="submit" class="btn-remove" title="Remove">
                        <svg width="15" height="15" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" stroke-linecap="round"
                             stroke-linejoin="round">
                            <polyline points="3 6 5 6 21 6"/>
                            <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2
                                     2 0 0 1-2-2L5 6"/>
                            <path d="M10 11v6"/>
                            <path d="M14 11v6"/>
                            <path d="M9 6V4h6v2"/>
                        </svg>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>

            <!-- Continue shopping link -->
            <div class="cart-footer">
                <a href="books.php" class="continue-link">
                    <svg width="13" height="13" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"/>
                        <polyline points="12 19 5 12 12 5"/>
                    </svg>
                    Continue shopping
                </a>
            </div>
        </div>

        <!-- RIGHT: Order Summary -->
        <!-- Kushal: this summary box is reusable, same style on checkout.php -->
        <div class="summary-panel">
            <div class="summary-header">Order Summary</div>
            <div class="summary-body">

                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="summary-row-value">
                        $<?= number_format($total, 2) ?>
                    </span>
                </div>

                <div class="summary-row">
                    <span>Shipping</span>
                    <span class="summary-row-value">
                        <?php if($shipping == 0): ?>
                            <span class="free-shipping">Free</span>
                        <?php else: ?>
                            $<?= number_format($shipping, 2) ?>
                        <?php endif; ?>
                    </span>
                </div>

                <!-- Encourage user to add more for free shipping -->
                <?php if($total < 50): ?>
                <div class="shipping-nudge">
                    <svg width="11" height="11" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    Add $<?= number_format(50 - $total, 2) ?> more for free shipping
                </div>
                <?php endif; ?>

                <div class="summary-row">
                    <span>Tax (8.5%)</span>
                    <span class="summary-row-value">
                        $<?= number_format($tax, 2) ?>
                    </span>
                </div>

                <hr class="summary-divider">

                <div class="summary-total">
                    <span>Total</span>
                    <span>$<?= number_format($grandTotal, 2) ?></span>
                </div>

                <a href="checkout.php" class="btn-checkout">
                    Proceed to checkout
                    <svg width="14" height="14" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>

                <!-- Secure note -->
                <!-- Kelsang: lock svg, replaced lock emoji -->
                <div class="secure-note">
                    <svg width="11" height="11" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    Secure checkout
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>