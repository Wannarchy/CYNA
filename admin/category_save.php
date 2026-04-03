<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/config.php';

$name = trim($_POST['name'] ?? '');
$image_path = trim($_POST['image_path'] ?? '');
$sort_order = (int)($_POST['sort_order'] ?? 1);
$is_active = (int)($_POST['is_active'] ?? 1);

if ($name === '') { header('Location: categories.php'); exit; }

$stmt = $connexion->prepare("INSERT INTO categories (name, image_path, sort_order, is_active) VALUES (?,?,?,?)");
$stmt->execute([$name, $image_path !== '' ? $image_path : 'logo.jpg', $sort_order, $is_active]);

header('Location: categories.php');
exit;