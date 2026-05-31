<?php
/**
 * Public footer.
 */
?>
</main>
<footer class="public-footer">
    <div class="container footer-grid">
        <div class="footer-brand">
            <a href="<?= url('index.php') ?>" class="footer-logo">Finova Bank</a>
            <p>Modern digital banking built for students and professionals. Secure transfers, loans, fixed deposits, and instant statements.</p>
        </div>
        <div class="footer-links">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="<?= url('index.php') ?>">Home</a></li>
                <li><a href="<?= url('auth/register.php') ?>">Register</a></li>
                <li><a href="<?= url('auth/login.php') ?>">Customer Login</a></li>
                <li><a href="<?= url('admin/login.php') ?>">Admin Portal</a></li>
            </ul>
        </div>
        <div class="footer-links">
            <h4>Services</h4>
            <ul>
                <li><a href="<?= url('auth/login.php') ?>">Fund Transfer</a></li>
                <li><a href="<?= url('auth/login.php') ?>">Loans &amp; EMI</a></li>
                <li><a href="<?= url('auth/login.php') ?>">Fixed Deposits</a></li>
                <li><a href="<?= url('auth/login.php') ?>">PDF Statements</a></li>
            </ul>
        </div>
        <div class="footer-contact">
            <h4>Contact</h4>
            <p><a href="mailto:support@finovabank.com">support@finovabank.com</a></p>
            <p>Mon – Sat, 9:00 AM – 6:00 PM IST</p>
            <div class="footer-badges">
                <span>256-bit SSL</span>
                <span>OTP 2FA</span>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <div class="container footer-bottom-inner">
            <p>&copy; <?= date('Y') ?> Finova Bank. Academic Banking Management System.</p>
            <p>Secure &bull; Reliable &bull; Digital</p>
        </div>
    </div>
</footer>
</body>
</html>
