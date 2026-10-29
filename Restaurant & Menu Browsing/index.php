<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Food Dash – Browse Restaurants &amp; Menus</title>
<meta name="description" content="Browse restaurants and menus in Udupi and Manipal. Filter by diet, spice, price and allergens, and discover chef's specials.">
<meta name="theme-color" content="#c2410c">
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='8' fill='%23c2410c'/%3E%3C/svg%3E">
<link rel="stylesheet" href="style.css">
</head>
<body>
<a class="skip" href="#main">Skip to main content</a>
<header class="top">
  <a class="logo" href="index.php">Food Dash</a>
  <form class="search" role="search" id="searchForm">
    <label for="q" class="sr">Search dishes, restaurants or cuisine</label>
    <input id="q" type="search" placeholder="Search dishes, restaurants or cuisine…" autocomplete="off" maxlength="60">
  </form>
  <button class="btn dark" id="myFood" type="button">My Food <span id="cartCount" aria-live="polite">0</span></button>
</header>
<main id="main">
  <div class="bar">
    <div class="tabs" role="tablist" aria-label="Browse by">
      <button role="tab" id="tab-dishes" aria-selected="true" aria-controls="view-dishes">All Dishes &amp; Explore</button>
      <button role="tab" id="tab-rest" aria-selected="false" aria-controls="view-rest" tabindex="-1">Restaurants Directory</button>
    </div>
    <div class="sort">
      <label for="sort">Sort by:</label>
      <select id="sort"></select>
      <button class="link" id="reset" type="button">Reset Filters</button>
    </div>
  </div>
  <div id="view-dishes" role="tabpanel" aria-labelledby="tab-dishes">
    <nav id="chips" class="chips" aria-label="Food categories"></nav>
    <div class="layout">
      <aside class="filters" aria-label="Refine search">
        <div class="fhead"><h2>Refine Search</h2><button class="link" id="clear" type="button">Clear</button></div>
        <fieldset><legend>Dietary type</legend>
          <label><input type="radio" name="diet" value="" checked> All Items</label>
          <label><input type="radio" name="diet" value="veg"> Veg Only</label>
          <label><input type="radio" name="diet" value="nonveg"> Non-Veg Only</label>
          <label><input type="radio" name="diet" value="vegan"> Vegan Options</label>
        </fieldset>
        <div class="field"><label for="spice" class="legend">Max spice level</label>
          <select id="spice"><option value="">Any Spice Level</option><option value="1">Mild</option><option value="2">Medium</option><option value="3">Spicy</option></select></div>
        <div class="field"><label for="price" class="legend price">Max Price: <b id="priceOut">₹600</b></label>
          <input id="price" type="range" min="50" max="600" step="10" value="600">
          <div class="scale"><span id="pmin">₹50</span><span id="pmax">₹600</span></div></div>
        <div class="field"><label for="rest" class="legend">Restaurant</label><select id="rest"><option value="">All Restaurants</option></select></div>
        <fieldset><legend>Exclude allergens</legend>
          <label><input type="checkbox" name="ex" value="gluten"> Gluten Free</label>
          <label><input type="checkbox" name="ex" value="dairy"> Dairy Free</label>
          <label><input type="checkbox" name="ex" value="nuts"> Nut Free</label>
        </fieldset>
      </aside>
      <div>
        <section class="hero" id="heroSec" aria-labelledby="heroH"><span class="tag">Feature highlight</span><h2 id="heroH">Chef's Special Recommendations</h2><div id="hero" class="hero-grid"></div></section>
        <div class="row"><h2>All Menu Items</h2><p id="count" role="status" class="muted"></p></div>
        <div id="grid" class="grid"></div>
      </div>
    </div>
  </div>
  <div id="view-rest" role="tabpanel" aria-labelledby="tab-rest" hidden>
    <div class="row"><h2>Restaurants Directory</h2><p id="rcount" role="status" class="muted"></p></div>
    <div id="rgrid" class="grid"></div>
  </div>
</main>
<dialog id="dlg" aria-labelledby="dTitle"><div id="dBody"></div></dialog>
<div id="toast" role="status" aria-live="polite"></div>
<script src="app.js" defer></script>
</body>
</html>
