<?php session_start(); $est_connecte = isset($_SESSION['utilisateur_id']); $nb_panier = array_sum(array_column($_SESSION['panier'] ?? [], 'qty')); ?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA — Conditions Générales d'Utilisation</title>
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
    .toc{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:20px 24px;margin-bottom:32px;}
    .toc h3{font-size:.8rem;font-weight:600;text-transform:uppercase;letter-spacing:1px;color:#8b92a8;margin-bottom:12px;}
    .toc a{display:block;font-size:.85rem;color:#8b92a8;text-decoration:none;padding:4px 0;transition:color .15s;}
    .toc a:hover{color:var(--cyan);}
    .legal-card{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:28px 32px;margin-bottom:20px;}
    .legal-card h2{font-size:1rem;font-weight:600;color:#fff;margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid rgba(255,255,255,.07);}
    .legal-card p,.legal-card li{font-size:.9rem;color:#8b92a8;line-height:1.8;margin-bottom:8px;}
    .legal-card ul{padding-left:20px;}
    .legal-card a{color:var(--cyan);text-decoration:none;}
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
    <h1>Conditions Générales d'Utilisation</h1>
    <p>En vigueur au 1er janvier 2025 — Version 1.0</p>
  </div>

  <div class="row justify-content-center">
    <div class="col-12 col-lg-8">

      <!-- Sommaire -->
      <div class="toc">
        <h3>Sommaire</h3>
        <a href="#art1">Article 1 — Objet</a>
        <a href="#art2">Article 2 — Accès au service</a>
        <a href="#art3">Article 3 — Inscription et compte utilisateur</a>
        <a href="#art4">Article 4 — Services proposés</a>
        <a href="#art5">Article 5 — Commandes et paiement</a>
        <a href="#art6">Article 6 — Abonnements et résiliation</a>
        <a href="#art7">Article 7 — Données personnelles</a>
        <a href="#art8">Article 8 — Responsabilité</a>
        <a href="#art9">Article 9 — Droit applicable</a>
      </div>

      <div class="legal-card" id="art1">
        <h2>Article 1 — Objet</h2>
        <p>Les présentes Conditions Générales d'Utilisation (CGU) ont pour objet de définir les modalités et conditions d'utilisation des services proposés sur le site CYNA-IT (ci-après « le Site »), ainsi que de définir les droits et obligations des parties dans ce cadre.</p>
        <p>Tout accès et/ou utilisation du Site suppose l'acceptation et le respect de l'ensemble des termes des présentes CGU.</p>
      </div>

      <div class="legal-card" id="art2">
        <h2>Article 2 — Accès au service</h2>
        <p>Le Site est accessible gratuitement à tout utilisateur disposant d'un accès à Internet. Tous les coûts afférents à l'accès au Site, que ce soit les frais matériels, logiciels ou d'accès à Internet sont exclusivement à la charge de l'utilisateur.</p>
        <p>CYNA-IT se réserve le droit de modifier, suspendre ou interrompre, sans préavis, l'accès à tout ou partie du Site pour effectuer des opérations de maintenance ou des mises à jour.</p>
      </div>

      <div class="legal-card" id="art3">
        <h2>Article 3 — Inscription et compte utilisateur</h2>
        <p>La souscription aux services SaaS de CYNA-IT nécessite la création d'un compte utilisateur. L'utilisateur s'engage à :</p>
        <ul>
          <li>Fournir des informations exactes, complètes et à jour lors de l'inscription</li>
          <li>Maintenir la confidentialité de ses identifiants de connexion</li>
          <li>Notifier immédiatement CYNA-IT de toute utilisation non autorisée de son compte</li>
          <li>Ne pas créer plusieurs comptes pour un même utilisateur</li>
        </ul>
        <p>CYNA-IT se réserve le droit de suspendre ou supprimer tout compte en cas de violation des présentes CGU.</p>
      </div>

      <div class="legal-card" id="art4">
        <h2>Article 4 — Services proposés</h2>
        <p>CYNA-IT propose des solutions de cybersécurité SaaS (Software as a Service) incluant notamment des services de type SOC (Security Operations Center), EDR (Endpoint Detection and Response) et XDR (Extended Detection and Response).</p>
        <p>Les caractéristiques détaillées de chaque service sont décrites sur les pages produits correspondantes. CYNA-IT se réserve le droit de faire évoluer ses offres à tout moment.</p>
      </div>

      <div class="legal-card" id="art5">
        <h2>Article 5 — Commandes et paiement</h2>
        <p>Toute commande passée sur le Site implique l'acceptation des présentes CGU et du prix indiqué. Les prix sont indiqués en euros TTC.</p>
        <p>Le paiement s'effectue en ligne par carte bancaire via une solution sécurisée conforme aux normes PCI-DSS. CYNA-IT ne conserve aucune donnée bancaire sur ses serveurs.</p>
        <p>La commande est considérée comme définitive après confirmation du paiement. Une confirmation par email est envoyée à l'adresse fournie lors de l'inscription.</p>
      </div>

      <div class="legal-card" id="art6">
        <h2>Article 6 — Abonnements et résiliation</h2>
        <p>Les services CYNA-IT sont proposés sous forme d'abonnements mensuels ou annuels. L'abonnement est reconduit automatiquement à échéance, sauf résiliation préalable.</p>
        <p>L'utilisateur peut résilier son abonnement à tout moment depuis son espace compte. La résiliation prend effet à la fin de la période d'abonnement en cours. Aucun remboursement prorata temporis n'est prévu sauf disposition légale contraire.</p>
      </div>

      <div class="legal-card" id="art7">
        <h2>Article 7 — Données personnelles</h2>
        <p>CYNA-IT collecte et traite des données personnelles dans le cadre de la fourniture de ses services, conformément à sa politique de confidentialité et au RGPD.</p>
        <p>Pour toute question relative à vos données personnelles, contactez notre DPO à : <a href="mailto:privacy@cyna-it.fr">privacy@cyna-it.fr</a></p>
      </div>

      <div class="legal-card" id="art8">
        <h2>Article 8 — Responsabilité</h2>
        <p>CYNA-IT s'engage à fournir ses services avec le soin et la diligence appropriés. Cependant, CYNA-IT ne pourra être tenue responsable des dommages indirects, pertes d'exploitation ou pertes de données résultant de l'utilisation ou de l'impossibilité d'utiliser les services.</p>
        <p>La responsabilité totale de CYNA-IT est limitée au montant des sommes versées par l'utilisateur au cours des 12 derniers mois.</p>
      </div>

      <div class="legal-card" id="art9">
        <h2>Article 9 — Droit applicable et litiges</h2>
        <p>Les présentes CGU sont soumises au droit français. En cas de litige, une solution amiable sera recherchée avant tout recours judiciaire. À défaut, les tribunaux de Paris seront seuls compétents.</p>
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