<?php
session_start();
if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}
$order_id = (int)($_GET['order_id'] ?? 0);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — Commande confirmée</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--grad:linear-gradient(135deg,#1a2980,#26d0ce);--cyan:#26d0ce;--border:rgba(255,255,255,.08);}
    *{box-sizing:border-box;margin:0;padding:0;}
    body{background:#0b1020;color:#e8eaf2;font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;flex-direction:column;}
    .bg-deco{position:fixed;inset:0;pointer-events:none;z-index:0;}
    .bg-deco::before{content:'';position:absolute;top:-20%;left:-10%;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(26,41,128,.2),transparent 70%);}
    .bg-deco::after{content:'';position:absolute;bottom:-20%;right:-10%;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(38,208,206,.12),transparent 70%);}
    .navbar{position:relative;z-index:10;padding:16px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
    .navbar-brand{font-weight:900;font-size:1.3rem;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none;}
    main{flex:1;display:flex;align-items:center;justify-content:center;padding:32px 16px;position:relative;z-index:1;}
    .confirm-card{width:100%;max-width:480px;text-align:center;}

    /* Checkmark animé */
    .check-circle{width:80px;height:80px;border-radius:50%;background:rgba(34,197,94,.12);border:2px solid rgba(34,197,94,.3);display:flex;align-items:center;justify-content:center;font-size:2.2rem;margin:0 auto 24px;animation:popIn .4s ease;}
    @keyframes popIn{0%{transform:scale(.5);opacity:0}70%{transform:scale(1.1)}100%{transform:scale(1);opacity:1}}

    .confirm-title{font-size:1.8rem;font-weight:800;color:#fff;margin-bottom:10px;}
    .confirm-sub{font-size:.92rem;color:rgba(255,255,255,.5);line-height:1.6;margin-bottom:28px;}
    .order-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(38,208,206,.08);border:1px solid rgba(38,208,206,.15);border-radius:12px;padding:10px 20px;font-size:.88rem;color:#fff;margin-bottom:28px;}
    .order-badge span{font-family:monospace;font-size:1rem;font-weight:700;color:var(--cyan);}

    .info-box{background:#0f1628;border:1px solid var(--border);border-radius:14px;padding:20px;margin-bottom:24px;text-align:left;}
    .info-row{display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);font-size:.85rem;}
    .info-row:last-child{border-bottom:none;}
    .info-icon{width:32px;height:32px;border-radius:8px;background:rgba(38,208,206,.1);display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;}
    .info-text{color:rgba(255,255,255,.6);}
    .info-text strong{color:#fff;display:block;font-size:.82rem;margin-bottom:1px;}

    .actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;}
    .btn-primary{display:inline-flex;align-items:center;gap:6px;background:var(--grad);color:#fff;border:none;border-radius:11px;padding:12px 24px;font-size:.88rem;font-weight:700;text-decoration:none;transition:opacity .15s;}
    .btn-primary:hover{opacity:.85;color:#fff;}
    .btn-secondary{display:inline-flex;align-items:center;gap:6px;background:transparent;color:rgba(255,255,255,.6);border:1px solid rgba(255,255,255,.15);border-radius:11px;padding:11px 20px;font-size:.85rem;font-weight:600;text-decoration:none;transition:all .15s;}
    .btn-secondary:hover{color:#fff;border-color:rgba(255,255,255,.35);}

    footer{position:relative;z-index:1;text-align:center;padding:20px;font-size:.72rem;color:rgba(255,255,255,.2);}
    footer a{color:rgba(255,255,255,.25);text-decoration:none;margin:0 8px;}
    footer a:hover{color:rgba(255,255,255,.5);}
  </style>
</head>
<body>
<div class="bg-deco"></div>

<nav class="navbar">
  <a class="navbar-brand" href="../index.php">CYNA</a>
</nav>

<main>
  <div class="confirm-card">

    <div class="check-circle">✅</div>

    <div class="confirm-title">Commande confirmée !</div>
    <div class="confirm-sub">
      Merci pour votre confiance. Votre abonnement est activé et<br>
      un email de confirmation vous a été envoyé.
    </div>

    <?php if ($order_id > 0): ?>
    <div class="order-badge">
      Commande <span>#<?= str_pad($order_id, 6, '0', STR_PAD_LEFT) ?></span>
    </div>
    <?php endif; ?>

    <div class="info-box">
      <div class="info-row">
        <div class="info-icon">📧</div>
        <div class="info-text">
          <strong>Email de confirmation</strong>
          Un récapitulatif a été envoyé à votre adresse email.
        </div>
      </div>
      <div class="info-row">
        <div class="info-icon">⚡</div>
        <div class="info-text">
          <strong>Activation immédiate</strong>
          Votre service SaaS est disponible dès maintenant.
        </div>
      </div>
      <div class="info-row">
        <div class="info-icon">📄</div>
        <div class="info-text">
          <strong>Facture disponible</strong>
          Téléchargeable depuis votre espace "Mes commandes".
        </div>
      </div>
    </div>

    <div class="actions">
      <a href="mes-commandes.php" class="btn-primary">📦 Voir mes commandes</a>
      <a href="../index.php" class="btn-secondary">← Retour à l'accueil</a>
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