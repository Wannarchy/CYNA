<?php
session_start();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/cart_repository.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$items = cart_get_products($connexion, $cart);
$total = cart_total($items);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || count($items) === 0) {
    header('Location: panier.php');
    exit;
}

$billing_name = trim($_POST['billing_name'] ?? '');
$billing_address = trim($_POST['billing_address'] ?? '');

if ($billing_name === '' || $billing_address === '') {
    header('Location: checkout.php');
    exit;
}

$connexion->beginTransaction();

// commande
$stmt = $connexion->prepare(
    "INSERT INTO orders (user_id, total, billing_name, billing_address)
     VALUES (?, ?, ?, ?)"
);
$stmt->execute([
    $_SESSION['utilisateur_id'],
    $total,
    $billing_name,
    $billing_address
]);

$order_id = $connexion->lastInsertId();

// items
$stmtItem = $connexion->prepare(
    "INSERT INTO order_items (order_id, product_id, cycle, price)
     VALUES (?, ?, ?, ?)"
);

foreach ($items as $it) {
    if (!$it['is_available']) continue;

    $stmtItem->execute([
        $order_id,
        $it['id'],
        $it['cycle'],
        $it['unit_price']
    ]);
}

$connexion->commit();

// vider panier
unset($_SESSION['cart']);

header("Location: confirmation.php?order_id=$order_id");
exit;