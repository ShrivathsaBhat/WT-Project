<?php
require_once 'includes/functions.php';
$user = require_login();
$uid  = (int)$user['user_id'];

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $current = $_POST['current_password'] ?? '';
    $new     = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (!password_verify($current, $user['password_hash'])) $errors['current_password'] = 'Your current password is incorrect.';
    if (!valid_password($new)) $errors['new_password'] = PASSWORD_RULE_TEXT;
    elseif ($new === $current) $errors['new_password'] = 'Choose a password different from your current one.';
    if ($new !== $confirm)     $errors['confirm_password'] = 'The two passwords do not match.';

    if (!$errors) {
        $pdo->prepare('UPDATE users SET password_hash = ?, password_changed_at = ? WHERE user_id = ?')
            ->execute([password_hash($new, PASSWORD_DEFAULT), now_sql(), $uid]);
        revoke_remember_tokens($pdo, $uid);
        $pdo->prepare('DELETE FROM user_sessions WHERE user_id = ? AND session_id <> ?')->execute([$uid, session_id()]);
        flash('success', 'Password changed. Other devices have been signed out.');
        redirect('security.php');
    }
}

$pageTitle = 'Security & Password';
$pageMode  = 'account';
include 'includes/header.php';
?>
<h1>Security &amp; Password</h1>
<p class="page-sub">Keep your account safe with a strong password.</p>

<section class="card">
  <h2>Change Password</h2>
  <p class="muted small">Ensure your account remains safe with a strong password.</p>
  <form method="post" novalidate data-confirm="Change your account password?">
    <?= csrf_field() ?>
    <?= password_field('current_password', 'Current Password', 'current_password', $errors['current_password'] ?? '', ['autocomplete' => 'current-password']) ?>
    <?= password_field('new_password', 'New Password', 'new_password', $errors['new_password'] ?? '', ['strength' => true]) ?>
    <?= password_field('confirm_password', 'Confirm New Password', 'confirm_password', $errors['confirm_password'] ?? '', ['match' => 'new_password']) ?>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Update Password</button>
    </div>
  </form>
</section>

<section class="card danger-zone">
  <h2>Danger Zone</h2>
  <p class="small">Permanently delete your account and remove all personal details.</p>
  <a class="btn btn-danger" href="delete_account.php">Delete Account</a>
</section>
<?php include 'includes/footer.php'; ?>
