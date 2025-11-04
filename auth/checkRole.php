<?php
// include this at top of pages that must be admin-only
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../unauthorized.php');
    exit();
}
