<?php
include '../layout.php';
require '../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo "<div class='alert alert-danger'>Purchase not found.</div>";
    include '../footer_close.php';
    exit;
}

$stmt = $conn->prepare('SELECT * FROM purchases WHERE id = ?');
$stmt->execute([$id]);
$purchase = $stmt->fetch();

if (!$purchase) {
    echo "<div class='alert alert-danger'>Purchase not found.</div>";
    include '../footer_close.php';
    exit;
}

$products = $conn->query('SELECT id, sku, model, quantity FROM products ORDER BY model')->fetchAll();
$error = '';
$originalPurchase = $purchase;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newProductId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $newQuantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : (int)$originalPurchase['quantity'];
    $unit_cost = isset($_POST['unit_cost']) ? max(0, round((float)$_POST['unit_cost'], 2)) : (float)$originalPurchase['unit_cost'];
    $supplier_name = trim($_POST['supplier_name'] ?? '');
    $delivery_date = trim($_POST['delivery_date'] ?? '');
    $status = trim($_POST['status'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($newProductId <= 0) {
        $error = 'Please select a product.';
    } else {
        try {
            $conn->beginTransaction();

            $deliveryValue = $delivery_date !== '' ? $delivery_date : null;
            $total_cost = $newQuantity * $unit_cost;

            if ((int)$originalPurchase['product_id'] === $newProductId) {
                $productStmt = $conn->prepare('SELECT id, sku, model, quantity FROM products WHERE id = ? FOR UPDATE');
                $productStmt->execute([$newProductId]);
                $product = $productStmt->fetch();

                if (!$product) {
                    throw new Exception('Selected product not found.');
                }

                $delta = $newQuantity - (int)$originalPurchase['quantity'];
                if ($delta < 0 && $product['quantity'] < abs($delta)) {
                    throw new Exception('Cannot reduce quantity because stock has already been used.');
                }

                $updateStmt = $conn->prepare('UPDATE purchases SET product_id=?, product_sku=?, product_name=?, supplier_name=?, quantity=?, unit_cost=?, total_cost=?, delivery_date=?, status=?, notes=? WHERE id=?');
                $updateStmt->execute([
                    $newProductId,
                    $product['sku'],
                    $product['model'],
                    $supplier_name,
                    $newQuantity,
                    $unit_cost,
                    $total_cost,
                    $deliveryValue,
                    $status,
                    $notes,
                    $id
                ]);

                if ($delta !== 0) {
                    $stockStmt = $conn->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
                    $stockStmt->execute([$delta, $newProductId]);
                }
            } else {
                // Lock old product
                if (!empty($originalPurchase['product_id'])) {
                    $oldProductStmt = $conn->prepare('SELECT id, quantity FROM products WHERE id = ? FOR UPDATE');
                    $oldProductStmt->execute([(int)$originalPurchase['product_id']]);
                    $oldProduct = $oldProductStmt->fetch();
                    if ($oldProduct && $oldProduct['quantity'] < (int)$originalPurchase['quantity']) {
                        throw new Exception('Cannot move this purchase because stock from the original product has already been used.');
                    }
                }

                // Lock new product
                $productStmt = $conn->prepare('SELECT id, sku, model FROM products WHERE id = ? FOR UPDATE');
                $productStmt->execute([$newProductId]);
                $product = $productStmt->fetch();

                if (!$product) {
                    throw new Exception('Selected product not found.');
                }

                // Remove stock from original product
                if (!empty($originalPurchase['product_id'])) {
                    $removeStmt = $conn->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?');
                    $removeStmt->execute([(int)$originalPurchase['quantity'], (int)$originalPurchase['product_id']]);
                }

                // Update purchase data
                $updateStmt = $conn->prepare('UPDATE purchases SET product_id=?, product_sku=?, product_name=?, supplier_name=?, quantity=?, unit_cost=?, total_cost=?, delivery_date=?, status=?, notes=? WHERE id=?');
                $updateStmt->execute([
                    $newProductId,
                    $product['sku'],
                    $product['model'],
                    $supplier_name,
                    $newQuantity,
                    $unit_cost,
                    $total_cost,
                    $deliveryValue,
                    $status,
                    $notes,
                    $id
                ]);

                // Add stock to new product
                $stockStmt = $conn->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
                $stockStmt->execute([$newQuantity, $newProductId]);
            }

            $conn->commit();
            echo "<script>Swal.fire({icon:'success',title:'Purchase updated',timer:1200,showConfirmButton:false}).then(()=>{window.location='list_purchases.php?success=updated'})</script>";
            exit;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = $e->getMessage();
        }
    }

    $purchase['product_id'] = $newProductId;
    $purchase['quantity'] = $newQuantity;
    $purchase['unit_cost'] = $unit_cost;
    $purchase['supplier_name'] = $supplier_name;
    $purchase['delivery_date'] = $delivery_date;
    $purchase['status'] = $status;
    $purchase['notes'] = $notes;
}
?>
<h2>Edit Purchase</h2>
<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="POST" class="card p-3 shadow-sm">
  <div class="row">
    <div class="col-md-4 mb-2">
      <select name="product_id" class="form-select" required>
        <option value="">Choose product</option>
        <?php foreach ($products as $p): ?>
          <option value="<?= $p['id'] ?>" <?= ((int)$purchase['product_id'] === (int)$p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['sku'].' - '.$p['model'].' (Stock: '.$p['quantity'].')') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 mb-2"><input name="quantity" type="number" min="1" value="<?= (int)$purchase['quantity'] ?>" class="form-control" required></div>
    <div class="col-md-2 mb-2"><input name="unit_cost" type="number" step="0.01" min="0" value="<?= htmlspecialchars(number_format((float)$purchase['unit_cost'], 2, '.', '')) ?>" class="form-control" placeholder="Unit cost"></div>
    <div class="col-md-4 mb-2"><input name="supplier_name" class="form-control" value="<?= htmlspecialchars($purchase['supplier_name']) ?>" placeholder="Supplier"></div>
    <div class="col-md-4 mb-2"><input name="delivery_date" type="date" class="form-control" value="<?= $purchase['delivery_date'] ? htmlspecialchars(substr($purchase['delivery_date'], 0, 10)) : '' ?>"></div>
    <div class="col-md-4 mb-2"><input name="status" class="form-control" value="<?= htmlspecialchars($purchase['status']) ?>" placeholder="Status"></div>
    <div class="col-md-12 mb-2"><textarea name="notes" class="form-control" placeholder="Notes"><?= htmlspecialchars($purchase['notes']) ?></textarea></div>
  </div>
  <button class="btn btn-primary">Update Purchase</button>
  <a class="btn btn-secondary" href="list_purchases.php">Cancel</a>
</form>
<?php include '../footer_close.php'; ?>
