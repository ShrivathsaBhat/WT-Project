
<?php
session_start();
require __DIR__ . '/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = db()->prepare(
        'SELECT id, full_name, email, password_hash, role
         FROM users WHERE email = ?'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (
        $user &&
        password_verify($password, $user['password_hash']) &&
        $user['role'] === 'admin'
    ) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['role'] = 'admin';

        header('Location: admin/orders.php');
        exit;
    }

    $error = 'Invalid administrator email or password.';
}

$pageTitle = 'Admin Login';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-card panel">
    <div class="eyebrow">FOOD DASH ADMINISTRATION</div>
    <h1>Admin login</h1>
    <p class="muted">Manage orders, payments and activity.</p>

    <?php if ($error): ?>
        <div class="notice error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <label>
            Admin email
            <input type="email" name="email" required
                   autocomplete="username">
        </label>

        <label>
            Password
            <input type="password" name="password" required
                   autocomplete="current-password">
        </label>

        <button class="button full" type="submit">
            Sign in as admin
        </button>
    </form>

    <p class="muted">
        Don't have an admin account?
        <a href="admin-register.php">Create admin account</a>.
    </p>

    <p class="muted">
        Not an admin?
        <a href="register.php">Customer sign up</a>.
    </p>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
