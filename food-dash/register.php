<?php
require_once 'includes/functions.php';
require_guest();

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name  = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = trim($_POST['phone'] ?? '');
    $pw    = $_POST['password'] ?? '';
    $cpw   = $_POST['confirm_password'] ?? '';
    $old   = ['name' => $name, 'email' => $email, 'phone' => $phone];

    if (!valid_name($name))     $errors['name']  = 'Enter your full name using letters only (2-50 characters).';
    if (!valid_email($email))   $errors['email'] = 'Enter a valid email address, like john@example.com.';
    if (!valid_phone($phone))   $errors['phone'] = 'Enter a valid 10-digit mobile number starting with 6-9.';
    if (!valid_password($pw))   $errors['password'] = PASSWORD_RULE_TEXT;
    if ($pw !== $cpw)           $errors['confirm_password'] = 'The two passwords do not match.';
    if (empty($_POST['agree'])) $errors['agree'] = 'Please agree to the Terms of Service and Privacy Policy.';

    if (!isset($errors['email']) && !isset($errors['phone'])) {
        $dups = duplicate_fields($email, $phone);
        if (isset($dups['email'])) $errors['email'] = 'An account with this email already exists.';
        if (isset($dups['phone'])) $errors['phone'] = 'An account with this phone number already exists.';
    }

    if (!$errors) {
        $now = now_sql();
        $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, password_changed_at, created_at) VALUES (?, ?, ?, ?, ?, ?)')
            ->execute([$name, $email, $phone, password_hash($pw, PASSWORD_DEFAULT), $now, $now]);
        flash('success', 'Account created. Please sign in.');
        redirect('login.php');
    }
}

$pageTitle = 'Create Account';
$pageMode  = 'auth';
include 'includes/header.php';
?>
<div class="auth-card">
  <div class="seg">
    <a href="login.php">Sign In</a>
    <a href="register.php" class="active">Create Account</a>
  </div>
  <h1>Create an Account</h1>
  <p class="sub">Join Food Dash today to order from your favourite restaurants.</p>

  <form method="post" novalidate>
    <?= csrf_field() ?>
    <?= input_field('name', 'Full Name *', $old['name'], $errors, 'text', 'placeholder="John Doe" autocomplete="name" data-validate="name" required') ?>
    <div class="form-grid">
      <?= input_field('email', 'Email Address *', $old['email'], $errors, 'email', 'placeholder="john@example.com" autocomplete="email" data-validate="email" data-unique="email" required') ?>
      <?= input_field('phone', 'Phone Number *', $old['phone'], $errors, 'tel', 'placeholder="9876543210" maxlength="10" inputmode="numeric" autocomplete="tel" data-validate="phone" data-unique="phone" required') ?>
    </div>

    <?= password_field('password', 'Password *', 'password', $errors['password'] ?? '', ['strength' => true]) ?>
    <?= password_field('confirm_password', 'Confirm Password *', 'confirm_password', $errors['confirm_password'] ?? '', ['match' => 'password']) ?>

    <label class="check">
      <input type="checkbox" name="agree" value="1">
      <span>I agree to the <a href="#">Terms of Service</a> &amp; <a href="#">Privacy Policy</a>.</span>
    </label>
    <?php if (isset($errors['agree'])): ?><p class="error"><?= e($errors['agree']) ?></p><?php endif; ?>

    <div class="mt">
      <button type="submit" class="btn btn-primary btn-block">Register Account</button>
    </div>
  </form>
</div>
<?php include 'includes/footer.php'; ?>
