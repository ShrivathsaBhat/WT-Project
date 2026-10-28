<?php
/* ==========================================================
   Food Dash - HOME PAGE (entry point)
   Module 1 (login / sign-up links, cookie-based delivery location)
   Module 2 (restaurant listing, categories, chef's specials)
   Module 5 (offers strip)
   Self-contained: PHP + HTML + CSS + JS in this one file.
   ========================================================== */
date_default_timezone_set('Asia/Kolkata');
session_start();
try {
    $pdo = new PDO('mysql:host=localhost;dbname=food_dash;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $ex) {
    exit('<h2>Database connection failed</h2><p>Import database/food_dash.sql and check the credentials at the top of this file.</p>');
}
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function money($v) { return '₹' . number_format((float)$v, 2); }
function go($url) { header('Location: ' . $url); exit; }
function flash($type, $msg) { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }

// Remember-me: auto-login from the persistent cookie
if (empty($_SESSION['user_id']) && !empty($_COOKIE['fd_remember'])) {
    $parts = explode(':', $_COOKIE['fd_remember'], 2);
    if (count($parts) === 2) {
        $st = $pdo->prepare('SELECT u.id, u.name, rt.token_hash FROM remember_tokens rt JOIN users u ON u.id = rt.user_id WHERE rt.selector = ? AND rt.expires_at > NOW() AND u.status = ?');
        $st->execute([$parts[0], 'Active']);
        if (($row = $st->fetch()) && hash_equals($row['token_hash'], hash('sha256', $parts[1]))) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$row['id'];
            $_SESSION['user_name'] = $row['name'];
        }
    }
}

// Save delivery location in a cookie (Module 1 - cookie management)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_location') {
    $loc = trim($_POST['location'] ?? '');
    if (preg_match('/^[A-Za-z0-9 ,.\-]{3,60}$/', $loc)) {
        setcookie('fd_location', $loc, time() + 30 * 86400, '/');
        flash('success', 'Delivery location updated to ' . $loc . '.');
    } else {
        flash('error', 'Enter a valid location (3-60 letters, numbers, spaces or , . -).');
    }
    go('index.php');
}

