<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../vendor/autoload.php';
require_once '../config/config.php';
require_once '../includes/function.php';

$message      = '';
$message_type = 'erreur';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('connexion.php');
    exit();
}

$email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);

if (!$email) {
    $message = "Adresse email invalide.";
} else {
    try {
        // Vérifier que le compte existe et n'est pas encore confirmé
        $stmt = $connexion->prepare("
            SELECT id, est_confirme FROM utilisateurs WHERE email = ?
        ");
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$utilisateur) {
            // Message neutre pour ne pas révéler si l'email existe
            $message      = "Si cette adresse est associée à un compte non confirmé, un email vient d'être envoyé.";
            $message_type = 'succes';
        } elseif ((int)$utilisateur['est_confirme'] === 1) {
            $message      = "Ce compte est déjà confirmé. Vous pouvez vous connecter directement.";
            $message_type = 'succes';
        } else {
            // Générer un nouveau token
            $nouveau_token = bin2hex(random_bytes(32));

            $stmt = $connexion->prepare("
                UPDATE utilisateurs SET token_confirmation = ? WHERE email = ?
            ");
            $stmt->execute([$nouveau_token, $email]);

            // Envoyer l'email
            $lien = "http://localhost/Cyna/public/confirmer-email.php"
                  . "?email=" . urlencode($email)
                  . "&token=" . $nouveau_token;

            $mail = new PHPMailer(true);
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
            $mail->Subject = 'Confirmez votre compte Cyna';
            $mail->Body    = "
                <html>
                <body style='font-family: Arial, sans-serif; color: #333;'>
                    <div style='max-width:500px; margin:auto; padding:30px; border:1px solid #ddd; border-radius:8px;'>
                        <h2 style='color:#1a2980;'>Confirmation de votre compte Cyna</h2>
                        <p>Bonjour,</p>
                        <p>Voici votre nouveau lien de confirmation (l'ancien a été remplacé) :</p>
                        <p style='text-align:center; margin:30px 0;'>
                            <a href='$lien'
                               style='background:linear-gradient(to right,#1a2980,#26d0ce);
                                      color:white; padding:12px 24px; text-decoration:none;
                                      border-radius:5px; font-size:16px;'>
                                Confirmer mon compte
                            </a>
                        </p>
                        <p style='color:#888; font-size:13px;'>
                            Si vous n'êtes pas à l'origine de cette demande, ignorez cet email.
                        </p>
                        <p>Cordialement,<br><strong>L'équipe Cyna</strong></p>
                    </div>
                </body>
                </html>
            ";
            $mail->AltBody = "Confirmez votre compte Cyna : $lien";

            $mail->send();
            $message      = "Un nouvel email de confirmation a été envoyé à $email. Vérifiez votre boîte mail (et les spams).";
            $message_type = 'succes';
        }
    } catch (Exception $e) {
        $message = "Erreur lors de l'envoi : " . $e->getMessage();
    } catch (PDOException $e) {
        $message = "Erreur : " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renvoi de confirmation - Cyna</title>
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
            padding: 40px; width: 100%; max-width: 420px;
            text-align: center; position: relative;
        }
        .container::before {
            content: ''; position: absolute;
            top: 0; left: 0; width: 100%; height: 5px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
            border-radius: 10px 10px 0 0;
        }
        h2 { color: #1a2980; margin-bottom: 25px; }
        .icone { font-size: 52px; margin-bottom: 10px; }
        .message { padding: 16px; border-radius: 8px; margin-bottom: 25px; line-height: 1.5; }
        .message.succes { background: #e8f5e9; color: #2E7D32; }
        .message.erreur { background: #ffebee; color: #d32f2f; }
        .btn-link {
            display: inline-block; padding: 12px 28px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
            color: white; text-decoration: none;
            border-radius: 5px; font-size: 16px; transition: opacity .2s;
        }
        .btn-link:hover { opacity: .9; }
    </style>
</head>
<body>
    <div class="container">
        <div class="icone"><?= $message_type === 'succes' ? '📨' : '❌' ?></div>
        <h2>Email de confirmation</h2>
        <div class="message <?= $message_type ?>">
            <p><?= htmlspecialchars($message) ?></p>
        </div>
        <a href="connexion.php" class="btn-link">← Retour à la connexion</a>
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
</body>
</html>