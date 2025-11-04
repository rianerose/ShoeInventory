<?php
require '../config/db.php';
header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename="sales_export.csv"');
$out = fopen('php://output','w');
fputcsv($out, ['id','product_sku','product_name','quantity','unit_price','total_amount','payment_method','customer_name','recorded_by','created_at']);
$rows = $conn->query('SELECT sales.*, users.username as recorder FROM sales LEFT JOIN users ON sales.recorded_by=users.id')->fetchAll();
foreach($rows as $r){
    fputcsv($out, [$r['id'],$r['product_sku'],$r['product_name'],$r['quantity'],$r['unit_price'],$r['total_amount'],$r['payment_method'],$r['customer_name'],$r['recorder'],$r['created_at']]);
}
fclose($out);
exit();
