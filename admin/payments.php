
<?php
require __DIR__ . '/../includes/auth.php';
require_admin();

require __DIR__ . '/../config/database.php';

$pdo = db();

// Payment summary
$summarySql = "SELECT
    COALESCE(SUM(
        CASE WHEN status = 'Successful' THEN amount ELSE 0 END
    ), 0) AS total_revenue,

    SUM(CASE WHEN status = 'Successful' THEN 1 ELSE 0 END)
        AS successful_count,

    SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END)
        AS pending_count,

    COALESCE(SUM(
        CASE WHEN status = 'Refunded' THEN amount ELSE 0 END
    ), 0) AS total_refunds

    FROM payments";

$summary = $pdo->query($summarySql)->fetch();

$sql = "SELECT
            p.*,
            o.order_code,
            u.full_name
        FROM payments p
        JOIN orders o ON o.id = p.order_id
        JOIN users u ON u.id = o.user_id
        ORDER BY p.created_at DESC";

$rows = $pdo->query($sql)->fetchAll();

$pageTitle = 'Payment log';

require __DIR__ . '/../includes/header.php';
?>

<style>
.payment-summary {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.payment-summary-card {
    background: var(--card, #ffffff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 14px;
    padding: 20px;
}

.payment-summary-card .summary-label {
    font-size: 13px;
    color: var(--muted, #777777);
    margin-bottom: 10px;
}

.payment-summary-card .summary-value {
    font-size: 25px;
    font-weight: 700;
}

@media (max-width: 750px) {
    .payment-summary {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 420px) {
    .payment-summary {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-heading">
    <div>
        <div class="eyebrow">ADMIN CONSOLE</div>
        <h1>Payment log</h1>
        <p class="muted">
            Payment records and their current status.
        </p>
    </div>

    <a class="button secondary" href="orders.php">
        All orders
    </a>
</div>

<!-- PAYMENT SUMMARY -->

<section class="payment-summary">

    <div class="payment-summary-card">
        <div class="summary-label">Total Revenue</div>
        <div class="summary-value">
            ₹<?= number_format((float)$summary['total_revenue'], 2) ?>
        </div>
    </div>

    <div class="payment-summary-card">
        <div class="summary-label">Successful Payments</div>
        <div class="summary-value">
            <?= (int)$summary['successful_count'] ?>
        </div>
    </div>

    <div class="payment-summary-card">
        <div class="summary-label">Pending Payments</div>
        <div class="summary-value">
            <?= (int)$summary['pending_count'] ?>
        </div>
    </div>

    <div class="payment-summary-card">
        <div class="summary-label">Total Refunds</div>
        <div class="summary-value">
            ₹<?= number_format((float)$summary['total_refunds'], 2) ?>
        </div>
    </div>

</section>

<section class="panel">

    <div class="notice">
        Payments are simulated for this academic project.
        No real money is charged.
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Method</th>
                    <th>Amount</th>
                    <th>Status</th>
                    <th>Recorded</th>
                    <th>Update</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($rows as $p): ?>
                    <tr>
                        <td>
                            <?= h($p['transaction_ref'] ?: '—') ?>
                        </td>

                        <td>
                            <?= h($p['order_code']) ?>
                        </td>

                        <td>
                            <?= h($p['full_name']) ?>
                        </td>

                        <td>
                            <?= h($p['method']) ?>
                        </td>

                        <td>
                            ₹<?= number_format((float)$p['amount'], 2) ?>
                        </td>

                        <td>
                            <span class="status <?= h(
                                strtolower($p['status'])
                            ) ?>">
                                <?= h($p['status']) ?>
                            </span>
                        </td>

                        <td>
                            <?= h(date(
                                'd M Y, h:i A',
                                strtotime($p['created_at'])
                            )) ?>
                        </td>

                        <td>
                            <?php if (
                                strcasecmp($p['method'], 'Cash on Delivery') === 0
                                && $p['status'] === 'Pending'
                            ): ?>

                                <form
                                    method="POST"
                                    action="../api/update-payment-status.php"
                                >
                                    <input
                                        type="hidden"
                                        name="payment_id"
                                        value="<?= (int)$p['id'] ?>"
                                    >

                                    <input
                                        type="hidden"
                                        name="status"
                                        value="Successful"
                                    >

                                    <button type="submit" class="button">
                                        Mark Paid
                                    </button>
                                </form>

                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$rows): ?>
                    <tr>
                        <td colspan="8" class="empty">
                            No payment records yet.
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>
    </div>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
