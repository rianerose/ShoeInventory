<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /inventory_system/auth/login.php');
    exit();
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Inventory System</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
body { background:#EEEEEE; color:#EEEEEE; }
.sidebar { background:#222831; color:#EEEEEE; width:220px; position:fixed; top:0; left:0; height:100%; padding:20px; }
.sidebar a {
    color:#fff;
    display:block;
    padding:8px 10px;
    text-decoration:none;
    border-radius:4px;
    margin-bottom:6px;
}
.sidebar a:hover { background:#495057; }
.content {
    margin-left:240px;
    padding:20px;
}
.table-img {
    width:48px;
    height:48px;
    object-fit:cover;
    border-radius:6px;
}
</style>

</head>
<body>

<div class="sidebar">
  <h4>Shoe Inventory</h4>
  <small class="text-white d-block mb-3">Hello, <?= htmlspecialchars($_SESSION['username']) ?></small>

  <a href="/inventory_system/dashboard.php">Dashboard</a>
  <a href="/inventory_system/products/list_products.php">Products</a>
  <a href="/inventory_system/sales/list_sales.php">Sales</a>
  <a href="/inventory_system/purchases/list_purchases.php">Purchases</a>
    <a href="/inventory_system/reports/reports.php">Reports</a>
  <a href="/inventory_system/auth/logout.php" class="text-danger">Logout</a>
</div>

<div class="content">
