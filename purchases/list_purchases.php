<?php
include '../layout.php';
require '../config/db.php';
$stmt = $conn->query('SELECT * FROM purchases ORDER BY id DESC');
$rows = $stmt->fetchAll();
$successMessage = isset($_GET['success']) ? $_GET['success'] : '';
$errorMessage = isset($_GET['error']) ? $_GET['error'] : '';
?>
<h2>Purchases</h2>
<div class="mb-3">
  <a class="btn btn-secondary" href="export_purchases.php">Export CSV</a>
  <a class="btn btn-success" href="record_purchase.php">+ Add Purchase</a>
</div>
<?php if ($successMessage): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $successMessage))) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<?php if ($errorMessage): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?= htmlspecialchars($errorMessage) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  </div>
<?php endif; ?>
<table class="table table-striped bg-white">
<thead class="table-dark"><tr><th>Product</th><th>Supplier</th><th>Qty</th><th>Total</th><th>Delivery</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
  <td><?= htmlspecialchars($r['product_name']) ?></td>
  <td><?= htmlspecialchars($r['supplier_name']) ?></td>
  <td><?= $r['quantity'] ?></td>
  <td>₱<?= number_format($r['total_cost'],2) ?></td>
  <td><?= $r['delivery_date'] ?></td>
  <td><?= htmlspecialchars($r['status']) ?></td>
  <td><?= $r['created_at'] ?></td>
  <td>
    <a class="btn btn-sm btn-primary" href="edit_purchase.php?id=<?= $r['id'] ?>">Edit</a>
    <a class="btn btn-sm btn-danger" href="delete_purchase.php?id=<?= $r['id'] ?>" onclick="return confirm('Delete this purchase? This will reduce product stock.');">Delete</a>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php include '../footer_close.php'; ?>
