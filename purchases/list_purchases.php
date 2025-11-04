<?php
include '../layout.php';
require '../config/db.php';
$stmt = $conn->query('SELECT * FROM purchases ORDER BY id DESC');
$rows = $stmt->fetchAll();
?>
<h2>Purchases</h2>
<div class="mb-3">
  <a class="btn btn-secondary" href="export_purchases.php">Export CSV</a>
  <a class="btn btn-success" href="record_purchase.php">+ Add Purchase</a>
</div>
<table class="table table-striped bg-white">
<thead class="table-dark"><tr><th>Product</th><th>Supplier</th><th>Qty</th><th>Total</th><th>Delivery</th><th>Status</th><th>Date</th></tr></thead>
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
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php include '../footer_close.php'; ?>
