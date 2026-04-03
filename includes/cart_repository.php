<?php
// includes/cart_repository.php

function cart_get_products(PDO $db, array $cart): array {
    if (empty($cart)) return [];

    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $sql = "SELECT id, name, price_monthly, price_yearly, is_available
            FROM products
            WHERE id IN ($placeholders)";

    $stmt = $db->prepare($sql);
    $stmt->execute($ids);
    $products = $stmt->fetchAll();

    // On fusionne données produit + données panier
    $result = [];
    foreach ($products as $p) {
        $pid = (int)$p['id'];
        if (!isset($cart[$pid])) continue;

        $cycle = $cart[$pid]['cycle'] ?? 'monthly';
        $price = ($cycle === 'yearly')
            ? (float)$p['price_yearly']
            : (float)$p['price_monthly'];

        $result[] = [
            'id' => $pid,
            'name' => $p['name'],
            'cycle' => $cycle,
            'price_monthly' => (float)$p['price_monthly'],
            'price_yearly' => (float)$p['price_yearly'],
            'unit_price' => $price,
            'is_available' => (int)$p['is_available'] === 1,
        ];
    }

    return $result;
}

function cart_total(array $items): float {
    $total = 0;
    foreach ($items as $it) {
        if ($it['is_available']) {
            $total += $it['unit_price'];
        }
    }
    return $total;
}