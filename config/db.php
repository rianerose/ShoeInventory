<?php
// Simple PDO MySQL connection - update with your credentials
$host = '127.0.0.1';
$db   = 'inventory_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    echo 'DB Connection failed: ' . $e->getMessage();
    exit;
}
