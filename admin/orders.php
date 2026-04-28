<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/config.php';

// Pagination & filtres
$per_page      = 20;
$page          = max(1, (int)($_GET['page']    ?? 1));
$filter_status = trim($_GET['status']          ?? '');
$filter_q      = trim($_GET['q']               ?? '');
$filter_user   = (int)($_GET['user_id']        ?? 0);

$where  = [];
$params = [];

if ($filter_status !== '') { $where[] = "o.status = ?";        $params[] = $filter_status; }
if ($filter_user > 0)      { $where[] = "o.user_id = ?";       $params[] = $filter_user; }
if ($filter_q !== '') {
    $where[]  = "(u.email LIKE ? OR o.billing_name LIKE ? OR o.id LIKE ?)";
    $like = "%$filter_q%";
    $params = array_merge($params, [$like, $like, $like]);
}

$where_sql = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';

$count_stmt = $connexion->prepare("SELECT COUNT(*) FROM orders o LEFT JOIN utilisateurs u ON u.id=o.user_id $where_sql");
$count_stmt->execute($params);
$total       = (int)$count_stmt->fetchColumn();
$total_pages = max(1, ceil($total / $per_page));
$page        = min($page, $total_pages);
$offset      = ($page - 1) * $per_page;

$stmt = $connexion->prepare("
    SELECT o.*, u.email, u.prenom, u.nom
    FROM orders o LEFT JOIN utilisateurs u ON u.id = o.user_id
    $where_sql
    ORDER BY o.id DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Stats
$total_orders   = (int)$connexion->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$paid_orders    = (int)$connexion->query("SELECT COUNT(*) FROM orders WHERE status='paid'")->fetchColumn();
$pending_orders = (int)$connexion->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','pending_3ds')")->fetchColumn();
$revenue        = (float)$connexion->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status='paid'")->fetchColumn();

$build_url = function($extra = []) use ($filter_status, $filter_q, $filter_user) {
    $p = array_merge(['status' => $filter_status, 'q' => $filter_q, 'user_id' => $filter_user], $extra);
    $filtered = array_filter($p, function($v) { return $v !== '' && $v !== 0; });
    return 'orders.php?' . http_build_query($filtered);
};

function status_badge($s) {
    $s = $s ?? 'test';
    if ($s === 'paid')        return '<span class="badge badge-green">✅ Payé</span>';
    if ($s === 'pending')     return '<span class="badge badge-yellow">⏳ En attente</span>';
    if ($s === 'pending_3ds') return '<span class="badge badge-yellow">🔐 3D Secure</span>';
    if ($s === 'failed')      return '<span class="badge badge-red">❌ Échoué</span>';
    if ($s === 'refunded')    return '<span class="badge" style="background:rgba(139,92,246,.1);color:#a78bfa;border:1px solid rgba(139,92,246,.2)">↩ Remboursé</span>';
    if ($s === 'test')        return '<span class="badge" style="background:rgba(99,102,241,.1);color:#818cf8;border:1px solid rgba(99,102,241,.2)">🧪 Test</span>';
    return '<span class="badge badge-gray">' . htmlspecialchars($s) . '</span>';
}
?>

<div class="ph">
  <div class="ph-left">
    <h1>Commandes</h1>
    <p><?= $total_orders ?> commande(s) au total — <?= number_format($revenue,2,',',' ') ?> € encaissés</p>
  </div>
</div>

<!-- KPI -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon">◎</div>
      <div class="stat-info"><div class="stat-val"><?= $total_orders ?></div><div class="stat-lbl">Total commandes</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="color:#4ade80">✅</div>
      <div class="stat-info"><div class="stat-val"><?= $paid_orders ?></div><div class="stat-lbl">Payées</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card" style="<?= $pending_orders > 0 ? 'border-color:rgba(245,158,11,.3)' : '' ?>">
      <div class="stat-icon" style="<?= $pending_orders > 0 ? 'color:#fbbf24' : '' ?>">⏳</div>
      <div class="stat-info"><div class="stat-val"><?= $pending_orders ?></div><div class="stat-lbl">En attente</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon" style="font-size:.85rem">€</div>
      <div class="stat-info"><div class="stat-val"><?= number_format($revenue,0,',',' ') ?> €</div><div class="stat-lbl">CA encaissé</div></div>
    </div>
  </div>
</div>

<!-- FILTRES -->
<form method="GET" action="orders.php" style="margin-bottom:14px">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="search" name="q" value="<?= htmlspecialchars($filter_q) ?>"
      placeholder="Email, nom, N° commande..."
      style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 13px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;width:240px">
    <select name="status" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none">
      <option value="">Tous les statuts</option>
      <option value="paid"        <?= $filter_status==='paid'?'selected':'' ?>>✅ Payées</option>
      <option value="pending"     <?= $filter_status==='pending'?'selected':'' ?>>⏳ En attente</option>
      <option value="pending_3ds" <?= $filter_status==='pending_3ds'?'selected':'' ?>>🔐 3D Secure</option>
      <option value="failed"      <?= $filter_status==='failed'?'selected':'' ?>>❌ Échouées</option>
      <option value="test"        <?= $filter_status==='test'?'selected':'' ?>>🧪 Test</option>
      <option value="refunded"    <?= $filter_status==='refunded'?'selected':'' ?>>↩ Remboursées</option>
    </select>
    <button type="submit" class="btn-cyna" style="padding:8px 18px;font-size:.83rem">Filtrer</button>
    <?php if ($filter_q || $filter_status || $filter_user): ?>
      <a href="orders.php" class="btn-ghost" style="padding:7px 14px;font-size:.8rem">✕ Reset</a>
    <?php endif; ?>
    <span style="margin-left:auto;font-size:.78rem;color:var(--c-muted)"><?= $total ?> résultat(s)</span>
  </div>
</form>

<!-- TABLE -->
<div class="card">
  <table class="ctable">
    <thead>
      <tr>
        <th>N°</th>
        <th>Client</th>
        <th>Facturation</th>
        <th>Montant</th>
        <th>Statut</th>
        <th>Date</th>
        <th class="text-right">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$orders): ?>
        <tr><td colspan="7"><div class="empty-state"><div class="icon">◎</div><p>Aucune commande trouvée</p></div></td></tr>
      <?php else: foreach ($orders as $o): ?>
      <tr>
        <td><span class="badge badge-blue">#<?= str_pad($o['id'],5,'0',STR_PAD_LEFT) ?></span></td>
        <td class="muted"><?= htmlspecialchars($o['email'] ?? '—') ?></td>
        <td class="muted"><?= htmlspecialchars($o['billing_name'] ?? '—') ?></td>
        <td style="font-weight:600;color:#fff"><?= number_format((float)$o['total'],2,',',' ') ?> €</td>
        <td><?= status_badge($o['status'] ?? null) ?></td>
        <td class="mono"><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
        <td class="text-right">
          <a href="order_view.php?id=<?= (int)$o['id'] ?>" class="btn-view">Voir →</a>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <!-- PAGINATION -->
  <?php if ($total_pages > 1): ?>
  <div style="padding:16px 20px;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div style="font-size:.78rem;color:var(--c-muted)">
      Page <?= $page ?> / <?= $total_pages ?> — <?= $total ?> commande(s)
    </div>
    <div style="display:flex;gap:4px">
      <?php if ($page > 1): ?>
        <a href="<?= $build_url(['page' => $page-1]) ?>" class="btn-view">← Préc</a>
      <?php endif; ?>
      <?php for ($i = max(1,$page-2); $i <= min($total_pages,$page+2); $i++): ?>
        <a href="<?= $build_url(['page' => $i]) ?>"
           style="padding:5px 12px;border-radius:6px;font-size:.78rem;text-decoration:none;<?= $i===$page ? 'background:var(--grad);color:#fff' : 'background:rgba(255,255,255,.06);color:#8b92a8' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
      <?php if ($page < $total_pages): ?>
        <a href="<?= $build_url(['page' => $page+1]) ?>" class="btn-view">Suiv →</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>