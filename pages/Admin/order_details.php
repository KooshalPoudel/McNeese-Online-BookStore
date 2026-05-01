<?php
// admin/order_details.php
// Admin single-order detail view
// Backend & permissions: Kushal (admin guard, status updates, item edits)
// Stock restore logic: Kushal (extends cancel logic from orders.php)
// UI / cards / tables: Alok and Kelsang
// Kushal: admin can update status, edit item quantities, remove items
//   only pending orders allow item edits, status changes always allowed

$pageTitle = 'Admin — Order Details';
require_once '../../includes/config.php';
require_once '../../includes/admin_guard.php';

$conn = getConnection();

$order_id = (int)($_GET['id'] ?? 0);
if ($order_id <= 0) {
    redirect('pages/admin/orders.php');
}

// POST handlers
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Always re-fetch current status so we know whats allowed
    $cur = $conn->query("SELECT status FROM orders WHERE id=$order_id LIMIT 1");
    if (!$cur || $cur->num_rows !== 1) {
        redirect('pages/admin/orders.php');
    }
    $curStatus = $cur->fetch_assoc()['status'];

    // Update status
    // Kushal: cancelling here also restores stock
    //     same logic as customer-side cancel
    if ($action === 'update_status') {
        $new_status = $_POST['new_status'] ?? '';
        $allowed    = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        if (in_array($new_status, $allowed, true)) {
            if ($new_status === 'cancelled' && $curStatus !== 'cancelled') {
                // Restoree  all item stock
                $items = $conn->query("SELECT book_id, quantity FROM order_items WHERE order_id=$order_id");
                while ($it = $items->fetch_assoc()) {
                    if ($it['book_id']) {
                        $bid = (int)$it['book_id'];
                        $q   = (int)$it['quantity'];
                        $conn->query("UPDATE books SET stock = stock + $q WHERE id=$bid");
                    }
                }
            }
            $ns_e = $conn->real_escape_string($new_status);
            $conn->query("UPDATE orders SET status='$ns_e' WHERE id=$order_id");
            $_SESSION['flash_success'] = 'Order status updated to ' . ucfirst($new_status) . '.';
        }
        header('Location: ' . SITE_URL . '/pages/admin/order_details.php?id=' . $order_id);
        exit();
    }

    // Item edits are only allowed on pending orders
    // Kushal: once order is processing/shipped/delivered, qty is locked
    //    stops admin from changing what was already prepared/sent
    if (in_array($action, ['item_update', 'item_remove'], true)) {
        if ($curStatus !== 'pending') {
            $_SESSION['flash_error'] = 'Items can only be edited while the order is pending.';
            header('Location: ' . SITE_URL . '/pages/admin/order_details.php?id=' . $order_id);
            exit();
        }

        $item_id = (int)($_POST['item_id'] ?? 0);
        if ($item_id <= 0) {
            header('Location: ' . SITE_URL . '/pages/admin/order_details.php?id=' . $order_id);
            exit();
        }

        // Load the item
        $itRes = $conn->query("SELECT * FROM order_items WHERE id=$item_id AND order_id=$order_id LIMIT 1");
        if (!$itRes || $itRes->num_rows !== 1) {
            header('Location: ' . SITE_URL . '/pages/admin/order_details.php?id=' . $order_id);
            exit();
        }
        $it = $itRes->fetch_assoc();
        $oldQty = (int)$it['quantity'];
        $bid    = (int)$it['book_id'];

        // Remove item
        if ($action === 'item_remove') {
            if ($bid > 0) {
                $conn->query("UPDATE books SET stock = stock + $oldQty WHERE id=$bid");
            }
            $conn->query("DELETE FROM order_items WHERE id=$item_id");
            $_SESSION['flash_success'] = 'Item removed from order.';
        }

        // Update item qty
        // Kushal: handles 3 cases - increase, decrease, or zero (= remove)
        if ($action === 'item_update') {
            $newQty = (int)($_POST['quantity'] ?? 0);

            if ($newQty <= 0) {
                // Treat 0 or negative as removal
                if ($bid > 0) {
                    $conn->query("UPDATE books SET stock = stock + $oldQty WHERE id=$bid");
                }
                $conn->query("DELETE FROM order_items WHERE id=$item_id");
                $_SESSION['flash_success'] = 'Item removed from order.';
            } else {
                $delta = $newQty - $oldQty;

                if ($delta > 0) {
                    // Need more stock, check availability
                    $stockRow = $conn->query("SELECT stock FROM books WHERE id=$bid LIMIT 1");
                    $available = $stockRow ? (int)$stockRow->fetch_assoc()['stock'] : 0;
                    if ($available < $delta) {
                        $_SESSION['flash_error'] = 'Not enough stock, only ' . $available . ' additional unit(s) available.';
                        header('Location: ' . SITE_URL . '/pages/admin/order_details.php?id=' . $order_id);
                        exit();
                    }
                    if ($bid > 0) {
                        $conn->query("UPDATE books SET stock = stock - $delta WHERE id=$bid");
                    }
                } elseif ($delta < 0) {
                    $restore = abs($delta);
                    if ($bid > 0) {
                        $conn->query("UPDATE books SET stock = stock + $restore WHERE id=$bid");
                    }
                }

                $conn->query("UPDATE order_items SET quantity=$newQty WHERE id=$item_id");
                $_SESSION['flash_success'] = 'Quantity updated.';
            }
        }

        // Recompute order totals using the same rule as checkout
        // Kushal: keep math consistent across pages
        $sumRes = $conn->query("SELECT COALESCE(SUM(price * quantity), 0) AS subtotal FROM order_items WHERE order_id=$order_id");
        $subtotal = (float)$sumRes->fetch_assoc()['subtotal'];
        $shippingCost = $subtotal > 50 ? 0 : ($subtotal > 0 ? 5.99 : 0);
        $tax = $subtotal * 0.085;
        $total = $subtotal + $shippingCost + $tax;

        $conn->query("UPDATE orders SET
            subtotal=$subtotal,
            shipping_cost=$shippingCost,
            tax=$tax,
            total=$total
            WHERE id=$order_id");

        // If no items left, auto-cancel
        // (stock was already restored per-remove)
        $cntRes = $conn->query("SELECT COUNT(*) AS c FROM order_items WHERE order_id=$order_id");
        if ((int)$cntRes->fetch_assoc()['c'] === 0) {
            $conn->query("UPDATE orders SET status='cancelled' WHERE id=$order_id");
            $_SESSION['flash_success'] = 'All items removed, order was auto-cancelled.';
        }

        header('Location: ' . SITE_URL . '/pages/admin/order_details.php?id=' . $order_id);
        exit();
    }
}


