<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once '../config/config.php';
require_once '../includes/function.php';
require_once '../vendor/autoload.php';

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
        $lien = "http://localhost/Cyna/public/confirmer-email.php?email=".urlencode($email)."&token=".$token;
        $mail->Subject = 'Confirmez votre compte Cyna';
        $mail->Body    = "<html><body style='font-family:Arial,sans-serif;color:#333'>
            <div style='max-width:500px;margin:auto;padding:30px;border:1px solid #ddd;border-radius:8px'>
                <h2 style='color:#1a2980'>Confirmation de votre compte Cyna</h2>
                <p>Bonjour,</p>
                <p>Merci de vous être inscrit. Cliquez ci-dessous pour confirmer votre email :</p>
                <p style='text-align:center;margin:30px 0'>
                    <a href='$lien' style='background:linear-gradient(to right,#1a2980,#26d0ce);color:white;padding:12px 24px;text-decoration:none;border-radius:5px;font-size:16px'>Confirmer mon compte</a>
                </p>
                <p style='color:#888;font-size:13px'>Si vous n'avez pas créé de compte sur Cyna, ignorez cet email.</p>
                <p>Cordialement,<br><strong>L'équipe Cyna</strong></p>
            </div></body></html>";
        $mail->AltBody = "Confirmez votre compte : $lien";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Erreur email : " . $mail->ErrorInfo);
        return false;
    }
}

