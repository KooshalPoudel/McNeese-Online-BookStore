<?php
// order_details.php
// Single order detail view 
// Order details + cancel logic: Kushal + Rojal
// Page styling / cards / tracker: Alok and Kelsang
// Kushal: shows full breakdown of one order
//         items, shipping address, summary, status tracker
// Rojal: same cancel modal as orders.php, reused here

$pageTitle = 'Order Details';
require_once '../includes/config.php';

if (!isLoggedIn()) redirect('pages/login.php');

$uid = (int)$_SESSION['user_id'];
$conn = getConnection();

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    redirect('pages/orders.php');
}

// Handle cancel action
// Kushal: same logic as orders.php, restore stock then mark cancelled
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $check = $conn->query("SELECT id, status FROM orders WHERE id=$order_id AND user_id=$uid LIMIT 1");
    if ($check && $check->num_rows === 1) {
        $ord = $check->fetch_assoc();
        if ($ord['status'] === 'pending') {
            // Restore stock
            $items = $conn->query("SELECT book_id, quantity FROM order_items WHERE order_id=$order_id");
            while ($it = $items->fetch_assoc()) {
                if ($it['book_id']) {
                    $bid = (int)$it['book_id'];
                    $q   = (int)$it['quantity'];
                    $conn->query("UPDATE books SET stock = stock + $q WHERE id=$bid");
                }
            }
            $conn->query("UPDATE orders SET status='cancelled' WHERE id=$order_id");
            $_SESSION['flash_success'] = 'Order cancelled successfully.';
        }
    }
    header('Location: ' . SITE_URL . '/pages/orders.php');
    exit();
}

// Load order(enforce ownership)
// kushal: AND user_id=$uid is critical
//         users cant view each others orders by changing the URL
$orderRes = $conn->query("SELECT * FROM orders WHERE id=$order_id AND user_id=$uid LIMIT 1");
if (!$orderRes || $orderRes->num_rows !== 1) {
    $_SESSION['flash_success'] = 'Order not found.';
    redirect('pages/orders.php');
}
$order = $orderRes->fetch_assoc();

// Load order items
$items = [];
$itemsRes = $conn->query("SELECT * FROM order_items WHERE order_id=$order_id");
while ($r = $itemsRes->fetch_assoc()) {
    $r['subtotal'] = $r['price'] * $r['quantity'];
    $items[] = $r;
}

require_once '../includes/header.php';

// Helper: status badge class + label
// Alok:same map as orders.php, should really refactor to shared file later
function statusBadge($status) {
    $map = [
        'pending'    => ['badge-pending',    'Pending'],
        'processing' => ['badge-processing', 'Processing'],
        'shipped'    => ['badge-shipped',    'Shipped'],
        'delivered'  => ['badge-delivered',  'Delivered'],
        'cancelled'  => ['badge-cancelled',  'Cancelled'],
    ];
    return $map[$status] ?? ['badge-pending', ucfirst($status)];
}

[$badgeClass, $badgeLabel] = statusBadge($order['status']);

// Order number display, fallback to padded id for old orders
$orderNo = !empty($order['order_number'])
    ? $order['order_number']
    : '#' . str_pad($order['id'], 5, '0', STR_PAD_LEFT);
?>

<!-- Page Header -->
<div class="page-header">
    <h1>Order <?= htmlspecialchars($orderNo) ?></h1>
    <p>
        Placed on
        <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?>
    </p>
</div>

