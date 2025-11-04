<?php
session_start();
require "../config/db.php";

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        header('Location: ../dashboard.php');
        exit();
    } else {
        $error = 'Invalid credentials';
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Login - Inventory</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex align-items-center" style="height:100vh; background:#f8f9fa;">
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="card p-4 shadow-sm">
        <h4 class="mb-3">Sign in</h4>
        <?php if($error): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST">
          <div class="mb-2">
            <input name="username" class="form-control" placeholder="Username" required>
          </div>
          <div class="mb-3">
            <input name="password" type="password" class="form-control" placeholder="Password" required>
          </div>
          <button class="btn btn-primary w-100">Login</button>
        </form>
        <p class="mt-3 text-muted small">Use your seeded user or create one in the DB.</p>
      </div>
    </div>
  </div>
</div>
</body>
</html>
