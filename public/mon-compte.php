<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['utilisateur_id'])) { header('Location: connexion.php'); exit; }

$user_id = (int)$_SESSION['utilisateur_id'];
$stmt = $connexion->prepare("SELECT * FROM utilisateurs WHERE id = ? LIMIT 1");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) { session_destroy(); header('Location: connexion.php'); exit; }

$nb_panier = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));
$tab       = $_GET['tab'] ?? 'profil';
$success   = '';
$errors    = [];

// Modifier infos perso
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $tab    = 'profil';
    $prenom = trim($_POST['prenom'] ?? '');
    $nom    = trim($_POST['nom']    ?? '');
    $email  = trim($_POST['email']  ?? '');
    if (empty($prenom)) $errors[] = "Le prénom est requis.";
    if (empty($nom))    $errors[] = "Le nom est requis.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide.";
    if (empty($errors) && $email !== $user['email']) {
        $check = $connexion->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        $check->execute([$email, $user_id]);
        if ($check->fetch()) $errors[] = "Cet email est déjà utilisé.";
    }
    if (empty($errors)) {
        $connexion->prepare("UPDATE utilisateurs SET prenom=?, nom=?, email=? WHERE id=?")->execute([$prenom, $nom, $email, $user_id]);
        $_SESSION['utilisateur_prenom'] = $prenom;
        $user['prenom'] = $prenom; $user['nom'] = $nom; $user['email'] = $email;
        $success = "Informations mises à jour !";
    }
}

// Changer mot de passe
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $tab       = 'securite';
    $ancien    = $_POST['ancien_mdp']    ?? '';
    $nouveau   = $_POST['nouveau_mdp']   ?? '';
    $confirmer = $_POST['confirmer_mdp'] ?? '';
    if (!password_verify($ancien, $user['mot_de_passe'])) $errors[] = "Mot de passe actuel incorrect.";
    if (strlen($nouveau) < 8)              $errors[] = "8 caractères minimum.";
    if (!preg_match('/[A-Z]/', $nouveau))  $errors[] = "Au moins une majuscule.";
    if (!preg_match('/[0-9]/', $nouveau))  $errors[] = "Au moins un chiffre.";
    if ($nouveau !== $confirmer)            $errors[] = "Les mots de passe ne correspondent pas.";
    if (empty($errors)) {
        $connexion->prepare("UPDATE utilisateurs SET mot_de_passe=? WHERE id=?")->execute([password_hash($nouveau, PASSWORD_DEFAULT), $user_id]);
        $success = "Mot de passe modifié avec succès !";
    }
}

