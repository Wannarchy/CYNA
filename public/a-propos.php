<?php
session_start();
require_once __DIR__ . '/../config/config.php';
$est_connecte = isset($_SESSION['utilisateur_id']);
$nb_panier    = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA — À propos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root{--blue:#1a2980;--cyan:#26d0ce;--grad:linear-gradient(135deg,#1a2980,#26d0ce);}
    *{box-sizing:border-box;}
    body{background:#0b1020;color:#e8eaf2;font-family:'DM Sans',sans-serif;margin:0;}

    /* NAVBAR */
    .navbar{background:rgba(11,16,32,.97)!important;border-bottom:1px solid rgba(255,255,255,.07);backdrop-filter:blur(12px);}
    .navbar-brand{font-weight:800;font-size:1.25rem;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
    .nav-link{color:rgba(255,255,255,.7)!important;font-size:.88rem;transition:color .15s;}
    .nav-link:hover{color:#fff!important;}
    .cart-pill{display:inline-flex;align-items:center;gap:6px;background:rgba(38,208,206,.12);border:1px solid rgba(38,208,206,.25);color:#26d0ce;border-radius:20px;padding:4px 12px;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .15s;}
    .cart-pill:hover{background:rgba(38,208,206,.22);color:#26d0ce;}

    /* HERO */
    .hero{background:linear-gradient(135deg,#0d1a3a 0%,#0b2a2a 100%);padding:80px 0 60px;text-align:center;position:relative;overflow:hidden;}
    .hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 80% 60% at 50% 0%,rgba(38,208,206,.12),transparent);}
    .hero-tag{display:inline-block;background:rgba(38,208,206,.12);border:1px solid rgba(38,208,206,.25);color:#26d0ce;border-radius:20px;padding:5px 16px;font-size:.75rem;font-weight:600;letter-spacing:.5px;text-transform:uppercase;margin-bottom:18px;}
    .hero h1{font-size:clamp(2rem,5vw,3.2rem);font-weight:800;color:#fff;line-height:1.15;margin-bottom:16px;}
    .hero h1 span{background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
    .hero p{font-size:1.05rem;color:rgba(255,255,255,.65);max-width:580px;margin:0 auto;}

    /* SECTIONS */
    .section{padding:64px 0;}
    .section-tag{font-size:.72rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--cyan);margin-bottom:10px;}
    .section-title{font-size:clamp(1.5rem,3vw,2rem);font-weight:700;color:#fff;line-height:1.2;margin-bottom:14px;}
    .section-sub{font-size:.95rem;color:rgba(255,255,255,.55);line-height:1.7;}

    /* CARDS */
    .ccard{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:28px;height:100%;transition:border-color .2s,transform .2s;}
    .ccard:hover{border-color:rgba(38,208,206,.25);transform:translateY(-3px);}
    .ccard-icon{width:48px;height:48px;border-radius:12px;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:1.25rem;margin-bottom:16px;}
    .ccard h5{font-size:.95rem;font-weight:700;color:#fff;margin-bottom:8px;}
    .ccard p{font-size:.84rem;color:rgba(255,255,255,.5);margin:0;line-height:1.6;}

    /* STAT ROW */
    .stat-row{display:flex;gap:0;background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:16px;overflow:hidden;}
    .stat-item{flex:1;padding:32px 20px;text-align:center;border-right:1px solid rgba(255,255,255,.07);}
    .stat-item:last-child{border-right:none;}
    .stat-num{font-size:2.2rem;font-weight:800;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1;}
    .stat-lbl{font-size:.75rem;color:rgba(255,255,255,.45);margin-top:6px;text-transform:uppercase;letter-spacing:.5px;}

    /* TEAM */
    .team-card{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:24px;text-align:center;transition:border-color .2s;}
    .team-card:hover{border-color:rgba(38,208,206,.2);}
    .team-av{width:64px;height:64px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:1.5rem;font-weight:700;color:#fff;margin:0 auto 14px;}
    .team-card h6{font-size:.92rem;font-weight:700;color:#fff;margin-bottom:4px;}
    .team-card .role{font-size:.75rem;color:var(--cyan);font-weight:600;}
    .team-card p{font-size:.78rem;color:rgba(255,255,255,.4);margin-top:8px;margin-bottom:0;line-height:1.5;}

    /* VALUES */
    .val-pill{display:inline-flex;align-items:center;gap:8px;background:#0f1628;border:1px solid rgba(255,255,255,.08);border-radius:12px;padding:14px 18px;font-size:.85rem;color:#e8eaf2;margin:5px;}
    .val-pill span{font-size:1.1rem;}

    /* CTA BAND */
    .cta-band{background:linear-gradient(135deg,rgba(26,41,128,.6),rgba(38,208,206,.2));border:1px solid rgba(38,208,206,.15);border-radius:20px;padding:48px 32px;text-align:center;}
    .cta-band h3{font-size:1.6rem;font-weight:700;color:#fff;margin-bottom:12px;}
    .cta-band p{color:rgba(255,255,255,.6);margin-bottom:28px;}
    .btn-cyna{display:inline-flex;align-items:center;gap:8px;background:var(--grad);color:#fff;border:none;border-radius:12px;padding:13px 28px;font-size:.92rem;font-weight:700;text-decoration:none;transition:opacity .15s,transform .1s;font-family:'DM Sans',sans-serif;}
    .btn-cyna:hover{opacity:.85;transform:translateY(-2px);color:#fff;}
    .btn-outline{display:inline-flex;align-items:center;gap:8px;background:transparent;color:rgba(255,255,255,.7);border:1px solid rgba(255,255,255,.2);border-radius:12px;padding:12px 24px;font-size:.88rem;font-weight:600;text-decoration:none;transition:all .15s;}
    .btn-outline:hover{color:#fff;border-color:rgba(255,255,255,.4);}

    /* FOOTER */
    footer{border-top:1px solid rgba(255,255,255,.07);padding:32px 16px;text-align:center;color:rgba(255,255,255,.3);font-size:.78rem;}
    footer a{color:rgba(255,255,255,.4);text-decoration:none;margin:0 12px;}
    footer a:hover{color:rgba(255,255,255,.7);}
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav me-auto gap-1">
        <li class="nav-item"><a class="nav-link" href="../index.php">Accueil</a></li>
        <li class="nav-item"><a class="nav-link" href="catalogue.php">Catalogue</a></li>
        <li class="nav-item"><a class="nav-link" href="Contact.php">Contact</a></li>
        <li class="nav-item"><a class="nav-link fw-semibold" style="color:#26d0ce!important" href="a-propos.php">À propos</a></li>
      </ul>
      <div class="d-flex align-items-center gap-2">
        <form class="d-flex" action="recherche.php" method="GET">
          <input class="form-control form-control-sm me-2" style="background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#fff;width:180px;" type="search" name="q" placeholder="Rechercher...">
          <button class="btn btn-sm btn-outline-info" type="submit">🔍</button>
        </form>
        <a href="panier.php" class="cart-pill">🛒 <?= $nb_panier > 0 ? $nb_panier : '' ?></a>
        <?php if ($est_connecte): ?>
          <a class="nav-link" href="mon-compte.php">Mon compte</a>
          <a class="nav-link" href="deconnexion.php">Déconnexion</a>
        <?php else: ?>
          <a class="nav-link" href="connexion.php">Connexion</a>
          <a class="nav-link" href="inscription.php">S'inscrire</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="container" style="position:relative;z-index:1">
    <div class="hero-tag">À propos de CYNA</div>
    <h1>Sécuriser votre avenir<br><span>numérique, c'est notre mission</span></h1>
    <p>CYNA est une entreprise spécialisée dans la cybersécurité SaaS, dédiée à protéger les entreprises contre les menaces informatiques les plus avancées.</p>
  </div>
</section>

<!-- STATS -->
<section class="section" style="background:#080d1c;">
  <div class="container">
    <div class="stat-row">
      <div class="stat-item">
        <div class="stat-num">500+</div>
        <div class="stat-lbl">Clients protégés</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">99,9%</div>
        <div class="stat-lbl">Disponibilité SLA</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">24/7</div>
        <div class="stat-lbl">Support SOC</div>
      </div>
      <div class="stat-item">
        <div class="stat-num">3</div>
        <div class="stat-lbl">Solutions SaaS</div>
      </div>
    </div>
  </div>
</section>

<!-- NOTRE MISSION -->
<section class="section">
  <div class="container">
    <div class="row align-items-center g-5">
      <div class="col-lg-5">
        <div class="section-tag">Notre mission</div>
        <h2 class="section-title">Une cybersécurité accessible et efficace</h2>
        <p class="section-sub">Jusqu'à présent, les solutions de sécurité avancées étaient réservées aux grandes entreprises. CYNA démocratise l'accès aux technologies EDR, XDR et SOC pour toutes les organisations, quelle que soit leur taille.</p>
        <p class="section-sub mt-3">Notre plateforme e-commerce permet à nos clients d'accéder, de configurer et de gérer leurs abonnements de sécurité directement en ligne — simplement, rapidement, de manière sécurisée.</p>
      </div>
      <div class="col-lg-7">
        <div class="row g-3">
          <?php
          $missions = [
            ['🛡️', 'Protection proactive', 'Nous détectons et neutralisons les menaces avant qu\'elles n\'impactent votre activité.'],
            ['⚡', 'Réponse en temps réel', 'Nos solutions SOC surveillent votre infrastructure 24h/24, 365 jours par an.'],
            ['🔒', 'Conformité garantie', 'Conformes RGPD, ISO 27001 et aux standards de sécurité les plus exigeants.'],
            ['📊', 'Visibilité totale', 'Des dashboards intuitifs pour piloter votre posture de sécurité en un coup d\'œil.'],
          ];
          foreach ($missions as [$icon, $title, $desc]):
          ?>
          <div class="col-6">
            <div class="ccard">
              <div class="ccard-icon"><?= $icon ?></div>
              <h5><?= $title ?></h5>
              <p><?= $desc ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- NOS SOLUTIONS -->
<section class="section" style="background:#080d1c;">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-tag">Nos produits</div>
      <h2 class="section-title">Des solutions SaaS de pointe</h2>
      <p class="section-sub mx-auto" style="max-width:520px">Trois plateformes complémentaires pour une protection complète de votre infrastructure informatique.</p>
    </div>
    <div class="row g-4">
      <?php
      $products = [
        ['🔵', 'SOC as a Service', 'Security Operations Center', 'Un centre d\'opérations de sécurité externalisé qui surveille votre infrastructure en continu. Nos analystes détectent, analysent et répondent aux incidents de sécurité en temps réel.', '#1a2980'],
        ['🟢', 'EDR', 'Endpoint Detection & Response', 'Protection avancée de vos postes de travail et serveurs. Détection comportementale, isolation automatique des endpoints compromis et remédiation guidée.', '#065f46'],
        ['🟣', 'XDR', 'Extended Detection & Response', 'Corrélation multi-sources de vos données de sécurité (endpoints, réseau, cloud, email) pour une détection plus rapide et une réponse coordonnée aux cybermenaces.', '#4c1d95'],
      ];
      foreach ($products as [$emoji, $name, $subtitle, $desc, $color]):
      ?>
      <div class="col-lg-4">
        <div class="ccard" style="border-color:rgba(255,255,255,.1)">
          <div style="font-size:2rem;margin-bottom:12px"><?= $emoji ?></div>
          <div style="font-size:.68rem;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:var(--cyan);margin-bottom:6px"><?= $subtitle ?></div>
          <h5 style="font-size:1.15rem;margin-bottom:12px"><?= $name ?></h5>
          <p style="font-size:.85rem;color:rgba(255,255,255,.5);line-height:1.65"><?= $desc ?></p>
          <a href="catalogue.php" style="display:inline-flex;align-items:center;gap:6px;margin-top:16px;font-size:.82rem;font-weight:600;color:var(--cyan);text-decoration:none;">Voir les offres →</a>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- NOS VALEURS -->
<section class="section">
  <div class="container">
    <div class="text-center mb-5">
      <div class="section-tag">Nos valeurs</div>
      <h2 class="section-title">Ce qui nous guide au quotidien</h2>
    </div>
    <div class="text-center">
      <?php
      $values = [
        ['🔐', 'Sécurité avant tout'],
        ['🤝', 'Transparence'],
        ['⚙️', 'Innovation'],
        ['🌍', 'Accessibilité'],
        ['📈', 'Excellence'],
        ['💬', 'Réactivité'],
        ['🔬', 'Rigueur'],
        ['🌱', 'Durabilité'],
      ];
      foreach ($values as [$icon, $label]):
      ?>
      <span class="val-pill"><span><?= $icon ?></span><?= $label ?></span>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- INFORMATIONS LÉGALES -->
<section class="section" style="background:#080d1c;">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="section-tag">Informations légales</div>
        <h2 class="section-title">CYNA-IT</h2>
        <div style="background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:28px;font-size:.87rem;color:rgba(255,255,255,.6);line-height:2.2;">
          <div><strong style="color:#fff">Raison sociale :</strong> CYNA-IT</div>
          <div><strong style="color:#fff">Adresse :</strong> 10 Rue de Penthièvre, 75008 Paris</div>
          <div><strong style="color:#fff">SIRET :</strong> 91371103200015</div>
          <div><strong style="color:#fff">Site web :</strong> <a href="https://www.cyna-it.fr" style="color:var(--cyan)">www.cyna-it.fr</a></div>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="section-tag">Certifications</div>
        <h2 class="section-title">Conformité & sécurité</h2>
        <div class="row g-3">
          <?php
          $certs = [
            ['🏆', 'RGPD', 'Protection des données personnelles'],
            ['🔒', 'SSL/TLS', 'Chiffrement des communications'],
            ['💳', 'PCI-DSS', 'Sécurité des paiements'],
            ['📋', 'ISO 27001', 'Management de la sécurité'],
          ];
          foreach ($certs as [$icon, $name, $desc]):
          ?>
          <div class="col-6">
            <div style="background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:16px;text-align:center;">
              <div style="font-size:1.4rem;margin-bottom:8px"><?= $icon ?></div>
              <div style="font-size:.85rem;font-weight:700;color:#fff;margin-bottom:4px"><?= $name ?></div>
              <div style="font-size:.72rem;color:rgba(255,255,255,.4)"><?= $desc ?></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- CTA -->
<section class="section">
  <div class="container">
    <div class="cta-band">
      <h3>Prêt à sécuriser votre infrastructure ?</h3>
      <p>Découvrez nos solutions SaaS et commencez dès aujourd'hui avec un essai gratuit.</p>
      <div class="d-flex gap-3 justify-content-center flex-wrap">
        <a href="catalogue.php" class="btn-cyna">🚀 Voir nos solutions</a>
        <a href="Contact.php" class="btn-outline">💬 Nous contacter</a>
      </div>
    </div>
  </div>
</section>

<!-- FOOTER -->
<footer>
  <div class="mb-2">
    <a href="../index.php">Accueil</a>
    <a href="catalogue.php">Catalogue</a>
    <a href="Contact.php">Contact</a>
    <a href="Cgu.php">CGU</a>
    <a href="mention_legales.php">Mentions légales</a>
    <a href="a-propos.php">À propos</a>
  </div>
  <div>© 2025 CYNA-IT — 10 Rue de Penthièvre, 75008 Paris</div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>