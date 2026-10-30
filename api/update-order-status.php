<?php

require __DIR__ . '/../includes/auth.php';
require_admin();

require __DIR__ . '/../config/database.php';

// Allow POST requests only
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Get and validate order ID
$id = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);

$newStatus = trim($_POST['status'] ?? '');

$validStatuses = [
    'Placed',
    'Confirmed',
    'Preparing',
    'Out for Delivery',
    'Delivered',
    'Cancelled'
];

if (!$id || !in_array($newStatus, $validStatuses, true)) {
    http_response_code(400);
    exit('Invalid order or status.');
}

$pdo = db();

$pdo->beginTransaction();

try {

    // Fetch the current order status
    $stmt = $pdo->prepare(
        'SELECT order_code, status
         FROM orders
         WHERE id = ?
         FOR UPDATE'
    );

    $stmt->execute([$id]);

    $oldOrder = $stmt->fetch();

    if (!$oldOrder) {
        $pdo->rollBack();

        http_response_code(404);
        exit('Order not found.');
    }

    // Update only when the status has changed
    if ($oldOrder['status'] !== $newStatus) {

        $updateStmt = $pdo->prepare(
            'UPDATE orders
             SET status = ?
             WHERE id = ?'
        );

        $updateStmt->execute([
            $newStatus,
            $id
        ]);

        // Record the change in admin activity logs
        $logStmt = $pdo->prepare(
            'INSERT INTO admin_logs (admin_id, action, details)
             VALUES (?, ?, ?)'
        );

        $logStmt->execute([
            $_SESSION['user_id'],
            'ORDER_STATUS_UPDATED',
            $oldOrder['order_code'] . ': ' .
            $oldOrder['status'] . ' → ' . $newStatus
        ]);
    }

    $pdo->commit();

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    exit('Could not update order status.');
}

// Redirect back to admin orders
header('Location: ../admin/orders.php');
exit;