<?php
// SHARED: one connection for the whole project. If the team's db.php already
// exists, keep theirs but make sure it provides a PDO-returning db().
if (session_status() === PHP_SESSION_NONE) session_start();
const DB_HOST = 'localhost', DB_NAME = 'food_dash', DB_USER = 'root', DB_PASS = '';

function db(): PDO {
    static $pdo;
    return $pdo ??= new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
