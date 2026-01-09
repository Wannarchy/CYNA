<?php
require_once '../config/config.php';
require_once '../includes/function.php';

// Démarrer la session
startSession();

// Détruire complètement la session
function deconnexion() {
    // Vider tous les données de session
    $_SESSION = array(); 

    // Supprimer toutes les variables de session
    session_unset(); 

    // Détruire la session
    session_destroy(); 

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
}

// Appeler la fonction de déconnexion
deconnexion();
?>