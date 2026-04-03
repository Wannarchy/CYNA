<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/config.php';
$id=(int)($_GET['id']??0);
if($id>0){$s=$connexion->prepare("DELETE FROM categories WHERE id=?");$s->execute([$id]);}
header('Location: categories.php');exit;