startSession();
$erreurs = [];
$succes  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $prenom  = trim($_POST['prenom'] ?? '');
    $nom     = trim($_POST['nom'] ?? '');
    $email   = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $mdp     = $_POST['mot_de_passe'] ?? '';
    $mdp2    = $_POST['confirmation_mot_de_passe'] ?? '';

    if (empty($prenom))  $erreurs[] = "Le prénom est requis.";
    if (empty($nom))     $erreurs[] = "Le nom est requis.";
    if (!$email)         $erreurs[] = "Email invalide.";
    if (strlen($mdp) < 8)           $erreurs[] = "Le mot de passe doit faire au moins 8 caractères.";
    if (!preg_match('/[A-Z]/', $mdp)) $erreurs[] = "Le mot de passe doit contenir au moins une majuscule.";
    if (!preg_match('/[0-9]/', $mdp)) $erreurs[] = "Le mot de passe doit contenir au moins un chiffre.";
    if ($mdp !== $mdp2)  $erreurs[] = "Les mots de passe ne correspondent pas.";

    if (empty($erreurs)) {
        try {
            $check = $connexion->prepare("SELECT id FROM utilisateurs WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $erreurs[] = "Cette adresse email est déjà utilisée.";
            } else {
                $hash  = password_hash($mdp, PASSWORD_DEFAULT);
                $token = bin2hex(random_bytes(32));
                $stmt  = $connexion->prepare("INSERT INTO utilisateurs (prenom, nom, email, mot_de_passe, token_confirmation) VALUES (?,?,?,?,?)");
                $stmt->execute([$prenom, $nom, $email, $hash, $token]);
                envoyerEmailConfirmation($email, $token);
                $succes = "Inscription réussie ! Vérifiez votre boîte mail pour confirmer votre compte.";
            }
        } catch (PDOException $e) {
            $erreurs[] = "Erreur : " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — Inscription</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--grad:linear-gradient(135deg,#1a2980,#26d0ce);--cyan:#26d0ce;--border:rgba(255,255,255,.08);}
    *{box-sizing:border-box;margin:0;padding:0;}
    body{background:#0b1020;color:#e8eaf2;font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;flex-direction:column;}
    .bg-deco{position:fixed;inset:0;pointer-events:none;overflow:hidden;z-index:0;}
    .bg-deco::before{content:'';position:absolute;top:-20%;left:-10%;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(26,41,128,.25),transparent 70%);}
    .bg-deco::after{content:'';position:absolute;bottom:-20%;right:-10%;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(38,208,206,.15),transparent 70%);}
    .navbar{position:relative;z-index:10;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;border-bottom:1px solid var(--border);}
    .navbar-brand{font-weight:900;font-size:1.3rem;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none;}
    .navbar-link{color:rgba(255,255,255,.5);text-decoration:none;font-size:.83rem;transition:color .15s;}
    .navbar-link:hover{color:#fff;}
    main{flex:1;display:flex;align-items:center;justify-content:center;padding:32px 16px;position:relative;z-index:1;}
    .auth-card{width:100%;max-width:460px;}
    .auth-header{text-align:center;margin-bottom:28px;}
    .auth-logo{display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:14px;background:var(--grad);font-size:1.4rem;margin-bottom:14px;}
    .auth-title{font-size:1.5rem;font-weight:800;color:#fff;margin-bottom:6px;}
    .auth-sub{font-size:.85rem;color:rgba(255,255,255,.45);}
    .form-box{background:#0f1628;border:1px solid var(--border);border-radius:18px;padding:28px;}
    .row-2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .field{margin-bottom:14px;}
    .field-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:rgba(255,255,255,.4);margin-bottom:6px;display:block;}
    .field-input{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:11px 14px;font-size:.88rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .15s;}
    .field-input::placeholder{color:#3a3f52;}
    .field-input:focus{border-color:rgba(38,208,206,.4);background:rgba(255,255,255,.07);box-shadow:0 0 0 3px rgba(38,208,206,.07);}
    /* Password strength */
    .strength-bar{height:3px;border-radius:2px;background:rgba(255,255,255,.08);margin-top:6px;overflow:hidden;}
    .strength-fill{height:100%;border-radius:2px;transition:width .3s,background .3s;}
    .strength-text{font-size:.67rem;color:rgba(255,255,255,.3);margin-top:4px;}
    /* CGU check */
    .cgu-row{display:flex;align-items:flex-start;gap:8px;margin-bottom:18px;font-size:.8rem;color:rgba(255,255,255,.45);}
    .cgu-row input{accent-color:var(--cyan);margin-top:2px;flex-shrink:0;}
    .cgu-row a{color:var(--cyan);text-decoration:none;}
    .cgu-row a:hover{color:#fff;}
    .btn-submit{width:100%;background:var(--grad);color:#fff;border:none;border-radius:11px;padding:13px;font-size:.95rem;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;letter-spacing:.2px;transition:opacity .15s;}
    .btn-submit:hover{opacity:.85;}
    .divider{display:flex;align-items:center;gap:12px;margin:18px 0;color:rgba(255,255,255,.2);font-size:.75rem;}
    .divider::before,.divider::after{content:'';flex:1;height:1px;background:var(--border);}
    .login-row{text-align:center;font-size:.84rem;color:rgba(255,255,255,.4);}
    .login-row a{color:var(--cyan);text-decoration:none;font-weight:600;}
    .login-row a:hover{color:#fff;}
    .alert-err{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 14px;font-size:.82rem;color:#f87171;margin-bottom:16px;}
    .alert-ok{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:14px 16px;font-size:.88rem;color:#4ade80;margin-bottom:16px;text-align:center;}
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
      <div class="auth-logo">🚀</div>
      <div class="auth-title">Créer un compte</div>
      <div class="auth-sub">Accédez à nos solutions SaaS de cybersécurité</div>
    </div>

    <div class="form-box">

      <?php if ($succes): ?>
        <div class="alert-ok">
          ✅ <?= htmlspecialchars($succes) ?><br>
          <a href="connexion.php" style="color:#4ade80;font-weight:600;margin-top:8px;display:inline-block">→ Se connecter</a>
        </div>
      <?php else: ?>

        <?php if (!empty($erreurs)): ?>
          <div class="alert-err">
            <?php foreach ($erreurs as $e): ?>⚠ <?= htmlspecialchars($e) ?><br><?php endforeach; ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="inscription.php">
          <div class="row-2">
            <div class="field">
              <label class="field-label">Prénom *</label>
              <input class="field-input" type="text" name="prenom" required placeholder="Jean"
                value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
            </div>
            <div class="field">
              <label class="field-label">Nom *</label>
              <input class="field-input" type="text" name="nom" required placeholder="Dupont"
                value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
            </div>
          </div>

          <div class="field">
            <label class="field-label">Adresse email *</label>
            <input class="field-input" type="email" name="email" required placeholder="vous@exemple.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>

          <div class="field">
            <label class="field-label">Mot de passe *</label>
            <input class="field-input" type="password" name="mot_de_passe" id="mdp"
              required placeholder="8 caractères min." oninput="checkStrength(this.value)">
            <div class="strength-bar"><div class="strength-fill" id="strength-fill" style="width:0%"></div></div>
            <div class="strength-text" id="strength-text">Entrez un mot de passe</div>
          </div>

          <div class="field">
            <label class="field-label">Confirmer le mot de passe *</label>
            <input class="field-input" type="password" name="confirmation_mot_de_passe" required placeholder="Répétez le mot de passe">
          </div>

          <div class="cgu-row">
            <input type="checkbox" name="cgu" required>
            <span>J'accepte les <a href="Cgu.php">Conditions Générales d'Utilisation</a> et la <a href="mention_legales.php">politique de confidentialité</a> de CYNA.</span>
          </div>

          <button type="submit" class="btn-submit">Créer mon compte →</button>
        </form>

        <div class="divider">ou</div>
        <div class="login-row">Déjà un compte ? <a href="connexion.php">Se connecter</a></div>

      <?php endif; ?>
    </div>
  </div>
</main>

<footer>
  <a href="Cgu.php">CGU</a>
  <a href="mention_legales.php">Mentions légales</a>
  <a href="Contact.php">Contact</a>
  <span>© 2025 CYNA-IT</span>
</footer>

<script>
function checkStrength(val) {
  var score = 0;
  if (val.length >= 8)          score++;
  if (/[A-Z]/.test(val))        score++;
  if (/[0-9]/.test(val))        score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  var colors = ['#ef4444','#f97316','#eab308','#22c55e'];
  var labels = ['Trop faible','Faible','Moyen','Fort'];
  var pct    = ['25%','50%','75%','100%'];
  var idx    = Math.max(0, score - 1);
  document.getElementById('strength-fill').style.width     = val.length ? pct[idx] : '0%';
  document.getElementById('strength-fill').style.background = val.length ? colors[idx] : 'transparent';
  document.getElementById('strength-text').textContent      = val.length ? labels[idx] : 'Entrez un mot de passe';
}
</script>
</body>
</html>