<?php
include '../layout.php';
require '../config/db.php';
$products = $conn->query('SELECT id, sku, model, price, quantity FROM products')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $quantity = intval($_POST['quantity']);
    $payment_method = $_POST['payment_method'];
    $customer_name = $_POST['customer_name'];
    $notes = $_POST['notes'];

    $pstmt = $conn->prepare('SELECT * FROM products WHERE id = ?');
    $pstmt->execute([$product_id]);
    $prod = $pstmt->fetch();
    if (!$prod) { echo 'Invalid product'; exit(); }
    if ($quantity > $prod['quantity']) { echo '<div class="alert alert-danger">Not enough stock</div>'; exit(); }

    $unit_price = $prod['price'];
    $total = $unit_price * $quantity;

    $stmt = $conn->prepare('INSERT INTO sales (product_id, product_sku, product_name, quantity, unit_price, total_amount, payment_method, customer_name, notes, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$product_id, $prod['sku'], $prod['model'], $quantity, $unit_price, $total, $payment_method, $customer_name, $notes, $_SESSION['user_id']]);

    $conn->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ?')->execute([$quantity, $product_id]);

    echo "<script>Swal.fire({icon:'success',title:'Sale recorded',timer:1200,showConfirmButton:false}).then(()=>{window.location='list_sales.php'})</script>";
    exit();
}
?>
<h2>Record Sale</h2>
<form method="POST" class="card p-3 shadow-sm">
  <div class="row">
    <div class="col-md-4 mb-2">
      <select name="product_id" class="form-select" required>
        <option value="">Choose product</option>
        <?php foreach($products as $p): ?>
          <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['sku'].' - '.$p['model'].' (Stock: '.$p['quantity'].')') ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 mb-2"><input name="quantity" type="number" min="1" value="1" class="form-control" required></div>
    <div class="col-md-3 mb-2"><input name="payment_method" class="form-control" placeholder="Payment method"></div>
    <div class="col-md-3 mb-2"><input name="customer_name" class="form-control" placeholder="Customer (optional)"></div>
    <div class="col-md-12 mb-2"><textarea name="notes" class="form-control" placeholder="Notes"></textarea></div>
  </div>
  <button class="btn btn-success">Save Sale</button>
  <a class="btn btn-secondary" href="list_sales.php">Cancel</a>
</form>
<?php include '../footer_close.php'; ?>
