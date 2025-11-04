<?php
include '../layout.php';
require '../config/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sku = $_POST['sku'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $category = $_POST['category'];
    $size = $_POST['size'];
    $color = $_POST['color'];
    $price = $_POST['price'];
    $cost = $_POST['cost'];
    $quantity = $_POST['quantity'];
    $min_stock_level = $_POST['min_stock_level'];

    $imagePath = '';
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $target = $uploadDir . '/' . time() . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $imagePath = 'uploads/' . basename($target);
        }
    }

    $stmt = $conn->prepare('INSERT INTO products (sku, brand, model, category, size, color, price, cost, quantity, min_stock_level, image_url) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([$sku,$brand,$model,$category,$size,$color,$price,$cost,$quantity,$min_stock_level,$imagePath]);
    echo "<script>Swal.fire({icon:'success',title:'Product added',timer:1400,showConfirmButton:false}).then(()=>{window.location='list_products.php'})</script>";
    exit();
}
?>
<h2>Add Product</h2>
<div class="container" style="margin-left:240px; padding:20px; background:#393E46; border-radius:8px;"><form method="POST" enctype="multipart/form-data" class="card p-3 shadow-sm">
  <div class="row">
    <div class="col-md-4 mb-2"><label style="color:#000000; text-transform:capitalize;">sku</label>
<input class="form-control" style="margin-bottom:10px;"  name="sku" class="form-control" placeholder="SKU" required></div>
    <div class="col-md-4 mb-2"><label style="color:#000000; text-transform:capitalize;">brand</label>
<input class="form-control" style="margin-bottom:10px;"  name="brand" class="form-control" placeholder="Brand"></div>
    <div class="col-md-4 mb-2"><label style="color:#000000; text-transform:capitalize;">model</label>
<input class="form-control" style="margin-bottom:10px;"  name="model" class="form-control" placeholder="Model"></div>
    <div class="col-md-3 mb-2"><label>Category</label>
<select name="category" required>
<option value="Men">Men</option>
<option value="Women">Women</option>
<option value="Kids">Kids</option>
<option value="Sports">Sports</option>
<option value="Casual">Casual</option>
<option value="Formal">Formal</option>
</select></div>
    <div class="col-md-3 mb-2"><label style="color:#000000; text-transform:capitalize;">size</label>
<input class="form-control" style="margin-bottom:10px;"  name="size" class="form-control" placeholder="Size"></div>
    <div class="col-md-3 mb-2"><label style="color:#000000; text-transform:capitalize;">color</label>
<input class="form-control" style="margin-bottom:10px;"  name="color" class="form-control" placeholder="Color"></div>
    <div class="col-md-3 mb-2"><label style="color:#000000; text-transform:capitalize;">price</label>
<input class="form-control" style="margin-bottom:10px;"  name="price" type="number" step="0.01" class="form-control" placeholder="Price"></div>
    <div class="col-md-3 mb-2"><label style="color:#000000; text-transform:capitalize;">cost</label>
<input class="form-control" style="margin-bottom:10px;"  name="cost" type="number" step="0.01" class="form-control" placeholder="Cost"></div>
    <div class="col-md-3 mb-2"><label>Quantity</label>
<input class="form-control" style="margin-bottom:10px;"  name="quantity" type="number" class="form-control" placeholder="Quantity" value="0"></div>
    <div class="col-md-3 mb-2"><label>Min Stock Level</label>
<input class="form-control" style="margin-bottom:10px;"  name="min_stock_level" type="number" class="form-control" placeholder="Min stock" value="0"></div>
    <div class="col-md-6 mb-2"><input class="form-control" style="margin-bottom:10px;"  name="image" type="file" class="form-control"></div>
  </div>
  <button style="background:#00ADB5; border:none; padding:10px 20px; color:#222831; font-weight:bold;"  class="btn btn-primary mt-2">Add Product</button>
  <a class="btn btn-secondary mt-2" href="list_products.php">Cancel</a>
</form></div>
<?php include '../footer_close.php'; ?>
