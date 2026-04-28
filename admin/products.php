<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/config.php';

$cats = $connexion->query("SELECT id, name FROM categories ORDER BY sort_order ASC")->fetchAll();

// Pagination
$per_page    = 15;
$page        = max(1, (int)($_GET['page'] ?? 1));
$offset      = ($page - 1) * $per_page;
$filter_cat  = (int)($_GET['cat']      ?? 0);
$filter_dispo = $_GET['dispo'] ?? '';
$filter_q    = trim($_GET['q']         ?? '');

$where  = [];
$params = [];
if ($filter_cat > 0)       { $where[] = "p.category_id = ?";    $params[] = $filter_cat; }
if ($filter_dispo === '1') { $where[] = "p.is_available = 1"; }
if ($filter_dispo === '0') { $where[] = "p.is_available = 0"; }
if ($filter_q !== '')      { $where[] = "p.name LIKE ?"; $params[] = "%$filter_q%"; }

$where_sql = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';

$total_products = (int)$connexion->prepare("SELECT COUNT(*) FROM products p $where_sql")->execute($params) ?
    $connexion->prepare("SELECT COUNT(*) FROM products p $where_sql")->execute($params) : 0;

// Refaire proprement
$count_stmt = $connexion->prepare("SELECT COUNT(*) FROM products p $where_sql");
$count_stmt->execute($params);
$total_products = (int)$count_stmt->fetchColumn();
$total_pages    = max(1, ceil($total_products / $per_page));
$page           = min($page, $total_pages);
$offset         = ($page - 1) * $per_page;

