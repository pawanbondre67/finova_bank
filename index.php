<?php
/**
 * Finova Bank - Landing / home page
 */
require_once __DIR__ . '/includes/functions.php';
$page_title = 'Welcome';
include __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div class="hero-bg" aria-hidden="true"></div>
    <div class="container hero-inner">
        <span class="hero-badge">Trusted Digital Banking</span>
        <h1>Bank smarter with <span>Finova</span></h1>
        <p class="hero-lead">Manage accounts, transfer funds, apply for loans, and grow your savings — all in one secure, beautifully simple platform.</p>
        <div class="hero-actions">
            <a href="<?= url('auth/register.php') ?>" class="btn btn-primary btn-lg">Open an Account</a>
            <a href="<?= url('auth/login.php') ?>" class="btn btn-outline-light btn-lg">Sign In</a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat">
                <strong>2FA</strong>
                <span>OTP protected login</span>
            </div>
            <div class="hero-stat">
                <strong>Instant</strong>
                <span>Fund transfers</span>
            </div>
            <div class="hero-stat">
                <strong>PDF</strong>
                <span>Account statements</span>
            </div>
        </div>
    </div>
</section>

<section class="trust-strip">
    <div class="container trust-strip-inner">
        <span>BCrypt encryption</span>
        <span>Prepared SQL queries</span>
        <span>Email notifications</span>
        <span>Admin dashboard</span>
    </div>
</section>

<section class="features" id="features">
    <div class="container">
        <div class="section-head">
            <span class="section-label">Why Finova</span>
            <h2>Everything you need in one place</h2>
            <p>From everyday banking to loans and fixed deposits — built for clarity, speed, and security.</p>
        </div>
        <div class="features-grid">
            <article class="feature-card">
                <div class="feature-icon feature-icon-shield" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M12 2l8 4v6c0 5-3.5 9-8 10-4.5-1-8-5-8-10V6l8-4z"/><path d="M9 12l2 2 4-4"/></svg>
                </div>
                <h3>Secure Banking</h3>
                <p>BCrypt passwords, OTP two-factor authentication, and prepared SQL statements protect your data.</p>
            </article>
            <article class="feature-card">
                <div class="feature-icon feature-icon-transfer" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M7 17l-4-4 4-4"/><path d="M3 13h12"/><path d="M17 7l4 4-4 4"/><path d="M21 11H9"/></svg>
                </div>
                <h3>Fund Transfers</h3>
                <p>Instant transfers between Finova accounts with email notifications and full transaction history.</p>
            </article>
            <article class="feature-card">
                <div class="feature-icon feature-icon-loan" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/><path d="M6 15h2"/><path d="M10 15h4"/></svg>
                </div>
                <h3>Loans &amp; FDs</h3>
                <p>Apply for loans with a live EMI calculator, or lock in attractive fixed deposit rates.</p>
            </article>
            <article class="feature-card">
                <div class="feature-icon feature-icon-doc" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8"/><path d="M8 17h5"/></svg>
                </div>
                <h3>PDF Statements</h3>
                <p>Download professional account statements for any date range, ready to share or print.</p>
            </article>
        </div>
    </div>
</section>

<section class="cta-banner">
    <div class="container cta-inner">
        <div class="cta-copy">
            <h2>Ready to get started?</h2>
            <p>Create your free account in minutes and explore modern digital banking today.</p>
        </div>
        <div class="cta-actions">
            <a href="<?= url('auth/register.php') ?>" class="btn btn-light btn-lg">Create Account</a>
            <a href="<?= url('auth/login.php') ?>" class="btn btn-outline-dark btn-lg">Login</a>
        </div>
    </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
