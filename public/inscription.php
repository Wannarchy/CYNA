<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once '../config/config.php';
require_once '../includes/function.php';
require_once '../vendor/autoload.php';

// Initialisation des variables
$erreurs = [];
$succes  = '';

// ─── Fonction d'envoi d'email de confirmation ───────────────────────────────
function envoyerEmailConfirmation($email, $token) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'noreplycyna@gmail.com';
        $mail->Password   = 'uaws jfaf jqal cahx';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('noreply@cyna.com', 'Cyna Sécurité');
        $mail->addAddress($email);
        $mail->isHTML(true);

        // ✅ CORRECTION : utiliser $token (le paramètre reçu), pas $token_confirmation
        $lien_confirmation = "http://localhost/Cyna/public/confirmer-email.php"
            . "?email=" . urlencode($email)
            . "&token=" . $token;

        $mail->Subject = 'Confirmez votre compte Cyna';
        $mail->Body    = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <div style='max-width:500px; margin:auto; padding:30px; border:1px solid #ddd; border-radius:8px;'>
                    <h2 style='color:#1a2980;'>Confirmation de votre compte Cyna</h2>
                    <p>Bonjour,</p>
                    <p>Merci de vous être inscrit. Cliquez sur le bouton ci-dessous pour confirmer votre adresse email :</p>
                    <p style='text-align:center; margin:30px 0;'>
                        <a href='$lien_confirmation'
                           style='background:linear-gradient(to right,#1a2980,#26d0ce);
                                  color:white; padding:12px 24px; text-decoration:none;
                                  border-radius:5px; font-size:16px;'>
                            Confirmer mon compte
                        </a>
                    </p>
                    <p style='color:#888; font-size:13px;'>
                        Si vous n'avez pas créé de compte sur Cyna, ignorez cet email.
                    </p>
                    <p>Cordialement,<br><strong>L'équipe Cyna</strong></p>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = "Confirmez votre compte Cyna en visitant ce lien : $lien_confirmation";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur envoi email confirmation : " . $mail->ErrorInfo);
        return false;
    }
}

// ─── Traitement du formulaire ────────────────────────────────────────────────
startSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom                    = trim($_POST['prenom'] ?? '');
    $nom                       = trim($_POST['nom'] ?? '');
    $email                     = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $mot_de_passe              = $_POST['mot_de_passe'] ?? '';
    $confirmation_mot_de_passe = $_POST['confirmation_mot_de_passe'] ?? '';

    // Validation
    if (empty($prenom))  $erreurs[] = "Le prénom est requis.";
    if (empty($nom))     $erreurs[] = "Le nom est requis.";
    if (!$email)         $erreurs[] = "L'adresse email est invalide.";

    if (strlen($mot_de_passe) < 8) {
        $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $mot_de_passe)) {
        $erreurs[] = "Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.";
    }

    if ($mot_de_passe !== $confirmation_mot_de_passe) {
        $erreurs[] = "Les mots de passe ne correspondent pas.";
    }

    // Vérification email déjà utilisé
    if ($email) {
        $stmt = $connexion->prepare("SELECT id FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            $erreurs[] = "Cette adresse email est déjà utilisée.";
        }
    }

    // Inscription
    if (empty($erreurs)) {
        $token_confirmation = bin2hex(random_bytes(32));
        $mot_de_passe_hash  = password_hash($mot_de_passe, PASSWORD_ARGON2ID);

        try {
            $stmt = $connexion->prepare("
                INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, token_confirmation)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$prenom, $nom, $email, $mot_de_passe_hash, $token_confirmation]);

            if (envoyerEmailConfirmation($email, $token_confirmation)) {
                $succes = "Inscription réussie ! Un email de confirmation vous a été envoyé à $email. "
                        . "Vérifiez votre boîte mail (et les spams) pour activer votre compte.";
            } else {
                $succes = "Inscription réussie, mais l'envoi de l'email a échoué. "
                        . "Contactez l'administrateur.";
            }

            // Vider les champs après succès
            $prenom = $nom = $email = '';

        } catch (PDOException $e) {
            $erreurs[] = "Erreur lors de l'inscription : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - Cyna</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            box-sizing: border-box;
        }
        .inscription-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            position: relative;
        }
        .inscription-container::before {
            content: '';
            position: absolute;
            top: 0; left: 0;
            width: 100%; height: 5px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
            border-radius: 10px 10px 0 0;
        }
        h2 { text-align: center; color: #1a2980; margin-bottom: 30px; }
        .form-groupe { margin-bottom: 20px; }
        .form-groupe label { display: block; margin-bottom: 8px; color: #2c3e50; }
        .form-groupe input {
            width: 100%; padding: 12px;
            border: 1px solid #d1d8e0; border-radius: 5px;
            font-size: 16px; box-sizing: border-box;
        }
        .form-groupe input:focus { outline: none; border-color: #1a2980; }
        .btn-inscription {
            width: 100%; padding: 15px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
            color: white; border: none; border-radius: 5px;
            cursor: pointer; font-size: 18px;
            text-transform: uppercase; letter-spacing: 1px;
            transition: opacity .2s;
        }
        .btn-inscription:hover { opacity: .9; }
        .erreurs {
            background-color: #ffebee; color: #d32f2f;
            padding: 15px; border-radius: 5px; margin-bottom: 20px;
        }
        .succes {
            background-color: #e8f5e9; color: #2E7D32;
            padding: 15px; border-radius: 5px; margin-bottom: 20px;
        }
        .lien-connexion { text-align: center; margin-top: 20px; }
        .lien-connexion a { color: #1a2980; text-decoration: none; }
        @media (max-width: 600px) {
            .inscription-container { padding: 20px; }
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

        <?php if (empty($succes)): ?>
        <form method="post" action="">
            <div class="form-groupe">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" required
                       value="<?= isset($prenom) ? htmlspecialchars($prenom) : '' ?>">
            </div>
            <div class="form-groupe">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" required
                       value="<?= isset($nom) ? htmlspecialchars($nom) : '' ?>">
            </div>
            <div class="form-groupe">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
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
        <?php endif; ?>

        <div class="lien-connexion">
            <a href="connexion.php">Déjà un compte ? Se connecter</a>
        </div>
    </div>
    <?php
$show_admin_link = false;
if (isset($_SESSION['utilisateur_id'])) {
    if (!isset($_SESSION['is_admin'])) {
        $stmt = $connexion->prepare("SELECT is_admin FROM utilisateurs WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['utilisateur_id']]);
        $row = $stmt->fetch();
        $_SESSION['is_admin'] = $row ? (int)$row['is_admin'] : 0;
    }
    $show_admin_link = $_SESSION['is_admin'] === 1;
}
?>
<?php if ($show_admin_link): ?>
<div style="position:fixed;bottom:20px;right:20px;z-index:9999">
  <a href="../admin/index.php" style="background:linear-gradient(135deg,#1a2980,#26d0ce);color:#fff;padding:8px 16px;border-radius:30px;font-size:.78rem;font-weight:600;text-decoration:none;box-shadow:0 4px 20px rgba(26,41,128,.4)">
    ⚙ Administration
  </a>
</div>
<?php endif; ?>
</body>
</html>