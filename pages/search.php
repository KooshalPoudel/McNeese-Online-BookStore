<?php
// search.php
// search page with sidebar filters 
// Search bar: Alok + Kushal (GET form, URL params)
// Sidebar filters: Rojal + Kelsang (price, availability, sort)
// PHP backend logic: Kushal + Rojal (build SQL from filters)

$pageTitle = 'Search Books';
require_once '../includes/header.php';

$results = [];

// Alok: read GET params from search bar
//   use sanitize() to clean them
$query = sanitize($_GET['q'] ?? '');
$category = sanitize($_GET['category'] ?? '');

// Rojal: price range only counts as set if value is non-empty
//        FIX (Week 5): empty price field used to filter incorrectly
//        because we treated "" as 0
//        now we check '' explicitly first
// Kushal: yeah that bug took me a bit to spot, good catch
$priceMin = (isset($_GET['price_min']) && $_GET['price_min'] !== '') ? (float)$_GET['price_min'] : null;
$priceMax = (isset($_GET['price_max']) && $_GET['price_max'] !== '') ? (float)$_GET['price_max'] : null;

$availability = sanitize($_GET['availability'] ?? '');
$sort = sanitize($_GET['sort'] ?? 'title_asc');  // default sort by title A-Z


// Build SQL query dynamically based on filters
// Kushal: every active filter adds one condition to WHERE clause
$conditions = [];

// Alok: text search across multiple columns , uses LIKE %query%
if (!empty($query)) {
    $q = $conn->real_escape_string($query);
    $conditions[] = "(title LIKE '%$q%' OR author LIKE '%$q%' OR isbn LIKE '%$q%' OR course_code LIKE '%$q%' OR description LIKE '%$q%')";
}

// Category filter. (textbook OR office_supply)
if ($category === 'textbook') $conditions[] = "category='textbook'";
elseif ($category === 'office_supply') $conditions[] = "category='office_supply'";

// Price range (skip if 0 or null)
if ($priceMin !== null && $priceMin > 0) $conditions[] = "price >= " . $priceMin;
if ($priceMax !== null && $priceMax > 0) $conditions[] = "price <= " . $priceMax;

// Availability filter (Rojal)
if ($availability === 'in_stock') $conditions[] = "stock > 0";
elseif ($availability === 'out_of_stock') $conditions[] = "stock = 0";

// Buildfinal SQL
$sql = "SELECT * FROM books";
if (!empty($conditions)) {
    $sql .= " WHERE " . implode(" AND ", $conditions);
}

// Sort options (Rojal)
switch ($sort) {
    case 'price_low':  $sql .= " ORDER BY price ASC"; break;
    case 'price_high': $sql .= " ORDER BY price DESC"; break;
    case 'title_desc': $sql .= " ORDER BY title DESC"; break;
    case 'newest':     $sql .= " ORDER BY created_at DESC"; break;
    default:           $sql .= " ORDER BY title ASC"; break;  // title A-Z
}

$res = $conn->query($sql);
if ($res) {
    while ($r = $res->fetch_assoc()) $results[] = $r;
}

// Alok: hasSearched = true if user actually applied any filter or query
//       used to decide between "Start your search" or actual results
$hasSearched = !empty($query) || !empty($category) || $priceMin !== null || $priceMax !== null || !empty($availability);

/**
 * Helper: build URL for category pill while keeping current filters
 * Kushal: wrote this so when user clicks a pill, other filters
 *         like price/sort dont get wiped out
 */
function buildPillUrl($cat, $query, $priceMin, $priceMax, $availability, $sort) {
    $params = [];
    if ($query) $params['q'] = $query;
    if ($cat) $params['category'] = $cat;
    if ($priceMin !== null) $params['price_min'] = $priceMin;
    if ($priceMax !== null) $params['price_max'] = $priceMax;
    if ($availability) $params['availability'] = $availability;
    if ($sort && $sort !== 'title_asc') $params['sort'] = $sort;
    return 'search.php' . ($params ? '?' . http_build_query($params) : '');
}
?>

