// McNeese Online Bookstore - Main JS

document.addEventListener('DOMContentLoaded', function () {

    // Mobile menu toggle
    var mobileMenuBtn = document.getElementById('mobileMenuBtn');
    var mobileNav = document.getElementById('mobileNav');
    if (mobileMenuBtn && mobileNav) {
        mobileMenuBtn.addEventListener('click', function () {
            mobileNav.classList.toggle('open');
        });
    }

    // Password strength checker
    var pwInput = document.getElementById('password');
    var strengthBar = document.getElementById('strengthFill');
    var strengthText = document.getElementById('strengthText');

    if (pwInput && strengthBar) {
        pwInput.addEventListener('input', function () {
            var password = this.value;
            var score = 0;
            if (password.length >= 8)  score++;
            if (password.length >= 12) score++;
            if (/[A-Z]/.test(password)) score++;
            if (/[0-9]/.test(password)) score++;
            if (/[^A-Za-z0-9]/.test(password)) score++;

            var levels = [
                { label: '', color: '' },
                { label: 'Weak', color: '#e74c3c' },
                { label: 'Fair', color: '#e67e22' },
                { label: 'Good', color: '#f1c40f' },
                { label: 'Strong', color: '#27ae60' },
                { label: 'Very Strong', color: '#1abc9c' }
            ];
            var result = levels[Math.min(score, 5)];
            var pct = (score / 5) * 100;

            strengthBar.style.width = pct + '%';
            strengthBar.style.background = result.color;
            if (strengthText) {
                strengthText.textContent = result.label;
                strengthText.style.color = result.color;
            }
        });
    }

    // Confirm password match
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

    // Auto-hide success alerts only (keep errors visible until user sees them)
    document.querySelectorAll('.alert.alert-success').forEach(function (el) {
        setTimeout(function () { el.style.transition = 'opacity 0.5s'; el.style.opacity = '0'; }, 4000);
        setTimeout(function () { el.remove(); }, 4500);
    });

    // Register form validation
    var registerForm = document.getElementById('registerForm');
    var emailInput = document.getElementById('email');
    var termsInput = document.getElementById('terms');

    if (registerForm && emailInput) {
        registerForm.addEventListener('submit', function (e) {
            var emailError = document.getElementById('emailError');
            var termsError = document.getElementById('termsError');
            var hasError = false;
            var email = emailInput.value.trim().toLowerCase();

            if (!email) {
                e.preventDefault();
                if (emailError) {
                    emailError.textContent = 'McNeese email is required.';
                    emailError.className = 'field-error';
                }
                emailInput.classList.add('error');
                hasError = true;
            } else if (!email.endsWith('@mcneese.edu')) {
                e.preventDefault();
                if (emailError) {
                    emailError.textContent = 'Please use your @mcneese.edu email address.';
                    emailError.className = 'field-error';
                }
                emailInput.classList.add('error');
                hasError = true;
            } else {
                if (emailError) {
                    emailError.textContent = 'Only @mcneese.edu email addresses are allowed';
                    emailError.className = 'field-hint';
                }
                emailInput.classList.remove('error');
            }

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

    // User menu toggle & close on outside click
    var userMenu = document.getElementById('userMenu');
    if (userMenu) {
        var trigger = userMenu.querySelector('.user-trigger');
        if (trigger) {
            trigger.addEventListener('click', function () {
                userMenu.classList.toggle('open');
            });
        }
        document.addEventListener('click', function (e) {
            if (userMenu && !userMenu.contains(e.target)) {
                userMenu.classList.remove('open');
            }
        });
    }

});

document.addEventListener('DOMContentLoaded', function () {
 
    var cancelModal      = document.getElementById('cancelOrderModal');
    var cancelOrderIdIn  = document.getElementById('cancelOrderIdInput');
    var cancelOrderLabel = document.getElementById('cancelOrderNumberLabel');
 
    if (!cancelModal) return; // no modal on this page
 
    function openCancelModal(orderId, orderNumber) {
        if (cancelOrderIdIn)  cancelOrderIdIn.value = orderId;
        if (cancelOrderLabel) cancelOrderLabel.textContent = orderNumber || '#' + orderId;
        cancelModal.classList.add('is-open');
        cancelModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }
 
    function closeCancelModal() {
        cancelModal.classList.remove('is-open');
        cancelModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }
 
    // Any button marked data-cancel-order opens the modal
    document.querySelectorAll('[data-cancel-order]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var oid = btn.getAttribute('data-order-id');
            var onm = btn.getAttribute('data-order-number');
            openCancelModal(oid, onm);
        });
    });
 
    // Any element marked data-modal-close closes the modal
    cancelModal.querySelectorAll('[data-modal-close]').forEach(function (el) {
        el.addEventListener('click', closeCancelModal);
    });
 
    // Click outside the modal body closes it
    cancelModal.addEventListener('click', function (e) {
        if (e.target === cancelModal) closeCancelModal();
    });
 
    // Escape key closes it
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && cancelModal.classList.contains('is-open')) {
            closeCancelModal();
        }
    });
 
});
