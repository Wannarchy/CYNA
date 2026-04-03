<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/config.php';

$orders = $connexion->query("
    SELECT o.*, u.email
    FROM orders o LEFT JOIN utilisateurs u ON u.id = o.user_id
    ORDER BY o.id DESC
")->fetchAll();
?>

<div class="ph">
  <div class="ph-left">
    <h1>Commandes</h1>
    <p><?= count($orders) ?> commande(s) au total</p>
  </div>
</div>

<div class="card">
  <table class="ctable">
    <thead>
      <tr>
        <th>N° commande</th>
        <th>Client</th>
        <th>Facturation</th>
        <th>Montant</th>
        <th>Date</th>
        <th class="text-right">Action</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$orders): ?>
        <tr><td colspan="6"><div class="empty-state"><div class="icon">◎</div><p>Aucune commande enregistrée</p></div></td></tr>
      <?php else: foreach ($orders as $o): ?>
      <tr>
        <td><span class="badge badge-blue">#<?= (int)$o['id'] ?></span></td>
        <td class="muted"><?= htmlspecialchars($o['email'] ?? '—') ?></td>
        <td class="muted"><?= htmlspecialchars($o['billing_name'] ?? '—') ?></td>
        <td style="font-weight:600;color:#fff"><?= number_format((float)$o['total'],2,',',' ') ?> €</td>
        <td class="mono"><?= date('d/m/Y H:i',strtotime($o['created_at'])) ?></td>
        <td class="text-right">
          <a href="order_view.php?id=<?= (int)$o['id'] ?>" class="btn-view">Voir détail →</a>
        </td>
      </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>