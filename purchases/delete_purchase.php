<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

require '../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: list_purchases.php?error=' . urlencode('Invalid purchase selected.'));
    exit;
}

$stmt = $conn->prepare('SELECT * FROM purchases WHERE id = ?');
$stmt->execute([$id]);
$purchase = $stmt->fetch();

if (!$purchase) {
    header('Location: list_purchases.php?error=' . urlencode('Purchase not found.'));
    exit;
}

try {
    $conn->beginTransaction();

    if (!empty($purchase['product_id'])) {
        $lockStmt = $conn->prepare('SELECT quantity FROM products WHERE id = ? FOR UPDATE');
        $lockStmt->execute([(int)$purchase['product_id']]);
        $product = $lockStmt->fetch();

        if ($product && $product['quantity'] < (int)$purchase['quantity']) {
            throw new Exception('Cannot delete this purchase because stock has already been used.');
        }

        if ($product) {
            $updateStmt = $conn->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?');
            $updateStmt->execute([(int)$purchase['quantity'], (int)$purchase['product_id']]);
        }
    }

    $deleteStmt = $conn->prepare('DELETE FROM purchases WHERE id = ?');
    $deleteStmt->execute([$id]);

    $conn->commit();
    header('Location: list_purchases.php?success=deleted');
    exit;
} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    header('Location: list_purchases.php?error=' . urlencode($e->getMessage()));
    exit;
}
