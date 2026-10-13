<?php
require_once 'includes/functions.php';
$user = require_login();
$uid  = (int)$user['user_id'];

$errors = [];
$old = ['name' => $user['name'], 'email' => $user['email'], 'phone' => $user['phone']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name  = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $old   = ['name' => $name, 'email' => $email, 'phone' => $phone];

    if (!valid_name($name))   $errors['name']  = 'Enter your full name using letters only (2-50 characters).';
    if (!valid_email($email)) $errors['email'] = 'Enter a valid email address, like john@example.com.';
    if (!valid_phone($phone)) $errors['phone'] = 'Enter a valid 10-digit mobile number starting with 6-9.';

    if (!isset($errors['email']) && !isset($errors['phone'])) {
        $dups = duplicate_fields($email, $phone, $uid);
        if (isset($dups['email'])) $errors['email'] = 'Another account already uses this email.';
        if (isset($dups['phone'])) $errors['phone'] = 'Another account already uses this phone number.';
    }

    if (!$errors) {
        $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE user_id = ?')
            ->execute([$name, $email, $phone, $uid]);
        $_SESSION['user_name'] = $name;
        flash('success', 'Profile updated.');
        redirect('profile.php');
    }
}

$pageTitle = 'Personal Profile';
$pageMode  = 'account';
include 'includes/header.php';
?>
<div class="page-head">
  <div>
    <h1>User Profile Management</h1>
    <p class="page-sub">Maintain your account information and contact details.</p>
  </div>
</div>

<section class="card">
  <div class="profile-head">
    <span class="avatar xl"><?= e(initials($old['name'])) ?></span>
    <div>
      <strong>Profile picture</strong>
      <p class="muted small">Your initials are used as your profile picture.</p>
    </div>
  </div>

  <form method="post" novalidate data-confirm="Save these changes to your account?">
    <?= csrf_field() ?>
    <div class="form-grid">
      <?= input_field('name', 'Full Name', $old['name'], $errors, 'text', 'autocomplete="name" data-validate="name" required') ?>
      <?= input_field('email', 'Email Address', $old['email'], $errors, 'email', 'autocomplete="email" data-validate="email" data-unique="email" required') ?>
      <?= input_field('phone', 'Phone Number', $old['phone'], $errors, 'tel', 'maxlength="10" inputmode="numeric" data-validate="phone" data-unique="phone" required') ?>
      <div class="field">
        <label for="role">Account Role / Status</label>
        <input type="text" id="role" value="Customer (Active)" readonly>
      </div>
    </div>
    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Save Changes</button>
    </div>
  </form>
</section>
<?php include 'includes/footer.php'; ?>
