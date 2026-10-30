<?php
// Food Dash – Simulated Payment Verification Endpoint
// Opened when customer scans the UPI QR code on their phone or clicks the simulated payment link.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/cart.php';

$txn = trim($_GET['txn'] ?? ($_GET['t'] ?? ''));
$code = trim($_GET['code'] ?? '');
$ok = false;
$alreadyPaid = false;
$row = null;

if ($txn !== '' || $code !== '') {
    try {
        $pdo = db();
        $s = $pdo->prepare("SELECT p.id AS pid, p.txn_id, p.status AS pay_status, p.amount,
                                   o.id AS oid, o.order_code, o.total, o.payment_status,
                                   r.name AS restaurant
                            FROM payments p
                            JOIN orders o ON o.id = p.order_id
                            JOIN restaurants r ON r.id = o.restaurant_id
                            WHERE p.txn_id = ? OR o.order_code = ?
                            ORDER BY p.id DESC LIMIT 1");
        $s->execute([$txn, $code]);
        $row = $s->fetch();

        if ($row) {
            if ($row['payment_status'] === 'Paid' || $row['pay_status'] === 'SUCCESS') {
                $ok = true;
                $alreadyPaid = true;
            } else {
                $pdo->beginTransaction();
                $pdo->prepare("UPDATE payments SET status='SUCCESS' WHERE id=?")->execute([$row['pid']]);
                $pdo->prepare("UPDATE orders SET payment_status='Paid' WHERE id=?")->execute([$row['oid']]);
                $pdo->prepare("INSERT INTO order_status_log (order_id, status, note) VALUES (?, 'Confirmed', 'Payment received via simulated UPI scan')")->execute([$row['oid']]);
                $pdo->commit();
                $ok = true;
            }
        }
    } catch (Throwable $e) {
        $ok = false;
    }
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Food Dash – Payment Confirmation</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@700;800&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
<style>
  body {
    margin: 0;
    min-height: 100vh;
    display: grid;
    place-items: center;
    background: #17261f;
    font-family: Figtree, system-ui, sans-serif;
    color: #17261f;
    padding: 16px;
    box-sizing: border-box;
  }
  .box {
    background: #fff;
    border-radius: 24px;
    padding: 38px 28px;
    width: min(92vw, 420px);
    text-align: center;
    box-shadow: 0 25px 60px rgba(0,0,0,0.35);
  }
  .tick {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #1e7a4c;
    color: #fff;
    font-size: 42px;
    line-height: 80px;
    margin: 0 auto 20px;
    box-shadow: 0 10px 25px rgba(30,122,76,0.3);
    animation: pop .4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
  }
  .bad .tick {
    background: #d4412f;
    box-shadow: 0 10px 25px rgba(212,65,47,0.3);
  }
  h1 {
    margin: 0 0 8px;
    font-family: 'Bricolage Grotesque', sans-serif;
    font-size: 24px;
  }
  p {
    margin: 6px 0;
    color: #66756d;
    font-size: 15px;
  }
  .amount {
    font-family: 'Bricolage Grotesque', sans-serif;
    font-size: 32px;
    font-weight: 800;
    color: #1e7a4c;
    margin: 12px 0;
  }
  .meta {
    background: #f4f7f5;
    border-radius: 12px;
    padding: 14px;
    margin: 18px 0;
    text-align: left;
    font-size: 14px;
  }
  .meta div {
    display: flex;
    justify-content: space-between;
    padding: 4px 0;
  }
  .meta span { color: #66756d; }
  .meta b { color: #17261f; }
  .btn {
    display: inline-block;
    background: #1e7a4c;
    color: #fff;
    text-decoration: none;
    font-weight: 600;
    padding: 12px 24px;
    border-radius: 10px;
    margin-top: 10px;
  }
  @keyframes pop {
    from { transform: scale(0.4); opacity: 0; }
    to { transform: scale(1); opacity: 1; }
  }
</style>
</head>
<body>
<div class="box <?= $ok ? '' : 'bad' ?>">
<?php if ($ok): ?>
  <div class="tick">✓</div>
  <h1><?= $alreadyPaid ? 'Payment Confirmed' : 'Payment Successful!' ?></h1>
  <p><?= $alreadyPaid ? 'This order has already been paid for.' : 'Simulated UPI payment received in test environment.' ?></p>
  <div class="amount"><?= CURRENCY . number_format($row['total'], 2) ?></div>
  <div class="meta">
    <div><span>Order ID</span><b>#<?= htmlspecialchars($row['order_code']) ?></b></div>
    <div><span>Restaurant</span><b><?= htmlspecialchars($row['restaurant']) ?></b></div>
    <div><span>Payment Method</span><b>UPI / QR Simulation</b></div>
    <div><span>Status</span><b style="color:#1e7a4c">Paid (Success)</b></div>
  </div>
  <p><small>You can close this tab and return to your computer screen.</small></p>
  <a href="<?= BASE_URL ?>/cart/index.php?order=<?= urlencode($row['order_code']) ?>" class="btn">View Order Confirmation</a>
<?php else: ?>
  <div class="tick">!</div>
  <h1>Payment Invalid</h1>
  <p>The payment identifier could not be verified or has expired.</p>
  <p><small>Please return to the checkout page and try again.</small></p>
  <a href="<?= BASE_URL ?>/cart/index.php" class="btn" style="background:#d4412f">Back to Cart</a>
<?php endif; ?>
</div>
</body>
</html>
