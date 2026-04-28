<?php
require_once '../vendor/autoload.php';
require_once '../config/config.php';
require_once '../includes/function.php';

$erreurs = [];
$succes  = '';

// ✅ FIX TIMEZONE : synchroniser PHP et MySQL sur le même fuseau horaire
// Sans ça, date() PHP et NOW() MySQL peuvent différer → token considéré expiré à tort
date_default_timezone_set('Europe/Paris');
$connexion->exec("SET time_zone = '+01:00'");

// ✅ CORRECTION : lire email et token depuis GET (arrivée via lien email)
//    ET depuis POST (champs cachés lors de la soumission du formulaire)
//    On priorise POST si disponible, sinon GET
$email = '';
$token = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $token = trim($_POST['token'] ?? '');
} else {
    $email = urldecode($_GET['email'] ?? '');
    $token = trim($_GET['token'] ?? '');
}

// Vérifications de base
if (empty($email) || empty($token)) {
    die("Lien invalide. Veuillez refaire une demande de réinitialisation.");
}

// Vérifier le token AVANT même d'afficher le formulaire
// (dès l'arrivée sur la page via le lien email)
$token_valide = false;
try {
    $stmt = $connexion->prepare("
        SELECT id FROM utilisateurs
        WHERE email = ?
        AND token_reinitialisation = ?
        AND expiration_token > NOW()
    ");
    $stmt->execute([$email, $token]);
    $token_valide = ($stmt->rowCount() > 0);
} catch (PDOException $e) {
    die("Erreur de vérification : " . $e->getMessage());
}

// Traitement du formulaire de réinitialisation (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveau_mot_de_passe      = $_POST['nouveau_mot_de_passe'] ?? '';
    $confirmation_mot_de_passe = $_POST['confirmation_mot_de_passe'] ?? '';

    if (!$token_valide) {
        $erreurs[] = "Le lien de réinitialisation est invalide ou a expiré. Veuillez en demander un nouveau.";
    }

    if (strlen($nouveau_mot_de_passe) < 8) {
        $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères.";
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $nouveau_mot_de_passe)) {
        $erreurs[] = "Le mot de passe doit contenir une majuscule, une minuscule, un chiffre et un caractère spécial.";
    }

    if ($nouveau_mot_de_passe !== $confirmation_mot_de_passe) {
        $erreurs[] = "Les mots de passe ne correspondent pas.";
    }

    if (empty($erreurs)) {
        try {
            $hash = password_hash($nouveau_mot_de_passe, PASSWORD_ARGON2ID);

            $stmt = $connexion->prepare("
                UPDATE utilisateurs
                SET mot_de_passe = ?,
                    token_reinitialisation = NULL,
                    expiration_token = NULL
                WHERE email = ?
                AND token_reinitialisation = ?
                AND expiration_token > NOW()
            ");
            $stmt->execute([$hash, $email, $token]);

            if ($stmt->rowCount() > 0) {
                $succes      = "Votre mot de passe a été réinitialisé avec succès !";
                $token_valide = false; // cacher le formulaire
            } else {
                $erreurs[] = "Le lien a expiré entre temps. Veuillez en demander un nouveau.";
            }
        } catch (PDOException $e) {
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
    <title>Réinitialisation du mot de passe - Cyna</title>
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
        .form-groupe input:focus { outline: none; border-color: #1a2980; }
        .btn {
            width: 100%; padding: 15px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
            color: white; border: none; border-radius: 5px;
            cursor: pointer; font-size: 18px; text-transform: uppercase;
        }
        .btn:hover { opacity: .9; }
        .erreurs { background: #ffebee; color: #d32f2f; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .succes  { background: #e8f5e9; color: #2E7D32;  padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center; }
        .alerte-expire {
            background: #fff3e0; color: #e65100;
            padding: 15px; border-left: 4px solid #ff9800;
            border-radius: 0 5px 5px 0; margin-bottom: 20px;
        }
        .liens { text-align: center; margin-top: 20px; }
        .liens a { color: #1a2980; text-decoration: none; }
        .btn-link {
            display: inline-block; padding: 10px 20px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
            color: white; text-decoration: none; border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Nouveau mot de passe</h2>

        <?php if (!empty($erreurs)): ?>
            <div class="erreurs">
                <?php foreach ($erreurs as $e): ?><p><?= htmlspecialchars($e) ?></p><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($succes)): ?>
            <div class="succes">
                <p>✅ <?= htmlspecialchars($succes) ?></p>
                <p style="margin-top:15px;"><a href="connexion.php" class="btn-link">Se connecter</a></p>
            </div>

        <?php elseif (!$token_valide): ?>
            <div class="alerte-expire">
                <p>⚠️ Ce lien de réinitialisation est <strong>invalide ou a expiré</strong>.</p>
                <p>Les liens sont valables 1 heure. Vous pouvez en demander un nouveau.</p>
            </div>
            <div class="liens">
                <a href="mot_de_passe_oublie.php" class="btn-link">Demander un nouveau lien</a>
            </div>

        <?php else: ?>
            <form method="post" action="">
                <!-- ✅ email et token transmis en champs cachés pour le POST -->
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-groupe">
                    <label for="nouveau_mot_de_passe">Nouveau mot de passe</label>
                    <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" required>
                </div>
                <div class="form-groupe">
                    <label for="confirmation_mot_de_passe">Confirmer le mot de passe</label>
                    <input type="password" id="confirmation_mot_de_passe" name="confirmation_mot_de_passe" required>
                </div>
                <button type="submit" class="btn">Réinitialiser</button>
            </form>
            <div class="liens">
                <a href="connexion.php">← Retour à la connexion</a>
            </div>
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
</body>
</html>