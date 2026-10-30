
<?php
session_start();
require __DIR__ . '/config/database.php';

if (!function_exists('h')) {
    function h($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $adminCode = $_POST['admin_code'] ?? '';

    // Replace this with your private administrator code.
    $correctAdminCode = 'THISISADMIN';

    if ($adminCode !== $correctAdminCode) {
        $error = 'Invalid administrator authorization code.';
    } elseif (
        $name === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8
    ) {
        $error = 'Enter your name, a valid email, and a password with at least 8 characters.';
    } else {
        try {
            $stmt = db()->prepare(
                "INSERT INTO users (full_name, email, password_hash, role)
                 VALUES (?, ?, ?, 'admin')"
            );

            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $success = 'Admin account created. You can now log in.';
        } catch (PDOException $e) {
            $error = $e->getCode() === '23000'
                ? 'An account with this email already exists.'
                : 'Could not create the admin account. Check the database setup.';
        }
    }
}

$pageTitle = 'Create Admin Account';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-card panel">
    <div class="eyebrow">FOOD DASH ADMINISTRATION</div>
    <h1>Create admin account</h1>
    <p class="muted">Register an authorized administrator.</p>

    <?php if ($error): ?>
        <div class="notice error"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="notice success"><?= h($success) ?></div>
        <p>
            <a class="button" href="admin-login.php">
                Go to admin login
            </a>
        </p>
    <?php else: ?>

        <form method="post">
            <label>
                Full name
                <input name="full_name" maxlength="100" required
                       autocomplete="name">
            </label>

            <label>
                Admin email
                <input type="email" name="email" maxlength="190" required
                       autocomplete="email">
            </label>

            <label>
                Password (minimum 8 characters)
                <input type="password" name="password" minlength="8"
                       required autocomplete="new-password">
            </label>

            <label>
                Admin authorization code
                <input type="password" name="admin_code" required
                       autocomplete="off">
            </label>

            <button class="button full" type="submit">
                Create admin account
            </button>
        </form>

        <p class="muted">
            Already have an admin account?
            <a href="admin-login.php">Sign in</a>.
        </p>

        <p class="muted">
            Not an admin?
            <a href="register.php">Customer sign up</a>.
        </p>

    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