// Données
$orders_stmt = $connexion->prepare("SELECT o.id, o.total, o.created_at, o.billing_name FROM orders o WHERE o.user_id = ? ORDER BY o.created_at DESC");
$orders_stmt->execute([$user_id]);
$orders      = $orders_stmt->fetchAll();
$nb_orders   = count($orders);
$total_spent = array_sum(array_column($orders, 'total'));
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA — Mon compte</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400&display=swap" rel="stylesheet">
  <style>
    :root{--blue:#1a2980;--cyan:#26d0ce;--grad:linear-gradient(135deg,#1a2980,#26d0ce);}
    *{box-sizing:border-box;}
    body{background:#0b1020;color:#e8eaf2;font-family:'DM Sans',sans-serif;margin:0;}
    .navbar{background:rgba(11,16,32,.95)!important;border-bottom:1px solid rgba(255,255,255,.07);backdrop-filter:blur(12px);}
    .navbar-brand{font-weight:700;font-size:1.2rem;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
    .wrap{max-width:1100px;margin:0 auto;padding:28px 16px;display:flex;gap:24px;align-items:flex-start;}
    .sb{width:240px;flex-shrink:0;position:sticky;top:72px;}
    .u-card{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:16px;padding:20px;margin-bottom:12px;text-align:center;}
    .u-av{width:54px;height:54px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:1.25rem;font-weight:700;color:#fff;margin:0 auto 10px;}
    .u-name{font-size:.92rem;font-weight:600;color:#fff;}
    .u-email{font-size:.73rem;color:#5c6378;margin-top:2px;}
    .u-badge{display:inline-flex;gap:4px;margin-top:8px;font-size:.67rem;font-weight:600;padding:3px 9px;border-radius:20px;background:rgba(38,208,206,.12);color:#26d0ce;border:1px solid rgba(38,208,206,.2);}
    .sb-nav{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:16px;overflow:hidden;}
    .sb-nav a{display:flex;align-items:center;gap:10px;padding:11px 16px;color:#8b92a8;text-decoration:none;font-size:.84rem;border-bottom:1px solid rgba(255,255,255,.05);transition:all .15s;}
    .sb-nav a:last-child{border-bottom:none;}
    .sb-nav a:hover{color:#e8eaf2;background:rgba(255,255,255,.03);}
    .sb-nav a.active{color:#fff;background:rgba(38,208,206,.08);border-left:3px solid var(--cyan);}
    .main{flex:1;min-width:0;}
    .ccard{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:16px;overflow:hidden;margin-bottom:20px;}
    .ccard-head{padding:15px 20px;border-bottom:1px solid rgba(255,255,255,.07);font-weight:600;font-size:.82rem;color:#8b92a8;text-transform:uppercase;letter-spacing:.5px;display:flex;align-items:center;justify-content:space-between;}
    .ccard-body{padding:22px;}
    .stats{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-bottom:20px;}
    .stt{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:16px;text-align:center;}
    .stt-v{font-size:1.35rem;font-weight:700;color:#fff;}
    .stt-l{font-size:.7rem;color:#5c6378;margin-top:2px;}
    .form-label{font-size:.72rem;font-weight:600;color:#8b92a8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;display:block;}
    .form-control,.form-select{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:9px 13px;font-size:.88rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;width:100%;transition:border-color .15s,box-shadow .15s;}
    .form-control::placeholder{color:#3a3f52;}
    .form-control:focus,.form-select:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(38,208,206,.1);background:rgba(38,208,206,.04);}
    .form-select option{background:#0f1628;}
    .btn-save{background:var(--grad);color:#fff;border:none;border-radius:10px;padding:10px 22px;font-size:.88rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;transition:opacity .15s;}
    .btn-save:hover{opacity:.85;}
    .a-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#4ade80;margin-bottom:18px;}
    .a-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#f87171;margin-bottom:18px;}
    .otable{width:100%;border-collapse:collapse;font-size:.84rem;}
    .otable thead tr{border-bottom:1px solid rgba(255,255,255,.1);}
    .otable thead th{padding:9px 14px;font-size:.67rem;font-weight:600;text-transform:uppercase;letter-spacing:.8px;color:#5c6378;text-align:left;}
    .otable tbody tr{border-bottom:1px solid rgba(255,255,255,.05);transition:background .1s;}
    .otable tbody tr:last-child{border-bottom:none;}
    .otable tbody tr:hover{background:rgba(255,255,255,.02);}
    .otable td{padding:12px 14px;vertical-align:middle;}
    .pwd-bar-wrap{height:3px;border-radius:2px;background:rgba(255,255,255,.1);margin-top:6px;overflow:hidden;}
    .pwd-bar{height:100%;border-radius:2px;transition:width .3s,background .3s;width:0;}
    .divider{height:1px;background:rgba(255,255,255,.07);margin:20px 0;}
    @media(max-width:768px){.wrap{flex-direction:column;}.sb{width:100%;position:static;}.stats{grid-template-columns:repeat(3,1fr);}}
    footer{border-top:1px solid rgba(255,255,255,.07);margin-top:20px;padding:20px;text-align:center;color:#5c6378;font-size:.78rem;}
    footer a{color:#5c6378;text-decoration:none;margin:0 12px;}
    footer a:hover{color:#8b92a8;}
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container-fluid px-3 px-lg-4">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <div class="d-flex align-items-center gap-2 ms-auto">
      <a href="catalogue.php" class="btn btn-outline-secondary btn-sm" style="font-size:.8rem">Catalogue</a>
      <a href="panier.php" class="btn btn-outline-light btn-sm" style="font-size:.8rem">🛒 <?= $nb_panier>0?"($nb_panier)":'' ?></a>
      <a href="deconnexion.php" class="btn btn-outline-danger btn-sm" style="font-size:.8rem">Déconnexion</a>
    </div>
  </div>
</nav>

<div class="wrap">

  <!-- Sidebar -->
  <aside class="sb">
    <div class="u-card">
      <div class="u-av"><?= strtoupper(mb_substr($user['prenom'],0,1)) ?></div>
      <div class="u-name"><?= htmlspecialchars($user['prenom'].' '.$user['nom']) ?></div>
      <div class="u-email"><?= htmlspecialchars($user['email']) ?></div>
      <div class="u-badge">✓ Compte vérifié</div>
    </div>
    <nav class="sb-nav">
      <a href="?tab=profil"    class="<?= $tab==='profil'   ?'active':'' ?>">◈ Mon profil</a>
      <a href="?tab=securite"  class="<?= $tab==='securite' ?'active':'' ?>">🔐 Sécurité</a>
      <a href="adresses.php">📍 Mes adresses</a>
      <a href="paiements.php">💳 Paiements</a>
      <a href="mes-abonnements.php">🔄 Abonnements</a>
      <a href="mes-commandes.php">
        ◎ Mes commandes
        <?php if ($nb_orders>0): ?><span style="margin-left:auto;font-size:.65rem;font-weight:600;background:rgba(38,208,206,.15);color:var(--cyan);padding:1px 7px;border-radius:20px"><?= $nb_orders ?></span><?php endif; ?>
      </a>
      <a href="deconnexion.php" style="color:rgba(239,68,68,.6)">⏻ Déconnexion</a>
    </nav>
  </aside>

  <!-- Contenu -->
  <main class="main">

    <?php if ($success): ?><div class="a-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($errors):  ?><div class="a-error"><?php foreach($errors as $e): ?>⚠ <?= htmlspecialchars($e) ?><br><?php endforeach; ?></div><?php endif; ?>

    <!-- Stats -->
    <div class="stats">
      <div class="stt"><div class="stt-v"><?= $nb_orders ?></div><div class="stt-l">Commandes</div></div>
      <div class="stt"><div class="stt-v"><?= number_format($total_spent,0,',',' ') ?> €</div><div class="stt-l">Total dépensé</div></div>
      <div class="stt"><div class="stt-v"><?= (int)($user['est_confirme']??0) ? '✓' : '✗' ?></div><div class="stt-l">Email vérifié</div></div>
    </div>

    <?php if ($tab==='profil'): ?>
    <!-- PROFIL -->
    <div class="ccard">
      <div class="ccard-head">Informations personnelles</div>
      <div class="ccard-body">
        <form method="POST">
          <input type="hidden" name="action" value="update_profile">
          <div class="row g-3 mb-3">
            <div class="col-md-6"><label class="form-label">Prénom</label><input class="form-control" name="prenom" value="<?= htmlspecialchars($user['prenom']) ?>" required></div>
            <div class="col-md-6"><label class="form-label">Nom</label><input class="form-control" name="nom" value="<?= htmlspecialchars($user['nom']) ?>" required></div>
          </div>
          <div class="mb-4">
            <label class="form-label">Adresse email</label>
            <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            <div style="font-size:.7rem;color:#5c6378;margin-top:4px">ℹ Modifier l'email nécessite une re-confirmation.</div>
          </div>
          <button class="btn-save" type="submit">Enregistrer</button>
        </form>
      </div>
    </div>

    <?php elseif ($tab==='securite'): ?>
    <!-- SÉCURITÉ -->
    <div class="ccard">
      <div class="ccard-head">Changer le mot de passe</div>
      <div class="ccard-body">
        <form method="POST">
          <input type="hidden" name="action" value="change_password">
          <div class="mb-3"><label class="form-label">Mot de passe actuel</label><input class="form-control" type="password" name="ancien_mdp" required placeholder="••••••••"></div>
          <div class="mb-3">
            <label class="form-label">Nouveau mot de passe</label>
            <input class="form-control" type="password" name="nouveau_mdp" id="new-pwd" required placeholder="8 car. min, 1 majuscule, 1 chiffre" oninput="checkPwd(this.value)">
            <div class="pwd-bar-wrap"><div class="pwd-bar" id="pwd-bar"></div></div>
            <div id="pwd-hint" style="font-size:.7rem;color:#5c6378;margin-top:4px"></div>
          </div>
          <div class="mb-4"><label class="form-label">Confirmer</label><input class="form-control" type="password" name="confirmer_mdp" required placeholder="••••••••"></div>
          <button class="btn-save" type="submit">Modifier le mot de passe</button>
        </form>
        <div class="divider"></div>
        <div style="background:rgba(239,68,68,.06);border:1px solid rgba(239,68,68,.12);border-radius:10px;padding:16px">
          <div style="font-size:.82rem;font-weight:600;color:#f87171;margin-bottom:6px">Zone sensible</div>
          <p style="font-size:.8rem;color:#8b92a8;margin:0 0 12px">Pour supprimer votre compte, contactez notre support.</p>
          <a href="contact.php" style="background:transparent;color:#8b92a8;border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:7px 14px;font-size:.8rem;text-decoration:none">Contacter le support →</a>
        </div>
      </div>
    </div>

    <?php elseif ($tab==='commandes'): ?>
    <!-- COMMANDES -->
    <div class="ccard">
      <div class="ccard-head">Historique des commandes <span style="font-size:.75rem;font-weight:500;color:#8b92a8"><?= $nb_orders ?> commande(s)</span></div>
      <?php if (!$orders): ?>
        <div style="text-align:center;padding:48px 24px;color:#5c6378">
          <div style="font-size:2.5rem;margin-bottom:12px;opacity:.3">◎</div>
          <p style="font-size:.88rem">Aucune commande pour l'instant.</p>
          <a href="catalogue.php" style="color:var(--cyan);font-size:.85rem;text-decoration:none">Découvrir nos services →</a>
        </div>
      <?php else: ?>
        <table class="otable">
          <thead><tr><th>N°</th><th>Services</th><th>Montant</th><th>Date</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($orders as $o):
              $its = $connexion->prepare("SELECT p.name, oi.cycle FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id WHERE oi.order_id=?");
              $its->execute([$o['id']]); $items = $its->fetchAll();
            ?>
            <tr>
              <td><span style="font-size:.7rem;font-weight:600;padding:2px 8px;border-radius:20px;background:rgba(79,140,255,.12);color:#93c5fd;border:1px solid rgba(79,140,255,.2)">#<?= (int)$o['id'] ?></span></td>
              <td>
                <div style="font-size:.84rem;color:#e8eaf2;font-weight:500"><?= htmlspecialchars($o['billing_name']??'—') ?></div>
                <?php if ($items): ?><div style="font-size:.72rem;color:#5c6378;margin-top:2px"><?= implode(', ', array_map(fn($i)=>htmlspecialchars($i['name']??''), array_slice($items,0,2))) ?><?= count($items)>2?' +'. (count($items)-2):'' ?></div><?php endif; ?>
              </td>
              <td style="font-weight:600;color:#fff"><?= number_format((float)$o['total'],2,',',' ') ?> €</td>
              <td style="font-size:.78rem;color:#5c6378;font-family:'DM Mono',monospace"><?= date('d/m/Y',strtotime($o['created_at'])) ?></td>
              <td style="text-align:right"><a href="confirmation.php?order_id=<?= (int)$o['id'] ?>" style="font-size:.73rem;font-weight:600;padding:4px 10px;border-radius:7px;background:rgba(79,140,255,.1);color:#93c5fd;border:1px solid rgba(79,140,255,.2);text-decoration:none">Détail →</a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </main>
</div>

<footer>
  <a href="mentions-legales.php">Mentions légales</a>
  <a href="cgu.php">CGU</a>
  <a href="contact.php">Contact</a>
  <span>© 2025 CYNA-IT</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function checkPwd(v){
  var bar=document.getElementById('pwd-bar'),hint=document.getElementById('pwd-hint'),s=0,tips=[];
  if(v.length>=8)s++;else tips.push('8 car. min');
  if(/[A-Z]/.test(v))s++;else tips.push('1 majuscule');
  if(/[0-9]/.test(v))s++;else tips.push('1 chiffre');
  if(/[^A-Za-z0-9]/.test(v))s++;
  var c=['#ef4444','#f59e0b','#22c55e','#26d0ce'],l=['Très faible','Faible','Bon','Excellent'];
  bar.style.width=(s*25)+'%';bar.style.background=c[s-1]||'#ef4444';
  hint.textContent=s>0?l[s-1]+(tips.length?' — manque : '+tips.join(', '):''):'';
}
</script>
</body>
</html>