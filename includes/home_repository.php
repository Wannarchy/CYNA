<?php
// includes/home_repository.php

function home_get_slides(PDO $db): array {
    $sql = "SELECT id, title, subtitle, image_path, link_url, sort_order
            FROM homepage_slides
            WHERE is_active = 1
            ORDER BY sort_order ASC, id ASC";
    return $db->query($sql)->fetchAll();
}

function home_get_text(PDO $db): string {
    $sql = "SELECT content_text FROM homepage_content ORDER BY id ASC LIMIT 1";
    $row = $db->query($sql)->fetch();
    return $row ? (string)$row['content_text'] : "";
}

function home_get_categories(PDO $db): array {
    $sql = "SELECT id, name, image_path, sort_order
            FROM categories
            WHERE is_active = 1
            ORDER BY sort_order ASC, id ASC";
    return $db->query($sql)->fetchAll();
}

function home_get_featured_products(PDO $db, int $limit = 8): array {
    $sql = "SELECT id, name, image_path, price_monthly, price_yearly
            FROM products
            WHERE is_featured = 1
              AND is_available = 1
            ORDER BY featured_order ASC, id ASC
            LIMIT :lim";

    $stmt = $db->prepare($sql);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Retourne une URL utilisable, même si le fichier image n'existe pas.
 * - si vide => placeholder
 * - si chemin relatif => OK
 * - si fichier absent => placeholder
 */
function asset_image(string $path = null): string {
    $placeholder = 'public/assets/img/placeholder.png';

    if (!$path) return $placeholder;

    // Si l'image est déjà une URL (http...), on la garde
    if (preg_match('/^https?:\/\//i', $path)) return $path;

    // Si le fichier existe localement, on le sert
    // Le script est appelé depuis index.php (racine), donc on teste tel quel
    if (file_exists(__DIR__ . '/../' . $path)) return $path;

    return $placeholder;
}