<?php
// Restaurant details. The restaurant is identified by ?id=, so a refresh or a shared link reopens the same page.
require_once 'includes/functions.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$restaurant = false;
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM restaurants WHERE id = ?");
    $stmt->execute([$id]);
    $restaurant = $stmt->fetch();
}

if (!$restaurant) {
    http_response_code(404);
    $pageTitle = 'Restaurant not found';
    $pageMode  = 'plain';
    include 'includes/header.php';
    ?>
    <div class="empty-state">
      <h1>Restaurant not found</h1>
      <p>This restaurant does not exist or the link is out of date.</p>
      <a class="btn btn-primary" href="restaurants.php">Browse restaurants</a>
    </div>
    <?php
    include 'includes/footer.php';
    exit;
}

$menuStmt = $pdo->prepare(
    "SELECT * FROM menu_items WHERE restaurant_id = ?
      ORDER BY FIELD(category,'Starters','Main Course','Desserts','Beverages'), featured DESC, name"
);
$menuStmt->execute([$restaurant['id']]);
$menu = [];
foreach ($menuStmt->fetchAll() as $item) {
    $menu[$item['category']][] = $item;
}

$pageTitle = $restaurant['name'];
$pageMode  = 'plain';
include 'includes/header.php';
?>
<nav class="crumbs" aria-label="Breadcrumb"><a href="index.php">Home</a> &rsaquo; <a href="restaurants.php">Restaurants</a> &rsaquo; <span><?= e($restaurant['name']) ?></span></nav>

<section class="rest-hero">
  <span class="rest-hero-art" aria-hidden="true"><?= e($restaurant['emoji']) ?></span>
  <div>
    <h1><?= e($restaurant['name']) ?></h1>
    <p class="muted"><?= e($restaurant['cuisine']) ?> &middot; <?= e($restaurant['area']) ?></p>
    <div class="rest-facts">
      <span class="rating">&#9733; <?= e($restaurant['rating']) ?></span>
      <span><?= (int)$restaurant['delivery_minutes'] ?> mins delivery</span>
      <span>&#8377;<?= (int)$restaurant['cost_for_two'] ?> for two</span>
    </div>
  </div>
</section>

<div class="details-grid">
  <aside class="info-card">
    <h2>Restaurant info</h2>
    <p><strong>Address</strong><br><?= e($restaurant['address']) ?></p>
    <p><strong>Contact</strong><br><?= e($restaurant['phone']) ?></p>
    <p><strong>Opening hours</strong><br><?= e($restaurant['opening_hours']) ?></p>
    <p class="legend">
      <span class="veg-dot veg"></span> Vegetarian &nbsp;
      <span class="veg-dot nonveg"></span> Non-vegetarian
    </p>
  </aside>

  <div class="menu-col">
    <?php if (!$menu): ?>
      <p class="muted">This restaurant has not added its menu yet.</p>
    <?php endif; ?>
    <?php foreach ($menu as $category => $items): ?>
      <section class="menu-section">
        <h2><?= e($category) ?></h2>
        <?php foreach ($items as $item): ?>
          <article class="menu-item">
            <span class="menu-art" aria-hidden="true"><?= e($item['emoji']) ?></span>
            <div class="menu-body">
              <div class="menu-title">
                <span class="veg-dot <?= $item['veg'] ? 'veg' : 'nonveg' ?>" title="<?= $item['veg'] ? 'Vegetarian' : 'Non-vegetarian' ?>"></span>
                <strong><?= e($item['name']) ?></strong>
                <?php if ($item['featured']): ?><span class="tag tag-special">Chef's special</span><?php endif; ?>
              </div>
              <p class="menu-desc"><?= e($item['description']) ?></p>
              <p class="menu-meta">
                <?php if ($item['spice'] !== 'None'): ?>Spice: <?= e($item['spice']) ?> &middot; <?php endif; ?>
                <?php if ($item['allergens']): ?>Allergens: <?= e($item['allergens']) ?><?php else: ?>No listed allergens<?php endif; ?>
              </p>
            </div>
            <span class="menu-price">&#8377;<?= number_format((float)$item['price'], 0) ?></span>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
