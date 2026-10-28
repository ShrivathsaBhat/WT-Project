<?php
/* ==========================================================
   Food Dash - FORGOT PASSWORD & RESET (Module 1)
   Step 1: verify identity (registered email + mobile number)
   Step 2: create a new password that meets the strength rules.
   (No e-mail is sent - this is a simulated recovery flow.)
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

$error = '';
$step = isset($_SESSION['reset_uid']) ? 2 : 1;   // step 2 once identity is verified
$action = $_POST['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'verify') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $phone = preg_replace('/\s+/', '', $_POST['phone'] ?? '');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid registered email address.';
    } elseif (!preg_match('/^[6-9][0-9]{9}$/', $phone)) {
        $error = 'Enter the 10-digit mobile number linked to your account.';
    } else {
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ? AND phone = ?');
        $st->execute([$email, $phone]);
        if ($u = $st->fetch()) {
            $_SESSION['reset_uid'] = (int)$u['id'];
            $step = 2;
        } else {
            $error = 'Details do not match any account.';
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'reset' && $step === 2) {
    $pass = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if (strlen($pass) < 8)                          $error = 'Password must be at least 8 characters.';
    elseif (!preg_match('/[A-Z]/', $pass))          $error = 'Password needs an uppercase letter.';
    elseif (!preg_match('/[a-z]/', $pass))          $error = 'Password needs a lowercase letter.';
    elseif (!preg_match('/[0-9]/', $pass))          $error = 'Password needs a number.';
    elseif (!preg_match('/[^A-Za-z0-9]/', $pass))   $error = 'Password needs a special character.';
    elseif ($pass !== $confirm)                     $error = 'Passwords do not match.';
    else {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')
            ->execute([password_hash($pass, PASSWORD_DEFAULT), $_SESSION['reset_uid']]);
        // Invalidate any "remember me" sessions for this user
        $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$_SESSION['reset_uid']]);
        unset($_SESSION['reset_uid']);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Password reset successfully. Please log in.'];
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Food Dash</title>
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
        <a href="login.php" class="text-sm font-bold text-brand-600 hover:underline">Back to log in</a>
    </div>
</header>

<main class="flex-grow flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-slate-200/80 p-6 sm:p-10">
        <?php if ($step === 1): ?>
            <h1 class="text-2xl font-black text-slate-900">Forgot password?</h1>
            <p class="text-sm text-slate-500 mt-1">Verify your account with the email and mobile number you registered with.</p>

            <?php if ($error): ?><div class="mt-4 p-3 rounded-xl text-sm font-semibold bg-red-50 text-red-800 border border-red-200"><?= e($error) ?></div><?php endif; ?>

            <form method="post" action="forgot_password.php" class="mt-6 space-y-4">
                <input type="hidden" name="action" value="verify">
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 mb-1">Registered email</label>
                    <input id="email" name="email" type="email" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 outline-none text-sm">
                </div>
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 mb-1">Registered mobile number</label>
                    <input id="phone" name="phone" type="tel" maxlength="10" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 outline-none text-sm">
                </div>
                <button class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-lg">Verify my account</button>
            </form>

        <?php else: ?>
            <h1 class="text-2xl font-black text-slate-900">Create a new password</h1>
            <p class="text-sm text-slate-500 mt-1">Identity verified. Choose a strong password you have not used before.</p>

            <?php if ($error): ?><div class="mt-4 p-3 rounded-xl text-sm font-semibold bg-red-50 text-red-800 border border-red-200"><?= e($error) ?></div><?php endif; ?>

            <form method="post" action="forgot_password.php" class="mt-6 space-y-4" id="reset-form">
                <input type="hidden" name="action" value="reset">
                <div>
                    <label for="password" class="block text-xs font-bold text-slate-700 mb-1">New password</label>
                    <div class="relative">
                        <input id="password" name="password" type="password" required class="w-full px-4 py-2.5 pr-20 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 outline-none text-sm">
                        <button type="button" data-toggle="password" class="absolute right-2 top-1.5 px-2.5 py-1 text-xs font-bold text-slate-500 hover:text-brand-600">Show</button>
                    </div>
                </div>
                <div>
                    <label for="confirm_password" class="block text-xs font-bold text-slate-700 mb-1">Confirm new password</label>
                    <div class="relative">
                        <input id="confirm_password" name="confirm_password" type="password" required class="w-full px-4 py-2.5 pr-20 rounded-xl border border-slate-300 focus:ring-2 focus:ring-brand-500 outline-none text-sm">
                        <button type="button" data-toggle="confirm_password" class="absolute right-2 top-1.5 px-2.5 py-1 text-xs font-bold text-slate-500 hover:text-brand-600">Show</button>
                    </div>
                </div>
                <p class="text-[11px] text-slate-500">8+ characters, with upper &amp; lower case, a number and a special character.</p>
                <button class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-lg">Reset password</button>
            </form>
        <?php endif; ?>
    </div>
</main>

<script>
document.querySelectorAll('[data-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.toggle);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Hide' : 'Show';
    });
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
