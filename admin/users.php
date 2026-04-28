<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/config.php';

// Actions
// Ajouter la colonne est_actif si elle n'existe pas encore
try {
    $connexion->exec("ALTER TABLE utilisateurs ADD COLUMN est_actif TINYINT(1) DEFAULT 1");
} catch (Exception $e) { /* colonne déjà présente */ }

$success = '';
$error   = '';

// Suspendre / activer un compte
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle_status') {
    $uid    = (int)$_POST['user_id'];
    $status = (int)$_POST['new_status'];
    $connexion->prepare("UPDATE utilisateurs SET est_actif=? WHERE id=?")->execute([$status, $uid]);
    $success = $status ? "Compte activé." : "Compte suspendu.";
}

// Supprimer un utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $uid = (int)$_POST['user_id'];
    try {
        $connexion->prepare("DELETE FROM utilisateurs WHERE id=? AND is_admin=0")->execute([$uid]);
        $success = "Utilisateur supprimé.";
    } catch (Exception $e) {
        $error = "Impossible de supprimer cet utilisateur (commandes associées).";
    }
}

// Filtres
$filter_q      = trim($_GET['q']      ?? '');
$filter_status = $_GET['status'] ?? '';

// Build query
$where  = [];
$params = [];
if ($filter_q !== '') {
    $where[]  = "(u.email LIKE ? OR u.prenom LIKE ? OR u.nom LIKE ?)";
    $like = "%$filter_q%";
    $params = array_merge($params, [$like, $like, $like]);
}
if ($filter_status === 'confirmed')    { $where[] = "u.est_confirme=1"; }
if ($filter_status === 'unconfirmed')  { $where[] = "u.est_confirme=0"; }
if ($filter_status === 'active')       { $where[] = "(u.est_actif IS NULL OR u.est_actif=1)"; }
if ($filter_status === 'suspended')    { $where[] = "u.est_actif=0"; }

$sql = "
    SELECT u.id, u.prenom, u.nom, u.email, u.est_confirme, u.is_admin,
           COALESCE(u.est_actif, 1) AS est_actif,
           COUNT(DISTINCT o.id) AS nb_orders,
           COALESCE(SUM(o.total), 0) AS total_spent
    FROM utilisateurs u
    LEFT JOIN orders o ON o.user_id = u.id
    " . (!empty($where) ? "WHERE " . implode(' AND ', $where) : "") . "
    GROUP BY u.id
    ORDER BY u.id DESC
    LIMIT 200
";

$stmt = $connexion->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Stats
$total_users     = (int)$connexion->query("SELECT COUNT(*) FROM utilisateurs")->fetchColumn();
$confirmed_users = (int)$connexion->query("SELECT COUNT(*) FROM utilisateurs WHERE est_confirme=1")->fetchColumn();
$admin_users     = (int)$connexion->query("SELECT COUNT(*) FROM utilisateurs WHERE is_admin=1")->fetchColumn();
?>

<div class="ph">
  <div class="ph-left">
    <h1>Utilisateurs</h1>
    <p><?= $total_users ?> compte(s) enregistré(s)</p>
  </div>
</div>

<!-- KPI -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon">👥</div>
      <div class="stat-info"><div class="stat-val"><?= $total_users ?></div><div class="stat-lbl">Total utilisateurs</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon">✅</div>
      <div class="stat-info"><div class="stat-val"><?= $confirmed_users ?></div><div class="stat-lbl">Comptes confirmés</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon">⏳</div>
      <div class="stat-info"><div class="stat-val"><?= $total_users - $confirmed_users ?></div><div class="stat-lbl">En attente confirmation</div></div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="stat-card">
      <div class="stat-icon">🔑</div>
      <div class="stat-info"><div class="stat-val"><?= $admin_users ?></div><div class="stat-lbl">Administrateurs</div></div>
    </div>
  </div>
</div>

<?php if ($success): ?>
<div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#4ade80;margin-bottom:16px">✅ <?= htmlspecialchars($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#f87171;margin-bottom:16px">⚠ <?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- FILTRES -->
<form method="GET" action="users.php" style="margin-bottom:16px">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="search" name="q" value="<?= htmlspecialchars($filter_q) ?>"
      placeholder="Rechercher par nom, email..."
      style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 13px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;width:280px;transition:border-color .15s"
      onfocus="this.style.borderColor='var(--c-accent)'" onblur="this.style.borderColor='rgba(255,255,255,.1)'">
    <select name="status" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;cursor:pointer">
      <option value="" <?= !$filter_status?'selected':'' ?>>Tous les statuts</option>
      <option value="confirmed"   <?= $filter_status==='confirmed'?'selected':''  ?>>✅ Email confirmé</option>
      <option value="unconfirmed" <?= $filter_status==='unconfirmed'?'selected':'' ?>>⏳ Non confirmé</option>
      <option value="active"      <?= $filter_status==='active'?'selected':''     ?>>🟢 Actif</option>
      <option value="suspended"   <?= $filter_status==='suspended'?'selected':''  ?>>🔴 Suspendu</option>
    </select>
    <button type="submit" class="btn-cyna" style="padding:8px 18px;font-size:.83rem">Filtrer</button>
    <?php if ($filter_q || $filter_status): ?>
      <a href="users.php" class="btn-ghost" style="padding:7px 14px;font-size:.8rem">✕ Réinitialiser</a>
    <?php endif; ?>
    <span style="margin-left:auto;font-size:.78rem;color:var(--c-muted)"><?= count($users) ?> résultat(s)</span>
  </div>