$prod_stmt = $connexion->prepare("
    SELECT p.*, c.name AS cat_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    $where_sql
    ORDER BY p.is_available ASC, p.is_featured DESC, p.featured_order ASC, p.id DESC
    LIMIT $per_page OFFSET $offset
");
$prod_stmt->execute($params);
$products = $prod_stmt->fetchAll();

// Stats alertes
$nb_indispo  = (int)$connexion->query("SELECT COUNT(*) FROM products WHERE is_available=0")->fetchColumn();
$nb_total    = (int)$connexion->query("SELECT COUNT(*) FROM products")->fetchColumn();
$nb_featured = (int)$connexion->query("SELECT COUNT(*) FROM products WHERE is_featured=1")->fetchColumn();

$build_url = function($extra = []) use ($filter_cat, $filter_dispo, $filter_q) {
    $p = array_merge(['cat' => $filter_cat, 'dispo' => $filter_dispo, 'q' => $filter_q], $extra);
    $filtered = array_filter($p, function($v) { return $v !== '' && $v !== 0; });
    return 'products.php?' . http_build_query($filtered);
};
?>

<div class="ph">
  <div class="ph-left">
    <h1>Produits SaaS</h1>
    <p><?= $nb_total ?> service(s) — <?= $nb_indispo ?> indisponible(s)</p>
  </div>
</div>

<!-- ALERTE STOCK -->
<?php if ($nb_indispo > 0): ?>
<div style="background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.2);border-radius:12px;padding:14px 18px;margin-bottom:16px;display:flex;align-items:center;gap:12px">
  <span style="font-size:1.2rem">⚠️</span>
  <div style="flex:1">
    <div style="font-size:.85rem;font-weight:700;color:#fbbf24"><?= $nb_indispo ?> service(s) indisponible(s)</div>
    <div style="font-size:.75rem;color:rgba(245,158,11,.6);margin-top:2px">Ces services sont masqués pour les clients. Pensez à les réactiver.</div>
  </div>
  <a href="<?= $build_url(['dispo' => '0']) ?>" style="font-size:.75rem;font-weight:600;color:#fbbf24;text-decoration:none;background:rgba(245,158,11,.1);border:1px solid rgba(245,158,11,.2);border-radius:8px;padding:5px 12px">Voir les indispos →</a>
</div>
<?php endif; ?>

<!-- KPI -->
<div class="row g-3 mb-4">
  <div class="col-4">
    <div class="stat-card">
      <div class="stat-icon">⬡</div>
      <div class="stat-info"><div class="stat-val"><?= $nb_total ?></div><div class="stat-lbl">Total services</div></div>
    </div>
  </div>
  <div class="col-4">
    <div class="stat-card" style="<?= $nb_indispo > 0 ? 'border-color:rgba(245,158,11,.3)' : '' ?>">
      <div class="stat-icon" style="<?= $nb_indispo > 0 ? 'background:rgba(245,158,11,.15);color:#fbbf24' : '' ?>">⚠</div>
      <div class="stat-info"><div class="stat-val" style="<?= $nb_indispo > 0 ? 'color:#fbbf24' : '' ?>"><?= $nb_indispo ?></div><div class="stat-lbl">Indisponibles</div></div>
    </div>
  </div>
  <div class="col-4">
    <div class="stat-card">
      <div class="stat-icon">⭐</div>
      <div class="stat-info"><div class="stat-val"><?= $nb_featured ?></div><div class="stat-lbl">Mis en avant</div></div>
    </div>
  </div>
</div>

<!-- FORMULAIRE AJOUT -->
<div class="card mb-3">
  <div class="card-head">Nouveau produit / service</div>
  <div class="card-body">
    <form method="POST" action="product_save.php">
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label">Nom du service *</label>
          <input class="form-control" name="name" required placeholder="Ex : Cyna EDR Pro">
        </div>
        <div class="col-md-3">
          <label class="form-label">Catégorie</label>
          <select class="form-select" name="category_id">
            <option value="">— Aucune —</option>
            <?php foreach ($cats as $c): ?>
              <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Prix mensuel (€)</label>
          <input class="form-control" name="price_monthly" type="number" step="0.01" value="0.00">
        </div>
        <div class="col-md-2">
          <label class="form-label">Prix annuel (€)</label>
          <input class="form-control" name="price_yearly" type="number" step="0.01" value="0.00">
        </div>
        <div class="col-md-1">
          <label class="form-label">Dispo</label>
          <select class="form-select" name="is_available">
            <option value="1">Oui</option>
            <option value="0">Non</option>
          </select>
        </div>
      </div>
      <div class="row g-3">
        <div class="col-md-4">
          <label class="form-label">Image (chemin)</label>
          <input class="form-control" name="image_path" placeholder="images/edr.jpg">
        </div>
        <div class="col-md-2">
          <label class="form-label">Ordre d'affichage</label>
          <input class="form-control" name="featured_order" type="number" value="0">
        </div>
        <div class="col-md-2">
          <label class="form-label">Mis en avant</label>
          <select class="form-select" name="is_featured">
            <option value="0">Non</option>
            <option value="1">Oui</option>
          </select>
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <button type="submit" class="btn-cyna w-100">+ Ajouter le service</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- FILTRES -->
<form method="GET" action="products.php" style="margin-bottom:14px">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="search" name="q" value="<?= htmlspecialchars($filter_q) ?>"
      placeholder="Rechercher un service..."
      style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 13px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;width:220px">
    <select name="cat" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none">
      <option value="0">Toutes catégories</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= (int)$c['id'] ?>" <?= $filter_cat===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="dispo" style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none">
      <option value="">Tous</option>
      <option value="1" <?= $filter_dispo==='1'?'selected':'' ?>>✅ Disponibles</option>
      <option value="0" <?= $filter_dispo==='0'?'selected':'' ?>>⚠ Indisponibles</option>
    </select>
    <button type="submit" class="btn-cyna" style="padding:8px 18px;font-size:.83rem">Filtrer</button>
    <?php if ($filter_q || $filter_cat || $filter_dispo !== ''): ?>
      <a href="products.php" class="btn-ghost" style="padding:7px 14px;font-size:.8rem">✕ Reset</a>
    <?php endif; ?>
  </div>
</form>

<!-- TABLE -->
<div class="card">
  <table class="ctable">
    <thead>
      <tr>
        <th>Service</th>
        <th>Catégorie</th>
        <th>Prix mensuel</th>
        <th>Prix annuel</th>
        <th>Statut</th>
        <th>Mis en avant</th>
        <th class="text-right">Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$products): ?>
        <tr><td colspan="7"><div class="empty-state"><div class="icon">⬡</div><p>Aucun produit trouvé</p></div></td></tr>
      <?php else: foreach ($products as $p): ?>
      <tr style="<?= !$p['is_available'] ? 'opacity:.65' : '' ?>">
        <td>
          <div style="display:flex;align-items:center;gap:10px">
            <?php if ($p['image_path']): ?>
              <img src="../<?= htmlspecialchars($p['image_path']) ?>" style="width:32px;height:32px;border-radius:6px;object-fit:cover" onerror="this.style.display='none'">
            <?php endif; ?>
            <div>
              <div style="font-weight:600;color:#fff;font-size:.84rem"><?= htmlspecialchars($p['name']) ?></div>
              <?php if (!$p['is_available']): ?>
                <span style="font-size:.65rem;background:rgba(245,158,11,.12);color:#fbbf24;border:1px solid rgba(245,158,11,.2);border-radius:20px;padding:1px 7px">⚠ Indisponible</span>
              <?php endif; ?>
            </div>
          </div>
        </td>
        <td class="muted"><?= htmlspecialchars($p['cat_name'] ?? '—') ?></td>
        <td style="font-weight:600;color:#fff"><?= number_format((float)$p['price_monthly'],2,',',' ') ?> €</td>
        <td class="muted"><?= number_format((float)$p['price_yearly'],2,',',' ') ?> €</td>
        <td>
          <?php if ($p['is_available']): ?>
            <span class="badge badge-green">● Disponible</span>
          <?php else: ?>
            <span class="badge badge-yellow">⚠ Indisponible</span>
          <?php endif; ?>
        </td>
        <td><?= $p['is_featured'] ? '<span class="badge badge-blue">⭐ Oui</span>' : '<span class="muted">—</span>' ?></td>
        <td class="text-right">
          <div style="display:flex;gap:6px;justify-content:flex-end">
            <a href="product_edit.php?id=<?= (int)$p['id'] ?>" class="btn-view">✏ Modifier</a>
            <form method="POST" action="product_delete.php" style="display:inline" onsubmit="return confirm('Supprimer ce service ?')">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button type="submit" class="btn-del">🗑</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>

  <!-- PAGINATION -->
  <?php if ($total_pages > 1): ?>
  <div style="padding:16px 20px;border-top:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px">
    <div style="font-size:.78rem;color:var(--c-muted)">
      Page <?= $page ?> / <?= $total_pages ?> — <?= $total_products ?> produit(s)
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