<?php
session_start();
$raison = htmlspecialchars($_GET['raison'] ?? 'Votre paiement a été refusé par votre banque.');
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — Paiement refusé</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--grad:linear-gradient(135deg,#1a2980,#26d0ce);--cyan:#26d0ce;--border:rgba(255,255,255,.08);}
    *{box-sizing:border-box;margin:0;padding:0;}
    body{background:#0b1020;color:#e8eaf2;font-family:'DM Sans',sans-serif;min-height:100vh;display:flex;flex-direction:column;}
    .bg-deco{position:fixed;inset:0;pointer-events:none;z-index:0;}
    .bg-deco::before{content:'';position:absolute;top:-20%;left:-10%;width:600px;height:600px;border-radius:50%;background:radial-gradient(circle,rgba(239,68,68,.1),transparent 70%);}
    .navbar{position:relative;z-index:10;padding:16px 24px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
    .navbar-brand{font-weight:900;font-size:1.3rem;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;text-decoration:none;}
    main{flex:1;display:flex;align-items:center;justify-content:center;padding:32px 16px;position:relative;z-index:1;}
    .card{width:100%;max-width:480px;text-align:center;}

    .error-circle{width:80px;height:80px;border-radius:50%;background:rgba(239,68,68,.12);border:2px solid rgba(239,68,68,.3);display:flex;align-items:center;justify-content:center;font-size:2.2rem;margin:0 auto 24px;animation:popIn .4s ease;}
    @keyframes popIn{0%{transform:scale(.5);opacity:0}70%{transform:scale(1.1)}100%{transform:scale(1);opacity:1}}

    h1{font-size:1.8rem;font-weight:800;color:#fff;margin-bottom:10px;}
    .sub{font-size:.92rem;color:rgba(255,255,255,.5);line-height:1.6;margin-bottom:24px;}

    .reason-box{background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.2);border-radius:12px;padding:16px 20px;margin-bottom:28px;font-size:.85rem;color:#f87171;line-height:1.5;}

    .tips{background:#0f1628;border:1px solid var(--border);border-radius:14px;padding:20px;margin-bottom:28px;text-align:left;}
    .tips-title{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:rgba(255,255,255,.35);margin-bottom:12px;}
    .tip{display:flex;align-items:flex-start;gap:10px;font-size:.83rem;color:rgba(255,255,255,.55);margin-bottom:10px;line-height:1.5;}
    .tip:last-child{margin-bottom:0;}
    .tip-icon{flex-shrink:0;font-size:1rem;}

    .actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;}
    .btn-primary{display:inline-flex;align-items:center;gap:6px;background:var(--grad);color:#fff;border:none;border-radius:11px;padding:12px 24px;font-size:.88rem;font-weight:700;text-decoration:none;transition:opacity .15s;}
    .btn-primary:hover{opacity:.85;color:#fff;}
    .btn-secondary{display:inline-flex;align-items:center;gap:6px;background:transparent;color:rgba(255,255,255,.6);border:1px solid rgba(255,255,255,.15);border-radius:11px;padding:11px 20px;font-size:.85rem;font-weight:600;text-decoration:none;transition:all .15s;}
    .btn-secondary:hover{color:#fff;border-color:rgba(255,255,255,.35);}

    footer{position:relative;z-index:1;text-align:center;padding:20px;font-size:.72rem;color:rgba(255,255,255,.2);}
  </style>
</head>
<body>
<div class="bg-deco"></div>

<nav class="navbar">
  <a class="navbar-brand" href="../index.php">CYNA</a>
</nav>

<main>
  <div class="card">
    <div class="error-circle">❌</div>
    <h1>Paiement refusé</h1>
    <div class="sub">Votre paiement n'a pas pu être traité. Aucun montant n'a été débité.</div>

    <div class="reason-box">⚠ <?= $raison ?></div>

    <div class="tips">
      <div class="tips-title">Que faire ?</div>
      <div class="tip"><span class="tip-icon">💳</span>Vérifiez que le numéro de carte, la date d'expiration et le CVV sont corrects.</div>
      <div class="tip"><span class="tip-icon">💰</span>Assurez-vous que votre carte dispose de fonds suffisants.</div>
      <div class="tip"><span class="tip-icon">🏦</span>Contactez votre banque — certaines banques bloquent les paiements en ligne par défaut.</div>
      <div class="tip"><span class="tip-icon">🔄</span>Essayez avec une autre carte bancaire.</div>
    </div>

    <div class="actions">
      <a href="checkout.php" class="btn-primary">🔄 Réessayer le paiement</a>
      <a href="panier.php" class="btn-secondary">← Retour au panier</a>
    </div>
  </div>
</main>

<footer>© 2025 CYNA-IT — Support : contact@cyna-it.fr</footer>
</body>
</html>