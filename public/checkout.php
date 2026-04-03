<?php
session_start();
$est_connecte = isset($_SESSION['utilisateur_id']);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/cart_repository.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}

$cart = $_SESSION['cart'] ?? [];
$items = cart_get_products($connexion, $cart);
$total = cart_total($items);

if (count($items) === 0) {
    header('Location: panier.php');
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>CYNA — Checkout</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background:#0b1020; }
    .section-title { color:#fff; }
  </style>
</head>
<body>

<nav class="navbar navbar-dark bg-dark">
  <div class="container d-flex align-items-center">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <div class="ms-auto d-flex gap-2">
      <?php if ($est_connecte): ?>
        <a class="nav-link text-white" href="mon-compte.php">Mon compte</a>
        <a class="nav-link text-white" href="deconnexion.php">Déconnexion</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container py-4">

  <h1 class="section-title h4 mb-4">Finaliser la commande</h1>

  <div class="row g-4">
    <!-- FORMULAIRE -->
    <div class="col-12 col-lg-7">
      <form action="checkout_submit.php" method="POST" class="card border-0 shadow-sm">
        <div class="card-body">

          <h2 class="h5 mb-3">Adresse de facturation</h2>

          <div class="mb-3">
            <label class="form-label">Nom / Société</label>
            <input type="text" name="billing_name" class="form-control" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Adresse complète</label>
            <textarea name="billing_address" class="form-control" rows="3" required></textarea>
          </div>

          <button type="submit" class="btn btn-info btn-lg w-100">
            Confirmer la commande
          </button>

        </div>
      </form>
    </div>

    <!-- RÉCAP -->
    <div class="col-12 col-lg-5">
      <div class="card bg-dark text-white">
        <div class="card-body">

          <h2 class="h5 mb-3">Récapitulatif</h2>

          <ul class="list-group list-group-flush mb-3">
            <?php foreach ($items as $it): ?>
              <li class="list-group-item bg-dark text-white d-flex justify-content-between">
                <div>
                  <?= htmlspecialchars($it['name']) ?><br>
                  <small class="text-white-50"><?= htmlspecialchars($it['cycle']) ?></small>
                </div>
                <div>
                  <?= number_format($it['unit_price'], 2, ',', ' ') ?> €
                </div>
              </li>
            <?php endforeach; ?>
          </ul>

          <div class="d-flex justify-content-between fs-5 fw-bold">
            <span>Total</span>
            <span><?= number_format($total, 2, ',', ' ') ?> €</span>
          </div>

        </div>
      </div>
    </div>
  </div>

</div>
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