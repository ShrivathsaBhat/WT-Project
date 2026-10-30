<?php require_once __DIR__ . '/../config/cart.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle ?? 'Food Dash') ?> – Food Dash</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;800&family=Figtree:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/common.css?v=<?= filemtime(__DIR__ . '/../assets/css/common.css') ?>">
<?php foreach (($extraCss ?? []) as $c): ?><link rel="stylesheet" href="<?= $c ?>">
<?php endforeach; ?>
</head>
<body>
<header class="fd-top">
  <a class="fd-logo" href="<?= BASE_URL ?>/index.php"><span>FD</span>Food Dash</a>
  <nav>
    <a href="<?= BASE_URL ?>/menu/index.php">Menu</a>
    <a href="<?= BASE_URL ?>/cart/index.php">Cart</a>
    <a href="<?= BASE_URL ?>/orders/index.php">Orders</a>
    <a href="<?= BASE_URL ?>/offers/index.php">Offers</a>
  </nav>
  <span class="fd-pill" id="cartPill"></span>
</header>
