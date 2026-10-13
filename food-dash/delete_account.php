<?php
require_once 'includes/functions.php';
$user = require_login();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $password = $_POST['password'] ?? '';
    $typed    = trim($_POST['confirm_text'] ?? '');

    if (!password_verify($password, $user['password_hash'])) $errors['password']     = 'Your password is incorrect.';
    if ($typed !== 'DELETE')                                  $errors['confirm_text'] = 'Type DELETE in capital letters to confirm.';

    if (!$errors) {
        // Addresses, reset tokens and sessions are removed by ON DELETE CASCADE
        $pdo->prepare('DELETE FROM users WHERE user_id = ?')->execute([$user['user_id']]);
        setcookie('saved_identifier', '', cookie_options(time() - 3600));
        logout_user();
        session_start();
        flash('success', 'Your account has been deleted.');
        redirect('login.php');
    }
}

$pageTitle = 'Delete Account';
$pageMode  = 'account';
include 'includes/header.php';
?>
<h1>Delete Account</h1>
<section class="card danger-zone mt">
  <p>This permanently removes your profile and saved addresses. You will not be able to recover them.</p>
  <form method="post" novalidate data-confirm="This will permanently delete your account. Continue?">
    <?= csrf_field() ?>
    <?= password_field('password', 'Enter your password', 'password', $errors['password'] ?? '', ['autocomplete' => 'current-password']) ?>
    <?= input_field('confirm_text', 'Type DELETE to confirm', '', $errors, 'text', 'autocomplete="off" required') ?>
    <div class="form-actions">
      <button type="submit" class="btn btn-danger">Delete My Account</button>
      <a class="btn" href="security.php">Keep My Account</a>
    </div>
  </form>
</section>
<?php include 'includes/footer.php'; ?>
