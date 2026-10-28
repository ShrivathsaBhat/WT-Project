<?php
/* ==========================================================
   Food Dash - LOGOUT (Module 1)
   Ends the session, removes the remember-me token from the
   database and clears the remember-me cookie.
   ========================================================== */
session_start();
try {
    $pdo = new PDO('mysql:host=localhost;dbname=food_dash;charset=utf8mb4', 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    if (!empty($_COOKIE['fd_remember'])) {
        $parts = explode(':', $_COOKIE['fd_remember'], 2);
        $pdo->prepare('DELETE FROM remember_tokens WHERE selector = ?')->execute([$parts[0]]);
    }
} catch (PDOException $ex) {
    // Logging out must still work even if the database is unreachable
}

setcookie('fd_remember', '', time() - 3600, '/');
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
session_start();
$_SESSION['flash'] = ['type' => 'success', 'msg' => 'You have been logged out. See you soon!'];
header('Location: login.php');
exit;
