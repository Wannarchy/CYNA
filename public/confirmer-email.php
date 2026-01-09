<?php
require_once '../config/config.php';
require_once '../includes/function.php';

// Vérifier si les paramètres email et token sont présents
if (!isset($_GET['email']) || !isset($_GET['token'])) {
    die("Paramètres invalides.");
}

$email = urldecode($_GET['email']);
$token = $_GET['token'];

try {
    // Préparer une requête pour vérifier le token
    $stmt = $connexion->prepare("
        SELECT id FROM utilisateurs 
        WHERE email = ? AND token_confirmation = ? AND est_confirme = 0
    ");
    $stmt->execute([$email, $token]);

    if ($stmt->rowCount() > 0) {
        // Mettre à jour le compte comme confirmé
        $stmt = $connexion->prepare("
            UPDATE utilisateurs 
            SET est_confirme = 1, token_confirmation = NULL 
            WHERE email = ? AND token_confirmation = ?
        ");
        $stmt->execute([$email, $token]);

        // Message de succès
        $message = "Votre compte a été confirmé avec succès. Vous pouvez maintenant vous connecter.";
        $message_type = 'succes';
    } else {
        // Token invalide ou compte déjà confirmé
        $message = "Le lien de confirmation est invalide ou a déjà été utilisé.";
        $message_type = 'erreur';
    }
} catch (PDOException $e) {
    $message = "Une erreur s'est produite : " . $e->getMessage();
    $message_type = 'erreur';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Confirmation de compte Cyna</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .confirmation-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
            position: relative;
        }

        .confirmation-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
        }

        h2 {
            color: #1a2980;
            margin-bottom: 20px;
        }

        .message {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
        }

        .message.succes {
            background-color: #e8f5e9;
            color: #2E7D32;
        }

        .message.erreur {
            background-color: #ffebee;
            color: #d32f2f;
        }

        .btn-connexion {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
        }

        .btn-connexion:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <h2>Confirmation de compte Cyna</h2>
        <div class="message <?= $message_type ?>">
            <p><?= htmlspecialchars($message) ?></p>
        </div>
        <a href="connexion.php" class="btn-connexion">Se connecter</a>
    </div>
</body>
</html>