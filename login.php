<?php
/* ==========================================================
   Food Dash - LOGIN (Module 1)
   Credential validation, session login, "Remember me" cookie
   (persistent token), email cookie pre-fill, login error
   handling with temporary lock-out after repeated failures.
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

// Only allow redirects to pages inside this project (basic open-redirect protection)
$next = $_GET['next'] ?? ($_POST['next'] ?? 'index.php');
if (!preg_match('/^[a-z_\/]+\.php(\?[A-Za-z0-9_=&%\-]*)?$/i', $next)) $next = 'index.php';

if (!empty($_SESSION['user_id'])) { header('Location: ' . $next); exit; }

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
$error = '';
$email = strtolower(trim($_COOKIE['fd_email'] ?? '')); // cookie pre-fill
$remember = isset($_COOKIE['fd_email']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $pass = $_POST['password'] ?? '';
    $remember = !empty($_POST['remember']);

    if (($_SESSION['lock_until'] ?? 0) > time()) {
        $error = 'Too many failed attempts. Try again in ' . ($_SESSION['lock_until'] - time()) . ' seconds.';
    } elseif ($email === '' || $pass === '') {
        $error = 'Please enter both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $st = $pdo->prepare('SELECT id, name, password_hash, status FROM users WHERE email = ?');
        $st->execute([$email]);
        $user = $st->fetch();

        if (!$user || !password_verify($pass, $user['password_hash'])) {
            $_SESSION['login_fails'] = ($_SESSION['login_fails'] ?? 0) + 1;
            if ($_SESSION['login_fails'] >= 5) {
                $_SESSION['lock_until'] = time() + 60;
                $_SESSION['login_fails'] = 0;
                $error = 'Too many failed attempts. Login locked for 60 seconds.';
            } else {
                $error = 'Incorrect email or password.';
            }
        } elseif ($user['status'] !== 'Active') {
            $error = 'This account is suspended. Please contact support.';
        } else {
            // Successful login
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['user_name'] = $user['name'];
            unset($_SESSION['login_fails'], $_SESSION['lock_until']);

            // Cookie: remember the email (login preference)
            setcookie('fd_email', $remember ? $email : '', $remember ? time() + 90 * 86400 : time() - 3600, '/');

            // Remember me: persistent login token stored hashed in the database
            if ($remember) {
                $selector = bin2hex(random_bytes(8));
                $validator = bin2hex(random_bytes(32));
                $pdo->prepare('INSERT INTO remember_tokens (user_id, selector, token_hash, expires_at) VALUES (?, ?, ?, ?)')
                    ->execute([$user['id'], $selector, hash('sha256', $validator), date('Y-m-d H:i:s', time() + 30 * 86400)]);
                setcookie('fd_remember', $selector . ':' . $validator, time() + 30 * 86400, '/', '', false, true);
            }

            header('Location: ' . $next);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log In - Food Dash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { 50:'#fff7ed', 100:'#ffedd5', 500:'#f97316', 600:'#ea580c', 700:'#c2410c' } } } } };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen flex flex-col text-slate-800">

<header class="bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        <a href="index.php" class="flex items-center gap-2 font-black text-xl text-slate-900">
            <span class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-amber-500 text-white flex items-center justify-center">🍔</span> Food Dash
        </a>
        <a href="register.php" class="text-sm font-bold text-brand-600 hover:underline">New here? Create account</a>
    </div>
</header>

<main class="flex-grow flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-slate-200/80 p-6 sm:p-10">
        <h1 class="text-2xl font-black text-slate-900">Welcome back 👋</h1>
        <p class="text-sm text-slate-500 mt-1">Log in to order, track deliveries and manage favourites.</p>

        <?php if ($flash): ?>
            <div class="mt-4 p-3 rounded-xl text-sm font-semibold <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200' ?>"><?= e($flash['msg']) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="mt-4 p-3 rounded-xl text-sm font-semibold bg-red-50 text-red-800 border border-red-200" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" class="mt-6 space-y-4" id="login-form">
            <input type="hidden" name="next" value="<?= e($next) ?>">
            <div>
                <label for="email" class="block text-xs font-bold text-slate-700 mb-1">Email</label>
                <input id="email" name="email" type="email" value="<?= e($email) ?>" required autocomplete="email"
                       class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 outline-none text-sm">
            </div>
            <div>
                <div class="flex justify-between items-center mb-1">
                    <label for="password" class="block text-xs font-bold text-slate-700">Password</label>
                    <a href="forgot_password.php" class="text-xs font-bold text-brand-600 hover:underline">Forgot password?</a>
                </div>
                <div class="relative">
                    <input id="password" name="password" type="password" required autocomplete="current-password"
                           class="w-full px-4 py-2.5 pr-20 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 outline-none text-sm">
                    <button type="button" id="toggle-pass" class="absolute right-2 top-1.5 px-2.5 py-1 text-xs font-bold text-slate-500 hover:text-brand-600">Show</button>
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                <input type="checkbox" name="remember" value="1" <?= $remember ? 'checked' : '' ?> class="w-4 h-4 rounded text-brand-600 focus:ring-brand-500">
                Remember me on this device
            </label>
            <button type="submit" class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-lg transition">Log in</button>
        </form>

        <p class="text-center text-sm text-slate-500 mt-6">New to Food Dash? <a href="register.php" class="font-bold text-brand-600 hover:underline">Create an account</a></p>
        <p class="text-center text-xs text-slate-400 mt-4">Demo: demo@fooddash.test / Demo@123</p>
    </div>
</main>

<script>
// Show / hide password
document.getElementById('toggle-pass').addEventListener('click', function () {
    const input = document.getElementById('password');
    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    this.textContent = show ? 'Hide' : 'Show';
});

// Client-side validation (email format + required fields)
document.getElementById('login-form').addEventListener('submit', function (ev) {
    const email = document.getElementById('email').value.trim();
    const pass = document.getElementById('password').value;
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(email) || pass.length === 0) {
        ev.preventDefault();
        alert('Enter a valid email address and your password.');
    }
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
