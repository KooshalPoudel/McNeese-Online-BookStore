// main.js
// McNeese Online Bookstore frontend scripts
// Auth0r: Rojal 
//        validation, password strength, form behavior
//        cancel modal added Week 7
// Rojal: kept all js in one file so we dont load multiple scripts
//        each section is self-contained


document.addEventListener('DOMContentLoaded', function () {

    // Mobile menu toggle
    // Rojal: simple toggle for the hamburger menu in header.php
    var mobileMenuBtn = document.getElementById('mobileMenuBtn');
    var mobileNav = document.getElementById('mobileNav');
    if (mobileMenuBtn && mobileNav) {
        mobileMenuBtn.addEventListener('click', function () {
            mobileNav.classList.toggle('open');  // css handles slide
        });
    }


    // Password strength checker (register.php)
    // Rojal: instant feedback as user types
    //        each rule passed = +1 score, max 5
    // Alok: strength bar look great
    //       maybe little color difference between Good and Strong
    //       but no big deal
    var pwInput = document.getElementById('password');
    var strengthBar = document.getElementById('strengthFill');
    var strengthText = document.getElementById('strengthText');

    if (pwInput && strengthBar) {
        pwInput.addEventListener('input', function () {
            var password = this.value;
            var score = 0;

            // Rojal: each check adds 1 to score
            if (password.length >= 8)  score++;          // min length
            if (password.length >= 12) score++;          // bonus for longer
            if (/[A-Z]/.test(password)) score++;         // has uppercase
            if (/[0-9]/.test(password)) score++;         // has digit
            if (/[^A-Za-z0-9]/.test(password)) score++;  // has special char

            // Rojal: score 0 to5 with matching label and color
            var levels = [
                { label: '', color: '' },
                { label: 'Weak', color: '#e74c3c' },
                { label: 'Fair', color: '#e67e22' },
                { label: 'Good', color: '#f1c40f' },
                { label: 'Strong', color: '#27ae60' },
                { label: 'Very Strong', color: '#1abc9c' }
            ];
            var result = levels[Math.min(score, 5)];  // clamp to 5 just in case
            var pct = (score / 5) * 100;

            // update the bar fill width and color
            strengthBar.style.width = pct + '%';
            strengthBar.style.background = result.color;
            if (strengthText) {
                strengthText.textContent = result.label;
                strengthText.style.color = result.color;
            }
        });
    }


    // Confirm password match (live as user types)
    // Rojal: shows error inline if 2nd password doesnt match
    var pw2 = document.getElementById('confirm_password');
    if (pw2 && pwInput) {
        pw2.addEventListener('input', function () {
            var err = document.getElementById('confirmError');
            if (err) {
                if (this.value && this.value !== pwInput.value) {
                    err.textContent = 'Passwords do not match';
                    this.classList.add('error');
                } else {
                    err.textContent = '';
                    this.classList.remove('error');
                }
            }
        });
    }


    // Auto-hide success alerts after 4 sec
    // Rojal: errors stay visible because user need to read them
    //        success messages fade out on their own
    document.querySelectorAll('.alert.alert-success').forEach(function (el) {
        // first timeout starts the fade
        setTimeout(function () {
            el.style.transition = 'opacity 0.5s';
            el.style.opacity = '0';
        }, 4000);
        // second timeout removes element after fade done
        setTimeout(function () { el.remove(); }, 4500);
    });


    // Register form validation (extra layer on top of PHP)
    // Rojal: catches bad email and missing terms before form submits
    //        instant feedback, no page reload
    // Kushal: good, lessround trips when validation fails
    var registerForm = document.getElementById('registerForm');
    var emailInput = document.getElementById('email');
    var termsInput = document.getElementById('terms');

    if (registerForm && emailInput) {
        registerForm.addEventListener('submit', function (e) {
            var emailError = document.getElementById('emailError');
            var termsError = document.getElementById('termsError');
            var hasError = false;
            var email = emailInput.value.trim().toLowerCase();

            // Email check
            if (!email) {
                e.preventDefault();
                if (emailError) {
                    emailError.textContent = 'McNeese email is required.';
                    emailError.className = 'field-error';
                }
                emailInput.classList.add('error');
                hasError = true;
            } else if (!email.endsWith('@mcneese.edu')) {
                // Rojal: must end with @mcneese.edu, McNeese students only
                e.preventDefault();
                if (emailError) {
                    emailError.textContent = 'Please use your @mcneese.edu email address.';
                    emailError.className = 'field-error';
                }
                emailInput.classList.add('error');
                hasError = true;
            } else {
                // email looks good, reset to hint style
                if (emailError) {
                    emailError.textContent = 'Only @mcneese.edu email addresses are allowed';
                    emailError.className = 'field-hint';
                }
                emailInput.classList.remove('error');
            }

            // Terms checkbox check
            if (termsInput && !termsInput.checked) {
                e.preventDefault();
                if (termsError) {
                    termsError.textContent = 'You must agree to the Terms of Service and Privacy Policy.';
                }
                hasError = true;
            } else if (termsError) {
                termsError.textContent = '';
            }

            return !hasError;
        });
    }


    // User dropdown menu toggle (header)
    // Rojal: click trigger to open, click outside to close
    var userMenu = document.getElementById('userMenu');
    if (userMenu) {
        var trigger = userMenu.querySelector('.user-trigger');
        if (trigger) {
            trigger.addEventListener('click', function () {
                userMenu.classList.toggle('open');
            });
        }
        // close menu when clicking anywhere outside
        document.addEventListener('click', function (e) {
            if (userMenu && !userMenu.contains(e.target)) {
                userMenu.classList.remove('open');
            }
        });
    }

});


// Cancel-Order modal handler (orders.php + order_details.php)
// Added by Rojal
// Kushal: lives in second DOMContentLoaded so it dont conflict
//         with the form-validation block above
document.addEventListener('DOMContentLoaded', function () {

    var cancelModal      = document.getElementById('cancelOrderModal');
    var cancelOrderIdIn  = document.getElementById('cancelOrderIdInput');
    var cancelOrderLabel = document.getElementById('cancelOrderNumberLabel');

    // Rojal: not every page has the cancel modal, bail early if missing
    if (!cancelModal) return;

    // open modal with given order info
    function openCancelModal(orderId, orderNumber) {
        if (cancelOrderIdIn)  cancelOrderIdIn.value = orderId;
        // Rojal: fallback to "#id" if no order_number set
        if (cancelOrderLabel) cancelOrderLabel.textContent = orderNumber || '#' + orderId;
        cancelModal.classList.add('is-open');
        cancelModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');  // prevents background scroll
    }

    // close modal cleanly
    function closeCancelModal() {
        cancelModal.classList.remove('is-open');
        cancelModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    // any [data-cancel-order] button opens the modal with that order data
    document.querySelectorAll('[data-cancel-order]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var oid = btn.getAttribute('data-order-id');
            var onm = btn.getAttribute('data-order-number');
            openCancelModal(oid, onm);
        });
    });

    // any [data-modal-close] element closes the modal
    cancelModal.querySelectorAll('[data-modal-close]').forEach(function (el) {
        el.addEventListener('click', closeCancelModal);
    });

    // click backdrop (outside modal box) = close
    cancelModal.addEventListener('click', function (e) {
        if (e.target === cancelModal) closeCancelModal();
    });

    // Escape key also closes
    // Rojal: small accessibility thing but nice to have
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && cancelModal.classList.contains('is-open')) {
            closeCancelModal();
        }
    });

});