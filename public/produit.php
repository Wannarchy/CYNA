<?php
session_start();
$est_connecte = isset($_SESSION['utilisateur_id']);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/home_repository.php';
require_once __DIR__ . '/../includes/product_repository.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); die("ID produit invalide."); }

$product = product_get_by_id($connexion, $id);
if (!$product) { http_response_code(404); die("Produit introuvable."); }

$isAvail  = (int)$product['is_available'] === 1;
$desc     = product_desc_fallback($product['name']);
$specs    = product_specs_fallback();
$similars = product_get_similar($connexion, $product['category_id'] ? (int)$product['category_id'] : null, $id, 6);
$ctaLabel = $isAvail ? "S'ABONNER MAINTENANT" : "SERVICE INDISPONIBLE";

// Admin check
$show_admin_link = false;
if (isset($_SESSION['utilisateur_id'])) {
    if (!isset($_SESSION['is_admin'])) {
        $stmt = $connexion->prepare("SELECT is_admin FROM utilisateurs WHERE id = ? LIMIT 1");
        $stmt->execute([$_SESSION['utilisateur_id']]);
        $row = $stmt->fetch();
        $_SESSION['is_admin'] = $row ? (int)$row['is_admin'] : 0;
    }
    $show_admin_link = $_SESSION['is_admin'] === 1;
}