// Looad order + customer
// Kushal: LEFT JOIN users so we get customer info
//    even if user account got deleted, order still loads
$orderRes = $conn->query("
    SELECT o.*,
           u.email      AS user_email,
           u.first_name AS user_first,
           u.last_name  AS user_last,
           u.phone      AS user_phone,
           u.student_id AS user_student_id
    FROM orders o
    LEFT JOIN users u ON u.id = o.user_id
    WHERE o.id = $order_id
    LIMIT 1
");
if (!$orderRes || $orderRes->num_rows !== 1) {
    $_SESSION['flash_success'] = 'Order not found.';
    redirect('pages/admin/orders.php');
}
$order = $orderRes->fetch_assoc();

// Load order items joinede with books to know current available stock
$items = [];
$itemsRes = $conn->query("
    SELECT oi.*, b.stock AS current_stock
    FROM order_items oi
    LEFT JOIN books b ON b.id = oi.book_id
    WHERE oi.order_id = $order_id
");
while ($r = $itemsRes->fetch_assoc()) {
    $r['subtotal'] = $r['price'] * $r['quantity'];
    $items[] = $r;
}

require_once '../../includes/header.php';

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
$orderNo = !empty($order['order_number'])
    ? $order['order_number']
    : '#' . str_pad($order['id'], 5, '0', STR_PAD_LEFT);
$allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
$canEditItems = ($order['status'] === 'pending');
?>

<!-- Admin ribbon -->
<!-- Kelsang: ribbon on top so admins know they are in admin area -->
<div class="admin-ribbon">
    <div class="admin-ribbon-inner">
        <span class="admin-ribbon-label">
            <svg width="13" height="13" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            Admin Area
        </span>
        <span class="admin-ribbon-user">
            Signed in as
            <strong>
                <?= htmlspecialchars($_SESSION['first_name'] . ' ' . $_SESSION['last_name']) ?>
            </strong>
        </span>
    </div>
</div>

<!-- Page Header -->
<div class="page-header">
    <h1>Order <?= htmlspecialchars($orderNo) ?></h1>
    <p>
        Placed on
        <?= date('F j, Y \a\t g:i A', strtotime($order['created_at'])) ?>
        &nbsp;&middot;&nbsp;
        Last updated
        <?= date('M j, g:i A', strtotime($order['updated_at'])) ?>
    </p>
</div>

<div class="section">

    <?php if(isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['flash_success'] ?></div>
    <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if(isset($_SESSION['flash_error'])): ?>
    <div class="alert alert-error"><?= $_SESSION['flash_error'] ?></div>
    <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

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

    <!-- Status management panel -->
    <!-- Kushal  : dropdown lets admin move order through pipeline -->
    <!-- Alok: confirm() popup so admin doesnt accidentally change status -->
    <div class="admin-status-panel">
        <div class="admin-status-panel-header">
            <div>
                <div class="admin-status-panel-label">Order Status Management</div>
                <div class="admin-status-panel-current">
                    Current status:
                    <span class="order-badge <?= $badgeClass ?>"><?= $badgeLabel ?></span>
                </div>
            </div>
        </div>
        <div class="admin-status-panel-body">
            <form method="POST" action="" class="admin-status-form-full"
                  onsubmit="return confirm('Change order status? This will immediately update the customer-facing view.');">
                <input type="hidden" name="action" value="update_status">
                <label for="new_status">Change status to:</label>
                <select name="new_status" id="new_status" class="admin-status-select-full">
                    <?php foreach($allowedStatuses as $s): ?>
                    <option value="<?= $s ?>" <?= $order['status'] === $s ? 'selected' : '' ?>>
                        <?= ucfirst($s) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="admin-update-btn">Update status</button>
            </form>
            <p class="admin-status-note">
                <svg width="12" height="12" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round"
                     stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                Changing status to <strong>Cancelled</strong> automatically restores stock.
                Item quantities can only be changed while status is <strong>Pending</strong>.
            </p>
        </div>
    </div>

    <!-- Customer/Shipping / Summary info -->
    <!-- Kelsang: 3-card grid layout, same style as profile panels -->
    <div class="admin-info-grid">
        <div class="admin-info-card">
            <div class="admin-info-card-label">Customer</div>
            <div class="admin-info-card-body">
                <div class="admin-info-row">
                    <strong>
                        <?= htmlspecialchars(trim(($order['user_first'] ?? '') . ' ' . ($order['user_last'] ?? ''))) ?>
                    </strong>
                </div>
                <div class="admin-info-row">
                    <a href="mailto:<?= htmlspecialchars($order['user_email'] ?? '') ?>"
                       class="admin-info-link">
                        <?= htmlspecialchars($order['user_email'] ?? '—') ?>
                    </a>
                </div>
                <?php if(!empty($order['user_student_id'])): ?>
                <div class="admin-info-row admin-info-sub">
                    Student ID: <?= htmlspecialchars($order['user_student_id']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="admin-info-card">
            <div class="admin-info-card-label">Shipping Address</div>
            <div class="admin-info-card-body">
                <div class="admin-info-row">
                    <strong><?= htmlspecialchars($order['full_name']) ?></strong>
                </div>
                <div class="admin-info-row">
                    <?= htmlspecialchars($order['street']) ?>
                    <?php if(!empty($order['apt'])): ?>
                        , <?= htmlspecialchars($order['apt']) ?>
                    <?php endif; ?>
                </div>
                <div class="admin-info-row">
                    <?= htmlspecialchars($order['city']) ?>,
                    <?= htmlspecialchars($order['state']) ?>
                    <?= htmlspecialchars($order['zip']) ?>
                </div>
                <?php if(!empty($order['phone'])): ?>
                <div class="admin-info-row admin-info-sub">
                    Phone: <?= htmlspecialchars($order['phone']) ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="admin-info-card">
            <div class="admin-info-card-label">Order Summary</div>
            <div class="admin-info-card-body">
                <div class="admin-info-row admin-info-row-split">
                    <span>Subtotal</span>
                    <span>$<?= number_format($order['subtotal'], 2) ?></span>
                </div>
                <div class="admin-info-row admin-info-row-split">
                    <span>Shipping</span>
                    <span>
                        <?php if($order['shipping_cost'] == 0): ?>
                            <span class="free-shipping">Free</span>
                        <?php else: ?>
                            $<?= number_format($order['shipping_cost'], 2) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="admin-info-row admin-info-row-split">
                    <span>Tax</span>
                    <span>$<?= number_format($order['tax'], 2) ?></span>
                </div>
                <hr class="admin-info-divider">
                <div class="admin-info-row admin-info-row-split admin-info-row-total">
                    <span>Total</span>
                    <span>$<?= number_format($order['total'], 2) ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Items panel with edit controls -->
    <!-- Alok: same cart-item structure as customer side -->
    <!--       just with edit/remove buttons enabled when status is pending -->
    <div class="cart-panel" style="margin-top:24px;">
        <div class="cart-panel-header">
            <span class="cart-panel-title">Items in this order</span>
            <span class="cart-panel-title"
                  style="font-weight:500; text-transform:none; letter-spacing:0;">
                <?= count($items) ?>
                item<?= count($items) !== 1 ? 's' : '' ?>
                <?php if(!$canEditItems): ?>
                <span style="color:var(--ink-faint); margin-left:8px;">
                    &middot; editing locked (status is <?= $badgeLabel ?>)
                </span>
                <?php endif; ?>
            </span>
        </div>

        <?php foreach($items as $item): ?>
        <div class="cart-item">
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
            <div class="cart-item-info">
                <div class="cart-item-title"><?= htmlspecialchars($item['title']) ?></div>
                <div class="cart-item-author">
                    <?= htmlspecialchars($item['author'] ?? '') ?>
                </div>
                <?php if($item['book_id'] !== null): ?>
                <!-- Kushal: show admin the available stock for this book -->
                <div class="cart-item-author" style="font-size:11px; margin-top:2px;">
                    Available stock: <?= (int)$item['current_stock'] ?>
                </div>
                <?php else: ?>
                <!-- book got deleted from books table after order placed -->
                <div class="cart-item-author"
                     style="font-size:11px; color:#c0392b; margin-top:2px;">
                    Book no longer exists
                </div>
                <?php endif; ?>
            </div>
            <div class="cart-item-price">
                $<?= number_format($item['price'], 2) ?>
            </div>

            <div class="qty-cell">
                <?php if($canEditItems && $item['book_id'] !== null): ?>
                    <div class="qty-controls">
                        <!-- Minus, same pattern as cart.php -->
                        <form method="POST" action="" class="qty-form">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <?php if($item['quantity'] <= 1): ?>
                                <input type="hidden" name="action" value="item_remove">
                                <button type="submit" class="qty-btn" title="Remove item">&minus;</button>
                            <?php else: ?>
                                <input type="hidden" name="action" value="item_update">
                                <button type="submit" name="quantity"
                                        value="<?= $item['quantity'] - 1 ?>"
                                        class="qty-btn">&minus;</button>
                            <?php endif; ?>
                        </form>
                        <span class="qty-display"><?= $item['quantity'] ?></span>
                        <!-- Plus -->
                        <form method="POST" action="" class="qty-form-plus">
                            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                            <input type="hidden" name="action" value="item_update">
                            <button type="submit" name="quantity"
                                    value="<?= $item['quantity'] + 1 ?>"
                                    class="qty-btn"
                                    <?= $item['current_stock'] < 1 ? 'disabled title="No additional stock"' : '' ?>>+</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="qty-controls">
                        <span class="qty-display">Qty: <?= $item['quantity'] ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <div class="cart-item-subtotal">
                $<?= number_format($item['subtotal'], 2) ?>
            </div>

            <?php if($canEditItems): ?>
            <!-- Trashbutton to remove item entirely -->
            <!-- Alok:confirm() so admin doesnt accidentally remove -->
            <form method="POST" action=""
                  onsubmit="return confirm('Remove this item from the order? Stock will be restored.');">
                <input type="hidden" name="action" value="item_remove">
                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                <button type="submit" class="btn-remove" title="Remove item from order">
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
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php if(empty($items)): ?>
        <div style="padding:40px 20px; text-align:center; color:var(--ink-faint);">
            No items in this order.
        </div>
        <?php endif; ?>
    </div>

</div>

<?php
require_once '../../includes/footer.php';
?>