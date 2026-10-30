
<?php
session_start();
require __DIR__ . '/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Incorrect email or password.';
    } else {
        try {
            $stmt = db()->prepare(
                'SELECT id, full_name, email, password_hash, role
                 FROM users
                 WHERE email = ?'
            );

            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (
                $user &&
                $user['role'] === 'customer' &&
                password_verify($password, $user['password_hash'])
            ) {
                session_regenerate_id(true);

                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['role'] = 'customer';

                header('Location: orders/tracking.php');
                exit;
            }

            $error = 'Incorrect email or password.';

        } catch (Throwable $e) {
            $error = 'Something went wrong. Please try again.';
        }
    }
}

$pageTitle = 'Customer Login';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-card panel">
    <div class="eyebrow">WELCOME TO FOOD DASH</div>
    <h1>Good food, clear updates.</h1>
    <p class="muted">Sign in to track your orders.</p>

    <?php if ($error): ?>
        <div class="notice error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>
            Email address
            <input type="email" name="email" required
                   autocomplete="username">
        </label>

        <label>
            Password
            <input type="password" name="password" required
                   autocomplete="current-password">
        </label>

        <button class="button full" type="submit">
            Log in
        </button>
    </form>

    <p class="muted">
        New customer?
        <a href="register.php">Create an account</a>.
    </p>

    <p class="muted">
        Not a customer?
        <a href="admin-login.php">Admin login</a>.
    </p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
