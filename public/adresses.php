<?php
session_start();
require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['utilisateur_id'])) { header('Location: connexion.php'); exit; }
$user_id = (int)$_SESSION['utilisateur_id'];

// Créer la table si elle n'existe pas
$connexion->exec("
    CREATE TABLE IF NOT EXISTS user_addresses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        label VARCHAR(80) DEFAULT 'Adresse',
        prenom VARCHAR(80) NOT NULL,
        nom VARCHAR(80) NOT NULL,
        adresse1 VARCHAR(200) NOT NULL,
        adresse2 VARCHAR(200) DEFAULT '',
        ville VARCHAR(100) NOT NULL,
        region VARCHAR(100) DEFAULT '',
        code_postal VARCHAR(20) NOT NULL,
        pays VARCHAR(80) NOT NULL DEFAULT 'France',
        telephone VARCHAR(30) DEFAULT '',
        is_default TINYINT(1) DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX(user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$success = '';
$errors  = [];
$edit    = null;

// DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $id = (int)$_POST['address_id'];
    $connexion->prepare("DELETE FROM user_addresses WHERE id=? AND user_id=?")->execute([$id, $user_id]);
    $success = "Adresse supprimée.";
}

// SET DEFAULT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'set_default') {
    $id = (int)$_POST['address_id'];
    $connexion->prepare("UPDATE user_addresses SET is_default=0 WHERE user_id=?")->execute([$user_id]);
    $connexion->prepare("UPDATE user_addresses SET is_default=1 WHERE id=? AND user_id=?")->execute([$id, $user_id]);
    $success = "Adresse par défaut mise à jour.";
}

// SAVE (create or update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['action'] ?? '', ['create', 'update'])) {
    $action    = $_POST['action'];
    $aid       = (int)($_POST['address_id'] ?? 0);
    $prenom    = trim($_POST['prenom']      ?? '');
    $nom       = trim($_POST['nom']         ?? '');
    $adresse1  = trim($_POST['adresse1']    ?? '');
    $adresse2  = trim($_POST['adresse2']    ?? '');
    $ville     = trim($_POST['ville']       ?? '');
    $region    = trim($_POST['region']      ?? '');
    $code      = trim($_POST['code_postal'] ?? '');
    $pays      = trim($_POST['pays']        ?? 'France');
    $tel       = trim($_POST['telephone']   ?? '');
    $label     = trim($_POST['label']       ?? 'Adresse');

    if (empty($prenom))   $errors[] = "Le prénom est requis.";
    if (empty($nom))      $errors[] = "Le nom est requis.";
    if (empty($adresse1)) $errors[] = "L'adresse est requise.";
    if (empty($ville))    $errors[] = "La ville est requise.";
    if (empty($code))     $errors[] = "Le code postal est requis.";
    if (empty($pays))     $errors[] = "Le pays est requis.";

    if (empty($errors)) {
        if ($action === 'create') {
            // Si c'est la première adresse, elle devient default
            $count = (int)$connexion->prepare("SELECT COUNT(*) FROM user_addresses WHERE user_id=?")->execute([$user_id]) ? $connexion->query("SELECT COUNT(*) FROM user_addresses WHERE user_id=$user_id")->fetchColumn() : 0;
            $is_def = ($count === 0) ? 1 : 0;
            $connexion->prepare("INSERT INTO user_addresses (user_id,label,prenom,nom,adresse1,adresse2,ville,region,code_postal,pays,telephone,is_default) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
                ->execute([$user_id,$label,$prenom,$nom,$adresse1,$adresse2,$ville,$region,$code,$pays,$tel,$is_def]);
            $success = "Adresse ajoutée avec succès !";
        } else {
            $connexion->prepare("UPDATE user_addresses SET label=?,prenom=?,nom=?,adresse1=?,adresse2=?,ville=?,region=?,code_postal=?,pays=?,telephone=? WHERE id=? AND user_id=?")
                ->execute([$label,$prenom,$nom,$adresse1,$adresse2,$ville,$region,$code,$pays,$tel,$aid,$user_id]);
            $success = "Adresse mise à jour !";
        }
    }
}

