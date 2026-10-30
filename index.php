<?php

session_start();

// Check whether the user is logged in
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Redirect based on the user's role
if (($_SESSION['role'] ?? '') === 'admin') {
    header('Location: admin/orders.php');
} else {
    header('Location: orders/tracking.php');
}

exit;