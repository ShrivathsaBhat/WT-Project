<?php
require_once 'includes/functions.php';
require_guest();

$error = '';
$identifier = $_COOKIE['saved_identifier'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $identifier = trim($_POST['identifier'] ?? '');
    $password   = $_POST['password'] ?? '';
    $remember   = !empty($_POST['remember']);

    if (($_SESSION['lock_until'] ?? 0) > time()) {
        $wait = ceil(($_SESSION['lock_until'] - time()) / 60);
        $error = "Too many failed attempts. Try again in about {$wait} minute(s).";
    } elseif ($identifier === '' || $password === '') {
        $error = 'Enter your email or phone number and your password.';
    } elseif (!valid_email(strtolower($identifier)) && !valid_phone($identifier)) {
        $error = 'Enter a valid email address or 10-digit phone number.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? OR phone = ?');
        $stmt->execute([strtolower($identifier), $identifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            unset($_SESSION['attempts'], $_SESSION['lock_until']);
            login_user($user);
            if ($remember) {
                set_remember_cookie((int)$user['user_id']);
                setcookie('saved_identifier', $identifier, cookie_options(time() + REMEMBER_DAYS * 86400));
            } else {
                clear_remember_cookie($pdo);
                setcookie('saved_identifier', '', cookie_options(time() - 3600));
            }
            flash('success', 'Welcome back, ' . first_name($user['name']) . '!');
            redirect('dashboard.php');
        }

        $_SESSION['attempts'] = ($_SESSION['attempts'] ?? 0) + 1;
        if ($_SESSION['attempts'] >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION['lock_until'] = time() + LOCK_SECONDS;
            $_SESSION['attempts'] = 0;
            $error = 'Too many failed attempts. Login is locked for 5 minutes.';
        } else {
            $left = MAX_LOGIN_ATTEMPTS - $_SESSION['attempts'];
            $error = "Incorrect email/phone or password. {$left} attempt(s) left.";
        }
    }
}

$pageTitle = 'Sign In';
$pageMode  = 'auth';
include 'includes/header.php';
?>
<div class="auth-card">
  <div class="seg">
    <a href="login.php" class="active">Sign In</a>
    <a href="register.php">Create Account</a>
  </div>
  <h1>Welcome Back</h1>
  <p class="sub">Access your personalized food ordering experience</p>

  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="field">
      <label for="identifier">Email or Phone Number</label>
      <input type="text" id="identifier" name="identifier" value="<?= e($identifier) ?>" autocomplete="username" required>
      <p class="error"></p>
    </div>

    <?= password_field('password', 'Password', 'password', '', [
        'autocomplete' => 'current-password',
        'extra'        => '<a class="small" href="forgot_password.php">Forgot password?</a>',
    ]) ?>

    <label class="check">
      <input type="checkbox" name="remember" <?= isset($_COOKIE['saved_identifier']) ? 'checked' : '' ?>>
      <span>Remember me on this device (30 days cookie)</span>
    </label>

    <div class="mt">
      <button type="submit" class="btn btn-primary btn-block">Sign In</button>
    </div>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
