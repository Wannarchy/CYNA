<?php
session_start();
$est_connecte = isset($_SESSION['utilisateur_id']);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/cart_repository.php';

$cart = $_SESSION['cart'] ?? [];

// Suppression produit
if (isset($_GET['remove'])) {
    $rid = (int)$_GET['remove'];
    unset($_SESSION['cart'][$rid]);
    header('Location: panier.php');
    exit;
}

$items = cart_get_products($connexion, $cart);
$total = cart_total($items);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — Panier</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background: #0b1020; }
    .section-title { color: #fff; }
    .price { font-weight: 600; }
    .muted { color: rgba(255,255,255,.75); }
    .disabled-row { opacity: .6; }
  </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark">
  <div class="container d-flex align-items-center gap-3">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <a class="nav-link text-white" href="catalogue.php">Catalogue</a>
    <div class="ms-auto d-flex gap-2">
      <?php if (!$est_connecte): ?>
        <a class="nav-link text-white" href="connexion.php">Connexion</a>
      <?php else: ?>
        <a class="nav-link text-white" href="mon-compte.php">Mon compte</a>
        <a class="nav-link text-white" href="deconnexion.php">Déconnexion</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container py-4 py-lg-5">

  <h1 class="section-title h4 mb-4">Votre panier</h1>

  <?php if (count($items) === 0): ?>
    <div class="alert alert-secondary">Votre panier est vide.</div>
  <?php else: ?>

    <div class="table-responsive mb-3">
      <table class="table table-dark table-bordered align-middle">
        <thead>
          <tr>
            <th>Service</th>
            <th>Cycle</th>
            <th>Prix</th>
            <th></th>
          </tr>
        </thead>
        <tbody>

        <?php foreach ($items as $it): ?>
          <tr class="<?= $it['is_available'] ? '' : 'disabled-row' ?>">
            <td><?= htmlspecialchars($it['name']) ?></td>

            <td>
              <?php if ($it['is_available']): ?>
                <select class="form-select form-select-sm cycle-select"
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
              <?php else: ?>
                <span class="text-muted">Indisponible</span>
              <?php endif; ?>
            </td>

            <td class="price" id="price-<?= (int)$it['id'] ?>">
              <?= number_format($it['unit_price'], 2, ',', ' ') ?> €
            </td>

            <td>
              <a href="panier.php?remove=<?= (int)$it['id'] ?>"
                 class="btn btn-sm btn-outline-danger">
                Supprimer
              </a>
            </td>
          </tr>
        <?php endforeach; ?>

        </tbody>
      </table>
    </div>

    <div class="card bg-dark text-white">
      <div class="card-body d-flex justify-content-between align-items-center">
        <div>
          <div class="fw-semibold">Total</div>
          <div class="muted small">Taxes non incluses</div>
        </div>
        <div class="fs-4 fw-bold" id="total">
          <?= number_format($total, 2, ',', ' ') ?> €
        </div>
      </div>
    </div>

    <div class="d-grid d-lg-flex justify-content-lg-end gap-2 mt-3">
      <a href="catalogue.php" class="btn btn-outline-light">
        Continuer les achats
      </a>
      <a href="checkout.php" class="btn btn-info btn-lg">
        Passer au paiement
      </a>
    </div>

  <?php endif; ?>

</div>

<script>
document.querySelectorAll('.cycle-select').forEach(select => {
  select.addEventListener('change', function () {
    const id = this.dataset.id;
    const monthly = parseFloat(this.dataset.monthly);
    const yearly = parseFloat(this.dataset.yearly);

    const priceCell = document.getElementById('price-' + id);
    let price = monthly;

    if (this.value === 'yearly') {
      price = yearly;
    }

    priceCell.textContent = price.toFixed(2).replace('.', ',') + ' €';

    // recalcul du total
    let total = 0;
    document.querySelectorAll('.price').forEach(p => {
      total += parseFloat(p.textContent.replace(',', '.'));
    });

    document.getElementById('total').textContent =
      total.toFixed(2).replace('.', ',') + ' €';
  });
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
<?php if ($show_admin_link): ?>
<div style="position:fixed;bottom:20px;right:20px;z-index:9999">
  <a href="../admin/index.php" style="background:linear-gradient(135deg,#1a2980,#26d0ce);color:#fff;padding:8px 16px;border-radius:30px;font-size:.78rem;font-weight:600;text-decoration:none;box-shadow:0 4px 20px rgba(26,41,128,.4)">
    ⚙ Administration
  </a>
</div>
<?php endif; ?>
</body>
</html>