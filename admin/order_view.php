<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/config.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $connexion->prepare("
    SELECT o.*, u.email
    FROM orders o LEFT JOIN utilisateurs u ON u.id = o.user_id
    WHERE o.id = ? LIMIT 1
");
$stmt->execute([$id]);
$order = $stmt->fetch();
if (!$order) die('<div class="content"><p style="color:#f87171">Commande introuvable.</p></div>');

$stmt = $connexion->prepare("
    SELECT oi.*, p.name
    FROM order_items oi LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
");
$stmt->execute([$id]);
$items = $stmt->fetchAll();
?>

<div class="ph">
  <div class="ph-left">
    <h1>Commande <span class="badge badge-blue" style="font-size:.9rem;vertical-align:middle">#<?= (int)$order['id'] ?></span></h1>
    <p>Détail complet de la commande</p>
  </div>
  <a href="orders.php" class="btn-ghost">← Retour aux commandes</a>
</div>

<div class="row g-3">
  <!-- Infos commande -->
  <div class="col-12 col-lg-4">
    <div class="card">
      <div class="card-head">Informations client</div>
      <div class="card-body">
        <div class="mb-3">
          <div class="form-label" style="margin-bottom:4px">Compte client</div>
          <div style="font-weight:500;color:#fff"><?= htmlspecialchars($order['email'] ?? '—') ?></div>
        </div>
        <div class="divider"></div>
        <div class="mb-3">
          <div class="form-label" style="margin-bottom:4px">Nom facturation</div>
          <div style="font-weight:500;color:#fff"><?= htmlspecialchars($order['billing_name'] ?? '—') ?></div>
        </div>
        <div class="mb-3">
          <div class="form-label" style="margin-bottom:4px">Adresse</div>
          <div style="color:var(--c-muted2);font-size:.82rem;line-height:1.5">
            <?= nl2br(htmlspecialchars($order['billing_address'] ?? '—')) ?>
          </div>
        </div>
        <div class="divider"></div>
        <div class="mb-3">
          <div class="form-label" style="margin-bottom:4px">Date de commande</div>
          <div class="mono" style="color:var(--c-muted2)"><?= date('d/m/Y à H:i', strtotime($order['created_at'])) ?></div>
        </div>
        <div>
          <div class="form-label" style="margin-bottom:4px">Montant total</div>
          <div style="font-size:1.5rem;font-weight:700;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent">
            <?= number_format((float)$order['total'],2,',',' ') ?> €
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Services commandés -->
  <div class="col-12 col-lg-8">
    <div class="card">
      <div class="card-head">
        Services commandés
        <span class="badge badge-blue"><?= count($items) ?> service(s)</span>
      </div>
      <table class="ctable">
        <thead>
          <tr><th>Service SaaS</th><th>Cycle de facturation</th><th>Prix</th></tr>
        </thead>
        <tbody>
          <?php if (!$items): ?>
            <tr><td colspan="3"><div class="empty-state"><div class="icon">◎</div><p>Aucun service trouvé</p></div></td></tr>
          <?php else: foreach ($items as $it): ?>
          <tr>
            <td style="font-weight:600;color:#fff"><?= htmlspecialchars($it['name'] ?? '—') ?></td>
            <td>
              <?php if ($it['cycle'] === 'yearly'): ?>
                <span class="badge badge-green">Annuel</span>
              <?php else: ?>
                <span class="badge badge-blue">Mensuel</span>
              <?php endif; ?>
            </td>
            <td style="font-weight:600;color:#fff"><?= number_format((float)$it['price'],2,',',' ') ?> €</td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>