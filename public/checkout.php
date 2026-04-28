<?php
session_start();
$est_connecte = isset($_SESSION['utilisateur_id']);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/stripe_config.php';
require_once __DIR__ . '/../includes/cart_repository.php';

if (!isset($_SESSION['utilisateur_id'])) {
    header('Location: connexion.php');
    exit;
}

$cart  = $_SESSION['cart'] ?? [];
$items = cart_get_products($connexion, $cart);
$total = cart_total($items);

if (count($items) === 0) {
    header('Location: panier.php');
    exit;
}

// Charger les infos utilisateur pour pré-remplir
$stmt = $connexion->prepare("SELECT prenom, nom, email, is_admin FROM utilisateurs WHERE id = ? LIMIT 1");
$stmt->execute([$_SESSION['utilisateur_id']]);
$user = $stmt->fetch();
$_SESSION['is_admin'] = $user ? (int)$user['is_admin'] : 0;
$show_admin_link = $_SESSION['is_admin'] === 1;

// Charger adresse par défaut si elle existe
$default_addr = null;
try {
    $astmt = $connexion->prepare("SELECT * FROM user_addresses WHERE user_id=? AND is_default=1 LIMIT 1");
    $astmt->execute([$_SESSION['utilisateur_id']]);
    $default_addr = $astmt->fetch();
} catch (Exception $e) { /* table pas encore créée */ }

// Charger les cartes enregistrées
$saved_cards = [];
try {
    $cstmt = $connexion->prepare("SELECT * FROM user_payment_methods WHERE user_id=? ORDER BY is_default DESC, id DESC");
    $cstmt->execute([$_SESSION['utilisateur_id']]);
    $saved_cards = $cstmt->fetchAll();
} catch (Exception $e) { /* table pas encore créée */ }

