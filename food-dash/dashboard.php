<?php
require_once 'includes/functions.php';
$user = require_login();
$uid  = (int)$user['user_id'];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM addresses WHERE user_id = ?');
$stmt->execute([$uid]);
$addressCount = (int)$stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT * FROM addresses WHERE user_id = ? AND is_default = 1 LIMIT 1');
$stmt->execute([$uid]);
$default = $stmt->fetch();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM user_sessions WHERE user_id = ?');
$stmt->execute([$uid]);
$sessionCount = (int)$stmt->fetchColumn();

$pageTitle = 'Account Overview';
$pageMode  = 'account';
include 'includes/header.php';
?>
<section class="hero">
  <h1>Hello, <?= e(first_name($user['name'])) ?> &#128075;</h1>
  <p>Welcome to your account dashboard.</p>
  <div class="hero-meta">Member since <?= e(date('M Y', strtotime($user['created_at']))) ?> &middot; Status <span class="pill green">Active</span></div>
</section>

<div class="stats">
  <div class="card stat"><span class="muted">Saved Addresses</span><strong><?= $addressCount ?></strong></div>
  <div class="card stat"><span class="muted">Active Sessions</span><strong class="indigo"><?= $sessionCount ?> Device<?= $sessionCount === 1 ? '' : 's' ?></strong></div>
  <div class="card stat"><span class="muted">Password Changed</span><strong class="green"><?= e(date('d M Y', strtotime($user['password_changed_at']))) ?></strong></div>
</div>

<section class="card mt">
  <div class="section-head">
    <h2>Personal Information</h2>
    <a href="profile.php">Edit Details</a>
  </div>
  <div class="tiles">
    <div class="tile"><span>Full Name</span><strong><?= e($user['name']) ?></strong></div>
    <div class="tile"><span>Email Address</span><strong><?= e($user['email']) ?></strong></div>
    <div class="tile"><span>Phone Number</span><strong><?= e($user['phone']) ?></strong></div>
    <div class="tile"><span>Primary Address</span>
      <strong><?= $default ? e($default['house'] . ', ' . $default['locality'] . ', ' . $default['city']) : 'Not added yet' ?></strong>
    </div>
  </div>
</section>
<?php include 'includes/footer.php'; ?>
