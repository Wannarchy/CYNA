<?php
// includes/catalog_repository.php

require_once __DIR__ . '/home_repository.php'; // pour asset_image()

function cat_get_by_id(PDO $db, int $category_id): ?array {
    $sql = "SELECT id, name, image_path
            FROM categories
            WHERE id = :id AND is_active = 1
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $category_id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Tri conforme CDC :
 * - d'abord les produits disponibles (is_available=1)
 * - ensuite indisponibles (is_available=0)
 * - puis par 'is_featured' / 'featured_order' (si tu veux pousser certains)
 * - sinon par date (plus récent d'abord)
 */
function products_get_by_category(PDO $db, int $category_id): array {
    $sql = "SELECT id, name, image_path, price_monthly, price_yearly, is_available
            FROM products
            WHERE category_id = :cat
            ORDER BY
              is_available DESC,
              is_featured DESC,
              featured_order ASC,
              created_at DESC,
              id DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([':cat' => $category_id]);
    return $stmt->fetchAll();
}

/**
 * Si aucun category_id => on affiche toutes les catégories (page d’accès au catalogue)
 * et une sélection globale des produits (optionnel).
 */
function categories_get_all(PDO $db): array {
    $sql = "SELECT id, name, image_path
            FROM categories
            WHERE is_active = 1
            ORDER BY sort_order ASC, id ASC";
    return $db->query($sql)->fetchAll();
}