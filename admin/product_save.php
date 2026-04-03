<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/config.php';

$name = trim($_POST['name'] ?? '');
$category_id = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
$image_path = trim($_POST['image_path'] ?? '');
$price_monthly = (float)($_POST['price_monthly'] ?? 0);
$price_yearly  = (float)($_POST['price_yearly'] ?? 0);
$is_available = (int)($_POST['is_available'] ?? 1);
$is_featured = (int)($_POST['is_featured'] ?? 0);
$featured_order = (int)($_POST['featured_order'] ?? 999);

if ($name === '') { header('Location: products.php'); exit; }

$stmt = $connexion->prepare("
  INSERT INTO products (category_id, name, image_path, price_monthly, price_yearly, is_available, is_featured, featured_order)
  VALUES (?,?,?,?,?,?,?,?)
");
$stmt->execute([
  $category_id,
  $name,
  $image_path !== '' ? $image_path : 'logo.jpg',
  $price_monthly,
  $price_yearly,
  $is_available,
  $is_featured,
  $featured_order
]);

header('Location: products.php');
exit;