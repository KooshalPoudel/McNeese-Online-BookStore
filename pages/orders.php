<?php
// orders.php
// My Orders page 
// Order list & cancel logic: Kushal
// Modal trigger / cancel UX: Rojal (with Kushal)
// Page styling / cards: Alok and Kelsang
// Kushal: shows all orders for current user, split into
//         current (active) and past sections
//         cancel button only appears for orders in "pending" status
// Rojal: hooked up the cancel modal here so its not a browser confirm()

$pageTitle = 'My Orders';
require_once '../includes/config.php';

if (!isLoggedIn()) redirect('pages/login.php');

$uid  = (int)$_SESSION['user_id'];
$conn = getConnection();


// Handle cancel order action (POST from modal)
// Kushal: when user confirms cancel in modal, this runs
//     we restore stock and mark order as cancelled
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $order_id = (int)($_POST['order_id'] ?? 0);
    if ($order_id > 0) {

        // Kushal: only allow cancel of YOUR OWN orders, only if pending
        // user_id check is critical so users cant cancel each others orders
        $check = $conn->query("SELECT id, status FROM orders WHERE id=$order_id AND user_id=$uid LIMIT 1");
        if ($check && $check->num_rows === 1) {
            $ord = $check->fetch_assoc();

            if ($ord['status'] === 'pending') {

                // Restore stock for each item
                // Kushal: add back the qty so other users can buy that book
                // Rojal: smart, otherwise stock would be lost forever after cancel
                $items = $conn->query("SELECT book_id, quantity FROM order_items WHERE order_id=$order_id");
                while ($it = $items->fetch_assoc()) {
                    if ($it['book_id']) {
                        $bid = (int)$it['book_id'];
                        $q   = (int)$it['quantity'];
                        $conn->query("UPDATE books SET stock = stock + $q WHERE id=$bid");
                    }
                }

                // mark order as cancelled
                $conn->query("UPDATE orders SET status='cancelled' WHERE id=$order_id");
                $_SESSION['flash_success'] = 'Order cancelled successfully.';
            }
        }
        $conn->close();
        // Redirect to refresh page (PRG pattern)
        header('Location: ' . SITE_URL . '/pages/orders.php');
        exit();
    }
}


