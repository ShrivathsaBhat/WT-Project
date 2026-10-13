<?php
require_once 'includes/functions.php';
require_guest();

$token = $_GET['token'] ?? $_POST['token'] ?? '';
$error = '';
$valid = false;
$reset = null;

if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $stmt = $pdo->prepare('SELECT * FROM password_resets WHERE token_hash = ? AND used = 0 AND expires_at > NOW()');
    $stmt->execute([hash('sha256', $token)]);
    $reset = $stmt->fetch();
    $valid = (bool)$reset;
}

if ($valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $pw  = $_POST['password'] ?? '';
    $cpw = $_POST['confirm_password'] ?? '';

    if (!valid_password($pw))  $error = PASSWORD_RULE_TEXT;
    elseif ($pw !== $cpw)      $error = 'The two passwords do not match.';
    else {
        $pdo->beginTransaction();
        $pdo->prepare('UPDATE users SET password_hash = ?, password_changed_at = ? WHERE user_id = ?')
            ->execute([password_hash($pw, PASSWORD_DEFAULT), now_sql(), $reset['user_id']]);
        $pdo->prepare('UPDATE password_resets SET used = 1 WHERE id = ?')->execute([$reset['id']]);
        $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$reset['user_id']]);
        $pdo->prepare('DELETE FROM user_sessions WHERE user_id = ?')->execute([$reset['user_id']]);
        $pdo->commit();
        flash('success', 'Password updated. Please sign in with your new password.');
        redirect('login.php');
    }
}

$pageTitle = 'Set New Password';
$pageMode  = 'auth';
include 'includes/header.php';
?>
<div class="auth-card">
  <h1>Set a New Password</h1>
  <?php if (!$valid): ?>
    <div class="alert alert-error">This reset link is invalid or has expired.</div>
    <p class="center small"><a href="forgot_password.php">Request a new link</a></p>
  <?php else: ?>
    <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <?= password_field('password', 'New Password', 'password', '', ['strength' => true]) ?>
      <?= password_field('confirm_password', 'Confirm New Password', 'confirm_password', '', ['match' => 'password']) ?>
      <div class="mt"><button type="submit" class="btn btn-primary btn-block">Save New Password</button></div>
    </form>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
