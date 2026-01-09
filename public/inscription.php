<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../config/config.php';
require_once '../includes/function.php';

// Chemin absolu et explicite
require_once 'C:\wamp64\www\Cyna\vendor\autoload.php';

// Ajoutez un test de débogage
if (!file_exists('C:\wamp64\www\Cyna\vendor\autoload.php')) {
    die('Autoload file not found. Please run composer install.');
}

// Initialisation des variables
$erreurs = [];
$succes = '';

// Fonction d'envoi d'email avec PHPMailer
function envoyerEmailConfirmation($email, $token) {
    $mail = new PHPMailer(true);

    try {
        // Configuration SMTP (Gmail comme exemple)
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreplycyna@gmail.com';
        $mail->Password   = 'uaws jfaf jqal cahx'; // Votre mot de passe d'application
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Paramètres d'envoi
        $mail->setFrom('noreply@cyna.com', 'Cyna Sécurité');
        $mail->addAddress($email);
        $mail->isHTML(true);
        
        // Lien de confirmation
        $lien_confirmation = "http://localhost/Cyna/public/confirmer-email.php?email=" . urlencode($email) . "&token=" . $token_confirmation;
        // Sujet et corps de l'email
        $mail->Subject = 'Confirmez votre compte Cyna';
        $mail->Body    = "
            <html>
            <body>
                <h2>Confirmation de votre compte Cyna</h2>
                <p>Bonjour,</p>
                <p>Cliquez sur le lien ci-dessous pour confirmer votre compte :</p>
                <p><a href='$lien_confirmation'>Confirmer mon compte</a></p>
                <p>Si vous n'avez pas créé de compte, ignorez cet email.</p>
                <p>Cordialement,<br>L'équipe Cyna</p>
            </body>
            </html>
        ";

        $mail->send();
        return true;
    } catch (Exception $e) {
        // Log de l'erreur
        error_log("Erreur d'envoi d'email : " . $mail->ErrorInfo);
        return false;
    }
}

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Nettoyage et validation des données
    $prenom = trim($_POST['prenom']);
    $nom = trim($_POST['nom']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $mot_de_passe = $_POST['mot_de_passe'];
    $confirmation_mot_de_passe = $_POST['confirmation_mot_de_passe'];

    // Validation des champs
    if (empty($prenom)) {
        $erreurs[] = "Le prénom est requis.";
    }

    if (empty($nom)) {
        $erreurs[] = "Le nom est requis.";
    }

    if (!$email) {
        $erreurs[] = "L'adresse email est invalide.";
    }

    // Validation du mot de passe
    if (strlen($mot_de_passe) < 8) {
        $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }

    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $mot_de_passe)) {
        $erreurs[] = "Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.";
    }

    if ($mot_de_passe !== $confirmation_mot_de_passe) {
        $erreurs[] = "Les mots de passe ne correspondent pas.";
    }

    // Vérification si l'email existe déjà
    $stmt = $connexion->prepare("SELECT id FROM utilisateurs WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->rowCount() > 0) {
        $erreurs[] = "Cette adresse email est déjà utilisée.";
    }

    // Si pas d'erreurs, on procède à l'inscription
    if (empty($erreurs)) {
        // Génération du token de confirmation
        $token_confirmation = bin2hex(random_bytes(32));

        // Hashage du mot de passe
        $mot_de_passe_hash = password_hash($mot_de_passe, PASSWORD_ARGON2ID);

        try {
            // Préparation de la requête d'insertion
            $stmt = $connexion->prepare("
                INSERT INTO utilisateurs 
                (prenom, nom, email, mot_de_passe, token_confirmation) 
                VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $prenom, 
                $nom, 
                $email, 
                $mot_de_passe_hash, 
                $token_confirmation
            ]);

            // Envoi de l'email de confirmation
            if (envoyerEmailConfirmation($email, $token_confirmation)) {
                $succes = "Votre compte a été créé. Veuillez vérifier votre email pour le confirmer.";
            } else {
                $erreurs[] = "Impossible d'envoyer l'email de confirmation.";
            }
        } catch (PDOException $e) {
            $erreurs[] = "Erreur lors de l'inscription : " . $e->getMessage();
        }
    }
}
?>

<!-- Le reste du code HTML reste inchangé -->

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription Cyna - Sécurisez votre avenir</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        /* Styles pour le formulaire d'inscription Cyna */
        body {
            font-family: 'Roboto', 'Arial', sans-serif;
            background-color: #f4f7f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
        }

        .inscription-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
        }

        .inscription-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
        }

        h2 {
            text-align: center;
            color: #1a2980;
            margin-bottom: 30px;
            font-weight: 300;
        }

        .form-groupe {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #d1d8e0;
            border-radius: 5px;
            transition: all 0.3s ease;
            font-size: 16px;
        }

        input:focus {
            border-color: #1a2980;
            box-shadow: 0 0 5px rgba(26, 41, 128, 0.2);
            outline: none;
        }

        .btn-inscription {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn-inscription:hover {
            opacity: 0.9;
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .erreurs {
            background-color: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .succes {
            background-color: #e8f5e9;
            color: #2E7D32;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        @keyframes shield-pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .inscription-container:hover {
            animation: shield-pulse 2s infinite;
        }

        /* Responsive */
        @media screen and (max-width: 600px) {
            .inscription-container {
                margin: 20px;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="inscription-container">
        <h2>Inscription Cyna</h2>
        
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

        <form method="post" action="">
            <div class="form-groupe">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" required value="<?= isset($prenom) ? htmlspecialchars($prenom) : '' ?>">
            </div>
            <div class="form-groupe">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" required value="<?= isset($nom) ? htmlspecialchars($nom) : '' ?>">
            </div>
            <div class="form-groupe">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
            </div>
            <div class="form-groupe">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <div class="form-groupe">
                <label for="confirmation_mot_de_passe">Confirmation du mot de passe</label>
                <input type="password" id="confirmation_mot_de_passe" name="confirmation_mot_de_passe" required>
            </div>
            <button type="submit" class="btn-inscription">S'inscrire</button>
        </form>
    </div>
</body>
</html>