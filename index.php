<?php
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/home_repository.php';

$est_connecte = isset($_SESSION['utilisateur_id']);

$slides     = home_get_slides($connexion);
$homeText   = home_get_text($connexion);
$categories = home_get_categories($connexion);
$featured   = home_get_featured_products($connexion, 8);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — Accueil</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #0b1020; }
    .hero { background: linear-gradient(135deg, #1a2980 0%, #26d0ce 100%); }
    .section-title { color: #fff; }
    .muted-on-dark { color: rgba(255,255,255,.75); }
    .card-img-top { object-fit: cover; height: 160px; }
    .cat-img { height: 140px; object-fit: cover; }
    .lift:hover { transform: translateY(-4px); transition: .2s ease; box-shadow: 0 10px 25px rgba(0,0,0,.15); }

    .carousel-slide { height: 280px; position: relative; }
    .carousel-slide img { height: 100%; width: 100%; object-fit: cover; }
    .carousel-overlay {
      position: absolute; inset: 0;
      background: rgba(0,0,0,.45); z-index: 1;
    }
    .carousel-caption {
      position: absolute; z-index: 2;
      color: #fff; bottom: 20%; left: 8%; right: 8%;
    }
    .carousel-caption h2 { text-shadow: 0 2px 8px rgba(0,0,0,.8); }
    .carousel-caption p  { text-shadow: 0 1px 4px rgba(0,0,0,.7); }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
  <div class="container">
    <a class="navbar-brand fw-semibold" href="index.php">CYNA</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">
      <form class="d-flex ms-lg-3 my-2 my-lg-0 w-100" action="public/recherche.php" method="GET">
        <input class="form-control me-2" type="search" name="q"
               placeholder="Rechercher un service (SOC, EDR, XDR...)">
        <button class="btn btn-outline-info" type="submit">Rechercher</button>
      </form>

      <ul class="navbar-nav ms-lg-3">
        <li class="nav-item">
          <a class="nav-link" href="public/panier.php">Panier</a>
        </li>
        <?php if (!$est_connecte): ?>
          <li class="nav-item">
            <a class="nav-link" href="public/connexion.php">Connexion</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="public/inscription.php">Inscription</a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a class="nav-link" href="public/mon-compte.php">Mon compte</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="public/deconnexion.php">Déconnexion</a>
          </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>

<!-- HERO / CAROUSEL -->
<section class="hero">
  <div class="container py-4 py-lg-5">

    <?php if (count($slides) > 0): ?>
      <div id="homeCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel">

        <div class="carousel-indicators">
          <?php foreach ($slides as $i => $s): ?>
            <button type="button" data-bs-target="#homeCarousel"
                    data-bs-slide-to="<?= $i ?>"
                    class="<?= $i === 0 ? 'active' : '' ?>"
                    aria-label="Slide <?= $i + 1 ?>"></button>
          <?php endforeach; ?>
        </div>

        <div class="carousel-inner rounded-4 overflow-hidden">
          <?php foreach ($slides as $i => $s): ?>
            <!-- ✅ Une seule balise carousel-item (bug doublon corrigé) -->
            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
              <div class="carousel-slide">
                <img src="<?= htmlspecialchars(asset_image($s['image_path'] ?? null)) ?>"
                     class="d-block w-100"
                     alt="<?= htmlspecialchars($s['title']) ?>">
                <div class="carousel-overlay"></div>
                <div class="carousel-caption text-start">
                  <h2 class="fw-bold"><?= htmlspecialchars($s['title']) ?></h2>
                  <?php if (!empty($s['subtitle'])): ?>
                    <p><?= htmlspecialchars($s['subtitle']) ?></p>
                  <?php endif; ?>
                  <?php if (!empty($s['link_url'])): ?>
                    <a class="btn btn-info" href="<?= htmlspecialchars($s['link_url']) ?>">Découvrir</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <button class="carousel-control-prev" type="button"
                data-bs-target="#homeCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon"></span>
          <span class="visually-hidden">Précédent</span>
        </button>
        <button class="carousel-control-next" type="button"
                data-bs-target="#homeCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon"></span>
          <span class="visually-hidden">Suivant</span>
        </button>
      </div>

    <?php else: ?>
      <div class="p-4 bg-dark text-white rounded-4">
        <h2 class="mb-1">CYNA</h2>
        <p class="mb-0 muted-on-dark">Sécurisez votre infrastructure avec nos solutions SaaS.</p>
      </div>
    <?php endif; ?>

    <?php if (!empty($homeText)): ?>
      <div class="mt-4 p-3 p-lg-4 bg-dark text-white rounded-4">
        <p class="mb-0"><?= nl2br(htmlspecialchars($homeText)) ?></p>
      </div>
    <?php endif; ?>

  </div>
</section>

<!-- CATÉGORIES -->
<?php if (count($categories) > 0): ?>
<section class="container py-4 py-lg-5">
  <h3 class="section-title mb-3">Catégories</h3>
  <div class="row g-3">
    <?php foreach ($categories as $cat): ?>
      <div class="col-12 col-md-6 col-lg-4">
        <a class="text-decoration-none"
           href="public/catalogue.php?category_id=<?= (int)$cat['id'] ?>">
          <div class="card border-0 shadow-sm h-100 lift">
            <img class="card-img-top cat-img"
                 src="<?= htmlspecialchars(asset_image($cat['image_path'] ?? null)) ?>"
                 alt="<?= htmlspecialchars($cat['name']) ?>">
            <div class="card-body text-center">
              <h4 class="card-title text-dark fw-bold mb-0">
                <?= htmlspecialchars($cat['name']) ?>
              </h4>
            </div>
          </div>
        </a>
      </div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- TOP PRODUITS -->
<section class="container pb-4 pb-lg-5">
  <h3 class="section-title mb-3">Les Top Produits du moment</h3>
  <?php if (count($featured) === 0): ?>
    <div class="alert alert-secondary">Aucun produit mis en avant pour le moment.</div>
  <?php else: ?>
    <div class="row g-3">
      <?php foreach ($featured as $p): ?>
        <div class="col-12 col-md-6 col-lg-3">
          <a class="text-decoration-none"
             href="public/produit.php?id=<?= (int)$p['id'] ?>">
            <div class="card border-0 shadow-sm h-100 lift">
              <img class="card-img-top"
                   src="<?= htmlspecialchars(asset_image($p['image_path'] ?? null)) ?>"
                   alt="<?= htmlspecialchars($p['name']) ?>">
              <div class="card-body">
                <h6 class="card-title mb-2"><?= htmlspecialchars($p['name']) ?></h6>
                <div class="small text-muted">
                  Mensuel : <?= number_format((float)$p['price_monthly'], 2, ',', ' ') ?> €<br>
                  Annuel  : <?= number_format((float)$p['price_yearly'],  2, ',', ' ') ?> €
                </div>
              </div>
              <div class="card-footer bg-white border-0 pt-0">
                <span class="btn btn-sm btn-outline-info w-100">Voir l'offre</span>
              </div>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<!-- FOOTER -->
<footer class="bg-dark text-white mt-4">
  <div class="container py-4">
    <div class="row g-3">
      <div class="col-12 col-md-6 col-lg-3">
        <div class="fw-semibold mb-2">CYNA</div>
        <div class="text-white-50 small">Solutions SaaS de cybersécurité</div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="fw-semibold mb-2">Informations</div>
        <div><a class="text-white-50" href="public/mentions-legales.php">Mentions légales</a></div>
        <div><a class="text-white-50" href="public/cgu.php">CGU</a></div>
      </div>
      <div class="col-6 col-lg-3">
        <div class="fw-semibold mb-2">Support</div>
        <div><a class="text-white-50" href="public/contact.php">Contact</a></div>
      </div>
      <div class="col-12 col-lg-3">
        <div class="fw-semibold mb-2">Réseaux</div>
        <div class="d-flex gap-3">
          <a class="text-white-50" href="#">LinkedIn</a>
          <a class="text-white-50" href="#">X</a>
          <a class="text-white-50" href="#">Facebook</a>
        </div>
      </div>
    </div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>