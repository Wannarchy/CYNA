<?php
// Démarrer la session
session_start();

// Vérifier si l'utilisateur est déjà connecté
$est_connecte = isset($_SESSION['utilisateur_id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cyna - Sécurisez votre avenir</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo img {
            max-width: 200px;
        }

        .titre {
            color: #1a2980;
            margin-bottom: 20px;
        }

        .boutons {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn {
            padding: 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-transform: uppercase;
            transition: all 0.3s ease;
        }

        .btn-connexion {
            background: linear-gradient(to right, #1a2980, #26d0ce);
            color: white;
        }

        .btn-inscription {
            background: #f0f0f0;
            color: #1a2980;
        }

        .btn:hover {
            opacity: 0.9;
            transform: translateY(-2px);
        }

        <?php if ($est_connecte): ?>
        .btn-deconnexion {
            background: #ff4d4d;
            color: white;
        }
        <?php endif; ?>
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="logo.jpg" alt="Logo Cyna">
        </div>
        
        <h1 class="titre">Bienvenue chez Cyna</h1>

        <div class="boutons">
            <?php if (!$est_connecte): ?>
                <a href="public/connexion.php">
                    <button class="btn btn-connexion">Se connecter</button>
                </a>
                <a href="public/inscription.php">
                    <button class="btn btn-inscription">Créer un compte</button>
                </a>
            <?php else: ?>
                <a href="dashboard.php">
                    <button class="btn btn-connexion">Accéder au tableau de bord</button>
                </a>
                <a href="deconnexion.php">
                    <button class="btn btn-deconnexion">Se déconnecter</button>
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>