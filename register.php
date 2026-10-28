<?php
/* ==========================================================
   Food Dash - CREATE ACCOUNT (Module 1)
   Registration, regex input validation, password strength,
   duplicate email / phone detection, show-hide password.
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

const ALLOWED_IMAGES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
const AVATAR_DIR = __DIR__ . '/uploads/avatars';

// Stores an uploaded photo under a random name and returns that name
function saveAvatar(array $file): string {
    $mime = getimagesize($file['tmp_name'])['mime'];
    if (!is_dir(AVATAR_DIR)) { mkdir(AVATAR_DIR, 0755, true); }
    $name = bin2hex(random_bytes(12)) . '.' . ALLOWED_IMAGES[$mime];
    move_uploaded_file($file['tmp_name'], AVATAR_DIR . '/' . $name);
    return $name;
}

if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name']  = trim($_POST['name'] ?? '');
    $old['email'] = strtolower(trim($_POST['email'] ?? ''));
    $old['phone'] = preg_replace('/\s+/', '', $_POST['phone'] ?? '');
    $pass    = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    // ---- Input validation with regular expressions ----
    if (!preg_match("/^[A-Za-z][A-Za-z .'\-]{1,99}$/", $old['name'])) {
        $errors['name'] = 'Name must be 2-100 letters (spaces, . \' - allowed).';
    }
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || !preg_match('/^[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}$/', $old['email'])) {
        $errors['email'] = 'Enter a valid email address, e.g. name@example.com.';
    }
    if (!preg_match('/^[6-9][0-9]{9}$/', $old['phone'])) {
        $errors['phone'] = 'Enter a valid 10-digit Indian mobile number (starts with 6-9).';
    }
    // ---- Password strength ----
    if (strlen($pass) < 8)                 $errors['password'] = 'Password must be at least 8 characters.';
    elseif (!preg_match('/[A-Z]/', $pass)) $errors['password'] = 'Password needs at least one uppercase letter.';
    elseif (!preg_match('/[a-z]/', $pass)) $errors['password'] = 'Password needs at least one lowercase letter.';
    elseif (!preg_match('/[0-9]/', $pass)) $errors['password'] = 'Password needs at least one number.';
    elseif (!preg_match('/[^A-Za-z0-9]/', $pass)) $errors['password'] = 'Password needs at least one special character.';
    if ($pass !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    // ---- Profile photo (required) ----
    $upload = $_FILES['avatar'] ?? null;
    if (!$upload || $upload['error'] !== UPLOAD_ERR_OK) {
        $errors['avatar'] = 'Please upload a profile photo.';
    } elseif ($upload['size'] > 2 * 1024 * 1024) {
        $errors['avatar'] = 'Photo must be 2 MB or smaller.';
    } elseif (!($info = @getimagesize($upload['tmp_name'])) || !isset(ALLOWED_IMAGES[$info['mime']])) {
        $errors['avatar'] = 'Only JPG, PNG or WebP images are allowed.';
    }

    // ---- Duplicate account detection ----
    if (!$errors) {
        $dup = $pdo->prepare('SELECT email, phone FROM users WHERE email = ? OR phone = ? LIMIT 1');
        $dup->execute([$old['email'], $old['phone']]);
        if ($row = $dup->fetch()) {
            if ($row['email'] === $old['email']) $errors['email'] = 'An account with this email already exists.';
            else                                 $errors['phone'] = 'An account with this phone number already exists.';
        }
    }

    if (!$errors) {
        $avatar = saveAvatar($upload);
        $ins = $pdo->prepare('INSERT INTO users (name, email, phone, password_hash, avatar) VALUES (?, ?, ?, ?, ?)');
        $ins->execute([$old['name'], $old['email'], $old['phone'], password_hash($pass, PASSWORD_DEFAULT), $avatar]);
        $_SESSION['flash'] = ['type' => 'success', 'msg' => 'Account created! Please log in.'];
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
    <title>Create Account - Food Dash</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { brand: { 50:'#fff7ed', 100:'#ffedd5', 500:'#f97316', 600:'#ea580c', 700:'#c2410c' } } } } };
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .strength-bar { height: 6px; border-radius: 9999px; transition: width .3s, background-color .3s; }
    </style>
</head>
<body class="min-h-screen flex flex-col text-slate-800">

<header class="bg-white border-b border-slate-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 h-16 flex items-center justify-between">
        <a href="index.php" class="flex items-center gap-2 font-black text-xl text-slate-900">
            <span class="w-9 h-9 rounded-xl bg-gradient-to-tr from-brand-600 to-amber-500 text-white flex items-center justify-center">🍔</span> Food Dash
        </a>
        <a href="login.php" class="text-sm font-bold text-brand-600 hover:underline">Already have an account? Log in</a>
    </div>
</header>

<main class="flex-grow flex items-center justify-center px-4 py-10">
    <div class="w-full max-w-xl bg-white rounded-3xl shadow-xl border border-slate-200/80 p-6 sm:p-10">
        <h1 class="text-2xl font-black text-slate-900">Create your account</h1>
        <p class="text-sm text-slate-500 mt-1">Order from hundreds of restaurants in your city.</p>

        <form method="post" action="register.php" novalidate class="mt-6 space-y-4" id="register-form" enctype="multipart/form-data">
            <div>
                <label class="block text-xs font-bold text-slate-700 mb-1">Profile photo *</label>
                <div class="flex items-center gap-4 p-3 rounded-xl border <?= isset($errors['avatar']) ? 'border-red-400' : 'border-slate-300' ?>">
                    <div id="avatar-preview" class="w-16 h-16 rounded-2xl bg-slate-100 flex items-center justify-center text-2xl overflow-hidden flex-shrink-0">📷</div>
                    <div class="flex-1 min-w-0">
                        <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp" required
                               class="block w-full text-xs text-slate-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-orange-50 file:text-orange-700 file:font-bold">
                        <p class="text-[11px] text-slate-500 mt-1">JPG, PNG or WebP, up to 2 MB. Shown on your profile.</p>
                    </div>
                </div>
                <?php if (isset($errors['avatar'])): ?><p class="text-xs text-red-600 mt-1"><?= e($errors['avatar']) ?></p><?php endif; ?>
            </div>
            <div>
                <label for="name" class="block text-xs font-bold text-slate-700 mb-1">Full name *</label>
                <input id="name" name="name" type="text" value="<?= e($old['name']) ?>" required
                       class="w-full px-4 py-2.5 rounded-xl border <?= isset($errors['name']) ? 'border-red-400' : 'border-slate-300' ?> focus:ring-2 focus:ring-brand-500 outline-none text-sm">
                <?php if (isset($errors['name'])): ?><p class="text-xs text-red-600 mt-1"><?= e($errors['name']) ?></p><?php endif; ?>
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-700 mb-1">Email *</label>
                    <input id="email" name="email" type="email" value="<?= e($old['email']) ?>" required
                           class="w-full px-4 py-2.5 rounded-xl border <?= isset($errors['email']) ? 'border-red-400' : 'border-slate-300' ?> focus:ring-2 focus:ring-brand-500 outline-none text-sm">
                    <?php if (isset($errors['email'])): ?><p class="text-xs text-red-600 mt-1"><?= e($errors['email']) ?></p><?php endif; ?>
                </div>
                <div>
                    <label for="phone" class="block text-xs font-bold text-slate-700 mb-1">Mobile number *</label>
                    <input id="phone" name="phone" type="tel" value="<?= e($old['phone']) ?>" maxlength="10" required
                           class="w-full px-4 py-2.5 rounded-xl border <?= isset($errors['phone']) ? 'border-red-400' : 'border-slate-300' ?> focus:ring-2 focus:ring-brand-500 outline-none text-sm">
                    <?php if (isset($errors['phone'])): ?><p class="text-xs text-red-600 mt-1"><?= e($errors['phone']) ?></p><?php endif; ?>
                </div>
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-slate-700 mb-1">Password *</label>
                <div class="relative">
                    <input id="password" name="password" type="password" required
                           class="w-full px-4 py-2.5 pr-20 rounded-xl border <?= isset($errors['password']) ? 'border-red-400' : 'border-slate-300' ?> focus:ring-2 focus:ring-brand-500 outline-none text-sm">
                    <button type="button" data-toggle="password" class="absolute right-2 top-1.5 px-2.5 py-1 text-xs font-bold text-slate-500 hover:text-brand-600">Show</button>
                </div>
                <div class="mt-2 h-1.5 bg-slate-100 rounded-full overflow-hidden"><div id="strength-bar" class="strength-bar w-0 bg-red-500"></div></div>
                <p id="strength-text" class="text-[11px] text-slate-500 mt-1">Use 8+ characters with upper & lower case, a number and a symbol.</p>
                <?php if (isset($errors['password'])): ?><p class="text-xs text-red-600 mt-1"><?= e($errors['password']) ?></p><?php endif; ?>
            </div>

            <div>
                <label for="confirm_password" class="block text-xs font-bold text-slate-700 mb-1">Confirm password *</label>
                <div class="relative">
                    <input id="confirm_password" name="confirm_password" type="password" required
                           class="w-full px-4 py-2.5 pr-20 rounded-xl border <?= isset($errors['confirm_password']) ? 'border-red-400' : 'border-slate-300' ?> focus:ring-2 focus:ring-brand-500 outline-none text-sm">
                    <button type="button" data-toggle="confirm_password" class="absolute right-2 top-1.5 px-2.5 py-1 text-xs font-bold text-slate-500 hover:text-brand-600">Show</button>
                </div>
                <?php if (isset($errors['confirm_password'])): ?><p class="text-xs text-red-600 mt-1"><?= e($errors['confirm_password']) ?></p><?php endif; ?>
            </div>

            <button type="submit" class="w-full py-3 rounded-xl bg-brand-600 hover:bg-brand-700 text-white font-bold text-sm shadow-lg transition">Create account</button>
        </form>
    </div>
</main>

<script>
// Show / hide password toggles
document.querySelectorAll('[data-toggle]').forEach(btn => {
    btn.addEventListener('click', () => {
        const input = document.getElementById(btn.dataset.toggle);
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.textContent = show ? 'Hide' : 'Show';
    });
});

// Live password-strength meter (same rules as the server)
const pwd = document.getElementById('password');
pwd.addEventListener('input', () => {
    const v = pwd.value;
    const checks = [v.length >= 8, /[A-Z]/.test(v), /[a-z]/.test(v), /[0-9]/.test(v), /[^A-Za-z0-9]/.test(v)];
    const score = checks.filter(Boolean).length;
    const bar = document.getElementById('strength-bar');
    const text = document.getElementById('strength-text');
    const levels = [
        ['0%', 'bg-red-500', 'Too weak'], ['20%', 'bg-red-500', 'Weak'], ['40%', 'bg-orange-500', 'Fair'],
        ['60%', 'bg-amber-500', 'Good'], ['80%', 'bg-lime-500', 'Strong'], ['100%', 'bg-emerald-600', 'Very strong']
    ];
    const [width, color, label] = levels[score];
    bar.style.width = v ? width : '0%';
    bar.className = 'strength-bar ' + color;
    text.textContent = v ? 'Password strength: ' + label : 'Use 8+ characters with upper & lower case, a number and a symbol.';
});

// Profile photo preview (same 2 MB limit as the server)
document.getElementById('avatar').addEventListener('change', ev => {
    const file = ev.target.files[0];
    if (!file) return;
    if (file.size > 2 * 1024 * 1024) { alert('Photo must be 2 MB or smaller.'); ev.target.value = ''; return; }
    document.getElementById('avatar-preview').innerHTML = `<img src="${URL.createObjectURL(file)}" alt="Your photo preview" class="w-full h-full object-cover">`;
});

// Client-side validation before submit (mirrors server rules)
document.getElementById('register-form').addEventListener('submit', (ev) => {
    const phone = document.getElementById('phone').value.replace(/\s+/g, '');
    if (!/^[6-9][0-9]{9}$/.test(phone)) { ev.preventDefault(); alert('Enter a valid 10-digit mobile number.'); return; }
    if (pwd.value !== document.getElementById('confirm_password').value) { ev.preventDefault(); alert('Passwords do not match.'); }
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
