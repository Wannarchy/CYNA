<?php
require_once '../config/config.php';
require_once '../includes/function.php';

startSession();

if (!empty($_SESSION['utilisateur_id'])) {
    redirectTo('../index.php');
}

$erreurs            = [];
$email_non_confirme = false;
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
                    $email_non_confirme = true;
                    $email_pour_renvoi  = $utilisateur['email'];
                } else {
                    session_regenerate_id(true);
                    $_SESSION['utilisateur_id']     = $utilisateur['id'];
                    $_SESSION['utilisateur_prenom'] = $utilisateur['prenom'];
                    $_SESSION['utilisateur_nom']    = $utilisateur['nom'];
                    $_SESSION['utilisateur_email']  = $utilisateur['email'];

                    try {
                        $stmt2 = $connexion->prepare("UPDATE utilisateurs SET derniere_connexion = CURRENT_TIMESTAMP WHERE id = ?");
                        $stmt2->execute([$utilisateur['id']]);
                    } catch (Exception $e) {}

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
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — Connexion</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--grad:linear-gradient(135deg,#1a2980,#26d0ce);--cyan:#26d0ce;--border:rgba(255,255,255,.08);}
    *{box-sizing:border-box;margin:0;padding:0;}
    body{background:#0b1020;color:#e8eaf2;font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;flex-direction:column;}

    /* BG déco */
    .bg-deco{position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:0;}
    .bg-deco::before{content:'';position:absolute;top:-20%;left:-10%;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(26,41,128,.25),transparent 70%);}
    .bg-deco::after{content:'';position:absolute;bottom:-20%;right:-10%;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(38,208,206,.15),transparent 70%);}

    /* NAVBAR */
    .navbar{position:relative;z-index:10;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);}
    .navbar-brand{font-weight:900;font-size:1.3rem;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none;}
    .navbar-link{color:rgba(255,255,255,.5);text-decoration:none;font-size:.83rem;transition:color .15s;}
    .navbar-link:hover{color:#fff;}

    /* MAIN */
    main{flex:1;display:flex;align-items:center;justify-content:center;padding:32px 16px;position:relative;z-index:1;}

    /* CARD */
    .auth-card{width:100%;max-width:420px;}
    .auth-header{text-align:center;margin-bottom:32px;}
    .auth-logo{display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:14px;background:var(--grad);font-size:1.4rem;margin-bottom:16px;}
    .auth-title{font-size:1.5rem;font-weight:800;color:#fff;margin-bottom:6px;}
    .auth-sub{font-size:.85rem;color:rgba(255,255,255,.45);}

    /* FORM */
    .form-box{background:#0f1628;border:1px solid var(--border);border-radius:18px;padding:28px;}
    .field{margin-bottom:16px;}
    .field-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:rgba(255,255,255,.4);margin-bottom:6px;display:block;}
    .field-input{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:11px 14px;font-size:.9rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .15s,background .15s;}
    .field-input::placeholder{color:#3a3f52;}
    .field-input:focus{border-color:rgba(38,208,206,.4);background:rgba(255,255,255,.07);box-shadow:0 0 0 3px rgba(38,208,206,.07);}

    /* REMEMBER + FORGOT */
    .row-opt{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
    .check-label{display:flex;align-items:center;gap:7px;font-size:.8rem;color:rgba(255,255,255,.5);cursor:pointer;}
    .check-label input{accent-color:var(--cyan);}
    .forgot-link{font-size:.8rem;color:var(--cyan);text-decoration:none;font-weight:600;}
    .forgot-link:hover{color:#fff;}

    /* SUBMIT */
    .btn-submit{width:100%;background:var(--grad);color:#fff;border:none;border-radius:11px;padding:13px;font-size:.95rem;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;letter-spacing:.2px;transition:opacity .15s;}
    .btn-submit:hover{opacity:.85;}

    /* DIVIDER */
    .divider{display:flex;align-items:center;gap:12px;margin:20px 0;color:rgba(255,255,255,.2);font-size:.75rem;}
    .divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border);}

    /* REGISTER LINK */
    .register-row{text-align:center;font-size:.84rem;color:rgba(255,255,255,.4);}
    .register-row a{color:var(--cyan);text-decoration:none;font-weight:600;}
    .register-row a:hover{color:#fff;}

    /* ALERTS */
    .alert-err{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 14px;font-size:.82rem;color:#f87171;margin-bottom:16px;}
    .alert-warn{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:10px;padding:12px 14px;font-size:.82rem;color:#fbbf24;margin-bottom:16px;}
    .btn-resend{display:inline-block;margin-top:8px;background:rgba(38,208,206,.1);border:1px solid rgba(38,208,206,.2);color:var(--cyan);border-radius:8px;padding:6px 14px;font-size:.78rem;font-weight:600;text-decoration:none;cursor:pointer;font-family:'DM Sans',sans-serif;}
    .btn-resend:hover{background:rgba(38,208,206,.2);}

    footer{position:relative;z-index:1;text-align:center;padding:20px;font-size:.72rem;color:rgba(255,255,255,.2);}
    footer a{color:rgba(255,255,255,.25);text-decoration:none;margin:0 8px;}
    footer a:hover{color:rgba(255,255,255,.5);}
  </style>
</head>
<body>
<div class="bg-deco"></div>

<nav class="navbar">
  <a class="navbar-brand" href="../index.php">CYNA</a>
  <a class="navbar-link" href="../index.php">← Retour à l'accueil</a>
</nav>

<main>
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-logo">🔐</div>
      <div class="auth-title">Bon retour !</div>
      <div class="auth-sub">Connectez-vous à votre espace CYNA</div>
    </div>

    <div class="form-box">

      <?php if (!empty($erreurs)): ?>
        <div class="alert-err">
          <?php foreach ($erreurs as $e): ?>
            ⚠ <?= htmlspecialchars($e) ?><br>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <?php if ($email_non_confirme): ?>
        <div class="alert-warn">
          ✉ Votre compte n'est pas encore confirmé. Vérifiez votre boîte mail.
          <br>
          <form method="POST" action="renvoyer_confirmation.php" style="display:inline">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email_pour_renvoi) ?>">
            <button type="submit" class="btn-resend">↺ Renvoyer l'email</button>
          </form>
        </div>
      <?php endif; ?>

      <form method="POST" action="connexion.php">
        <div class="field">
          <label class="field-label">Adresse email</label>
          <input class="field-input" type="email" name="email" required
            placeholder="vous@exemple.com"
            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        </div>
        <div class="field">
          <label class="field-label">Mot de passe</label>
          <input class="field-input" type="password" name="mot_de_passe" required placeholder="••••••••">
        </div>

        <div class="row-opt">
          <label class="check-label">
            <input type="checkbox" name="remember"> Se souvenir de moi
          </label>
          <a href="mot_de_passe_oublie.php" class="forgot-link">Mot de passe oublié ?</a>
        </div>

        <button type="submit" class="btn-submit">Se connecter →</button>
      </form>

      <div class="divider">ou</div>

      <div class="register-row">
        Pas encore de compte ? <a href="inscription.php">S'inscrire gratuitement</a>
      </div>

    </div>
  </div>
</main>

<footer>
  <a href="Cgu.php">CGU</a>
  <a href="mention_legales.php">Mentions légales</a>
  <a href="Contact.php">Contact</a>
  <span>© 2025 CYNA-IT</span>
</footer>
</body>
</html>