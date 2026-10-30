
<?php
require __DIR__ . '/../includes/auth.php';
require_admin();

require __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

$id = filter_input(INPUT_POST, 'payment_id', FILTER_VALIDATE_INT);
$new = trim($_POST['status'] ?? '');

if (!$id || $new !== 'Successful') {
    http_response_code(400);
    exit('Invalid payment or status.');
}

$pdo = db();
$pdo->beginTransaction();

try {
    $q = $pdo->prepare(
        'SELECT method, status FROM payments WHERE id = ? FOR UPDATE'
    );
    $q->execute([$id]);
    $payment = $q->fetch();

    if (!$payment) {
        $pdo->rollBack();
        http_response_code(404);
        exit('Payment not found.');
    }

    if (strcasecmp($payment['method'], 'Cash on Delivery') !== 0) {
        $pdo->rollBack();
        http_response_code(400);
        exit('Only COD payments can be updated here.');
    }

    if ($payment['status'] === 'Pending') {
        $update = $pdo->prepare(
            'UPDATE payments SET status = ? WHERE id = ?'
        );
        $update->execute(['Successful', $id]);

        $log = $pdo->prepare(
            'INSERT INTO admin_logs(admin_id, action, details)
             VALUES (?, ?, ?)'
        );

        $log->execute([
            $_SESSION['user_id'],
            'PAYMENT_STATUS_UPDATED',
            'COD payment ID ' . $id . ': Pending → Successful'
        ]);
    }

    $pdo->commit();

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    exit('Could not update payment status.');
}

header('Location: ../admin/payments.php');
exit;
