<?php

require __DIR__ . '/../includes/auth.php';
require_login();

require __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

// Allow customers only
if (($_SESSION['role'] ?? '') !== 'customer') {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Forbidden'
    ]);

    exit;
}

// Get and validate order ID
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// Fetch the customer's order
$stmt = db()->prepare(
    'SELECT id, status, estimated_delivery, updated_at
     FROM orders
     WHERE id = ? AND user_id = ?'
);

$stmt->execute([
    $id ?: 0,
    $_SESSION['user_id']
]);

$order = $stmt->fetch();

// Check whether the order exists
if (!$order) {
    http_response_code(404);

    echo json_encode([
        'success' => false,
        'message' => 'Order not found'
    ]);

    exit;
}

// Return order details
echo json_encode([
    'success' => true,
    'data' => $order
]);