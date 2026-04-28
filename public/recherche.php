<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/home_repository.php';

// ── PARAMÈTRES ────────────────────────────────────────────
$q           = trim($_GET['q']           ?? '');
$cat_id      = (int)($_GET['cat_id']     ?? 0);
$price_min   = isset($_GET['price_min']) && $_GET['price_min'] !== '' ? (float)$_GET['price_min'] : null;
$price_max   = isset($_GET['price_max']) && $_GET['price_max'] !== '' ? (float)$_GET['price_max'] : null;
$dispo_only  = isset($_GET['dispo']) && $_GET['dispo'] === '1';
$sort        = $_GET['sort'] ?? 'pertinence';

// ── CATÉGORIES pour filtre ────────────────────────────────
$categories = $connexion->query("SELECT id, name FROM categories ORDER BY sort_order ASC")->fetchAll();

// ── REQUÊTE PRINCIPALE ────────────────────────────────────
$where  = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = "(p.name LIKE ?)";
    $params[] = "%$q%";
}
if ($cat_id > 0) {
    $where[] = "p.category_id = ?";
    $params[] = $cat_id;
}
if ($price_min !== null) {
    $where[] = "p.price_monthly >= ?";
    $params[] = $price_min;
}
if ($price_max !== null) {
    $where[] = "p.price_monthly <= ?";
    $params[] = $price_max;
}
if ($dispo_only) {
    $where[] = "p.is_available = 1";
}

// TRI
switch ($sort) {
    case 'price_asc':  $order_sql = "p.price_monthly ASC"; break;
    case 'price_desc': $order_sql = "p.price_monthly DESC"; break;
    case 'newest':     $order_sql = "p.id DESC"; break;
    case 'dispo':      $order_sql = "p.is_available DESC, p.price_monthly ASC"; break;
    default:           $order_sql = "p.is_available DESC, p.is_featured DESC, p.featured_order ASC, p.price_monthly ASC";
}

$sql = "
    SELECT p.*, c.name AS cat_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY $order_sql
";

$stmt = $connexion->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Prix min/max global pour slider
$minmax = $connexion->query("SELECT MIN(price_monthly), MAX(price_monthly) FROM products WHERE is_available=1")->fetch(PDO::FETCH_NUM);
$global_min = (float)($minmax[0] ?? 0);
$global_max = (float)($minmax[1] ?? 9999);

