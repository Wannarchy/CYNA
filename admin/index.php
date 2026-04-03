<?php
require_once __DIR__ . '/header.php';

// ── KPI ─────────────────────────────────────────────────────
$nb_cats     = (int)$connexion->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$nb_products = (int)$connexion->query("SELECT COUNT(*) FROM products")->fetchColumn();
$nb_orders   = (int)$connexion->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$nb_slides   = (int)$connexion->query("SELECT COUNT(*) FROM homepage_slides WHERE is_active=1")->fetchColumn();
$revenue     = (float)$connexion->query("SELECT COALESCE(SUM(total),0) FROM orders")->fetchColumn();

// ── DERNIÈRES COMMANDES ──────────────────────────────────────
$latest = $connexion->query("
    SELECT o.id, o.total, o.created_at, o.billing_name, u.email
    FROM orders o LEFT JOIN utilisateurs u ON u.id = o.user_id
    ORDER BY o.created_at DESC LIMIT 6
")->fetchAll();

// ── GRAPHIQUE 1 : Ventes par jour (7 derniers jours) ─────────
$sales_days = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $label = date('d/m', strtotime("-$i days"));
    $stmt = $connexion->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at) = ?");
    $stmt->execute([$date]);
    $sales_days[] = ['label' => $label, 'value' => (float)$stmt->fetchColumn()];
}

// ── GRAPHIQUE 2 : Ventes par semaine (5 dernières semaines) ──
$sales_weeks = [];
for ($i = 4; $i >= 0; $i--) {
    $start = date('Y-m-d', strtotime("monday -".(($i)*7)." days"));
    $end   = date('Y-m-d', strtotime("sunday -".(($i)*7)." days"));
    $label = 'S'.date('W', strtotime($start));
    $stmt  = $connexion->prepare("SELECT COALESCE(SUM(total),0) FROM orders WHERE DATE(created_at) BETWEEN ? AND ?");
    $stmt->execute([$start, $end]);
    $sales_weeks[] = ['label' => $label, 'value' => (float)$stmt->fetchColumn()];
}

