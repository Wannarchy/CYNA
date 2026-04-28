<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['utilisateur_id'])) { header('Location: connexion.php'); exit; }
$user_id = (int)$_SESSION['utilisateur_id'];

// Charger l'utilisateur
$stmt = $connexion->prepare("SELECT prenom,nom,email FROM utilisateurs WHERE id=?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) { session_destroy(); header('Location: connexion.php'); exit; }

$nb_panier = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));

// Téléchargement facture PDF (simple HTML → PDF via impression navigateur)
$download_id = isset($_GET['facture']) ? (int)$_GET['facture'] : 0;
if ($download_id > 0) {
    $ostmt = $connexion->prepare("SELECT o.*, u.email, u.prenom, u.nom FROM orders o JOIN utilisateurs u ON u.id=o.user_id WHERE o.id=? AND o.user_id=?");
    $ostmt->execute([$download_id, $user_id]);
    $order = $ostmt->fetch();
    if ($order) {
        $items_stmt = $connexion->prepare("SELECT oi.*, p.name FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?");        $items_stmt->execute([$download_id]);
        $items = $items_stmt->fetchAll();
        // Générer facture HTML simple (printable)
        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Facture #'.(int)$order['id'].'</title><style>
        body{font-family:Arial,sans-serif;margin:40px;color:#222;max-width:800px;margin:40px auto;}
        .header{display:flex;justify-content:space-between;margin-bottom:40px;}
        .logo{font-size:1.8rem;font-weight:900;color:#1a2980;}
        .inv-info{text-align:right;font-size:.9rem;color:#555;}
        h2{color:#1a2980;border-bottom:2px solid #1a2980;padding-bottom:8px;}
        table{width:100%;border-collapse:collapse;margin-top:16px;}
        th{background:#1a2980;color:#fff;padding:10px;text-align:left;font-size:.85rem;}
        td{padding:10px;border-bottom:1px solid #eee;font-size:.88rem;}
        .total-row td{font-weight:700;background:#f8f9fa;font-size:1rem;}
        .info-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin:20px 0;}
        .info-box{background:#f8f9fa;padding:16px;border-radius:8px;}
        .info-box h4{font-size:.8rem;color:#888;text-transform:uppercase;margin-bottom:8px;}
        .footer-note{margin-top:40px;padding-top:16px;border-top:1px solid #eee;font-size:.78rem;color:#888;text-align:center;}
        @media print{body{margin:20px;}.no-print{display:none;}}
        </style></head><body>';
        echo '<div class="no-print" style="background:#1a2980;color:#fff;padding:12px 20px;border-radius:8px;margin-bottom:20px;display:flex;justify-content:space-between;align-items:center">';
        echo '<span>📄 Facture prête à imprimer</span>';
        echo '<button onclick="window.print()" style="background:#26d0ce;color:#222;border:none;border-radius:6px;padding:8px 18px;font-weight:700;cursor:pointer;font-size:.9rem">🖨 Imprimer / Sauvegarder PDF</button>';
        echo '</div>';
        echo '<div class="header">';
        echo '<div><div class="logo">CYNA</div><div style="font-size:.8rem;color:#888;margin-top:4px">CYNA-IT — 10 Rue de Penthièvre, 75008 Paris<br>SIRET : 91371103200015</div></div>';
        echo '<div class="inv-info"><strong>FACTURE</strong><br>N° FAC-'.str_pad($order['id'],6,'0',STR_PAD_LEFT).'<br>Date : '.date('d/m/Y',strtotime($order['created_at'])).'</div>';
        echo '</div>';
        echo '<div class="info-grid">';
        echo '<div class="info-box"><h4>Facturer à</h4><strong>'.htmlspecialchars($order['billing_name']??($order['prenom'].' '.$order['nom'])).'</strong><br>'.htmlspecialchars($order['email']).'<br>'.htmlspecialchars($order['billing_address']??'').'</div>';
        echo '<div class="info-box"><h4>Détails</h4>Commande #'.(int)$order['id'].'<br>Date : '.date('d/m/Y H:i',strtotime($order['created_at'])).'</div>';
        echo '</div>';
        echo '<h2>Détail de la commande</h2>';
        echo '<table><thead><tr><th>Service</th><th>Abonnement</th><th>Prix</th></tr></thead><tbody>';
        foreach ($items as $item) {
            $cycle = $item['cycle'] ?? 'mensuel';
            $price = (float)($item['price'] ?? 0);
            echo '<tr><td>'.htmlspecialchars($item['name']??'Service').'</td><td>'.ucfirst($cycle).'</td><td>'.number_format($price,2,',','.').' €</td></tr>';
        }
        echo '<tr class="total-row"><td colspan="4" style="text-align:right">TOTAL TTC</td><td>'.number_format((float)$order['total'],2,',','.').' €</td></tr>';
        echo '</tbody></table>';
        echo '<div class="footer-note">Merci de votre confiance. Pour toute question : contact@cyna-it.fr — www.cyna-it.fr</div>';
        echo '</body></html>';
        exit;
    }
}

// Détail commande
$detail_id = isset($_GET['order']) ? (int)$_GET['order'] : 0;
$detail_order = null;
$detail_items = [];
if ($detail_id > 0) {
    $ostmt = $connexion->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
    $ostmt->execute([$detail_id, $user_id]);
    $detail_order = $ostmt->fetch();
    if ($detail_order) {
        $istmt = $connexion->prepare("SELECT oi.*, p.name, p.image_path FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?");
        $istmt->execute([$detail_id]);
        $detail_items = $istmt->fetchAll();
    }
}

// Charger toutes les commandes groupées par année
$all_orders = $connexion->prepare("
    SELECT o.id, o.total, o.created_at, o.billing_name, o.billing_address,
           YEAR(o.created_at) AS year
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
");
$all_orders->execute([$user_id]);
$all_orders = $all_orders->fetchAll();

// Filtres
$filter_year = isset($_GET['annee']) ? (int)$_GET['annee'] : 0;
$filter_q    = trim($_GET['q'] ?? '');

// Années disponibles
$years = array_unique(array_column($all_orders, 'year'));
rsort($years);

// Appliquer filtres
$filtered = array_filter($all_orders, function($o) use ($filter_year, $filter_q) {
    if ($filter_year > 0 && (int)$o['year'] !== $filter_year) return false;
    if ($filter_q !== '') {
        $search = strtolower($filter_q);
        if (strpos(strtolower($o['billing_name']??''), $search) === false &&
            strpos((string)$o['id'], $filter_q) === false) return false;
    }
    return true;
});

// Grouper par année
$by_year = [];
foreach ($filtered as $o) {
    $by_year[$o['year']][] = $o;
}
krsort($by_year);

// Cache des items pour les commandes visibles
$order_items_cache = [];
foreach ($filtered as $o) {
    $is = $connexion->prepare("SELECT p.name, oi.cycle, oi.price FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?");
    $is->execute([$o['id']]);
    $order_items_cache[$o['id']] = $is->fetchAll();
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA — Historique des commandes</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root{--blue:#1a2980;--cyan:#26d0ce;--grad:linear-gradient(135deg,#1a2980,#26d0ce);}
    *{box-sizing:border-box;}
    body{background:#0b1020;color:#e8eaf2;font-family:'DM Sans',sans-serif;margin:0;}
    .navbar{background:rgba(11,16,32,.97)!important;border-bottom:1px solid rgba(255,255,255,.07);backdrop-filter:blur(12px);}
    .navbar-brand{font-weight:800;font-size:1.2rem;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
    .nav-link{color:rgba(255,255,255,.7)!important;font-size:.88rem;}
    .nav-link:hover{color:#fff!important;}

    .wrap{max-width:1200px;margin:0 auto;padding:32px 16px;display:flex;gap:24px;align-items:flex-start;}
    .sb{width:230px;flex-shrink:0;position:sticky;top:72px;}
    .u-card{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:18px;margin-bottom:10px;text-align:center;}
    .u-av{width:50px;height:50px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:1.15rem;font-weight:700;color:#fff;margin:0 auto 8px;}
    .u-name{font-size:.88rem;font-weight:600;color:#fff;}
    .u-email{font-size:.7rem;color:#5c6378;margin-top:2px;}
    .sb-nav{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:14px;overflow:hidden;}
    .sb-nav a{display:flex;align-items:center;gap:10px;padding:11px 16px;color:#8b92a8;text-decoration:none;font-size:.83rem;border-bottom:1px solid rgba(255,255,255,.05);transition:all .15s;}
    .sb-nav a:last-child{border-bottom:none;}
    .sb-nav a:hover{color:#e8eaf2;background:rgba(255,255,255,.03);}
    .sb-nav a.active{color:#fff;background:rgba(38,208,206,.08);border-left:3px solid var(--cyan);}
    .main{flex:1;min-width:0;}

    .ccard{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:14px;overflow:hidden;margin-bottom:16px;}
    .ccard-head{padding:14px 20px;border-bottom:1px solid rgba(255,255,255,.07);font-weight:600;font-size:.82rem;color:#8b92a8;text-transform:uppercase;letter-spacing:.5px;display:flex;align-items:center;justify-content:space-between;}
    .ccard-body{padding:20px;}

    /* FILTERS */
    .filters{display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap;}
    .form-control-sm-dark{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .15s;}
    .form-control-sm-dark::placeholder{color:#3a3f52;}
    .form-control-sm-dark:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(38,208,206,.1);}
    .year-pill{display:inline-flex;align-items:center;padding:6px 14px;border-radius:20px;font-size:.78rem;font-weight:600;cursor:pointer;text-decoration:none;border:1px solid rgba(255,255,255,.1);color:#8b92a8;background:transparent;transition:all .15s;}
    .year-pill:hover{color:#fff;border-color:rgba(255,255,255,.25);}
    .year-pill.active{background:var(--grad);color:#fff;border-color:transparent;}

    /* YEAR GROUP */
    .year-group{margin-bottom:32px;}
    .year-label{font-size:1.1rem;font-weight:700;color:#fff;margin-bottom:12px;display:flex;align-items:center;gap:10px;}
    .year-label::after{content:'';flex:1;height:1px;background:rgba(255,255,255,.07);}

    /* ORDER ROW */
    .order-row{background:#131c30;border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:16px 20px;margin-bottom:8px;transition:border-color .2s;cursor:pointer;}
    .order-row:hover{border-color:rgba(255,255,255,.15);}
    .order-row.open{border-color:rgba(38,208,206,.25);background:rgba(38,208,206,.04);}
    .order-top{display:flex;align-items:center;gap:12px;flex-wrap:wrap;}
    .order-id{font-size:.72rem;font-weight:700;padding:3px 10px;border-radius:20px;background:rgba(79,140,255,.12);color:#93c5fd;border:1px solid rgba(79,140,255,.2);font-family:'DM Mono',monospace;}
    .order-name{flex:1;font-size:.88rem;font-weight:600;color:#fff;}
    .order-services{font-size:.75rem;color:rgba(255,255,255,.45);}
    .order-date{font-size:.75rem;color:#5c6378;font-family:'DM Mono',monospace;}
    .order-total{font-size:.92rem;font-weight:700;color:#fff;}
    .order-chevron{color:#5c6378;transition:transform .2s;}
    .order-row.open .order-chevron{transform:rotate(90deg);}

    /* ORDER DETAIL */
    .order-detail{display:none;padding-top:14px;border-top:1px solid rgba(255,255,255,.07);margin-top:14px;}
    .order-row.open .order-detail{display:block;}
    .detail-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
    .detail-box{background:rgba(255,255,255,.03);border-radius:8px;padding:12px;}
    .detail-box-label{font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#5c6378;margin-bottom:6px;}
    .detail-box-val{font-size:.83rem;color:#e8eaf2;line-height:1.5;}

    /* ITEMS TABLE */
    .items-table{width:100%;border-collapse:collapse;font-size:.83rem;margin-bottom:12px;}
    .items-table thead tr{border-bottom:1px solid rgba(255,255,255,.08);}
    .items-table thead th{padding:8px 12px;font-size:.65rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#5c6378;text-align:left;}
    .items-table tbody tr{border-bottom:1px solid rgba(255,255,255,.04);}
    .items-table tbody tr:last-child{border-bottom:none;}
    .items-table td{padding:9px 12px;color:#e8eaf2;}

    .btn-facture{display:inline-flex;align-items:center;gap:6px;background:rgba(38,208,206,.1);color:#26d0ce;border:1px solid rgba(38,208,206,.2);border-radius:8px;padding:7px 14px;font-size:.78rem;font-weight:600;text-decoration:none;transition:all .15s;}
    .btn-facture:hover{background:rgba(38,208,206,.2);color:#26d0ce;}

    .no-orders{text-align:center;padding:48px 24px;color:#5c6378;}
    .no-orders .icon{font-size:2.5rem;margin-bottom:12px;opacity:.3;}
    footer{border-top:1px solid rgba(255,255,255,.07);padding:24px 16px;text-align:center;color:rgba(255,255,255,.3);font-size:.75rem;}
    footer a{color:rgba(255,255,255,.35);text-decoration:none;margin:0 10px;}
    footer a:hover{color:rgba(255,255,255,.6);}
    @media(max-width:768px){.wrap{flex-direction:column;}.sb{width:100%;position:static;}.detail-grid{grid-template-columns:1fr;}.order-top{flex-direction:column;align-items:flex-start;}}
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container-fluid px-3 px-lg-4">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto gap-1 align-items-center">
        <li class="nav-item"><a class="nav-link" href="panier.php">🛒 <?= $nb_panier > 0 ? "($nb_panier)" : '' ?></a></li>
        <li class="nav-item"><a class="nav-link" href="deconnexion.php">Déconnexion</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="wrap">
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
      <a href="mes-abonnements.php">🔄 Abonnements</a>
      <a href="mes-commandes.php" class="active">📦 Commandes</a>
      <a href="deconnexion.php" style="color:rgba(239,68,68,.6)">⏻ Déconnexion</a>
    </nav>
  </aside>

  <main class="main">
    <!-- FILTRES -->
    <form method="GET" action="mes-commandes.php">
      <div class="filters">
        <input type="search" class="form-control-sm-dark" name="q" placeholder="🔍 Rechercher une commande..." value="<?= htmlspecialchars($filter_q) ?>" style="flex:1;min-width:200px">
        <button type="submit" style="background:var(--grad);color:#fff;border:none;border-radius:9px;padding:8px 16px;font-size:.83rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif">Filtrer</button>
        <?php if ($filter_q || $filter_year): ?>
          <a href="mes-commandes.php" style="background:transparent;color:#8b92a8;border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 14px;font-size:.8rem;text-decoration:none">✕ Reset</a>
        <?php endif; ?>
      </div>
      <!-- Pills années -->
      <div class="d-flex gap-2 flex-wrap mb-4">
        <a href="mes-commandes.php<?= $filter_q ? '?q='.urlencode($filter_q) : '' ?>" class="year-pill <?= !$filter_year ? 'active' : '' ?>">Toutes</a>
        <?php foreach ($years as $y): ?>
        <a href="mes-commandes.php?annee=<?= $y ?><?= $filter_q ? '&q='.urlencode($filter_q) : '' ?>" class="year-pill <?= $filter_year===$y ? 'active' : '' ?>"><?= $y ?></a>
        <?php endforeach; ?>
      </div>
    </form>

    <?php if (empty($filtered)): ?>
    <div class="no-orders">
      <div class="icon">📦</div>
      <p style="font-size:.88rem"><?= $filter_q || $filter_year ? 'Aucune commande ne correspond à votre recherche.' : 'Aucune commande pour l\'instant.' ?></p>
      <?php if (!$filter_q && !$filter_year): ?>
        <a href="catalogue.php" style="color:var(--cyan);font-size:.85rem;text-decoration:none">Découvrir nos services →</a>
      <?php endif; ?>
    </div>

    <?php else: ?>

    <?php foreach ($by_year as $year => $year_orders): ?>
    <div class="year-group">
      <div class="year-label"><?= $year ?> <span style="font-size:.75rem;font-weight:500;color:#5c6378"><?= count($year_orders) ?> commande(s)</span></div>

      <?php foreach ($year_orders as $o):
        $items = $order_items_cache[$o['id']] ?? [];
        $services = implode(', ', array_slice(array_map(fn($i) => htmlspecialchars($i['name']??''), $items), 0, 3));
        if (count($items) > 3) $services .= ' +' . (count($items)-3);
      ?>
      <div class="order-row" id="row-<?= (int)$o['id'] ?>" onclick="toggleOrder(<?= (int)$o['id'] ?>)">
        <div class="order-top">
          <span class="order-id">#<?= str_pad($o['id'],6,'0',STR_PAD_LEFT) ?></span>
          <div class="flex-grow-1">
            <div class="order-name"><?= htmlspecialchars($o['billing_name'] ?? $user['prenom'].' '.$user['nom']) ?></div>
            <?php if ($services): ?><div class="order-services"><?= $services ?></div><?php endif; ?>
          </div>
          <span class="order-date"><?= date('d/m/Y', strtotime($o['created_at'])) ?></span>
          <span class="order-total"><?= number_format((float)$o['total'],2,',','&nbsp;') ?> €</span>
          <span class="order-chevron">▶</span>
        </div>

        <!-- DÉTAIL -->
        <div class="order-detail" id="detail-<?= (int)$o['id'] ?>">
          <?php
          // Charger les détails complets de cette commande
          $dstmt = $connexion->prepare("SELECT * FROM orders WHERE id=? AND user_id=?");
          $dstmt->execute([$o['id'], $user_id]);
          $dorder = $dstmt->fetch();
          ?>
          <div class="detail-grid">
            <div class="detail-box">
              <div class="detail-box-label">Facturation</div>
              <div class="detail-box-val">
                <?= htmlspecialchars($dorder['billing_name'] ?? '') ?><br>
                <?php if (!empty($dorder['billing_address'])): ?>
                  <?= nl2br(htmlspecialchars($dorder['billing_address'])) ?>
                <?php endif; ?>
              </div>
            </div>
            <div class="detail-box">
              <div class="detail-box-label">Paiement</div>
              <div class="detail-box-val">
                <?php
                // Masquer les infos carte (juste les 4 derniers)
                $card = $dorder['card_last4'] ?? null;
                if ($card): ?>
                  💳 •••• •••• •••• <?= htmlspecialchars($card) ?>
                <?php else: ?>
                  Paiement sécurisé
                <?php endif; ?>
                <br>
                <span style="font-size:.75rem;color:#5c6378"><?= date('d/m/Y à H:i', strtotime($o['created_at'])) ?></span>
              </div>
            </div>
          </div>

          <!-- Tableau des services -->
          <?php if ($items): ?>
          <table class="items-table">
            <thead><tr><th>Service</th><th>Abonnement</th><th style="text-align:right">Prix</th></tr></thead>
            <tbody>
              <?php foreach ($items as $item): ?>
              <tr>
                <td><?= htmlspecialchars($item['name'] ?? 'Service') ?></td>
                <td><span style="font-size:.72rem;padding:2px 8px;border-radius:20px;background:rgba(38,208,206,.1);color:#26d0ce;border:1px solid rgba(38,208,206,.2)"><?= ucfirst($item['cycle'] ?? 'mensuel') ?></span></td>
                <td style="text-align:right;font-weight:600"><?= number_format((float)($item['price'] ?? 0),2,',',' ') ?> €</td>
              </tr>
              <?php endforeach; ?>
              <tr style="border-top:1px solid rgba(255,255,255,.1)">
                <td colspan="3" style="text-align:right;font-weight:700;color:#fff;padding-top:12px">Total</td>
                <td style="text-align:right;font-weight:700;color:#fff;padding-top:12px"><?= number_format((float)$o['total'],2,',',' ') ?> €</td>
              </tr>
            </tbody>
          </table>
          <?php endif; ?>

          <div style="display:flex;gap:10px;margin-top:4px" onclick="event.stopPropagation()">
            <a href="mes-commandes.php?facture=<?= (int)$o['id'] ?>" target="_blank" class="btn-facture">📄 Télécharger la facture</a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </main>
</div>

<footer>
  <a href="Cgu.php">CGU</a>
  <a href="mention_legales.php">Mentions légales</a>
  <a href="Contact.php">Contact</a>
  <span>© 2025 CYNA-IT</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleOrder(id) {
  var row = document.getElementById('row-' + id);
  row.classList.toggle('open');
}
// Auto-ouvrir si un order_id est dans l'URL
<?php if ($detail_id > 0): ?>
  document.addEventListener('DOMContentLoaded', function() {
    toggleOrder(<?= (int)$detail_id ?>);
    document.getElementById('row-<?= (int)$detail_id ?>').scrollIntoView({behavior:'smooth',block:'center'});
  });
<?php endif; ?>
</script>
</body>
</html>