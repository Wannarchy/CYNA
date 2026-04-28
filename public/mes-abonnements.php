<?php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/home_repository.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

if (!isset($_SESSION['utilisateur_id'])) { header('Location: connexion.php'); exit; }
$user_id = (int)$_SESSION['utilisateur_id'];

$stmt = $connexion->prepare("SELECT * FROM utilisateurs WHERE id=? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) { session_destroy(); header('Location: connexion.php'); exit; }

// Créer table abonnements si pas encore existante
$connexion->exec("
    CREATE TABLE IF NOT EXISTS subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        order_id INT NOT NULL,
        product_id INT NOT NULL,
        cycle VARCHAR(20) DEFAULT 'monthly',
        price DECIMAL(10,2) NOT NULL,
        status VARCHAR(20) DEFAULT 'active',
        start_date DATE NOT NULL,
        next_billing DATE,
        cancelled_at DATETIME DEFAULT NULL,
        INDEX(user_id),
        INDEX(status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Synchroniser les abonnements depuis les commandes existantes
$sync = $connexion->prepare("
    SELECT oi.*, o.created_at, o.id AS order_id
    FROM order_items oi
    JOIN orders o ON o.id = oi.order_id
    WHERE o.user_id = ?
    AND NOT EXISTS (
        SELECT 1 FROM subscriptions s
        WHERE s.order_id = o.id AND s.product_id = oi.product_id
    )
");
$sync->execute([$user_id]);
foreach ($sync->fetchAll() as $row) {
    $start = date('Y-m-d', strtotime($row['created_at']));
    $next  = $row['cycle'] === 'yearly'
        ? date('Y-m-d', strtotime($start . ' +1 year'))
        : date('Y-m-d', strtotime($start . ' +1 month'));
    $connexion->prepare("
        INSERT INTO subscriptions (user_id, order_id, product_id, cycle, price, status, start_date, next_billing)
        VALUES (?,?,?,?,?,'active',?,?)
    ")->execute([$user_id, $row['order_id'], $row['product_id'], $row['cycle'], $row['price'], $start, $next]);
}

$success = '';
$error   = '';

// RÉSILIER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    $sub_id = (int)$_POST['sub_id'];
    $check  = $connexion->prepare("SELECT id FROM subscriptions WHERE id=? AND user_id=? AND status='active'");
    $check->execute([$sub_id, $user_id]);
    if ($check->fetch()) {
        $connexion->prepare("UPDATE subscriptions SET status='cancelled', cancelled_at=NOW() WHERE id=?")
                  ->execute([$sub_id]);
        // Email de confirmation résiliation
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'noreplycyna@gmail.com';
            $mail->Password   = 'uaws jfaf jqal cahx';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom('noreply@cyna.com', 'CYNA Sécurité');
            $mail->addAddress($user['email']);
            $mail->isHTML(true);
            $mail->Subject = 'Résiliation de votre abonnement CYNA';
            $mail->Body    = "<html><body style='font-family:Arial,sans-serif;padding:20px'>
                <div style='max-width:500px;margin:auto;background:#fff;border-radius:12px;overflow:hidden'>
                    <div style='background:linear-gradient(135deg,#1a2980,#26d0ce);padding:24px;text-align:center'>
                        <h1 style='color:#fff;margin:0;font-size:20px'>CYNA</h1>
                    </div>
                    <div style='padding:24px'>
                        <h2 style='color:#333'>Résiliation confirmée</h2>
                        <p>Bonjour <strong>" . htmlspecialchars($user['prenom']) . "</strong>,</p>
                        <p>Votre abonnement a bien été résilié. Il reste actif jusqu'à la fin de la période en cours.</p>
                        <p style='color:#888;font-size:13px'>Si vous changez d'avis, vous pouvez vous réabonner à tout moment depuis notre catalogue.</p>
                    </div>
                </div>
            </body></html>";
            $mail->send();
        } catch (Exception $e) {}

        $success = "Abonnement résilié. Il reste actif jusqu'à la fin de la période en cours.";
    }
}

// RENOUVELER (changer de cycle)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'upgrade') {
    $sub_id   = (int)$_POST['sub_id'];
    $new_cycle = $_POST['new_cycle'] === 'yearly' ? 'yearly' : 'monthly';
    $check = $connexion->prepare("SELECT * FROM subscriptions WHERE id=? AND user_id=? AND status='active'");
    $check->execute([$sub_id, $user_id]);
    $sub = $check->fetch();
    if ($sub) {
        // Calculer le nouveau prix
        $prod = $connexion->prepare("SELECT price_monthly, price_yearly FROM products WHERE id=?");
        $prod->execute([$sub['product_id']]);
        $p = $prod->fetch();
        if ($p) {
            $new_price = $new_cycle === 'yearly' ? $p['price_yearly'] : $p['price_monthly'];
            $next = $new_cycle === 'yearly'
                ? date('Y-m-d', strtotime('+1 year'))
                : date('Y-m-d', strtotime('+1 month'));
            $connexion->prepare("UPDATE subscriptions SET cycle=?, price=?, next_billing=? WHERE id=?")
                      ->execute([$new_cycle, $new_price, $next, $sub_id]);
            $success = "Abonnement mis à jour vers le cycle " . ($new_cycle === 'yearly' ? 'annuel' : 'mensuel') . " !";
        }
    }
}

// Charger les abonnements
$subs = $connexion->prepare("
    SELECT s.*, p.name AS product_name, p.image_path, p.price_monthly, p.price_yearly,
           c.name AS category_name
    FROM subscriptions s
    JOIN products p ON p.id = s.product_id
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE s.user_id = ?
    ORDER BY s.status ASC, s.start_date DESC
");
$subs->execute([$user_id]);
$subscriptions = $subs->fetchAll();

$active_count    = count(array_filter($subscriptions, fn($s) => $s['status'] === 'active'));
$cancelled_count = count(array_filter($subscriptions, fn($s) => $s['status'] === 'cancelled'));
$total_monthly   = array_sum(array_map(fn($s) => $s['status'] === 'active' ? (float)$s['price'] : 0, $subscriptions));

$nb_panier = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — Mes abonnements</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--grad:linear-gradient(135deg,#1a2980,#26d0ce);--cyan:#26d0ce;--bg:#0b1020;--card:#0f1628;--border:rgba(255,255,255,.07);--muted:rgba(255,255,255,.45);}
    *{box-sizing:border-box;}
    body{background:var(--bg);color:#e8eaf2;font-family:'DM Sans',sans-serif;margin:0;}

    /* NAVBAR */
    .navbar{background:rgba(11,16,32,.97)!important;border-bottom:1px solid var(--border);backdrop-filter:blur(14px);height:62px;padding:0;}
    .navbar .container-fluid{height:62px;align-items:center;}
    .navbar-brand{font-weight:900;font-size:1.3rem;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;padding:0;margin-right:20px;}
    .nav-link-p{color:rgba(255,255,255,.6);text-decoration:none;font-size:.83rem;padding:6px 12px;border-radius:8px;transition:all .15s;}
    .nav-link-p:hover{color:#fff;background:rgba(255,255,255,.06);}

    /* LAYOUT */
    .wrap{max-width:1100px;margin:0 auto;padding:32px 16px;display:flex;gap:24px;align-items:flex-start;}
    .sb{width:230px;flex-shrink:0;position:sticky;top:72px;}
    .main{flex:1;min-width:0;}

    /* SIDEBAR */
    .u-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:18px;margin-bottom:10px;text-align:center;}
    .u-av{width:50px;height:50px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:1.15rem;font-weight:700;color:#fff;margin:0 auto 8px;}
    .u-name{font-size:.88rem;font-weight:600;color:#fff;}
    .u-email{font-size:.7rem;color:#5c6378;margin-top:2px;}
    .sb-nav{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;}
    .sb-nav a{display:flex;align-items:center;gap:10px;padding:11px 16px;color:#8b92a8;text-decoration:none;font-size:.83rem;border-bottom:1px solid rgba(255,255,255,.05);transition:all .15s;}
    .sb-nav a:last-child{border-bottom:none;}
    .sb-nav a:hover{color:#e8eaf2;background:rgba(255,255,255,.03);}
    .sb-nav a.active{color:#fff;background:rgba(38,208,206,.08);border-left:3px solid var(--cyan);}

    /* STATS */
    .stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;}
    .stat-box{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;text-align:center;}
    .stat-val{font-size:1.5rem;font-weight:800;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
    .stat-lbl{font-size:.7rem;color:var(--muted);margin-top:4px;text-transform:uppercase;letter-spacing:.5px;}

    /* CARDS */
    .ccard{background:var(--card);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:14px;transition:border-color .2s;}
    .ccard:hover{border-color:rgba(255,255,255,.12);}
    .ccard.cancelled{opacity:.6;}
    .ccard-body{padding:20px;}

    /* SUB ITEM */
    .sub-top{display:flex;align-items:center;gap:14px;margin-bottom:16px;}
    .sub-img{width:52px;height:52px;border-radius:10px;object-fit:cover;flex-shrink:0;background:#131c30;}
    .sub-info{flex:1;min-width:0;}
    .sub-name{font-size:.95rem;font-weight:700;color:#fff;margin-bottom:3px;}
    .sub-cat{font-size:.73rem;color:var(--muted);}

    /* BADGES */
    .badge-active{display:inline-flex;align-items:center;gap:4px;font-size:.68rem;font-weight:700;color:#4ade80;background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.2);border-radius:20px;padding:3px 10px;}
    .badge-cancelled{display:inline-flex;align-items:center;gap:4px;font-size:.68rem;font-weight:700;color:#f87171;background:rgba(248,113,113,.1);border:1px solid rgba(248,113,113,.2);border-radius:20px;padding:3px 10px;}

    /* DETAILS */
    .sub-details{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;background:#131c30;border-radius:10px;padding:14px;margin-bottom:14px;}
    .sub-detail-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:4px;}
    .sub-detail-val{font-size:.85rem;font-weight:600;color:#fff;}

    /* PRIX */
    .sub-price{font-size:1.2rem;font-weight:800;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
    .sub-period{font-size:.72rem;color:var(--muted);}

    /* ACTIONS */
    .sub-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center;}
    .btn-cancel{background:transparent;border:1px solid rgba(239,68,68,.25);color:rgba(239,68,68,.7);border-radius:9px;padding:7px 16px;font-size:.8rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .15s;}
    .btn-cancel:hover{background:rgba(239,68,68,.1);color:#f87171;}
    .btn-upgrade{background:rgba(38,208,206,.1);border:1px solid rgba(38,208,206,.2);color:var(--cyan);border-radius:9px;padding:7px 16px;font-size:.8rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .15s;}
    .btn-upgrade:hover{background:rgba(38,208,206,.2);}
    .btn-renew{background:var(--grad);border:none;color:#fff;border-radius:9px;padding:7px 16px;font-size:.8rem;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;text-decoration:none;transition:opacity .15s;display:inline-flex;align-items:center;gap:6px;}
    .btn-renew:hover{opacity:.85;color:#fff;}

    /* CYCLE SELECTOR */
    .cycle-form{display:flex;align-items:center;gap:8px;}
    .cycle-select{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:8px;padding:6px 10px;font-size:.78rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;cursor:pointer;}
    .cycle-select option{background:#0f1628;}

    /* ALERTS */
    .alert-ok{background:rgba(34,197,94,.08);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#4ade80;margin-bottom:16px;}
    .alert-err{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#f87171;margin-bottom:16px;}

    /* EMPTY */
    .empty{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:48px 24px;text-align:center;color:var(--muted);}

    /* MODAL */
    .modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:999;align-items:center;justify-content:center;backdrop-filter:blur(4px);}
    .modal-bg.open{display:flex;}
    .modal-box{background:#0f1628;border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:28px;max-width:420px;width:90%;}
    .modal-title{font-size:1.1rem;font-weight:700;color:#fff;margin-bottom:8px;}
    .modal-sub{font-size:.85rem;color:var(--muted);margin-bottom:24px;line-height:1.6;}
    .modal-actions{display:flex;gap:10px;justify-content:flex-end;}
    .btn-modal-cancel{background:transparent;border:1px solid var(--border);color:var(--muted);border-radius:9px;padding:9px 18px;font-size:.83rem;cursor:pointer;font-family:'DM Sans',sans-serif;}
    .btn-modal-confirm{background:linear-gradient(135deg,#dc2626,#ef4444);border:none;color:#fff;border-radius:9px;padding:9px 18px;font-size:.83rem;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;}

    footer{border-top:1px solid var(--border);padding:20px;text-align:center;color:var(--muted);font-size:.75rem;margin-top:40px;}
    footer a{color:rgba(255,255,255,.3);text-decoration:none;margin:0 8px;}
    @media(max-width:768px){.wrap{flex-direction:column;}.sb{width:100%;position:static;}.stats-row{grid-template-columns:repeat(2,1fr);}.sub-details{grid-template-columns:repeat(2,1fr);}}
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar sticky-top">
  <div class="container-fluid px-3 px-lg-4">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <div class="d-flex align-items-center gap-2 ms-auto">
      <a href="panier.php" class="nav-link-p">🛒<?= $nb_panier > 0 ? " ($nb_panier)" : '' ?></a>
      <a href="deconnexion.php" class="nav-link-p">Déconnexion</a>
    </div>
  </div>
</nav>

<div class="wrap">
  <!-- SIDEBAR -->
  <aside class="sb">
    <div class="u-card">
      <div class="u-av"><?= strtoupper(substr($user['prenom']??'U',0,1)) ?></div>
      <div class="u-name"><?= htmlspecialchars(($user['prenom']??'').' '.($user['nom']??'')) ?></div>
      <div class="u-email"><?= htmlspecialchars($user['email']??'') ?></div>
    </div>
    <nav class="sb-nav">
      <a href="mon-compte.php?tab=profil">👤 Mon profil</a>
      <a href="mon-compte.php?tab=securite">🔒 Sécurité</a>
      <a href="adresses.php">📍 Mes adresses</a>
      <a href="paiements.php">💳 Paiements</a>
      <a href="mes-abonnements.php" class="active">🔄 Abonnements <span style="margin-left:auto;font-size:.65rem;background:rgba(74,222,128,.15);color:#4ade80;border-radius:20px;padding:1px 7px"><?= $active_count ?></span></a>
      <a href="mes-commandes.php">📦 Commandes</a>
      <a href="deconnexion.php" style="color:rgba(239,68,68,.6)">⏻ Déconnexion</a>
    </nav>
  </aside>

  <!-- MAIN -->
  <main class="main">
    <?php if ($success): ?><div class="alert-ok">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error):   ?><div class="alert-err">⚠ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- STATS -->
    <div class="stats-row">
      <div class="stat-box">
        <div class="stat-val"><?= $active_count ?></div>
        <div class="stat-lbl">Actifs</div>
      </div>
      <div class="stat-box">
        <div class="stat-val"><?= $cancelled_count ?></div>
        <div class="stat-lbl">Résiliés</div>
      </div>
      <div class="stat-box">
        <div class="stat-val"><?= number_format($total_monthly, 0, ',', ' ') ?> €</div>
        <div class="stat-lbl">/ période</div>
      </div>
    </div>

    <?php if (empty($subscriptions)): ?>
      <div class="empty">
        <div style="font-size:2.5rem;margin-bottom:12px;opacity:.3">🔄</div>
        <p style="font-size:.88rem">Aucun abonnement pour le moment.</p>
        <a href="catalogue.php" style="color:var(--cyan);font-size:.85rem;text-decoration:none">Découvrir nos services →</a>
      </div>

    <?php else: ?>

      <!-- ABONNEMENTS ACTIFS -->
      <?php $actifs = array_filter($subscriptions, fn($s) => $s['status'] === 'active'); ?>
      <?php if ($actifs): ?>
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin-bottom:10px">🟢 Abonnements actifs</div>
        <?php foreach ($actifs as $sub): ?>
        <div class="ccard">
          <div class="ccard-body">
            <div class="sub-top">
              <img class="sub-img"
                   src="<?= htmlspecialchars(asset_image($sub['image_path'] ?? null)) ?>"
                   alt="<?= htmlspecialchars($sub['product_name']) ?>">
              <div class="sub-info">
                <div class="sub-name"><?= htmlspecialchars($sub['product_name']) ?></div>
                <div class="sub-cat"><?= htmlspecialchars($sub['category_name'] ?? '') ?></div>
              </div>
              <div>
                <span class="badge-active">● Actif</span>
              </div>
            </div>

            <div class="sub-details">
              <div>
                <div class="sub-detail-label">Prix</div>
                <div class="sub-detail-val">
                  <span class="sub-price"><?= number_format((float)$sub['price'],2,',',' ') ?> €</span>
                  <span class="sub-period">/<?= $sub['cycle'] === 'yearly' ? 'an' : 'mois' ?></span>
                </div>
              </div>
              <div>
                <div class="sub-detail-label">Cycle</div>
                <div class="sub-detail-val"><?= $sub['cycle'] === 'yearly' ? 'Annuel' : 'Mensuel' ?></div>
              </div>
              <div>
                <div class="sub-detail-label">Prochain renouvellement</div>
                <div class="sub-detail-val">
                  <?= $sub['next_billing'] ? date('d/m/Y', strtotime($sub['next_billing'])) : '—' ?>
                </div>
              </div>
            </div>

            <div class="sub-actions">
              <!-- Changer de cycle -->
              <form method="POST" class="cycle-form">
                <input type="hidden" name="action" value="upgrade">
                <input type="hidden" name="sub_id" value="<?= (int)$sub['id'] ?>">
                <select name="new_cycle" class="cycle-select">
                  <option value="monthly" <?= $sub['cycle']==='monthly'?'selected':'' ?>>Mensuel</option>
                  <option value="yearly"  <?= $sub['cycle']==='yearly'?'selected':'' ?>>Annuel (économisez 10%)</option>
                </select>
                <button type="submit" class="btn-upgrade">Changer</button>
              </form>

              <!-- Résilier -->
              <button class="btn-cancel"
                      onclick="openCancel(<?= (int)$sub['id'] ?>, '<?= htmlspecialchars(addslashes($sub['product_name'])) ?>')">
                🗑 Résilier
              </button>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>

      <!-- ABONNEMENTS RÉSILIÉS -->
      <?php $resilies = array_filter($subscriptions, fn($s) => $s['status'] === 'cancelled'); ?>
      <?php if ($resilies): ?>
        <div style="font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:1px;color:var(--muted);margin:20px 0 10px">🔴 Abonnements résiliés</div>
        <?php foreach ($resilies as $sub): ?>
        <div class="ccard cancelled">
          <div class="ccard-body">
            <div class="sub-top">
              <img class="sub-img"
                   src="<?= htmlspecialchars(asset_image($sub['image_path'] ?? null)) ?>"
                   alt="<?= htmlspecialchars($sub['product_name']) ?>">
              <div class="sub-info">
                <div class="sub-name"><?= htmlspecialchars($sub['product_name']) ?></div>
                <div class="sub-cat"><?= htmlspecialchars($sub['category_name'] ?? '') ?></div>
              </div>
              <span class="badge-cancelled">● Résilié</span>
            </div>
            <div style="font-size:.8rem;color:var(--muted);margin-bottom:14px">
              Résilié le <?= $sub['cancelled_at'] ? date('d/m/Y', strtotime($sub['cancelled_at'])) : '—' ?>
            </div>
            <div class="sub-actions">
              <a href="produit.php?id=<?= (int)$sub['product_id'] ?>" class="btn-renew">
                🔄 Se réabonner
              </a>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>

    <?php endif; ?>
  </main>
</div>

<!-- MODAL CONFIRMATION RÉSILIATION -->
<div class="modal-bg" id="cancelModal">
  <div class="modal-box">
    <div class="modal-title">⚠ Résilier l'abonnement</div>
    <div class="modal-sub">
      Voulez-vous vraiment résilier <strong id="modal-product-name"></strong> ?<br>
      L'abonnement reste actif jusqu'à la fin de la période en cours. Aucun remboursement ne sera effectué.
    </div>
    <form method="POST" id="cancelForm">
      <input type="hidden" name="action" value="cancel">
      <input type="hidden" name="sub_id" id="modal-sub-id">
      <div class="modal-actions">
        <button type="button" class="btn-modal-cancel" onclick="closeCancel()">Annuler</button>
        <button type="submit" class="btn-modal-confirm">Confirmer la résiliation</button>
      </div>
    </form>
  </div>
</div>

<footer>
  <a href="Cgu.php">CGU</a>
  <a href="mention_legales.php">Mentions légales</a>
  <a href="Contact.php">Contact</a>
  <span>© 2025 CYNA-IT</span>
</footer>

<script>
function openCancel(subId, productName) {
  document.getElementById('modal-sub-id').value       = subId;
  document.getElementById('modal-product-name').textContent = productName;
  document.getElementById('cancelModal').classList.add('open');
}
function closeCancel() {
  document.getElementById('cancelModal').classList.remove('open');
}
document.getElementById('cancelModal').addEventListener('click', function(e) {
  if (e.target === this) closeCancel();
});
</script>
</body>
</html>