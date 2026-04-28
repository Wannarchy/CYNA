<?php
require_once '../vendor/autoload.php';
require_once '../config/config.php';
require_once '../includes/function.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$erreurs = [];
$succes  = '';

date_default_timezone_set('Europe/Paris');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $erreurs[] = "Adresse email invalide.";
    } else {
        try {
            $stmt = $connexion->prepare("SELECT id, est_confirme FROM utilisateurs WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token   = bin2hex(random_bytes(32));
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

                try {
                    $connexion->prepare("UPDATE utilisateurs SET reset_token=?, reset_token_expires=? WHERE email=?")
                              ->execute([$token, $expires, $email]);
                } catch (Exception $e) {
                    // Colonne peut ne pas exister encore — on crée
                    $connexion->exec("ALTER TABLE utilisateurs ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL, ADD COLUMN reset_token_expires DATETIME DEFAULT NULL");
                    $connexion->prepare("UPDATE utilisateurs SET reset_token=?, reset_token_expires=? WHERE email=?")
                              ->execute([$token, $expires, $email]);
                }

                $lien = "http://localhost/Cyna/public/reinitialiser_mot_de_passe.php?email=".urlencode($email)."&token=".$token;

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
                    $mail->Subject = 'Réinitialisation de votre mot de passe Cyna';
                    $mail->Body    = "<html><body style='font-family:Arial,sans-serif;color:#333'>
                        <div style='max-width:500px;margin:auto;padding:30px;border:1px solid #ddd;border-radius:8px'>
                            <h2 style='color:#1a2980'>Réinitialisation de mot de passe</h2>
                            <p>Vous avez demandé à réinitialiser votre mot de passe. Ce lien est valide <strong>1 heure</strong>.</p>
                            <p style='text-align:center;margin:30px 0'>
                                <a href='$lien' style='background:linear-gradient(to right,#1a2980,#26d0ce);color:white;padding:12px 24px;text-decoration:none;border-radius:5px;font-size:16px'>Réinitialiser mon mot de passe</a>
                            </p>
                            <p style='color:#888;font-size:13px'>Si vous n'avez pas fait cette demande, ignorez cet email.</p>
                            <p>Cordialement,<br><strong>L'équipe Cyna</strong></p>
                        </div></body></html>";
                    $mail->send();
                } catch (Exception $e) {
                    error_log("Erreur email reset : " . $mail->ErrorInfo);
                }
            }
            // On affiche toujours le même message (sécurité anti-énumération)
            $succes = "Si cette adresse est associée à un compte, un email de réinitialisation a été envoyé.";
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
  <title>CYNA — Mot de passe oublié</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--grad:linear-gradient(135deg,#1a2980,#26d0ce);--cyan:#26d0ce;--border:rgba(255,255,255,.08);}
    *{box-sizing:border-box;margin:0;padding:0;}
    body{background:#0b1020;color:#e8eaf2;font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;flex-direction:column;}
    .bg-deco{position:fixed;inset:0;pointer-events:none;z-index:0;}
    .bg-deco::before{content:'';position:absolute;top:-20%;left:-10%;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(26,41,128,.25),transparent 70%);}
    .bg-deco::after{content:'';position:absolute;bottom:-20%;right:-10%;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(38,208,206,.15),transparent 70%);}
    .navbar{position:relative;z-index:10;padding:16px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
    .navbar-brand{font-weight:900;font-size:1.3rem;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none;}
    .navbar-link{color:rgba(255,255,255,.5);text-decoration:none;font-size:.83rem;}
    .navbar-link:hover{color:#fff;}
    main{flex:1;display:flex;align-items:center;justify-content:center;padding:32px 16px;position:relative;z-index:1;}
    .auth-card{width:100%;max-width:400px;}
    .auth-header{text-align:center;margin-bottom:28px;}
    .auth-logo{display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;border-radius:14px;background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.25);font-size:1.4rem;margin-bottom:14px;}
    .auth-title{font-size:1.4rem;font-weight:800;color:#fff;margin-bottom:6px;}
    .auth-sub{font-size:.84rem;color:rgba(255,255,255,.45);line-height:1.5;}
    .form-box{background:#0f1628;border:1px solid var(--border);border-radius:18px;padding:28px;}
    .field{margin-bottom:18px;}
    .field-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:rgba(255,255,255,.4);margin-bottom:6px;display:block;}
    .field-input{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:11px 14px;font-size:.9rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .15s;}
    .field-input::placeholder{color:#3a3f52;}
    .field-input:focus{border-color:rgba(38,208,206,.4);background:rgba(255,255,255,.07);box-shadow:0 0 0 3px rgba(38,208,206,.07);}
    .btn-submit{width:100%;background:var(--grad);color:#fff;border:none;border-radius:11px;padding:13px;font-size:.95rem;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;transition:opacity .15s;}
    .btn-submit:hover{opacity:.85;}
    .back-link{display:block;text-align:center;margin-top:16px;font-size:.83rem;color:rgba(255,255,255,.4);text-decoration:none;}
    .back-link:hover{color:#fff;}
    .alert-err{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 14px;font-size:.82rem;color:#f87171;margin-bottom:16px;}
    .alert-ok{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:12px;padding:16px;font-size:.88rem;color:#4ade80;text-align:center;}
    .alert-ok .icon{font-size:2rem;margin-bottom:10px;}
    footer{position:relative;z-index:1;text-align:center;padding:20px;font-size:.72rem;color:rgba(255,255,255,.2);}
    footer a{color:rgba(255,255,255,.25);text-decoration:none;margin:0 8px;}
  </style>
</head>
<body>
<div class="bg-deco"></div>

<nav class="navbar">
  <a class="navbar-brand" href="../index.php">CYNA</a>
  <a class="navbar-link" href="connexion.php">← Retour connexion</a>
</nav>

<main>
  <div class="auth-card">
    <div class="auth-header">
      <div class="auth-logo">🔑</div>
      <div class="auth-title">Mot de passe oublié ?</div>
      <div class="auth-sub">Entrez votre email et nous vous enverrons un lien pour réinitialiser votre mot de passe.</div>
    </div>

    <div class="form-box">
      <?php if ($succes): ?>
        <div class="alert-ok">
          <div class="icon">📧</div>
          <?= htmlspecialchars($succes) ?>
          <br><br>
          <a href="connexion.php" style="color:#4ade80;font-weight:600">← Retour à la connexion</a>
        </div>
      <?php else: ?>
        <?php if (!empty($erreurs)): ?>
          <div class="alert-err"><?php foreach ($erreurs as $e): ?>⚠ <?= htmlspecialchars($e) ?><br><?php endforeach; ?></div>
        <?php endif; ?>
        <form method="POST">
          <div class="field">
            <label class="field-label">Adresse email</label>
            <input class="field-input" type="email" name="email" required
              placeholder="vous@exemple.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <button type="submit" class="btn-submit">Envoyer le lien →</button>
        </form>
        <a href="connexion.php" class="back-link">← Retour à la connexion</a>
      <?php endif; ?>
    </div>
  </div>
</main>

<footer>
  <a href="Cgu.php">CGU</a>
  <a href="mention_legales.php">Mentions légales</a>
  <span>© 2025 CYNA-IT</span>
</footer>
</body>
</html>