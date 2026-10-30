
<?php
session_start();
$pageTitle = 'Choose Account Type';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-card panel account-choice">
    <div class="eyebrow">WELCOME TO FOOD DASH</div>
    <h1>How would you like to continue?</h1>
    <p class="muted">
        Choose your account type to get started.
    </p>

    <div class="account-choice-grid">

        <article class="account-choice-card">
            <div class="account-choice-icon" aria-hidden="true">
                <span>👤</span>
            </div>

            <h2>Customer</h2>
            <p class="muted">
                Discover food, place orders and track deliveries.
            </p>

            <a class="button full"
               href="register.php">
                Create account
            </a>

            <p class="account-choice-login">
                Already registered?
                <a href="login.php">Log in</a>
            </p>
        </article>

        <article class="account-choice-card">
            <div class="account-choice-icon" aria-hidden="true">
                <span>🛡️</span>
            </div>

            <h2>Admin</h2>
            <p class="muted">
                Manage orders, payments and activity.
            </p>

            <a class="button full"
               href="admin-register.php">
                Create admin account
            </a>

            <p class="account-choice-login">
                Already an admin?
                <a href="admin-login.php">Log in</a>
            </p>
        </article>

    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