<!-- SEARCH HERO (top of page) -->
<!-- Alok: big search bar at top with category pills below -->
<div class="search-hero">
    <h1>Find your <em>textbooks</em> &amp; supplies</h1>
    <p>Search across our full catalog &mdash; books, supplies, and more</p>

    <!-- Search bar form (GET method, passes params via URL) -->
    <!-- Alok: hidden inputs preserve other filter values when re-searching -->
    <form method="GET" action="">
        <div class="search-bar-wrap">
            <input type="search" name="q"
                   value="<?= htmlspecialchars($query) ?>"
                   placeholder="Search by title, author, ISBN, course code...">

            <?php if($category): ?>
                <input type="hidden" name="category"
                       value="<?= htmlspecialchars($category) ?>">
            <?php endif; ?>
            <?php if($priceMin !== null): ?>
                <input type="hidden" name="price_min" value="<?= $priceMin ?>">
            <?php endif; ?>
            <?php if($priceMax !== null): ?>
                <input type="hidden" name="price_max" value="<?= $priceMax ?>">
            <?php endif; ?>
            <?php if($availability): ?>
                <input type="hidden" name="availability"
                       value="<?= htmlspecialchars($availability) ?>">
            <?php endif; ?>
            <?php if($sort !== 'title_asc'): ?>
                <input type="hidden" name="sort"
                       value="<?= htmlspecialchars($sort) ?>">
            <?php endif; ?>

            <button type="submit" class="search-bar-btn">
                <svg width="15" height="15" viewBox="0 0 24 24"
                     fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round"
                     stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                Search
            </button>
        </div>

        <!-- Category pill buttons (use   buildPillUrl helper) -->
        <div class="category-pills">
            <a href="<?= buildPillUrl('', $query, $priceMin, $priceMax, $availability, $sort) ?>"
               class="pill <?= !$category ? 'active' : '' ?>">All</a>

            <a href="<?= buildPillUrl('textbook', $query, $priceMin, $priceMax, $availability, $sort) ?>"
               class="pill <?= $category === 'textbook' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5
                             0 0 1 4 19.5v-15A2.5 2.5
                             0 0 1 6.5 2z"/>
                </svg>
                Textbooks
            </a>

            <a href="<?= buildPillUrl('office_supply', $query, $priceMin, $priceMax, $availability, $sort) ?>"
               class="pill <?= $category === 'office_supply' ? 'active' : '' ?>">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="1.8" stroke-linecap="round"
                     stroke-linejoin="round">
                    <path d="M12 19l7-7 3 3-7 7-3-3z"/>
                    <path d="M18 13l-1.5-7.5L2 2l3.5
                             14.5L13 18l5-5z"/>
                    <path d="M2 2l7.586 7.586"/>
                    <circle cx="11" cy="11" r="2"/>
                </svg>
                Office Supplies
            </a>
        </div>
    </form>
</div>

