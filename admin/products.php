<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/config.php';

$cats = $connexion->query("SELECT id, name FROM categories ORDER BY sort_order ASC")->fetchAll();
$products = $connexion->query("
    SELECT p.*, c.name AS cat_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    ORDER BY p.is_featured DESC, p.featured_order ASC, p.id DESC
")->fetchAll();
?>

<div class="ph">
  <div class="ph-left">
    <h1>Produits SaaS</h1>
    <p>Gérez les services EDR, XDR, SOC et autres solutions</p>
  </div>
</div>

<!-- Formulaire ajout -->
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
          <label class="form-label">Top produit ⭐</label>
          <select class="form-select" name="is_featured">
            <option value="0">Non</option>
            <option value="1">Oui</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Ordre (top)</label>
          <input class="form-control" name="featured_order" type="number" value="999">
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <button class="btn-cyna" style="width:100%">+ Ajouter le produit</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Liste -->
<div class="card">
  <div class="card-head">
    Catalogue de services
    <span class="badge badge-blue"><?= count($products) ?> produit(s)</span>
  </div>
  <table class="ctable">
    <thead>
      <tr>
        <th>ID</th>
        <th>Service</th>
        <th>Catégorie</th>
        <th>€/mois</th>
        <th>€/an</th>
        <th>Dispo</th>
        <th>Top</th>
        <th class="text-right">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$products): ?>
        <tr><td colspan="8"><div class="empty-state"><div class="icon">⬡</div><p>Aucun produit ajouté</p></div></td></tr>
      <?php else: foreach ($products as $p): ?>
      <tr>
        <td class="mono">#<?= (int)$p['id'] ?></td>
        <td style="font-weight:600;color:#fff"><?= htmlspecialchars($p['name']) ?></td>
        <td><?php if ($p['cat_name']): ?><span class="badge badge-blue"><?= htmlspecialchars($p['cat_name']) ?></span><?php else: ?><span class="muted">—</span><?php endif; ?></td>
        <td class="muted"><?= number_format((float)$p['price_monthly'],2,',',' ') ?> €</td>
        <td class="muted"><?= number_format((float)$p['price_yearly'],2,',',' ') ?> €</td>
        <td>
          <?= (int)$p['is_available']
            ? '<span class="badge badge-green">Disponible</span>'
            : '<span class="badge badge-red">Indisponible</span>' ?>
        </td>
        <td>
          <?= (int)$p['is_featured']
            ? '<span class="badge badge-yellow">⭐ #'.(int)$p['featured_order'].'</span>'
            : '<span class="muted">—</span>' ?>
        </td>
        <td class="text-right" style="display:flex;gap:6px;justify-content:flex-end">
          <a class="btn-view" href="product_edit.php?id=<?= (int)$p['id'] ?>">Modifier</a>
          <a class="btn-del" href="product_delete.php?id=<?= (int)$p['id'] ?>"
             onclick="return confirm('Supprimer « <?= addslashes(htmlspecialchars($p['name'])) ?> » ?')">
            Supprimer
          </a>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>