<?php
// Shared helpers for Food Dash (Module 1). Every page includes this file first.

date_default_timezone_set('Asia/Kolkata');

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['lifetime' => 0, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

require_once __DIR__ . '/../config/db.php';

define('SESSION_TIMEOUT', 1800);   // 30 minutes of inactivity
define('REMEMBER_DAYS', 30);
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCK_SECONDS', 300);

const PASSWORD_RULE_TEXT = 'Use 8-64 characters with uppercase and lowercase letters, a number and a special symbol.';

/* ---------- Output, navigation and messages ---------- */

function e($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function base_url(): string {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    return $scheme . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
}

function flash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function get_flash(): array {
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function initials(string $name): string {
    $out = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $part) {
        $out .= strtoupper(substr($part, 0, 1));
    }
    return $out ?: 'U';
}

function first_name(string $name): string {
    return explode(' ', trim($name))[0];
}

function now_sql(): string {
    return date('Y-m-d H:i:s');
}

function time_ago(string $timestamp): string {
    $diff = time() - strtotime($timestamp);
    if ($diff < 60)    return 'just now';
    if ($diff < 3600)  return floor($diff / 60) . ' min ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hr ago';
    return floor($diff / 86400) . ' day(s) ago';
}

/* ---------- CSRF protection ---------- */

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        die('Invalid or expired form. Go back, refresh the page and try again.');
    }
}

/* ---------- Validation rules (regular expressions) ---------- */

function valid_name(string $v): bool {
    return (bool)preg_match("/^[A-Za-z][A-Za-z .'-]{1,49}$/", $v);
}

function valid_email(string $v): bool {
    return strlen($v) <= 100 && filter_var($v, FILTER_VALIDATE_EMAIL) !== false;
}

function valid_phone(string $v): bool {
    return (bool)preg_match('/^[6-9][0-9]{9}$/', $v);
}

function valid_place(string $v): bool {
    return (bool)preg_match("/^[A-Za-z][A-Za-z .'-]{1,49}$/", $v);
}

function valid_pincode(string $v): bool {
    return (bool)preg_match('/^[1-9][0-9]{5}$/', $v);
}

function valid_line(string $v): bool {
    $n = strlen($v);
    return $n >= 3 && $n <= 100;
}

// Shown as the live checklist on password fields (matches the design)
function password_rules(string $pw): array {
    return [
        '8+ characters'                   => strlen($pw) >= 8,
        'Uppercase and lowercase letters' => (bool)preg_match('/[a-z]/', $pw) && (bool)preg_match('/[A-Z]/', $pw),
        'Number (0-9)'                    => (bool)preg_match('/\d/', $pw),
        'Special symbol (!@#$)'           => (bool)preg_match('/[^A-Za-z0-9]/', $pw),
    ];
}

function valid_password(string $pw): bool {
    return strlen($pw) <= 64 && !in_array(false, password_rules($pw), true);
}

/* ---------- User lookups ---------- */

function find_user(int $id): ?array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE user_id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

// Returns ['email' => true, 'phone' => true] for values used by another account.
function duplicate_fields(string $email, string $phone, int $excludeId = 0): array {
    global $pdo;
    $stmt = $pdo->prepare('SELECT email, phone FROM users WHERE (email = ? OR phone = ?) AND user_id <> ?');
    $stmt->execute([$email, $phone, $excludeId]);
    $found = [];
    foreach ($stmt->fetchAll() as $row) {
        if ($row['email'] === $email) $found['email'] = true;
        if ($row['phone'] === $phone) $found['phone'] = true;
    }
    return $found;
}

/* ---------- Form markup helpers ---------- */

function field_class(array $errors, string $key): string {
    return 'field' . (isset($errors[$key]) ? ' has-error' : '');
}

function field_error(array $errors, string $key): string {
    return '<p class="error">' . e($errors[$key] ?? '') . '</p>';
}

// $attrs is trusted HTML written by us (validation hooks, required, placeholder, etc.)
function input_field(string $name, string $label, string $value, array $errors, string $type = 'text', string $attrs = ''): string {
    return '<div class="' . field_class($errors, $name) . '">'
         . '<label for="' . e($name) . '">' . e($label) . '</label>'
         . '<input type="' . e($type) . '" id="' . e($name) . '" name="' . e($name) . '" value="' . e($value) . '" ' . $attrs . '>'
         . field_error($errors, $name) . '</div>';
}

// opts: strength (bool), autocomplete (string), extra (raw HTML beside label), match (id of field to match)
function password_field(string $name, string $label, string $id, string $error = '', array $opts = []): string {
    $strength = !empty($opts['strength']);
    $auto     = $opts['autocomplete'] ?? ($strength ? 'new-password' : 'current-password');
    $extra    = $opts['extra'] ?? '';
    $match    = isset($opts['match']) ? ' data-match="' . e($opts['match']) . '"' : '';

    $h  = '<div class="' . ($error !== '' ? 'field has-error' : 'field') . '" data-level="0">';
    $h .= '<div class="label-row"><label for="' . e($id) . '">' . e($label) . '</label>' . $extra . '</div>';
    $h .= '<div class="pw-wrap"><input type="password" id="' . e($id) . '" name="' . e($name) . '" autocomplete="' . e($auto) . '"'
        . ($strength ? ' data-validate="password" data-meter' : '') . $match . ' required>'
        . '<button type="button" class="toggle-pw" data-target="' . e($id) . '">Show</button></div>';
    if ($strength) {
        $h .= '<div class="meter"><span class="bar"></span></div>'
            . '<div class="meter-label"><span>Password Strength</span><span class="meter-text">Too Short</span></div>'
            . '<ul class="rules">';
        foreach (array_keys(password_rules('')) as $rule) {
            $h .= '<li>' . e($rule) . '</li>';
        }
        $h .= '</ul>';
    }
    $h .= '<p class="error">' . e($error) . '</p></div>';
    return $h;
}

