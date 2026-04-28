<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['utilisateur_id'])) { header('Location: connexion.php'); exit; }
$user_id = (int)$_SESSION['utilisateur_id'];

// Créer la table si elle n'existe pas
$connexion->exec("
    CREATE TABLE IF NOT EXISTS user_payment_methods (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        card_holder VARCHAR(120) NOT NULL,
        card_last4 CHAR(4) NOT NULL,
        card_brand VARCHAR(20) DEFAULT 'Visa',
        exp_month TINYINT NOT NULL,
        exp_year SMALLINT NOT NULL,
        is_default TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$success = '';
$errors  = [];

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)$_POST['card_id'];
    $connexion->prepare("DELETE FROM user_payment_methods WHERE id=? AND user_id=?")->execute([$id, $user_id]);
    $success = "Carte supprimée.";
}

// SET DEFAULT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_default') {
    $id = (int)$_POST['card_id'];
    $connexion->prepare("UPDATE user_payment_methods SET is_default=0 WHERE user_id=?")->execute([$user_id]);
    $connexion->prepare("UPDATE user_payment_methods SET is_default=1 WHERE id=? AND user_id=?")->execute([$id, $user_id]);
    $success = "Carte par défaut mise à jour.";
}

// ADD CARD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $holder = trim($_POST['card_holder'] ?? '');
    $number = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $month  = (int)($_POST['exp_month'] ?? 0);
    $year   = (int)($_POST['exp_year']  ?? 0);
    $cvv    = trim($_POST['cvv'] ?? '');

    if (empty($holder))                      $errors[] = "Le nom du titulaire est requis.";
    if (strlen($number) !== 16)              $errors[] = "Le numéro de carte doit contenir 16 chiffres.";
    if ($month < 1 || $month > 12)           $errors[] = "Mois d'expiration invalide.";
    if ($year < (int)date('Y'))              $errors[] = "Année d'expiration invalide.";
    if (strlen($cvv) < 3 || strlen($cvv) > 4) $errors[] = "CVV invalide.";

    if (empty($errors)) {
        // Détecter la marque
        $brand = 'Visa';
        if (preg_match('/^5[1-5]/', $number))  $brand = 'Mastercard';
        if (preg_match('/^3[47]/', $number))    $brand = 'Amex';
        if (preg_match('/^6(?:011|5)/', $number)) $brand = 'Discover';

        $last4 = substr($number, -4);

        // Check doublon
        $dup = $connexion->prepare("SELECT id FROM user_payment_methods WHERE user_id=? AND card_last4=? AND exp_month=? AND exp_year=?");
        $dup->execute([$user_id, $last4, $month, $year]);
        if ($dup->fetch()) {
            $errors[] = "Cette carte est déjà enregistrée.";
        } else {
            // Si première carte → default
            $cnt = (int)$connexion->query("SELECT COUNT(*) FROM user_payment_methods WHERE user_id=$user_id")->fetchColumn();
            $is_def = ($cnt === 0) ? 1 : 0;
            $connexion->prepare("INSERT INTO user_payment_methods (user_id,card_holder,card_last4,card_brand,exp_month,exp_year,is_default) VALUES (?,?,?,?,?,?,?)")
                ->execute([$user_id, $holder, $last4, $brand, $month, $year, $is_def]);
            $success = "Carte ajoutée avec succès !";
        }
    }
}

// Load cards
$cards = $connexion->prepare("SELECT * FROM user_payment_methods WHERE user_id=? ORDER BY is_default DESC, id DESC");
$cards->execute([$user_id]);
$cards = $cards->fetchAll();

$nb_panier  = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));
$show_form  = isset($_GET['new']) || !empty($errors);
$cur_year   = (int)date('Y');
$cur_month  = (int)date('m');

// User info
$stmt2 = $connexion->prepare("SELECT prenom,nom,email FROM utilisateurs WHERE id=?");
$stmt2->execute([$user_id]);
$user = $stmt2->fetch();

