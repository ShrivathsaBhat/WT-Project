<?php
require_once 'includes/functions.php';
require_guest();

$error = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');

    if (!valid_email($email) || !valid_phone($phone)) {
        $error = 'Enter your registered email address and 10-digit phone number.';
    } else {
        // Verification: both email and phone must belong to the same account
        $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ? AND phone = ?');
        $stmt->execute([$email, $phone]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'No account matches that email and phone number.';
        } else {
            $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$user['user_id']]);
            $token = bin2hex(random_bytes(32));
            $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))')
                ->execute([$user['user_id'], hash('sha256', $token)]);
            // The project has no mail server, so the link is shown on the page
            $resetLink = base_url() . '/reset_password.php?token=' . $token;
        }
    }
}

$pageTitle = 'Forgot Password';
$pageMode  = 'auth';
include 'includes/header.php';
?>
<div class="auth-card">
  <h1>Reset Password</h1>
  <p class="sub">Confirm your email and phone number to create a reset link.</p>

  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

  <?php if ($resetLink): ?>
    <div class="alert alert-success">
      Identity verified. This project has no email server, so the link is shown here (valid for 15 minutes):
      <p class="small"><a href="<?= e($resetLink) ?>"><?= e($resetLink) ?></a></p>
    </div>
  <?php else: ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <?= input_field('email', 'Registered Email', '', [], 'email', 'data-validate="email" required') ?>
      <?= input_field('phone', 'Registered Phone Number', '', [], 'tel', 'maxlength="10" inputmode="numeric" data-validate="phone" required') ?>
      <div class="mt"><button type="submit" class="btn btn-primary btn-block">Create Reset Link</button></div>
    </form>
  <?php endif; ?>

  <p class="center small mt"><a href="login.php">Back to sign in</a></p>
</div>
<?php include 'includes/footer.php'; ?>
