<?php
require '../config/db.php';
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="purchases_export.csv"');
$out = fopen('php://output','w');
fputcsv($out, ['id','product_sku','product_name','supplier_name','quantity','unit_cost','total_cost','delivery_date','status','created_at']);
$rows = $conn->query('SELECT * FROM purchases')->fetchAll();
foreach($rows as $r) fputcsv($out, [$r['id'],$r['product_sku'],$r['product_name'],$r['supplier_name'],$r['quantity'],$r['unit_cost'],$r['total_cost'],$r['delivery_date'],$r['status'],$r['created_at']]);
fclose($out);
exit();
