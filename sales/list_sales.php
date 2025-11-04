<?php
include '../layout.php';
require '../config/db.php';
$stmt = $conn->query('SELECT sales.*, users.username FROM sales LEFT JOIN users ON sales.recorded_by = users.id ORDER BY sales.id DESC');
$sales = $stmt->fetchAll();
?>
<h2>Sales</h2>
<div class="mb-3">
  <a class="btn btn-secondary" href="export_sales.php">Export CSV</a>
  <a class="btn btn-success" href="record_sale.php">+ Add Sale</a>
</div>
<table class="table table-striped bg-white">
<thead class="table-dark"><tr><th>Product</th><th>Qty</th><th>Total</th><th>Payment</th><th>Customer</th><th>By</th><th>Date</th></tr></thead>
<tbody>
<?php foreach($sales as $s): ?>
<tr>
  <td><?= htmlspecialchars($s['product_name']) ?></td>
  <td><?= $s['quantity'] ?></td>
  <td>₱<?= number_format($s['total_amount'],2) ?></td>
  <td><?= htmlspecialchars($s['payment_method']) ?></td>
  <td><?= htmlspecialchars($s['customer_name']) ?></td>
  <td><?= htmlspecialchars($s['username'] ?? '—') ?></td>
  <td><?= $s['created_at'] ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php include '../footer_close.php'; ?>
