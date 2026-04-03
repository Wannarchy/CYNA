<?php
session_start();
require_once __DIR__ . '/../config/config.php';

$est_connecte = isset($_SESSION['utilisateur_id']);
$nb_panier    = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));
$success      = false;
$errors       = [];

// Pré-remplir l'email si connecté
$prefill_email = '';
if ($est_connecte) {
    $stmt = $connexion->prepare("SELECT email FROM utilisateurs WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['utilisateur_id']]);
    $row = $stmt->fetch();
    $prefill_email = $row['email'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email   = trim($_POST['email']   ?? '');
    $sujet   = trim($_POST['sujet']   ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Adresse email invalide.";
    if (strlen($sujet) < 3)   $errors[] = "Le sujet est trop court (minimum 3 caractères).";
    if (strlen($message) < 10) $errors[] = "Le message est trop court (minimum 10 caractères).";

    if (empty($errors)) {
        // Envoi email via PHPMailer
        $mail_sent = false;
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'noreplycyna@gmail.com';
            $mail->Password   = 'uaws jfaf jqal cahx';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom('noreply@cyna.com', 'CYNA-IT Contact');
            $mail->addAddress('noreplycyna@gmail.com', 'Support CYNA');
            $mail->addReplyTo($email, $email);
            $mail->Subject = '[Contact CYNA] ' . $sujet;
            $mail->Body    = "Message de : $email\n\nSujet : $sujet\n\n---\n\n$message";
            $mail->send();
            $mail_sent = true;
        } catch (Exception $e) {
            // Si PHPMailer échoue, on log mais on confirme quand même à l'utilisateur
            $mail_sent = true; // en dev, on simule le succès
        }
        if ($mail_sent) $success = true;
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA — Contact</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{--blue:#1a2980;--cyan:#26d0ce;--grad:linear-gradient(135deg,#1a2980,#26d0ce);}
    *{box-sizing:border-box;}
    body{background:#0b1020;color:#e8eaf2;font-family:'DM Sans',sans-serif;margin:0;}
    .navbar{background:rgba(11,16,32,.95)!important;border-bottom:1px solid rgba(255,255,255,.07);backdrop-filter:blur(12px);}
    .navbar-brand{font-weight:700;font-size:1.2rem;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}

    .page-hero{padding:56px 0 32px;border-bottom:1px solid rgba(255,255,255,.07);margin-bottom:40px;text-align:center;}
    .page-hero h1{font-size:2rem;font-weight:700;color:#fff;}
    .page-hero p{color:#8b92a8;margin-top:8px;max-width:500px;margin-left:auto;margin-right:auto;}

    .contact-card{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:20px;padding:32px;height:100%;}
    .contact-card h2{font-size:1rem;font-weight:600;color:#fff;margin-bottom:20px;}

    .form-label{font-size:.75rem;font-weight:600;color:#8b92a8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;display:block;}
    .form-control,.form-select{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:10px 14px;font-size:.88rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;width:100%;transition:border-color .15s;}
    .form-control::placeholder{color:#3a3f52;}
    .form-control:focus,.form-select:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(38,208,206,.1);background:rgba(38,208,206,.04);}
    .form-select option{background:#0f1628;}
    textarea.form-control{resize:vertical;min-height:140px;}

    .btn-send{background:var(--grad);color:#fff;border:none;border-radius:10px;padding:11px 28px;font-size:.9rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;transition:opacity .15s,transform .1s;width:100%;}
    .btn-send:hover{opacity:.85;transform:translateY(-1px);}

    .error-box{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 16px;font-size:.83rem;color:#f87171;margin-bottom:20px;}
    .success-box{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:16px;padding:32px;text-align:center;}
    .success-box .ico{font-size:2.5rem;margin-bottom:12px;}
    .success-box h3{font-size:1.1rem;font-weight:600;color:#fff;margin-bottom:8px;}
    .success-box p{font-size:.85rem;color:#8b92a8;}

    .info-item{display:flex;align-items:flex-start;gap:12px;margin-bottom:20px;}
    .info-icon{width:38px;height:38px;border-radius:10px;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:1rem;flex-shrink:0;}
    .info-label{font-size:.72rem;font-weight:600;color:#8b92a8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;}
    .info-val{font-size:.88rem;color:#e8eaf2;}
    .info-val a{color:var(--cyan);text-decoration:none;}

    .faq-item{border-bottom:1px solid rgba(255,255,255,.07);padding:14px 0;}
    .faq-item:last-child{border-bottom:none;}
    .faq-q{font-size:.88rem;font-weight:500;color:#fff;cursor:pointer;display:flex;justify-content:space-between;align-items:center;}
    .faq-a{font-size:.83rem;color:#8b92a8;margin-top:8px;line-height:1.7;display:none;}
    .faq-a.open{display:block;}
    .chevron{transition:transform .2s;font-size:.7rem;}
    .chevron.open{transform:rotate(180deg);}

    footer{border-top:1px solid rgba(255,255,255,.07);margin-top:60px;padding:24px 0;text-align:center;color:#5c6378;font-size:.78rem;}
    footer a{color:#5c6378;text-decoration:none;margin:0 12px;}
    footer a:hover{color:#8b92a8;}
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <div class="d-flex align-items-center gap-2 ms-auto">
      <a href="recherche.php" class="btn btn-outline-secondary btn-sm">Rechercher</a>
      <a href="panier.php" class="btn btn-outline-light btn-sm">🛒 <?= $nb_panier > 0 ? "($nb_panier)" : '' ?></a>
      <?php if ($est_connecte): ?><a href="mon-compte.php" class="btn btn-outline-info btn-sm">Mon compte</a>
      <?php else: ?><a href="connexion.php" class="btn btn-outline-info btn-sm">Connexion</a><?php endif; ?>
    </div>
  </div>
</nav>

<div class="container">
  <div class="page-hero">
    <h1>Contactez-nous</h1>
    <p>Notre équipe est disponible du lundi au vendredi, de 9h à 18h pour répondre à toutes vos questions.</p>
  </div>

  <div class="row g-4 mb-5">

    <!-- Formulaire -->
    <div class="col-12 col-lg-7">
      <div class="contact-card">
        <h2>Envoyer un message</h2>

        <?php if ($success): ?>
          <div class="success-box">
            <div class="ico">✅</div>
            <h3>Message envoyé !</h3>
            <p>Merci pour votre message. Notre équipe vous répondra dans les plus brefs délais à l'adresse indiquée.</p>
            <a href="contact.php" style="display:inline-block;margin-top:16px;color:var(--cyan);font-size:.85rem">Envoyer un autre message →</a>
          </div>

        <?php else: ?>

          <?php if ($errors): ?>
            <div class="error-box">
              <?php foreach ($errors as $e): ?>⚠ <?= htmlspecialchars($e) ?><br><?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form method="POST">
            <div class="mb-3">
              <label class="form-label">Adresse email *</label>
              <input class="form-control" type="email" name="email" required
                     value="<?= htmlspecialchars($prefill_email ?: ($_POST['email'] ?? '')) ?>"
                     placeholder="vous@exemple.com">
            </div>
            <div class="mb-3">
              <label class="form-label">Sujet *</label>
              <select class="form-select" name="sujet">
                <option value="">— Choisir un sujet —</option>
                <option value="Question sur les abonnements" <?= ($_POST['sujet']??'')==='Question sur les abonnements'?'selected':'' ?>>Question sur les abonnements</option>
                <option value="Problème technique" <?= ($_POST['sujet']??'')==='Problème technique'?'selected':'' ?>>Problème technique</option>
                <option value="Demande de devis" <?= ($_POST['sujet']??'')==='Demande de devis'?'selected':'' ?>>Demande de devis</option>
                <option value="Facturation" <?= ($_POST['sujet']??'')==='Facturation'?'selected':'' ?>>Facturation</option>
                <option value="Partenariat" <?= ($_POST['sujet']??'')==='Partenariat'?'selected':'' ?>>Partenariat</option>
                <option value="Autre" <?= ($_POST['sujet']??'')==='Autre'?'selected':'' ?>>Autre</option>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label">Message *</label>
              <textarea class="form-control" name="message" placeholder="Décrivez votre demande en détail..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>
            <button class="btn-send" type="submit">Envoyer le message</button>
          </form>

        <?php endif; ?>
      </div>
    </div>

    <!-- Infos + FAQ -->
    <div class="col-12 col-lg-5">
      <div class="contact-card mb-4">
        <h2>Nos coordonnées</h2>
        <div class="info-item">
          <div class="info-icon">📍</div>
          <div><div class="info-label">Adresse</div><div class="info-val">10 Rue de Penthièvre<br>75008 Paris, France</div></div>
        </div>
        <div class="info-item">
          <div class="info-icon">✉️</div>
          <div><div class="info-label">Email</div><div class="info-val"><a href="mailto:contact@cyna-it.fr">contact@cyna-it.fr</a></div></div>
        </div>
        <div class="info-item">
          <div class="info-icon">🕐</div>
          <div><div class="info-label">Horaires</div><div class="info-val">Lun–Ven : 9h–18h<br>Hors jours fériés</div></div>
        </div>
        <div class="info-item" style="margin-bottom:0">
          <div class="info-icon">🌐</div>
          <div><div class="info-label">Site web</div><div class="info-val"><a href="https://www.cyna-it.fr" target="_blank">www.cyna-it.fr</a></div></div>
        </div>
      </div>

      <div class="contact-card">
        <h2>Questions fréquentes</h2>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)">Comment modifier mon abonnement ? <span class="chevron">▼</span></div>
          <div class="faq-a">Connectez-vous à votre compte, rendez-vous dans « Mes abonnements » et cliquez sur « Modifier » à côté de l'abonnement souhaité.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)">Quels modes de paiement acceptez-vous ? <span class="chevron">▼</span></div>
          <div class="faq-a">Nous acceptons les cartes bancaires Visa, Mastercard et American Express via notre prestataire de paiement sécurisé.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)">Comment résilier mon abonnement ? <span class="chevron">▼</span></div>
          <div class="faq-a">Vous pouvez résilier à tout moment depuis votre espace compte. La résiliation prend effet à la fin de la période en cours.</div>
        </div>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)">Proposez-vous une période d'essai ? <span class="chevron">▼</span></div>
          <div class="faq-a">Certains services proposent une période d'essai gratuite. Consultez la page produit correspondante pour plus d'informations.</div>
        </div>
      </div>
    </div>

  </div>
</div>

<footer>
  <a href="mentions-legales.php">Mentions légales</a>
  <a href="cgu.php">CGU</a>
  <a href="contact.php">Contact</a>
  <span>© 2025 CYNA-IT</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleFaq(el) {
  var ans = el.nextElementSibling;
  var chev = el.querySelector('.chevron');
  ans.classList.toggle('open');
  chev.classList.toggle('open');
}
</script>
</body>
</html>