<?php
session_start();
$est_connecte = isset($_SESSION['utilisateur_id']);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/home_repository.php';      // asset_image()
require_once __DIR__ . '/../includes/catalog_repository.php';

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;

$category = null;
$products = [];
$allCategories = [];

if ($category_id > 0) {
    $category = cat_get_by_id($connexion, $category_id);
    if ($category) {
        $products = products_get_by_category($connexion, $category_id);
    }
} else {
    // mode "accès au catalogue" : affiche toutes les catégories
    $allCategories = categories_get_all($connexion);
}

// petite description fallback car ta table categories n’a pas description
function category_desc_fallback(string $name): string {
    $map = [
        'SOC' => "Supervision & détection 24/7 pour identifier et répondre aux incidents en temps réel.",
        'EDR' => "Protection des endpoints et réponse aux menaces avancées sur postes et serveurs.",
        'XDR' => "Corrélation multi-sources pour une visibilité globale sur votre surface d’attaque.",
    ];
    $upper = strtoupper(trim($name));
    return $map[$upper] ?? "Découvrez nos solutions SaaS de cybersécurité dans cette catégorie.";
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — Catalogue</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background: #0b1020; }
    .section-title { color: #fff; }
    .lift:hover { transform: translateY(-4px); transition: .2s ease; box-shadow: 0 10px 25px rgba(0,0,0,.15); }
    .hero-img {
      height: 220px;
      object-fit: cover;
      width: 100%;
      filter: brightness(0.7);
    }
    .hero-wrap { position: relative; border-radius: 16px; overflow: hidden; }
    .hero-overlay {
      position: absolute; inset: 0;
      display: flex; align-items: end;
      padding: 16px;
      color: #fff;
      background: linear-gradient(180deg, rgba(0,0,0,0) 30%, rgba(0,0,0,.65) 100%);
    }
    .card-img-top { object-fit: cover; height: 160px; }
    .muted-on-dark { color: rgba(255,255,255,.75); }
    .soldout {
      opacity: .6;
      border: 1px dashed rgba(0,0,0,.2);
    }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="../index.php">CYNA</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">
      <form class="d-flex ms-lg-3 my-2 my-lg-0 w-100" action="recherche.php" method="GET">
        <input class="form-control me-2" type="search" name="q" placeholder="Rechercher un service (SOC, EDR, XDR...)">
        <button class="btn btn-outline-info" type="submit">Rechercher</button>
      </form>

            <ul class="navbar-nav ms-lg-3">
        <li class="nav-item"><a class="nav-link" href="panier.php">Panier</a></li>
        <?php if (!$est_connecte): ?>
          <li class="nav-item"><a class="nav-link" href="connexion.php">Connexion</a></li>
          <li class="nav-item"><a class="nav-link" href="inscription.php">Inscription</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="mon-compte.php">Mon compte</a></li>
          <li class="nav-item"><a class="nav-link" href="deconnexion.php">Déconnexion</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<div class="container py-4 py-lg-5">

<?php if ($category_id > 0): ?>
  <?php if (!$category): ?>
    <div class="alert alert-warning">
      Catégorie introuvable ou inactive.
      <a href="catalogue.php" class="alert-link">Retour au catalogue</a>
    </div>
  <?php else: ?>
    <!-- HERO catégorie (image + surimpression nom) -->
    <div class="hero-wrap mb-3">
      <img class="hero-img" src="<?= htmlspecialchars(asset_image($category['image_path'] ?? null)) ?>" alt="<?= htmlspecialchars($category['name']) ?>">
      <div class="hero-overlay">
        <div>
          <h1 class="h3 mb-1 fw-bold"><?= htmlspecialchars($category['name']) ?></h1>
          <div class="muted-on-dark"><?= htmlspecialchars(category_desc_fallback($category['name'])) ?></div>
        </div>
      </div>
    </div>

    <!-- Produits -->
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="section-title h5 mb-0">Produits</h2>
      <a class="btn btn-sm btn-outline-light" href="catalogue.php">Toutes les catégories</a>
    </div>

    <?php if (count($products) === 0): ?>
      <div class="alert alert-secondary">Aucun produit dans cette catégorie.</div>
    <?php else: ?>

      <!-- MOBILE: liste verticale -->
      <div class="d-block d-lg-none">
        <div class="list-group">
          <?php foreach ($products as $p): ?>
            <?php $isAvail = (int)$p['is_available'] === 1; ?>
            <a href="produit.php?id=<?= (int)$p['id'] ?>"
               class="list-group-item list-group-item-action <?= $isAvail ? '' : 'soldout' ?>">
              <div class="d-flex gap-3">
                <img src="<?= htmlspecialchars(asset_image($p['image_path'] ?? null)) ?>"
                     alt="<?= htmlspecialchars($p['name']) ?>"
                     style="width: 84px; height: 84px; object-fit: cover; border-radius: 12px;">
                <div class="flex-grow-1">
                  <div class="d-flex justify-content-between align-items-start">
                    <div class="fw-semibold"><?= htmlspecialchars($p['name']) ?></div>
                    <?php if (!$isAvail): ?>
                      <span class="badge text-bg-secondary">Indisponible</span>
                    <?php endif; ?>
                  </div>
                  <div class="text-muted small">
                    Mensuel : <?= number_format((float)$p['price_monthly'], 2, ',', ' ') ?> € ·
                    Annuel : <?= number_format((float)$p['price_yearly'], 2, ',', ' ') ?> €
                  </div>
                </div>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- DESKTOP: grille -->
      <div class="row g-3 d-none d-lg-flex">
        <?php foreach ($products as $p): ?>
          <?php $isAvail = (int)$p['is_available'] === 1; ?>
          <div class="col-12 col-md-6 col-lg-4">
            <a class="text-decoration-none" href="produit.php?id=<?= (int)$p['id'] ?>">
              <div class="card border-0 shadow-sm h-100 lift <?= $isAvail ? '' : 'soldout' ?>">
                <img class="card-img-top" src="<?= htmlspecialchars(asset_image($p['image_path'] ?? null)) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                    <h6 class="card-title mb-1"><?= htmlspecialchars($p['name']) ?></h6>
                    <?php if (!$isAvail): ?>
                      <span class="badge text-bg-secondary">Indisponible</span>
                    <?php endif; ?>
                  </div>

                  <div class="text-muted small">
                    Mensuel : <?= number_format((float)$p['price_monthly'], 2, ',', ' ') ?> €<br>
                    Annuel : <?= number_format((float)$p['price_yearly'], 2, ',', ' ') ?> €
                  </div>
                </div>
                <div class="card-footer bg-white border-0 pt-0">
                  <span class="btn btn-sm btn-outline-info w-100">
                    <?= $isAvail ? "Voir l’offre" : "Voir quand même" ?>
                  </span>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>

    <?php endif; ?>
  <?php endif; ?>

<?php else: ?>
  <!-- MODE "accès catalogue" : grille catégories -->
  <h1 class="section-title h4 mb-3">Catalogue</h1>
  <p class="muted-on-dark mb-4">Choisissez une catégorie pour afficher les services disponibles.</p>

  <?php if (count($allCategories) === 0): ?>
    <div class="alert alert-secondary">Aucune catégorie disponible.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($allCategories as $cat): ?>
        <div class="col-12 col-md-6 col-lg-4">
          <a class="text-decoration-none" href="catalogue.php?category_id=<?= (int)$cat['id'] ?>">
            <div class="card border-0 shadow-sm h-100 lift">
              <img class="card-img-top" src="<?= htmlspecialchars(asset_image($cat['image_path'] ?? null)) ?>" alt="<?= htmlspecialchars($cat['name']) ?>">
              <div class="card-body text-center">
                <h4 class="card-title text-dark fw-bold mb-1"><?= htmlspecialchars($cat['name']) ?></h4>
                <div class="text-muted small"><?= htmlspecialchars(category_desc_fallback($cat['name'])) ?></div>
              </div>
              <div class="card-footer bg-white border-0 pt-0">
                <span class="btn btn-sm btn-outline-info w-100">Voir la catégorie</span>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
<?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php
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
?>
<?php if ($show_admin_link): ?>
<div style="position:fixed;bottom:20px;right:20px;z-index:9999">
  <a href="../admin/index.php" style="background:linear-gradient(135deg,#1a2980,#26d0ce);color:#fff;padding:8px 16px;border-radius:30px;font-size:.78rem;font-weight:600;text-decoration:none;box-shadow:0 4px 20px rgba(26,41,128,.4)">
    ⚙ Administration
  </a>
</div>
<?php endif; ?>
</body>
</html>