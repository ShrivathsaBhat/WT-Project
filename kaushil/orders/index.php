<?php
require_once __DIR__ . '/../config/cart.php';
$code = $_GET['code'] ?? '';
header('Location: ' . BASE_URL . '/orders/track.php' . ($code ? '?code=' . urlencode($code) : ''));
exit;