$nb_panier = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — <?= htmlspecialchars($product['name']) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{
      --blue:#1a2980;--cyan:#26d0ce;
      --grad:linear-gradient(135deg,#1a2980,#26d0ce);
      --bg:#0b1020;--card:#0f1628;--card2:#131c30;
      --border:rgba(255,255,255,.07);--muted:rgba(255,255,255,.45);
    }
    *{box-sizing:border-box;}
    body{background:var(--bg);color:#e8eaf2;font-family:'DM Sans',sans-serif;margin:0;}

    /* NAVBAR */
    .navbar{background:rgba(11,16,32,.97)!important;border-bottom:1px solid var(--border);backdrop-filter:blur(14px);height:62px;padding:0;}
    .navbar .container{height:62px;align-items:center;}
    .navbar-brand{font-weight:900;font-size:1.3rem;letter-spacing:-.5px;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;padding:0;margin-right:20px;}
    .search-wrap{flex:1;max-width:480px;position:relative;}
    .search-wrap input{width:100%;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:9px 16px 9px 40px;font-size:.87rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .15s;}
    .search-wrap input::placeholder{color:#4a5168;}
    .search-wrap input:focus{border-color:rgba(38,208,206,.4);box-shadow:0 0 0 3px rgba(38,208,206,.08);}
    .search-icon{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#4a5168;font-size:.85rem;pointer-events:none;}
    .search-btn{position:absolute;right:6px;top:50%;transform:translateY(-50%);background:var(--grad);border:none;border-radius:7px;color:#fff;padding:5px 12px;font-size:.76rem;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;}
    .nav-links{display:flex;align-items:center;gap:4px;margin-left:16px;flex-shrink:0;}
    .nav-link-plain{color:rgba(255,255,255,.6);text-decoration:none;font-size:.83rem;font-weight:500;padding:6px 12px;border-radius:8px;transition:all .15s;white-space:nowrap;}
    .nav-link-plain:hover{color:#fff;background:rgba(255,255,255,.06);}
    .cart-btn{display:inline-flex;align-items:center;gap:6px;background:rgba(38,208,206,.1);border:1px solid rgba(38,208,206,.2);color:#26d0ce;border-radius:20px;padding:5px 14px;font-size:.8rem;font-weight:700;text-decoration:none;}
    .btn-cyna-nav{background:var(--grad);color:#fff;border:none;border-radius:9px;padding:7px 16px;font-size:.82rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;}
    .btn-cyna-nav:hover{opacity:.85;color:#fff;}
    .navbar-toggler{border:1px solid rgba(255,255,255,.12);padding:5px 8px;}
    .navbar-toggler-icon{background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255,255,255,.7)' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");}

    /* PAGE */
    .page-wrap{max-width:1200px;margin:0 auto;padding:36px 16px;}
    .breadcrumb-nav{font-size:.75rem;color:var(--muted);margin-bottom:20px;}
    .breadcrumb-nav a{color:var(--muted);text-decoration:none;}
    .breadcrumb-nav a:hover{color:#fff;}
    .breadcrumb-nav span{margin:0 8px;opacity:.4;}

    /* HERO */
    .prod-hero{border-radius:18px;overflow:hidden;margin-bottom:28px;position:relative;height:300px;}
    .prod-hero img{width:100%;height:100%;object-fit:cover;filter:brightness(.6);}
    .prod-hero-overlay{position:absolute;inset:0;background:linear-gradient(90deg,rgba(11,16,32,.9) 0%,rgba(11,16,32,.2) 100%);display:flex;align-items:flex-end;padding:32px 36px;}
    .prod-hero-content{}
    .prod-hero-badges{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px;}
    .badge-cat{display:inline-flex;align-items:center;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.85);border-radius:20px;padding:3px 12px;font-size:.72rem;font-weight:600;}
    .badge-avail{display:inline-flex;align-items:center;gap:5px;background:rgba(74,222,128,.15);border:1px solid rgba(74,222,128,.25);color:#4ade80;border-radius:20px;padding:3px 12px;font-size:.72rem;font-weight:700;}
    .badge-unavail{display:inline-flex;align-items:center;gap:5px;background:rgba(248,113,113,.15);border:1px solid rgba(248,113,113,.25);color:#f87171;border-radius:20px;padding:3px 12px;font-size:.72rem;font-weight:700;}
    .prod-hero-name{font-size:clamp(1.5rem,3vw,2.2rem);font-weight:800;color:#fff;margin-bottom:8px;line-height:1.15;}
    .prod-hero-desc{font-size:.9rem;color:rgba(255,255,255,.65);max-width:500px;line-height:1.6;}

    /* CARDS */
    .info-card{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;margin-bottom:16px;}
    .info-card-head{padding:16px 20px;border-bottom:1px solid var(--border);font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);}
    .info-card-body{padding:20px;}

    /* SPECS */
    .specs-list{list-style:none;padding:0;margin:0;}
    .specs-list li{display:flex;align-items:flex-start;gap:10px;padding:10px 0;border-bottom:1px solid var(--border);font-size:.88rem;color:#e8eaf2;}
    .specs-list li:last-child{border-bottom:none;}
    .specs-list li::before{content:'✓';color:var(--cyan);font-weight:800;font-size:.9rem;flex-shrink:0;margin-top:1px;}

    /* ABOUT */
    .about-text{font-size:.9rem;color:rgba(255,255,255,.65);line-height:1.7;}

    /* PRICE CARD */
    .price-card{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:24px;margin-bottom:16px;position:sticky;top:76px;}
    .price-row{display:flex;justify-content:space-between;align-items:flex-end;margin-bottom:20px;}
    .price-block{text-align:left;}
    .price-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:4px;}
    .price-amount{font-size:1.8rem;font-weight:800;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;line-height:1;}
    .price-period{font-size:.75rem;color:var(--muted);}
    .price-annual{text-align:right;}
    .price-annual .price-amount{font-size:1.2rem;}
    .price-divider{border:none;border-top:1px solid var(--border);margin:16px 0;}

    /* CTA */
    .btn-subscribe{display:block;width:100%;background:var(--grad);color:#fff;border:none;border-radius:12px;padding:15px;font-size:.95rem;font-weight:800;cursor:pointer;font-family:'DM Sans',sans-serif;text-align:center;letter-spacing:.3px;transition:opacity .15s;}
    .btn-subscribe:hover{opacity:.85;}
    .btn-subscribe:disabled{background:rgba(255,255,255,.1);color:rgba(255,255,255,.3);cursor:not-allowed;opacity:1;}
    .cta-note{font-size:.75rem;color:var(--muted);text-align:center;margin-top:10px;line-height:1.5;}

    /* SECURITY CARD */
    .security-card{background:rgba(38,208,206,.05);border:1px solid rgba(38,208,206,.12);border-radius:14px;padding:16px 18px;}
    .security-card h3{font-size:.8rem;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--cyan);margin-bottom:10px;}
    .security-item{display:flex;align-items:flex-start;gap:8px;font-size:.8rem;color:rgba(255,255,255,.55);margin-bottom:8px;line-height:1.5;}
    .security-item:last-child{margin-bottom:0;}

    /* SIMILARS */
    .prod-card{display:block;text-decoration:none;background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;transition:border-color .2s,transform .2s;height:100%;}
    .prod-card:hover{border-color:rgba(38,208,206,.25);transform:translateY(-4px);}
    .prod-card-img{width:100%;height:140px;object-fit:cover;display:block;background:var(--card2);}
    .prod-card-body{padding:14px 16px;}
    .prod-card-name{font-size:.88rem;font-weight:700;color:#fff;margin-bottom:6px;line-height:1.3;}
    .prod-card-price{font-size:.82rem;color:var(--muted);}
    .prod-card-footer{padding:10px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
    .b-avail{font-size:.65rem;font-weight:700;color:#4ade80;background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.2);border-radius:20px;padding:2px 8px;}
    .b-unavail{font-size:.65rem;font-weight:700;color:#f87171;background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.2);border-radius:20px;padding:2px 8px;}

    /* SIMILARS HEADER */
    .section-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
    .section-title{font-size:1.1rem;font-weight:800;color:#fff;}
    .btn-cat{display:inline-flex;align-items:center;gap:6px;background:transparent;border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.6);border-radius:9px;padding:6px 14px;font-size:.78rem;font-weight:600;text-decoration:none;transition:all .15s;}
    .btn-cat:hover{color:#fff;border-color:rgba(255,255,255,.3);}

    footer{border-top:1px solid var(--border);padding:24px 16px;text-align:center;color:var(--muted);font-size:.75rem;margin-top:40px;}
    footer a{color:rgba(255,255,255,.35);text-decoration:none;margin:0 10px;}
    footer a:hover{color:rgba(255,255,255,.6);}

    @media(max-width:991px){
      .price-card{position:static;}
      .nav-links{flex-wrap:wrap;margin-left:0;margin-top:8px;}
      .search-wrap{max-width:100%;margin-top:8px;}
      .prod-hero{height:220px;}
      .prod-hero-overlay{padding:20px;}
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <form class="search-wrap my-2 my-lg-0" action="recherche.php" method="GET">
        <span class="search-icon">🔍</span>
        <input type="search" name="q" placeholder="Rechercher un service (SOC, EDR, XDR...)">
        <button class="search-btn" type="submit">Chercher</button>
      </form>
      <div class="nav-links">
        <a href="panier.php" class="cart-btn">🛒<?= $nb_panier > 0 ? ' '.$nb_panier : '' ?></a>
        <a href="catalogue.php" class="nav-link-plain">Catalogue</a>
        <?php if (!$est_connecte): ?>
          <a href="connexion.php" class="nav-link-plain">Connexion</a>
          <a href="inscription.php" class="btn-cyna-nav">S'inscrire</a>
        <?php else: ?>
          <a href="mon-compte.php" class="nav-link-plain">Mon compte</a>
          <a href="deconnexion.php" class="nav-link-plain">Déconnexion</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<div class="page-wrap">

  <!-- BREADCRUMB -->
  <div class="breadcrumb-nav">
    <a href="../index.php">Accueil</a><span>›</span>
    <a href="catalogue.php">Catalogue</a>
    <?php if (!empty($product['category_id'])): ?>
      <span>›</span>
      <a href="catalogue.php?category_id=<?= (int)$product['category_id'] ?>"><?= htmlspecialchars($product['category_name'] ?? 'Catégorie') ?></a>
    <?php endif; ?>
    <span>›</span>
    <strong style="color:#fff"><?= htmlspecialchars($product['name']) ?></strong>
  </div>

  <!-- HERO PRODUIT -->
  <div class="prod-hero">
    <img src="<?= htmlspecialchars(asset_image($product['image_path'] ?? null)) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
    <div class="prod-hero-overlay">
      <div class="prod-hero-content">
        <div class="prod-hero-badges">
          <?php if (!empty($product['category_name'])): ?>
            <span class="badge-cat"><?= htmlspecialchars($product['category_name']) ?></span>
          <?php endif; ?>
          <?php if ($isAvail): ?>
            <span class="badge-avail">● Disponible immédiatement</span>
          <?php else: ?>
            <span class="badge-unavail">● Momentanément indisponible</span>
          <?php endif; ?>
        </div>
        <div class="prod-hero-name"><?= htmlspecialchars($product['name']) ?></div>
        <div class="prod-hero-desc"><?= htmlspecialchars($desc) ?></div>
      </div>
    </div>
  </div>

  <!-- CONTENU -->
  <div class="row g-4">

    <!-- GAUCHE : infos -->
    <div class="col-12 col-lg-8">

      <!-- Caractéristiques techniques -->
      <div class="info-card">
        <div class="info-card-head">Caractéristiques techniques</div>
        <div class="info-card-body">
          <ul class="specs-list">
            <?php foreach ($specs as $s): ?>
              <li><?= htmlspecialchars($s) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <!-- À propos -->
      <div class="info-card">
        <div class="info-card-head">À propos de ce service</div>
        <div class="info-card-body">
          <p class="about-text mb-0">
            Ce service CYNA est conçu pour les entreprises souhaitant renforcer leur posture de cybersécurité
            avec une solution SaaS moderne. Vous bénéficiez d'une mise en place rapide, d'une visibilité
            centralisée et d'une supervision continue.
          </p>
        </div>
      </div>

    </div>

    <!-- DROITE : prix + CTA -->
    <div class="col-12 col-lg-4">

      <!-- Prix -->
      <div class="price-card">
        <div class="price-row">
          <div class="price-block">
            <div class="price-label">Mensuel</div>
            <div class="price-amount"><?= number_format((float)$product['price_monthly'],2,',',' ') ?> €</div>
            <div class="price-period">par mois</div>
          </div>
          <div class="price-block price-annual" style="text-align:right">
            <div class="price-label">Annuel</div>
            <div class="price-amount"><?= number_format((float)$product['price_yearly'],2,',',' ') ?> €</div>
            <div class="price-period">par an</div>
          </div>
        </div>

        <hr class="price-divider">

        <form action="panier_add.php" method="POST">
          <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
          <button type="submit" class="btn-subscribe" <?= $isAvail ? '' : 'disabled' ?>>
            <?= $isAvail ? '🚀 S\'ABONNER MAINTENANT' : '⏸ SERVICE INDISPONIBLE' ?>
          </button>
        </form>

        <div class="cta-note">
          <?php if ($isAvail): ?>
            Vous choisirez le cycle (mensuel / annuel) dans votre panier.
          <?php else: ?>
            Ce service est actuellement en maintenance ou temporairement suspendu.
          <?php endif; ?>
        </div>
      </div>

      <!-- Sécurité -->
      <div class="security-card">
        <h3>🔒 Paiement & sécurité</h3>
        <div class="security-item">✓ Paiement sécurisé (Stripe / PayPal)</div>
        <div class="security-item">✓ Données chiffrées en transit et au repos</div>
        <div class="security-item">✓ Abonnement résiliable à tout moment</div>
        <div class="security-item">✓ Support 24/7 inclus</div>
      </div>

    </div>
  </div>

  <!-- SERVICES SIMILAIRES -->
  <?php if (count($similars) > 0): ?>
  <div style="margin-top:48px">
    <div class="section-header">
      <div class="section-title">Services similaires</div>
      <?php if (!empty($product['category_id'])): ?>
        <a href="catalogue.php?category_id=<?= (int)$product['category_id'] ?>" class="btn-cat">Voir la catégorie →</a>
      <?php endif; ?>
    </div>
    <div class="row g-3">
      <?php foreach ($similars as $sp): ?>
        <?php $spAvail = (int)$sp['is_available'] === 1; ?>
        <div class="col-12 col-md-6 col-lg-4">
          <a class="prod-card" href="produit.php?id=<?= (int)$sp['id'] ?>" style="<?= $spAvail ? '' : 'opacity:.55' ?>">
            <img class="prod-card-img" src="<?= htmlspecialchars(asset_image($sp['image_path'] ?? null)) ?>" alt="<?= htmlspecialchars($sp['name']) ?>">
            <div class="prod-card-body">
              <div class="prod-card-name"><?= htmlspecialchars($sp['name']) ?></div>
              <div class="prod-card-price"><?= number_format((float)$sp['price_monthly'],2,',',' ') ?> € / mois</div>
            </div>
            <div class="prod-card-footer">
              <?php if ($spAvail): ?>
                <span class="b-avail">● Disponible</span>
              <?php else: ?>
                <span class="b-unavail">● Indisponible</span>
              <?php endif; ?>
              <span style="font-size:.75rem;font-weight:700;color:var(--cyan)">Voir →</span>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<footer>
  <a href="Cgu.php">CGU</a>
  <a href="mention_legales.php">Mentions légales</a>
  <a href="Contact.php">Contact</a>
  <a href="a-propos.php">À propos</a>
  <span style="display:block;margin-top:8px">© 2025 CYNA-IT</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>