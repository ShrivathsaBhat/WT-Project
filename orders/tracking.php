
<?php
require __DIR__ . '/../includes/auth.php';
require_login();

if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: ../admin/orders.php');
    exit;
}

require __DIR__ . '/../config/database.php';

$stmt = db()->prepare(
    "SELECT * FROM orders WHERE user_id=? ORDER BY order_date DESC LIMIT 1"
);
$stmt->execute([$_SESSION['user_id']]);
$order = $stmt->fetch();

$pageTitle = 'Track your order';
require __DIR__ . '/../includes/header.php';

$steps = ['Placed', 'Confirmed', 'Preparing', 'Out for Delivery', 'Delivered'];
?>

<div class="page-heading">
    <div>
        <div class="eyebrow">YOUR FOOD JOURNEY</div>
        <h1>Live order tracking</h1>
        <p class="muted">Follow your order from confirmation to delivery.</p>
    </div>
    <a class="button secondary" href="history.php">Order history →</a>
</div>

<?php if (!$order): ?>

<section class="panel empty">
    <h2>No orders yet</h2>
    <p>Place your first order to see its delivery progress here.</p>
</section>

<?php else: ?>

<section class="panel order-hero">
    <div class="split">
        <div>
            <span class="eyebrow">YOUR ORDER</span>
            <h2><?= h($order['order_code']) ?></h2>
            <p class="muted">
                Placed <?= h(date('d M Y, h:i A', strtotime($order['order_date']))) ?>
            </p>
        </div>
        <span id="status-pill"
              class="status <?= strtolower(str_replace(' ', '-', $order['status'])) ?>">
            <?= h($order['status']) ?>
        </span>
    </div>

    <div class="tracking-banner">
        <div>
            <span class="muted small">ORDER STATUS</span>
            <h2><?= h($order['status']) ?></h2>
            <p class="muted small">Your order progress is shown below.</p>
        </div>
        <div class="tracking-icon">↗</div>
    </div>

    <h3 class="section-label">Order progress</h3>

    <div class="progress" id="progress"
         data-status="<?= h($order['status']) ?>">
        <?php foreach ($steps as $i => $step): ?>
            <div class="progress-step" data-step="<?= h($step) ?>">
                <span class="step-dot"><?= $i + 1 ?></span>
                <span><?= h($step) ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="info-grid">
        <div class="info-tile">
            <span class="muted">ORDER TOTAL</span>
            <strong>₹<?= number_format((float)$order['total_amount'], 2) ?></strong>
        </div>
        <div class="info-tile">
            <span class="muted">ESTIMATED DELIVERY</span>
            <strong>
                <?= !empty($order['estimated_delivery'])
                    ? h(date('h:i A', strtotime($order['estimated_delivery'])))
                    : 'To be updated' ?>
            </strong>
        </div>
        <div class="info-tile">
            <span class="muted">DELIVERY ADDRESS</span>
            <strong><?= h($order['delivery_address']) ?></strong>
        </div>
    </div>

    <p class="muted small" id="last-updated">
        Status refreshes automatically every 15 seconds.
    </p>
</section>

<section class="panel">
    <div class="split">
        <div>
            <div class="eyebrow">ORDER SUMMARY</div>
            <h2>Items in this order</h2>
        </div>
        <a href="history.php">View history →</a>
    </div>

    <?php
    $items = db()->prepare(
        'SELECT item_name, quantity, unit_price FROM order_items WHERE order_id=?'
    );
    $items->execute([$order['id']]);

    foreach ($items as $it):
    ?>
        <div class="item-row">
            <div>
                <b><?= h($it['item_name']) ?></b>
                <div class="muted small">
                    Qty <?= (int)$it['quantity'] ?> ×
                    ₹<?= number_format((float)$it['unit_price'], 2) ?>
                </div>
            </div>
            <b>
                ₹<?= number_format(
                    (float)$it['quantity'] * (float)$it['unit_price'],
                    2
                ) ?>
            </b>
        </div>
    <?php endforeach; ?>
</section>

<script>
window.FD_ORDER_ID = <?= (int)$order['id'] ?>;
</script>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
