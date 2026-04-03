<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/config.php';
$id=(int)($_GET['id']??0);
if($id>0){
  $s=$connexion->prepare("DELETE FROM order_items WHERE product_id=?");$s->execute([$id]);
  $s=$connexion->prepare("DELETE FROM products WHERE id=?");$s->execute([$id]);
}
header('Location: products.php');exit;