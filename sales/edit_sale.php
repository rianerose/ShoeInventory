<?php
include '../layout.php';
require '../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    echo "<div class='alert alert-danger'>Sale not found.</div>";
    include '../footer_close.php';
    exit;
}

$stmt = $conn->prepare('SELECT * FROM sales WHERE id = ?');
$stmt->execute([$id]);
$sale = $stmt->fetch();

if (!$sale) {
    echo "<div class='alert alert-danger'>Sale not found.</div>";
    include '../footer_close.php';
    exit;
}

$products = $conn->query('SELECT id, sku, model, price, quantity FROM products ORDER BY model')->fetchAll();
$error = '';
$originalSale = $sale;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newProductId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $newQuantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : (int)$originalSale['quantity'];
    $payment_method = trim($_POST['payment_method'] ?? '');
    $customer_name = trim($_POST['customer_name'] ?? '');
    $notes = trim($_POST['notes'] ?? '');

    if ($newProductId <= 0) {
        $error = 'Please select a product.';
    } else {
        try {
            $conn->beginTransaction();

            // Lock selected product
            $productStmt = $conn->prepare('SELECT id, sku, model, price, quantity FROM products WHERE id = ? FOR UPDATE');
            $productStmt->execute([$newProductId]);
            $product = $productStmt->fetch();

            if (!$product) {
                throw new Exception('Selected product not found.');
            }

            if ((int)$originalSale['product_id'] === $newProductId) {
                $delta = $newQuantity - (int)$originalSale['quantity'];
                if ($delta > 0 && $product['quantity'] < $delta) {
                    throw new Exception('Not enough stock available for the additional quantity requested.');
                }

                $unit_price = $originalSale['unit_price'] !== null ? (float)$originalSale['unit_price'] : (float)$product['price'];
                $total_amount = $unit_price * $newQuantity;

                $updateStmt = $conn->prepare('UPDATE sales SET product_id=?, product_sku=?, product_name=?, quantity=?, unit_price=?, total_amount=?, payment_method=?, customer_name=?, notes=? WHERE id=?');
                $updateStmt->execute([
                    $newProductId,
                    $product['sku'],
                    $product['model'],
                    $newQuantity,
                    $unit_price,
                    $total_amount,
                    $payment_method,
                    $customer_name,
                    $notes,
                    $id
                ]);

                if ($delta !== 0) {
                    $stockStmt = $conn->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?');
                    $stockStmt->execute([$delta, $newProductId]);
                }
            } else {
                // Lock old product and return stock
                if (!empty($originalSale['product_id'])) {
                    $oldProductStmt = $conn->prepare('SELECT id FROM products WHERE id = ? FOR UPDATE');
                    $oldProductStmt->execute([(int)$originalSale['product_id']]);
                }

                // Return old sale quantity to previous product
                if (!empty($originalSale['product_id'])) {
                    $restoreStmt = $conn->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?');
                    $restoreStmt->execute([(int)$originalSale['quantity'], (int)$originalSale['product_id']]);
                }

                if ($product['quantity'] < $newQuantity) {
                    throw new Exception('Not enough stock available for the selected product.');
                }

                $unit_price = $product['price'] !== null ? (float)$product['price'] : (float)$originalSale['unit_price'];
                $total_amount = $unit_price * $newQuantity;

                $updateStmt = $conn->prepare('UPDATE sales SET product_id=?, product_sku=?, product_name=?, quantity=?, unit_price=?, total_amount=?, payment_method=?, customer_name=?, notes=? WHERE id=?');
                $updateStmt->execute([
                    $newProductId,
                    $product['sku'],
                    $product['model'],
                    $newQuantity,
                    $unit_price,
                    $total_amount,
                    $payment_method,
                    $customer_name,
                    $notes,
                    $id
                ]);

                $stockStmt = $conn->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?');
                $stockStmt->execute([$newQuantity, $newProductId]);
            }

            $conn->commit();
            echo "<script>Swal.fire({icon:'success',title:'Sale updated',timer:1200,showConfirmButton:false}).then(()=>{window.location='list_sales.php?success=updated'})</script>";
            exit;
        } catch (Exception $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = $e->getMessage();
        }
    }

    $sale['product_id'] = $newProductId;
    $sale['quantity'] = $newQuantity;
    $sale['payment_method'] = $payment_method;
    $sale['customer_name'] = $customer_name;
    $sale['notes'] = $notes;
}
?>
<h2>Edit Sale</h2>
<?php if ($error): ?>
  <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>
<form method="POST" class="card p-3 shadow-sm">
  <div class="row">
    <div class="col-md-4 mb-2">
      <select name="product_id" class="form-select" required>
        <option value="">Choose product</option>
        <?php foreach ($products as $p): ?>
          <option value="<?= $p['id'] ?>" <?= ((int)$sale['product_id'] === (int)$p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['sku'].' - '.$p['model'].' (Stock: '.$p['quantity'].')') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 mb-2"><input name="quantity" type="number" min="1" value="<?= (int)$sale['quantity'] ?>" class="form-control" required></div>
    <div class="col-md-3 mb-2"><input name="payment_method" class="form-control" placeholder="Payment method" value="<?= htmlspecialchars($sale['payment_method']) ?>"></div>
    <div class="col-md-3 mb-2"><input name="customer_name" class="form-control" placeholder="Customer (optional)" value="<?= htmlspecialchars($sale['customer_name']) ?>"></div>
    <div class="col-md-12 mb-2"><textarea name="notes" class="form-control" placeholder="Notes"><?= htmlspecialchars($sale['notes']) ?></textarea></div>
  </div>
  <button class="btn btn-primary">Update Sale</button>
  <a class="btn btn-secondary" href="list_sales.php">Cancel</a>
</form>
<?php include '../footer_close.php'; ?>