<!-- Layout:   Sidebar + Results -->
<!-- Kelsang: one big GET form so all sidebar filters submit together -->
<form method="GET" action="">
    <input type="hidden" name="q" value="<?= htmlspecialchars($query) ?>">
    <?php if($category): ?>
        <input type="hidden" name="category"
               value="<?= htmlspecialchars($category) ?>">
    <?php endif; ?>

    <div class="search-layout">

        <!-- SIDEBAR (filters) -->
        <!-- Rojal + Kelsang: sidebar with price range, availability, sort -->
        <aside class="search-sidebar">

            <!-- Price Range section -->
            <!-- Rojal: two number inputs for min and max -->
            <!--   step=0.01 for cents -->
            <div class="sidebar-section">
                <div class="sidebar-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round"
                         stroke-linejoin="round">
                        <line x1="12" y1="1" x2="12" y2="23"/>
                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0
                                 7h5a3.5 3.5 0 0 1 0 7H6"/>
                    </svg>
                    Price Range
                </div>
                <div class="price-row">
                    <input type="number" name="price_min" class="price-input"
                           placeholder="Min" min="0" step="0.01"
                           value="<?= $priceMin !== null ? htmlspecialchars($priceMin) : '' ?>">
                    <span class="price-dash">&ndash;</span>
                    <input type="number" name="price_max" class="price-input"
                           placeholder="Max" min="0" step="0.01"
                           value="<?= $priceMax !== null ? htmlspecialchars($priceMax) : '' ?>">
                </div>
            </div>

            <!-- Availability radio group -->
            <!-- Kelsang: customized the radio style with css to match brand -->
            <div class="sidebar-section">
                <div class="sidebar-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    Availability
                </div>
                <label class="filter-option">
                    <input type="radio" name="availability" value=""
                           <?= !$availability ? 'checked' : '' ?>>
                    <span class="filter-option-label">All</span>
                </label>
                <label class="filter-option">
                    <input type="radio" name="availability" value="in_stock"
                           <?= $availability === 'in_stock' ? 'checked' : '' ?>>
                    <span class="filter-option-label">In Stock</span>
                </label>
                <label class="filter-option">
                    <input type="radio" name="availability" value="out_of_stock"
                           <?= $availability === 'out_of_stock' ? 'checked' : '' ?>>
                    <span class="filter-option-label">Out of Stock</span>
                </label>
            </div>

            <!-- Sort options -->
            <!-- Rojal: 5 sort options, default is Title A-Z -->
            <div class="sidebar-section">
                <div class="sidebar-label">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round"
                         stroke-linejoin="round">
                        <line x1="4" y1="6" x2="20" y2="6"/>
                        <line x1="4" y1="12" x2="14" y2="12"/>
                        <line x1="4" y1="18" x2="8" y2="18"/>
                    </svg>
                    Sort
                </div>
                <label class="filter-option">
                    <input type="radio" name="sort" value="title_asc"
                           <?= $sort === 'title_asc' ? 'checked' : '' ?>>
                    <span class="filter-option-label">Title A&ndash;Z</span>
                </label>
                <label class="filter-option">
                    <input type="radio" name="sort" value="title_desc"
                           <?= $sort === 'title_desc' ? 'checked' : '' ?>>
                    <span class="filter-option-label">Title Z&ndash;A</span>
                </label>
                <label class="filter-option">
                    <input type="radio" name="sort" value="price_low"
                           <?= $sort === 'price_low' ? 'checked' : '' ?>>
                    <span class="filter-option-label">Price: Low to High</span>
                </label>
                <label class="filter-option">
                    <input type="radio" name="sort" value="price_high"
                           <?= $sort === 'price_high' ? 'checked' : '' ?>>
                    <span class="filter-option-label">Price: High to Low</span>
                </label>
                <label class="filter-option">
                    <input type="radio" name="sort" value="newest"
                           <?= $sort === 'newest' ? 'checked' : '' ?>>
                    <span class="filter-option-label">Newest First</span>
                </label>
            </div>

            <!-- Apply / Clear buttons -->
            <!-- Kelsang: clear preserves search query but drops all filters -->
            <div class="sidebar-apply">
                <button type="submit" class="btn-apply">Apply Filters</button>
                <a href="search.php<?= $query ? '?q=' . urlencode($query) : '' ?>"
                   class="btn-clear">Clear all filters</a>
            </div>

        </aside>

        <!-- RESULTS AREA -->
        <div class="results-area">

            <!-- Result count summary -->
            <?php if ($hasSearched): ?>
                <p class="results-meta">
                    <strong><?= count($results) ?></strong>
                    result<?= count($results) !== 1 ? 's' : '' ?>
                    <?php if($query): ?>
                        for &ldquo;<?= htmlspecialchars($query) ?>&rdquo;
                    <?php endif; ?>
                    <?php if($category): ?>
                        in <strong>
                            <?= $category === 'office_supply' ? 'Office Supplies' : 'Textbooks' ?>
                        </strong>
                    <?php endif; ?>
                </p>
            <?php endif; ?>

            <!-- Result cards -->
            <?php if (!empty($results)): ?>
            <div class="books-grid">
                <?php foreach($results as $book): ?>
                <!-- Alok: book card markup same as books.php -->
                <!--       could refactor into shared partial later -->
                <div class="book-card">
                    <div class="book-cover">
                        <?php if(!empty($book['cover_image'])): ?>
                            <img src="<?= SITE_URL ?>/<?= htmlspecialchars($book['cover_image']) ?>"
                                 alt="<?= htmlspecialchars($book['title']) ?>"
                                 class="book-cover-img">
                        <?php else: ?>
                            <div class="book-cover-icon">
                                <?php if($book['category'] === 'office_supply'): ?>
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
                        <span class="category-badge">
                            <?= $book['category'] === 'office_supply' ? 'Supply' : 'Textbook' ?>
                        </span>
                    </div>
                    <div class="book-body">
                        <h3><?= htmlspecialchars($book['title']) ?></h3>
                        <p class="book-author">
                            <?= htmlspecialchars($book['author']) ?>
                        </p>
                        <?php if($book['course_code']): ?>
                            <span class="book-course">
                                <?= htmlspecialchars($book['course_code']) ?>
                            </span>
                        <?php endif; ?>
                        <?php if($book['isbn']): ?>
                            <p class="book-isbn">
                                ISBN: <?= htmlspecialchars($book['isbn']) ?>
                            </p>
                        <?php endif; ?>
                        <?php if($book['description']): ?>
                            <p class="book-desc">
                                <?= htmlspecialchars($book['description']) ?>
                            </p>
                        <?php endif; ?>
                        <div class="stock-status <?= $book['stock'] > 0 ? ($book['stock'] < 5 ? 'low-stock' : 'in-stock') : 'out-stock' ?>">
                            <span class="stock-dot"></span>
                            <?php if($book['stock'] <= 0): ?>Out of stock
                            <?php elseif($book['stock'] < 5): ?>In stock (only <?= $book['stock'] ?> left)
                            <?php else: ?>In stock<?php endif; ?>
                        </div>
                        <div class="book-footer">
                            <span class="book-price">
                                $<?= number_format($book['price'], 2) ?>
                            </span>
                            <?php if($book['stock'] > 0): ?>
                                <?php if(isLoggedIn()): ?>
                                <form method="POST" action="cart.php">
                                    <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                                    <input type="hidden" name="action" value="add">
                                    <button type="submit" class="btn-add">Add</button>
                                </form>
                                <?php else: ?>
                                <a href="login.php" class="btn-add">Sign in</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn-add" disabled>Unavailable</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?php elseif($hasSearched): ?>
            <!-- Searched but found nothing -->
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
                <h3>No results found</h3>
                <p>Try a different keyword, category, or adjust your filters.</p>
                <a href="books.php" class="btn-primary">Browse all books</a>
            </div>

            <?php else: ?>
            <!-- First visit, no search yet -->
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
                <h3>Start your search</h3>
                <p>Search for textbooks by title, author, ISBN, or course code. Browse office supplies by name or keyword.</p>
            </div>
            <?php endif; ?>

        </div>
    </div>
</form>

<?php require_once '../includes/footer.php'; ?>