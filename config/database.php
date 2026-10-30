<?php
// Update these values if your local MySQL configuration differs.
$DB_HOST = '127.0.0.1';
$DB_PORT = '3307';
$DB_NAME = 'food_dash';
$DB_USER = 'root';
$DB_PASS = '';

function db(bool $withDatabase = true): PDO {
    global $DB_HOST, $DB_PORT, $DB_NAME, $DB_USER, $DB_PASS;
    $dsn = 'mysql:host=' . $DB_HOST . ';port=' . $DB_PORT . ($withDatabase ? ';dbname=' . $DB_NAME : '') . ';charset=utf8mb4';
    return new PDO($dsn, $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}
