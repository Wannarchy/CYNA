<?php
require_once '../vendor/autoload.php';
require_once '../config/config.php';
require_once '../includes/function.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$erreurs = [];
$succes  = '';

// ✅ FIX TIMEZONE : même fuseau entre PHP et MySQL
date_default_timezone_set('Europe/Paris');
$connexion->exec("SET time_zone = '+01:00'");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $erreurs[] = "Adresse email invalide.";
    }

    if (empty($erreurs)) {
        try {
            $stmt = $connexion->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->rowCount() > 0) {
                $token_reinitialisation = bin2hex(random_bytes(32));
                $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $stmt = $connexion->prepare("
                    UPDATE utilisateurs
                    SET token_reinitialisation = ?, expiration_token = ?
                    WHERE email = ?
                ");
                $stmt->execute([$token_reinitialisation, $expiration, $email]);

                // ✅ CORRECTION : ajout de "http://" pour que le lien soit cliquable
                $lien_reinitialisation = "http://localhost/Cyna/public/reinitialiser_mot_de_passe.php"
                    . "?email=" . urlencode($email)
                    . "&token=" . $token_reinitialisation;

                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'noreplycyna@gmail.com';
                $mail->Password   = 'uaws jfaf jqal cahx';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';

                $mail->setFrom('noreplycyna@gmail.com', 'Cyna Sécurité');
                $mail->addAddress($email);
                $mail->isHTML(true);
                $mail->Subject = 'Réinitialisation de votre mot de passe Cyna';
                $mail->Body    = "
                    <html>
                    <body style='font-family: Arial, sans-serif; color: #333;'>
                        <div style='max-width:500px; margin:auto; padding:30px; border:1px solid #ddd; border-radius:8px;'>
                            <h2 style='color:#1a2980;'>Réinitialisation de mot de passe</h2>
                            <p>Bonjour,</p>
                            <p>Vous avez demandé une réinitialisation de votre mot de passe.</p>
                            <p>Cliquez sur le bouton ci-dessous (lien valable <strong>1 heure</strong>) :</p>
                            <p style='text-align:center; margin:30px 0;'>
                                <a href='$lien_reinitialisation'
                                   style='background:linear-gradient(to right,#1a2980,#26d0ce);
                                          color:white; padding:12px 24px; text-decoration:none;
                                          border-radius:5px; font-size:16px;'>
                                    Réinitialiser mon mot de passe
                                </a>
                            </p>
                            <p style='color:#888; font-size:13px;'>
                                Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.
                            </p>
                            <p>Cordialement,<br><strong>L'équipe Cyna</strong></p>
                        </div>
                    </body>
                    </html>
                ";
                $mail->AltBody = "Réinitialisez votre mot de passe ici : $lien_reinitialisation";

                $mail->send();
                $succes = "Un email de réinitialisation a été envoyé à $email. Vérifiez votre boîte mail (et les spams).";
            } else {
                // Message volontairement neutre (sécurité : ne pas révéler si l'email existe)
                $succes = "Si cette adresse est associée à un compte, vous recevrez un email de réinitialisation.";
            }
        } catch (Exception $e) {
            $erreurs[] = "Erreur lors de l'envoi : " . $e->getMessage();
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
            display: flex; justify-content: center; align-items: center;
            height: 100vh; margin: 0;
        }
        .container {
            background: white; border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,.1);
            padding: 40px; width: 100%; max-width: 400px;
            position: relative;
        }
        .container::before {
            content: ''; position: absolute;
            top: 0; left: 0; width: 100%; height: 5px;
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
        .btn {
            width: 100%; padding: 15px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
            color: white; border: none; border-radius: 5px;
            cursor: pointer; font-size: 18px; text-transform: uppercase;
        }
        .erreurs { background: #ffebee; color: #d32f2f; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .succes  { background: #e8f5e9; color: #2E7D32; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .liens { text-align: center; margin-top: 20px; }
        .liens a { color: #1a2980; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Mot de passe oublié</h2>

        <?php if (!empty($erreurs)): ?>
            <div class="erreurs">
                <?php foreach ($erreurs as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
            <div class="succes"><p><?= htmlspecialchars($succes) ?></p></div>
        <?php endif; ?>

        <?php if (empty($succes)): ?>
        <form method="post" action="">
            <div class="form-groupe">
                <label for="email">Votre adresse email</label>
                <input type="email" id="email" name="email" required
                       value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
            </div>
            <button type="submit" class="btn">Envoyer le lien</button>
        </form>
        <?php endif; ?>

        <div class="liens">
            <a href="connexion.php">← Retour à la connexion</a>
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