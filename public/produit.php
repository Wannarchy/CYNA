<?php
session_start();
$est_connecte = isset($_SESSION['utilisateur_id']);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/home_repository.php';    // asset_image()
require_once __DIR__ . '/../includes/product_repository.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    http_response_code(400);
    die("ID produit invalide.");
}

$product = product_get_by_id($connexion, $id);
if (!$product) {
    http_response_code(404);
    die("Produit introuvable.");
}

$isAvail = (int)$product['is_available'] === 1;
$desc = product_desc_fallback($product['name']);
$specs = product_specs_fallback();
$similars = product_get_similar($connexion, $product['category_id'] ? (int)$product['category_id'] : null, $id, 6);

// CTA texte selon dispo
$ctaLabel = $isAvail ? "S'ABONNER MAINTENANT" : "SERVICE INDISPONIBLE";
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — <?= htmlspecialchars($product['name']) ?></title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background: #0b1020; }
    .section-title { color: #fff; }
    .muted-on-dark { color: rgba(255,255,255,.75); }
    .lift:hover { transform: translateY(-4px); transition: .2s ease; box-shadow: 0 10px 25px rgba(0,0,0,.15); }
    .hero-card { border-radius: 16px; overflow: hidden; }
    .hero-img { height: 320px; object-fit: cover; width: 100%; filter: brightness(.75); }
    .hero-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(180deg, rgba(0,0,0,0) 30%, rgba(0,0,0,.7) 100%);
    }
    .badge-soft { background: rgba(255,255,255,.12); border: 1px solid rgba(255,255,255,.18); }
    .card-img-top { height: 150px; object-fit: cover; }
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
        <li class="nav-item"><a class="nav-link" href="catalogue.php">Catalogue</a></li>
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

  <!-- Fil d'Ariane simple -->
  <div class="mb-3">
    <a class="text-info text-decoration-none" href="catalogue.php">← Retour catalogue</a>
    <?php if (!empty($product['category_id'])): ?>
      <span class="text-white-50">/</span>
      <a class="text-info text-decoration-none" href="catalogue.php?category_id=<?= (int)$product['category_id'] ?>">
        <?= htmlspecialchars($product['category_name'] ?? 'Catégorie') ?>
      </a>
    <?php endif; ?>
  </div>

  <!-- HERO produit -->
  <div class="card border-0 shadow-sm hero-card mb-4">
    <div class="position-relative">
      <img class="hero-img" src="<?= htmlspecialchars(asset_image($product['image_path'] ?? null)) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
      <div class="hero-overlay"></div>

      <div class="position-absolute bottom-0 start-0 p-3 p-lg-4 text-white">
        <div class="d-flex flex-wrap gap-2 mb-2">
          <?php if (!empty($product['category_name'])): ?>
            <span class="badge badge-soft"><?= htmlspecialchars($product['category_name']) ?></span>
          <?php endif; ?>
          <span class="badge <?= $isAvail ? 'text-bg-success' : 'text-bg-secondary' ?>">
            <?= $isAvail ? 'Disponible immédiatement' : 'Momentanément indisponible' ?>
          </span>
        </div>

        <h1 class="h3 fw-bold mb-1"><?= htmlspecialchars($product['name']) ?></h1>
        <div class="muted-on-dark"><?= htmlspecialchars($desc) ?></div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <!-- Col gauche: infos -->
    <div class="col-12 col-lg-8">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h2 class="h5 mb-3">Caractéristiques techniques</h2>
          <ul class="mb-0">
            <?php foreach ($specs as $s): ?>
              <li><?= htmlspecialchars($s) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>

      <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
          <h2 class="h5 mb-2">À propos de ce service</h2>
          <p class="mb-0 text-muted">
            Ce service CYNA est conçu pour les entreprises souhaitant renforcer leur posture de cybersécurité avec une solution SaaS moderne.
            Vous bénéficiez d’une mise en place rapide, d’une visibilité centralisée et d’une supervision continue.
          </p>
        </div>
      </div>
    </div>

    <!-- Col droite: prix + CTA -->
    <div class="col-12 col-lg-4">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="text-muted small">Prix mensuel</div>
              <div class="fs-4 fw-bold">
                <?= number_format((float)$product['price_monthly'], 2, ',', ' ') ?> €
              </div>
            </div>
            <div class="text-end">
              <div class="text-muted small">Prix annuel</div>
              <div class="fs-5 fw-semibold">
                <?= number_format((float)$product['price_yearly'], 2, ',', ' ') ?> €
              </div>
            </div>
          </div>

          <hr>

          <!-- CTA dynamique -->
          <form action="panier_add.php" method="POST" class="d-grid gap-2">
            <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
            <button type="submit" class="btn btn-info btn-lg"
              <?= $isAvail ? '' : 'disabled' ?>>
              <?= htmlspecialchars($ctaLabel) ?>
            </button>

            <?php if (!$isAvail): ?>
              <div class="text-muted small">
                Ce service est actuellement en maintenance ou temporairement suspendu.
              </div>
            <?php else: ?>
              <div class="text-muted small">
                Vous pourrez choisir le cycle (mensuel/annuel) dans le panier.
              </div>
            <?php endif; ?>
          </form>
        </div>
      </div>

      <div class="card border-0 shadow-sm mt-3">
        <div class="card-body">
          <h3 class="h6 mb-2">Sécurité</h3>
          <div class="text-muted small">
            Paiement sécurisé (Stripe/PayPal en mode test). Sessions & protection CSRF recommandées.
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- SERVICES SIMILAIRES -->
  <div class="mt-5">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h2 class="section-title h5 mb-0">Services similaires</h2>
      <?php if (!empty($product['category_id'])): ?>
        <a class="btn btn-sm btn-outline-light" href="catalogue.php?category_id=<?= (int)$product['category_id'] ?>">Voir la catégorie</a>
      <?php endif; ?>
    </div>

    <?php if (count($similars) === 0): ?>
      <div class="alert alert-secondary">Aucun service similaire trouvé.</div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($similars as $sp): ?>
          <?php $spAvail = (int)$sp['is_available'] === 1; ?>
          <div class="col-12 col-md-6 col-lg-4">
            <a class="text-decoration-none" href="produit.php?id=<?= (int)$sp['id'] ?>">
              <div class="card border-0 shadow-sm h-100 lift <?= $spAvail ? '' : 'opacity-75' ?>">
                <img class="card-img-top" src="<?= htmlspecialchars(asset_image($sp['image_path'] ?? null)) ?>" alt="<?= htmlspecialchars($sp['name']) ?>">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                    <h6 class="card-title mb-1"><?= htmlspecialchars($sp['name']) ?></h6>
                    <span class="badge <?= $spAvail ? 'text-bg-success' : 'text-bg-secondary' ?>">
                      <?= $spAvail ? 'Dispo' : 'Indispo' ?>
                    </span>
                  </div>
                  <div class="text-muted small">
                    <?= number_format((float)$sp['price_monthly'], 2, ',', ' ') ?> € / mois
                  </div>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

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