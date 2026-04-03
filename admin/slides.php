<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/config.php';
$slides = $connexion->query("SELECT * FROM homepage_slides ORDER BY sort_order ASC, id ASC")->fetchAll();
?>

<div class="ph">
  <div class="ph-left">
    <h1>Slides du Carousel</h1>
    <p>Gérez les slides affichées sur la page d'accueil</p>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-5">
    <div class="card">
      <div class="card-head">Nouvelle slide</div>
      <div class="card-body">
        <form method="POST" action="slide_save.php">
          <div class="mb-3">
            <label class="form-label">Titre *</label>
            <input class="form-control" name="title" required placeholder="Ex : Sécurisez votre infrastructure">
          </div>
          <div class="mb-3">
            <label class="form-label">Sous-titre</label>
            <input class="form-control" name="subtitle" placeholder="SOC, EDR, XDR — déploiement en 24h">
          </div>
          <div class="mb-3">
            <label class="form-label">Lien (URL)</label>
            <input class="form-control" name="link_url" placeholder="public/catalogue.php">
          </div>
          <div class="mb-3">
            <label class="form-label">Image (chemin)</label>
            <input class="form-control" name="image_path" placeholder="images/slide1.jpg">
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label">Ordre</label>
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
          <button class="btn-cyna" style="width:100%">+ Ajouter la slide</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-12 col-lg-7">
    <div class="card">
      <div class="card-head">
        Slides configurées
        <span class="badge badge-blue"><?= count($slides) ?></span>
      </div>
      <table class="ctable">
        <thead>
          <tr><th>ID</th><th>Titre</th><th>Sous-titre</th><th>Ordre</th><th>Statut</th><th class="text-right">Action</th></tr>
        </thead>
        <tbody>
          <?php if (!$slides): ?>
            <tr><td colspan="6"><div class="empty-state"><div class="icon">▣</div><p>Aucune slide configurée</p></div></td></tr>
          <?php else: foreach ($slides as $s): ?>
          <tr>
            <td class="mono">#<?= (int)$s['id'] ?></td>
            <td style="font-weight:500;color:#fff"><?= htmlspecialchars($s['title']) ?></td>
            <td class="muted" style="max-width:180px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($s['subtitle'] ?? '—') ?></td>
            <td><span class="badge badge-gray"><?= (int)$s['sort_order'] ?></span></td>
            <td>
              <?= (int)$s['is_active']
                ? '<span class="badge badge-green">Actif</span>'
                : '<span class="badge badge-red">Inactif</span>' ?>
            </td>
            <td class="text-right">
              <a class="btn-del" href="slide_delete.php?id=<?= (int)$s['id'] ?>"
                 onclick="return confirm('Supprimer cette slide ?')">Supprimer</a>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>