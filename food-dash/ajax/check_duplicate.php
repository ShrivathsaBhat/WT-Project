<?php
// AJAX endpoint: reports whether an email or phone number is already registered.
// Response (JSON): {"valid": true|false, "exists": true|false}
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json');

$field = $_GET['field'] ?? '';
$value = trim($_GET['value'] ?? '');
$self  = is_logged_in() ? (int)$_SESSION['user_id'] : 0;   // ignore the user's own record when editing a profile

if ($field === 'email') {
    $value = strtolower($value);
    $valid = valid_email($value);
} elseif ($field === 'phone') {
    $valid = valid_phone($value);
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid field']);
    exit;
}

$exists = false;
if ($valid) {
    $stmt = $pdo->prepare("SELECT 1 FROM users WHERE $field = ? AND user_id <> ? LIMIT 1");   // $field is whitelisted above
    $stmt->execute([$value, $self]);
    $exists = (bool)$stmt->fetchColumn();
}
echo json_encode(['valid' => $valid, 'exists' => $exists]);