/* ---------- Sessions (tracked in user_sessions) ---------- */

function client_device(): string {
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $browser = 'Browser';
    foreach (['Edg' => 'Edge', 'OPR' => 'Opera', 'Chrome' => 'Chrome', 'Firefox' => 'Firefox', 'Safari' => 'Safari'] as $needle => $name) {
        if (stripos($ua, $needle) !== false) { $browser = $name; break; }
    }
    $os = 'Unknown OS';
    foreach (['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iOS', 'iPad' => 'iPadOS', 'Mac OS' => 'macOS', 'Linux' => 'Linux'] as $needle => $name) {
        if (stripos($ua, $needle) !== false) { $os = $name; break; }
    }
    return $browser . ' on ' . $os;
}

function login_user(array $user): void {
    global $pdo;
    session_regenerate_id(true);                // prevents session fixation
    $_SESSION['user_id']       = (int)$user['user_id'];
    $_SESSION['user_name']     = $user['name'];
    $_SESSION['last_activity'] = time();
    $now = now_sql();
    $pdo->prepare('INSERT INTO user_sessions (session_id, user_id, device, ip, created_at, last_seen) VALUES (?, ?, ?, ?, ?, ?)')
        ->execute([session_id(), $_SESSION['user_id'], client_device(), $_SERVER['REMOTE_ADDR'] ?? '', $now, $now]);
}

function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function logout_user(): void {
    global $pdo;
    if (is_logged_in()) {
        $pdo->prepare('DELETE FROM user_sessions WHERE session_id = ?')->execute([session_id()]);
    }
    clear_remember_cookie($pdo);
    $_SESSION = [];
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    session_destroy();
}

// Removes every remember-me login for the account (used when devices are signed out).
function revoke_remember_tokens(PDO $pdo, int $userId): void {
    $pdo->prepare('DELETE FROM remember_tokens WHERE user_id = ?')->execute([$userId]);
}

// Call at the top of every page that needs a signed-in user. Returns the user row.
function require_login(): array {
    global $pdo;
    check_remember_cookie();

    if (is_logged_in()) {
        if (time() - ($_SESSION['last_activity'] ?? 0) > SESSION_TIMEOUT) {
            logout_user();
            session_start();
            flash('info', 'You were signed out after 30 minutes of inactivity.');
            redirect('login.php');
        }
        $stmt = $pdo->prepare('SELECT session_id FROM user_sessions WHERE session_id = ? AND user_id = ?');
        $stmt->execute([session_id(), $_SESSION['user_id']]);
        if ($stmt->fetch()) {
            $user = find_user((int)$_SESSION['user_id']);
            if ($user) {
                $_SESSION['last_activity'] = time();
                $pdo->prepare('UPDATE user_sessions SET last_seen = ? WHERE session_id = ?')->execute([now_sql(), session_id()]);
                return $user;
            }
        }
        // This device was signed out elsewhere, or the account was deleted
        $_SESSION = [];
        session_destroy();
        session_start();
        flash('info', 'This device was signed out. Please log in again.');
        redirect('login.php');
    }
    flash('error', 'Please log in to open that page.');
    redirect('login.php');
}

// Call on login, register and password pages.
function require_guest(): void {
    check_remember_cookie();
    if (is_logged_in()) redirect('dashboard.php');
}

/* ---------- Remember-me cookie ---------- */

function cookie_options(int $expires): array {
    return ['expires' => $expires, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax'];
}

function set_remember_cookie(int $userId): void {
    global $pdo;
    $selector  = bin2hex(random_bytes(9));      // 18 characters
    $validator = bin2hex(random_bytes(32));
    $pdo->prepare('INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at)
                   VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL ' . REMEMBER_DAYS . ' DAY))')
        ->execute([$userId, $selector, hash('sha256', $validator)]);
    setcookie('remember', $selector . ':' . $validator, cookie_options(time() + REMEMBER_DAYS * 86400));
}

function check_remember_cookie(): void {
    global $pdo;
    if (is_logged_in() || empty($_COOKIE['remember'])) return;

    $parts = explode(':', $_COOKIE['remember']);
    if (count($parts) === 2) {
        [$selector, $validator] = $parts;
        $stmt = $pdo->prepare('SELECT * FROM remember_tokens WHERE selector = ? AND expires_at > NOW()');
        $stmt->execute([$selector]);
        $token = $stmt->fetch();
        if ($token && hash_equals($token['validator_hash'], hash('sha256', $validator))) {
            $user = find_user((int)$token['user_id']);
            if ($user) {
                $pdo->prepare('DELETE FROM remember_tokens WHERE id = ?')->execute([$token['id']]);
                login_user($user);
                set_remember_cookie((int)$user['user_id']);   // rotate the token
                return;
            }
        }
    }
    setcookie('remember', '', cookie_options(time() - 3600));  // invalid cookie: remove it
}

function clear_remember_cookie(PDO $pdo): void {
    if (!empty($_COOKIE['remember'])) {
        $selector = explode(':', $_COOKIE['remember'])[0];
        $pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$selector]);
        setcookie('remember', '', cookie_options(time() - 3600));
    }
}
