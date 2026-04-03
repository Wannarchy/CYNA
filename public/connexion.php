<?php
require_once '../config/config.php';
require_once '../includes/function.php';

startSession();

if (!empty($_SESSION['utilisateur_id'])) {
    redirectTo('../index.php');
}

$erreurs            = [];
$email_non_confirme = false; // flag pour afficher le bouton de renvoi
$email_pour_renvoi  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email        = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';

    if (!$email)              $erreurs[] = "Adresse email invalide.";
    if (empty($mot_de_passe)) $erreurs[] = "Mot de passe requis.";

    if (empty($erreurs)) {
        try {
            $stmt = $connexion->prepare("
                SELECT id, prenom, nom, email, mot_de_passe, est_confirme
                FROM utilisateurs WHERE email = ?
            ");
            $stmt->execute([$email]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {

                if ((int)$utilisateur['est_confirme'] === 0) {
                    // On active le bloc de renvoi au lieu d'un simple message
                    $email_non_confirme = true;
                    $email_pour_renvoi  = $utilisateur['email'];
                } else {
                    session_regenerate_id(true);
                    $_SESSION['utilisateur_id']     = $utilisateur['id'];
                    $_SESSION['utilisateur_prenom'] = $utilisateur['prenom'];
                    $_SESSION['utilisateur_nom']    = $utilisateur['nom'];
                    $_SESSION['utilisateur_email']  = $utilisateur['email'];

                    $stmt = $connexion->prepare("
                        UPDATE utilisateurs SET derniere_connexion = CURRENT_TIMESTAMP WHERE id = ?
                    ");
                    $stmt->execute([$utilisateur['id']]);

                    redirectTo('../index.php');
                    exit();
                }
            } else {
                $erreurs[] = "Email ou mot de passe incorrect.";
            }
        } catch (PDOException $e) {
            $erreurs[] = "Erreur de connexion : " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Cyna</title>
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
        .connexion-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            position: relative;
        }
        .connexion-container::before {
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
        .btn-connexion {
            width: 100%; padding: 15px;
            background: linear-gradient(to right, #1a2980, #26d0ce);
            color: white; border: none; border-radius: 5px;
            cursor: pointer; font-size: 18px;
            text-transform: uppercase;
        }
        .btn-connexion:hover { opacity: .9; }
        .erreurs {
            background-color: #ffebee; color: #d32f2f;
            padding: 15px; border-radius: 5px; margin-bottom: 20px;
        }
        .erreurs p { margin: 5px 0; }
        /* Bloc email non confirmé */
        .bloc-non-confirme {
            background: #fff8e1;
            border-left: 4px solid #ff9800;
            border-radius: 0 8px 8px 0;
            padding: 16px 18px;
            margin-bottom: 20px;
            color: #5d4037;
        }
        .bloc-non-confirme p { margin: 0 0 12px 0; line-height: 1.5; }
        .bloc-non-confirme p:last-child { margin-bottom: 0; }
        .btn-renvoi {
            display: inline-block;
            width: 100%;
            padding: 12px;
            background: linear-gradient(to right, #ff9800, #f44336);
            color: white; border: none; border-radius: 5px;
            cursor: pointer; font-size: 15px;
            text-align: center; text-decoration: none;
            box-sizing: border-box;
            transition: opacity .2s;
        }
        .btn-renvoi:hover { opacity: .88; }
        .liens-supplementaires {
            text-align: center;
            margin-top: 20px;
        }
        .liens-supplementaires a {
            color: #1a2980; text-decoration: none; margin: 0 10px;
        }
    </style>
</head>
<body>
    <div class="connexion-container">
        <h2>Connexion Cyna</h2>

        <?php if (!empty($erreurs)): ?>
            <div class="erreurs">
                <?php foreach ($erreurs as $erreur): ?>
                    <p><?= htmlspecialchars($erreur) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($email_non_confirme): ?>
            <div class="bloc-non-confirme">
                <p>✉️ <strong>Votre adresse email n'est pas encore confirmée.</strong><br>
                   Vérifiez votre boîte mail (et les spams).<br>
                   Vous n'avez plus l'email ? Renvoyez-en un nouveau :</p>
                <form method="post" action="renvoyer_confirmation.php">
                    <input type="hidden" name="email" value="<?= htmlspecialchars($email_pour_renvoi) ?>">
                    <button type="submit" class="btn-renvoi">📨 Renvoyer l'email de confirmation</button>
                </form>
            </div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-groupe">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?= isset($email) ? htmlspecialchars($email) : '' ?>">
            </div>
            <div class="form-groupe">
                <label for="mot_de_passe">Mot de passe</label>
                <input type="password" id="mot_de_passe" name="mot_de_passe" required>
            </div>
            <button type="submit" class="btn-connexion">Se connecter</button>
        </form>

        <div class="liens-supplementaires">
            <a href="inscription.php">Créer un compte</a>
            <a href="mot_de_passe_oublie.php">Mot de passe oublié ?</a>
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