</form>

<!-- TABLE -->
<div class="card">
  <table class="ctable">
    <thead>
      <tr>
        <th>ID</th>
        <th>Utilisateur</th>
        <th>Email</th>
        <th>Statut</th>
        <th>Commandes</th>
        <th>Total dépensé</th>
        <th class="text-right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$users): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="icon">👥</div><p>Aucun utilisateur trouvé</p></div></td></tr>
      <?php else: foreach ($users as $u): ?>
      <tr>
        <td class="mono">#<?= (int)$u['id'] ?></td>
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:.75rem;font-weight:700;color:#fff;flex-shrink:0">
              <?= strtoupper(substr($u['prenom']??'U',0,1)) ?>
            </div>
            <div>
              <div style="font-weight:500;color:#fff;font-size:.84rem"><?= htmlspecialchars(trim(($u['prenom']??'').' '.($u['nom']??''))) ?: '—' ?></div>
              <?php if ($u['is_admin']): ?>
                <span class="badge badge-blue">Admin</span>
              <?php endif; ?>
            </div>
          </div>
        </td>
        <td class="muted"><?= htmlspecialchars($u['email']) ?></td>
        <td>
          <?php if ($u['est_confirme']): ?>
            <span class="badge badge-green">✅ Confirmé</span>
          <?php else: ?>
            <span class="badge badge-yellow">⏳ En attente</span>
          <?php endif; ?>
          &nbsp;
          <?php if ((int)$u['est_actif'] === 0): ?>
            <span class="badge badge-red">🔴 Suspendu</span>
          <?php else: ?>
            <span class="badge badge-gray">🟢 Actif</span>
          <?php endif; ?>
        </td>
        <td>
          <?php if ($u['nb_orders'] > 0): ?>
            <span style="font-weight:600;color:#fff"><?= (int)$u['nb_orders'] ?></span>
          <?php else: ?>
            <span class="muted">0</span>
          <?php endif; ?>
        </td>
        <td style="font-weight:600;color:<?= $u['total_spent'] > 0 ? '#fff' : 'var(--c-muted)' ?>">
          <?= number_format((float)$u['total_spent'],2,',',' ') ?> €
        </td>
        <td class="text-right">
          <div style="display:flex;gap:6px;justify-content:flex-end;flex-wrap:wrap">
            <?php if ($u['nb_orders'] > 0): ?>
              <a href="orders.php?user_id=<?= (int)$u['id'] ?>" class="btn-view" title="Voir les commandes">📦</a>
            <?php endif; ?>
            <?php if (!$u['is_admin']): ?>
              <!-- Toggle actif/suspendu -->
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="toggle_status">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <input type="hidden" name="new_status" value="<?= (int)$u['est_actif'] === 0 ? 1 : 0 ?>">
                <?php if ((int)$u['est_actif'] === 0): ?>
                  <button type="submit" class="btn-view" title="Activer" style="background:rgba(34,197,94,.1);color:#4ade80;border-color:rgba(34,197,94,.2)">✅ Activer</button>
                <?php else: ?>
                  <button type="submit" class="btn-del" title="Suspendre">⏸ Suspendre</button>
                <?php endif; ?>
              </form>
              <!-- Supprimer -->
              <?php if ($u['nb_orders'] == 0): ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer définitivement cet utilisateur ?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                <button type="submit" class="btn-del">🗑</button>
              </form>
              <?php endif; ?>
            <?php else: ?>
              <span class="badge badge-blue">Protégé</span>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<style>
/* Colonne stat-card override pour cette page */
.stat-card { display:flex; align-items:center; gap:14px; background:var(--c-card); border:1px solid var(--c-border); border-radius:var(--radius-lg); padding:16px 20px; text-decoration:none; color:var(--c-text); transition:all .2s; position:relative; overflow:hidden; }
</style>

<?php require_once __DIR__ . '/footer.php'; ?>