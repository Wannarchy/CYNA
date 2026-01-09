<?php
require_once '../vendor/autoload.php';
require_once '../config/config.php';
require_once '../includes/function.php';

// Initialisation des variables
$erreurs = [];
$succes = '';

// Récupérer les paramètres GET
$email = urldecode($_GET['email']);
$token = $_GET['token'];

// Traitement du formulaire de réinitialisation
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nouveau_mot_de_passe = $_POST['nouveau_mot_de_passe'];
    $confirmation_mot_de_passe = $_POST['confirmation_mot_de_passe'];

    // Validation du mot de passe
    if (strlen($nouveau_mot_de_passe) < 8) {
        $erreurs[] = "Le mot de passe doit contenir au moins 8 caractères.";
    }

    if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/", $nouveau_mot_de_passe)) {
        $erreurs[] = "Le mot de passe doit contenir au moins une majuscule, une minuscule, un chiffre et un caractère spécial.";
    }

    if ($nouveau_mot_de_passe !== $confirmation_mot_de_passe) {
        $erreurs[] = "Les mots de passe ne correspondent pas.";
    }

    // Si pas d'erreurs, on vérifie le token et réinitialise le mot de passe
    if (empty($erreurs)) {
        try {
            // Log avant la vérification
            error_log("Tentative de réinitialisation - Email: $email, Token: $token");

            // Vérifier la validité du token
            $stmt_verif = $connexion->prepare("
                SELECT id FROM utilisateurs 
                WHERE email = ? 
                AND token_reinitialisation = ? 
                AND expiration_token > NOW()
            ");
            $stmt_verif->execute([$email, $token]);

            // Log du résultat de la vérification
            error_log("Nombre de lignes trouvées : " . $stmt_verif->rowCount());

            if ($stmt_verif->rowCount() > 0) {
                // Hasher le nouveau mot de passe
                $nouveau_mot_de_passe_hash = password_hash($nouveau_mot_de_passe, PASSWORD_ARGON2ID);

                // Mettre à jour le mot de passe et supprimer le token
                $stmt_update = $connexion->prepare("
                    UPDATE utilisateurs 
                    SET mot_de_passe = ?, 
                        token_reinitialisation = NULL, 
                        expiration_token = NULL 
                    WHERE email = ? 
                    AND token_reinitialisation = ?
                ");

                try {
                    // Logs détaillés avant l'exécution
                    error_log("Détails de mise à jour :");
                    error_log("Email: $email");
                    error_log("Token: $token");
                    error_log("Nouveau mot de passe haché : $nouveau_mot_de_passe_hash");

                    $resultat = $stmt_update->execute([
                        $nouveau_mot_de_passe_hash, 
                        $email, 
                        $token
                    ]);
                    
                    // Afficher des informations de débogage détaillées
                    error_log("Résultat : " . ($resultat ? "Succès" : "Échec"));
                    error_log("Nombre de lignes affectées : " . $stmt_update->rowCount());
                    
                    // Vérifier si les paramètres correspondent réellement en base
                    $stmt_verif = $connexion->prepare("
                        SELECT * FROM utilisateurs 
                        WHERE email = ? 
                        AND token_reinitialisation = ?
                    ");
                    $stmt_verif->execute([$email, $token]);
                    $utilisateur = $stmt_verif->fetch(PDO::FETCH_ASSOC);
                    
                    if ($utilisateur) {
                        error_log("Utilisateur trouvé avec ces paramètres");
                        error_log(print_r($utilisateur, true));
                    } else {
                        error_log("Aucun utilisateur trouvé avec ces paramètres");
                    }

                    if ($resultat && $stmt_update->rowCount() > 0) {
                        // Vérification supplémentaire
                        $stmt_verification = $connexion->prepare("
                            SELECT mot_de_passe FROM utilisateurs 
                            WHERE email = ?
                        ");
                        $stmt_verification->execute([$email]);
                        $utilisateur = $stmt_verification->fetch(PDO::FETCH_ASSOC);

                        // Log du mot de passe haché pour vérification
                        error_log("Ancien mot de passe haché : " . $utilisateur['mot_de_passe']);
                        error_log("Nouveau mot de passe haché : " . $nouveau_mot_de_passe_hash);

                        // Vérification si le mot de passe a changé
                        if (password_verify($nouveau_mot_de_passe, $utilisateur['mot_de_passe'])) {
                            $succes = "Votre mot de passe a été réinitialisé avec succès.";
                        } else {
                            $erreurs[] = "Erreur lors de la mise à jour du mot de passe.";
                        }
                    } else {
                        // Log détaillé en cas d'échec
                        error_log("Échec de mise à jour - Aucune ligne modifiée");
                        $erreurs[] = "Impossible de mettre à jour le mot de passe.";
                    }
                } catch (PDOException $e) {
                    // Log de l'erreur détaillée
                    error_log("Erreur de mise à jour : " . $e->getMessage());
                    $erreurs[] = "Erreur : " . $e->getMessage();
                }
            } else {
                $erreurs[] = "Le lien de réinitialisation est invalide ou a expiré.";
            }
        } catch (PDOException $e) {
            // Log de l'erreur de vérification
            error_log("Erreur de vérification : " . $e->getMessage());
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
    <title>Réinitialisation de mot de passe - Cyna</title>
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

        .reinitialisation-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }

        .reinitialisation-container h2 {
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
        .retour-connexion {
    text-align: center;
    margin-top: 20px;
}

.btn-retour {
    display: inline-block;
    padding: 10px 20px;
    background-color: #1a2980;
    color: white;
    text-decoration: none;
    border-radius: 5px;
}
.btn-connexion {
    display: inline-block;
    padding: 10px 20px;
    background: linear-gradient(to right, #1a2980, #26d0ce);
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin-top: 15px;
}
        
    </style>
</head>
<body>
    <div class="reinitialisation-container">
        <h2>Réinitialisation de mot de passe</h2>
        
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
        <p><a href="connexion.php" class="btn btn-connexion">Se connecter</a></p>
    </div>
<?php endif; ?>
            <form method="post" action="">
                <input type="hidden" name="email" value="<?= htmlspecialchars($email) ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                
                <div class="form-groupe">
                    <label for="nouveau_mot_de_passe">Nouveau mot de passe</label>
                    <input type="password" id="nouveau_mot_de_passe" name="nouveau_mot_de_passe" required>
                </div>
                
                <div class="form-groupe">
                    <label for="confirmation_mot_de_passe">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirmation_mot_de_passe" name="confirmation_mot_de_passe" required>
                </div>
                
                <button type="submit" class="btn-reinitialisation">Réinitialiser le mot de passe</button>
                <div class="retour-connexion">
                    <a href="connexion.php" class="btn btn-retour">Retour à la connexion</a>
                </div>
            </form>
            </div>
</body>
</html>