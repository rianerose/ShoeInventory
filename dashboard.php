<?php
include 'layout.php';
require 'config/db.php';
$totalProducts = $conn->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalSales = $conn->query('SELECT IFNULL(SUM(total_amount),0) FROM sales')->fetchColumn();
$lowStock = $conn->query('SELECT COUNT(*) FROM products WHERE quantity<=min_stock_level')->fetchColumn();
?>
<h2>Dashboard</h2>
<div class="row mt-3">
  <div class="col-md-4">
    <div class="card p-3 shadow-sm">
      <h6>Total Products</h6>
      <div class="fs-3 fw-bold"><?= $totalProducts ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3 shadow-sm">
      <h6>Total Sales (₱)</h6>
      <div class="fs-3 fw-bold"><?= number_format($totalSales,2) ?></div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card p-3 shadow-sm">
      <h6>Low Stock Items</h6>
      <div class="fs-3 fw-bold"><?= $lowStock ?></div>
    </div>
  </div>
</div>
<?php include 'footer_close.php'; ?>