// ── GRAPHIQUE 3 : Ventes par catégorie (camembert) ───────────
$cat_sales_stmt = $connexion->query("
    SELECT c.name, COALESCE(SUM(oi.price), 0) AS total
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    LEFT JOIN order_items oi ON oi.product_id = p.id
    GROUP BY c.id, c.name
    HAVING total > 0
    ORDER BY total DESC
");
$cat_sales = $cat_sales_stmt->fetchAll();

// Si pas de données catégories, on essaie sans filtre HAVING
if (empty($cat_sales)) {
    $cat_sales_stmt = $connexion->query("
        SELECT c.name, COALESCE(SUM(oi.price), 0) AS total
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id
        LEFT JOIN order_items oi ON oi.product_id = p.id
        GROUP BY c.id, c.name
        ORDER BY total DESC
        LIMIT 6
    ");
    $cat_sales = $cat_sales_stmt->fetchAll();
}

// Encodage JSON pour Chart.js
$days_labels  = json_encode(array_column($sales_days,  'label'));
$days_values  = json_encode(array_column($sales_days,  'value'));
$weeks_labels = json_encode(array_column($sales_weeks, 'label'));
$weeks_values = json_encode(array_column($sales_weeks, 'value'));
$cat_labels   = json_encode(array_column($cat_sales,   'name'));
$cat_values   = json_encode(array_column($cat_sales,   'total'));
?>

<div class="ph">
  <div class="ph-left">
    <h1>Tableau de bord</h1>
    <p>Vue d'ensemble des performances CYNA</p>
  </div>
</div>

<!-- ── KPI ───────────────────────────────────────────────── -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <a href="categories.php" class="stat-card">
      <div class="stat-icon">▦</div>
      <div class="stat-info"><div class="stat-val"><?= $nb_cats ?></div><div class="stat-lbl">Catégories</div></div>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <a href="products.php" class="stat-card">
      <div class="stat-icon">⬡</div>
      <div class="stat-info"><div class="stat-val"><?= $nb_products ?></div><div class="stat-lbl">Produits SaaS</div></div>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <a href="orders.php" class="stat-card">
      <div class="stat-icon">◎</div>
      <div class="stat-info"><div class="stat-val"><?= $nb_orders ?></div><div class="stat-lbl">Commandes</div></div>
    </a>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="font-size:.85rem">€€</div>
      <div class="stat-info"><div class="stat-val"><?= number_format($revenue,0,',',' ') ?> €</div><div class="stat-lbl">Chiffre d'affaires</div></div>
    </div>
  </div>
</div>

<!-- ── GRAPHIQUES ────────────────────────────────────────── -->
<div class="row g-3 mb-3">

  <!-- Histogramme ventes -->
  <div class="col-12 col-xl-8">
    <div class="card">
      <div class="card-head">
        Ventes
        <div style="display:flex;gap:6px">
          <button id="btn-days" onclick="switchChart('days')" style="background:var(--grad);color:#fff;border:none;border-radius:6px;padding:4px 12px;font-size:.72rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif">7 jours</button>
          <button id="btn-weeks" onclick="switchChart('weeks')" style="background:rgba(255,255,255,.08);color:var(--c-muted2);border:1px solid var(--c-border2);border-radius:6px;padding:4px 12px;font-size:.72rem;font-weight:500;cursor:pointer;font-family:'DM Sans',sans-serif">5 semaines</button>
        </div>
      </div>
      <div class="card-body" style="padding:20px">
        <canvas id="salesChart" height="220"></canvas>
      </div>
    </div>
  </div>

  <!-- Camembert catégories -->
  <div class="col-12 col-xl-4">
    <div class="card">
      <div class="card-head">Ventes par catégorie</div>
      <div class="card-body" style="padding:20px;display:flex;flex-direction:column;align-items:center;gap:16px">
        <?php if (array_sum(array_column($cat_sales, 'total')) > 0): ?>
          <canvas id="catChart" style="max-width:200px;max-height:200px"></canvas>
          <!-- Légende custom -->
          <div id="cat-legend" style="width:100%;display:flex;flex-direction:column;gap:6px"></div>
        <?php else: ?>
          <div class="empty-state" style="padding:32px 0">
            <div class="icon" style="font-size:2rem;margin-bottom:8px;opacity:.3">◎</div>
            <p style="font-size:.8rem">Aucune vente par catégorie pour l'instant</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- ── DERNIÈRES COMMANDES + RACCOURCIS ─────────────────── -->
<div class="row g-3">
  <div class="col-12 col-xl-8">
    <div class="card">
      <div class="card-head">Dernières commandes <a href="orders.php" class="btn-view">Tout voir →</a></div>
      <table class="ctable">
        <thead><tr><th>N°</th><th>Client</th><th>Facturation</th><th>Montant</th><th>Date</th><th></th></tr></thead>
        <tbody>
          <?php if (!$latest): ?>
            <tr><td colspan="6"><div class="empty-state"><div class="icon">◎</div><p>Aucune commande</p></div></td></tr>
          <?php else: foreach ($latest as $o): ?>
          <tr>
            <td><span class="badge badge-blue">#<?= (int)$o['id'] ?></span></td>
            <td class="muted"><?= htmlspecialchars($o['email'] ?? '—') ?></td>
            <td class="muted"><?= htmlspecialchars($o['billing_name'] ?? '—') ?></td>
            <td style="font-weight:600;color:#fff"><?= number_format((float)$o['total'],2,',',' ') ?> €</td>
            <td class="mono"><?= date('d/m/Y H:i',strtotime($o['created_at'])) ?></td>
            <td class="text-right"><a href="order_view.php?id=<?= (int)$o['id'] ?>" class="btn-view">Voir</a></td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="col-12 col-xl-4">
    <div class="card" style="height:100%">
      <div class="card-head">Accès rapides</div>
      <div class="card-body d-flex flex-column gap-2">
        <?php foreach ([
          ['products.php',   '⬡', 'Produits',        $nb_products.' produit(s)'],
          ['categories.php', '▦', 'Catégories',       $nb_cats.' catégorie(s)'],
          ['slides.php',     '▣', 'Slides homepage',  $nb_slides.' active(s)'],
          ['home_text.php',  '≡', "Texte d'accueil",  'Modifier'],
        ] as [$url,$icon,$label,$sub]): ?>
        <a href="<?= $url ?>" class="stat-card" style="padding:12px 14px">
          <div class="stat-icon" style="width:34px;height:34px;font-size:.82rem;border-radius:8px"><?= $icon ?></div>
          <div class="stat-info">
            <div style="font-weight:500;font-size:.82rem;color:#fff"><?= $label ?></div>
            <div class="stat-lbl"><?= $sub ?></div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<!-- ── CHART.JS ───────────────────────────────────────────── -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// Données PHP → JS
var daysLabels  = <?= $days_labels ?>;
var daysValues  = <?= $days_values ?>;
var weeksLabels = <?= $weeks_labels ?>;
var weeksValues = <?= $weeks_values ?>;
var catLabels   = <?= $cat_labels ?>;
var catValues   = <?= $cat_values ?>;

// Palette Cyna
var COLORS = ['#26d0ce','#4f8cff','#a78bfa','#34d399','#f59e0b','#f87171','#60a5fa'];
var GRID   = 'rgba(255,255,255,0.05)';
var TEXT   = '#8b92a8';

// ── Histogramme ────────────────────────────────────────────
var salesCtx = document.getElementById('salesChart').getContext('2d');

var gradient = salesCtx.createLinearGradient(0, 0, 0, 220);
gradient.addColorStop(0,   'rgba(38,208,206,0.35)');
gradient.addColorStop(1,   'rgba(38,208,206,0.02)');

var salesChart = new Chart(salesCtx, {
  type: 'bar',
  data: {
    labels: daysLabels,
    datasets: [{
      label: 'Ventes (€)',
      data: daysValues,
      backgroundColor: gradient,
      borderColor: '#26d0ce',
      borderWidth: 1.5,
      borderRadius: 6,
      borderSkipped: false,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#0f1628',
        borderColor: 'rgba(255,255,255,.1)',
        borderWidth: 1,
        titleColor: '#fff',
        bodyColor: TEXT,
        callbacks: {
          label: function(ctx) { return ' ' + ctx.parsed.y.toFixed(2) + ' €'; }
        }
      }
    },
    scales: {
      x: {
        grid: { color: GRID },
        ticks: { color: TEXT, font: { size: 11 } }
      },
      y: {
        grid: { color: GRID },
        ticks: {
          color: TEXT, font: { size: 11 },
          callback: function(v) { return v + ' €'; }
        },
        beginAtZero: true
      }
    }
  }
});

// Switcher 7 jours / 5 semaines
function switchChart(mode) {
  var btnD = document.getElementById('btn-days');
  var btnW = document.getElementById('btn-weeks');
  var activeStyle = 'background:var(--grad);color:#fff;border:none;border-radius:6px;padding:4px 12px;font-size:.72rem;font-weight:600;cursor:pointer;font-family:DM Sans,sans-serif';
  var inactiveStyle = 'background:rgba(255,255,255,.08);color:#8b92a8;border:1px solid rgba(255,255,255,.12);border-radius:6px;padding:4px 12px;font-size:.72rem;font-weight:500;cursor:pointer;font-family:DM Sans,sans-serif';

  if (mode === 'days') {
    salesChart.data.labels   = daysLabels;
    salesChart.data.datasets[0].data = daysValues;
    btnD.style.cssText = activeStyle;
    btnW.style.cssText = inactiveStyle;
  } else {
    salesChart.data.labels   = weeksLabels;
    salesChart.data.datasets[0].data = weeksValues;
    btnW.style.cssText = activeStyle;
    btnD.style.cssText = inactiveStyle;
  }
  salesChart.update();
}

// ── Camembert ──────────────────────────────────────────────
<?php if (array_sum(array_column($cat_sales, 'total')) > 0): ?>
var catCtx = document.getElementById('catChart').getContext('2d');
new Chart(catCtx, {
  type: 'doughnut',
  data: {
    labels: catLabels,
    datasets: [{
      data: catValues,
      backgroundColor: COLORS.slice(0, catLabels.length),
      borderColor: '#131720',
      borderWidth: 3,
      hoverOffset: 8
    }]
  },
  options: {
    responsive: true,
    cutout: '62%',
    plugins: {
      legend: { display: false },
      tooltip: {
        backgroundColor: '#0f1628',
        borderColor: 'rgba(255,255,255,.1)',
        borderWidth: 1,
        titleColor: '#fff',
        bodyColor: TEXT,
        callbacks: {
          label: function(ctx) {
            var total = ctx.dataset.data.reduce(function(a,b){return a+b;}, 0);
            var pct = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
            return ' ' + ctx.parsed.toFixed(2) + ' € (' + pct + '%)';
          }
        }
      }
    }
  }
});

// Légende custom
var legend = document.getElementById('cat-legend');
catLabels.forEach(function(lbl, i) {
  var total = catValues.reduce(function(a,b){return a+b;}, 0);
  var pct   = total > 0 ? ((catValues[i] / total) * 100).toFixed(1) : 0;
  var item  = document.createElement('div');
  item.style.cssText = 'display:flex;align-items:center;justify-content:space-between;font-size:.75rem;color:#8b92a8;gap:8px';
  item.innerHTML =
    '<div style="display:flex;align-items:center;gap:7px">' +
      '<span style="width:10px;height:10px;border-radius:3px;background:' + COLORS[i] + ';flex-shrink:0;display:inline-block"></span>' +
      '<span>' + lbl + '</span>' +
    '</div>' +
    '<span style="color:#e8eaf2;font-weight:600">' + pct + '%</span>';
  legend.appendChild(item);
});
<?php endif; ?>
</script>

<?php require_once __DIR__ . '/footer.php'; ?>