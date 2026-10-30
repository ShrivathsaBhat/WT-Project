<?php
// Food Dash entry point - redirects to Module 3 (Cart & Checkout)
require_once __DIR__ . '/config/cart.php';
header('Location: ' . BASE_URL . '/cart/');
exit;

