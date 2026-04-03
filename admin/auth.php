<?php
// admin/auth.php
session_start();

require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: login.php');
    exit;
}

// Vérifie le rôle admin en DB
$stmt = $connexion->prepare("SELECT is_admin FROM utilisateurs WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['utilisateur_id']]);
$row = $stmt->fetch();

if (!$row || (int)$row['is_admin'] !== 1) {
    http_response_code(403);
    die("Accès refusé (admin uniquement).");
}