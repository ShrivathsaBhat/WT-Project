<?php

require __DIR__ . '/../includes/auth.php';
require_login();

// Allow customers only
if (($_SESSION['role'] ?? '') !== 'customer') {
    header('Location: ../admin/orders.php');
    exit;
}

require __DIR__ . '/../config/database.php';

$error = '';
$items = [];

// Fetch available menu items
try {
    $items = db()
        ->query('SELECT id, name, price FROM menu_items ORDER BY name')
        ->fetchAll();

} catch (Throwable $e) {
    $error = 'Menu is unavailable. Run install.php first.';
}

// Process the order form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {

    $address = trim($_POST['delivery_address'] ?? '');
    $method = $_POST['method'] ?? 'Cash on Delivery';

    $allowedMethods = [
        'Cash on Delivery',
        'Card',
        'UPI'
    ];

    $quantities = $_POST['qty'] ?? [];

    // Validate delivery address and payment method
    if (
        $address === '' ||
        strlen($address) > 255 ||
        !in_array($method, $allowedMethods, true)
    ) {
        $error = 'Enter a delivery address and select a valid payment method.';

    } else {

        $pdo = db();
        $selected = [];
        $total = 0;

        $find = $pdo->prepare(
            'SELECT id, name, price
             FROM menu_items
             WHERE id = ?'
        );

        // Fetch selected menu items
        foreach ($quantities as $id => $qty) {

            $qty = filter_var(
                $qty,
                FILTER_VALIDATE_INT,
                [
                    'options' => [
                        'min_range' => 0,
                        'max_range' => 20
                    ]
                ]
            );

            if (!$qty || !ctype_digit((string) $id)) {
                continue;
            }

            $find->execute([(int) $id]);

            $item = $find->fetch();

            if ($item) {
                $item['quantity'] = $qty;

                $selected[] = $item;

                $total += (float) $item['price'] * $qty;
            }
        }

        // Check whether at least one item was selected
        if (!$selected || $total <= 0) {

            $error = 'Choose at least one menu item.';

        } else {

            try {

                $pdo->beginTransaction();

                // Generate a unique order code
                $code = 'FD-' . date('ymd') . '-' .
                    strtoupper(bin2hex(random_bytes(3)));

                // Insert the order
                $stmt = $pdo->prepare(
                    "INSERT INTO orders (
                        order_code,
                        user_id,
                        status,
                        total_amount,
                        delivery_address,
                        estimated_delivery
                    )
                    VALUES (
                        ?,
                        ?,
                        'Placed',
                        ?,
                        ?,
                        DATE_ADD(NOW(), INTERVAL 45 MINUTE)
                    )"
                );

                $stmt->execute([
                    $code,
                    (int) $_SESSION['user_id'],
                    $total,
                    $address
                ]);

                $orderId = (int) $pdo->lastInsertId();

                // Insert the selected order items
                $insertItem = $pdo->prepare(
                    'INSERT INTO order_items (
                        order_id,
                        menu_item_id,
                        item_name,
                        quantity,
                        unit_price
                    )
                    VALUES (?, ?, ?, ?, ?)'
                );

                foreach ($selected as $item) {

                    $insertItem->execute([
                        $orderId,
                        $item['id'],
                        $item['name'],
                        $item['quantity'],
                        $item['price']
                    ]);
                }

                // Set payment status
                $payStatus = $method === 'Cash on Delivery'
                    ? 'Pending'
                    : 'Successful';

                // Generate a simulated transaction reference
                $ref = $method === 'Cash on Delivery'
                    ? null
                    : 'SIM-' . strtoupper(bin2hex(random_bytes(4)));

                // Insert payment details
                $pay = $pdo->prepare(
                    'INSERT INTO payments (
                        order_id,
                        method,
                        amount,
                        status,
                        transaction_ref
                    )
                    VALUES (?, ?, ?, ?, ?)'
                );

                $pay->execute([
                    $orderId,
                    $method,
                    $total,
                    $payStatus,
                    $ref
                ]);

                $pdo->commit();

                // Redirect to order details
                header('Location: order-details.php?id=' . $orderId);
                exit;

            } catch (Throwable $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $error = 'Could not save your order. Please try again.';
            }
        }
    }
}

$pageTitle = 'Place an order';

require __DIR__ . '/../includes/header.php';

?>

<section class="panel">

    <div class="eyebrow">ORDER FOOD</div>

    <h1>Place a new order</h1>

    <p class="muted">
        Orders are saved to your Food Dash database.
        Card and UPI payments are simulations for this college project.
    </p>

    <?php if ($error): ?>

        <div class="notice error">
            <?= h($error) ?>
        </div>

    <?php endif; ?>

    <?php if (!$items): ?>

        <p>
            No menu items are available yet.
            Ask your team to add menu items in the shared menu module.
        </p>

    <?php else: ?>

        <form method="post">

            <div class="table-wrap">

                <table>

                    <thead>
                        <tr>
                            <th>Menu item</th>
                            <th>Price</th>
                            <th>Quantity</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php foreach ($items as $item): ?>

                            <tr>
                                <td>
                                    <?= h($item['name']) ?>
                                </td>

                                <td>
                                    ₹<?= number_format((float) $item['price'], 2) ?>
                                </td>

                                <td>
                                    <input
                                        type="number"
                                        name="qty[<?= (int) $item['id'] ?>]"
                                        min="0"
                                        max="20"
                                        value="0"
                                        aria-label="Quantity for <?= h($item['name']) ?>"
                                    >
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

            <label>
                Delivery address

                <textarea
                    name="delivery_address"
                    maxlength="255"
                    required
                    rows="3"
                ></textarea>
            </label>

            <label>
                Payment method

                <select name="method">
                    <option>Cash on Delivery</option>
                    <option>UPI</option>
                    <option>Card</option>
                </select>
            </label>

            <button class="button" type="submit">
                Place order
            </button>

        </form>

    <?php endif; ?>

</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>