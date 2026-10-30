<?php

require __DIR__ . '/../includes/auth.php';
require_login();

// Prevent administrators from accessing customer order details
if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: ../admin/orders.php');
    exit;
}

require __DIR__ . '/../config/database.php';

// Get and validate the order ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Fetch the order belonging to the logged-in customer
$stmt = db()->prepare(
    'SELECT *
     FROM orders
     WHERE id = ? AND user_id = ?'
);

$stmt->execute([
    $id ?: 0,
    $_SESSION['user_id']
]);

$o = $stmt->fetch();

// Check whether the order exists
if (!$o) {
    http_response_code(404);
    exit('Order not found.');
}

$pageTitle = 'Order details';

require __DIR__ . '/../includes/header.php';

?>

<div class="page-heading">

    <div>
        <div class="eyebrow">ORDER DETAILS</div>

        <h1>
            <?= h($o['order_code']) ?>
        </h1>
    </div>

    <a href="history.php">
        ← Back to history
    </a>

</div>

<section class="panel">

    <div class="split">

        <h2>Order summary</h2>

        <span class="status <?= strtolower(str_replace(' ', '-', $o['status'])) ?>">
            <?= h($o['status']) ?>
        </span>

    </div>

    <p>
        <b>Order date:</b>
        <?= h(date('d M Y, h:i A', strtotime($o['order_date']))) ?>
    </p>

    <p>
        <b>Address:</b>
        <?= h($o['delivery_address']) ?>
    </p>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Unit price</th>
                    <th>Total</th>
                </tr>
            </thead>

            <tbody>

                <?php

                // Fetch items belonging to this order
                $q = db()->prepare(
                    'SELECT *
                     FROM order_items
                     WHERE order_id = ?'
                );

                $q->execute([$o['id']]);

                foreach ($q as $it):

                ?>

                    <tr>
                        <td>
                            <?= h($it['item_name']) ?>
                        </td>

                        <td>
                            <?= (int) $it['quantity'] ?>
                        </td>

                        <td>
                            ₹<?= number_format((float) $it['unit_price'], 2) ?>
                        </td>

                        <td>
                            ₹<?= number_format(
                                (float) $it['quantity'] * (float) $it['unit_price'],
                                2
                            ) ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </tbody>

        </table>

    </div>

    <h2 class="total-line">
        Total: ₹<?= number_format((float) $o['total_amount'], 2) ?>
    </h2>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>