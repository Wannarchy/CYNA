<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/config.php';

$title = trim($_POST['title'] ?? '');
$subtitle = trim($_POST['subtitle'] ?? '');
$link_url = trim($_POST['link_url'] ?? '');
$image_path = trim($_POST['image_path'] ?? '');
$sort_order = (int)($_POST['sort_order'] ?? 1);
$is_active = (int)($_POST['is_active'] ?? 1);

if ($title === '') { header('Location: slides.php'); exit; }

$stmt = $connexion->prepare("
  INSERT INTO homepage_slides (title, subtitle, image_path, link_url, sort_order, is_active)
  VALUES (?,?,?,?,?,?)
");
$stmt->execute([
  $title,
  $subtitle !== '' ? $subtitle : null,
  $image_path !== '' ? $image_path : 'logo.jpg',
  $link_url !== '' ? $link_url : null,
  $sort_order,
  $is_active
]);

header('Location: slides.php');
exit;