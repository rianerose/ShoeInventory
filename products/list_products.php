<?php
include '../layout.php';
require '../config/db.php';

if (!function_exists('resolve_image_src')) {
    function resolve_image_src(?string $path): string {
        if (!$path) {
            return '';
        }
        if (preg_match('#^(https?:)?//#', $path)) {
            return $path;
        }
        if ($path[0] === '/') {
            return $path;
        }
        return '/inventory_system/' . ltrim($path, '/');
    }
}

// Pagination
$limit = 8;
$page = isset($_GET['page']) ? max(1,intval($_GET['page'])) : 1;
$offset = ($page-1)*$limit;

// Filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$where = 'WHERE 1=1';
$params = [];
if ($search !== '') {
    $where .= ' AND (sku LIKE ? OR brand LIKE ? OR model LIKE ? OR category LIKE ?)';
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like; $params[] = $like;
}
if ($category !== '') {
    $where .= ' AND category = ?';
    $params[] = $category;
}

// total count
$countStmt = $conn->prepare("SELECT COUNT(*) FROM products " . $where);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = $limit > 0 ? ceil($total / $limit) : 0;

// fetch page
$sql = "SELECT * FROM products " . $where . " ORDER BY id DESC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($sql);

// bind filter params first (positional)
$paramIndex = 1;
foreach ($params as $p) {
    $stmt->bindValue($paramIndex, $p);
    $paramIndex++;
}
// bind limit & offset as integers
$stmt->bindValue($paramIndex, $limit, PDO::PARAM_INT);
$paramIndex++;
$stmt->bindValue($paramIndex, $offset, PDO::PARAM_INT);

$stmt->execute();
$products = $stmt->fetchAll();
?>
<h2>Products</h2>
<div class="mb-3 d-flex justify-content-between">
  <div>
    <a href="add_product.php" class="btn btn-primary">+ Add Product</a>
    <?php if ($_SESSION['role'] === 'admin'): ?>
      <a href="export_products.php" class="btn btn-secondary">Export CSV</a>
    <?php endif; ?>
  </div>
  <form class="d-flex" method="GET">
    <input name="search" class="form-control me-2" placeholder="Search..." value="<?= htmlspecialchars($search) ?>">
    <input name="category" class="form-control me-2" placeholder="Category" value="<?= htmlspecialchars($category) ?>">
    <button class="btn btn-outline-primary">Filter</button>
  </form>
</div>

<table class="table table-hover bg-white">
<thead class="table-dark">
<tr><th>SKU</th><th>Model</th><th>Brand</th><th>Category</th><th>Qty</th><th>Price</th><th>Image</th><th>Action</th></tr>
</thead>
<tbody>
<?php foreach($products as $p): ?>
<tr class="<?= $p['quantity'] <= $p['min_stock_level'] ? 'table-danger' : '' ?>">
  <td><?= htmlspecialchars($p['sku']) ?></td>
  <td><?= htmlspecialchars($p['model']) ?></td>
  <td><?= htmlspecialchars($p['brand']) ?></td>
  <td><?= htmlspecialchars($p['category']) ?></td>
  <td><?= $p['quantity'] ?></td>
  <td>₱<?= number_format($p['price'],2) ?></td>
  <?php $imageSrc = resolve_image_src($p['image_url']); ?>
  <td><?php if($imageSrc): ?><img src="<?= htmlspecialchars($imageSrc) ?>" class="table-img"><?php endif; ?></td>
  <td>
    <a class="btn btn-sm btn-warning" href="edit_product.php?id=<?= $p['id'] ?>">Edit</a>
    <?php if($_SESSION['role']==='admin'): ?>
      <button class="btn btn-sm btn-danger" onclick="confirmDelete(<?= $p['id'] ?>)">Delete</button>
    <?php endif; ?>
  </td>
</tr>
<?php endforeach; ?>
</tbody>
</table>

<nav>
<ul class="pagination">
<?php for($i=1;$i<=$totalPages;$i++): ?>
<li class="page-item <?= $i==$page ? 'active' : '' ?>"><a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>"><?= $i ?></a></li>
<?php endfor; ?>
</ul>
</nav>

<script>
function confirmDelete(id){
  Swal.fire({
    title: 'Delete product?',
    text: 'This action cannot be undone.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Yes, delete',
  }).then((res)=>{
    if(res.isConfirmed){
      window.location = 'delete_product.php?id=' + id;
    }
  });
}
</script>
<?php include '../footer_close.php'; ?>
