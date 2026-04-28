<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/config.php';

// Créer la table si besoin
$connexion->exec("
    CREATE TABLE IF NOT EXISTS chat_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT DEFAULT NULL,
        session_id VARCHAR(100),
        user_message TEXT NOT NULL,
        bot_response TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id),
        INDEX(created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// Filtres
$filter_q    = trim($_GET['q']    ?? '');
$filter_date = trim($_GET['date'] ?? '');

$where  = [];
$params = [];

if ($filter_q !== '') {
    $where[]  = "(cl.user_message LIKE ? OR cl.bot_response LIKE ? OR u.email LIKE ?)";
    $like = "%$filter_q%";
    $params = array_merge($params, [$like, $like, $like]);
}
if ($filter_date !== '') {
    $where[]  = "DATE(cl.created_at) = ?";
    $params[] = $filter_date;
}

$sql = "
    SELECT cl.*, u.email, u.prenom, u.nom
    FROM chat_logs cl
    LEFT JOIN utilisateurs u ON u.id = cl.user_id
    " . (!empty($where) ? "WHERE " . implode(' AND ', $where) : "") . "
    ORDER BY cl.created_at DESC
    LIMIT 200
";

$stmt = $connexion->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Stats
$total_conversations = (int)$connexion->query("SELECT COUNT(*) FROM chat_logs")->fetchColumn();
$today_conversations = (int)$connexion->query("SELECT COUNT(*) FROM chat_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$unique_sessions     = (int)$connexion->query("SELECT COUNT(DISTINCT session_id) FROM chat_logs")->fetchColumn();
?>

<div class="ph">
  <div class="ph-left">
    <h1>Conversations Chatbot</h1>
    <p>Historique des échanges avec l'assistant virtuel CYNA</p>
  </div>
</div>

<!-- KPI -->
<div class="row g-3 mb-4">
  <div class="col-4">
    <div class="stat-card">
      <div class="stat-icon">💬</div>
      <div class="stat-info"><div class="stat-val"><?= $total_conversations ?></div><div class="stat-lbl">Messages total</div></div>
    </div>
  </div>
  <div class="col-4">
    <div class="stat-card">
      <div class="stat-icon">📅</div>
      <div class="stat-info"><div class="stat-val"><?= $today_conversations ?></div><div class="stat-lbl">Aujourd'hui</div></div>
    </div>
  </div>
  <div class="col-4">
    <div class="stat-card">
      <div class="stat-icon">👤</div>
      <div class="stat-info"><div class="stat-val"><?= $unique_sessions ?></div><div class="stat-lbl">Sessions uniques</div></div>
    </div>
  </div>
</div>

<!-- FILTRES -->
<form method="GET" action="chat_logs.php" style="margin-bottom:16px">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input type="search" name="q" value="<?= htmlspecialchars($filter_q) ?>"
      placeholder="Rechercher dans les messages..."
      style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 13px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;width:280px"
      onfocus="this.style.borderColor='var(--c-accent)'" onblur="this.style.borderColor='rgba(255,255,255,.1)'">
    <input type="date" name="date" value="<?= htmlspecialchars($filter_date) ?>"
      style="background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:9px;padding:8px 12px;font-size:.83rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;colorscheme:dark">
    <button type="submit" class="btn-cyna" style="padding:8px 18px;font-size:.83rem">Filtrer</button>
    <?php if ($filter_q || $filter_date): ?>
      <a href="chat_logs.php" class="btn-ghost" style="padding:7px 14px;font-size:.8rem">✕ Reset</a>
    <?php endif; ?>
    <span style="margin-left:auto;font-size:.78rem;color:var(--c-muted)"><?= count($logs) ?> résultat(s)</span>
  </div>
</form>

<!-- TABLE -->
<?php if (!$logs): ?>
<div class="card">
  <div class="empty-state" style="padding:48px">
    <div class="icon" style="font-size:2rem;margin-bottom:12px;opacity:.3">💬</div>
    <p>Aucune conversation pour le moment.</p>
  </div>
</div>

<?php else: ?>

<!-- Vue conversations groupées par session -->
<?php
$sessions = [];
foreach ($logs as $log) {
    $key = $log['session_id'];
    if (!isset($sessions[$key])) {
        $sessions[$key] = [
            'session_id' => $log['session_id'],
            'email'      => $log['email'],
            'prenom'     => $log['prenom'],
            'nom'        => $log['nom'],
            'first_at'   => $log['created_at'],
            'last_at'    => $log['created_at'],
            'messages'   => [],
            'count'      => 0,
        ];
    }
    $sessions[$key]['messages'][] = $log;
    $sessions[$key]['count']++;
    if ($log['created_at'] < $sessions[$key]['first_at']) $sessions[$key]['first_at'] = $log['created_at'];
    if ($log['created_at'] > $sessions[$key]['last_at'])  $sessions[$key]['last_at']  = $log['created_at'];
}
?>

<div style="display:flex;flex-direction:column;gap:12px">
<?php foreach ($sessions as $sid => $session): ?>
<div class="card" style="overflow:hidden">
  <!-- Session header -->
  <div style="padding:14px 20px;border-bottom:1px solid var(--c-border);display:flex;align-items:center;justify-content:space-between;gap:12px;cursor:pointer"
       onclick="toggleSession('<?= htmlspecialchars($sid) ?>')">
    <div style="display:flex;align-items:center;gap:12px">
      <div style="width:36px;height:36px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:.85rem;font-weight:700;color:#fff;flex-shrink:0">
        <?= $session['email'] ? strtoupper(substr($session['email'],0,1)) : '?' ?>
      </div>
      <div>
        <div style="font-size:.85rem;font-weight:600;color:#fff">
          <?= $session['email'] ? htmlspecialchars($session['email']) : 'Visiteur anonyme' ?>
        </div>
        <div style="font-size:.72rem;color:var(--c-muted)">
          <?= date('d/m/Y à H:i', strtotime($session['first_at'])) ?>
          · <?= $session['count'] ?> message(s)
        </div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px">
      <span style="font-size:.72rem;padding:3px 9px;border-radius:20px;background:rgba(38,208,206,.1);color:#26d0ce;border:1px solid rgba(38,208,206,.2)"><?= $session['count'] ?> msg</span>
      <span style="color:var(--c-muted);font-size:.8rem" id="chev-<?= htmlspecialchars($sid) ?>">▶</span>
    </div>
  </div>

  <!-- Messages de la session -->
  <div id="session-<?= htmlspecialchars($sid) ?>" style="display:none;padding:16px 20px;background:rgba(0,0,0,.15)">
    <div style="display:flex;flex-direction:column;gap:12px;max-height:400px;overflow-y:auto">
      <?php foreach (array_reverse($session['messages']) as $msg): ?>
      <div style="display:flex;flex-direction:column;gap:6px">
        <!-- Message utilisateur -->
        <div style="display:flex;justify-content:flex-end">
          <div style="background:var(--grad);color:#fff;border-radius:12px 12px 4px 12px;padding:8px 14px;font-size:.82rem;max-width:75%">
            <?= htmlspecialchars($msg['user_message']) ?>
          </div>
        </div>
        <!-- Réponse bot -->
        <div style="display:flex;justify-content:flex-start">
          <div style="background:#1a2035;border:1px solid rgba(255,255,255,.08);color:#e8eaf2;border-radius:12px 12px 12px 4px;padding:8px 14px;font-size:.82rem;max-width:75%">
            <?= htmlspecialchars($msg['bot_response']) ?>
          </div>
        </div>
        <div style="font-size:.65rem;color:var(--c-muted);text-align:center">
          <?= date('d/m/Y H:i', strtotime($msg['created_at'])) ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function toggleSession(sid) {
  var el   = document.getElementById('session-' + sid);
  var chev = document.getElementById('chev-' + sid);
  var open = el.style.display !== 'none';
  el.style.display = open ? 'none' : 'block';
  chev.textContent = open ? '▶' : '▼';
}
</script>

<?php require_once __DIR__ . '/footer.php'; ?>