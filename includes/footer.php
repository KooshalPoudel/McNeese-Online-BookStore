<?php
// footer.php
// shared site footer, included on every page
// Built by Alok and Kelsang (Week 1)
// Alok: closes the <main> tag from header.php
// Kelsang: keep this in sync with header.php
//          both must be included as a pair
?>
</main>

<!-- =SITE FOOTER -->
<footer class="site-footer">
    <div class="footer-inner">

        <!-- Brandcolumn -->
        <!-- Alok: same logo as header but small -->
        <!-- text color changes in css -->
        <div class="footer-brand">
            <div class="footer-logo">
                <div class="logo-mark">
                    <svg width="16" height="16" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5
                                 0 0 1 4 19.5v-15A2.5 2.5
                                 0 0 1 6.5 2z"/>
                    </svg>
                </div>
                <span class="footer-logo-name">McNeese Bookstore</span>
            </div>
            <p class="footer-desc">
                Your one-stop shop for textbooks, academic materials,
                and supplies at McNeese State University.
            </p>
            <p class="footer-course-tag">CSCI 413 &mdash; Software Engineering</p>
        </div>

        <!-- Navigate column -->
        <div class="footer-col">
            <h4>Navigate</h4>
            <ul>
                <li><a href="<?= SITE_URL ?>/index.php">Home</a></li>
                <li><a href="<?= SITE_URL ?>/pages/books.php">Browse Books</a></li>
                <li><a href="<?= SITE_URL ?>/pages/search.php">Search</a></li>
                <li><a href="<?= SITE_URL ?>/pages/register.php">Create Account</a></li>
            </ul>
        </div>

        <!-- Categories column -->
        <!-- Alok: these link to books page with ?category= filter -->
        <div class="footer-col">
            <h4>Categories</h4>
            <ul>
                <li><a href="<?= SITE_URL ?>/pages/books.php?category=textbook">Textbooks</a></li>
                <li><a href="<?= SITE_URL ?>/pages/books.php?category=office_supply">Office Supplies</a></li>
            </ul>
        </div>

        <!-- Contact column -->
        <!-- Kelsang: svg icons instead of emoji -->
        <!-- we replaced emojis for cleaner look -->
        <div class="footer-col">
            <h4>Contact</h4>
            <address>
                <p>McNeese State University</p>
                <p>Lake Charles, LA 70609</p>

                <p class="footer-contact-item">
                    <svg width="13" height="13" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M4 4h16c1.1 0 2 .9 2 2v12c0
                                 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2
                                 2-2z"/>
                        <polyline points="22,6 12,13 2,6"/>
                    </svg>
                    bookstore@mcneese.edu
                </p>

                <p class="footer-contact-item">
                    <svg width="13" height="13" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="1.8" stroke-linecap="round"
                         stroke-linejoin="round">
                        <path d="M22 16.92v3a2 2 0 0 1-2.18
                                 2 19.79 19.79 0 0 1-8.63-3.07A19.5
                                 19.5 0 0 1 4.69 12 19.79 19.79 0 0
                                 1 1.61 3.38 2 2 0 0 1 3.58 1h3a2 2
                                 0 0 1 2 1.72c.127.96.361 1.903.7
                                 2.81a2 2 0 0 1-.45 2.11L7.91 8.5a16
                                 16 0 0 0 6 6l.92-.92a2 2 0 0 1
                                 2.11-.45c.907.339 1.85.573 2.81.7A2
                                 2 0 0 1 22 16.92z"/>
                    </svg>
                    (337) 475-5000
                </p>
            </address>
        </div>
    </div>

    <!-- Footer bottom strip -->
    <!-- Kelsang: copyright + team names -->
    <!-- date('Y') so year updates automatic -->
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> McNeese Online Bookstore &mdash; CSCI 413 Project</p>
        <p class="footer-team">
            Kushal Poudel &middot; Alok Poudel &middot;
            Rojal Shrestha &middot; Kelsang Yonjan
        </p>
    </div>
</footer>

<!-- main.js loaded at bottom so DOM is ready -->
<script src="<?= SITE_URL ?>/js/main.js"></script>
<?php
// Kushal: close DB connection if opened this request
//         using isset because some pages close it themselves
?>
</body>
</html>