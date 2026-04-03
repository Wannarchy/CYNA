<?php
session_start();
$est_connecte = isset($_SESSION['utilisateur_id']);

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}

$order_id = (int)($_GET['order_id'] ?? 0);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Commande confirmée</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container py-5 text-center">

  <h1 class="text-success mb-3">✅ Commande confirmée</h1>

  <p class="lead">
    Merci pour votre confiance.<br>
    Votre commande <strong>#<?= $order_id ?></strong> a bien été enregistrée.
  </p>

  <div class="d-flex justify-content-center gap-3 mt-4">
    <a href="../index.php" class="btn btn-outline-secondary">Accueil</a>
    <a href="catalogue.php" class="btn btn-info">Retour au catalogue</a>
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