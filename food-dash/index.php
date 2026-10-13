<?php
// Public homepage. Open to everyone, signed in or not. It never redirects, so Home always lands here
// and a refresh on this page keeps the visitor here.
require_once 'includes/functions.php';

$featuredRestaurants = $pdo->query(
    "SELECT id, name, cuisine, area, rating, delivery_minutes, cost_for_two, emoji
       FROM restaurants WHERE featured = 1 ORDER BY rating DESC, name"
)->fetchAll();

$chefSpecials = $pdo->query(
    "SELECT m.id, m.name, m.description, m.price, m.veg, m.emoji, r.id AS restaurant_id, r.name AS restaurant_name
       FROM menu_items m JOIN restaurants r ON r.id = m.restaurant_id
      WHERE m.featured = 1 ORDER BY m.id LIMIT 4"
)->fetchAll();

$areas = $pdo->query("SELECT DISTINCT area FROM restaurants ORDER BY area")->fetchAll(PDO::FETCH_COLUMN);

$categories = [
    ['Starters',    '🥟', 'Starters'],
    ['Main Course', '🍛', 'Main Course'],
    ['Desserts',    '🍰', 'Desserts'],
    ['Beverages',   '🥤', 'Beverages'],
];

$offers = [
    ['40% OFF', 'Your first order',         'Up to ₹80 off on orders above ₹300.', 'WELCOME40'],
    ['FREE DELIVERY', 'Weekend lunch',         'No delivery fee on Saturdays and Sundays.', 'WEEKEND0'],
    ['BUY 1 GET 1', 'Desserts',               'Pick any dessert from Sweet Crumb Bakery.', 'SWEETDUO'],
];

$pageTitle = 'Food Dash - Order Food Online';
$pageMode  = 'landing';
$hideSiteFooter = true;   // the homepage has its own full footer
include 'includes/header.php';
?>
<section class="hero-land">
  <div class="hero-inner hero-grid">
    <div class="hero-copy">
      <span class="eyebrow">Food delivery in Udupi, Manipal &amp; Mangaluru</span>
      <h1>Hungry? Your favourite food, delivered.</h1>
      <p class="lead">Browse restaurants near you, search for a dish, and see every menu with prices, spice levels and allergen details.</p>

      <form class="hero-search" action="restaurants.php" method="get" role="search">
        <label class="search-field">
          <span class="search-label">Deliver to</span>
          <input type="text" name="area" list="area-list" placeholder="Choose your area" autocomplete="off">
          <datalist id="area-list">
            <?php foreach ($areas as $area): ?><option value="<?= e($area) ?>"><?php endforeach; ?>
          </datalist>
        </label>
        <label class="search-field search-field-wide">
          <span class="search-label">Search</span>
          <input type="search" name="q" placeholder="Search for restaurants or dishes" autocomplete="off">
        </label>
        <button type="submit" class="btn btn-primary btn-lg">Find food</button>
      </form>

      <div class="cta-row cta-left">
        <?php if (!is_logged_in()): ?>
          <a class="btn btn-primary" href="register.php">Create Account</a>
          <a class="btn" href="login.php">Log In</a>
        <?php else: ?>
          <a class="btn btn-primary" href="dashboard.php">My account</a>
        <?php endif; ?>
      </div>
    </div>
    <div class="hero-art" aria-hidden="true">
      <span class="art-plate">🍛</span>
      <span class="art-float f1">🥞</span>
      <span class="art-float f2">🍰</span>
      <span class="art-float f3">🦐</span>
      <span class="art-float f4">🥤</span>
    </div>
  </div>
</section>

<section class="land-section">
  <h2 class="section-title">What are you craving?</h2>
  <div class="cat-row">
    <?php foreach ($categories as [$label, $icon, $cat]): ?>
      <a class="cat-tile" href="restaurants.php?category=<?= urlencode($cat) ?>">
        <span class="cat-icon" aria-hidden="true"><?= $icon ?></span>
        <span><?= e($label) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="land-section land-alt">
  <div class="section-head">
    <h2 class="section-title">Top restaurants</h2>
    <a class="section-link" href="restaurants.php">See all restaurants &rarr;</a>
  </div>
  <div class="rest-grid">
    <?php foreach ($featuredRestaurants as $r): ?>
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
</section>

<section class="land-section">
  <div class="section-head">
    <h2 class="section-title">Chef's specials</h2>
    <a class="section-link" href="restaurants.php?category=Starters">Explore menus &rarr;</a>
  </div>
  <div class="special-grid">
    <?php foreach ($chefSpecials as $dish): ?>
      <a class="special-card" href="restaurant.php?id=<?= (int)$dish['restaurant_id'] ?>">
        <span class="special-art" aria-hidden="true"><?= e($dish['emoji']) ?></span>
        <span class="special-body">
          <span class="veg-dot <?= $dish['veg'] ? 'veg' : 'nonveg' ?>" title="<?= $dish['veg'] ? 'Vegetarian' : 'Non-vegetarian' ?>"></span>
          <strong><?= e($dish['name']) ?></strong>
          <span class="rest-meta"><?= e($dish['restaurant_name']) ?></span>
          <span class="special-price">&#8377;<?= number_format((float)$dish['price'], 0) ?></span>
        </span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<section class="land-section land-alt" id="offers">
  <h2 class="section-title">Offers &amp; discounts</h2>
  <p class="section-sub">Sample offers for the demo. Codes become usable once the cart and checkout module is built.</p>
  <div class="offer-grid">
    <?php foreach ($offers as [$big, $title, $text, $code]): ?>
      <article class="offer-card">
        <span class="offer-big"><?= e($big) ?></span>
        <h3><?= e($title) ?></h3>
        <p><?= e($text) ?></p>
        <span class="offer-code">Code: <strong><?= e($code) ?></strong></span>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<footer class="home-footer">
  <div class="footer-grid">
    <div>
      <a class="brand brand-light" href="index.php"><span class="logo">F</span>Food Dash</a>
      <p class="footer-about">Order from local restaurants in Udupi, Manipal and Mangaluru. Web Technology project, Batch B2, Team 4.</p>
    </div>
    <div>
      <h4>Explore</h4>
      <a href="index.php">Home</a>
      <a href="restaurants.php">Restaurants</a>
      <a href="index.php#offers">Offers</a>
      <a href="restaurants.php?category=Desserts">Desserts</a>
    </div>
    <div>
      <h4>Account</h4>
      <?php if (is_logged_in()): ?>
        <a href="dashboard.php">My account</a>
        <a href="addresses.php">Delivery addresses</a>
        <a href="logout.php">Log out</a>
      <?php else: ?>
        <a href="register.php">Create account</a>
        <a href="login.php">Log in</a>
        <a href="forgot_password.php">Forgot password</a>
      <?php endif; ?>
    </div>
    <div>
      <h4>Contact</h4>
      <span>Customer care: <strong>0820-200000</strong></span>
      <span>Email: <strong>support@fooddash.example</strong></span>
      <span>Hours: 9:00 AM - 10:00 PM, every day</span>
    </div>
  </div>
  <div class="footer-bottom">&copy; <?= date('Y') ?> Food Dash &middot; Web Technology project &middot; Batch B2 &middot; Team 4</div>
</footer>
<?php include 'includes/footer.php'; ?>