$uid = (int)($_SESSION['user_id'] ?? 0);
$userName = $_SESSION['user_name'] ?? '';
$location = $_COOKIE['fd_location'] ?? 'Udupi, Karnataka';
$cartCount = 0;
if ($uid) {
    $cartCount = (int)$pdo->query("SELECT COALESCE(SUM(qty),0) FROM cart_items WHERE user_id = $uid")->fetchColumn();
}
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();
$restaurants = $pdo->query("SELECT r.*, (SELECT COUNT(*) FROM menu_items m WHERE m.restaurant_id = r.id) AS dish_count FROM restaurants r ORDER BY (r.status = 'Open') DESC, r.rating DESC")->fetchAll();
$offers = $pdo->query("SELECT code, title, badge FROM coupons WHERE status = 'Active' AND expiry_date >= CURDATE() ORDER BY id LIMIT 4")->fetchAll();
$specials = $pdo->query("SELECT m.id, m.name, m.price, m.image_url, m.rating, r.id AS rid, r.name AS rname FROM menu_items m JOIN restaurants r ON r.id = m.restaurant_id WHERE m.is_chefs_special = 1 AND m.in_stock = 1 ORDER BY m.rating DESC LIMIT 4")->fetchAll();
$catEmoji = ['Starters' => '🥟', 'Main Course' => '🍛', 'Desserts' => '🍰', 'Beverages' => '🥤'];
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Food Dash - Order Food Online</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { 50:'#fff7ed', 100:'#ffedd5', 500:'#f97316', 600:'#ea580c', 700:'#c2410c' } } } } };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .hero-bg { background: radial-gradient(circle at 20% 20%, #fb923c 0%, transparent 45%), linear-gradient(120deg, #ea580c 0%, #f97316 55%, #fbbf24 100%); }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { scrollbar-width: none; }
        .card-hover { transition: transform .2s ease, box-shadow .2s ease; }
        .card-hover:hover { transform: translateY(-3px); box-shadow: 0 12px 30px -12px rgba(15,23,42,.25); }
    </style>
</head>
<body class="min-h-screen flex flex-col text-slate-800 antialiased">

<!-- ================= NAVBAR ================= -->
<header class="bg-white sticky top-0 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        <a href="index.php" class="flex items-center gap-2 font-black text-xl text-slate-900">
            <span class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-amber-500 text-white flex items-center justify-center shadow">🍔</span>
            Food Dash
        </a>
        <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-slate-600">
            <a href="restaurants.php" class="hover:text-brand-600">Restaurants</a>
            <a href="offers.php" class="hover:text-brand-600">Offers</a>
            <?php if ($uid): ?>
                <a href="favourites.php" class="hover:text-brand-600">Favourites</a>
                <a href="orders.php" class="hover:text-brand-600">My Orders</a>
                <a href="profile.php" class="hover:text-brand-600">Profile</a>
            <?php endif; ?>
        </nav>
        <div class="flex items-center gap-2">
            <a href="cart.php" class="relative px-3 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold flex items-center gap-2">
                🛒 <span class="hidden sm:inline">Cart</span>
                <span class="min-w-[20px] h-5 px-1 rounded-full bg-brand-600 text-[10px] flex items-center justify-center"><?= $cartCount ?></span>
            </a>
            <?php if ($uid): ?>
                <span class="hidden lg:inline text-xs font-semibold text-slate-600">Hi, <?= e(explode(' ', $userName)[0]) ?></span>
                <a href="logout.php" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold">Logout</a>
            <?php else: ?>
                <a href="login.php" class="px-4 py-2 rounded-xl border border-slate-300 hover:border-brand-600 hover:text-brand-600 text-xs font-bold">Log in</a>
                <a href="register.php" class="px-4 py-2 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-xs font-bold shadow">Sign up</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="flex-grow">

    <?php if ($flash): ?>
        <div class="max-w-7xl mx-auto px-4 sm:px-6 mt-4">
            <div class="p-3 rounded-xl text-sm font-semibold <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                <?= e($flash['msg']) ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- ================= HERO ================= -->
    <section class="hero-bg text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-12 md:py-16 grid md:grid-cols-2 gap-8 items-center">
            <div class="space-y-5">
                <span class="inline-block px-3 py-1 rounded-full bg-white/20 text-xs font-bold tracking-wide">🚀 Delivery in 30 minutes</span>
                <h1 class="text-4xl md:text-5xl font-black leading-tight">
                    <?= $uid ? 'What are you craving, ' . e(explode(' ', $userName)[0]) . '?' : 'Hungry? Get food delivered.' ?>
                </h1>
                <p class="text-orange-50 text-sm md:text-base max-w-md">Browse restaurants near you, customise your order and track it live until it reaches your door.</p>

                <!-- Delivery location (cookie-based) -->
                <form method="post" action="index.php" class="flex flex-col sm:flex-row gap-2 max-w-xl">
                    <input type="hidden" name="action" value="set_location">
                    <div class="flex-1 flex items-center gap-2 bg-white rounded-xl px-3 py-2.5 text-slate-800 shadow">
                        <span>📍</span>
                        <input type="text" name="location" value="<?= e($location) ?>" maxlength="60" required
                               class="w-full text-sm font-semibold outline-none bg-transparent" aria-label="Delivery location">
                    </div>
                    <button class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold">Set location</button>
                </form>

                <!-- Search (sends to the Restaurants explorer) -->
                <form method="get" action="restaurants.php" class="flex gap-2 max-w-xl">
                    <input type="text" name="q" placeholder="Search for restaurants or dishes…"
                           class="flex-1 px-4 py-2.5 rounded-xl text-slate-800 text-sm shadow outline-none focus:ring-2 focus:ring-slate-900">
                    <button class="px-5 py-2.5 rounded-xl bg-white text-brand-700 text-sm font-bold shadow">Search</button>
                </form>
            </div>

            <!-- Login / sign-up call to action (Module 1) -->
            <?php if (!$uid): ?>
                <div class="bg-white/10 backdrop-blur-md border border-white/25 rounded-3xl p-6 md:p-8 space-y-4">
                    <h2 class="text-2xl font-black">Get started in seconds</h2>
                    <p class="text-sm text-orange-50">Create an account to save favourites, reorder your meals and get exclusive coupons.</p>
                    <div class="grid grid-cols-2 gap-3">
                        <a href="login.php" class="text-center px-4 py-3 rounded-xl border-2 border-white font-bold text-sm hover:bg-white hover:text-brand-700 transition">Log in</a>
                        <a href="register.php" class="text-center px-4 py-3 rounded-xl bg-slate-900 font-bold text-sm hover:bg-slate-800 transition">Create account</a>
                    </div>
                    <p class="text-[11px] text-orange-100">Demo account: demo@fooddash.test / Demo@123</p>
                </div>
            <?php else: ?>
                <div class="bg-white/10 backdrop-blur-md border border-white/25 rounded-3xl p-6 md:p-8 grid grid-cols-2 gap-3 text-center">
                    <a href="orders.php" class="p-4 rounded-2xl bg-white/15 hover:bg-white/25 font-bold text-sm">📦<br>My Orders</a>
                    <a href="favourites.php" class="p-4 rounded-2xl bg-white/15 hover:bg-white/25 font-bold text-sm">❤️<br>Favourites</a>
                    <a href="offers.php" class="p-4 rounded-2xl bg-white/15 hover:bg-white/25 font-bold text-sm">🏷️<br>Offers</a>
                    <a href="cart.php" class="p-4 rounded-2xl bg-white/15 hover:bg-white/25 font-bold text-sm">🛒<br>Cart (<?= $cartCount ?>)</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ================= CATEGORY CHIPS (Module 2) ================= -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 mt-8">
        <h2 class="text-xs font-extrabold uppercase tracking-widest text-slate-500 mb-3">What's on your mind?</h2>
        <div class="flex gap-3 overflow-x-auto no-scrollbar pb-2">
            <?php foreach ($categories as $c): ?>
                <a href="restaurants.php?cat=<?= (int)$c['id'] ?>&view=dishes"
                   class="flex-shrink-0 w-28 text-center p-4 rounded-2xl bg-white border border-slate-200 hover:border-brand-500 hover:shadow transition">
                    <div class="text-3xl"><?= $catEmoji[$c['name']] ?? '🍽️' ?></div>
                    <div class="mt-2 text-xs font-bold text-slate-700"><?= e($c['name']) ?></div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- ================= OFFERS STRIP (Module 5) ================= -->
    <?php if ($offers): ?>
    <section class="max-w-7xl mx-auto px-4 sm:px-6 mt-8">
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-lg font-black text-slate-900">Offers for you</h2>
            <a href="offers.php" class="text-xs font-bold text-brand-600 hover:underline">View all →</a>
        </div>
        <div class="flex gap-4 overflow-x-auto no-scrollbar pb-2">
            <?php foreach ($offers as $o): ?>
                <a href="offers.php" class="flex-shrink-0 w-72 p-4 rounded-2xl bg-gradient-to-r from-slate-900 to-slate-700 text-white card-hover">
                    <span class="text-[10px] font-extrabold uppercase bg-amber-400 text-slate-900 px-2 py-0.5 rounded"><?= e($o['badge']) ?></span>
                    <p class="font-bold mt-2"><?= e($o['title']) ?></p>
                    <p class="font-mono text-xs text-amber-300 mt-1">Use code <?= e($o['code']) ?></p>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ================= CHEF'S SPECIALS (Module 2) ================= -->
    <?php if ($specials): ?>
    <section class="max-w-7xl mx-auto px-4 sm:px-6 mt-10">
        <h2 class="text-lg font-black text-slate-900 mb-3">Chef's special recommendations 🔥</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($specials as $s): ?>
                <a href="restaurant.php?id=<?= (int)$s['rid'] ?>" class="bg-white rounded-2xl border border-slate-200 p-3 flex gap-3 card-hover">
                    <div class="w-20 h-20 rounded-xl bg-amber-50 flex-shrink-0 overflow-hidden flex items-center justify-center text-3xl">
                        <?php if ($s['image_url']): ?><img src="<?= e($s['image_url']) ?>" alt="" class="w-full h-full object-cover"><?php else: ?>🍽️<?php endif; ?>
                    </div>
                    <div class="min-w-0">
                        <span class="text-[10px] font-extrabold bg-amber-400 text-slate-900 px-1.5 py-0.5 rounded">SPECIAL</span>
                        <p class="font-bold text-sm text-slate-900 truncate mt-1"><?= e($s['name']) ?></p>
                        <p class="text-xs text-slate-500 truncate"><?= e($s['rname']) ?></p>
                        <p class="text-xs font-black mt-1"><?= money($s['price']) ?> <span class="text-amber-600 font-bold">★ <?= e($s['rating']) ?></span></p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ================= RESTAURANTS NEAR YOU (Module 2) ================= -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 mt-10 mb-12">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-xl font-black text-slate-900">Restaurants near you</h2>
                <p class="text-xs text-slate-500">Delivering to <?= e($location) ?></p>
            </div>
            <a href="restaurants.php" class="text-xs font-bold text-brand-600 hover:underline">See all →</a>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($restaurants as $r): $open = $r['status'] === 'Open'; ?>
                <a href="restaurant.php?id=<?= (int)$r['id'] ?>"
                   class="bg-white rounded-3xl border border-slate-200 overflow-hidden card-hover <?= $open ? '' : 'opacity-60' ?>">
                    <div class="relative h-40">
                        <img src="<?= e($r['banner_url']) ?>" alt="<?= e($r['name']) ?>" class="w-full h-full object-cover">
                        <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-xs font-bold bg-white/95 <?= $open ? 'text-emerald-700' : 'text-slate-500' ?>">
                            <?= $open ? 'Open now' : 'Closed' ?>
                        </span>
                        <span class="absolute bottom-3 left-3 px-2 py-1 rounded-lg bg-slate-900/80 text-white text-xs font-bold">⏱ <?= e($r['delivery_time']) ?></span>
                    </div>
                    <div class="p-5">
                        <div class="flex justify-between items-start gap-2">
                            <h3 class="font-bold text-slate-900 text-lg"><?= e($r['name']) ?></h3>
                            <span class="px-2 py-0.5 rounded-lg bg-emerald-600 text-white text-xs font-bold">★ <?= e($r['rating']) ?></span>
                        </div>
                        <p class="text-xs text-slate-500 mt-1 truncate"><?= e($r['cuisine']) ?></p>
                        <p class="text-xs text-slate-400 mt-2">📍 <?= e($r['address']) ?> · <?= (int)$r['dish_count'] ?> dishes</p>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
</main>

<!-- ================= FOOTER ================= -->
<footer class="bg-slate-900 text-slate-400 text-xs">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 flex flex-col md:flex-row justify-between gap-4">
        <div>
            <p class="font-black text-white text-base">Food Dash</p>
            <p class="mt-1">Online food ordering &amp; delivery · Web Technology project (B2 · T4)</p>
        </div>
        <div class="flex gap-4">
            <a href="restaurants.php" class="hover:text-white">Restaurants</a>
            <a href="offers.php" class="hover:text-white">Offers</a>
            <a href="admin/login.php" class="hover:text-white">Admin</a>
        </div>
    </div>
</footer>

<script>
// Reset to the top on browser reload; restore position for form redirects and back/forward.
(function () {
    if ('scrollRestoration' in history) { history.scrollRestoration = 'manual'; }
    var KEY = 'fd-scroll:' + location.pathname + location.search;
    var ready = false, timer = null;

    function save() {
        if (!ready) return;
        try { sessionStorage.setItem(KEY, JSON.stringify({ y: window.scrollY, t: Date.now() })); } catch (e) {}
    }
    function startSaving() {
        ready = true;
        window.addEventListener('scroll', function () { clearTimeout(timer); timer = setTimeout(save, 100); }, { passive: true });
        window.addEventListener('pagehide', save);
        document.addEventListener('visibilitychange', function () { if (document.visibilityState === 'hidden') save(); });
    }
    function restore() {
        var s = null;
        try { s = JSON.parse(sessionStorage.getItem(KEY) || 'null'); } catch (e) {}
        var nav = (performance.getEntriesByType && performance.getEntriesByType('navigation')[0]) || {};
        // Reload starts at the top; back/forward and recent form redirects restore the saved position.
        if (nav.type === 'reload') {
            try { sessionStorage.removeItem(KEY); } catch (e) {}
            window.scrollTo({ top: 0, behavior: 'auto' });
            startSaving();
            return;
        }
        var allowed = nav.type === 'back_forward' || (s && Date.now() - s.t < 15000);
        if (!s || !allowed || !(s.y > 0)) { startSaving(); return; }
        var tries = 0;
        (function go() {
            window.scrollTo(0, s.y);
            // Images/fonts may still be changing the page height: retry until we reach the spot
            if (Math.abs(window.scrollY - s.y) > 2 && tries++ < 25) { setTimeout(go, 120); }
            else { startSaving(); }
        })();
    }
    if (document.readyState === 'complete') { restore(); } else { window.addEventListener('load', restore); }
})();
</script>
</body>
</html>
