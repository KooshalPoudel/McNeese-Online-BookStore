<?php
// books.php
// Browse Books page 
// All four members contributed:
//   Alok: image rendering, page layout
//   Kushal: DB query and PHP
//   Rojal: filter logic
//   Kelsang: CSS / styling
// Alok: this is the main browse page for books and supplies
// Kushal: also supports filtering by category via ?category= URL param

$pageTitle = 'Browse Books';
require_once '../includes/header.php';

// Kushal: read category from URL, sanitize before using in SQL
$category = sanitize($_GET['category'] ?? '');

// Kushal: build WHERE clause only if category given
$where = $category ? "WHERE category='" . $conn->real_escape_string($category) . "'" : '';
$books = $conn->query("SELECT * FROM books $where ORDER BY title ASC");

// Kushal: count books in each category for the tab badges
$counts = $conn->query("SELECT category, COUNT(*) as cnt FROM books GROUP BY category");
$cats = [];
while ($r = $counts->fetch_assoc()) $cats[$r['category']] = $r['cnt'];

// Alok: pull flash msg from session
//  e.g. after login or adding to cart
$flash = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);
?>

<!-- Page Header -->
<div class="page-header">
    <h1>Browse Books &amp; Supplies</h1>
    <p>Find your textbooks, academic materials, and office supplies</p>
</div>

<div class="section">

    <!-- Flash messages -->
    <?php if($flash): ?>
    <div class="alert alert-success"><?= $flash ?></div>
    <?php endif; ?>

    <?php if(isset($_SESSION['cart_msg'])): ?>
    <!-- Alok: cart_msg gets set when user clicks Add -->
    <!-- confirms item added -->
    <div class="alert alert-success"><?= $_SESSION['cart_msg'] ?></div>
    <?php unset($_SESSION['cart_msg']); ?>
    <?php endif; ?>

    <!--Filter Tabs (All / Textbooks / Office Supplies) -->
    <!-- Rojal: tabs change category via URL param -->
    <!-- Alok: active class marks current tab -->
    <!-- Kelsang: count badges next to each tab so user know how many items -->
    <div class="filter-tabs">
        <a href="books.php"
           class="filter-tab <?= !$category ? 'active' : '' ?>">
            All
            <span class="filter-tab-count"><?= array_sum($cats) ?></span>
        </a>
        <a href="books.php?category=textbook"
           class="filter-tab <?= $category === 'textbook' ? 'active' : '' ?>">
            Textbooks
            <span class="filter-tab-count"><?= $cats['textbook'] ?? 0 ?></span>
        </a>
        <a href="books.php?category=office_supply"
           class="filter-tab <?= $category === 'office_supply' ? 'active' : '' ?>">
            Office Supplies
            <span class="filter-tab-count"><?= $cats['office_supply'] ?? 0 ?></span>
        </a>
    </div>

    <!-- Books Grid -->
    <?php if($books->num_rows === 0): ?>

    <!-- Empty state shown if no books match category -->
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg width="22" height="22" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor"
                 stroke-width="1.5" stroke-linecap="round"
                 stroke-linejoin="round">
                <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                <path d="M6.5 2H20v20H6.5A2.5 2.5
                         0 0 1 4 19.5v-15A2.5 2.5
                         0 0 1 6.5 2z"/>
            </svg>
        </div>
        <h3>No items found</h3>
        <p>Try a different category or check back later.</p>
        <a href="books.php" class="btn-primary">View all</a>
    </div>

    <?php else: ?>

    <!-- Alok: grid of book cards -->
    <!--  each card has cover, info, price, add button -->
    <div class="books-grid">
        <?php while($book = $books->fetch_assoc()): ?>
        <div class="book-card">

            <!-- Book Cover (image OR fallback icon) -->
            <!-- Alok: image paths stored in books.cover_image (T7 fix) -->
            <div class="book-cover">
                <?php if(!empty($book['cover_image'])): ?>
                    <img src="<?= SITE_URL ?>/<?= htmlspecialchars($book['cover_image']) ?>"
                         alt="<?= htmlspecialchars($book['title']) ?>"
                         class="book-cover-img">
                <?php else: ?>
                    <!-- Fallback: category-appropriate SVG if no cover -->
                    <div class="book-cover-icon">
                        <?php if($book['category'] === 'office_supply'): ?>
                        <!-- Kelsang: pencil/tool icon for supplies -->
                        <svg width="36" height="36" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M12 19l7-7 3 3-7 7-3-3z"/>
                            <path d="M18 13l-1.5-7.5L2 2l3.5
                                     14.5L13 18l5-5z"/>
                            <path d="M2 2l7.586 7.586"/>
                            <circle cx="11" cy="11" r="2"/>
                        </svg>
                        <?php else: ?>
                        <!-- Kelsang: book icon for textbooks -->
                        <svg width="36" height="36" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1" stroke-linecap="round"
                             stroke-linejoin="round">
                            <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                            <path d="M6.5 2H20v20H6.5A2.5 2.5
                                     0 0 1 4 19.5v-15A2.5 2.5
                                     0 0 1 6.5 2z"/>
                        </svg>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- Category badge (top-right corner of cover) -->
                <span class="category-badge">
                    <?= $book['category'] === 'office_supply' ? 'Supply' : 'Textbook' ?>
                </span>
            </div>

            <!-- Book Info -->
            <div class="book-body">
                <h3><?= htmlspecialchars($book['title']) ?></h3>
                <p class="book-author"><?= htmlspecialchars($book['author']) ?></p>

                <!-- Course code shown for textbooks (CSCI 413, MATH 201 etc.) -->
                <?php if($book['course_code']): ?>
                    <span class="book-course">
                        <?= htmlspecialchars($book['course_code']) ?>
                    </span>
                <?php endif; ?>

                <?php if($book['isbn']): ?>
                    <p class="book-isbn">ISBN: <?= htmlspecialchars($book['isbn']) ?></p>
                <?php endif; ?>
                <?php if($book['description']): ?>
                    <p class="book-desc">
                        <?= htmlspecialchars($book['description']) ?>
                    </p>
                <?php endif; ?>

                <!-- Stock indicator -->
                <!-- Kushal: 3 states - out of stock, low (under 5), in stock -->
                <!-- Kelsang: dot color changes per state via css class -->
                <div class="stock-status <?= $book['stock'] > 0 ? ($book['stock'] < 5 ? 'low-stock' : 'in-stock') : 'out-stock' ?>">
                    <span class="stock-dot"></span>
                    <?php if($book['stock'] <= 0): ?>
                        Out of stock
                    <?php elseif($book['stock'] < 5): ?>
                        In stock (only <?= $book['stock'] ?> left)
                    <?php else: ?>
                        In stock
                    <?php endif; ?>
                </div>

                <!-- Price + Add to Cart -->
                <div class="book-footer">
                    <span class="book-price">
                        $<?= number_format($book['price'], 2) ?>
                    </span>

                    <?php if($book['stock'] > 0): ?>
                        <?php if(isLoggedIn()): ?>
                        <!-- Alok: posts to cart.php with action=add -->
                        <form method="POST" action="cart.php">
                            <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                            <input type="hidden" name="action" value="add">
                            <button type="submit" class="btn-add">Add</button>
                        </form>
                        <?php else: ?>
                        <!-- Not logged in, redirect to login instead -->
                        <a href="login.php" class="btn-add">Sign in</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Stock = 0, button disabled -->
                        <button class="btn-add" disabled>Unavailable</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>

</div>

<?php require_once '../includes/footer.php'; ?>