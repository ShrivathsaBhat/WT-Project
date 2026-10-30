
<?php
require __DIR__ . '/../includes/auth.php';
require_admin();

require __DIR__ . '/../config/database.php';

$pdo = db();

$search = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? '');

// Order summary
$summarySql = "SELECT
    COUNT(*) AS total_orders,

    SUM(CASE WHEN status = 'Delivered' THEN 1 ELSE 0 END)
        AS delivered_count,

    SUM(CASE
        WHEN status NOT IN ('Delivered', 'Cancelled') THEN 1
        ELSE 0
    END) AS progress_count

    FROM orders";

$summary = $pdo->query($summarySql)->fetch();

$sql = "SELECT
            o.*,
            u.full_name,
            u.email,
            p.method AS payment_method,
            p.status AS payment_status
        FROM orders o
        JOIN users u ON u.id = o.user_id
        LEFT JOIN payments p ON p.order_id = o.id
        WHERE 1 = 1";

$args = [];

if ($search !== '') {
    $sql .= " AND (
        o.order_code LIKE ?
        OR u.full_name LIKE ?
        OR u.email LIKE ?
    )";

    $args[] = "%$search%";
    $args[] = "%$search%";
    $args[] = "%$search%";
}

$valid = [
    'Placed',
    'Confirmed',
    'Preparing',
    'Out for Delivery',
    'Delivered',
    'Cancelled'
];

if (in_array($status, $valid, true)) {
    $sql .= " AND o.status = ?";
    $args[] = $status;
}

$sql .= " ORDER BY o.order_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$orders = $stmt->fetchAll();

$pageTitle = 'Admin order monitoring';

require __DIR__ . '/../includes/header.php';
?>

<style>
.order-summary {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.order-summary-card {
    background: var(--card, #ffffff);
    border: 1px solid var(--border, #e5e7eb);
    border-radius: 14px;
    padding: 20px;
}

.order-summary-card .summary-label {
    font-size: 13px;
    color: var(--muted, #777777);
    margin-bottom: 10px;
}

.order-summary-card .summary-value {
    font-size: 25px;
    font-weight: 700;
}

@media (max-width: 600px) {
    .order-summary {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="page-heading">
    <div>
        <div class="eyebrow">ADMIN CONSOLE</div>
        <h1>Order monitoring</h1>
        <p class="muted">
            Search an order and update its delivery status!
        </p>
    </div>
</div>

<!-- ORDER SUMMARY -->

<section class="order-summary">

    <div class="order-summary-card">
        <div class="summary-label">Total Orders</div>
        <div class="summary-value">
            <?= (int)$summary['total_orders'] ?>
        </div>
    </div>

    <div class="order-summary-card">
        <div class="summary-label">Delivered</div>
        <div class="summary-value">
            <?= (int)$summary['delivered_count'] ?>
        </div>
    </div>

    <div class="order-summary-card">
        <div class="summary-label">In Progress</div>
        <div class="summary-value">
            <?= (int)$summary['progress_count'] ?>
        </div>
    </div>

</section>

<section class="panel">

    <form class="filters" method="get" id="orderFilters">

        <input
            type="text"
            name="q"
            id="orderSearch"
            value="<?= h($search) ?>"
            placeholder="Search order ID, customer or email"
            autocomplete="off"
        >

        <select name="status" id="orderStatus">
            <option value="">All statuses</option>

            <?php foreach ($valid as $v): ?>
                <option
                    value="<?= h($v) ?>"
                    <?= $status === $v ? 'selected' : '' ?>
                >
                    <?= h($v) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <a href="orders.php" class="button secondary">
            Reset
        </a>

    </form>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Order status</th>
                    <th>Update</th>
                </tr>
            </thead>

            <tbody>

                <?php foreach ($orders as $o): ?>
                    <tr>
                        <td>
                            <b><?= h($o['order_code']) ?></b>
                            <div class="muted small">
                                #<?= (int)$o['id'] ?>
                            </div>
                        </td>

                        <td>
                            <?= h($o['full_name']) ?>
                            <div class="muted small">
                                <?= h($o['email']) ?>
                            </div>
                        </td>

                        <td>
                            <?= h(date(
                                'd M, h:i A',
                                strtotime($o['order_date'])
                            )) ?>
                        </td>

                        <td>
                            ₹<?= number_format(
                                (float)$o['total_amount'],
                                2
                            ) ?>
                        </td>

                        <td>
                            <?= h($o['payment_method'] ?? '—') ?>
                            <div class="muted small">
                                <?= h($o['payment_status'] ?? '—') ?>
                            </div>
                        </td>

                        <td>
                            <span class="status <?= h(
                                strtolower(
                                    str_replace(' ', '-', $o['status'])
                                )
                            ) ?>">
                                <?= h($o['status']) ?>
                            </span>
                        </td>

                        <td>
                            <form
                                class="inline-form"
                                method="post"
                                action="../api/update-order-status.php"
                            >
                                <input
                                    type="hidden"
                                    name="order_id"
                                    value="<?= (int)$o['id'] ?>"
                                >

                                <input
                                    type="hidden"
                                    name="return_to"
                                    value="admin"
                                >

                                <select name="status" aria-label="New status">
                                    <?php foreach ($valid as $v): ?>
                                        <option
                                            value="<?= h($v) ?>"
                                            <?= $o['status'] === $v
                                                ? 'selected'
                                                : '' ?>
                                        >
                                            <?= h($v) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>

                                <button
                                    class="button small-button"
                                    type="submit"
                                >
                                    Save
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (!$orders): ?>
                    <tr>
                        <td colspan="7" class="empty">
                            No matching orders found.
                        </td>
                    </tr>
                <?php endif; ?>

            </tbody>
        </table>
    </div>

</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('orderFilters');
    const search = document.getElementById('orderSearch');
    const status = document.getElementById('orderStatus');

    let timer;

    search.addEventListener('input', function () {
        clearTimeout(timer);

        timer = setTimeout(function () {
            form.requestSubmit();
        }, 400);
    });

    status.addEventListener('change', function () {
        clearTimeout(timer);
        form.requestSubmit();
    });
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
