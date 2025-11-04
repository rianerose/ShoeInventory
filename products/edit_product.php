<?php
include '../layout.php';
require '../config/db.php';
$id = $_GET['id'] ?? null;
if (!$id) { header('Location: list_products.php'); exit(); }
$stmt = $conn->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();
if (!$product) { echo 'Not found'; exit(); }

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
    $imagePath = $product['image_url'];

    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . '/../uploads';
        if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
        $target = $uploadDir . '/' . time() . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            $imagePath = 'uploads/' . basename($target);
        }
    }

    $sql = 'UPDATE products SET sku=?, brand=?, model=?, category=?, size=?, color=?, price=?, cost=?, quantity=?, min_stock_level=?, image_url=? WHERE id=?';
    $conn->prepare($sql)->execute([$sku,$brand,$model,$category,$size,$color,$price,$cost,$quantity,$min_stock_level,$imagePath,$id]);
    echo "<script>Swal.fire({icon:'success',title:'Saved',timer:1200,showConfirmButton:false}).then(()=>{window.location='list_products.php'})</script>";
    exit();
}
?>
<h2>Edit Product</h2>
<div class="container" style="margin-left:240px; padding:20px; background:#393E46; border-radius:8px;"><form method="POST" enctype="multipart/form-data" class="card p-3 shadow-sm">
  <div class="row">
    <div class="col-md-4 mb-2"><label style="color:#000000; text-transform:capitalize;">sku</label>
<input class="form-control" style="margin-bottom:10px;"  name="sku" class="form-control" value="<?= htmlspecialchars($product['sku']) ?>"></div>
    <div class="col-md-4 mb-2"><label style="color:#000000; text-transform:capitalize;">brand</label>
<input class="form-control" style="margin-bottom:10px;"  name="brand" class="form-control" value="<?= htmlspecialchars($product['brand']) ?>"></div>
    <div class="col-md-4 mb-2"><label style="color:#000000; text-transform:capitalize;">model</label>
<input class="form-control" style="margin-bottom:10px;"  name="model" class="form-control" value="<?= htmlspecialchars($product['model']) ?>"></div>
    <div class="col-md-3 mb-2"><input class="form-control" style="margin-bottom:10px;"  name="category" class="form-control" value="<?= htmlspecialchars($product['category']) ?>"></div>
    <div class="col-md-3 mb-2"><label style="color:#000000; text-transform:capitalize;">size</label>
<input class="form-control" style="margin-bottom:10px;"  name="size" class="form-control" value="<?= htmlspecialchars($product['size']) ?>"></div>
    <div class="col-md-3 mb-2"><label style="color:#000000; text-transform:capitalize;">color</label>
<input class="form-control" style="margin-bottom:10px;"  name="color" class="form-control" value="<?= htmlspecialchars($product['color']) ?>"></div>
    <div class="col-md-3 mb-2"><label style="color:#000000; text-transform:capitalize;">price</label>
<input class="form-control" style="margin-bottom:10px;"  name="price" type="number" step="0.01" class="form-control" value="<?= $product['price'] ?>"></div>
    <div class="col-md-3 mb-2"><label style="color:#000000; text-transform:capitalize;">cost</label>
<input class="form-control" style="margin-bottom:10px;"  name="cost" type="number" step="0.01" class="form-control" value="<?= $product['cost'] ?>"></div>
    <div class="col-md-3 mb-2"><input class="form-control" style="margin-bottom:10px;"  name="quantity" type="number" class="form-control" value="<?= $product['quantity'] ?>"></div>
    <div class="col-md-3 mb-2"><input class="form-control" style="margin-bottom:10px;"  name="min_stock_level" type="number" class="form-control" value="<?= $product['min_stock_level'] ?>"></div>
    <div class="col-md-6 mb-2">
      <input class="form-control" style="margin-bottom:10px;"  name="image" type="file" class="form-control">
      <?php if($product['image_url']): ?><img src="<?= htmlspecialchars($product['image_url']) ?>" class="table-img mt-2"><?php endif; ?>
    </div>
  </div>
  <button style="background:#00ADB5; border:none; padding:10px 20px; color:#222831; font-weight:bold;"  class="btn btn-primary mt-2">Save</button>
  <a class="btn btn-secondary mt-2" href="list_products.php">Cancel</a>
</form></div>
<?php include '../footer_close.php'; ?>