$nb_panier = count($items);
$tva       = $total * 0.20;
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — Finaliser la commande</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <script src="https://js.stripe.com/v3/"></script>
  <style>
    :root{
      --blue:#1a2980;--cyan:#26d0ce;
      --grad:linear-gradient(135deg,#1a2980,#26d0ce);
      --bg:#0b1020;--card:#0f1628;--card2:#131c30;
      --border:rgba(255,255,255,.07);--muted:rgba(255,255,255,.45);
    }
    *{box-sizing:border-box;}
    body{background:var(--bg);color:#e8eaf2;font-family:'DM Sans',sans-serif;margin:0;min-height:100vh;}

    /* NAVBAR */
    .navbar{background:rgba(11,16,32,.97)!important;border-bottom:1px solid var(--border);backdrop-filter:blur(14px);height:62px;padding:0;}
    .navbar .container{height:62px;align-items:center;}
    .navbar-brand{font-weight:900;font-size:1.3rem;letter-spacing:-.5px;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;padding:0;margin-right:20px;}
    .nav-link-plain{color:rgba(255,255,255,.6);text-decoration:none;font-size:.83rem;font-weight:500;padding:6px 12px;border-radius:8px;transition:all .15s;}
    .nav-link-plain:hover{color:#fff;background:rgba(255,255,255,.06);}

    /* STEPS */
    .steps{display:flex;align-items:center;gap:0;margin-bottom:36px;}
    .step{display:flex;align-items:center;gap:8px;font-size:.8rem;font-weight:600;}
    .step-num{width:26px;height:26px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;flex-shrink:0;}
    .step.done .step-num{background:rgba(74,222,128,.15);border:1px solid rgba(74,222,128,.3);color:#4ade80;}
    .step.active .step-num{background:var(--grad);color:#fff;}
    .step.inactive .step-num{background:rgba(255,255,255,.06);border:1px solid var(--border);color:var(--muted);}
    .step.done .step-label{color:#4ade80;}
    .step.active .step-label{color:#fff;}
    .step.inactive .step-label{color:var(--muted);}
    .step-sep{flex:1;height:1px;background:var(--border);margin:0 10px;max-width:40px;}

    /* PAGE */
    .page-wrap{max-width:1100px;margin:0 auto;padding:36px 16px;}
    .page-title{font-size:1.5rem;font-weight:800;color:#fff;margin-bottom:6px;}
    .page-sub{font-size:.85rem;color:var(--muted);margin-bottom:28px;}

    /* FORM SECTIONS */
    .form-section{background:var(--card);border:1px solid var(--border);border-radius:16px;overflow:hidden;margin-bottom:16px;}
    .form-section-head{padding:16px 22px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;}
    .form-section-icon{width:32px;height:32px;border-radius:8px;background:rgba(38,208,206,.1);border:1px solid rgba(38,208,206,.15);display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;}
    .form-section-title{font-size:.88rem;font-weight:700;color:#fff;}
    .form-section-sub{font-size:.72rem;color:var(--muted);}
    .form-section-body{padding:22px;}

    /* INPUTS */
    .field-label{font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--muted);margin-bottom:5px;display:block;}
    .field-input{width:100%;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:10px 14px;font-size:.88rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .15s,background .15s;}
    .field-input::placeholder{color:#3a3f52;}
    .field-input:focus{border-color:rgba(38,208,206,.4);background:rgba(255,255,255,.07);box-shadow:0 0 0 3px rgba(38,208,206,.08);}
    .field-input.mono{font-family:'DM Mono',monospace;letter-spacing:2px;}
    textarea.field-input{resize:vertical;min-height:80px;}
    select.field-input option{background:#0f1628;}

    /* ADDR DEFAULT BADGE */
    .addr-default{background:rgba(38,208,206,.08);border:1px solid rgba(38,208,206,.15);border-radius:10px;padding:12px 16px;font-size:.82rem;color:rgba(255,255,255,.7);display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px;}
    .addr-default-text{display:flex;align-items:center;gap:8px;}
    .addr-change{font-size:.75rem;color:var(--cyan);text-decoration:none;font-weight:600;}
    .addr-change:hover{color:#fff;}

    /* CARD INPUT */
    .card-preview{background:linear-gradient(135deg,#1a2980,#26d0ce);border-radius:12px;padding:18px 20px;margin-bottom:20px;position:relative;overflow:hidden;min-height:100px;}
    .card-preview::before{content:'';position:absolute;top:-20px;right:-20px;width:120px;height:120px;border-radius:50%;background:rgba(255,255,255,.06);}
    .card-preview-num{font-family:'DM Mono',monospace;font-size:.95rem;color:#fff;letter-spacing:3px;margin-bottom:12px;}
    .card-preview-bottom{display:flex;justify-content:space-between;align-items:flex-end;}
    .card-preview-label{font-size:.55rem;color:rgba(255,255,255,.5);text-transform:uppercase;letter-spacing:.5px;}
    .card-preview-val{font-size:.8rem;color:#fff;font-family:'DM Mono',monospace;}

    /* RECAP */
    .recap{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:24px;position:sticky;top:76px;}
    .recap-title{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:16px;}
    .recap-item{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;padding:10px 0;border-bottom:1px solid var(--border);}
    .recap-item:last-of-type{border-bottom:none;}
    .recap-item-name{font-size:.85rem;font-weight:600;color:#fff;}
    .recap-item-cycle{font-size:.72rem;color:var(--muted);margin-top:2px;}
    .recap-item-price{font-size:.88rem;font-weight:700;color:#fff;white-space:nowrap;}
    .recap-divider{border:none;border-top:1px solid var(--border);margin:16px 0;}
    .recap-row{display:flex;justify-content:space-between;font-size:.84rem;color:var(--muted);margin-bottom:8px;}
    .recap-total{display:flex;justify-content:space-between;align-items:center;margin-top:4px;}
    .recap-total-label{font-size:1rem;font-weight:800;color:#fff;}
    .recap-total-amount{font-size:1.3rem;font-weight:800;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;}

    /* SUBMIT */
    .btn-submit{display:block;width:100%;background:var(--grad);color:#fff;border:none;border-radius:12px;padding:15px;font-size:.95rem;font-weight:800;cursor:pointer;font-family:'DM Sans',sans-serif;text-align:center;letter-spacing:.3px;transition:opacity .15s;margin-top:20px;}
    .btn-submit:hover{opacity:.85;}

    /* SECURE */
    .secure-row{display:flex;gap:16px;justify-content:center;margin-top:14px;flex-wrap:wrap;}
    .secure-item{display:flex;align-items:center;gap:5px;font-size:.7rem;color:var(--muted);}

    /* NOTICE */
    .notice{background:rgba(38,208,206,.05);border:1px solid rgba(38,208,206,.12);border-radius:10px;padding:12px 16px;font-size:.78rem;color:rgba(255,255,255,.55);margin-top:14px;line-height:1.6;}

    footer{border-top:1px solid var(--border);padding:24px 16px;text-align:center;color:var(--muted);font-size:.75rem;margin-top:40px;}
    footer a{color:rgba(255,255,255,.35);text-decoration:none;margin:0 10px;}
    footer a:hover{color:rgba(255,255,255,.6);}

    @media(max-width:991px){.recap{position:static;}}
    @media(max-width:640px){.steps{gap:0;}.step-label{display:none;}}
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar sticky-top">
  <div class="container">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <div class="d-flex align-items-center gap-2 ms-auto">
      <a href="panier.php" class="nav-link-plain">← Panier</a>
      <?php if ($est_connecte): ?>
        <a href="mon-compte.php" class="nav-link-plain">Mon compte</a>
        <a href="deconnexion.php" class="nav-link-plain">Déconnexion</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="page-wrap">

  <!-- ÉTAPES -->
  <div class="steps">
    <div class="step done">
      <div class="step-num">✓</div>
      <div class="step-label">Panier</div>
    </div>
    <div class="step-sep"></div>
    <div class="step active">
      <div class="step-num">2</div>
      <div class="step-label">Facturation</div>
    </div>
    <div class="step-sep"></div>
    <div class="step inactive">
      <div class="step-num">3</div>
      <div class="step-label">Confirmation</div>
    </div>
  </div>

  <div class="page-title">Finaliser la commande</div>
  <div class="page-sub"><?= $nb_panier ?> service(s) · Total : <?= number_format($total, 2, ',', ' ') ?> €</div>

  <?php
  // Afficher les erreurs Stripe/paiement
  if (!empty($_SESSION['checkout_errors'])) {
      echo '<div style="background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.25);border-radius:12px;padding:16px 20px;margin-bottom:20px">';
      echo '<div style="font-size:.9rem;font-weight:700;color:#f87171;margin-bottom:6px">⚠ Paiement refusé</div>';
      foreach ($_SESSION['checkout_errors'] as $err) {
          echo '<div style="font-size:.84rem;color:rgba(239,68,68,.8)">' . htmlspecialchars($err) . '</div>';
      }
      echo '</div>';
      unset($_SESSION['checkout_errors']);
  }
  ?>

  <form action="checkout_submit.php" method="POST">
    <div class="row g-4">

      <!-- GAUCHE : formulaire -->
      <div class="col-12 col-lg-7">

        <!-- Adresse de facturation -->
        <div class="form-section">
          <div class="form-section-head">
            <div class="form-section-icon">📍</div>
            <div>
              <div class="form-section-title">Adresse de facturation</div>
              <div class="form-section-sub">Ces informations apparaîtront sur votre facture</div>
            </div>
          </div>
          <div class="form-section-body">

            <?php if ($default_addr): ?>
            <div class="addr-default">
              <div class="addr-default-text">
                <span>⭐</span>
                <span>
                  <strong style="color:#fff"><?= htmlspecialchars($default_addr['prenom'].' '.$default_addr['nom']) ?></strong><br>
                  <?= htmlspecialchars($default_addr['adresse1'].', '.$default_addr['code_postal'].' '.$default_addr['ville']) ?>
                </span>
              </div>
              <a href="adresses.php" class="addr-change">Changer →</a>
            </div>
            <?php endif; ?>

            <div class="row g-3">
              <div class="col-12">
                <label class="field-label">Nom / Société *</label>
                <input type="text" name="billing_name" class="field-input" required
                  placeholder="Nom complet ou raison sociale"
                  value="<?= htmlspecialchars($default_addr ? $default_addr['prenom'].' '.$default_addr['nom'] : ($user['prenom'].' '.$user['nom'])) ?>">
              </div>
              <div class="col-12">
                <label class="field-label">Adresse complète *</label>
                <textarea name="billing_address" class="field-input" required
                  placeholder="Rue, numéro, code postal, ville, pays"><?= htmlspecialchars($default_addr ? $default_addr['adresse1']."\n".$default_addr['code_postal'].' '.$default_addr['ville']."\n".$default_addr['pays'] : '') ?></textarea>
              </div>
            </div>
          </div>
        </div>

        <!-- Informations de paiement -->
        <div class="form-section">
          <div class="form-section-head">
            <div class="form-section-icon">💳</div>
            <div>
              <div class="form-section-title">Informations de paiement</div>
              <div class="form-section-sub">Connexion sécurisée SSL — tokenisation Stripe</div>
            </div>
          </div>
          <div class="form-section-body">

            <?php if ($saved_cards): ?>
            <!-- CARTES ENREGISTRÉES -->
            <div style="margin-bottom:18px">
              <label class="field-label" style="margin-bottom:10px">Choisir une carte enregistrée</label>
              <div style="display:flex;flex-direction:column;gap:8px" id="saved-cards-list">
                <?php foreach ($saved_cards as $card):
                  $is_exp = ($card['exp_year'] < (int)date('Y')) ||
                            ($card['exp_year'] == (int)date('Y') && $card['exp_month'] < (int)date('m'));
                ?>
                <label class="saved-card-label <?= $is_exp ? 'expired' : '' ?>"
                       style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:11px;padding:12px 16px;cursor:<?= $is_exp ? 'not-allowed' : 'pointer' ?>;transition:border-color .15s">
                  <input type="radio" name="saved_card_id" value="<?= (int)$card['id'] ?>"
                         <?= $card['is_default'] && !$is_exp ? 'checked' : '' ?>
                         <?= $is_exp ? 'disabled' : '' ?>
                         onchange="useSavedCard(true)"
                         style="accent-color:#26d0ce;width:16px;height:16px;flex-shrink:0">
                  <div style="flex:1;min-width:0">
                    <div style="font-size:.87rem;font-weight:600;color:<?= $is_exp ? '#5c6378' : '#fff' ?>">
                      <?= htmlspecialchars($card['card_brand']) ?> •••• <?= htmlspecialchars($card['card_last4']) ?>
                      <?php if ($card['is_default']): ?>
                        <span style="font-size:.65rem;background:rgba(38,208,206,.12);color:#26d0ce;border:1px solid rgba(38,208,206,.2);border-radius:20px;padding:1px 7px;margin-left:6px">Par défaut</span>
                      <?php endif; ?>
                      <?php if ($is_exp): ?>
                        <span style="font-size:.65rem;background:rgba(239,68,68,.1);color:#f87171;border:1px solid rgba(239,68,68,.2);border-radius:20px;padding:1px 7px;margin-left:6px">Expirée</span>
                      <?php endif; ?>
                    </div>
                    <div style="font-size:.73rem;color:#5c6378;margin-top:2px">
                      Expire <?= str_pad($card['exp_month'],2,'0',STR_PAD_LEFT) ?>/<?= $card['exp_year'] ?>
                      · <?= htmlspecialchars($card['card_holder']) ?>
                    </div>
                  </div>
                  <div style="font-size:1.3rem"><?= $card['card_brand']==='Mastercard'?'🔴':'💳' ?></div>
                </label>
                <?php endforeach; ?>

                <!-- Option nouvelle carte -->
                <label style="display:flex;align-items:center;gap:12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);border-radius:11px;padding:12px 16px;cursor:pointer;transition:border-color .15s">
                  <input type="radio" name="saved_card_id" value="new"
                         onchange="useSavedCard(false)"
                         style="accent-color:#26d0ce;width:16px;height:16px;flex-shrink:0">
                  <div style="font-size:.87rem;font-weight:600;color:#e8eaf2">+ Utiliser une nouvelle carte</div>
                </label>
              </div>
            </div>

            <!-- NOUVELLE CARTE (masquée si carte enregistrée sélectionnée) -->
            <div id="new-card-section" style="display:none">
            <?php else: ?>
            <div id="new-card-section">
            <?php endif; ?>

              <div class="row g-3">
                <div class="col-12">
                  <label class="field-label">Nom sur la carte *</label>
                  <input type="text" name="card_holder" id="card_holder" class="field-input"
                    placeholder="NOM PRÉNOM" style="text-transform:uppercase"
                    value="<?= htmlspecialchars(strtoupper($user['prenom'].' '.$user['nom'])) ?>">
                </div>
                <div class="col-12">
                  <label class="field-label">Numéro de carte *</label>
                  <div id="stripe-card-number" class="field-input" style="padding:11px 14px;min-height:44px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;transition:border-color .15s"></div>
                </div>
                <div class="col-6">
                  <label class="field-label">Date d'expiration *</label>
                  <div id="stripe-card-expiry" class="field-input" style="padding:11px 14px;min-height:44px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;transition:border-color .15s"></div>
                </div>
                <div class="col-6">
                  <label class="field-label">CVV *</label>
                  <div id="stripe-card-cvc" class="field-input" style="padding:11px 14px;min-height:44px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;transition:border-color .15s"></div>
                </div>
              </div>

              <input type="hidden" name="stripe_token" id="stripe_token">
              <input type="hidden" name="card_last4"   id="card_last4">
              <div id="stripe-error" style="display:none;margin-top:12px;background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:8px;padding:10px 14px;font-size:.82rem;color:#f87171"></div>

            </div><!-- /new-card-section -->

            <div class="notice" style="margin-top:14px">
              🔒 Vos données bancaires sont tokenisées par Stripe et ne transitent jamais par nos serveurs.
            </div>
          </div>
        </div>

      </div>

      <!-- DROITE : récap -->
      <div class="col-12 col-lg-5">
        <div class="recap">
          <div class="recap-title">Récapitulatif de commande</div>

          <?php foreach ($items as $it): ?>
          <div class="recap-item">
            <div>
              <div class="recap-item-name"><?= htmlspecialchars($it['name']) ?></div>
              <div class="recap-item-cycle">
                <?= $it['cycle'] === 'yearly' ? 'Abonnement annuel' : 'Abonnement mensuel' ?>
              </div>
            </div>
            <div class="recap-item-price"><?= number_format($it['unit_price'],2,',',' ') ?> €</div>
          </div>
          <?php endforeach; ?>

          <hr class="recap-divider">

          <div class="recap-row">
            <span>Sous-total HT</span>
            <span><?= number_format($total / 1.2, 2, ',', ' ') ?> €</span>
          </div>
          <div class="recap-row">
            <span>TVA (20%)</span>
            <span><?= number_format($total - ($total / 1.2), 2, ',', ' ') ?> €</span>
          </div>

          <hr class="recap-divider">

          <div class="recap-total">
            <span class="recap-total-label">Total TTC</span>
            <span class="recap-total-amount"><?= number_format($total, 2, ',', ' ') ?> €</span>
          </div>

          <button type="submit" class="btn-submit">
            🔒 Confirmer et payer <?= number_format($total, 2, ',', ' ') ?> €
          </button>

          <div class="secure-row">
            <span class="secure-item">🔒 SSL sécurisé</span>
            <span class="secure-item">🛡 Données chiffrées</span>
            <span class="secure-item">↩ Résiliable</span>
          </div>
        </div>
      </div>

    </div>
  </form>
</div>

<footer>
  <a href="Cgu.php">CGU</a>
  <a href="mention_legales.php">Mentions légales</a>
  <a href="Contact.php">Contact</a>
  <span style="display:block;margin-top:8px">© 2025 CYNA-IT</span>
</footer>

<script>
// ── Cartes enregistrées ───────────────────────────────────────
var hasSavedCards = <?= $saved_cards ? 'true' : 'false' ?>;

function useSavedCard(isSaved) {
  var section = document.getElementById('new-card-section');
  if (section) section.style.display = isSaved ? 'none' : 'block';
}

// Init : si carte par défaut dispo → masquer le form nouvelle carte
if (hasSavedCards) {
  var defaultChecked = document.querySelector('input[name="saved_card_id"]:checked');
  if (defaultChecked && defaultChecked.value !== 'new') {
    useSavedCard(true);
  } else {
    useSavedCard(false);
  }
}

// ── Stripe Elements ───────────────────────────────────────────
var stripe   = Stripe('<?= htmlspecialchars(STRIPE_PUBLISHABLE_KEY) ?>');
var elements = stripe.elements();

var style = {
  base: {
    color: '#e8eaf2',
    fontFamily: '"DM Sans", sans-serif',
    fontSize: '15px',
    '::placeholder': { color: '#3a3f52' },
    iconColor: '#26d0ce',
  },
  invalid: { color: '#f87171', iconColor: '#f87171' },
  complete: { color: '#4ade80', iconColor: '#4ade80' }
};

var cardNumber = elements.create('cardNumber', { style: style, placeholder: '0000 0000 0000 0000' });
var cardExpiry = elements.create('cardExpiry', { style: style });
var cardCvc    = elements.create('cardCvc',    { style: style });

cardNumber.mount('#stripe-card-number');
cardExpiry.mount('#stripe-card-expiry');
cardCvc.mount('#stripe-card-cvc');

// Focus/blur
[
  {el: cardNumber, id: 'stripe-card-number'},
  {el: cardExpiry, id: 'stripe-card-expiry'},
  {el: cardCvc,    id: 'stripe-card-cvc'},
].forEach(function(item) {
  item.el.on('focus', function() {
    document.getElementById(item.id).style.borderColor = 'rgba(38,208,206,.5)';
    document.getElementById(item.id).style.boxShadow   = '0 0 0 3px rgba(38,208,206,.08)';
  });
  item.el.on('blur', function() {
    document.getElementById(item.id).style.borderColor = 'rgba(255,255,255,.1)';
    document.getElementById(item.id).style.boxShadow   = 'none';
  });
  item.el.on('change', function(e) {
    var div = document.getElementById('stripe-error');
    if (e.error) { div.style.display = 'block'; div.textContent = '⚠ ' + e.error.message; }
    else { div.style.display = 'none'; }
  });
});

// ── Submit ────────────────────────────────────────────────────
var form = document.querySelector('form');
form.addEventListener('submit', function(e) {
  e.preventDefault();

  var btn = form.querySelector('.btn-submit');
  btn.disabled = true;
  btn.textContent = '⏳ Traitement en cours...';

  // Carte enregistrée sélectionnée ?
  var savedRadio = document.querySelector('input[name="saved_card_id"]:checked');
  if (savedRadio && savedRadio.value !== 'new') {
    // Soumettre directement avec l'ID de la carte enregistrée
    form.submit();
    return;
  }

  // Nouvelle carte → tokeniser avec Stripe
  var name = document.getElementById('card_holder') ? document.getElementById('card_holder').value : '';
  stripe.createToken(cardNumber, { name: name }).then(function(result) {
    if (result.error) {
      var div = document.getElementById('stripe-error');
      div.style.display = 'block';
      div.textContent = '⚠ ' + result.error.message;
      btn.disabled = false;
      btn.textContent = '🔒 Confirmer et payer <?= number_format($total, 2, ',', ' ') ?> €';
    } else {
      document.getElementById('stripe_token').value = result.token.id;
      document.getElementById('card_last4').value   = result.token.card.last4;
      form.submit();
    }
  });
});
</script>
</body>
</html>