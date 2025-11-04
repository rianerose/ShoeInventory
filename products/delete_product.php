<?php
include '../layout.php';
require '../config/db.php';
if ($_SESSION['role'] !== 'admin') { echo 'Unauthorized'; exit(); }
$id = $_GET['id'] ?? null;
if ($id) {
    $stmt = $conn->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
}
header('Location: list_products.php');
exit();
