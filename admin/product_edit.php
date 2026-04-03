<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/config.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header('Location: products.php'); exit; }

$product = $connexion->prepare("SELECT * FROM products WHERE id = ?");
$product->execute([$id]);
$p = $product->fetch();
if (!$p) { header('Location: products.php'); exit; }

$cats = $connexion->query("SELECT id, name FROM categories ORDER BY sort_order ASC")->fetchAll();

$success = false;
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name           = trim($_POST['name'] ?? '');
    $category_id    = $_POST['category_id'] !== '' ? (int)$_POST['category_id'] : null;
    $image_path     = trim($_POST['image_path'] ?? '');
    $price_monthly  = (float)($_POST['price_monthly'] ?? 0);
    $price_yearly   = (float)($_POST['price_yearly']  ?? 0);
    $is_available   = (int)($_POST['is_available']     ?? 1);
    $is_featured    = (int)($_POST['is_featured']      ?? 0);
    $featured_order = (int)($_POST['featured_order']   ?? 999);
    $description    = trim($_POST['description'] ?? '');

    if ($name === '') {
        $error = "Le nom du service est obligatoire.";
    } else {
        $stmt = $connexion->prepare("
            UPDATE products SET
                name=?, category_id=?, image_path=?, price_monthly=?, price_yearly=?,
                is_available=?, is_featured=?, featured_order=?, description=?
            WHERE id=?
        ");
        $stmt->execute([
            $name,
            $category_id,
            $image_path !== '' ? $image_path : $p['image_path'],
            $price_monthly,
            $price_yearly,
            $is_available,
            $is_featured,
            $featured_order,
            $description,
            $id
        ]);
        // Recharger le produit mis à jour
        $product->execute([$id]);
        $p = $product->fetch();
        $success = true;
    }
}
?>

<div class="ph">
  <div class="ph-left">
    <h1>Modifier le produit</h1>
    <p>Mise à jour de « <?= htmlspecialchars($p['name']) ?> »</p>
  </div>
  <a href="products.php" class="btn-ghost">← Retour aux produits</a>
</div>

<?php if ($success): ?>
  <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:12px 18px;font-size:.84rem;color:#4ade80;margin-bottom:20px;display:flex;align-items:center;gap:8px">
    ✅ Produit mis à jour avec succès.
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 18px;font-size:.84rem;color:#f87171;margin-bottom:20px">
    ⚠ <?= htmlspecialchars($error) ?>
  </div>
<?php endif; ?>

