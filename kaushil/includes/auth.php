<?php
// SHARED (Srujan owns the real one). Keep the function name require_login() or change the calls in api/cart.php and cart/index.php.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/cart.php';
require_once __DIR__ . '/json.php';

function require_login(bool $json = false): int {
    if (empty($_SESSION['user_id']) && DEMO_MODE) $_SESSION['user_id'] = 1;
    if (!empty($_SESSION['user_id'])) return (int)$_SESSION['user_id'];
    if ($json) json_fail('Please log in first', 401);
    header('Location: ' . BASE_URL . '/user/login.php'); exit;
}
