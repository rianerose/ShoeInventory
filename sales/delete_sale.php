<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../index.php');
    exit;
}

require '../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: list_sales.php?error=' . urlencode('Invalid sale selected.'));
    exit;
}

$stmt = $conn->prepare('SELECT * FROM sales WHERE id = ?');
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    header('Location: list_sales.php?error=' . urlencode('Sale not found.'));
    exit;
}

try {
    $conn->beginTransaction();

    if (!empty($sale['product_id'])) {
        $stockStmt = $conn->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
        $stockStmt->execute([(int)$sale['quantity'], (int)$sale['product_id']]);
    }

    $deleteStmt = $conn->prepare('DELETE FROM sales WHERE id = ?');
    $deleteStmt->execute([$id]);

    $conn->commit();
    header('Location: list_sales.php?success=deleted');
    exit;
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    header('Location: list_sales.php?error=' . urlencode('Failed to delete sale.'));
    exit;
}
