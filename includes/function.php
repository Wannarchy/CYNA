<?php
// Fonction de démarrage de session
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
}

// Fonction de destruction de session
function destroySession() {
    session_unset();
    session_destroy();
}

// Fonction de redirection
function redirectTo($page) {
    header("Location: $page");
    exit();
}

// Fonction de génération de token CSRF
function generateCSRFToken() {
    if (session_status() == PHP_SESSION_NONE) {
        startSession();
    }
    
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return $_SESSION['csrf_token'];
}

// Fonction de vérification de token CSRF
function verifyCSRFToken($token) {
    if (session_status() == PHP_SESSION_NONE) {
        startSession();
    }
    
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        error_log("Tentative d'attaque CSRF détectée");
        die("Erreur de sécurité : Token CSRF invalide");
    }
    
    // Régénérer le token après validation pour plus de sécurité
    unset($_SESSION['csrf_token']);
    return true;
}

// Fonction de journalisation des événements
function logEvent($message, $type = 'info') {
    $logFile = '../logs/' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$type}] {$message}" . PHP_EOL;
    
    // Assurez-vous que le dossier logs existe
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// Fonction de nettoyage des inputs
function sanitizeInput($input) {
    $input = trim($input);
    $input = strip_tags($input);
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    return $input;
}

// Fonction d'envoi d'email sécurisé
function sendSecureEmail($to, $subject, $message, $from = 'noreply@votre-site.com') {
    $headers = [
        'From' => $from,
        'Reply-To' => $from,
        'X-Mailer' => 'PHP/' . phpversion(),
        'Content-Type' => 'text/plain; charset=UTF-8'
    ];
    
    // Ajout de headers de sécurité
    $headers['MIME-Version'] = '1.0';
    
    return mail($to, $subject, $message, $headers);
}