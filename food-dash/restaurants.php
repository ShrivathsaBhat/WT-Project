<?php
// Restaurant listing. Search, area and category filters live in the URL (GET), so refreshing
// keeps the same results and Back/Forward move between searches.
require_once 'includes/functions.php';

$categories = ['Starters', 'Main Course', 'Desserts', 'Beverages'];
$q        = trim($_GET['q'] ?? '');
$area     = trim($_GET['area'] ?? '');
$category = in_array($_GET['category'] ?? '', $categories, true) ? $_GET['category'] : '';

$where  = [];
$params = [];
if ($q !== '') {
    // Each placeholder has its own name: native prepared statements do not allow reusing one name.
    $where[] = "(r.name LIKE :q1 OR r.cuisine LIKE :q2 OR EXISTS (
                    SELECT 1 FROM menu_items mq WHERE mq.restaurant_id = r.id AND mq.name LIKE :q3))";
    $like = '%' . $q . '%';
    $params[':q1'] = $like;
    $params[':q2'] = $like;
    $params[':q3'] = $like;
}
if ($area !== '') {
    $where[] = 'r.area LIKE :area';
    $params[':area'] = '%' . $area . '%';
}
if ($category !== '') {
    $where[] = 'EXISTS (SELECT 1 FROM menu_items mc WHERE mc.restaurant_id = r.id AND mc.category = :cat)';
    $params[':cat'] = $category;
}
$sql = "SELECT r.id, r.name, r.cuisine, r.area, r.rating, r.delivery_minutes, r.cost_for_two, r.emoji
          FROM restaurants r"
     . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
     . ' ORDER BY r.rating DESC, r.name';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$restaurants = $stmt->fetchAll();

$areas = $pdo->query("SELECT DISTINCT area FROM restaurants ORDER BY area")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Restaurants';
$pageMode  = 'plain';
include 'includes/header.php';
?>
<div class="page-head">
  <h1>Restaurants<?= $area !== '' ? ' in ' . e($area) : '' ?></h1>
  <p class="muted"><?= count($restaurants) ?> restaurant<?= count($restaurants) === 1 ? '' : 's' ?> found<?= $q !== '' ? ' for &ldquo;' . e($q) . '&rdquo;' : '' ?><?= $category !== '' ? ' serving ' . e($category) : '' ?></p>
</div>

<form class="filter-bar" action="restaurants.php" method="get">
  <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search restaurants or dishes" aria-label="Search">
  <input type="text" name="area" value="<?= e($area) ?>" list="area-list" placeholder="Area" aria-label="Area">
  <datalist id="area-list">
    <?php foreach ($areas as $a): ?><option value="<?= e($a) ?>"><?php endforeach; ?>
  </datalist>
  <select name="category" aria-label="Category">
    <option value="">All categories</option>
    <?php foreach ($categories as $c): ?>
      <option value="<?= e($c) ?>" <?= $c === $category ? 'selected' : '' ?>><?= e($c) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-primary">Apply</button>
  <a class="btn" href="restaurants.php">Clear</a>
</form>

<?php if (!$restaurants): ?>
  <div class="empty-state">
    <p>No restaurants match these filters. Try a different search or <a href="restaurants.php">see all restaurants</a>.</p>
  </div>
<?php else: ?>
  <div class="rest-grid">
    <?php foreach ($restaurants as $r): ?>
      <a class="rest-card" href="restaurant.php?id=<?= (int)$r['id'] ?>">
        <span class="rest-art" aria-hidden="true"><?= e($r['emoji']) ?></span>
        <span class="rest-body">
          <strong class="rest-name"><?= e($r['name']) ?></strong>
          <span class="rest-meta"><?= e($r['cuisine']) ?></span>
          <span class="rest-meta"><?= e($r['area']) ?> &middot; &#8377;<?= (int)$r['cost_for_two'] ?> for two</span>
          <span class="rest-foot">
            <span class="rating">&#9733; <?= e($r['rating']) ?></span>
            <span><?= (int)$r['delivery_minutes'] ?> mins</span>
          </span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
