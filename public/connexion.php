<?php
require_once '../config/config.php';
require_once '../includes/function.php';

// Initialisation des variables
$erreurs = [];

// Démarrage de session
startSession();

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Nettoyage et validation des données
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $mot_de_passe = $_POST['mot_de_passe'];

    // Validation de base
    if (!$email) {
        $erreurs[] = "Adresse email invalide.";
    }

    if (empty($mot_de_passe)) {
        $erreurs[] = "Mot de passe requis.";
    }

    // Si pas d'erreurs, on vérifie les identifiants
    if (empty($erreurs)) {
        try {
            // Préparer la requête de vérification
            $stmt = $connexion->prepare("
                SELECT id, prenom, nom, email, mot_de_passe, est_confirme 
                FROM utilisateurs 
                WHERE email = ?
            ");
            $stmt->execute([$email]);
            $utilisateur = $stmt->fetch(PDO::FETCH_ASSOC);

            // Vérifier si l'utilisateur existe et si le mot de passe est correct
            if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
                // Vérifier si le compte est confirmé
                if ($utilisateur['est_confirme'] == 0) {
                    $erreurs[] = "Veuillez confirmer votre compte via l'email de confirmation.";
                } else {
                    // Connexion réussie
                    $_SESSION['utilisateur_id'] = $utilisateur['id'];
                    $_SESSION['utilisateur_prenom'] = $utilisateur['prenom'];
                    $_SESSION['utilisateur_nom'] = $utilisateur['nom'];
                    $_SESSION['utilisateur_email'] = $utilisateur['email'];

                    // Mettre à jour la dernière connexion
                    $stmt = $connexion->prepare("
                        UPDATE utilisateurs 
                        SET derniere_connexion = CURRENT_TIMESTAMP 
                        WHERE id = ?
                    ");
                    $stmt->execute([$utilisateur['id']]);

                    // Redirection vers le tableau de bord ou page d'accueil
                    redirectTo('../public/dashboard.php');
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
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }

        .connexion-container h2 {
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

        .btn-connexion {
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

        .liens-supplementaires {
            text-align: center;
            margin-top: 20px;
        }

        .liens-supplementaires a {
            color: #1a2980;
            text-decoration: none;
            margin: 0 10px;
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
            <a href="mot_de_passe_oublie.php">Mot de passe oublié</a>
        </div>
    </div>
</body>
</html>