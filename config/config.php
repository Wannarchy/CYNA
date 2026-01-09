<?php
// Paramètres de connexion à la base de données
define('DB_HOST', 'localhost');     // Hôte de la base de données
define('DB_NAME', 'cyna');       // Nom de la base de données
define('DB_USER', 'root');           // Utilisateur MySQL
define('DB_PASS', 'Zani1966');       // Mot de passe MySQL

try {
    // Création de la connexion PDO
    $connexion = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4", 
        DB_USER, 
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch(PDOException $e) {
    // Gestion des erreurs de connexion
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Une erreur s'est produite. Veuillez réessayer plus tard.");
}