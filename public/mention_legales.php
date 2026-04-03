<?php session_start(); $est_connecte = isset($_SESSION['utilisateur_id']); $nb_panier = array_sum(array_column($_SESSION['panier'] ?? [], 'qty')); ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA — Mentions légales</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{--blue:#1a2980;--cyan:#26d0ce;--grad:linear-gradient(135deg,#1a2980,#26d0ce);}
    body{background:#0b1020;color:#e8eaf2;font-family:'DM Sans',sans-serif;}
    .navbar{background:rgba(11,16,32,.95)!important;border-bottom:1px solid rgba(255,255,255,.07);backdrop-filter:blur(12px);}
    .navbar-brand{font-weight:700;font-size:1.2rem;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
    .page-hero{padding:56px 0 32px;border-bottom:1px solid rgba(255,255,255,.07);margin-bottom:40px;}
    .page-hero h1{font-size:2rem;font-weight:700;color:#fff;}
    .page-hero p{color:#8b92a8;margin-top:6px;}
    .legal-card{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:28px 32px;margin-bottom:20px;}
    .legal-card h2{font-size:1rem;font-weight:600;color:#fff;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,.07);}
    .legal-card p,.legal-card li{font-size:.9rem;color:#8b92a8;line-height:1.8;margin-bottom:8px;}
    .legal-card a{color:var(--cyan);text-decoration:none;}
    .legal-card a:hover{text-decoration:underline;}
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
      <?php if ($est_connecte): ?>
        <a href="mon-compte.php" class="btn btn-outline-info btn-sm">Mon compte</a>
      <?php else: ?>
        <a href="connexion.php" class="btn btn-outline-info btn-sm">Connexion</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container">
  <div class="page-hero">
    <h1>Mentions légales</h1>
    <p>Informations légales relatives au site CYNA-IT</p>
  </div>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">

      <div class="legal-card">
        <h2>Éditeur du site</h2>
        <p><strong style="color:#fff">CYNA-IT</strong><br>
        10 Rue de Penthièvre<br>75008 Paris, France<br>
        SIRET : 91371103200015<br>
        Email : <a href="mailto:contact@cyna-it.fr">contact@cyna-it.fr</a><br>
        Site : <a href="https://www.cyna-it.fr" target="_blank">www.cyna-it.fr</a></p>
      </div>

      <div class="legal-card">
        <h2>Hébergement</h2>
        <p>Ce site est hébergé par un prestataire d'hébergement professionnel. Les coordonnées de l'hébergeur sont disponibles sur demande auprès de CYNA-IT.</p>
      </div>

      <div class="legal-card">
        <h2>Propriété intellectuelle</h2>
        <p>L'ensemble du contenu de ce site (textes, images, logos, icônes, sons, logiciels) est la propriété exclusive de CYNA-IT ou de ses partenaires. Toute reproduction, représentation, modification, publication ou adaptation de tout ou partie des éléments du site, quel que soit le moyen ou le procédé utilisé, est interdite sans l'autorisation écrite préalable de CYNA-IT.</p>
      </div>

      <div class="legal-card">
        <h2>Protection des données personnelles</h2>
        <p>Conformément au Règlement Général sur la Protection des Données (RGPD) et à la loi Informatique et Libertés, vous disposez d'un droit d'accès, de rectification, de suppression et de portabilité de vos données personnelles.</p>
        <p>Pour exercer ces droits, contactez-nous à : <a href="mailto:privacy@cyna-it.fr">privacy@cyna-it.fr</a></p>
        <p>Les données collectées sur ce site sont utilisées uniquement dans le cadre de la relation commerciale avec CYNA-IT et ne sont jamais revendues à des tiers.</p>
      </div>

      <div class="legal-card">
        <h2>Cookies</h2>
        <p>Ce site utilise des cookies techniques nécessaires à son bon fonctionnement (gestion de session, panier d'achat). Aucun cookie publicitaire ou de tracking n'est déposé sans votre consentement préalable.</p>
      </div>

      <div class="legal-card">
        <h2>Responsabilité</h2>
        <p>CYNA-IT s'efforce d'assurer l'exactitude et la mise à jour des informations diffusées sur ce site. Cependant, CYNA-IT ne peut garantir l'exactitude, la précision ou l'exhaustivité des informations mises à disposition. CYNA-IT décline toute responsabilité pour toute imprécision, inexactitude ou omission portant sur des informations disponibles sur ce site.</p>
      </div>

      <div class="legal-card">
        <h2>Droit applicable</h2>
        <p>Les présentes mentions légales sont soumises au droit français. En cas de litige, les tribunaux français seront seuls compétents.</p>
        <p style="color:#5c6378;font-size:.8rem">Dernière mise à jour : janvier 2025</p>
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
</body>
</html>