<?php
require_once '../config/config.php';
require_once '../includes/function.php';

if (!isset($_GET['email']) || !isset($_GET['token'])) {
    die("Paramètres invalides.");
}

$email = urldecode($_GET['email']);
$token = $_GET['token'];

try {
    $stmt = $connexion->prepare("
        SELECT id FROM utilisateurs
        WHERE email = ? AND token_confirmation = ? AND est_confirme = 0
    ");
    $stmt->execute([$email, $token]);

    if ($stmt->rowCount() > 0) {
        $stmt = $connexion->prepare("
            UPDATE utilisateurs
            SET est_confirme = 1, token_confirmation = NULL
            WHERE email = ? AND token_confirmation = ?
        ");
        $stmt->execute([$email, $token]);

        $message      = "Votre compte a été confirmé avec succès ! Vous pouvez maintenant vous connecter.";
        $message_type = 'succes';
        $redirect     = true;
    } else {
        $message      = "Le lien de confirmation est invalide ou a déjà été utilisé.";
        $message_type = 'erreur';
        $redirect     = false;
    }
} catch (PDOException $e) {
    $message      = "Une erreur s'est produite : " . $e->getMessage();
    $message_type = 'erreur';
    $redirect     = false;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Confirmation de compte - Cyna</title>
    <?php if ($redirect): ?>
        <meta http-equiv="refresh" content="4;url=connexion.php">
    <?php endif; ?>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
            display: flex; justify-content: center; align-items: center;
            height: 100vh; margin: 0;
        }
        .confirmation-container {
            background-color: white; border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,.1);
            padding: 40px; width: 100%; max-width: 420px;
            text-align: center; position: relative;
        }
        .confirmation-container::before {
            content: ''; position: absolute;
            top: 0; left: 0; width: 100%; height: 5px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
            border-radius: 10px 10px 0 0;
        }
        h2 { color: #1a2980; margin-bottom: 20px; }
        .message { margin-bottom: 20px; padding: 15px; border-radius: 5px; }
        .message.succes { background-color: #e8f5e9; color: #2E7D32; }
        .message.erreur { background-color: #ffebee; color: #d32f2f; }
        .icone { font-size: 48px; margin-bottom: 10px; }
        .btn-connexion {
            display: inline-block; padding: 12px 24px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
            color: white; text-decoration: none;
            border-radius: 5px; font-size: 16px; transition: opacity .2s;
        }
        .btn-connexion:hover { opacity: .9; }
        .redirect-info { color: #888; font-size: 13px; margin-top: 15px; }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <?php if ($message_type === 'succes'): ?>
            <div class="icone">✅</div>
            <h2>Compte confirmé !</h2>
        <?php else: ?>
            <div class="icone">❌</div>
            <h2>Confirmation de compte</h2>
        <?php endif; ?>

        <div class="message <?= $message_type ?>">
            <p><?= htmlspecialchars($message) ?></p>
        </div>

        <a href="connexion.php" class="btn-connexion">Se connecter</a>

        <?php if ($redirect): ?>
            <p class="redirect-info">Redirection automatique dans 4 secondes…</p>
        <?php endif; ?>
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