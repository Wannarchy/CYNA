<?php
session_start();

require_once __DIR__ . '/../config/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panier.php');
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
if ($product_id <= 0) {
    header('Location: panier.php');
    exit;
}

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// SaaS → quantité fixe 1
$_SESSION['cart'][$product_id] = [
    'product_id' => $product_id,
    'qty' => 1,
    'cycle' => 'monthly' // défaut
];

header('Location: panier.php');
exit;