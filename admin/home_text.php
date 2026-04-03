<?php
require_once __DIR__ . '/header.php';
require_once __DIR__ . '/../config/config.php';

$row = $connexion->query("SELECT * FROM homepage_content ORDER BY id ASC LIMIT 1")->fetch();
$current = $row ? $row['content_text'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $text = trim($_POST['content_text'] ?? '');
    if ($row) {
        $s = $connexion->prepare("UPDATE homepage_content SET content_text=? WHERE id=?");
        $s->execute([$text, $row['id']]);
    } else {
        $s = $connexion->prepare("INSERT INTO homepage_content (content_text) VALUES (?)");
        $s->execute([$text]);
    }
    header('Location: home_text.php'); exit;
}
?>

<div class="ph">
  <div class="ph-left">
    <h1>Texte Homepage</h1>
    <p>Modifiez le texte affiché sous le carousel de la page d'accueil</p>
  </div>
</div>

<div class="row g-3">
  <div class="col-12 col-lg-7">
    <div class="card">
      <div class="card-head">Éditer le contenu</div>
      <div class="card-body">
        <form method="POST">
          <div class="mb-3">
            <label class="form-label">Texte d'accueil</label>
            <textarea class="form-control" name="content_text" rows="8" required
              placeholder="Ex : Cyna, votre partenaire en cybersécurité..."><?= htmlspecialchars($current) ?></textarea>
          </div>
          <button class="btn-cyna">Enregistrer les modifications</button>
        </form>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-5">
    <div class="card" style="height:100%">
      <div class="card-head">Aperçu rendu</div>
      <div class="card-body">
        <div style="
          background:linear-gradient(135deg,rgba(26,41,128,.3),rgba(38,208,206,.15));
          border:1px solid rgba(38,208,206,.15);
          border-radius:10px; padding:18px;
          font-size:.88rem; color:rgba(255,255,255,.8); line-height:1.7;
          min-height:120px;
        ">
          <?= $current ? nl2br(htmlspecialchars($current)) : '<span style="opacity:.4;font-style:italic">Aucun texte configuré.</span>' ?>
        </div>
        <p style="font-size:.72rem;color:var(--c-muted);margin-top:10px">
          Ce texte s'affiche sous le carousel sur la page d'accueil publique.
        </p>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>