<?php
/* ==========================================================
   Food Dash - MY PROFILE (Module 1)
   Edit profile, notification preferences, change password,
   multiple delivery addresses (CRUD + validation), confirmed
   account deletion.
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
function go($url) { header('Location: ' . $url); exit; }
function flash($type, $msg) { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }

// Remember-me: auto-login from cookie
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
$uid = (int)($_SESSION['user_id'] ?? 0);
if (!$uid) { header('Location: login.php?next=profile.php'); exit; }

const ALLOWED_IMAGES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const AVATAR_DIR = __DIR__ . '/uploads/avatars';
// Optional: paste a Google Maps Geocoding API key here to use Google Maps.
// Leave it empty to use OpenStreetMap (free, no key needed).
const GOOGLE_MAPS_API_KEY = '';

function jsonOut($data, $code = 200) { http_response_code($code); header('Content-Type: application/json'); echo json_encode($data); exit; }

// Stores an uploaded photo under a random name and returns that name
function saveAvatar(array $file): string {
    $mime = getimagesize($file['tmp_name'])['mime'];
    if (!is_dir(AVATAR_DIR)) { mkdir(AVATAR_DIR, 0755, true); }
    $name = bin2hex(random_bytes(12)) . '.' . ALLOWED_IMAGES[$mime];
    move_uploaded_file($file['tmp_name'], AVATAR_DIR . '/' . $name);
    return $name;
}

// Converts GPS coordinates into a street address (Google Maps if a key is set, else OpenStreetMap)
function reverseGeocode(float $lat, float $lng): array {
    $ctx = stream_context_create(['http' => [
        'timeout' => 12,
        'ignore_errors' => true,
        'header' => "User-Agent: FoodDash/1.0 (delivery address lookup)\r\nAccept: application/json\r\n",
    ]]);

    if (GOOGLE_MAPS_API_KEY !== '') {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=' . $lat . ',' . $lng . '&key=' . urlencode(GOOGLE_MAPS_API_KEY);
        $d = json_decode((string)@file_get_contents($url, false, $ctx), true);
        if (($d['status'] ?? '') !== 'OK') { throw new RuntimeException('Google Maps could not find an address for this location.'); }
        $p = [];
        foreach ($d['results'][0]['address_components'] as $c) {
            foreach ($c['types'] as $t) { $p[$t] ??= $c['long_name']; }
        }
        return [
            'house'    => trim(($p['street_number'] ?? '') . ' ' . ($p['route'] ?? '')),
            'locality' => $p['sublocality_level_1'] ?? $p['neighborhood'] ?? $p['sublocality'] ?? '',
            'city'     => $p['locality'] ?? $p['administrative_area_level_2'] ?? '',
            'state'    => $p['administrative_area_level_1'] ?? '',
            'pincode'  => preg_replace('/\D/', '', $p['postal_code'] ?? ''),
        ];
    }

    $url = 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&addressdetails=1&zoom=18&lat=' . $lat . '&lon=' . $lng;
    $body = @file_get_contents($url, false, $ctx);
    $d = is_string($body) ? json_decode($body, true) : null;
    $a = $d['address'] ?? null;
    if (!$a) { throw new RuntimeException('Address lookup failed. Check your internet connection and try again.'); }
    $road = $a['road'] ?? $a['pedestrian'] ?? $a['residential'] ?? $a['footway'] ?? '';
    return [
        // A road is not a house number; keep the house field for the user's building/flat.
        'house'    => trim($a['house_number'] ?? ''),
        'locality' => implode(', ', array_filter(array_unique([
            $a['suburb'] ?? $a['neighbourhood'] ?? $a['quarter'] ?? $a['village'] ?? $a['hamlet'] ?? $a['city_district'] ?? '',
            $road,
        ]))),
        'city'     => $a['city'] ?? $a['town'] ?? $a['municipality'] ?? $a['county'] ?? $a['village'] ?? '',
        'state'    => $a['state'] ?? '',
        'pincode'  => preg_replace('/\D/', '', $a['postcode'] ?? ''),
    ];
}

// ---------- JSON API: "Use my current location" ----------
if (($_GET['action'] ?? '') === 'reverse_geocode') {
    $lat = (float)($_GET['lat'] ?? 999);
    $lng = (float)($_GET['lng'] ?? 999);
    if (abs($lat) > 90 || abs($lng) > 180) { jsonOut(['ok' => false, 'message' => 'Invalid coordinates.'], 400); }
    try {
        jsonOut(['ok' => true, 'address' => reverseGeocode($lat, $lng)]);
    } catch (RuntimeException $ex) {
        jsonOut(['ok' => false, 'message' => $ex->getMessage()], 502);
    }
}

// ---------- POST handlers ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_avatar') {
        $up = $_FILES['avatar'] ?? null;
        if (!$up || $up['error'] !== UPLOAD_ERR_OK) {
            flash('error', 'Choose a photo to upload.');
        } elseif ($up['size'] > 2 * 1024 * 1024) {
            flash('error', 'Photo must be 2 MB or smaller.');
        } elseif (!($info = @getimagesize($up['tmp_name'])) || !isset(ALLOWED_IMAGES[$info['mime']])) {
            flash('error', 'Only JPG, PNG or WebP images are allowed.');
        } else {
            $oldPhoto = $pdo->query("SELECT avatar FROM users WHERE id = $uid")->fetchColumn();
            $name = saveAvatar($up);
            $pdo->prepare('UPDATE users SET avatar = ? WHERE id = ?')->execute([$name, $uid]);
            if ($oldPhoto) { @unlink(AVATAR_DIR . '/' . basename($oldPhoto)); }
            flash('success', 'Profile photo updated.');
        }
        go('profile.php');
    }

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = preg_replace('/\s+/', '', $_POST['phone'] ?? '');
        if (!preg_match("/^[A-Za-z][A-Za-z .'\-]{1,99}$/", $name)) {
            flash('error', 'Name must contain only letters (2-100 characters).');
        } elseif (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
            flash('error', 'Enter a valid 10-digit mobile number.');
        } else {
            $chk = $pdo->prepare('SELECT COUNT(*) FROM users WHERE phone = ? AND id <> ?');
            $chk->execute([$phone, $uid]);
            if ($chk->fetchColumn()) {
                flash('error', 'That phone number is already used by another account.');
            } else {
                $pdo->prepare('UPDATE users SET name = ?, phone = ? WHERE id = ?')->execute([$name, $phone, $uid]);
                $_SESSION['user_name'] = $name;
                flash('success', 'Profile updated.');
            }
        }
        go('profile.php');
    }

    if ($action === 'update_notifications') {
        $pdo->prepare('UPDATE users SET notify_orders = ?, notify_account = ? WHERE id = ?')
            ->execute([!empty($_POST['notify_orders']) ? 1 : 0, !empty($_POST['notify_account']) ? 1 : 0, $uid]);
        flash('success', 'Notification preferences saved.');
        go('profile.php');
    }

    if ($action === 'change_password') {
        $cur = $_POST['current_password'] ?? '';
        $new = $_POST['new_password'] ?? '';
        $cnf = $_POST['confirm_password'] ?? '';
        $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([$uid]);
        $hash = $st->fetchColumn();
        if (!password_verify($cur, $hash)) flash('error', 'Current password is incorrect.');
        elseif (strlen($new) < 8 || !preg_match('/[A-Z]/', $new) || !preg_match('/[a-z]/', $new) || !preg_match('/[0-9]/', $new) || !preg_match('/[^A-Za-z0-9]/', $new))
            flash('error', 'New password must be 8+ characters with upper & lower case, a number and a symbol.');
        elseif ($new !== $cnf) flash('error', 'New passwords do not match.');
        elseif (password_verify($new, $hash)) flash('error', 'New password must be different from the current one.');
        else {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
            flash('success', 'Password changed successfully.');
        }
        go('profile.php');
    }

    if ($action === 'save_address') {
        $id = (int)($_POST['address_id'] ?? 0);
        $label = $_POST['label'] ?? 'Home';
        $house = trim($_POST['house'] ?? '');
        $locality = trim($_POST['locality'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $state = trim($_POST['state'] ?? '');
        $pin = trim($_POST['pincode'] ?? '');
        $err = null;
        if (!in_array($label, ['Home', 'Work', 'Other'], true))   $err = 'Choose a valid address label.';
        elseif (!preg_match('/^.{3,150}$/', $house))               $err = 'House / flat / building must be 3-150 characters.';
        elseif (!preg_match('/^.{3,150}$/', $locality))            $err = 'Locality / landmark must be 3-150 characters.';
        elseif (!preg_match("/^[A-Za-z][A-Za-z .'\-]{1,79}$/", $city))  $err = 'City must contain only letters.';
        elseif (!preg_match("/^[A-Za-z][A-Za-z .'\-]{1,79}$/", $state)) $err = 'State must contain only letters.';
        elseif (!preg_match('/^[1-9][0-9]{5}$/', $pin))            $err = 'PIN code must be a valid 6-digit number.';
        if ($err) {
            flash('error', $err);
        } elseif ($id) {
            $pdo->prepare('UPDATE addresses SET label=?, house=?, locality=?, city=?, state=?, pincode=? WHERE id=? AND user_id=?')
                ->execute([$label, $house, $locality, $city, $state, $pin, $id, $uid]);
            flash('success', 'Address updated.');
        } else {
            $cnt = (int)$pdo->query("SELECT COUNT(*) FROM addresses WHERE user_id = $uid")->fetchColumn();
            $pdo->prepare('INSERT INTO addresses (user_id, label, house, locality, city, state, pincode, is_default) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$uid, $label, $house, $locality, $city, $state, $pin, $cnt === 0 ? 1 : 0]);
            flash('success', 'Address saved.');
        }
        go('profile.php');
    }

    if ($action === 'default_address') {
        $aid = (int)($_POST['address_id'] ?? 0);
        $pdo->prepare('UPDATE addresses SET is_default = (id = ?) WHERE user_id = ?')->execute([$aid, $uid]);
        flash('success', 'Default address changed.');
        go('profile.php');
    }

    if ($action === 'delete_address') {
        $pdo->prepare('DELETE FROM addresses WHERE id = ? AND user_id = ?')->execute([(int)$_POST['address_id'], $uid]);
        flash('success', 'Address deleted.');
        go('profile.php');
    }

    if ($action === 'delete_account') {
        $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
        $st->execute([$uid]);
        if (!password_verify($_POST['confirm_password'] ?? '', $st->fetchColumn())) {
            flash('error', 'Password is incorrect. Account was not deleted.');
            go('profile.php');
        }
        $photo = $pdo->query("SELECT avatar FROM users WHERE id = $uid")->fetchColumn();
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);   // cascades to addresses, cart, orders, favourites
        if ($photo) { @unlink(AVATAR_DIR . '/' . basename($photo)); }
        setcookie('fd_remember', '', time() - 3600, '/');
        $_SESSION = [];
        session_destroy();
        session_start();
        flash('success', 'Your account has been permanently deleted.');
        go('login.php');
    }
}

// ---------- Load data for display ----------
$st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$uid]);
$me = $st->fetch();
$addresses = $pdo->query("SELECT * FROM addresses WHERE user_id = $uid ORDER BY is_default DESC, id")->fetchAll();
$editId = (int)($_GET['edit'] ?? 0);
$editAddr = null;
foreach ($addresses as $a) { if ($a['id'] == $editId) $editAddr = $a; }
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$userName = $_SESSION['user_name'] ?? $me['name'];
$cartCount = (int)$pdo->query("SELECT COALESCE(SUM(qty),0) FROM cart_items WHERE user_id = $uid")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Food Dash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { 50:'#fff7ed', 100:'#ffedd5', 500:'#f97316', 600:'#ea580c', 700:'#c2410c' } } } } };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1.25rem; padding: 1.5rem; box-shadow: 0 1px 2px rgba(15,23,42,.04); }
        .field { width: 100%; padding: .6rem .9rem; border: 1px solid #cbd5e1; border-radius: .75rem; font-size: .875rem; outline: none; }
        .field:focus { box-shadow: 0 0 0 2px #f97316; border-color: #f97316; }
        .lbl { display: block; font-size: .75rem; font-weight: 700; color: #334155; margin-bottom: .3rem; }
    </style>
</head>
<body class="min-h-screen flex flex-col text-slate-800">

<header class="bg-white sticky top-0 z-40 shadow-sm">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between gap-4">
        <a href="index.php" class="flex items-center gap-2 font-black text-xl text-slate-900">
            <span class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-amber-500 text-white flex items-center justify-center">🍔</span> Food Dash
        </a>
        <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-slate-600">
            <a href="restaurants.php" class="hover:text-brand-600">Restaurants</a>
            <a href="offers.php" class="hover:text-brand-600">Offers</a>
            <a href="favourites.php" class="hover:text-brand-600">Favourites</a>
            <a href="orders.php" class="hover:text-brand-600">My Orders</a>
            <a href="profile.php" class="text-brand-600 font-bold">Profile</a>
        </nav>
        <div class="flex items-center gap-2">
            <a href="cart.php" class="px-3 py-2 rounded-xl bg-slate-900 text-white text-xs font-bold">🛒 Cart <span class="ml-1 px-1.5 rounded-full bg-brand-600"><?= $cartCount ?></span></a>
            <a href="logout.php" class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-xs font-bold">Logout</a>
        </div>
    </div>
</header>

<main class="flex-grow max-w-5xl w-full mx-auto px-4 sm:px-6 py-8 space-y-6">

    <?php if ($flash): ?>
        <div class="p-3 rounded-xl text-sm font-semibold <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200' ?>"><?= e($flash['msg']) ?></div>
    <?php endif; ?>

    <!-- Account header -->
    <section class="card flex flex-wrap items-center gap-4">
        <?php if (!empty($me['avatar'])): ?>
            <img src="uploads/avatars/<?= e($me['avatar']) ?>" alt="Profile photo" class="w-16 h-16 rounded-2xl object-cover ring-1 ring-slate-200">
        <?php else: ?>
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-brand-600 to-amber-500 text-white flex items-center justify-center text-2xl font-black">
                <?= e(strtoupper(substr($me['name'], 0, 1))) ?>
            </div>
        <?php endif; ?>
        <div>
            <h1 class="text-xl font-black text-slate-900"><?= e($me['name']) ?></h1>
            <p class="text-sm text-slate-500"><?= e($me['email']) ?> · <?= e($me['phone']) ?></p>
            <p class="text-xs text-slate-400 mt-1">Member since <?= date('d M Y', strtotime($me['created_at'])) ?></p>
        </div>
        <form method="post" action="profile.php" enctype="multipart/form-data" class="sm:ml-auto flex flex-wrap items-center gap-2 text-xs" data-confirm="Use this as your profile photo?">
            <input type="hidden" name="action" value="update_avatar">
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required class="text-xs text-slate-600 max-w-[200px]">
            <button class="px-3 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold">📷 Change photo</button>
        </form>
    </section>

    <div class="grid lg:grid-cols-2 gap-6">
        <!-- Personal details -->
        <section class="card">
            <h2 class="font-black text-slate-900 mb-4">Personal details</h2>
            <form method="post" action="profile.php" class="space-y-4" data-confirm="Save these changes to your account details?">
                <input type="hidden" name="action" value="update_profile">
                <div><label class="lbl" for="name">Full name</label><input class="field" id="name" name="name" value="<?= e($me['name']) ?>" required></div>
                <div><label class="lbl" for="phone">Mobile number</label><input class="field" id="phone" name="phone" maxlength="10" value="<?= e($me['phone']) ?>" required></div>
                <div><label class="lbl">Email (cannot be changed)</label><input class="field bg-slate-50" value="<?= e($me['email']) ?>" disabled></div>
                <button class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold">Save details</button>
            </form>
        </section>

        <!-- Notifications -->
        <section class="card">
            <h2 class="font-black text-slate-900 mb-4">Notification preferences</h2>
            <form method="post" action="profile.php" class="space-y-3 text-sm">
                <input type="hidden" name="action" value="update_notifications">
                <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 cursor-pointer">
                    <span><b>Order updates</b><br><span class="text-xs text-slate-500">Status changes for your deliveries</span></span>
                    <input type="checkbox" name="notify_orders" value="1" <?= $me['notify_orders'] ? 'checked' : '' ?> class="w-5 h-5 text-brand-600 rounded">
                </label>
                <label class="flex items-center justify-between p-3 rounded-xl border border-slate-200 cursor-pointer">
                    <span><b>Account activity</b><br><span class="text-xs text-slate-500">Login and security alerts</span></span>
                    <input type="checkbox" name="notify_account" value="1" <?= $me['notify_account'] ? 'checked' : '' ?> class="w-5 h-5 text-brand-600 rounded">
                </label>
                <button class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold">Save preferences</button>
            </form>
        </section>
    </div>

    <!-- Change password -->
    <section class="card">
        <h2 class="font-black text-slate-900 mb-4">Change password</h2>
        <form method="post" action="profile.php" class="grid sm:grid-cols-3 gap-4 items-end" data-confirm="Change your password now?">
            <input type="hidden" name="action" value="change_password">
            <div>
                <label class="lbl" for="current_password">Current password</label>
                <div class="relative"><input class="field pr-16" id="current_password" type="password" name="current_password" required><button type="button" data-toggle="current_password" class="absolute right-2 top-2 text-xs font-bold text-slate-500">Show</button></div>
            </div>
            <div>
                <label class="lbl" for="new_password">New password</label>
                <div class="relative"><input class="field pr-16" id="new_password" type="password" name="new_password" required><button type="button" data-toggle="new_password" class="absolute right-2 top-2 text-xs font-bold text-slate-500">Show</button></div>
            </div>
            <div>
                <label class="lbl" for="confirm_password">Confirm new password</label>
                <div class="relative"><input class="field pr-16" id="confirm_password" type="password" name="confirm_password" required><button type="button" data-toggle="confirm_password" class="absolute right-2 top-2 text-xs font-bold text-slate-500">Show</button></div>
            </div>
            <div class="sm:col-span-3"><button class="px-5 py-2.5 rounded-xl bg-brand-600 hover:bg-brand-700 text-white text-sm font-bold">Update password</button></div>
        </form>
    </section>

    <!-- Addresses (multiple delivery addresses, CRUD) -->
    <section class="card">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-black text-slate-900">Saved delivery addresses</h2>
            <span class="text-xs text-slate-500"><?= count($addresses) ?> saved</span>
        </div>

        <div class="grid md:grid-cols-2 gap-3">
            <?php foreach ($addresses as $a): ?>
                <div class="p-4 rounded-2xl border <?= $a['is_default'] ? 'border-brand-500 bg-brand-50/40' : 'border-slate-200' ?>">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="font-bold text-sm"><?= e($a['label']) ?></span>
                        <?php if ($a['is_default']): ?><span class="text-[10px] font-bold bg-slate-900 text-white px-2 py-0.5 rounded">Default</span><?php endif; ?>
                    </div>
                    <p class="text-xs text-slate-600 leading-relaxed"><?= e($a['house']) ?>, <?= e($a['locality']) ?>, <?= e($a['city']) ?>, <?= e($a['state']) ?> - <?= e($a['pincode']) ?></p>
                    <div class="flex flex-wrap gap-3 mt-3 text-xs font-bold">
                        <a href="profile.php?edit=<?= (int)$a['id'] ?>#address-form" class="text-brand-600 hover:underline">Edit</a>
                        <?php if (!$a['is_default']): ?>
                            <form method="post" action="profile.php" class="inline"><input type="hidden" name="action" value="default_address"><input type="hidden" name="address_id" value="<?= (int)$a['id'] ?>"><button class="text-slate-600 hover:underline">Make default</button></form>
                        <?php endif; ?>
                        <form method="post" action="profile.php" class="inline" data-confirm="Delete this address?">
                            <input type="hidden" name="action" value="delete_address"><input type="hidden" name="address_id" value="<?= (int)$a['id'] ?>">
                            <button class="text-red-600 hover:underline">Delete</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$addresses): ?><p class="text-sm text-slate-500">No addresses yet. Add one below.</p><?php endif; ?>
        </div>

        <!-- Add / edit form -->
        <form method="post" action="profile.php" id="address-form" class="mt-6 pt-6 border-t border-slate-100 grid sm:grid-cols-2 gap-4">
            <input type="hidden" name="action" value="save_address">
            <input type="hidden" name="address_id" value="<?= $editAddr ? (int)$editAddr['id'] : 0 ?>">
            <div class="sm:col-span-2 flex items-center justify-between">
                <h3 class="font-bold text-sm"><?= $editAddr ? 'Edit address' : 'Add a new address' ?></h3>
                <?php if ($editAddr): ?><a href="profile.php" class="text-xs font-bold text-slate-500 hover:underline">Cancel edit</a><?php endif; ?>
            </div>
            <div class="sm:col-span-2 flex flex-wrap items-center gap-3">
                <button type="button" onclick="useMyLocation(this)" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-60 text-white text-xs font-bold">📍 Use my current location</button>
                <span data-loc-status class="text-xs text-slate-500">Fills the address in automatically from your GPS.</span>
            </div>
            <div>
                <label class="lbl" for="label">Label</label>
                <select class="field" id="label" name="label"><?php foreach (['Home','Work','Other'] as $l): ?><option <?= ($editAddr['label'] ?? 'Home') === $l ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?></select>
            </div>
            <div><label class="lbl" for="house">Flat / House no. / Building *</label><input class="field" id="house" name="house" required minlength="3" maxlength="150" value="<?= e($editAddr['house'] ?? '') ?>"></div>
            <div><label class="lbl" for="locality">Locality / Landmark *</label><input class="field" id="locality" name="locality" required minlength="3" maxlength="150" value="<?= e($editAddr['locality'] ?? '') ?>"></div>
            <div><label class="lbl" for="city">City *</label><input class="field" id="city" name="city" required value="<?= e($editAddr['city'] ?? 'Udupi') ?>"></div>
            <div><label class="lbl" for="state">State *</label><input class="field" id="state" name="state" required value="<?= e($editAddr['state'] ?? 'Karnataka') ?>"></div>
            <div><label class="lbl" for="pincode">PIN code *</label><input class="field" id="pincode" name="pincode" required maxlength="6" placeholder="576101" value="<?= e($editAddr['pincode'] ?? '') ?>"></div>
            <div class="flex items-end"><button class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-bold"><?= $editAddr ? 'Update address' : 'Save address' ?></button></div>
        </form>
    </section>

    <!-- Danger zone -->
    <section class="card border-red-200">
        <h2 class="font-black text-red-700 mb-2">Delete account</h2>
        <p class="text-sm text-slate-600 mb-4">This permanently removes your profile, addresses, favourites, cart and order history. This cannot be undone.</p>
        <form method="post" action="profile.php" class="flex flex-col sm:flex-row gap-3 items-start sm:items-end" id="delete-form">
            <input type="hidden" name="action" value="delete_account">
            <div class="w-full sm:w-72"><label class="lbl" for="del_pass">Confirm with your password</label><input class="field" id="del_pass" type="password" name="confirm_password" required></div>
            <button class="px-5 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-bold">Delete my account</button>
        </form>
    </section>
</main>

<script>
// "Use my current location": browser GPS -> server reverse geocoding -> fills the address form
async function useMyLocation(btn) {
    const form = btn.closest('form');
    const status = form.querySelector('[data-loc-status]');
    const say = (text, cls) => { status.textContent = text; status.className = 'text-xs ' + (cls || 'text-slate-500'); };
    if (!navigator.geolocation) { say('Your browser does not support location access.', 'text-red-600'); return; }

    const GOOD_ACCURACY = 100;    // metres: accept immediately (phone GPS)
    const MAX_ACCURACY  = 5000;   // metres: beyond this the fix is IP-based and likely the wrong area
    const SETTLE_MS     = 8000;   // wait this long for a better fix before settling for the best one
    const HARD_STOP_MS  = 20000;  // absolute limit

    btn.disabled = true;
    say('Finding your location…');

    const fillForm = async pos => {
        const acc = Math.round(pos.coords.accuracy || 0);
        try {
            const params = new URLSearchParams({ action: 'reverse_geocode', lat: pos.coords.latitude, lng: pos.coords.longitude });
            const res = await fetch(`profile.php?${params}`, { cache: 'no-store', headers: { Accept: 'application/json' } });
            const j = await res.json().catch(() => null);
            if (!res.ok || !j?.ok) throw new Error(j?.message || 'Address lookup failed. Please try again.');
            const a = j.address;
            if (![a.locality, a.city, a.state, a.pincode].some(Boolean)) {
                throw new Error('Location found, but no address details were returned. Please type the address.');
            }
            // Fill the form only; the user reviews it and saves manually.
            for (const [name, value] of Object.entries(a)) {
                if (value && form.elements[name]) form.elements[name].value = value;
            }
            if (form.elements.locality && !form.elements.locality.value.trim()) form.elements.locality.value = a.city || a.state || '';
            if (form.elements.label && !form.elements.label.value) form.elements.label.value = 'Home';
            const house = form.elements.house;
            if (house && !house.value.trim()) house.focus();
            if (acc <= GOOD_ACCURACY) {
                say(`Location found (±${acc} m). Add your flat / house number, check the address, then save.`, 'text-emerald-700');
            } else {
                say(`Approximate location (±${acc} m) because this device has no GPS. Please check the locality and PIN, add your flat / house number, then save.`, 'text-amber-700');
            }
        } catch (err) {
            say(err.message || 'Could not read the address automatically. Please type it in.', 'text-red-600');
        } finally {
            btn.disabled = false;
        }
    };

    let best = null, watchId = null, finished = false, settleTimer = null, hardTimer = null;
    const stop = () => {
        finished = true;
        if (watchId !== null) navigator.geolocation.clearWatch(watchId);
        clearTimeout(settleTimer); clearTimeout(hardTimer);
    };
    const finishWithBest = () => {
        if (finished) return;
        stop();
        if (best && best.coords.accuracy <= MAX_ACCURACY) { fillForm(best); return; }
        btn.disabled = false;
        say(best
            ? `Only a very rough location (±${Math.round(best.coords.accuracy)} m) was available, so it was not used. Turn on Windows Location, or type the address.`
            : 'Could not get your location. Allow location for this site in the browser and Windows settings, or type the address.', 'text-red-600');
    };
    const onError = err => {
        if (finished) return;
        if (best) { finishWithBest(); return; }
        stop();
        btn.disabled = false;
        const messages = {
            1: 'Location access is blocked. Click the lock icon in the address bar, allow Location, and try again.',
            2: 'Your location is unavailable. Turn on Windows Location services and check your connection.',
            3: 'Getting your location took too long. Try again, or type the address.'
        };
        say(messages[err.code] || 'Could not get your location. Please try again.', 'text-red-600');
    };

    settleTimer = setTimeout(finishWithBest, SETTLE_MS);
    hardTimer = setTimeout(finishWithBest, HARD_STOP_MS);
    watchId = navigator.geolocation.watchPosition(pos => {
        if (finished) return;
        if (!best || pos.coords.accuracy < best.coords.accuracy) best = pos;
        if (pos.coords.accuracy <= GOOD_ACCURACY) { stop(); fillForm(pos); return; }
        say(`Improving location accuracy… ±${Math.round(best.coords.accuracy)} m`);
    }, onError, { enableHighAccuracy: true, timeout: HARD_STOP_MS, maximumAge: 0 });
}

// Show / hide password toggles
document.querySelectorAll('[data-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.toggle);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Hide' : 'Show';
    });
});

// Confirmation prompts before sensitive actions (Module 1)
document.querySelectorAll('form[data-confirm]').forEach(f => {
    f.addEventListener('submit', ev => { if (!confirm(f.dataset.confirm)) ev.preventDefault(); });
});
document.getElementById('delete-form').addEventListener('submit', ev => {
    if (!confirm('Permanently delete your account? This cannot be undone.')) ev.preventDefault();
});
</script>
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
<script>
// Passwords cannot be copied, cut, pasted or dragged out of the field
document.querySelectorAll('input[type="password"]').forEach(function (input) {
    ['copy', 'cut', 'paste', 'contextmenu', 'dragstart', 'drop'].forEach(function (ev) {
        input.addEventListener(ev, function (e) { e.preventDefault(); });
    });
});
</script>
</body>
</html>
