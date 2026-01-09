<?php
require_once '../vendor/autoload.php';
require_once '../config/config.php';
require_once '../includes/function.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
// Initialisation des variables
$erreurs = [];
$succes = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Nettoyage et validation de l'email
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $erreurs[] = "Adresse email invalide.";
    }

    // Si pas d'erreurs, on vérifie l'existence de l'email
    if (empty($erreurs)) {
        try {
            // Vérifier si l'email existe
            $stmt = $connexion->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                // Générer un token de réinitialisation unique
                $token_reinitialisation = bin2hex(random_bytes(32));
                $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

                // Stocker le token et son expiration en base de données
                $stmt = $connexion->prepare("
                    UPDATE utilisateurs 
                    SET token_reinitialisation = ?, 
                        expiration_token = ? 
                    WHERE email = ?
                ");
                $stmt->execute([$token_reinitialisation, $expiration, $email]);

                // Préparer le lien de réinitialisation
                $lien_reinitialisation = "localhost/Cyna/public/reinitialiser_mot_de_passe.php?email=" . 
                    urlencode($email) . "&token=" . $token_reinitialisation;

                // Envoi de l'email
                $mail = new PHPMailer(true);
                
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'noreplycyna@gmail.com';
                $mail->Password   = 'uaws jfaf jqal cahx';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;

                $mail->setFrom('noreplycyna@gmail.com', 'Cyna Sécurité');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Réinitialisation de votre mot de passe';
                $mail->Body    = "
                    <html>
                    <body>
                        <h2>Réinitialisation de mot de passe</h2>
                        <p>Vous avez demandé une réinitialisation de mot de passe.</p>
                        <p>Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe (valable 1 heure) :</p>
                        <p><a href='$lien_reinitialisation'>Réinitialiser mon mot de passe</a></p>
                        <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
                    </body>
                    </html>
                ";

                $mail->send();
                $succes = "Un email de réinitialisation a été envoyé à votre adresse.";
            } else {
                $erreurs[] = "Aucun compte associé à cet email.";
            }
        } catch (Exception $e) {
            $erreurs[] = "Erreur : " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - Cyna</title>
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

        .mot-de-passe-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }

        .mot-de-passe-container h2 {
            text-align: center;
            color: #1a2980;
            margin-bottom: 30px;
        }

        .form-groupe {
            margin-bottom: 20px;
        }

        .form-groupe label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .form-groupe input {
            width: 100%;
            padding: 12px;
            border: 1px solid #d1d8e0;
            border-radius: 5px;
            font-size: 16px;
        }

        .btn-reinitialisation {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            text-transform: uppercase;
        }

        .erreurs {
            background-color: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .succes {
            background-color: #e8f5e9;
            color: #2E7D32;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .liens-supplementaires {
            text-align: center;
            margin-top: 20px;
        }

        .liens-supplementaires a {
            color: #1a2980;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="mot-de-passe-container">
        <h2>Mot de passe oublié</h2>
        
        <?php if (!empty($erreurs)): ?>
            <div class="erreurs">
                <?php foreach ($erreurs as $erreur): ?>
                    <p><?= htmlspecialchars($erreur) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
            <div class="succes">
                <p><?= htmlspecialchars($succes) ?></p>
            </div>
        <?php endif; ?>

        <?php if (empty($succes)): ?>
            <form method="post" action="">
                <div class="form-groupe">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required 
                           value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
                </div>
                <button type="submit" class="btn-reinitialisation">Réinitialiser le mot de passe</button>
            </form>
        <?php endif; ?>

        <div class="liens-supplementaires">
            <a href="connexion.php">Retour à la connexion</a>
        </div>
    </div>
</body>
</html>