// Load addresses
$addresses = $connexion->prepare("SELECT * FROM user_addresses WHERE user_id=? ORDER BY is_default DESC, id DESC");
$addresses->execute([$user_id]);
$addresses = $addresses->fetchAll();

// Load edit target
if (isset($_GET['edit'])) {
    $stmt = $connexion->prepare("SELECT * FROM user_addresses WHERE id=? AND user_id=?");
    $stmt->execute([(int)$_GET['edit'], $user_id]);
    $edit = $stmt->fetch();
}

$nb_panier = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));
$show_form = isset($_GET['new']) || $edit || !empty($errors);
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA — Carnet d'adresses</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
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

    /* SIDEBAR */
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

    /* CARDS */
    .ccard{background:#0f1628;border:1px solid rgba(255,255,255,.07);border-radius:14px;overflow:hidden;margin-bottom:16px;}
    .ccard-head{padding:14px 20px;border-bottom:1px solid rgba(255,255,255,.07);font-weight:600;font-size:.82rem;color:#8b92a8;text-transform:uppercase;letter-spacing:.5px;display:flex;align-items:center;justify-content:space-between;}
    .ccard-body{padding:20px;}

    /* ADDRESS GRID */
    .addr-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;}
    .addr-item{background:#131c30;border:1px solid rgba(255,255,255,.07);border-radius:12px;padding:18px;position:relative;transition:border-color .2s;}
    .addr-item.is-default{border-color:rgba(38,208,206,.35);background:rgba(38,208,206,.04);}
    .addr-item:hover{border-color:rgba(255,255,255,.15);}
    .addr-label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--cyan);margin-bottom:8px;}
    .addr-default-badge{display:inline-flex;align-items:center;gap:4px;background:rgba(38,208,206,.12);border:1px solid rgba(38,208,206,.2);color:#26d0ce;border-radius:20px;padding:2px 8px;font-size:.62rem;font-weight:600;margin-left:8px;}
    .addr-name{font-size:.9rem;font-weight:600;color:#fff;margin-bottom:4px;}
    .addr-line{font-size:.8rem;color:rgba(255,255,255,.45);line-height:1.6;}
    .addr-actions{display:flex;gap:6px;margin-top:14px;flex-wrap:wrap;}
    .btn-sm-edit{font-size:.73rem;padding:5px 12px;border-radius:7px;background:rgba(79,140,255,.1);color:#93c5fd;border:1px solid rgba(79,140,255,.2);text-decoration:none;transition:all .15s;}
    .btn-sm-edit:hover{background:rgba(79,140,255,.2);color:#bfdbfe;}
    .btn-sm-del{font-size:.73rem;padding:5px 12px;border-radius:7px;background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2);cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .15s;}
    .btn-sm-del:hover{background:rgba(239,68,68,.2);}
    .btn-sm-def{font-size:.73rem;padding:5px 12px;border-radius:7px;background:rgba(38,208,206,.1);color:#26d0ce;border:1px solid rgba(38,208,206,.2);cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .15s;}
    .btn-sm-def:hover{background:rgba(38,208,206,.2);}

    /* FORM */
    .form-label{font-size:.73rem;font-weight:600;color:#8b92a8;text-transform:uppercase;letter-spacing:.4px;margin-bottom:5px;display:block;}
    .form-control,.form-select{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:9px 13px;font-size:.87rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;width:100%;transition:border-color .15s;}
    .form-control::placeholder{color:#3a3f52;}
    .form-control:focus,.form-select:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(38,208,206,.1);}
    .form-select option{background:#0f1628;}
    .btn-save{background:var(--grad);color:#fff;border:none;border-radius:10px;padding:10px 22px;font-size:.87rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;}
    .btn-save:hover{opacity:.85;}
    .btn-cancel{background:transparent;color:#8b92a8;border:1px solid rgba(255,255,255,.12);border-radius:10px;padding:9px 18px;font-size:.85rem;font-weight:500;cursor:pointer;font-family:'DM Sans',sans-serif;text-decoration:none;display:inline-block;}
    .btn-cancel:hover{color:#e8eaf2;border-color:rgba(255,255,255,.25);}
    .btn-add{display:inline-flex;align-items:center;gap:6px;background:var(--grad);color:#fff;border:none;border-radius:10px;padding:9px 18px;font-size:.83rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;text-decoration:none;}
    .btn-add:hover{opacity:.85;color:#fff;}

    .a-success{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#4ade80;margin-bottom:16px;}
    .a-error{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 16px;font-size:.84rem;color:#f87171;margin-bottom:16px;}

    footer{border-top:1px solid rgba(255,255,255,.07);padding:24px 16px;text-align:center;color:rgba(255,255,255,.3);font-size:.75rem;}
    footer a{color:rgba(255,255,255,.35);text-decoration:none;margin:0 10px;}
    footer a:hover{color:rgba(255,255,255,.6);}
    @media(max-width:768px){.wrap{flex-direction:column;}.sb{width:100%;position:static;}.addr-grid{grid-template-columns:1fr;}}
  </style>
</head>
<body>

<?php
$stmt2 = $connexion->prepare("SELECT prenom,nom,email FROM utilisateurs WHERE id=?");
$stmt2->execute([$user_id]);
$user = $stmt2->fetch();
?>

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
  <!-- SIDEBAR -->
  <aside class="sb">
    <div class="u-card">
      <div class="u-av"><?= strtoupper(substr($user['prenom']??'U',0,1)) ?></div>
      <div class="u-name"><?= htmlspecialchars(($user['prenom']??'').' '.($user['nom']??'')) ?></div>
      <div class="u-email"><?= htmlspecialchars($user['email']??'') ?></div>
    </div>
    <nav class="sb-nav">
      <a href="mon-compte.php?tab=profil">👤 Mon profil</a>
      <a href="mon-compte.php?tab=securite">🔒 Sécurité</a>
      <a href="adresses.php" class="active">📍 Mes adresses</a>
      <a href="paiements.php">💳 Paiements</a>
      <a href="mes-abonnements.php">🔄 Abonnements</a>
      <a href="mes-commandes.php">📦 Commandes</a>
      <a href="deconnexion.php" style="color:rgba(239,68,68,.6)">⏻ Déconnexion</a>
    </nav>
  </aside>

  <!-- MAIN -->
  <main class="main">
    <?php if ($success): ?><div class="a-success">✅ <?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($errors):  ?><div class="a-error"><?php foreach($errors as $e): ?>⚠ <?= htmlspecialchars($e) ?><br><?php endforeach; ?></div><?php endif; ?>

    <!-- FORMULAIRE -->
    <?php if ($show_form): ?>
    <div class="ccard">
      <div class="ccard-head">
        <?= $edit ? 'Modifier une adresse' : 'Nouvelle adresse' ?>
      </div>
      <div class="ccard-body">
        <form method="POST" action="adresses.php">
          <input type="hidden" name="action" value="<?= $edit ? 'update' : 'create' ?>">
          <?php if ($edit): ?><input type="hidden" name="address_id" value="<?= (int)$edit['id'] ?>"><?php endif; ?>
          <div class="row g-3">
            <div class="col-12">
              <label class="form-label">Libellé de l'adresse</label>
              <input class="form-control" name="label" placeholder="Ex : Domicile, Bureau..." value="<?= htmlspecialchars($_POST['label'] ?? $edit['label'] ?? 'Adresse') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Prénom *</label>
              <input class="form-control" name="prenom" required value="<?= htmlspecialchars($_POST['prenom'] ?? $edit['prenom'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Nom *</label>
              <input class="form-control" name="nom" required value="<?= htmlspecialchars($_POST['nom'] ?? $edit['nom'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Adresse ligne 1 *</label>
              <input class="form-control" name="adresse1" required placeholder="N° et nom de rue" value="<?= htmlspecialchars($_POST['adresse1'] ?? $edit['adresse1'] ?? '') ?>">
            </div>
            <div class="col-12">
              <label class="form-label">Adresse ligne 2 (optionnel)</label>
              <input class="form-control" name="adresse2" placeholder="Bâtiment, appartement..." value="<?= htmlspecialchars($_POST['adresse2'] ?? $edit['adresse2'] ?? '') ?>">
            </div>
            <div class="col-md-4">
              <label class="form-label">Code postal *</label>
              <input class="form-control" name="code_postal" required value="<?= htmlspecialchars($_POST['code_postal'] ?? $edit['code_postal'] ?? '') ?>">
            </div>
            <div class="col-md-5">
              <label class="form-label">Ville *</label>
              <input class="form-control" name="ville" required value="<?= htmlspecialchars($_POST['ville'] ?? $edit['ville'] ?? '') ?>">
            </div>
            <div class="col-md-3">
              <label class="form-label">Région</label>
              <input class="form-control" name="region" value="<?= htmlspecialchars($_POST['region'] ?? $edit['region'] ?? '') ?>">
            </div>
            <div class="col-md-6">
              <label class="form-label">Pays *</label>
              <select class="form-select" name="pays">
                <?php foreach (['France','Belgique','Suisse','Luxembourg','Canada','États-Unis','Maroc','Algérie','Tunisie','Autre'] as $p): ?>
                  <option <?= ($edit['pays']??'France')===$p?'selected':'' ?>><?= $p ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Téléphone</label>
              <input class="form-control" name="telephone" type="tel" placeholder="+33 6 00 00 00 00" value="<?= htmlspecialchars($_POST['telephone'] ?? $edit['telephone'] ?? '') ?>">
            </div>
          </div>
          <div class="d-flex gap-3 mt-4">
            <button class="btn-save" type="submit"><?= $edit ? 'Mettre à jour' : 'Ajouter l\'adresse' ?></button>
            <a href="adresses.php" class="btn-cancel">Annuler</a>
          </div>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- LISTE DES ADRESSES -->
    <div class="ccard">
      <div class="ccard-head">
        Mes adresses <span style="font-weight:400;color:#5c6378"><?= count($addresses) ?> adresse(s)</span>
        <?php if (!$show_form): ?>
          <a href="adresses.php?new=1" class="btn-add">+ Ajouter</a>
        <?php endif; ?>
      </div>
      <div class="ccard-body">
        <?php if (!$addresses): ?>
        <div style="text-align:center;padding:40px 0;color:#5c6378">
          <div style="font-size:2.5rem;margin-bottom:12px;opacity:.3">📍</div>
          <p style="font-size:.88rem">Aucune adresse enregistrée.</p>
          <a href="adresses.php?new=1" class="btn-add" style="margin-top:12px">+ Ajouter une adresse</a>
        </div>
        <?php else: ?>
        <div class="addr-grid">
          <?php foreach ($addresses as $addr): ?>
          <div class="addr-item <?= $addr['is_default'] ? 'is-default' : '' ?>">
            <div class="addr-label">
              <?= htmlspecialchars($addr['label']) ?>
              <?php if ($addr['is_default']): ?><span class="addr-default-badge">⭐ Par défaut</span><?php endif; ?>
            </div>
            <div class="addr-name"><?= htmlspecialchars($addr['prenom'].' '.$addr['nom']) ?></div>
            <div class="addr-line">
              <?= htmlspecialchars($addr['adresse1']) ?><br>
              <?php if ($addr['adresse2']): ?><?= htmlspecialchars($addr['adresse2']) ?><br><?php endif; ?>
              <?= htmlspecialchars($addr['code_postal'].' '.$addr['ville']) ?><br>
              <?php if ($addr['region']): ?><?= htmlspecialchars($addr['region']) ?>, <?php endif; ?>
              <?= htmlspecialchars($addr['pays']) ?>
              <?php if ($addr['telephone']): ?><br>📞 <?= htmlspecialchars($addr['telephone']) ?><?php endif; ?>
            </div>
            <div class="addr-actions">
              <a href="adresses.php?edit=<?= (int)$addr['id'] ?>" class="btn-sm-edit">✏️ Modifier</a>
              <?php if (!$addr['is_default']): ?>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="set_default">
                <input type="hidden" name="address_id" value="<?= (int)$addr['id'] ?>">
                <button type="submit" class="btn-sm-def">⭐ Définir défaut</button>
              </form>
              <?php endif; ?>
              <form method="POST" style="display:inline" onsubmit="return confirm('Supprimer cette adresse ?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="address_id" value="<?= (int)$addr['id'] ?>">
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
  <a href="a-propos.php">À propos</a>
  <span>© 2025 CYNA-IT</span>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>