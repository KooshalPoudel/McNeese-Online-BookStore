<?php
// admin/orders.php
// Admin Manage Orders dashboard (Week 7 admin extension)
// Backend & permissions: Kushal (admin guard, status updates, filters)
// Dashboard metrics & queries: Kushal
// UI / table / metric cards / filters: Alok and Kelsang
// Kushal: dashboard for admin to see all orders across users
//   status updates, search/filter, metrics at top

$pageTitle = 'Admin — Manage Orders';
require_once '../../includes/config.php';

// Security gate, blocks non-admins
require_once '../../includes/admin_guard.php';

$conn = getConnection();


// Handle inline status update from the dashboard
// Kushal: same status change logic as order_details.php
//         but inline from dropdown in the table
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $order_id   = (int)($_POST['order_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? '';
    $allowed    = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

    if ($order_id > 0 && in_array($new_status, $allowed, true)) {
        // Fetch current status first so we can handle stock restore on cancel
        $cur = $conn->query("SELECT status FROM orders WHERE id=$order_id LIMIT 1");
        if ($cur && $cur->num_rows === 1) {
            $oldStatus = $cur->fetch_assoc()['status'];

            // If admin is cancelling an order that wasnt already cancelled,
            // restore the stock (same logic as user self-cancel)
            if ($new_status === 'cancelled' && $oldStatus !== 'cancelled') {
                $items = $conn->query("SELECT book_id, quantity FROM order_items WHERE order_id=$order_id");
                while ($it = $items->fetch_assoc()) {
                    if ($it['book_id']) {
                        $bid = (int)$it['book_id'];
                        $q   = (int)$it['quantity'];
                        $conn->query("UPDATE books SET stock = stock + $q WHERE id=$bid");
                    }
                }
            }

            // Kushal: if admin is un-cancelling (rare, cancelled -> pending)
            //         we do NOT auto subtract stock
            //         admin can adjust stock manually
            $ns_e = $conn->real_escape_string($new_status);
            $conn->query("UPDATE orders SET status='$ns_e' WHERE id=$order_id");

            $_SESSION['flash_success'] = 'Order status updated to ' . ucfirst($new_status) . '.';
        }
    }
    $conn->close();
    // Preserve filters on redirect
    $qs = $_GET ? '?' . http_build_query($_GET) : '';
    header('Location: ' . SITE_URL . '/pages/admin/orders.php' . $qs);
    exit();
}


// Read filter params
$filterStatus = sanitize($_GET['status']  ?? '');
$searchTerm   = sanitize($_GET['q']       ?? '');

$allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
if ($filterStatus !== '' && !in_array($filterStatus, $allowedStatuses, true)) {
    $filterStatus = '';
}

// Build query with filters
// Kushal: filters work together (AND), search across order#/name/email
$whereParts = [];
if ($filterStatus !== '') {
    $s_e = $conn->real_escape_string($filterStatus);
    $whereParts[] = "o.status = '$s_e'";
}
if ($searchTerm !== '') {
    $q_e = $conn->real_escape_string($searchTerm);
    $whereParts[] = "(o.order_number LIKE '%$q_e%' OR o.full_name LIKE '%$q_e%' OR u.email LIKE '%$q_e%')";
}
$whereSql = $whereParts ? ('WHERE ' . implode(' AND ', $whereParts)) : '';

// Kushal: ORDER BY case statement so pending orders show first
//         most urgent stuff at the top
$ordersRes = $conn->query("
    SELECT
        o.*,
        u.email AS user_email,
        u.first_name AS user_first,
        u.last_name  AS user_last,
        COUNT(oi.id) AS item_count,
        SUM(oi.quantity) AS total_qty
    FROM orders o
    LEFT JOIN users u       ON u.id = o.user_id
    LEFT JOIN order_items oi ON oi.order_id = o.id
    $whereSql
    GROUP BY o.id
    ORDER BY
        CASE o.status
            WHEN 'pending'    THEN 1
            WHEN 'processing' THEN 2
            WHEN 'shipped'    THEN 3
            WHEN 'delivered'  THEN 4
            WHEN 'cancelled'  THEN 5
            ELSE 6
        END,
        o.created_at DESC
");
$orders = [];
while ($r = $ordersRes->fetch_assoc()) {
    $orders[] = $r;
}

// Dashboard metrics (computed across ALL orders, not filtered)
// Kushal: counts per status + total revenue
//         revenue excludes cancelled orders
$metricsRes = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(CASE WHEN status='pending'    THEN 1 ELSE 0 END) AS pending_count,
        SUM(CASE WHEN status='processing' THEN 1 ELSE 0 END) AS processing_count,
        SUM(CASE WHEN status='shipped'    THEN 1 ELSE 0 END) AS shipped_count,
        SUM(CASE WHEN status='delivered'  THEN 1 ELSE 0 END) AS delivered_count,
        SUM(CASE WHEN status='cancelled'  THEN 1 ELSE 0 END) AS cancelled_count,
        SUM(CASE WHEN status != 'cancelled' THEN total ELSE 0 END) AS revenue
    FROM orders
");
$metrics = $metricsRes->fetch_assoc();

require_once '../../includes/header.php';

// Helpers
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

function displayOrderNo($o) {
    if (!empty($o['order_number'])) return $o['order_number'];
    return '#' . str_pad($o['id'], 5, '0', STR_PAD_LEFT);
}
?>

<!-- Admin ribbon -->
<!-- Kelsang: same ribbon as order_details.php for consistency -->
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
    <h1>Manage Orders</h1>
    <p>Review, update status, and manage all customer orders</p>
</div>

<div class="section">

    <?php if(isset($_SESSION['flash_success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['flash_success'] ?></div>
    <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>

    <!-- Dashboard metric cards -->
    <!-- Alok: 6-card grid showing key counts at a glance -->
    <!-- Kelsang: pending and revenue have accent style to stand out -->
    <div class="admin-metrics">
        <div class="admin-metric">
            <div class="admin-metric-label">Total orders</div>
            <div class="admin-metric-value"><?= (int)$metrics['total'] ?></div>
        </div>
        <div class="admin-metric admin-metric-accent">
            <div class="admin-metric-label">Pending</div>
            <div class="admin-metric-value"><?= (int)$metrics['pending_count'] ?></div>
        </div>
        <div class="admin-metric">
            <div class="admin-metric-label">Processing</div>
            <div class="admin-metric-value"><?= (int)$metrics['processing_count'] ?></div>
        </div>
        <div class="admin-metric">
            <div class="admin-metric-label">Shipped</div>
            <div class="admin-metric-value"><?= (int)$metrics['shipped_count'] ?></div>
        </div>
        <div class="admin-metric">
            <div class="admin-metric-label">Delivered</div>
            <div class="admin-metric-value"><?= (int)$metrics['delivered_count'] ?></div>
        </div>
        <div class="admin-metric admin-metric-revenue">
            <div class="admin-metric-label">Revenue</div>
            <div class="admin-metric-value">
                $<?= number_format((float)$metrics['revenue'], 2) ?>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <!-- Alok: search box + status dropdown -->
    <!-- Kelsang: status dropdown auto-submits on change for fast filtering -->
    <form method="GET" action="" class="admin-filter-bar">
        <div class="admin-filter-search">
            <svg width="14" height="14" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round"
                 stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
            <input type="search" name="q"
                   placeholder="Search by order #, customer name, or email"
                   value="<?= htmlspecialchars($searchTerm) ?>">
        </div>
        <div class="admin-filter-status">
            <select name="status" onchange="this.form.submit()">
                <option value="">All statuses</option>
                <?php foreach($allowedStatuses as $s): ?>
                <option value="<?= $s ?>" <?= $filterStatus === $s ? 'selected' : '' ?>>
                    <?= ucfirst($s) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="admin-filter-btn">Apply</button>
        <?php if($filterStatus !== '' || $searchTerm !== ''): ?>
        <a href="orders.php" class="admin-filter-clear">Clear</a>
        <?php endif; ?>
    </form>

    <!-- Orders table -->
    <?php if(empty($orders)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg width="22" height="22" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor"
                 stroke-width="1.5" stroke-linecap="round"
                 stroke-linejoin="round">
                <circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
            </svg>
        </div>
        <h3>No orders match your filters</h3>
        <p>Try adjusting your search or status filter.</p>
    </div>
    <?php else: ?>

    <!-- Alok: full orders table, sortable by status priority -->
    <!-- Kelsang: status dropdown in each row uses color class per status -->
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Placed</th>
                    <th class="admin-th-center">Items</th>
                    <th class="admin-th-right">Total</th>
                    <th>Status</th>
                    <th class="admin-th-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($orders as $o):
                    [$badgeClass, $badgeLabel] = statusBadge($o['status']);
                ?>
                <tr>
                    <td>
                        <div class="admin-order-no">
                            <?= htmlspecialchars(displayOrderNo($o)) ?>
                        </div>
                    </td>
                    <td>
                        <div class="admin-customer-name">
                            <?= htmlspecialchars(trim(($o['user_first'] ?? '') . ' ' . ($o['user_last'] ?? ''))) ?>
                        </div>
                        <div class="admin-customer-email">
                            <?= htmlspecialchars($o['user_email'] ?? '—') ?>
                        </div>
                    </td>
                    <td>
                        <div class="admin-date-main">
                            <?= date('M j, Y', strtotime($o['created_at'])) ?>
                        </div>
                        <div class="admin-date-sub">
                            <?= date('g:i A', strtotime($o['created_at'])) ?>
                        </div>
                    </td>
                    <td class="admin-td-center"><?= (int)$o['total_qty'] ?></td>
                    <td class="admin-td-right admin-total-cell">
                        $<?= number_format($o['total'], 2) ?>
                    </td>
                    <td>
                        <!-- Kushal: status dropdown auto-submits, preserves filter URL -->
                        <form method="POST"
                              action="<?= $_GET ? '?' . htmlspecialchars(http_build_query($_GET)) : '' ?>"
                              class="admin-status-form">
                            <input type="hidden" name="action"   value="update_status">
                            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                            <select name="new_status"
                                    class="admin-status-select admin-status-select-<?= $o['status'] ?>"
                                    onchange="this.form.submit()">
                                <?php foreach($allowedStatuses as $s): ?>
                                <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>>
                                    <?= ucfirst($s) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </td>
                    <td class="admin-td-right">
                        <a href="order_details.php?id=<?= $o['id'] ?>" class="admin-link-btn">
                            View
                            <svg width="11" height="11" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor"
                                 stroke-width="2.5" stroke-linecap="round"
                                 stroke-linejoin="round">
                                <line x1="5" y1="12" x2="19" y2="12"/>
                                <polyline points="12 5 19 12 12 19"/>
                            </svg>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php endif; ?>
</div>

<?php
// Note: dont close $conn here, footer.php handles that
require_once '../../includes/footer.php';
?>