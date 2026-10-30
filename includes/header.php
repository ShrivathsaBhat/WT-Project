
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'Food Dash';

$accountPages = [
    'account-type.php',
    'admin-login.php',
    'admin-register.php',
    'login.php',
    'register.php'
];

$isAccountPage = in_array(
    basename($_SERVER['PHP_SELF']),
    $accountPages,
    true
);
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?> | Food Dash</title>
  <link rel="stylesheet" href="/food_dash_module4_starter/food_dash_module4/assets/css/style.css?v=3">
</head>
<body class="<?= $isAccountPage ? 'auth-page' : '' ?>">
<header class="topbar">
  <a class="brand" href="/food_dash_module4_starter/food_dash_module4/index.php">
    <span class="brand-mark">FD</span> Food Dash
  </a>

  <?php if (!$isAccountPage): ?>
    <nav>
      <?php if (!empty($_SESSION['user_id'])): ?>

        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
          <a href="/food_dash_module4_starter/food_dash_module4/admin/orders.php">Orders</a>
          <a href="/food_dash_module4_starter/food_dash_module4/admin/payments.php">Payments</a>
          <a href="/food_dash_module4_starter/food_dash_module4/admin/activity-logs.php">Activity log</a>
        <?php else: ?>
          <a href="/food_dash_module4_starter/food_dash_module4/orders/tracking.php">Track order</a>
          <a href="/food_dash_module4_starter/food_dash_module4/orders/history.php">Order history</a>
        <?php endif; ?>

        <a href="/food_dash_module4_starter/food_dash_module4/logout.php"
           onclick="return confirm('Are you sure you want to log out?');">
          Log out
        </a>

      <?php else: ?>
        <a href="/food_dash_module4_starter/food_dash_module4/login.php">Log in</a>
        <a href="/food_dash_module4_starter/food_dash_module4/register.php">Register</a>
      <?php endif; ?>
    </nav>
  <?php endif; ?>
</header>

<main class="container">
