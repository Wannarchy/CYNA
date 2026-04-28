<?php
session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/home_repository.php';

$est_connecte = isset($_SESSION['utilisateur_id']);
$nb_panier    = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));

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
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue: #1a2980;
      --cyan: #26d0ce;
      --grad: linear-gradient(135deg, #1a2980, #26d0ce);
      --bg: #0b1020;
      --card: #0f1628;
      --border: rgba(255,255,255,.07);
      --muted: rgba(255,255,255,.45);
    }
    * { box-sizing: border-box; }
    body { background: var(--bg); color: #e8eaf2; font-family: 'DM Sans', sans-serif; margin: 0; }

    /* ── NAVBAR ── */
    .navbar {
      background: rgba(11,16,32,.97) !important;
      border-bottom: 1px solid var(--border);
      backdrop-filter: blur(14px);
      padding: 0 0;
      height: 62px;
    }
    .navbar .container { height: 62px; align-items: center; }
    .navbar-brand {
      font-weight: 900;
      font-size: 1.3rem;
      letter-spacing: -0.5px;
      background: var(--grad);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      padding: 0;
      margin-right: 24px;
      flex-shrink: 0;
    }

    /* Search */
    .search-wrap {
      flex: 1;
      max-width: 540px;
      position: relative;
    }
    .search-wrap input {
      width: 100%;
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 10px;
      padding: 9px 16px 9px 42px;
      font-size: .87rem;
      color: #e8eaf2;
      font-family: 'DM Sans', sans-serif;
      outline: none;
      transition: border-color .15s, background .15s;
    }
    .search-wrap input::placeholder { color: #4a5168; }
    .search-wrap input:focus {
      border-color: rgba(38,208,206,.4);
      background: rgba(255,255,255,.09);
      box-shadow: 0 0 0 3px rgba(38,208,206,.08);
    }
    .search-wrap .search-icon {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #4a5168;
      font-size: .9rem;
      pointer-events: none;
    }
    .search-btn {
      position: absolute;
      right: 6px;
      top: 50%;
      transform: translateY(-50%);
      background: var(--grad);
      border: none;
      border-radius: 7px;
      color: #fff;
      padding: 5px 14px;
      font-size: .78rem;
      font-weight: 700;
      cursor: pointer;
      font-family: 'DM Sans', sans-serif;
      transition: opacity .15s;
    }
    .search-btn:hover { opacity: .85; }

    /* Nav links */
    .nav-links {
      display: flex;
      align-items: center;
      gap: 4px;
      margin-left: 16px;
      flex-shrink: 0;
    }
    .nav-links a {
      color: rgba(255,255,255,.6);
      text-decoration: none;
      font-size: .83rem;
      font-weight: 500;
      padding: 6px 12px;
      border-radius: 8px;
      transition: color .15s, background .15s;
      white-space: nowrap;
    }
    .nav-links a:hover { color: #fff; background: rgba(255,255,255,.06); }

    /* Panier pill */
    .cart-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(38,208,206,.1);
      border: 1px solid rgba(38,208,206,.2);
      color: #26d0ce !important;
      border-radius: 20px;
      padding: 5px 14px !important;
      font-weight: 600 !important;
      font-size: .8rem !important;
      transition: background .15s !important;
    }
    .cart-btn:hover { background: rgba(38,208,206,.2) !important; }
    .cart-count {
      background: #26d0ce;
      color: #0b1020;
      border-radius: 50%;
      width: 18px;
      height: 18px;
      font-size: .65rem;
      font-weight: 800;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* CTA btn */
    .btn-cyna {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: var(--grad);
      color: #fff !important;
      border: none;
      border-radius: 9px;
      padding: 7px 16px !important;
      font-size: .82rem !important;
      font-weight: 700 !important;
      text-decoration: none;
      transition: opacity .15s !important;
    }
    .btn-cyna:hover { opacity: .85; }

    /* Mobile toggler */
    .navbar-toggler {
      border: 1px solid rgba(255,255,255,.12);
      padding: 5px 8px;
    }
    .navbar-toggler-icon {
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba(255,255,255,.7)' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
    }

    /* ── HERO / CAROUSEL ── */
    .hero-section {
      background: linear-gradient(180deg, #0d1a3a 0%, #080d1c 100%);
      padding: 32px 0 0;
    }
    .carousel-slide { height: 340px; position: relative; border-radius: 16px; overflow: hidden; }
    .carousel-slide img { height: 100%; width: 100%; object-fit: cover; }
    .carousel-overlay {
      position: absolute; inset: 0;
      background: linear-gradient(90deg, rgba(11,16,32,.85) 0%, rgba(11,16,32,.3) 100%);
      z-index: 1;
    }
    .carousel-caption {
      position: absolute; z-index: 2;
      color: #fff; bottom: 20%; left: 6%; right: 40%;
      text-align: left;
    }
    .carousel-caption h2 { font-size: clamp(1.3rem,3vw,2rem); font-weight: 800; line-height: 1.2; margin-bottom: 8px; }
    .carousel-caption p  { font-size: .9rem; color: rgba(255,255,255,.7); margin-bottom: 16px; }
    .carousel-caption .btn {
      background: var(--grad); color: #fff; border: none;
      border-radius: 10px; padding: 10px 22px; font-weight: 700;
      font-family: 'DM Sans', sans-serif; font-size: .88rem;
    }
    .carousel-indicators button {
      width: 24px; height: 3px; border-radius: 2px;
      background: rgba(255,255,255,.4); border: none; margin: 0 3px;
    }
    .carousel-indicators button.active { background: var(--cyan); width: 40px; }
    .carousel-control-prev, .carousel-control-next {
      width: 40px; height: 40px; top: 50%; transform: translateY(-50%);
      background: rgba(255,255,255,.1); border-radius: 50%; margin: 0 8px;
    }

    /* Home text */
    .home-text {
      margin-top: 20px;
      background: rgba(255,255,255,.04);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 18px 24px;
      font-size: .9rem;
      color: rgba(255,255,255,.6);
      line-height: 1.6;
    }

    /* ── SECTIONS ── */
    .section { padding: 52px 0; }
    .section-label {
      font-size: .7rem; font-weight: 700; letter-spacing: 1.5px;
      text-transform: uppercase; color: var(--cyan); margin-bottom: 6px;
    }
    .section-title {
      font-size: clamp(1.3rem,2.5vw,1.8rem);
      font-weight: 800; color: #fff; margin-bottom: 28px;
      line-height: 1.2;
    }

    /* ── CATEGORY CARDS ── */
    .cat-card {
      display: block;
      text-decoration: none;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
      transition: border-color .2s, transform .2s;
      height: 100%;
    }
    .cat-card:hover { border-color: rgba(38,208,206,.3); transform: translateY(-4px); }
    .cat-card-img {
      width: 100%; height: 150px; object-fit: cover;
      display: block;
    }
    .cat-card-body {
      padding: 14px 16px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .cat-card-name {
      font-size: .92rem; font-weight: 700; color: #fff;
    }
    .cat-card-arrow {
      color: var(--cyan); font-size: 1rem; font-weight: 700;
    }

    /* ── PRODUCT CARDS ── */
    .prod-card {
      display: block;
      text-decoration: none;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 16px;
      overflow: hidden;
      transition: border-color .2s, transform .2s;
      height: 100%;
    }
    .prod-card:hover { border-color: rgba(38,208,206,.25); transform: translateY(-4px); }
    .prod-card-img {
      width: 100%; height: 140px; object-fit: cover; display: block;
    }
    .prod-card-body { padding: 14px 16px; }
    .prod-card-name {
      font-size: .88rem; font-weight: 700; color: #fff;
      margin-bottom: 8px; line-height: 1.3;
    }
    .prod-price-badge {
      display: inline-flex;
      align-items: baseline;
      gap: 3px;
    }
    .prod-price-badge .amount {
      font-size: 1rem; font-weight: 800;
      background: var(--grad);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .prod-price-badge .period {
      font-size: .7rem; color: var(--muted);
    }
    .prod-card-footer {
      padding: 10px 16px;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .prod-available {
      font-size: .68rem; font-weight: 600; color: #4ade80;
      background: rgba(74,222,128,.1); border-radius: 20px;
      padding: 2px 8px; border: 1px solid rgba(74,222,128,.2);
    }
    .prod-unavailable {
      font-size: .68rem; font-weight: 600; color: #f87171;
      background: rgba(248,113,113,.1); border-radius: 20px;
      padding: 2px 8px; border: 1px solid rgba(248,113,113,.2);
    }
    .prod-cta {
      font-size: .75rem; font-weight: 700; color: var(--cyan);
    }

    /* ── FOOTER ── */
    footer {
      border-top: 1px solid var(--border);
      padding: 40px 0 24px;
      margin-top: 20px;
    }
    .footer-brand {
      font-size: 1.1rem; font-weight: 900;
      background: var(--grad);
      -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
      margin-bottom: 6px;
    }
    .footer-sub { font-size: .78rem; color: var(--muted); }
    .footer-title { font-size: .75rem; font-weight: 700; color: #fff; text-transform: uppercase; letter-spacing: .5px; margin-bottom: 10px; }
    .footer-link { display: block; font-size: .8rem; color: var(--muted); text-decoration: none; margin-bottom: 6px; transition: color .15s; }
    .footer-link:hover { color: #fff; }
    .footer-bottom {
      border-top: 1px solid var(--border);
      margin-top: 28px;
      padding-top: 18px;
      font-size: .72rem;
      color: rgba(255,255,255,.25);
      text-align: center;
    }

    @media (max-width: 991px) {
      .nav-links { flex-wrap: wrap; margin-left: 0; margin-top: 8px; }
      .search-wrap { max-width: 100%; margin-top: 8px; }
      .carousel-caption { right: 10%; }
    }
  </style>
</head>
<body>

<!-- ══ NAVBAR ══ -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand" href="index.php">CYNA</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="nav">
      <!-- Search -->
      <form class="search-wrap my-2 my-lg-0" action="public/recherche.php" method="GET">
        <span class="search-icon">🔍</span>
        <input type="search" name="q" placeholder="Rechercher un service (SOC, EDR, XDR...)">
        <button class="search-btn" type="submit">Chercher</button>
      </form>

      <!-- Nav links -->
      <div class="nav-links">
        <a href="public/panier.php" class="cart-btn">
          🛒 Panier
          <?php if ($nb_panier > 0): ?>
            <span class="cart-count"><?= $nb_panier ?></span>
          <?php endif; ?>
        </a>

        <?php
      // Lien admin uniquement si connecté ET admin
      $is_admin = false;
      if ($est_connecte) {
          if (!isset($_SESSION['is_admin'])) {
              $stmt_adm = $connexion->prepare("SELECT is_admin FROM utilisateurs WHERE id=? LIMIT 1");
              $stmt_adm->execute([$_SESSION['utilisateur_id']]);
              $row_adm = $stmt_adm->fetch();
              $_SESSION['is_admin'] = $row_adm ? (int)$row_adm['is_admin'] : 0;
          }
          $is_admin = $_SESSION['is_admin'] === 1;
      }
      ?>
      <?php if ($is_admin): ?>
        <a href="admin/index.php" style="display:inline-flex;align-items:center;gap:5px;background:rgba(139,92,246,.15);border:1px solid rgba(139,92,246,.25);color:#a78bfa;border-radius:20px;padding:5px 12px;font-size:.75rem;font-weight:700;text-decoration:none;transition:all .15s"
           onmouseover="this.style.background='rgba(139,92,246,.25)'" onmouseout="this.style.background='rgba(139,92,246,.15)'">
          ⚙ Admin
        </a>
      <?php endif; ?>
      <?php if (!$est_connecte): ?>
        <a href="public/connexion.php">Connexion</a>
        <a href="public/inscription.php" class="btn-cyna">S'inscrire</a>
      <?php else: ?>
        <a href="public/mon-compte.php">Mon compte</a>
        <a href="public/deconnexion.php">Déconnexion</a>
      <?php endif; ?>
      </div>
    </div>
  </div>
</nav>

<!-- ══ HERO / CAROUSEL ══ -->
<section class="hero-section">
  <div class="container">

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
            <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
              <div class="carousel-slide">
                <img src="<?= htmlspecialchars(asset_image($s['image_path'] ?? null)) ?>"
                     alt="<?= htmlspecialchars($s['title']) ?>">
                <div class="carousel-overlay"></div>
                <div class="carousel-caption">
                  <h2><?= htmlspecialchars($s['title']) ?></h2>
                  <?php if (!empty($s['subtitle'])): ?>
                    <p><?= htmlspecialchars($s['subtitle']) ?></p>
                  <?php endif; ?>
                  <?php if (!empty($s['link_url'])): ?>
                    <a class="btn" href="<?= htmlspecialchars($s['link_url']) ?>">Découvrir →</a>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <button class="carousel-control-prev" type="button" data-bs-target="#homeCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#homeCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon"></span>
        </button>
      </div>

    <?php else: ?>
      <div style="background:var(--card);border:1px solid var(--border);border-radius:16px;padding:40px;text-align:center">
        <h2 style="font-weight:800;color:#fff">Bienvenue chez CYNA</h2>
        <p style="color:var(--muted)">Sécurisez votre infrastructure avec nos solutions SaaS.</p>
      </div>
    <?php endif; ?>

    <?php if (!empty($homeText)): ?>
      <div class="home-text"><?= nl2br(htmlspecialchars($homeText)) ?></div>
    <?php endif; ?>

  </div>
</section>

<!-- ══ CATÉGORIES ══ -->
<?php if (count($categories) > 0): ?>
<section class="section" style="background:#080d1c">
  <div class="container">
    <div class="section-label">Explorer</div>
    <div class="section-title">Nos catégories</div>
    <div class="row g-3">
      <?php foreach ($categories as $cat): ?>
        <div class="col-12 col-sm-6 col-lg-4">
          <a class="cat-card" href="public/catalogue.php?category_id=<?= (int)$cat['id'] ?>">
            <img class="cat-card-img"
                 src="<?= htmlspecialchars(asset_image($cat['image_path'] ?? null)) ?>"
                 alt="<?= htmlspecialchars($cat['name']) ?>">
            <div class="cat-card-body">
              <span class="cat-card-name"><?= htmlspecialchars($cat['name']) ?></span>
              <span class="cat-card-arrow">→</span>
            </div>
          </a>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ══ TOP PRODUITS ══ -->
<section class="section">
  <div class="container">
    <div class="section-label">Sélection</div>
    <div class="section-title">Les Top Produits du moment</div>

    <?php if (count($featured) === 0): ?>
      <div style="background:var(--card);border:1px solid var(--border);border-radius:14px;padding:32px;text-align:center;color:var(--muted)">
        Aucun produit mis en avant pour le moment.
      </div>
    <?php else: ?>
      <div class="row g-3">
        <?php foreach ($featured as $p): ?>
          <div class="col-12 col-sm-6 col-lg-3">
            <a class="prod-card" href="public/produit.php?id=<?= (int)$p['id'] ?>">
              <img class="prod-card-img"
                   src="<?= htmlspecialchars(asset_image($p['image_path'] ?? null)) ?>"
                   alt="<?= htmlspecialchars($p['name']) ?>">
              <div class="prod-card-body">
                <div class="prod-card-name"><?= htmlspecialchars($p['name']) ?></div>
                <div class="prod-price-badge">
                  <span class="amount"><?= number_format((float)$p['price_monthly'], 2, ',', ' ') ?> €</span>
                  <span class="period">/ mois</span>
                </div>
              </div>
              <div class="prod-card-footer">
                <span class="prod-available">● Disponible</span>
                <span class="prod-cta">Voir →</span>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ══ FOOTER ══ -->
<footer>
  <div class="container">
    <div class="row g-4">
      <div class="col-12 col-md-4">
        <div class="footer-brand">CYNA</div>
        <div class="footer-sub">Solutions SaaS de cybersécurité<br>pour les entreprises</div>
      </div>
      <div class="col-6 col-md-2">
        <div class="footer-title">Légal</div>
        <a class="footer-link" href="public/mention_legales.php">Mentions légales</a>
        <a class="footer-link" href="public/Cgu.php">CGU</a>
      </div>
      <div class="col-6 col-md-2">
        <div class="footer-title">Support</div>
        <a class="footer-link" href="public/Contact.php">Contact</a>
        <a class="footer-link" href="public/a-propos.php">À propos</a>
      </div>
      <div class="col-6 col-md-2">
        <div class="footer-title">Compte</div>
        <?php if ($est_connecte): ?>
          <a class="footer-link" href="public/mon-compte.php">Mon compte</a>
          <a class="footer-link" href="public/mes-commandes.php">Mes commandes</a>
        <?php else: ?>
          <a class="footer-link" href="public/connexion.php">Connexion</a>
          <a class="footer-link" href="public/inscription.php">S'inscrire</a>
        <?php endif; ?>
      </div>
      <div class="col-6 col-md-2">
        <div class="footer-title">Réseaux</div>
        <a class="footer-link" href="#">LinkedIn</a>
        <a class="footer-link" href="#">X (Twitter)</a>
        <a class="footer-link" href="#">Facebook</a>
      </div>
    </div>
    <div class="footer-bottom">© 2025 CYNA-IT — 10 Rue de Penthièvre, 75008 Paris — SIRET : 91371103200015</div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>