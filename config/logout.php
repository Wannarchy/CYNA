<?php
require_once '../config/config.php';
require_once '../includes/functions.php';

// Démarrer la session
startSession();

// Détruire complètement la session
$_SESSION = array(); // Vider tous les données de session
session_unset(); // Supprimer toutes les variables de session
session_destroy(); // Détruire la session

// Effacer le cookie de session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Rediriger vers la page de connexion
redirectTo('../public/connexion.php');
exit();
?>