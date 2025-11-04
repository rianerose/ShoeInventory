<?php
require '../config/db.php';
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="products_export.csv"');
$out = fopen('php://output','w');
fputcsv($out, ['id','sku','brand','model','category','size','color','price','cost','quantity','min_stock_level','image_url']);
$rows = $conn->query('SELECT * FROM products')->fetchAll();
foreach($rows as $r) fputcsv($out, $r);
fclose($out);
exit();