<div class="row g-3">

  <!-- Formulaire principal -->
  <div class="col-12 col-lg-8">
    <div class="card">
      <div class="card-head">Informations du service</div>
      <div class="card-body">
        <form method="POST">
          <div class="row g-3 mb-3">
            <div class="col-md-8">
              <label class="form-label">Nom du service *</label>
              <input class="form-control" name="name" required value="<?= htmlspecialchars($p['name']) ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Catégorie</label>
              <select class="form-select" name="category_id">
                <option value="">— Aucune —</option>
                <?php foreach ($cats as $c): ?>
                  <option value="<?= (int)$c['id'] ?>" <?= (int)$p['category_id']===(int)$c['id']?'selected':'' ?>>
                    <?= htmlspecialchars($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea class="form-control" name="description" rows="4"
              placeholder="Décrivez les fonctionnalités principales du service..."><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Prix mensuel (€)</label>
              <input class="form-control" name="price_monthly" type="number" step="0.01"
                     value="<?= number_format((float)$p['price_monthly'],2,'.','') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Prix annuel (€)</label>
              <input class="form-control" name="price_yearly" type="number" step="0.01"
                     value="<?= number_format((float)$p['price_yearly'],2,'.','') ?>">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Image (chemin)</label>
              <input class="form-control" name="image_path"
                     value="<?= htmlspecialchars($p['image_path'] ?? '') ?>"
                     placeholder="images/edr.jpg">
            </div>
            <div class="col-md-3">
              <label class="form-label">Disponibilité</label>
              <select class="form-select" name="is_available">
                <option value="1" <?= (int)$p['is_available']===1?'selected':'' ?>>✅ Disponible</option>
                <option value="0" <?= (int)$p['is_available']===0?'selected':'' ?>>❌ Indisponible</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Top produit</label>
              <select class="form-select" name="is_featured">
                <option value="0" <?= (int)$p['is_featured']===0?'selected':'' ?>>Non</option>
                <option value="1" <?= (int)$p['is_featured']===1?'selected':'' ?>>⭐ Oui</option>
              </select>
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label">Ordre (top produits)</label>
            <input class="form-control" name="featured_order" type="number"
                   value="<?= (int)$p['featured_order'] ?>" style="max-width:120px">
          </div>

          <div class="d-flex gap-2">
            <button class="btn-cyna" type="submit">Enregistrer les modifications</button>
            <a href="products.php" class="btn-ghost">Annuler</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Aperçu + danger zone -->
  <div class="col-12 col-lg-4">
    <div class="card mb-3">
      <div class="card-head">Aperçu</div>
      <div class="card-body">
        <div style="background:rgba(255,255,255,.03);border:1px solid var(--c-border);border-radius:10px;overflow:hidden">
          <?php if (!empty($p['image_path']) && $p['image_path'] !== 'logo.jpg'): ?>
            <img src="../<?= htmlspecialchars($p['image_path']) ?>"
                 style="width:100%;height:120px;object-fit:cover;display:block"
                 alt="<?= htmlspecialchars($p['name']) ?>">
          <?php else: ?>
            <div style="height:120px;background:linear-gradient(135deg,rgba(26,41,128,.4),rgba(38,208,206,.2));display:flex;align-items:center;justify-content:center;font-size:2.5rem">🛡</div>
          <?php endif; ?>
          <div style="padding:14px">
            <div style="font-size:.65rem;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:var(--c-cyan);margin-bottom:4px">
              <?= htmlspecialchars($p['cat_name'] ?? 'Sans catégorie') ?>
            </div>
            <div style="font-weight:600;color:#fff;margin-bottom:6px"><?= htmlspecialchars($p['name']) ?></div>
            <div style="font-size:.8rem;color:var(--c-muted2)">
              <?= number_format((float)$p['price_monthly'],2,',',' ') ?> € / mois
            </div>
            <div style="margin-top:8px">
              <?= (int)$p['is_available']
                ? '<span style="font-size:.68rem;font-weight:600;padding:2px 9px;border-radius:20px;background:rgba(34,197,94,.12);color:#4ade80;border:1px solid rgba(34,197,94,.2)">● Disponible</span>'
                : '<span style="font-size:.68rem;font-weight:600;padding:2px 9px;border-radius:20px;background:rgba(239,68,68,.12);color:#f87171;border:1px solid rgba(239,68,68,.2)">● Indisponible</span>' ?>
            </div>
          </div>
        </div>
        <div style="margin-top:12px;text-align:center">
          <a href="../public/produit.php?id=<?= $id ?>" target="_blank" class="btn-view" style="font-size:.75rem">
            Voir la page publique →
          </a>
        </div>
      </div>
    </div>

    <!-- Danger zone -->
    <div class="card" style="border-color:rgba(239,68,68,.2)">
      <div class="card-head" style="color:#f87171">Zone dangereuse</div>
      <div class="card-body">
        <p style="font-size:.8rem;color:var(--c-muted2);margin-bottom:12px">
          La suppression est irréversible. Les commandes liées seront également supprimées.
        </p>
        <a href="product_delete.php?id=<?= $id ?>" class="btn-del"
           onclick="return confirm('Supprimer définitivement « <?= addslashes(htmlspecialchars($p['name'])) ?> » ?')"
           style="width:100%;text-align:center;display:block;padding:8px">
          Supprimer ce produit
        </a>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/footer.php'; ?>