<?php
$pageTitle = 'Home';
require_once 'includes/header.php';

// Fetch featured books — reuse $conn from header
$books = $conn->query("SELECT * FROM books ORDER BY id DESC LIMIT 4");
?>

<!-- HERO -->
<section class="hero">

    <div class="hero-inner">
        <div class="hero-eyebrow">
            <span></span>
            McNeese State University
        </div>

        <h1>Textbooks &amp; supplies,<br><em>all in one place</em></h1>
        <p>Browse course materials, office supplies, and more — available 24/7 for McNeese students.</p>

        <div class="hero-btns">
            <a href="pages/books.php" class="btn-primary">
                Browse catalog
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"/>
                <polyline points="12 5 19 12 12 19"/></svg>
            </a>
            <?php if(!isLoggedIn()): ?>
            <a href="pages/register.php" class="btn-outline">Create account</a>
            <?php else: ?>
            <a href="pages/cart.php" class="btn-outline">View cart</a>
            <?php endif; ?>
        </div>
        
    </div>
</section>

<!-- FEATURES -->
<div class="features-strip">
    <p class="features-label">Why students choose us</p>
    <div class="features-grid">
        <div class="feature-card">

            <div class="feature-icon-wrap">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" 
                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" 
                stroke-linejoin="round"><circle cx="11" cy="11" r="8"/>
                <line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            </div>

            <h3>Search by Course</h3>
            <p>Find books by course code, ISBN, title, or author</p>
        </div>
        <div class="feature-card">

            <div class="feature-icon-wrap">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" 
                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" 
                stroke-linejoin="round"><circle cx="9" cy="21" r="1"/>
                <circle cx="20" cy="21" r="1"/>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
                </svg>
            </div>

            <h3>Easy Checkout</h3>
            <p>Add to cart and complete your order in minutes</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon-wrap">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" 
                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" 
                stroke-linejoin="round">
                <rect x="1" y="4" width="22" height="16" rx="2" ry="2"/>
                <line x1="1" y1="10" x2="23" y2="10"/></svg>
            </div>
            <h3>Secure Payment</h3>
            <p>Safe transactions via credit card or student account</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon-wrap">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" 
                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" 
                stroke-linejoin="round">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/>
                <circle cx="12" cy="10" r="3"/></svg>
            </div>
            <h3>Order Tracking</h3>
            <p>Real-time updates on your order and delivery</p>
        </div>
        <div class="feature-card">
            <div class="feature-icon-wrap">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" 
                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" 
                stroke-linejoin="round"><rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                <line x1="12" y1="18" x2="12.01" y2="18"/></svg>
            </div>
            <h3>Mobile Ready</h3>
            <p>Access from any device — desktop, tablet, or phone</p>
        </div>
    </div>
</div>

<!-- FEATURED BOOKS -->
<div class="section">
    <div class="section-header">
        <div>
            <h2 class="section-title">Featured Books &amp; Supplies</h2>
            <p class="section-subtitle">Latest additions to our inventory</p>
        </div>
        <a href="pages/books.php" class="view-all-link">
            View all
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
            stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
        </a>
    </div>

    <div class="books-grid">
        <?php while($book = $books->fetch_assoc()): ?>
        <div class="book-card">
            <div class="book-cover">
                <?php if(!empty($book['cover_image'])): ?>
                    <img src="<?= SITE_URL ?>/<?= htmlspecialchars($book['cover_image']) ?>" 
                    alt="<?= htmlspecialchars($book['title']) ?>" class="book-cover-img">
                <?php else: ?>
                    
                    <div class="book-cover-icon">
                        <?php if($book['category'] === 'office_supply'): ?>
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" 
                        stroke="currentColor" stroke-width="1" stroke-linecap="round" 
                        stroke-linejoin="round"><path d="M12 19l7-7 3 3-7 7-3-3z"/>
                        <path d="M18 13l-1.5-7.5L2 2l3.5 14.5L13 18l5-5z"/>
                        <path d="M2 2l7.586 7.586"/><circle cx="11" cy="11" r="2"/></svg>
                        <?php else: ?>
                        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" 
                        stroke-width="1" stroke-linecap="round" 
                        stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
                        <?php endif; ?>
                    </div>

                <?php endif; ?>
                <span class="category-badge">
                <?= $book['category'] === 'office_supply' ? 'Supply' : 'Textbook' ?></span>
            </div>
            <div class="book-body">
                <h3><?= htmlspecialchars($book['title']) ?></h3>
                <p class="book-author"><?= htmlspecialchars($book['author']) ?></p>
                <?php if($book['course_code']): ?>
                    <span class="book-course"><?= htmlspecialchars($book['course_code']) ?></span>             
                    <?php endif; ?>

                <div class="book-footer">
                    <span class="book-price">$<?= number_format($book['price'], 2) ?></span>
                    <?php if(isLoggedIn()): ?>
                    <form method="POST" action="pages/cart.php">
                        <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                        <input type="hidden" name="action" value="add">
                        <button type="submit" class="btn-add">Add</button>
                    </form>
                    <?php else: ?>
                    <a href="pages/login.php" class="btn-add">Sign in</a>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- CTA BAND -->
<?php if(!isLoggedIn()): ?>
<div class="cta-band">
    <h2>Ready to get started?</h2>
    <p>Create your free account and start shopping today.</p>
    <a href="pages/register.php" class="btn-cta">Create account</a>
</div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>