// Card brand icons
function card_icon($brand) {
    if ($brand === 'Mastercard') return '🟠';
    if ($brand === 'Amex')       return '🔵';
    if ($brand === 'Discover')   return '🟡';
    return '💳';
}
function card_color($brand) {
    if ($brand === 'Mastercard') return 'linear-gradient(135deg,#1a1a2e,#e63946)';
    if ($brand === 'Amex')       return 'linear-gradient(135deg,#003580,#0097b2)';
    if ($brand === 'Discover')   return 'linear-gradient(135deg,#e07b39,#c1440e)';
    return 'linear-gradient(135deg,#1a2980,#26d0ce)';
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA — Méthodes de paiement</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <style>
    :root{--blue:#1a2980;--cyan:#26d0ce;--grad:linear-gradient(135deg,#1a2980,#26d0ce);}
    *{box-sizing:border-box;}
    body{background:#0b1020;color:#e8eaf2;font-family:'DM Sans',sans-serif;margin:0;}
    .navbar{background:rgba(11,16,32,.97)!important;border-bottom:1px solid rgba(255,255,255,.07);backdrop-filter:blur(12px);}
    .navbar-brand{font-weight:800;font-size:1.2rem;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}
    .nav-link{color:rgba(255,255,255,.7)!important;font-size:.88rem;}
    .nav-link:hover{color:#fff!important;}

    .wrap{max-width:1100px;margin:0 auto;padding:32px 16px;display:flex;gap:24px;align-items:flex-start;}
    .sb{width:230px;flex-shrink:0;position:sticky;top:72px;}
    .u-card{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:14px;padding:18px;margin-bottom:10px;text-align:center;}
    .u-av{width:50px;height:50px;border-radius:50%;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:1.15rem;font-weight:700;color:#fff;margin:0 auto 8px;}
    .u-name{font-size:.88rem;font-weight:600;color:#fff;}
    .u-email{font-size:.7rem;color:#5c6378;margin-top:2px;}
    .sb-nav{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:14px;overflow:hidden;}
    .sb-nav a{display:flex;align-items:center;gap:10px;padding:11px 16px;color:#8b92a8;text-decoration:none;font-size:.83rem;border-bottom:1px solid rgba(255,255,255,.05);transition:all .15s;}
    .sb-nav a:last-child{border-bottom:none;}
    .sb-nav a:hover{color:#e8eaf2;background:rgba(255,255,255,.03);}
    .sb-nav a.active{color:#fff;background:rgba(38,208,206,.08);border-left:3px solid var(--cyan);}
    .main{flex:1;min-width:0;}

    .ccard{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:14px;overflow:hidden;margin-bottom:16px;}
    .ccard-head{padding:14px 20px;border-bottom:1px solid rgba(255,255,255,.07);font-weight:600;font-size:.82rem;color:#8b92a8;text-transform:uppercase;letter-spacing:.5px;display:flex;align-items:center;justify-content:space-between;}
    .ccard-body{padding:20px;}

    /* CREDIT CARD VISUAL */
    .cards-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;}
    .cc-visual{border-radius:14px;padding:22px;position:relative;overflow:hidden;min-height:160px;display:flex;flex-direction:column;justify-content:space-between;cursor:default;}
    .cc-visual::before{content:'';position:absolute;top:-30px;right:-30px;width:150px;height:150px;border-radius:50%;background:rgba(255,255,255,.06);}
    .cc-visual::after{content:'';position:absolute;bottom:-50px;right:20px;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.04);}
    .cc-top{display:flex;align-items:center;justify-content:space-between;position:relative;z-index:1;}
    .cc-brand{font-size:.72rem;font-weight:700;color:rgba(255,255,255,.8);text-transform:uppercase;letter-spacing:1px;}
    .cc-chip{width:30px;height:22px;border-radius:4px;background:linear-gradient(135deg,#d4af37,#f5e17a);display:flex;align-items:center;justify-content:center;}
    .cc-number{font-family:'DM Mono',monospace;font-size:1rem;color:#fff;letter-spacing:3px;position:relative;z-index:1;margin:14px 0;}
    .cc-bottom{display:flex;justify-content:space-between;align-items:flex-end;position:relative;z-index:1;}
    .cc-holder{font-size:.75rem;color:rgba(255,255,255,.7);text-transform:uppercase;letter-spacing:.5px;}
    .cc-exp-label{font-size:.58rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.5px;}
    .cc-exp{font-size:.82rem;color:#fff;font-family:'DM Mono',monospace;}
    .default-badge{display:inline-flex;align-items:center;gap:4px;background:rgba(255,255,255,.15);color:#fff;border-radius:20px;padding:2px 8px;font-size:.6rem;font-weight:700;}
    .expired-badge{display:inline-flex;align-items:center;gap:4px;background:rgba(239,68,68,.3);color:#fca5a5;border-radius:20px;padding:2px 8px;font-size:.6rem;font-weight:700;}

    .cc-actions{display:flex;gap:6px;margin-top:12px;flex-wrap:wrap;}
    .btn-sm-del{font-size:.73rem;padding:5px 12px;border-radius:7px;background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2);cursor:pointer;font-family:'DM Sans',sans-serif;}
    .btn-sm-del:hover{background:rgba(239,68,68,.2);}
    .btn-sm-def{font-size:.73rem;padding:5px 12px;border-radius:7px;background:rgba(38,208,206,.1);color:#26d0ce;border:1px solid rgba(38,208,206,.2);cursor:pointer;font-family:'DM Sans',sans-serif;}
    .btn-sm-def:hover{background:rgba(38,208,206,.2);}

    /* FORM */
    .form-label{font-size:.73rem;font-weight:600;color:#8b92a8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;display:block;}
    .form-control,.form-select{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:9px 13px;font-size:.87rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;width:100%;transition:border-color .15s;}
    .form-control::placeholder{color:#3a3f52;}
    .form-control:focus,.form-select:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(38,208,206,.1);}
    .form-select option{background:#0f1628;}
    .btn-save{background:var(--grad);color:#fff;border:none;border-radius:10px;padding:10px 22px;font-size:.87rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;}
    .btn-save:hover{opacity:.85;}
    .btn-cancel{background:transparent;color:#8b92a8;border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:9px 18px;font-size:.85rem;cursor:pointer;font-family:'DM Sans',sans-serif;text-decoration:none;display:inline-block;}
    .btn-cancel:hover{color:#e8eaf2;}
    .btn-add{display:inline-flex;align-items:center;gap:6px;background:var(--grad);color:#fff;border:none;border-radius:10px;padding:9px 18px;font-size:.83rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;text-decoration:none;}
    .btn-add:hover{opacity:.85;color:#fff;}

    .a-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#4ade80;margin-bottom:16px;}
    .a-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#f87171;margin-bottom:16px;}
    .security-note{background:rgba(245,158,11,.06);border:1px solid rgba(245,158,11,.15);border-radius:10px;padding:12px 16px;font-size:.78rem;color:rgba(245,158,11,.8);display:flex;gap:8px;align-items:flex-start;}

    footer{border-top:1px solid rgba(255,255,255,.07);padding:24px 16px;text-align:center;color:rgba(255,255,255,.3);font-size:.75rem;}
    footer a{color:rgba(255,255,255,.35);text-decoration:none;margin:0 10px;}
    footer a:hover{color:rgba(255,255,255,.6);}
    @media(max-width:768px){.wrap{flex-direction:column;}.sb{width:100%;position:static;}.cards-grid{grid-template-columns:1fr;}}
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container-fluid px-3 px-lg-4">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto gap-1 align-items-center">
        <li class="nav-item"><a class="nav-link" href="panier.php">🛒 <?= $nb_panier > 0 ? "($nb_panier)" : '' ?></a></li>
        <li class="nav-item"><a class="nav-link" href="deconnexion.php">Déconnexion</a></li>
      </ul>
    </div>
  </div>
</nav>

<div class="wrap">
  <aside class="sb">
    <div class="u-card">
      <div class="u-av"><?= strtoupper(substr($user['prenom']??'U',0,1)) ?></div>
      <div class="u-name"><?= htmlspecialchars(($user['prenom']??'').' '.($user['nom']??'')) ?></div>
      <div class="u-email"><?= htmlspecialchars($user['email']??'') ?></div>
    </div>
    <nav class="sb-nav">
      <a href="mon-compte.php?tab=profil">👤 Mon profil</a>
      <a href="mon-compte.php?tab=securite">🔒 Sécurité</a>
      <a href="adresses.php">📍 Mes adresses</a>
      <a href="paiements.php" class="active">💳 Paiements</a>
      <a href="mes-abonnements.php">🔄 Abonnements</a>
      <a href="mes-commandes.php">📦 Commandes</a>
      <a href="deconnexion.php" style="color:rgba(239,68,68,.6)">⏻ Déconnexion</a>
    </nav>
  </aside>

  <main class="main">
    <?php if ($success): ?><div class="a-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($errors):  ?><div class="a-error"><?php foreach($errors as $e): ?>⚠ <?= htmlspecialchars($e) ?><br><?php endforeach; ?></div><?php endif; ?>

    <!-- FORM -->
    <?php if ($show_form): ?>
    <div class="ccard">
      <div class="ccard-head">Ajouter une carte</div>
      <div class="ccard-body">
        <div class="security-note mb-4">
          <span>🔒</span>
          <div>Vos informations de carte sont sécurisées. Seuls les 4 derniers chiffres sont stockés. Le numéro complet n'est jamais conservé sur nos serveurs (conforme PCI-DSS).</div>
        </div>
        <form method="POST" action="paiements.php" id="card-form">
          <input type="hidden" name="action" value="add">
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Nom du titulaire *</label>
              <input class="form-control" name="card_holder" required placeholder="NOM PRÉNOM" style="text-transform:uppercase" value="<?= htmlspecialchars($_POST['card_holder'] ?? ($user['prenom'].' '.$user['nom'])) ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Numéro de carte *</label>
              <input class="form-control" name="card_number" required placeholder="0000 0000 0000 0000" maxlength="19" id="card-number-input" autocomplete="cc-number">
            </div>
            <div class="col-4">
              <label class="form-label">Mois *</label>
              <select class="form-select" name="exp_month">
                <?php for ($m=1; $m<=12; $m++): ?>
                  <option value="<?= $m ?>" <?= $m==$cur_month?'selected':'' ?>><?= str_pad($m,2,'0',STR_PAD_LEFT) ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="col-4">
              <label class="form-label">Année *</label>
              <select class="form-select" name="exp_year">
                <?php for ($y=$cur_year; $y<=$cur_year+10; $y++): ?>
                  <option value="<?= $y ?>"><?= $y ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="col-4">
              <label class="form-label">CVV *</label>
              <input class="form-control" name="cvv" required placeholder="•••" maxlength="4" type="password" autocomplete="cc-csc">
            </div>
          </div>
          <div class="d-flex gap-3 mt-4">
            <button class="btn-save" type="submit">Ajouter la carte</button>
            <a href="paiements.php" class="btn-cancel">Annuler</a>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- LISTE DES CARTES -->
    <div class="ccard">
      <div class="ccard-head">
        Mes cartes <span style="font-weight:400;color:#5c6378"><?= count($cards) ?> carte(s)</span>
        <?php if (!$show_form): ?>
          <a href="paiements.php?new=1" class="btn-add">+ Ajouter</a>
        <?php endif; ?>
      </div>
      <div class="ccard-body">
        <?php if (!$cards): ?>
        <div style="text-align:center;padding:40px 0;color:#5c6378">
          <div style="font-size:2.5rem;margin-bottom:12px;opacity:.3">💳</div>
          <p style="font-size:.88rem">Aucune carte enregistrée.</p>
          <a href="paiements.php?new=1" class="btn-add" style="margin-top:12px">+ Ajouter une carte</a>
        </div>
        <?php else: ?>
        <div class="cards-grid">
          <?php foreach ($cards as $card):
            $is_expired = ($card['exp_year'] < $cur_year) || ($card['exp_year'] == $cur_year && $card['exp_month'] < $cur_month);
          ?>
          <div>
            <!-- Carte visuelle -->
            <div class="cc-visual" style="background:<?= card_color($card['card_brand']) ?>">
              <div class="cc-top">
                <div class="cc-brand"><?= htmlspecialchars($card['card_brand']) ?> <?= card_icon($card['card_brand']) ?></div>
                <div class="cc-chip">
                  <svg width="18" height="14" viewBox="0 0 18 14"><rect x="0" y="0" width="18" height="14" rx="2" fill="none"/><line x1="6" y1="0" x2="6" y2="14" stroke="rgba(0,0,0,.3)" stroke-width="1"/><line x1="12" y1="0" x2="12" y2="14" stroke="rgba(0,0,0,.3)" stroke-width="1"/><line x1="0" y1="5" x2="18" y2="5" stroke="rgba(0,0,0,.3)" stroke-width="1"/><line x1="0" y1="9" x2="18" y2="9" stroke="rgba(0,0,0,.3)" stroke-width="1"/></svg>
                </div>
              </div>
              <div class="cc-number">•••• •••• •••• <?= htmlspecialchars($card['card_last4']) ?></div>
              <div class="cc-bottom">
                <div>
                  <div class="cc-holder"><?= htmlspecialchars($card['card_holder']) ?></div>
                  <?php if ($card['is_default']): ?><span class="default-badge">⭐ Par défaut</span><?php endif; ?>
                  <?php if ($is_expired): ?><span class="expired-badge">⚠ Expirée</span><?php endif; ?>
                </div>
                <div style="text-align:right">
                  <div class="cc-exp-label">Expire</div>
                  <div class="cc-exp"><?= str_pad($card['exp_month'],2,'0',STR_PAD_LEFT) ?>/<?= $card['exp_year'] ?></div>
                </div>
              </div>
            </div>
            <!-- Actions -->
            <div class="cc-actions">
              <?php if (!$card['is_default']): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="set_default">
                <input type="hidden" name="card_id" value="<?= (int)$card['id'] ?>">
                <button type="submit" class="btn-sm-def">⭐ Définir par défaut</button>
              </form>
              <?php endif; ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer cette carte ?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="card_id" value="<?= (int)$card['id'] ?>">
                <button type="submit" class="btn-sm-del">🗑 Supprimer</button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<footer>
  <a href="Cgu.php">CGU</a>
  <a href="mention_legales.php">Mentions légales</a>
  <a href="Contact.php">Contact</a>
  <span>© 2025 CYNA-IT</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Format numéro de carte
var inp = document.getElementById('card-number-input');
if (inp) {
  inp.addEventListener('input', function(e) {
    var v = e.target.value.replace(/\D/g, '').slice(0, 16);
    e.target.value = v.replace(/(\d{4})(?=\d)/g, '$1 ');
  });
}
</script>
</body>
</html>