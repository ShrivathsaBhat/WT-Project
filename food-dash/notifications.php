<?php
require_once 'includes/functions.php';
$user = require_login();
$uid  = (int)$user['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $pdo->prepare('UPDATE users SET notify_order_email = ?, notify_order_sms = ?, notify_account_email = ?, notify_promo_email = ? WHERE user_id = ?')
        ->execute([
            isset($_POST['order_email'])   ? 1 : 0,
            isset($_POST['order_sms'])     ? 1 : 0,
            isset($_POST['account_email']) ? 1 : 0,
            isset($_POST['promo_email'])   ? 1 : 0,
            $uid,
        ]);
    flash('success', 'Notification preferences saved.');
    redirect('notifications.php');
}

function pref_toggle(string $name, string $label, bool $on): string {
    return '<label class="pref-toggle"><span>' . e($label) . '</span>'
         . '<span class="switch"><input type="checkbox" name="' . e($name) . '" data-autosubmit ' . ($on ? 'checked' : '') . '>'
         . '<span class="track"></span></span></label>';
}

$pageTitle = 'Notifications';
$pageMode  = 'account';
include 'includes/header.php';
?>
<h1>Notification Preferences</h1>
<p class="page-sub">Choose how and when you want to be notified. Changes save automatically.</p>

<form method="post" class="card">
  <?= csrf_field() ?>
  <div class="pref-row">
    <div>
      <strong>Order Updates</strong>
      <p>Real-time status updates for your orders and delivery.</p>
    </div>
    <div class="pref-toggles">
      <?= pref_toggle('order_email', 'Email', (bool)$user['notify_order_email']) ?>
      <?= pref_toggle('order_sms', 'SMS', (bool)$user['notify_order_sms']) ?>
    </div>
  </div>
  <div class="pref-row">
    <div>
      <strong>Security &amp; Account Activity</strong>
      <p>Alerts about logins, password changes and address updates.</p>
    </div>
    <div class="pref-toggles">
      <?= pref_toggle('account_email', 'Email', (bool)$user['notify_account_email']) ?>
    </div>
  </div>
  <div class="pref-row">
    <div>
      <strong>Promotions &amp; Discounts</strong>
      <p>Weekly newsletters, flash sales and offers.</p>
    </div>
    <div class="pref-toggles">
      <?= pref_toggle('promo_email', 'Email', (bool)$user['notify_promo_email']) ?>
    </div>
  </div>
</form>
<?php include 'includes/footer.php'; ?>
