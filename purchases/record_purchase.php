<?php
include '../layout.php';
require '../config/db.php';
$products = $conn->query('SELECT id, sku, model FROM products')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $quantity = intval($_POST['quantity']);
    $unit_cost = floatval($_POST['unit_cost']);
    $supplier_name = $_POST['supplier_name'];
    $delivery_date = $_POST['delivery_date'];
    $status = $_POST['status'];
    $notes = $_POST['notes'];

    $pstmt = $conn->prepare('SELECT * FROM products WHERE id = ?');
    $pstmt->execute([$product_id]);
    $prod = $pstmt->fetch();
    if (!$prod) { echo 'Invalid product'; exit(); }

    $total_cost = $quantity * $unit_cost;
    $stmt = $conn->prepare('INSERT INTO purchases (product_id, product_sku, product_name, supplier_name, quantity, unit_cost, total_cost, delivery_date, status, notes) VALUES (?,?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$product_id, $prod['sku'], $prod['model'], $supplier_name, $quantity, $unit_cost, $total_cost, $delivery_date, $status, $notes]);

    $conn->prepare('UPDATE products SET quantity = quantity + ? WHERE id = ?')->execute([$quantity, $product_id]);

    echo "<script>Swal.fire({icon:'success',title:'Purchase recorded',timer:1200,showConfirmButton:false}).then(()=>{window.location='list_purchases.php'})</script>";
    exit();
}
?>
<h2>Record Purchase</h2>
<form method="POST" class="card p-3 shadow-sm">
  <div class="row">
    <div class="col-md-4 mb-2">
      <select name="product_id" class="form-select" required>
        <option value="">Choose product</option>
        <?php foreach($products as $p): ?>
          <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['sku'].' - '.$p['model']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 mb-2"><input name="quantity" type="number" min="1" value="1" class="form-control" required></div>
    <div class="col-md-2 mb-2"><input name="unit_cost" type="number" step="0.01" class="form-control" placeholder="Unit cost"></div>
    <div class="col-md-4 mb-2"><input name="supplier_name" class="form-control" placeholder="Supplier"></div>
    <div class="col-md-4 mb-2"><input name="delivery_date" type="date" class="form-control"></div>
    <div class="col-md-4 mb-2"><input name="status" class="form-control" placeholder="Status (e.g. delivered)"></div>
    <div class="col-md-12 mb-2"><textarea name="notes" class="form-control" placeholder="Notes"></textarea></div>
  </div>
  <button class="btn btn-success">Save Purchase</button>
  <a class="btn btn-secondary" href="list_purchases.php">Cancel</a>
</form>
<?php include '../footer_close.php'; ?>
