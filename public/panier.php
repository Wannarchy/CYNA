<?php
session_start();
$est_connecte = isset($_SESSION['utilisateur_id']);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/home_repository.php';
require_once __DIR__ . '/../includes/cart_repository.php';

$cart = $_SESSION['cart'] ?? [];

// Suppression produit
if (isset($_GET['remove'])) {
    $rid = (int)$_GET['remove'];
    unset($_SESSION['cart'][$rid]);
    header('Location: panier.php');
    exit;
}

$items     = cart_get_products($connexion, $cart);
$total     = cart_total($items);
$nb_panier = count($items);

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
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — Panier</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue: #1a2980;
      --cyan: #26d0ce;
      --grad: linear-gradient(135deg, #1a2980, #26d0ce);
      --bg: #0b1020;
      --card: #0f1628;
      --card2: #131c30;
      --border: rgba(255,255,255,.07);
      --muted: rgba(255,255,255,.45);
    }
    * { box-sizing: border-box; }
    body { background: var(--bg); color: #e8eaf2; font-family: 'DM Sans', sans-serif; margin: 0; min-height: 100vh; }

    /* NAVBAR */
    .navbar {
      background: rgba(11,16,32,.97) !important;
      border-bottom: 1px solid var(--border);
      backdrop-filter: blur(14px);
      height: 62px;
      padding: 0;
    }
    .navbar .container { height: 62px; align-items: center; }
    .navbar-brand {
      font-weight: 900; font-size: 1.3rem; letter-spacing: -.5px;
      background: var(--grad);
      -webkit-background-clip: text; -webkit-text-fill-color: transparent;
      padding: 0; margin-right: 20px;
    }
    .nav-link-plain {
      color: rgba(255,255,255,.6); text-decoration: none;
      font-size: .85rem; font-weight: 500; padding: 6px 12px;
      border-radius: 8px; transition: all .15s;
    }
    .nav-link-plain:hover { color: #fff; background: rgba(255,255,255,.06); }
    .cart-pill {
      display: inline-flex; align-items: center; gap: 6px;
      background: rgba(38,208,206,.1); border: 1px solid rgba(38,208,206,.2);
      color: #26d0ce; border-radius: 20px; padding: 5px 14px;
      font-size: .8rem; font-weight: 700; text-decoration: none;
    }
    .btn-cyna {
      background: var(--grad); color: #fff; border: none;
      border-radius: 9px; padding: 7px 18px; font-size: .83rem;
      font-weight: 700; cursor: pointer; font-family: 'DM Sans', sans-serif;
      text-decoration: none; transition: opacity .15s; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-cyna:hover { opacity: .85; color: #fff; }
    .btn-outline-cyna {
      background: transparent; color: rgba(255,255,255,.6);
      border: 1px solid rgba(255,255,255,.15); border-radius: 9px;
      padding: 7px 18px; font-size: .83rem; font-weight: 600;
      text-decoration: none; transition: all .15s; display: inline-flex; align-items: center; gap: 6px;
    }
    .btn-outline-cyna:hover { color: #fff; border-color: rgba(255,255,255,.35); }

    /* PAGE */
    .page-wrap { max-width: 900px; margin: 0 auto; padding: 40px 16px; }
    .page-title { font-size: 1.5rem; font-weight: 800; color: #fff; margin-bottom: 6px; }
    .page-sub { font-size: .85rem; color: var(--muted); margin-bottom: 28px; }

    /* EMPTY */
    .empty-cart {
      background: var(--card); border: 1px solid var(--border);
      border-radius: 16px; padding: 60px 24px; text-align: center;
    }
    .empty-icon { font-size: 3rem; margin-bottom: 16px; opacity: .4; }
    .empty-title { font-size: 1.1rem; font-weight: 700; color: #fff; margin-bottom: 8px; }
    .empty-sub { font-size: .88rem; color: var(--muted); margin-bottom: 24px; }

    /* CART ITEMS */
    .cart-list { display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px; }
    .cart-item {
      background: var(--card); border: 1px solid var(--border);
      border-radius: 14px; padding: 18px 20px;
      display: flex; align-items: center; gap: 16px;
      transition: border-color .15s;
    }
    .cart-item:hover { border-color: rgba(255,255,255,.12); }
    .cart-item.unavailable { opacity: .5; }

    .item-img {
      width: 56px; height: 56px; border-radius: 10px;
      object-fit: cover; flex-shrink: 0;
      background: var(--card2);
    }
    .item-info { flex: 1; min-width: 0; }
    .item-name { font-size: .92rem; font-weight: 700; color: #fff; margin-bottom: 4px; }
    .item-unavail {
      display: inline-flex; align-items: center; gap: 4px;
      font-size: .7rem; font-weight: 600; color: #f87171;
      background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.2);
      border-radius: 20px; padding: 2px 8px;
    }

    /* Cycle select */
    .cycle-select {
      background: rgba(255,255,255,.06);
      border: 1px solid rgba(255,255,255,.12);
      border-radius: 8px; padding: 6px 10px;
      font-size: .8rem; color: #e8eaf2;
      font-family: 'DM Sans', sans-serif;
      outline: none; cursor: pointer;
      transition: border-color .15s;
    }
    .cycle-select:focus { border-color: var(--cyan); }
    .cycle-select option { background: #0f1628; }

    /* Price */
    .item-price {
      font-size: 1rem; font-weight: 800; text-align: right;
      background: var(--grad); -webkit-background-clip: text;
      -webkit-text-fill-color: transparent; flex-shrink: 0; min-width: 90px;
    }
    .item-price-sub { font-size: .7rem; color: var(--muted); font-weight: 400; -webkit-text-fill-color: var(--muted); }

    /* Remove btn */
    .btn-remove {
      background: transparent; border: 1px solid rgba(239,68,68,.2);
      color: rgba(239,68,68,.6); border-radius: 8px; padding: 6px 10px;
      font-size: .8rem; cursor: pointer; font-family: 'DM Sans', sans-serif;
      transition: all .15s; flex-shrink: 0; text-decoration: none;
      display: inline-flex; align-items: center; gap: 4px;
    }
    .btn-remove:hover { background: rgba(239,68,68,.1); color: #f87171; border-color: rgba(239,68,68,.4); }

    /* SUMMARY */
    .summary {
      background: var(--card); border: 1px solid var(--border);
      border-radius: 16px; padding: 24px;
    }
    .summary-title { font-size: .75rem; font-weight: 700; text-transform: uppercase;
      letter-spacing: .8px; color: var(--muted); margin-bottom: 16px; }
    .summary-row {
      display: flex; justify-content: space-between; align-items: center;
      font-size: .88rem; color: var(--muted); margin-bottom: 10px;
    }
    .summary-row.total {
      border-top: 1px solid var(--border); padding-top: 14px; margin-top: 6px;
      font-size: 1.1rem; font-weight: 800; color: #fff;
    }
    .summary-total-amount {
      font-size: 1.3rem; font-weight: 800;
      background: var(--grad); -webkit-background-clip: text;
      -webkit-text-fill-color: transparent;
    }
    .summary-note { font-size: .72rem; color: var(--muted); margin-top: 8px; }

    .actions { display: flex; flex-direction: column; gap: 10px; margin-top: 20px; }

    /* SECURE BADGES */
    .secure-badges {
      display: flex; gap: 12px; justify-content: center;
      margin-top: 16px; flex-wrap: wrap;
    }
    .secure-badge {
      display: flex; align-items: center; gap: 5px;
      font-size: .7rem; color: var(--muted);
    }

    /* LOGIN PROMPT */
    .login-prompt {
      background: rgba(38,208,206,.06); border: 1px solid rgba(38,208,206,.15);
      border-radius: 12px; padding: 14px 18px;
      display: flex; align-items: center; justify-content: space-between;
      gap: 12px; margin-bottom: 16px; flex-wrap: wrap;
    }
    .login-prompt-text { font-size: .84rem; color: rgba(255,255,255,.7); }
    .login-prompt-text strong { color: #fff; }

    /* ADMIN BTN */
    .admin-fab {
      position: fixed; bottom: 20px; right: 20px; z-index: 9999;
    }

    footer {
      border-top: 1px solid var(--border);
      padding: 24px 16px; text-align: center;
      color: var(--muted); font-size: .75rem;
      margin-top: 40px;
    }
    footer a { color: rgba(255,255,255,.35); text-decoration: none; margin: 0 10px; }
    footer a:hover { color: rgba(255,255,255,.6); }

    @media (max-width: 640px) {
      .cart-item { flex-wrap: wrap; }
      .item-price { min-width: auto; }
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar sticky-top">
  <div class="container">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <div class="d-flex align-items-center gap-2 ms-auto">
      <a href="catalogue.php" class="nav-link-plain d-none d-md-inline">Catalogue</a>
      <a href="recherche.php" class="nav-link-plain d-none d-md-inline">Services</a>
      <?php if (!$est_connecte): ?>
        <a href="connexion.php" class="nav-link-plain">Connexion</a>
        <a href="inscription.php" class="btn-cyna">S'inscrire</a>
      <?php else: ?>
        <a href="mon-compte.php" class="nav-link-plain">Mon compte</a>
        <a href="deconnexion.php" class="nav-link-plain">Déconnexion</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="page-wrap">

  <!-- BREADCRUMB -->
  <div style="font-size:.75rem;color:var(--muted);margin-bottom:20px">
    <a href="../index.php" style="color:var(--muted);text-decoration:none">Accueil</a>
    <span style="margin:0 8px;opacity:.4">›</span>
    <span style="color:#fff">Panier</span>
  </div>

  <div class="page-title">🛒 Votre panier</div>
  <div class="page-sub">
    <?= count($items) > 0 ? count($items).' service(s) sélectionné(s)' : 'Votre panier est vide' ?>
  </div>

  <?php if (count($items) === 0): ?>

    <!-- PANIER VIDE -->
    <div class="empty-cart">
      <div class="empty-icon">🛒</div>
      <div class="empty-title">Votre panier est vide</div>
      <div class="empty-sub">Explorez nos solutions de cybersécurité SaaS et ajoutez des services.</div>
      <a href="catalogue.php" class="btn-cyna">Voir le catalogue</a>
    </div>

  <?php else: ?>

    <div class="row g-4">
      <!-- COLONNE GAUCHE : items -->
      <div class="col-lg-8">

        <!-- Rappel connexion si non connecté -->
        <?php if (!$est_connecte): ?>
        <div class="login-prompt">
          <div class="login-prompt-text">
            <strong>Connectez-vous</strong> pour sauvegarder votre panier et finaliser votre commande.
          </div>
          <div class="d-flex gap-2">
            <a href="connexion.php" class="btn-outline-cyna" style="font-size:.78rem;padding:5px 14px">Connexion</a>
            <a href="inscription.php" class="btn-cyna" style="font-size:.78rem;padding:5px 14px">S'inscrire</a>
          </div>
        </div>
        <?php endif; ?>

        <!-- LISTE DES SERVICES -->
        <div class="cart-list">
          <?php foreach ($items as $it): ?>
          <div class="cart-item <?= $it['is_available'] ? '' : 'unavailable' ?>">

            <!-- Image -->
            <img class="item-img"
                 src="<?= htmlspecialchars(asset_image($it['image_path'] ?? null)) ?>"
                 alt="<?= htmlspecialchars($it['name']) ?>">

            <!-- Info -->
            <div class="item-info">
              <div class="item-name"><?= htmlspecialchars($it['name']) ?></div>
              <?php if (!$it['is_available']): ?>
                <span class="item-unavail">⚠ Temporairement indisponible</span>
              <?php else: ?>
                <div style="margin-top:6px">
                  <select class="cycle-select"
                          data-id="<?= (int)$it['id'] ?>"
                          data-monthly="<?= $it['price_monthly'] ?>"
                          data-yearly="<?= $it['price_yearly'] ?>">
                    <option value="monthly" <?= $it['cycle'] === 'monthly' ? 'selected' : '' ?>>
                      Mensuel
                    </option>
                    <option value="yearly" <?= $it['cycle'] === 'yearly' ? 'selected' : '' ?>>
                      Annuel (-10%)
                    </option>
                  </select>
                </div>
              <?php endif; ?>
            </div>

            <!-- Prix -->
            <div class="item-price" id="price-<?= (int)$it['id'] ?>">
              <?= number_format($it['unit_price'], 2, ',', ' ') ?> €
              <div class="item-price-sub">
                <?= $it['cycle'] === 'yearly' ? '/ an' : '/ mois' ?>
              </div>
            </div>

            <!-- Supprimer -->
            <a href="panier.php?remove=<?= (int)$it['id'] ?>" class="btn-remove">
              🗑
            </a>

          </div>
          <?php endforeach; ?>
        </div>

        <div class="d-flex justify-content-between align-items-center">
          <a href="catalogue.php" class="btn-outline-cyna">← Continuer les achats</a>
        </div>
      </div>

      <!-- COLONNE DROITE : résumé -->
      <div class="col-lg-4">
        <div class="summary">
          <div class="summary-title">Récapitulatif</div>

          <?php foreach ($items as $it): ?>
          <div class="summary-row">
            <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:60%">
              <?= htmlspecialchars($it['name']) ?>
            </span>
            <span style="color:#e8eaf2;font-weight:600">
              <?= number_format($it['unit_price'], 2, ',', ' ') ?> €
            </span>
          </div>
          <?php endforeach; ?>

          <div class="summary-row total">
            <span>Total estimé</span>
            <span class="summary-total-amount" id="total">
              <?= number_format($total, 2, ',', ' ') ?> €
            </span>
          </div>
          <div class="summary-note">Taxes non incluses. Prix susceptibles de varier selon l'abonnement.</div>

          <div class="actions">
            <?php
            $has_unavailable = count(array_filter($items, fn($i) => !$i['is_available'])) > 0;
            ?>
            <?php if ($has_unavailable): ?>
              <div style="background:rgba(248,113,113,.08);border:1px solid rgba(248,113,113,.2);border-radius:10px;padding:10px 14px;font-size:.78rem;color:#f87171">
                ⚠ Un ou plusieurs services sont indisponibles. Retirez-les pour continuer.
              </div>
            <?php else: ?>
              <a href="<?= $est_connecte ? 'checkout.php' : 'connexion.php?redirect=checkout.php' ?>"
                 class="btn-cyna" style="justify-content:center;padding:13px">
                <?= $est_connecte ? '✓ Passer au paiement' : '🔒 Connexion pour payer' ?>
              </a>
            <?php endif; ?>
          </div>

          <!-- Badges sécurité -->
          <div class="secure-badges">
            <span class="secure-badge">🔒 Paiement sécurisé</span>
            <span class="secure-badge">🛡 Données chiffrées</span>
            <span class="secure-badge">↩ Résiliable</span>
          </div>
        </div>
      </div>
    </div>

  <?php endif; ?>
</div>

<!-- FOOTER -->
<footer>
  <a href="Cgu.php">CGU</a>
  <a href="mention_legales.php">Mentions légales</a>
  <a href="Contact.php">Contact</a>
  <a href="a-propos.php">À propos</a>
  <span style="display:block;margin-top:8px">© 2025 CYNA-IT</span>
</footer>

<script>
document.querySelectorAll('.cycle-select').forEach(function(select) {
  select.addEventListener('change', function() {
    var id      = this.dataset.id;
    var monthly = parseFloat(this.dataset.monthly);
    var yearly  = parseFloat(this.dataset.yearly);
    var price   = this.value === 'yearly' ? yearly : monthly;
    var period  = this.value === 'yearly' ? '/ an' : '/ mois';

    var cell = document.getElementById('price-' + id);
    cell.innerHTML = price.toFixed(2).replace('.', ',') + ' €<div class="item-price-sub">' + period + '</div>';

    // Recalcul total
    var total = 0;
    document.querySelectorAll('[id^="price-"]').forEach(function(p) {
      var txt = p.firstChild ? p.firstChild.textContent : p.textContent;
      total += parseFloat(txt.replace(',', '.').replace('€','').trim()) || 0;
    });
    document.getElementById('total').textContent = total.toFixed(2).replace('.', ',') + ' €';
  });
});
</script>

</body>
</html>