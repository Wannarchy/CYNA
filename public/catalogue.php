<?php
session_start();
$est_connecte = isset($_SESSION['utilisateur_id']);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/home_repository.php';
require_once __DIR__ . '/../includes/catalog_repository.php';

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$category      = null;
$products      = [];
$allCategories = [];

if ($category_id > 0) {
    $category = cat_get_by_id($connexion, $category_id);
    if ($category) {
        $products = products_get_by_category($connexion, $category_id);
    }
} else {
    $allCategories = categories_get_all($connexion);
}

function category_desc_fallback($name) {
    $map = [
        'SOC' => "Supervision & détection 24/7 pour identifier et répondre aux incidents en temps réel.",
        'EDR' => "Protection des endpoints et réponse aux menaces avancées sur postes et serveurs.",
        'XDR' => "Corrélation multi-sources pour une visibilité globale sur votre surface d'attaque.",
    ];
    $upper = strtoupper(trim($name));
    return isset($map[$upper]) ? $map[$upper] : "Découvrez nos solutions SaaS de cybersécurité dans cette catégorie.";
}

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
  <title>CYNA — Catalogue</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue:#1a2980; --cyan:#26d0ce;
      --grad:linear-gradient(135deg,#1a2980,#26d0ce);
      --bg:#0b1020; --card:#0f1628; --card2:#131c30;
      --border:rgba(255,255,255,.07); --muted:rgba(255,255,255,.45);
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
    .btn-cyna{background:var(--grad);color:#fff;border:none;border-radius:9px;padding:7px 16px;font-size:.82rem;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;text-decoration:none;display:inline-flex;align-items:center;gap:6px;transition:opacity .15s;}
    .btn-cyna:hover{opacity:.85;color:#fff;}
    .navbar-toggler{border:1px solid rgba(255,255,255,.12);padding:5px 8px;}
    .navbar-toggler-icon{background-image:url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255,255,255,.7)' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");}

    /* PAGE */
    .page-wrap{max-width:1200px;margin:0 auto;padding:36px 16px;}
    .breadcrumb-nav{font-size:.75rem;color:var(--muted);margin-bottom:24px;}
    .breadcrumb-nav a{color:var(--muted);text-decoration:none;}
    .breadcrumb-nav a:hover{color:#fff;}
    .breadcrumb-nav span{margin:0 8px;opacity:.4;}

    /* SECTION HEADER */
    .section-label{font-size:.7rem;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;color:var(--cyan);margin-bottom:6px;}
    .section-title{font-size:clamp(1.3rem,2.5vw,1.8rem);font-weight:800;color:#fff;margin-bottom:6px;}
    .section-sub{font-size:.88rem;color:var(--muted);margin-bottom:28px;}

    /* CATEGORY HERO */
    .cat-hero{border-radius:18px;overflow:hidden;margin-bottom:28px;position:relative;height:220px;}
    .cat-hero img{width:100%;height:100%;object-fit:cover;filter:brightness(.65);}
    .cat-hero-overlay{position:absolute;inset:0;background:linear-gradient(90deg,rgba(11,16,32,.85) 0%,rgba(11,16,32,.2) 100%);display:flex;align-items:flex-end;padding:28px 32px;}
    .cat-hero-name{font-size:clamp(1.5rem,3vw,2.2rem);font-weight:800;color:#fff;margin-bottom:6px;}
    .cat-hero-desc{font-size:.88rem;color:rgba(255,255,255,.65);max-width:500px;}
    .cat-hero-badge{display:inline-flex;align-items:center;gap:6px;background:rgba(38,208,206,.15);border:1px solid rgba(38,208,206,.25);color:#26d0ce;border-radius:20px;padding:4px 12px;font-size:.72rem;font-weight:700;margin-bottom:10px;}

    /* CATEGORY CARDS */
    .cat-card{display:block;text-decoration:none;background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;transition:border-color .2s,transform .2s;height:100%;}
    .cat-card:hover{border-color:rgba(38,208,206,.3);transform:translateY(-4px);}
    .cat-card-img{width:100%;height:180px;object-fit:cover;display:block;background:var(--card2);}
    .cat-card-body{padding:18px 20px;}
    .cat-card-name{font-size:1rem;font-weight:700;color:#fff;margin-bottom:6px;}
    .cat-card-desc{font-size:.8rem;color:var(--muted);line-height:1.5;margin-bottom:14px;}
    .cat-card-cta{display:inline-flex;align-items:center;gap:6px;font-size:.8rem;font-weight:700;color:var(--cyan);}

    /* PRODUCT CARDS */
    .prod-card{display:block;text-decoration:none;background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;transition:border-color .2s,transform .2s;height:100%;}
    .prod-card:hover{border-color:rgba(38,208,206,.25);transform:translateY(-4px);}
    .prod-card.unavailable{opacity:.55;}
    .prod-card-img{width:100%;height:150px;object-fit:cover;display:block;background:var(--card2);}
    .prod-card-body{padding:14px 16px;}
    .prod-card-name{font-size:.9rem;font-weight:700;color:#fff;margin-bottom:8px;line-height:1.3;}
    .prod-price{display:flex;align-items:baseline;gap:4px;margin-bottom:4px;}
    .prod-price .amount{font-size:1rem;font-weight:800;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
    .prod-price .period{font-size:.7rem;color:var(--muted);}
    .prod-price-annual{font-size:.75rem;color:var(--muted);}
    .prod-card-footer{padding:10px 16px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;}
    .badge-avail{font-size:.65rem;font-weight:700;color:#4ade80;background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.2);border-radius:20px;padding:2px 8px;}
    .badge-unavail{font-size:.65rem;font-weight:700;color:#f87171;background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.2);border-radius:20px;padding:2px 8px;}
    .prod-arrow{font-size:.75rem;font-weight:700;color:var(--cyan);}

    /* MOBILE LIST */
    .prod-list-item{display:flex;align-items:center;gap:14px;background:var(--card);border:1px solid var(--border);border-radius:14px;padding:14px 16px;text-decoration:none;color:#e8eaf2;transition:border-color .15s;margin-bottom:8px;}
    .prod-list-item:hover{border-color:rgba(38,208,206,.2);color:#e8eaf2;}
    .prod-list-item.unavailable{opacity:.55;}
    .prod-list-img{width:60px;height:60px;border-radius:10px;object-fit:cover;flex-shrink:0;background:var(--card2);}
    .prod-list-name{font-size:.88rem;font-weight:700;color:#fff;margin-bottom:4px;}
    .prod-list-price{font-size:.8rem;color:var(--muted);}

    /* TOOLBAR */
    .toolbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:10px;}
    .toolbar-title{font-size:1rem;font-weight:700;color:#fff;}
    .btn-back{display:inline-flex;align-items:center;gap:6px;background:transparent;border:1px solid rgba(255,255,255,.12);color:rgba(255,255,255,.6);border-radius:9px;padding:7px 14px;font-size:.8rem;font-weight:600;text-decoration:none;transition:all .15s;}
    .btn-back:hover{color:#fff;border-color:rgba(255,255,255,.3);}

    /* EMPTY */
    .empty{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:48px 24px;text-align:center;color:var(--muted);}

    footer{border-top:1px solid var(--border);padding:24px 16px;text-align:center;color:var(--muted);font-size:.75rem;margin-top:40px;}
    footer a{color:rgba(255,255,255,.35);text-decoration:none;margin:0 10px;}
    footer a:hover{color:rgba(255,255,255,.6);}

    @media(max-width:991px){.nav-links{flex-wrap:wrap;margin-left:0;margin-top:8px;}.search-wrap{max-width:100%;margin-top:8px;}}
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
        <?php if (!$est_connecte): ?>
          <a href="connexion.php" class="nav-link-plain">Connexion</a>
          <a href="inscription.php" class="btn-cyna">S'inscrire</a>
        <?php else: ?>
          <a href="mon-compte.php" class="nav-link-plain">Mon compte</a>
          <a href="deconnexion.php" class="nav-link-plain">Déconnexion</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<div class="page-wrap">

<?php if ($category_id > 0): ?>

  <?php if (!$category): ?>
    <div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:12px;padding:16px 20px;color:#fbbf24;margin-bottom:20px">
      ⚠ Catégorie introuvable. <a href="catalogue.php" style="color:#fbbf24;font-weight:700">← Retour au catalogue</a>
    </div>
  <?php else: ?>

    <!-- BREADCRUMB -->
    <div class="breadcrumb-nav">
      <a href="../index.php">Accueil</a><span>›</span>
      <a href="catalogue.php">Catalogue</a><span>›</span>
      <strong style="color:#fff"><?= htmlspecialchars($category['name']) ?></strong>
    </div>

    <!-- HERO CATÉGORIE -->
    <div class="cat-hero">
      <img src="<?= htmlspecialchars(asset_image($category['image_path'] ?? null)) ?>" alt="<?= htmlspecialchars($category['name']) ?>">
      <div class="cat-hero-overlay">
        <div>
          <div class="cat-hero-badge">🛡 Solution SaaS</div>
          <div class="cat-hero-name"><?= htmlspecialchars($category['name']) ?></div>
          <div class="cat-hero-desc"><?= htmlspecialchars(category_desc_fallback($category['name'])) ?></div>
        </div>
      </div>
    </div>

    <!-- TOOLBAR -->
    <div class="toolbar">
      <div class="toolbar-title"><?= count($products) ?> service(s) disponible(s)</div>
      <a href="catalogue.php" class="btn-back">← Toutes les catégories</a>
    </div>

    <?php if (count($products) === 0): ?>
      <div class="empty"><div style="font-size:2rem;margin-bottom:12px;opacity:.3">📦</div><p>Aucun produit dans cette catégorie.</p></div>
    <?php else: ?>

      <!-- MOBILE: liste -->
      <div class="d-block d-lg-none">
        <?php foreach ($products as $p): ?>
          <?php $isAvail = (int)$p['is_available'] === 1; ?>
          <a href="produit.php?id=<?= (int)$p['id'] ?>" class="prod-list-item <?= $isAvail ? '' : 'unavailable' ?>">
            <img class="prod-list-img" src="<?= htmlspecialchars(asset_image($p['image_path'] ?? null)) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
            <div style="flex:1;min-width:0">
              <div class="prod-list-name"><?= htmlspecialchars($p['name']) ?></div>
              <div class="prod-list-price"><?= number_format((float)$p['price_monthly'],2,',',' ') ?> € / mois · <?= number_format((float)$p['price_yearly'],2,',',' ') ?> € / an</div>
            </div>
            <?php if ($isAvail): ?>
              <span class="badge-avail">● Dispo</span>
            <?php else: ?>
              <span class="badge-unavail">● Indispo</span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- DESKTOP: grille -->
      <div class="row g-3 d-none d-lg-flex">
        <?php foreach ($products as $p): ?>
          <?php $isAvail = (int)$p['is_available'] === 1; ?>
          <div class="col-12 col-md-6 col-lg-4">
            <a class="prod-card <?= $isAvail ? '' : 'unavailable' ?>" href="produit.php?id=<?= (int)$p['id'] ?>">
              <img class="prod-card-img" src="<?= htmlspecialchars(asset_image($p['image_path'] ?? null)) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
              <div class="prod-card-body">
                <div class="prod-card-name"><?= htmlspecialchars($p['name']) ?></div>
                <div class="prod-price">
                  <span class="amount"><?= number_format((float)$p['price_monthly'],2,',',' ') ?> €</span>
                  <span class="period">/ mois</span>
                </div>
                <div class="prod-price-annual"><?= number_format((float)$p['price_yearly'],2,',',' ') ?> € / an</div>
              </div>
              <div class="prod-card-footer">
                <?php if ($isAvail): ?>
                  <span class="badge-avail">● Disponible</span>
                <?php else: ?>
                  <span class="badge-unavail">● Indisponible</span>
                <?php endif; ?>
                <span class="prod-arrow">Voir →</span>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  <?php endif; ?>

<?php else: ?>

  <!-- MODE ACCUEIL CATALOGUE -->
  <div class="breadcrumb-nav">
    <a href="../index.php">Accueil</a><span>›</span>
    <strong style="color:#fff">Catalogue</strong>
  </div>

  <div class="section-label">Nos services</div>
  <div class="section-title">Catalogue de solutions SaaS</div>
  <div class="section-sub">Choisissez une catégorie pour découvrir les services disponibles.</div>

  <?php if (count($allCategories) === 0): ?>
    <div class="empty"><div style="font-size:2rem;margin-bottom:12px;opacity:.3">📂</div><p>Aucune catégorie disponible.</p></div>
  <?php else: ?>
    <div class="row g-4">
      <?php foreach ($allCategories as $cat): ?>
        <div class="col-12 col-md-6 col-lg-4">
          <a class="cat-card" href="catalogue.php?category_id=<?= (int)$cat['id'] ?>">
            <img class="cat-card-img" src="<?= htmlspecialchars(asset_image($cat['image_path'] ?? null)) ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
            <div class="cat-card-body">
              <div class="cat-card-name"><?= htmlspecialchars($cat['name']) ?></div>
              <div class="cat-card-desc"><?= htmlspecialchars(category_desc_fallback($cat['name'])) ?></div>
              <div class="cat-card-cta">Voir la catégorie →</div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

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