
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

    if (
        $name === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8
    ) {
        $error = 'Enter your name, a valid email, and a password with at least 8 characters.';
    } else {
        try {
            $stmt = db()->prepare(
                "INSERT INTO users(full_name, email, password_hash, role)
                 VALUES (?, ?, ?, 'customer')"
            );

            $stmt->execute([
                $name,
                $email,
                password_hash($password, PASSWORD_DEFAULT)
            ]);

            $success = 'Account created. You can now log in.';
        } catch (PDOException $e) {
            $error = $e->getCode() === '23000'
                ? 'An account with this email already exists.'
                : 'Could not create the account. Check the database setup.';
        }
    }
}

$pageTitle = 'Create Customer Account';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-card panel">
    <div class="eyebrow">JOIN FOOD DASH</div>
    <h1>Create customer account</h1>

    <?php if ($error): ?>
        <div class="notice error"><?= h($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="notice success"><?= h($success) ?></div>
        <p>
            <a class="button" href="login.php">
                Go to customer login
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
                Email address
                <input type="email" name="email" maxlength="190" required
                       autocomplete="email">
            </label>

            <label>
                Password (minimum 8 characters)
                <input type="password" name="password" minlength="8"
                       required autocomplete="new-password">
            </label>

            <button class="button full" type="submit">
                Create account
            </button>
        </form>

        <p class="muted">
            Already registered?
            <a href="login.php">Customer login</a>.
        </p>

        <p class="muted">
            Not a customer?
            <a href="admin-login.php">Admin login</a>.
        </p>

    <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
