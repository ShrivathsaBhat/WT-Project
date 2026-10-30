<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$pageTitle = 'Order Tracking';
$extraCss = [BASE_URL . '/cart/cart.css?v=' . filemtime(__DIR__ . '/../cart/cart.css')];
include __DIR__ . '/../includes/header.php';

$code = trim($_GET['code'] ?? '');
?>
<div id="fd-cart">
  <div class="wrap" style="max-width:700px;margin:40px auto;text-align:center">
    <div class="card" style="padding:40px 20px">
      <?php if ($code): ?>
        <h2>Tracking Order #<?= htmlspecialchars($code) ?></h2>
        <p class="muted" style="margin:16px 0">Your order has been placed and is currently being prepared.</p>
        <div style="display:inline-block;padding:8px 16px;background:#e1f5fe;color:#0288d1;border-radius:20px;font-weight:600;margin-bottom:24px">
          Status: Order Placed
        </div>
        <p class="muted" style="font-size:13px">Live tracking integration placeholder (Module 4)</p>
      <?php else: ?>
        <h2>Order Tracking</h2>
        <p class="muted" style="margin:16px 0">No active order code provided.</p>
      <?php endif; ?>
      <div style="margin-top:24px">
        <a href="<?= BASE_URL ?>/cart/index.php" class="btn" style="text-decoration:none;display:inline-block">← Back to Cart &amp; Menu</a>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