// Fetch all orders for this user with item count
// Kushal: LEFT JOIN order_items so we get item count per order
$orders = [];
$res = $conn->query("
    SELECT o.*, COUNT(oi.id) as item_count, SUM(oi.quantity) as total_qty
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.user_id = $uid
    GROUP BY o.id
    ORDER BY o.created_at DESC
");
while ($r = $res->fetch_assoc()) {
    $orders[] = $r;
}


// Split orders into current (active) and past (done/cancelled)
// Kushal: matches what user expects, see active first then history
$currentOrders = [];
$pastOrders    = [];
foreach ($orders as $o) {
    if (in_array($o['status'], ['pending', 'processing', 'shipped'])) {
        $currentOrders[] = $o;
    } else {
        // delivered or cancelled both go to past
        $pastOrders[] = $o;
    }
}

require_once '../includes/header.php';


// Helper: status badge class + label
// Kushal: maps each status to css class + display label
// Alok: i use the same map in admin orders page
//   maybe later we put in a shared helper file
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

// Helper: display order number
// use friendly format MC-2026-XXXXXX
// fallback to padded ID for old orders that didnt have order_number column
// Kelsang: i added the order_number column in week 6
//          so older orders show #00003 format
function displayOrderNo($o) {
    if (!empty($o['order_number'])) return $o['order_number'];
    return '#' . str_pad($o['id'], 5, '0', STR_PAD_LEFT);
}
?>

<!-- Page Header -->
<div class="page-header">
    <h1>My Orders</h1>
    <p>
        <?= count($orders) ?>
        order<?= count($orders) !== 1 ? 's' : '' ?> in your account
    </p>
</div>

<div class="section">

    <!-- flash msg (e.g. "Order cancelled successfully") -->
    <?php if(isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['flash_success'] ?></div>
    <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <?php if(empty($orders)): ?>

    <!-- Empty state - no orders yet -->
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg width="22" height="22" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor"
                 stroke-width="1.5" stroke-linecap="round"
                 stroke-linejoin="round">
                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2
                         2 0 0 0 2-2V6l-3-4z"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <path d="M16 10a4 4 0 0 1-8 0"/>
            </svg>
        </div>
        <h3>No orders yet</h3>
        <p>When you place an order, it will appear here for you to track.</p>
        <a href="books.php" class="btn-primary">Start shopping</a>
    </div>

    <?php else: ?>

    <!-- CURRENT ORDERS section (with progress tracker) -->
    <!-- Kushal: only shown if user has any active orders -->
    <?php if(!empty($currentOrders)): ?>
    <div class="orders-section">
        <div class="orders-section-header">
            <h2>Current Orders</h2>
            <span class="orders-section-count">
                <?= count($currentOrders) ?> active
            </span>
        </div>

        <?php foreach($currentOrders as $o):
            // PHP destructuring for badge class + label
            [$badgeClass, $badgeLabel] = statusBadge($o['status']);
        ?>
        <div class="order-card">

            <!-- Order header: number, date, items, total, status badge -->
            <div class="order-card-header">
                <div class="order-card-id">
                    <div class="order-card-label">Order</div>
                    <div class="order-card-number">
                        <?= htmlspecialchars(displayOrderNo($o)) ?>
                    </div>
                </div>
                <div class="order-card-meta">
                    <div class="order-card-label">Placed on</div>
                    <div class="order-card-date">
                        <?= date('M j, Y', strtotime($o['created_at'])) ?>
                    </div>
                </div>
                <div class="order-card-meta">
                    <div class="order-card-label">Items</div>
                    <div class="order-card-date">
                        <?= (int)$o['total_qty'] ?>
                        item<?= $o['total_qty'] != 1 ? 's' : '' ?>
                    </div>
                </div>
                <div class="order-card-meta">
                    <div class="order-card-label">Total</div>
                    <div class="order-card-total">
                        $<?= number_format($o['total'], 2) ?>
                    </div>
                </div>
                <span class="order-badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
            </div>

            <!-- PROGRESS TRACKER -->
            <!-- Kushal: shows the 4 steps with current step highlighted -->
            <!-- Alok: kelsang and i designed this tracker UI -->
            <div class="order-card-body">
                <div class="order-tracker">
                    <?php
                    // Map of status -> [label, step number]
                    $steps = [
                        'pending'    => ['Order Placed',  1],
                        'processing' => ['Processing',    2],
                        'shipped'    => ['Shipped',       3],
                        'delivered'  => ['Delivered',     4],
                    ];
                    $currentStep = $steps[$o['status']][1] ?? 1;
                    ?>
                    <?php foreach($steps as $key => [$label, $num]):
                        $isActive   = $num <= $currentStep;
                        $isCurrent  = $num == $currentStep;
                    ?>
                    <div class="tracker-step <?= $isActive ? 'active' : '' ?> <?= $isCurrent ? 'current' : '' ?>">
                        <div class="tracker-dot">
                            <?php if($isActive && !$isCurrent): ?>
                                <!-- Completed step: show checkmark -->
                                <svg width="10" height="10" viewBox="0 0 24 24"
                                     fill="none" stroke="currentColor"
                                     stroke-width="3.5" stroke-linecap="round"
                                     stroke-linejoin="round">
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            <?php else: ?>
                                <!-- Current or future step: show step number -->
                                <?= $num ?>
                            <?php endif; ?>
                        </div>
                        <div class="tracker-label"><?= $label ?></div>
                    </div>
                    <?php if($num < 4): ?>
                    <!-- Connecting line between steps (active = filled) -->
                    <div class="tracker-line <?= $num < $currentStep ? 'active' : '' ?>"></div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Action buttons (View / Cancel) -->
                <div class="order-card-actions">
                    <a href="order_details.php?id=<?= $o['id'] ?>" class="btn-view-order">
                        View details
                        <svg width="12" height="12" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="2.5" stroke-linecap="round"
                             stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>

                    <?php if($o['status'] === 'pending'): ?>
                    <!-- Cancel only allowed when status is pending -->
                    <!-- Rojal: data-* attributes pass order info to JS modal handler -->
                    <button type="button" class="btn-cancel-order"
                            data-cancel-order
                            data-order-number="<?= htmlspecialchars(displayOrderNo($o)) ?>"
                            data-order-id="<?= $o['id'] ?>">
                        Cancel order
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- PAST ORDERS section (compact, no tracker) -->
    <!-- Kushal: just show summary card without progress bar -->
    <?php if(!empty($pastOrders)): ?>
    <div class="orders-section">
        <div class="orders-section-header">
            <h2>Past Orders</h2>
            <span class="orders-section-count">
                <?= count($pastOrders) ?> completed
            </span>
        </div>

        <?php foreach($pastOrders as $o):
            [$badgeClass, $badgeLabel] = statusBadge($o['status']);
        ?>
        <div class="order-card order-card-compact">
            <div class="order-card-header">
                <div class="order-card-id">
                    <div class="order-card-label">Order</div>
                    <div class="order-card-number">
                        <?= htmlspecialchars(displayOrderNo($o)) ?>
                    </div>
                </div>
                <div class="order-card-meta">
                    <div class="order-card-label">Placed on</div>
                    <div class="order-card-date">
                        <?= date('M j, Y', strtotime($o['created_at'])) ?>
                    </div>
                </div>
                <div class="order-card-meta">
                    <div class="order-card-label">Items</div>
                    <div class="order-card-date">
                        <?= (int)$o['total_qty'] ?>
                        item<?= $o['total_qty'] != 1 ? 's' : '' ?>
                    </div>
                </div>
                <div class="order-card-meta">
                    <div class="order-card-label">Total</div>
                    <div class="order-card-total">
                        $<?= number_format($o['total'], 2) ?>
                    </div>
                </div>
                <span class="order-badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
            </div>
            <div class="order-card-body order-card-body-compact">
                <a href="order_details.php?id=<?= $o['id'] ?>" class="btn-view-order">
                    View details
                    <svg width="12" height="12" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"/>
                        <polyline points="12 5 19 12 12 19"/>
                    </svg>
                </a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>

</div>

<!-- CANCEL ORDER MODAL -->
<!-- Rojal: built this modal in week 7 -->
<!--     used by order_details.php too -->
<!--      hidden by default (aria-hidden=true) -->
<!--        revealed by JS when user clicks any [data-cancel-order] button -->
<div class="modal-backdrop" id="cancelOrderModal" aria-hidden="true">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="cancelModalTitle">

        <div class="modal-header">
            <!-- Warning triangle icon -->
            <!-- Kelsang: replaced warning emoji with svg -->
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
            <!-- close X button -->
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
            <!-- Rojal: order number filled in by main.js via data attributes -->
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
            <!-- Form posts back to this page with action=cancel -->
            <!-- Rojal: order_id is set dynamically by JS when modal opens -->
            <form method="POST" action="" id="cancelOrderForm" style="display:inline;">
                <input type="hidden" name="action" value="cancel">
                <input type="hidden" name="order_id" id="cancelOrderIdInput" value="">
                <button type="submit" class="modal-btn modal-btn-danger">
                    Yes, cancel order
                </button>
            </form>
        </div>
    </div>
</div>

<?php
// Kushal: dont close $conn here, footer.php takes care of it
require_once '../includes/footer.php';
?>