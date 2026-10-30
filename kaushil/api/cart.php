<?php
// Module 3 Cart & Order Placement API.
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/cart_data.php';
require_once __DIR__ . '/../includes/json.php';

$u = require_login(true);
$pdo = db();
$act = $_GET['action'] ?? '';
$rawInput = file_get_contents('php://input');
$in = json_decode($rawInput, true) ?: [];

function pay_url(string $txnId, string $orderCode): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host . BASE_URL . '/api/pay.php?txn=' . urlencode($txnId) . '&code=' . urlencode($orderCode);
}

try {
    switch ($act) {
    case 'menu':
        json_ok(q_menu_all($pdo));

    case 'cart':
        json_ok(cartData($pdo, $u));

    case 'add':
        $id = (int)($in['item_id'] ?? 0);
        $qty = max(1, min(20, (int)($in['quantity'] ?? 1)));
        $item = q_menu_item($pdo, $id);
        if (!$item) json_fail('Dish is currently unavailable or out of stock', 404);

        $cur = q_cart_rows($pdo, $u);
        if ($cur && (int)$cur[0]['restaurant_id'] !== (int)$item['restaurant_id']) {
            $curRest = q_restaurant($pdo, (int)$cur[0]['restaurant_id']);
            $newRest = q_restaurant($pdo, (int)$item['restaurant_id']);
            $curName = $curRest ? $curRest['name'] : 'another restaurant';
            $newName = $newRest ? $newRest['name'] : 'a new restaurant';

            if (empty($in['force'])) {
                json_ok([
                    'conflict' => true,
                    'message' => "Your cart contains dishes from \"{$curName}\". Replace with items from \"{$newName}\"?"
                ]);
            }
            // Customer explicitly confirmed replacement: clear existing restaurant items
            $pdo->prepare("DELETE FROM cart_items WHERE user_id=?")->execute([$u]);
            unset($_SESSION['coupon']);
        }

        $pdo->prepare("INSERT INTO cart_items (user_id, menu_item_id, qty, instruction) VALUES (?, ?, ?, '')
            ON DUPLICATE KEY UPDATE qty = LEAST(qty + VALUES(qty), 20)")->execute([$u, $id, $qty]);
        json_ok(cartData($pdo, $u));

    case 'update':
        $q = (int)($in['quantity'] ?? 0);
        $id = (int)($in['item_id'] ?? 0);
        if ($q <= 0) {
            $pdo->prepare("DELETE FROM cart_items WHERE user_id=? AND menu_item_id=?")->execute([$u, $id]);
        } else {
            $pdo->prepare("UPDATE cart_items SET qty=? WHERE user_id=? AND menu_item_id=?")->execute([min($q, 20), $u, $id]);
        }
        json_ok(cartData($pdo, $u));

    case 'remove':
        $pdo->prepare("DELETE FROM cart_items WHERE user_id=? AND menu_item_id=?")->execute([$u, (int)($in['item_id'] ?? 0)]);
        json_ok(cartData($pdo, $u));

    case 'note':
        $id = (int)($in['item_id'] ?? 0);
        $text = mb_substr(trim($in['text'] ?? ''), 0, 150);
        $pdo->prepare("UPDATE cart_items SET instruction=? WHERE user_id=? AND menu_item_id=?")->execute([$text, $u, $id]);
        json_ok(['updated' => true]);

    case 'clear':
        $pdo->prepare("DELETE FROM cart_items WHERE user_id=?")->execute([$u]);
        unset($_SESSION['coupon']);
        json_ok(cartData($pdo, $u));

    case 'coupon':
        $code = strtoupper(trim($in['code'] ?? ''));
        if ($code === '') {
            unset($_SESSION['coupon']);
        } else {
            $_SESSION['coupon'] = $code;
        }
        json_ok(cartData($pdo, $u));

    case 'address_add':
        $f = [
            'label' => trim($in['label'] ?? ''),
            'house' => trim($in['line1'] ?? ($in['house'] ?? '')),
            'locality' => trim($in['locality'] ?? ''),
            'city' => trim($in['city'] ?? ''),
            'state' => trim($in['state'] ?? ''),
            'pincode' => trim($in['pincode'] ?? '')
        ];
        if (in_array('', $f, true)) {
            json_fail('Please fill in all delivery address fields');
        }
        if (!preg_match('/^\d{6}$/', $f['pincode'])) {
            json_fail('PIN code must be a valid 6-digit Indian postal code');
        }
        $newId = q_address_insert($pdo, $u, $f);
        $d = cartData($pdo, $u);
        $d['new_address_id'] = $newId;
        json_ok($d);

    case 'place_order':
        $rows = q_cart_rows($pdo, $u);
        if (!$rows) json_fail('Your cart is empty. Please add items to checkout');

        $addrId = (int)($in['address_id'] ?? 0);
        $ad = q_address($pdo, $addrId, $u);
        if (!$ad) json_fail('Please select a valid delivery address');

        $pmInput = $in['payment'] ?? 'UPI';
        $validMethods = ['COD', 'UPI', 'Card', 'NetBanking'];
        // Normalize Card
        $pm = (strtoupper($pmInput) === 'CARD') ? 'Card' : $pmInput;
        if (!in_array($pm, $validMethods, true)) {
            json_fail('Please select a valid payment method');
        }

        $cardOutcome = $in['card_outcome'] ?? 'success'; // for simulated card testing
        if ($pm === 'Card') {
            // Verify format of simulated card fields if provided
            $cNum = preg_replace('/\s+/', '', (string)($in['card_number'] ?? ''));
            $cExp = trim((string)($in['card_expiry'] ?? ''));
            $cCvv = trim((string)($in['card_cvv'] ?? ''));
            if ($cNum !== '' || $cExp !== '' || $cCvv !== '') {
                if (strlen($cNum) < 13 || strlen($cNum) > 19 || !ctype_digit($cNum)) {
                    json_fail('Invalid card number: Please enter a 16-digit test card number');
                }
                if (!preg_match('/^(0[1-9]|1[0-2])\/?(\d{2})$/', $cExp)) {
                    json_fail('Invalid card expiry: format must be MM/YY');
                }
                if (!preg_match('/^\d{3,4}$/', $cCvv)) {
                    json_fail('Invalid CVV: enter 3 or 4 digits');
                }
            }
        }

        $b = bill($pdo, $rows, $_SESSION['coupon'] ?? null);
        $rest = q_restaurant($pdo, (int)$rows[0]['restaurant_id']);
        $mins = (int)($rest['delivery_mins'] ?? 30);

        // Generate unique order code matching format ORD-XXXX-XX
        do {
            $code = 'ORD-' . mt_rand(1000, 9999) . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 2));
            $chk = $pdo->prepare("SELECT id FROM orders WHERE order_code=?");
            $chk->execute([$code]);
        } while ($chk->fetch());

        $deliveryAddress = "{$ad['house']}, {$ad['locality']}, {$ad['city']}, {$ad['state']} - {$ad['pincode']}";
        $instructions = mb_substr(trim($in['instructions'] ?? ''), 0, 200);

        $pdo->beginTransaction();

        $paymentStatus = 'Pending';
        $payRecordStatus = 'PENDING';
        if ($pm === 'Card' && $cardOutcome === 'success') {
            $paymentStatus = 'Paid';
            $payRecordStatus = 'SUCCESS';
        }

        // Insert into orders
        $stmtOrder = $pdo->prepare("INSERT INTO orders (
            order_code, user_id, restaurant_id, delivery_address, delivery_note,
            subtotal, packaging, tax, delivery_fee, discount, coupon_code, total,
            payment_method, payment_status, status, eta_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Placed', DATE_ADD(NOW(), INTERVAL ? MINUTE))");

        $stmtOrder->execute([
            $code,
            $u,
            (int)$rows[0]['restaurant_id'],
            $deliveryAddress,
            $instructions,
            $b['subtotal'],
            $b['packaging'],
            $b['tax'],
            $b['delivery'],
            $b['discount'],
            $b['coupon'],
            $b['total'],
            $pm,
            $paymentStatus,
            $mins
        ]);
        $oid = (int)$pdo->lastInsertId();

        // Snapshot order items with item_name, unit_price, qty, instruction
        $insItem = $pdo->prepare("INSERT INTO order_items (order_id, menu_item_id, item_name, unit_price, qty, instruction) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($rows as $i) {
            $insItem->execute([
                $oid,
                $i['id'],
                $i['name'],
                $i['price'],
                $i['quantity'],
                $i['instructions'] ?? ''
            ]);
        }

        // Module 4 order status log initialization
        $pdo->prepare("INSERT INTO order_status_log (order_id, status, note) VALUES (?, 'Placed', 'Order placed successfully')")->execute([$oid]);

        // Simulated payment record
        $txnId = 'TXN-' . strtoupper(bin2hex(random_bytes(4)));
        if ($pm === 'Card' && $cardOutcome === 'fail') {
            $payRecordStatus = 'FAILED';
        }

        $pdo->prepare("INSERT INTO payments (txn_id, order_id, method, amount, status) VALUES (?, ?, ?, ?, ?)")
            ->execute([$txnId, $oid, $pm, $b['total'], $payRecordStatus]);

        // Cart is cleared only on successful order creation (preserved on payment failure for retry)
        if (!($pm === 'Card' && $cardOutcome === 'fail')) {
            $pdo->prepare("DELETE FROM cart_items WHERE user_id=?")->execute([$u]);
            unset($_SESSION['coupon']);
        }

        $pdo->commit();

        $payUrl = ($pm === 'UPI') ? pay_url($txnId, $code) : null;

        if ($pm === 'Card' && $cardOutcome === 'fail') {
            json_ok([
                'order_code' => $code,
                'total' => $b['total'],
                'payment_method' => $pm,
                'card_failed' => true,
                'message' => 'Simulated card transaction was declined (demo test). You can retry payment.'
            ]);
        }

        json_ok([
            'order_code' => $code,
            'total' => $b['total'],
            'payment_method' => $pm,
            'pay_url' => $payUrl,
            'txn_id' => $txnId
        ]);

    case 'pay_status':
        $code = trim($_GET['code'] ?? '');
        $s = $pdo->prepare("SELECT payment_status, status FROM orders WHERE order_code=? AND user_id=?");
        $s->execute([$code, $u]);
        $ord = $s->fetch();
        if (!$ord) json_fail('Order not found', 404);
        json_ok(['paid' => ($ord['payment_status'] === 'Paid'), 'status' => $ord['status']]);

    case 'pay_simulate':
        $code = trim($in['code'] ?? ($_GET['code'] ?? ''));
        $s = $pdo->prepare("SELECT id, order_code, user_id, payment_status, payment_method FROM orders WHERE order_code=? AND user_id=?");
        $s->execute([$code, $u]);
        $ord = $s->fetch();
        if (!$ord) json_fail('Order not found', 404);

        $pdo->beginTransaction();
        $pdo->prepare("UPDATE payments SET status='SUCCESS' WHERE order_id=?")->execute([$ord['id']]);
        $pdo->prepare("UPDATE orders SET payment_status='Paid' WHERE id=?")->execute([$ord['id']]);
        $pdo->prepare("INSERT INTO order_status_log (order_id, status, note) VALUES (?, 'Confirmed', 'Payment received via simulated UPI')")->execute([$ord['id']]);
        $pdo->commit();
        json_ok(['paid' => true, 'order_code' => $code]);

    case 'pay_card_simulate':
        $code = trim($in['code'] ?? '');
        $outcome = $in['outcome'] ?? 'success';
        $s = $pdo->prepare("SELECT id, order_code, user_id, payment_status FROM orders WHERE order_code=? AND user_id=?");
        $s->execute([$code, $u]);
        $ord = $s->fetch();
        if (!$ord) json_fail('Order not found', 404);

        if ($outcome === 'fail') {
            $pdo->prepare("UPDATE payments SET status='FAILED' WHERE order_id=?")->execute([$ord['id']]);
            json_fail('Simulated card payment failed: Bank declined (Demo test).');
        }

        $pdo->beginTransaction();
        $pdo->prepare("UPDATE payments SET status='SUCCESS' WHERE order_id=?")->execute([$ord['id']]);
        $pdo->prepare("UPDATE orders SET payment_status='Paid' WHERE id=?")->execute([$ord['id']]);
        $pdo->prepare("INSERT INTO order_status_log (order_id, status, note) VALUES (?, 'Confirmed', 'Payment verified via simulated Card')")->execute([$ord['id']]);
        $pdo->commit();
        json_ok(['paid' => true, 'order_code' => $code]);

    case 'advance_status':
        $code = trim($in['code'] ?? ($_GET['code'] ?? ''));
        $s = $pdo->prepare("SELECT id, status, order_code FROM orders WHERE order_code=? AND user_id=?");
        $s->execute([$code, $u]);
        $ord = $s->fetch();
        if (!$ord) json_fail('Order not found', 404);

        $stages = ['Placed', 'Confirmed', 'Preparing', 'Out for Delivery', 'Delivered'];
        $notes = [
            'Confirmed' => 'Restaurant confirmed your order',
            'Preparing' => 'Kitchen accepted order, chef is cooking',
            'Out for Delivery' => 'Rider assigned and on the way',
            'Delivered' => 'Order delivered successfully at your door'
        ];

        $idx = array_search($ord['status'], $stages, true);
        if ($idx !== false && $idx < count($stages) - 1) {
            $nextStatus = $stages[$idx + 1];
            $note = $notes[$nextStatus] ?? "Status changed to {$nextStatus}";
            $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$nextStatus, $ord['id']]);
            $pdo->prepare("INSERT INTO order_status_log (order_id, status, note) VALUES (?, ?, ?)")
                ->execute([$ord['id'], $nextStatus, $note]);
            json_ok(['status' => $nextStatus, 'order_code' => $code]);
        }
        json_ok(['status' => $ord['status'], 'order_code' => $code]);

    case 'order':
        $code = trim($_GET['code'] ?? '');
        $s = $pdo->prepare("SELECT o.*, r.name AS restaurant,
            TIMESTAMPDIFF(SECOND, o.created_at, NOW()) AS elapsed_seconds,
            GREATEST(0, TIMESTAMPDIFF(SECOND, NOW(), o.eta_at)) AS eta_seconds
            FROM orders o
            JOIN restaurants r ON r.id = o.restaurant_id
            WHERE o.order_code=? AND o.user_id=?");
        $s->execute([$code, $u]);
        $o = $s->fetch();
        if (!$o) json_fail('Order not found', 404);

        // Time-based automatic status progression after order placement
        if ($o['status'] !== 'Cancelled') {
            $elapsed = (int)($o['elapsed_seconds'] ?? 0);
            $newStatus = $o['status'];
            $note = '';

            // Realistic timing schedule:
            // >= 60s (1 min): Confirmed (Restaurant accepted)
            // >= 180s (3 mins): Preparing (Chef cooking)
            // >= 600s (10 mins): Out for Delivery (Rider on the way)
            // >= 1200s (20 mins): Delivered
            if ($elapsed >= 1200 && $o['status'] !== 'Delivered') {
                $newStatus = 'Delivered';
                $note = 'Delivered at door';
            } elseif ($elapsed >= 600 && in_array($o['status'], ['Placed', 'Confirmed', 'Preparing'], true)) {
                $newStatus = 'Out for Delivery';
                $note = 'Rider picked up order and is on the way';
            } elseif ($elapsed >= 180 && in_array($o['status'], ['Placed', 'Confirmed'], true)) {
                $newStatus = 'Preparing';
                $note = 'Kitchen accepted order, chef is cooking';
            } elseif ($elapsed >= 60 && $o['status'] === 'Placed') {
                $newStatus = 'Confirmed';
                $note = 'Restaurant confirmed your order';
            }

            if ($newStatus !== $o['status']) {
                $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$newStatus, $o['id']]);
                $pdo->prepare("INSERT INTO order_status_log (order_id, status, note) VALUES (?, ?, ?)")
                    ->execute([$o['id'], $newStatus, $note]);
                $o['status'] = $newStatus;
            }
        }

        $i = $pdo->prepare("SELECT item_name AS name, unit_price AS price, qty AS quantity, instruction AS instructions FROM order_items WHERE order_id=?");
        $i->execute([$o['id']]);
        $o['items'] = $i->fetchAll();

        // Map column names for UI consistency
        $o['address_text'] = $o['delivery_address'];
        $o['delivery_instructions'] = $o['delivery_note'];

        $o['pay_url'] = null;
        if ($o['payment_method'] === 'UPI' && $o['payment_status'] === 'Pending') {
            $p = $pdo->prepare("SELECT txn_id FROM payments WHERE order_id=? AND status='PENDING' ORDER BY id DESC LIMIT 1");
            $p->execute([$o['id']]);
            if ($txn = $p->fetchColumn()) {
                $o['pay_url'] = pay_url($txn, $o['order_code']);
            }
        }
        json_ok($o);

    case 'reset_demo':
        // Helper for standalone demonstration: re-seeds sample items in cart
        $pdo->prepare("DELETE FROM cart_items WHERE user_id=?")->execute([$u]);
        $pdo->prepare("INSERT INTO cart_items (user_id, menu_item_id, qty, instruction) VALUES
            (?, 1, 1, 'Extra crispy base'),
            (?, 3, 2, 'Less sugar')")->execute([$u, $u]);
        unset($_SESSION['coupon']);
        json_ok(cartData($pdo, $u));

    default:
        json_fail('Unknown action: ' . htmlspecialchars($act));
    }
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Food Dash API Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    json_fail('Server error: ' . $e->getMessage(), 500);
}
