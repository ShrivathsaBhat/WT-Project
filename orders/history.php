<?php

require __DIR__ . '/../includes/auth.php';
require_login();

// Prevent administrators from accessing customer order history
if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: ../admin/orders.php');
    exit;
}

require __DIR__ . '/../config/database.php';

// Fetch all orders belonging to the logged-in customer
$stmt = db()->prepare(
    "SELECT
        o.*,
        p.method AS payment_method,
        p.status AS payment_status
     FROM orders o
     LEFT JOIN payments p ON p.order_id = o.id
     WHERE o.user_id = ?
     ORDER BY o.order_date DESC"
);

$stmt->execute([$_SESSION['user_id']]);

$orders = $stmt->fetchAll();

$pageTitle = 'Order history';

require __DIR__ . '/../includes/header.php';

?>

<div class="page-heading">
    <div>
        <div class="eyebrow">YOUR FOOD JOURNEY</div>

        <h1>Order history</h1>

        <p class="muted">
            Review your past orders and payment statuses.
        </p>
    </div>

    <a class="button secondary" href="tracking.php">
        Track latest order
    </a>
</div>

<section class="panel">

    <?php if (!$orders): ?>

        <div class="empty">
            <h2>No orders found</h2>

            <p>Your order history will appear here.</p>
        </div>

    <?php else: ?>

        <div class="table-wrap">

            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Order status</th>
                        <th></th>
                    </tr>
                </thead>

                <tbody>

                    <?php foreach ($orders as $o): ?>

                        <tr>
                            <td>
                                <b><?= h($o['order_code']) ?></b>
                            </td>

                            <td>
                                <?= h(date('d M Y', strtotime($o['order_date']))) ?>
                            </td>

                            <td>
                                ₹<?= number_format((float) $o['total_amount'], 2) ?>
                            </td>

                            <td>
                                <?= h($o['payment_method'] ?? '—') ?>

                                <div class="muted small">
                                    <?= h($o['payment_status'] ?? '—') ?>
                                </div>
                            </td>

                            <td>
                                <span class="status <?= strtolower(str_replace(' ', '-', $o['status'])) ?>">
                                    <?= h($o['status']) ?>
                                </span>
                            </td>

                            <td>
                                <a href="order-details.php?id=<?= (int) $o['id'] ?>">
                                    Details
                                </a>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                </tbody>
            </table>

        </div>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>