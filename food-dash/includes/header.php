<?php
// Expects $pageTitle. Optional $pageMode: 'auth' (centred card), 'account' (sidebar), 'plain' (default).
$pageTitle = $pageTitle ?? 'Food Dash';
$pageMode  = $pageMode ?? 'plain';
$navFile   = basename($_SERVER['SCRIPT_NAME']);
$loggedIn  = is_logged_in();

$moduleTabs = [
    ['Home',                'index.php',       ['index.php']],
    ['Account',             'dashboard.php',   ['dashboard.php', 'profile.php', 'addresses.php', 'security.php', 'delete_account.php', 'notifications.php', 'sessions.php']],
    ['Restaurants',         'restaurants.php', ['restaurants.php', 'restaurant.php']],
    ['Menu',                'menu.php',        ['menu.php']],
    ['Cart',                'cart.php',        ['cart.php']],
    ['Orders',              'orders.php',      ['orders.php']],
    ['Offers & Favourites', 'offers.php',      ['offers.php']],
];
$sidebarLinks = [
    ['Account Overview',    'dashboard.php',     ['dashboard.php']],
    ['Personal Profile',    'profile.php',       ['profile.php']],
    ['Delivery Addresses',  'addresses.php',     ['addresses.php']],
    ['Security & Password', 'security.php',      ['security.php', 'delete_account.php']],
    ['Notifications',       'notifications.php', ['notifications.php']],
    ['Sessions & Cookies',  'sessions.php',      ['sessions.php']],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | Food Dash</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php"><span class="logo">F</span>Food Dash</a>
  <?php if ($loggedIn): ?>
    <nav class="modtabs" aria-label="Modules">
      <?php foreach ($moduleTabs as [$label, $href, $pages]): ?>
        <a href="<?= e($href) ?>" class="<?= in_array($navFile, $pages, true) ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="top-user">
      <span class="avatar sm"><?= e(initials($_SESSION['user_name'])) ?></span>
      <span class="top-name"><?= e($_SESSION['user_name']) ?></span>
      <a href="logout.php" class="link-out">Log out</a>
    </div>
  <?php else: ?>
    <nav class="modtabs" aria-label="Modules">
      <a href="index.php" class="<?= $navFile === 'index.php' ? 'active' : '' ?>">Home</a>
      <a href="restaurants.php" class="<?= in_array($navFile, ['restaurants.php', 'restaurant.php'], true) ? 'active' : '' ?>">Restaurants</a>
      <a href="index.php#offers">Offers</a>
    </nav>
    <div class="top-user">
      <a href="login.php" class="link-out">Log in</a>
      <a href="register.php" class="btn btn-primary btn-sm">Create account</a>
    </div>
  <?php endif; ?>
</header>

<div class="layout">
  <?php if ($pageMode === 'account'): ?>
    <aside class="sidebar">
      <div class="side-user">
        <span class="avatar"><?= e(initials($_SESSION['user_name'])) ?></span>
        <div><strong><?= e($_SESSION['user_name']) ?></strong><small class="ok">&#9679; Customer account</small></div>
      </div>
      <nav>
        <?php foreach ($sidebarLinks as [$label, $href, $pages]): ?>
          <a href="<?= e($href) ?>" class="<?= in_array($navFile, $pages, true) ? 'active' : '' ?>"><?= e($label) ?></a>
        <?php endforeach; ?>
      </nav>
      <a href="logout.php" class="side-out">Sign Out</a>
    </aside>
  <?php endif; ?>

  <main class="content mode-<?= e($pageMode) ?>">
    <?php foreach (get_flash() as $flashMsg): ?>
      <div class="alert alert-<?= e($flashMsg['type']) ?>" role="status"><?= e($flashMsg['msg']) ?></div>
    <?php endforeach; ?>