// Session pour navbar
$est_connecte = isset($_SESSION['utilisateur_id']);
$nb_panier    = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — Recherche<?= $q !== '' ? ' : '.htmlspecialchars($q) : '' ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue:#1a2980; --cyan:#26d0ce;
      --grad:linear-gradient(135deg,#1a2980,#26d0ce);
      --bg:#0b1020; --surface:#0f1628; --card:#131b2e;
      --border:rgba(255,255,255,.07); --border2:rgba(255,255,255,.12);
      --text:#e8eaf2; --muted:#8b92a8;
    }
    * { box-sizing:border-box; }
    body { background:var(--bg); color:var(--text); font-family:'DM Sans',sans-serif; margin:0; }

    /* NAV */
    .navbar { background:rgba(11,16,32,.95) !important; border-bottom:1px solid var(--border); backdrop-filter:blur(12px); }
    .navbar-brand { font-weight:700; font-size:1.2rem; background:var(--grad); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
    .search-bar { background:rgba(255,255,255,.06); border:1px solid var(--border2); border-radius:10px; color:var(--text); padding:8px 14px; font-size:.88rem; flex:1; outline:none; font-family:'DM Sans',sans-serif; }
    .search-bar:focus { border-color:var(--cyan); box-shadow:0 0 0 3px rgba(38,208,206,.12); background:rgba(255,255,255,.08); }
    .search-bar::placeholder { color:var(--muted); }
    .btn-search { background:var(--grad); color:#fff; border:none; border-radius:10px; padding:8px 18px; font-size:.88rem; font-weight:600; cursor:pointer; white-space:nowrap; font-family:'DM Sans',sans-serif; }

    /* LAYOUT */
    .page { max-width:1280px; margin:0 auto; padding:28px 16px; display:flex; gap:24px; align-items:flex-start; }

    /* SIDEBAR FILTRES */
    .filters {
      width:260px; flex-shrink:0;
      background:var(--surface); border:1px solid var(--border);
      border-radius:16px; padding:20px; position:sticky; top:72px;
    }
    .filters h2 { font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:1px; color:var(--muted); margin:0 0 16px; }
    .filter-section { margin-bottom:24px; padding-bottom:24px; border-bottom:1px solid var(--border); }
    .filter-section:last-child { border-bottom:none; margin-bottom:0; padding-bottom:0; }
    .filter-title { font-size:.78rem; font-weight:600; color:var(--muted); text-transform:uppercase; letter-spacing:.8px; margin-bottom:10px; }

    .filter-input {
      width:100%; background:rgba(255,255,255,.05); border:1px solid var(--border2);
      border-radius:8px; padding:8px 11px; font-size:.83rem; color:var(--text);
      font-family:'DM Sans',sans-serif; outline:none; transition:border-color .15s;
    }
    .filter-input:focus { border-color:var(--cyan); box-shadow:0 0 0 2px rgba(38,208,206,.1); }
    .filter-input::placeholder { color:var(--muted); }

    .cat-option {
      display:flex; align-items:center; gap:8px; padding:6px 0;
      cursor:pointer; font-size:.83rem; color:var(--muted);
      transition:color .15s; border-radius:6px;
    }
    .cat-option input[type=radio] { accent-color:var(--cyan); }
    .cat-option:hover { color:var(--text); }
    .cat-option.active-cat { color:var(--text); font-weight:500; }

    .toggle-dispo {
      display:flex; align-items:center; justify-content:space-between;
      padding:8px 12px; background:rgba(255,255,255,.04);
      border:1px solid var(--border2); border-radius:8px; cursor:pointer;
      font-size:.83rem; color:var(--muted); transition:all .15s;
    }
    .toggle-dispo:hover { background:rgba(38,208,206,.08); border-color:rgba(38,208,206,.2); color:var(--text); }
    .toggle-dispo input { accent-color:var(--cyan); width:16px; height:16px; }

    .btn-reset { width:100%; background:rgba(255,255,255,.05); border:1px solid var(--border2); color:var(--muted); border-radius:8px; padding:8px; font-size:.78rem; cursor:pointer; font-family:'DM Sans',sans-serif; transition:all .15s; }
    .btn-reset:hover { background:rgba(255,255,255,.08); color:var(--text); }

    /* RÉSULTATS */
    .results { flex:1; min-width:0; }
    .results-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; gap:12px; flex-wrap:wrap; }
    .results-title { font-size:1.05rem; font-weight:600; }
    .results-count { font-size:.8rem; color:var(--muted); }
    .sort-select { background:rgba(255,255,255,.05); border:1px solid var(--border2); border-radius:8px; padding:7px 12px; font-size:.82rem; color:var(--text); font-family:'DM Sans',sans-serif; outline:none; cursor:pointer; }
    .sort-select option { background:#0f1628; }

    /* PRODUCT CARDS */
    .products-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:16px; }

    .prod-card {
      background:var(--card); border:1px solid var(--border);
      border-radius:14px; overflow:hidden; text-decoration:none; color:var(--text);
      transition:border-color .2s, transform .15s, box-shadow .2s;
      display:flex; flex-direction:column;
    }
    .prod-card:hover { border-color:rgba(38,208,206,.3); transform:translateY(-3px); box-shadow:0 12px 40px rgba(0,0,0,.3); color:var(--text); }
    .prod-card.unavail { opacity:.55; }
    .prod-card.unavail:hover { border-color:var(--border); transform:none; }

    .prod-img { height:140px; object-fit:cover; width:100%; display:block; }
    .prod-img-placeholder { height:140px; background:linear-gradient(135deg,rgba(26,41,128,.4),rgba(38,208,206,.2)); display:flex; align-items:center; justify-content:center; font-size:2rem; }

    .prod-body { padding:14px 16px; flex:1; display:flex; flex-direction:column; gap:6px; }
    .prod-cat { font-size:.67rem; font-weight:600; text-transform:uppercase; letter-spacing:.8px; color:var(--cyan); }
    .prod-name { font-size:.95rem; font-weight:600; color:#fff; line-height:1.3; }
    .prod-price { font-size:.82rem; color:var(--muted); margin-top:2px; }
    .prod-price b { color:var(--text); font-size:.9rem; }
    .prod-foot { padding:10px 16px; border-top:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
    .badge-avail   { font-size:.67rem; font-weight:600; padding:3px 9px; border-radius:20px; background:rgba(34,197,94,.12); color:#4ade80; border:1px solid rgba(34,197,94,.2); }
    .badge-unavail { font-size:.67rem; font-weight:600; padding:3px 9px; border-radius:20px; background:rgba(239,68,68,.12); color:#f87171; border:1px solid rgba(239,68,68,.2); }
    .prod-cta { font-size:.75rem; font-weight:600; color:var(--cyan); }

    /* EMPTY */
    .empty { text-align:center; padding:64px 24px; color:var(--muted); }
    .empty .ico { font-size:3rem; margin-bottom:16px; opacity:.3; }
    .empty h3 { font-size:1.1rem; font-weight:600; color:var(--text); margin-bottom:8px; }
    .empty p { font-size:.85rem; line-height:1.6; }

    /* HIGHLIGHT recherche */
    mark { background:rgba(38,208,206,.25); color:var(--cyan); border-radius:3px; padding:0 2px; }

    /* MOBILE */
    @media (max-width:768px) {
      .filters { display:none; }
      .filters.show { display:block; position:fixed; inset:0; z-index:500; overflow-y:auto; border-radius:0; }
      .mobile-filter-btn { display:flex !important; }
      .page { padding:16px; }
    }
    .mobile-filter-btn { display:none; }
  </style>
</head>
<body>

<!-- ═══ NAVBAR ═══ -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container-fluid px-3 px-lg-4">
    <a class="navbar-brand" href="../index.php">CYNA</a>

    <form class="d-flex gap-2 flex-grow-1 mx-3" action="recherche.php" method="GET">
      <input class="search-bar" type="search" name="q"
             value="<?= htmlspecialchars($q) ?>"
             placeholder="Rechercher un service (SOC, EDR, XDR…)">
      <?php if ($cat_id):  ?><input type="hidden" name="cat_id" value="<?= $cat_id ?>"><?php endif; ?>
      <?php if ($dispo_only): ?><input type="hidden" name="dispo" value="1"><?php endif; ?>
      <button class="btn-search" type="submit">Rechercher</button>
    </form>

    <div class="d-flex align-items-center gap-2">
      <a href="panier.php" class="btn btn-outline-light btn-sm" style="font-size:.8rem">
        🛒 <?= $nb_panier > 0 ? "($nb_panier)" : '' ?> Panier
      </a>
      <?php if ($est_connecte): ?>
        <a href="mon-compte.php" class="btn btn-outline-info btn-sm" style="font-size:.8rem">Mon compte</a>
      <?php else: ?>
        <a href="connexion.php" class="btn btn-outline-info btn-sm" style="font-size:.8rem">Connexion</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- ═══ BODY ═══ -->
<div class="page">

  <!-- SIDEBAR FILTRES -->
  <aside class="filters" id="filters-panel">
    <form method="GET" action="recherche.php" id="filter-form">
      <?php if ($q !== ''): ?>
        <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
      <?php endif; ?>
      <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>">

      <h2>Filtres</h2>

      <!-- Catégorie -->
      <div class="filter-section">
        <div class="filter-title">Catégorie</div>
        <label class="cat-option <?= $cat_id===0?'active-cat':'' ?>">
          <input type="radio" name="cat_id" value="0" <?= $cat_id===0?'checked':'' ?> onchange="this.form.submit()">
          Toutes les catégories
        </label>
        <?php foreach ($categories as $cat): ?>
          <label class="cat-option <?= $cat_id===(int)$cat['id']?'active-cat':'' ?>">
            <input type="radio" name="cat_id" value="<?= (int)$cat['id'] ?>"
                   <?= $cat_id===(int)$cat['id']?'checked':'' ?> onchange="this.form.submit()">
            <?= htmlspecialchars($cat['name']) ?>
          </label>
        <?php endforeach; ?>
      </div>

      <!-- Prix -->
      <div class="filter-section">
        <div class="filter-title">Prix mensuel (€)</div>
        <div class="d-flex gap-2">
          <input class="filter-input" type="number" name="price_min" placeholder="Min"
                 value="<?= $price_min !== null ? $price_min : '' ?>" min="0" step="1" style="width:50%">
          <input class="filter-input" type="number" name="price_max" placeholder="Max"
                 value="<?= $price_max !== null ? $price_max : '' ?>" min="0" step="1" style="width:50%">
        </div>
        <button type="submit" style="width:100%;margin-top:8px;background:var(--grad);border:none;color:#fff;border-radius:8px;padding:7px;font-size:.78rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;">
          Appliquer
        </button>
      </div>

      <!-- Disponibilité -->
      <div class="filter-section">
        <div class="filter-title">Disponibilité</div>
        <label class="toggle-dispo">
          <span>Services disponibles uniquement</span>
          <input type="checkbox" name="dispo" value="1" <?= $dispo_only?'checked':'' ?> onchange="this.form.submit()">
        </label>
      </div>

      <!-- Reset -->
      <a href="recherche.php" class="btn-reset">Réinitialiser les filtres</a>
    </form>
  </aside>

  <!-- RÉSULTATS -->
  <main class="results">

    <!-- Header résultats -->
    <div class="results-header">
      <div>
        <div class="results-title">
          <?php if ($q !== ''): ?>
            Résultats pour <span style="color:var(--cyan)">"<?= htmlspecialchars($q) ?>"</span>
          <?php else: ?>
            Tous les services
          <?php endif; ?>
        </div>
        <div class="results-count"><?= count($products) ?> résultat(s) trouvé(s)</div>
      </div>

      <div class="d-flex align-items-center gap-2">
        <!-- Bouton filtres mobile -->
        <button class="mobile-filter-btn btn btn-sm btn-outline-light" onclick="document.getElementById('filters-panel').classList.toggle('show')">
          ⚙ Filtres
        </button>

        <!-- Tri -->
        <form method="GET" action="recherche.php" id="sort-form">
          <?php if ($q !== ''):  ?><input type="hidden" name="q"        value="<?= htmlspecialchars($q) ?>"><?php endif; ?>
          <?php if ($cat_id):    ?><input type="hidden" name="cat_id"   value="<?= $cat_id ?>"><?php endif; ?>
          <?php if ($price_min): ?><input type="hidden" name="price_min" value="<?= $price_min ?>"><?php endif; ?>
          <?php if ($price_max): ?><input type="hidden" name="price_max" value="<?= $price_max ?>"><?php endif; ?>
          <?php if ($dispo_only): ?><input type="hidden" name="dispo"   value="1"><?php endif; ?>
          <select class="sort-select" name="sort" onchange="document.getElementById('sort-form').submit()">
            <option value="pertinence" <?= $sort==='pertinence'?'selected':'' ?>>Pertinence</option>
            <option value="price_asc"  <?= $sort==='price_asc' ?'selected':'' ?>>Prix croissant</option>
            <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Prix décroissant</option>
            <option value="newest"     <?= $sort==='newest'    ?'selected':'' ?>>Plus récents</option>
            <option value="dispo"      <?= $sort==='dispo'     ?'selected':'' ?>>Disponibilité</option>
          </select>
        </form>
      </div>
    </div>

    <!-- Tags filtres actifs -->
    <?php if ($cat_id || $price_min !== null || $price_max !== null || $dispo_only): ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
      <?php if ($cat_id): ?>
        <?php
        $catName = array_filter($categories, function($c) use ($cat_id) { return (int)$c['id'] === $cat_id; });
        ?>
        <?php if ($catName): ?>
          <span style="background:rgba(38,208,206,.12);color:var(--cyan);border:1px solid rgba(38,208,206,.2);padding:3px 10px;border-radius:20px;font-size:.73rem;font-weight:500">
            📁 <?= htmlspecialchars(array_values($catName)[0]['name']) ?>
          </span>
        <?php endif; ?>
      <?php endif; ?>
      <?php if ($price_min !== null): ?>
        <span style="background:rgba(79,140,255,.12);color:#93c5fd;border:1px solid rgba(79,140,255,.2);padding:3px 10px;border-radius:20px;font-size:.73rem;font-weight:500">
          € min : <?= $price_min ?> €
        </span>
      <?php endif; ?>
      <?php if ($price_max !== null): ?>
        <span style="background:rgba(79,140,255,.12);color:#93c5fd;border:1px solid rgba(79,140,255,.2);padding:3px 10px;border-radius:20px;font-size:.73rem;font-weight:500">
          € max : <?= $price_max ?> €
        </span>
      <?php endif; ?>
      <?php if ($dispo_only): ?>
        <span style="background:rgba(34,197,94,.12);color:#4ade80;border:1px solid rgba(34,197,94,.2);padding:3px 10px;border-radius:20px;font-size:.73rem;font-weight:500">
          ✅ Disponibles seulement
        </span>
      <?php endif; ?>
      <a href="recherche.php<?= $q!==''?'?q='.urlencode($q):'' ?>" style="color:var(--muted);font-size:.73rem;padding:3px 8px;border-radius:20px;text-decoration:none;border:1px solid var(--border2)">
        ✕ Effacer
      </a>
    </div>
    <?php endif; ?>

    <!-- Grille produits -->
    <?php if (count($products) === 0): ?>
      <div class="empty">
        <div class="ico">🔍</div>
        <h3>Aucun résultat</h3>
        <p>
          <?php if ($q !== ''): ?>
            Aucun service ne correspond à <strong>"<?= htmlspecialchars($q) ?>"</strong>.<br>
            Essayez un autre terme ou supprimez les filtres.
          <?php else: ?>
            Aucun service ne correspond aux filtres sélectionnés.
          <?php endif; ?>
        </p>
        <a href="recherche.php" style="color:var(--cyan);font-size:.85rem">Voir tous les services →</a>
      </div>
    <?php else: ?>
      <div class="products-grid">
        <?php foreach ($products as $p):
          $avail = (int)$p['is_available'] === 1;
          // Highlight recherche dans le nom
          $name = htmlspecialchars($p['name']);
          if ($q !== '') {
            $name = preg_replace('/('.preg_quote(htmlspecialchars($q),'/').')/i', '<mark>$1</mark>', $name);
          }
        ?>
        <a href="produit.php?id=<?= (int)$p['id'] ?>" class="prod-card <?= $avail?'':'unavail' ?>">
          <?php if (!empty($p['image_path']) && $p['image_path'] !== 'logo.jpg'): ?>
            <img class="prod-img" src="../<?= htmlspecialchars($p['image_path']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
          <?php else: ?>
            <div class="prod-img-placeholder">🛡</div>
          <?php endif; ?>

          <div class="prod-body">
            <?php if ($p['cat_name']): ?>
              <div class="prod-cat"><?= htmlspecialchars($p['cat_name']) ?></div>
            <?php endif; ?>
            <div class="prod-name"><?= $name ?></div>
            <div class="prod-price">
              À partir de <b><?= number_format((float)$p['price_monthly'],2,',',' ') ?> € /mois</b>
              <?php if ((float)$p['price_yearly'] > 0): ?>
                · <span style="color:var(--cyan)"><?= number_format((float)$p['price_yearly'],0,',',' ') ?> € /an</span>
              <?php endif; ?>
            </div>
          </div>

          <div class="prod-foot">
            <?= $avail
              ? '<span class="badge-avail">● Disponible</span>'
              : '<span class="badge-unavail">● Indisponible</span>' ?>
            <span class="prod-cta">
              <?= $avail ? "Voir l'offre →" : "Voir quand même →" ?>
            </span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </main>
</div>

<!-- Footer -->
<footer style="border-top:1px solid var(--border);margin-top:48px;padding:24px 16px;text-align:center;color:var(--muted);font-size:.78rem;">
  <div style="max-width:1280px;margin:0 auto;display:flex;justify-content:center;gap:24px;flex-wrap:wrap">
    <a href="mention_legales.php" style="color:var(--muted);text-decoration:none">Mentions légales</a>
    <a href="Cgu.php"             style="color:var(--muted);text-decoration:none">CGU</a>
    <a href="Contact.php"         style="color:var(--muted);text-decoration:none">Contact</a>
    <a href="a-propos.php"        style="color:var(--muted);text-decoration:none">À propos</a>
    <span>© 2025 CYNA-IT</span>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Fermer panneau filtres mobile en cliquant dehors
  document.addEventListener('click', function(e) {
    const panel = document.getElementById('filters-panel');
    if (panel.classList.contains('show') && !panel.contains(e.target) && !e.target.closest('.mobile-filter-btn')) {
      panel.classList.remove('show');
    }
  });
</script>
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
</body>
</html>