<div class="section">

    <?php if(isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['flash_success'] ?></div>
    <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Back link -->
    <a href="orders.php" class="continue-link"
       style="margin-bottom:16px; display:inline-flex;">
        <svg width="13" height="13" viewBox="0 0 24 24"
             fill="none" stroke="currentColor"
             stroke-width="2.5" stroke-linecap="round"
             stroke-linejoin="round">
            <line x1="19" y1="12" x2="5" y2="12"/>
            <polyline points="12 19 5 12 12 5"/>
        </svg>
        Back to all orders
    </a>

    <!-- Status tracker (only for non-cancelled orders) -->
    <!-- Alok + Kelsang: same tracker design as orders.php -->
    <?php if($order['status'] !== 'cancelled'): ?>
    <div class="order-status-panel">
        <div class="order-status-header">
            <div>
                <div class="order-status-title">Order Status</div>
                <div class="order-status-sub">
                    Tracking number:
                    <strong><?= htmlspecialchars($orderNo) ?></strong>
                </div>
            </div>
            <span class="order-badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
        </div>
        <div class="order-status-body">
            <div class="order-tracker">
                <?php
                // Step labels with little description for each
                // Kushal: more detail than orders.php since this is the dedicated detail page
                $steps = [
                    'pending'    => ['Order Placed',  1, 'We\'ve received your order'],
                    'processing' => ['Processing',    2, 'We\'re preparing your items'],
                    'shipped'    => ['Shipped',       3, 'On the way to you'],
                    'delivered'  => ['Delivered',     4, 'Your order has arrived'],
                ];
                $currentStep = $steps[$order['status']][1] ?? 1;
                ?>
                <?php foreach($steps as $key => [$label, $num, $desc]):
                    $isActive  = $num <= $currentStep;
                    $isCurrent = $num == $currentStep;
                ?>
                <div class="tracker-step <?= $isActive ? 'active' : '' ?> <?= $isCurrent ? 'current' : '' ?>">
                    <div class="tracker-dot">
                        <?php if($isActive && !$isCurrent): ?>
                            <svg width="10" height="10" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor"
                                 stroke-width="3.5" stroke-linecap="round"
                                 stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        <?php else: ?>
                            <?= $num ?>
                        <?php endif; ?>
                    </div>
                    <div class="tracker-label"><?= $label ?></div>
                </div>
                <?php if($num < 4): ?>
                <div class="tracker-line <?= $num < $currentStep ? 'active' : '' ?>"></div>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php else: ?>
    <!-- Cancelled order, no tracker needed -->
    <div class="alert alert-error" style="margin-bottom:20px;">
        This order was cancelled.
    </div>
    <?php endif; ?>

    <!-- Cart-style layout: items on left, summary on right -->
    <!-- Kelsang: reusing the same cart-layout css from cart.php -->
    <div class="cart-layout">

        <!-- Items Panel -->
        <div class="cart-panel">
            <div class="cart-panel-header">
                <span class="cart-panel-title">Items in this order</span>
                <span class="cart-panel-title"
                      style="font-weight:500; text-transform:none; letter-spacing:0;">
                    <?= count($items) ?>
                    item<?= count($items) !== 1 ? 's' : '' ?>
                </span>
            </div>

            <?php foreach($items as $item): ?>
            <div class="cart-item">
                <!-- Cover Image -->
                <div class="cart-item-thumb">
                    <?php if(!empty($item['cover_image'])): ?>
                        <img src="<?= SITE_URL ?>/<?= htmlspecialchars($item['cover_image']) ?>"
                             alt="<?= htmlspecialchars($item['title']) ?>"
                             class="cart-item-img">
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
                </div>

                <!-- Info -->
                <div class="cart-item-info">
                    <div class="cart-item-title">
                        <?= htmlspecialchars($item['title']) ?>
                    </div>
                    <div class="cart-item-author">
                        <?= htmlspecialchars($item['author'] ?? '') ?>
                    </div>
                </div>

                <!-- Unit Price -->
                <div class="cart-item-price">
                    $<?= number_format($item['price'], 2) ?>
                </div>

                <!-- Qty (read-only here, not editable) -->
                <div class="qty-cell">
                    <div class="qty-controls">
                        <span class="qty-display">Qty: <?= $item['quantity'] ?></span>
                    </div>
                </div>

                <!-- Subtotal -->
                <div class="cart-item-subtotal">
                    $<?= number_format($item['subtotal'], 2) ?>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Shipping Address inside same panel -->
            <!-- Kushal: pulled from orders table snapshot, not user profile -->
            <!--         so address shows what was used at order time -->
            <div class="order-shipping-block">
                <div class="order-shipping-label">Shipping Address</div>
                <div class="order-shipping-address">
                    <strong><?= htmlspecialchars($order['full_name']) ?></strong><br>
                    <?= htmlspecialchars($order['street']) ?>
                    <?php if(!empty($order['apt'])): ?>
                        , <?= htmlspecialchars($order['apt']) ?>
                    <?php endif; ?>
                    <br>
                    <?= htmlspecialchars($order['city']) ?>,
                    <?= htmlspecialchars($order['state']) ?>
                    <?= htmlspecialchars($order['zip']) ?>
                    <?php if(!empty($order['phone'])): ?>
                    <br>
                    <span style="color:var(--ink-faint);">
                        Phone: <?= htmlspecialchars($order['phone']) ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>

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

        <!-- Order Summary -->
        <div class="summary-panel">
            <div class="summary-header">Order Summary</div>
            <div class="summary-body">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span class="summary-row-value">
                        $<?= number_format($order['subtotal'], 2) ?>
                    </span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span class="summary-row-value">
                        <?php if($order['shipping_cost'] == 0): ?>
                            <span class="free-shipping">Free</span>
                        <?php else: ?>
                            $<?= number_format($order['shipping_cost'], 2) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="summary-row">
                    <span>Tax</span>
                    <span class="summary-row-value">
                        $<?= number_format($order['tax'], 2) ?>
                    </span>
                </div>
                <hr class="summary-divider">
                <div class="summary-total">
                    <span>Total</span>
                    <span>$<?= number_format($order['total'], 2) ?></span>
                </div>

                <?php if($order['status'] === 'pending'): ?>
                <!-- Cancel button only when pending -->
                <!-- Rojal: same data attributes pattern as orders.php -->
                <button type="button" class="btn-cancel-order-full"
                        data-cancel-order
                        data-order-number="<?= htmlspecialchars($orderNo) ?>"
                        data-order-id="<?= $order['id'] ?>"
                        style="margin-top:16px;">
                    Cancel this order
                </button>
                <?php endif; ?>

                <div class="secure-note" style="margin-top:12px;">
                    <svg width="11" height="11" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                    Order <?= htmlspecialchars($orderNo) ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- CANCEL ORDER MODAL -->
<!-- Rojal: same modal markup as orders.php, JS handler in main.js works on both -->
<div class="modal-backdrop" id="cancelOrderModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="cancelModalTitle">
        <div class="modal-header">
            <div class="modal-icon modal-icon-danger">
                <svg width="20" height="20" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0
                             1.71 3h16.94a2 2 0 0 0 1.71-3L13.71
                             3.86a2 2 0 0 0-3.42 0z"/>
                    <line x1="12" y1="9"  x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </div>
            <button type="button" class="modal-close" data-modal-close aria-label="Close">
                <svg width="16" height="16" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <line x1="18" y1="6"  x2="6"  y2="18"/>
                    <line x1="6"  y1="6"  x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <div class="modal-body">
            <h3 id="cancelModalTitle">Cancel this order?</h3>
            <p class="modal-lead">
                You're about to cancel order
                <strong id="cancelOrderNumberLabel">&mdash;</strong>.
            </p>
            <ul class="modal-bullets">
                <li>Your items will be returned to inventory</li>
                <li>This action cannot be undone</li>
            </ul>
        </div>

        <div class="modal-footer">
            <button type="button" class="modal-btn modal-btn-secondary" data-modal-close>
                Keep order
            </button>
            <form method="POST" action="" id="cancelOrderForm" style="display:inline;">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="order_id" id="cancelOrderIdInput"
                       value="<?= $order['id'] ?>">
                <button type="submit" class="modal-btn modal-btn-danger">
                    Yes, cancel order
                </button>
            </form>
        </div>
    </div>
</div>

<?php
// Note: dont close $conn here, footer.php handles that
require_once '../includes/footer.php';
?>