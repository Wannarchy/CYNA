<?php
// includes/product_repository.php

require_once __DIR__ . '/home_repository.php'; // asset_image()

function product_get_by_id(PDO $db, int $id): ?array {
    $sql = "SELECT p.*, c.name AS category_name
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.id = :id
            LIMIT 1";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function product_get_similar(PDO $db, ?int $category_id, int $exclude_id, int $limit = 6): array {
    // Priorité aux disponibles, puis un peu d'aléatoire pour varier.
    if ($category_id) {
        $sql = "SELECT id, name, image_path, price_monthly, price_yearly, is_available
                FROM products
                WHERE category_id = :cat
                  AND id <> :exclude
                ORDER BY is_available DESC, RAND()
                LIMIT :lim";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':cat', $category_id, PDO::PARAM_INT);
        $stmt->bindValue(':exclude', $exclude_id, PDO::PARAM_INT);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Si pas de catégorie, on prend global
    $sql = "SELECT id, name, image_path, price_monthly, price_yearly, is_available
            FROM products
            WHERE id <> :exclude
            ORDER BY is_available DESC, RAND()
            LIMIT :lim";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':exclude', $exclude_id, PDO::PARAM_INT);
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Fallback description (car tu n'as pas de champ description dans products)
 */
function product_desc_fallback(string $name): string {
    return "Solution SaaS CYNA : " . $name . ". Déploiement rapide, supervision en temps réel, alertes et conformité renforcée.";
}

/**
 * Fallback caractéristiques techniques (simple mais crédible)
 */
function product_specs_fallback(): array {
    return [
        "Supervision et alertes en temps réel",
        "Tableaux de bord & reporting",
        "Conformité & journalisation",
        "Support et SLA selon l’offre",
        "Déploiement rapide (SaaS)",
    ];
}