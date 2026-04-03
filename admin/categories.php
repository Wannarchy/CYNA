<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/config.php';
$cats = $connexion->query("SELECT * FROM categories ORDER BY sort_order ASC, id ASC")->fetchAll();
?>

<div class="ph">
  <div class="ph-left">
    <h1>Catégories</h1>
    <p>Gérez les catégories de services SaaS du catalogue</p>
  </div>
</div>

<div class="row g-3">
  <!-- Formulaire ajout -->
  <div class="col-12 col-lg-4">
    <div class="card">
      <div class="card-head">Nouvelle catégorie</div>
      <div class="card-body">
        <form method="POST" action="category_save.php">
          <input type="hidden" name="id" value="">
          <div class="mb-3">
            <label class="form-label">Nom de la catégorie *</label>
            <input class="form-control" name="name" required placeholder="Ex : SOC & Surveillance">
          </div>
          <div class="mb-3">
            <label class="form-label">Image (chemin relatif)</label>
            <input class="form-control" name="image_path" placeholder="images/soc.jpg">
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Ordre d'affichage</label>
              <input class="form-control" name="sort_order" type="number" value="1" min="1">
            </div>
            <div class="col-6">
              <label class="form-label">Statut</label>
              <select class="form-select" name="is_active">
                <option value="1">✅ Actif</option>
                <option value="0">⏸ Inactif</option>
              </select>
            </div>
          </div>
          <button class="btn-cyna" style="width:100%">+ Ajouter la catégorie</button>
        </form>
      </div>
    </div>
  </div>

  <!-- Liste -->
  <div class="col-12 col-lg-8">
    <div class="card">
      <div class="card-head">
        Liste des catégories
        <span class="badge badge-blue"><?= count($cats) ?></span>
      </div>
      <table class="ctable">
        <thead>
          <tr>
            <th>ID</th>
            <th>Nom</th>
            <th>Image</th>
            <th>Ordre</th>
            <th>Statut</th>
            <th class="text-right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!$cats): ?>
            <tr><td colspan="6"><div class="empty-state"><div class="icon">▦</div><p>Aucune catégorie créée</p></div></td></tr>
          <?php else: foreach ($cats as $c): ?>
          <tr>
            <td class="mono">#<?= (int)$c['id'] ?></td>
            <td style="font-weight:500;color:#fff"><?= htmlspecialchars($c['name']) ?></td>
            <td class="muted"><?= htmlspecialchars($c['image_path'] ?? '—') ?></td>
            <td><span class="badge badge-gray"><?= (int)$c['sort_order'] ?></span></td>
            <td>
              <?= (int)$c['is_active']
                ? '<span class="badge badge-green">Actif</span>'
                : '<span class="badge badge-red">Inactif</span>' ?>
            </td>
            <td class="text-right">
              <a class="btn-del" href="category_delete.php?id=<?= (int)$c['id'] ?>"
                 onclick="return confirm('Supprimer « <?= addslashes(htmlspecialchars($c['name'])) ?> » ?')">
                Supprimer
              </a>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>