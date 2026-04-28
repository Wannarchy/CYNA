<?php
session_start();
require_once __DIR__ . '/../config/config.php';

$est_connecte = isset($_SESSION['utilisateur_id']);
$nb_panier    = array_sum(array_column($_SESSION['panier'] ?? [], 'qty'));
$success      = false;
$errors       = [];

// Pré-remplir l'email si connecté
$prefill_email = '';
if ($est_connecte) {
    $stmt = $connexion->prepare("SELECT email, prenom, nom FROM utilisateurs WHERE id = ? LIMIT 1");
    $stmt->execute([$_SESSION['utilisateur_id']]);
    $row = $stmt->fetch();
    $prefill_email = $row['email'] ?? '';
}

// Créer table chat_logs si besoin
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

// FORMULAIRE DE CONTACT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'contact') {
    $email   = trim($_POST['email']   ?? '');
    $sujet   = trim($_POST['sujet']   ?? '');
    $message = trim($_POST['message'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Adresse email invalide.";
    if (strlen($sujet) < 3)    $errors[] = "Le sujet est trop court.";
    if (strlen($message) < 10) $errors[] = "Le message est trop court.";

    if (empty($errors)) {
        try {
            require_once __DIR__ . '/../vendor/autoload.php';
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'noreplycyna@gmail.com';
            $mail->Password   = 'uaws jfaf jqal cahx';
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';
            $mail->setFrom('noreply@cyna.com', 'CYNA Contact');
            $mail->addAddress('noreplycyna@gmail.com', 'Support CYNA');
            $mail->addReplyTo($email, $email);
            $mail->Subject = '[Contact CYNA] ' . $sujet;
            $mail->Body    = "De : $email\nSujet : $sujet\n\n$message";
            $mail->send();
        } catch (Exception $e) {}
        $success = true;
    }
}

// CHATBOT API
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'chat') {
    header('Content-Type: application/json');

    $user_msg = trim($_POST['message'] ?? '');
    if (empty($user_msg)) {
        echo json_encode(['response' => "Bonjour ! Comment puis-je vous aider ?"]);
        exit;
    }

    // Base de connaissances CYNA
    $knowledge = "
Tu es l'assistant virtuel de CYNA, une entreprise spécialisée dans les solutions de cybersécurité SaaS (SOC, EDR, XDR).
CYNA est situé au 10 Rue de Penthièvre, 75008 Paris. SIRET : 91371103200015. Email : contact@cyna-it.fr.
Horaires : Lun-Ven 9h-18h.

Produits CYNA :
- SOC (Security Operations Center) : surveillance et détection 24/7, à partir de 299€/mois
- EDR (Endpoint Detection & Response) : protection des endpoints, à partir de 149€/mois
- XDR (Extended Detection & Response) : corrélation multi-sources, prix selon configuration

Paiement : Visa, Mastercard, American Express via Stripe. Paiement sécurisé SSL.
Abonnements : mensuel ou annuel (10% de réduction). Résiliation possible à tout moment depuis l'espace compte.
Essai : certains services proposent une période d'essai gratuite.
Support 24/7 inclus dans tous les abonnements.

Répondre en français, de façon concise et professionnelle (max 2-3 phrases).
Si tu ne sais pas, propose de contacter le support via le formulaire.
Ne jamais inventer de prix ou de fonctionnalités non listées.
";

    // Appel API Claude (Claude in Claude)
    $api_url = 'https://api.anthropic.com/v1/messages';
    $payload = json_encode([
        'model'      => 'claude-sonnet-4-20250514',
        'max_tokens' => 300,
        'system'     => $knowledge,
        'messages'   => [
            ['role' => 'user', 'content' => $user_msg]
        ]
    ]);

    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'x-api-key: ' . ($_ENV['ANTHROPIC_API_KEY'] ?? ''),
        'anthropic-version: 2023-06-01'
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data    = json_decode($response, true);
    $bot_msg = $data['content'][0]['text'] ?? null;

    // Fallback si l'API n'est pas dispo — réponses locales
    if (!$bot_msg) {
        $msg_lower = strtolower($user_msg);
        if (strpos($msg_lower, 'prix') !== false || strpos($msg_lower, 'tarif') !== false || strpos($msg_lower, 'coût') !== false) {
            $bot_msg = "Nos services démarrent à partir de 149€/mois pour l'EDR, 299€/mois pour le SOC. Un abonnement annuel vous fait économiser 10%. Consultez notre catalogue pour les tarifs détaillés.";
        } elseif (strpos($msg_lower, 'abonnement') !== false || strpos($msg_lower, 'résilier') !== false || strpos($msg_lower, 'annuler') !== false) {
            $bot_msg = "Vous pouvez gérer vos abonnements depuis votre espace compte → 'Mes abonnements'. La résiliation prend effet à la fin de la période en cours, sans frais supplémentaires.";
        } elseif (strpos($msg_lower, 'paiement') !== false || strpos($msg_lower, 'carte') !== false || strpos($msg_lower, 'payer') !== false) {
            $bot_msg = "Nous acceptons les cartes Visa, Mastercard et American Express via Stripe (paiement 100% sécurisé). Vous pouvez enregistrer vos cartes dans 'Mes paiements'.";
        } elseif (strpos($msg_lower, 'soc') !== false) {
            $bot_msg = "Notre service SOC (Security Operations Center) offre une surveillance et détection des menaces 24/7, avec des analystes dédiés et des alertes en temps réel. À partir de 299€/mois.";
        } elseif (strpos($msg_lower, 'edr') !== false) {
            $bot_msg = "Notre EDR (Endpoint Detection & Response) protège vos postes de travail et serveurs contre les menaces avancées, avec isolation automatique et remédiation guidée. À partir de 149€/mois.";
        } elseif (strpos($msg_lower, 'xdr') !== false) {
            $bot_msg = "Notre XDR corrèle les données de sécurité multi-sources (endpoints, réseau, cloud, email) pour une détection plus rapide et une réponse coordonnée aux cybermenaces.";
        } elseif (strpos($msg_lower, 'essai') !== false || strpos($msg_lower, 'gratuit') !== false || strpos($msg_lower, 'demo') !== false) {
            $bot_msg = "Certains de nos services proposent une période d'essai gratuite. Consultez les pages produits de notre catalogue pour voir les offres d'essai disponibles.";
        } elseif (strpos($msg_lower, 'bonjour') !== false || strpos($msg_lower, 'salut') !== false || strpos($msg_lower, 'hello') !== false) {
            $bot_msg = "Bonjour ! 👋 Je suis l'assistant virtuel CYNA. Comment puis-je vous aider aujourd'hui ? Je peux répondre à vos questions sur nos services SOC, EDR, XDR, les abonnements ou le paiement.";
        } elseif (strpos($msg_lower, 'contact') !== false || strpos($msg_lower, 'humain') !== false || strpos($msg_lower, 'agent') !== false || strpos($msg_lower, 'support') !== false) {
            $bot_msg = "Je vais vous mettre en relation avec notre équipe ! Utilisez le formulaire de contact ci-dessous ou écrivez-nous à contact@cyna-it.fr. Nous répondons sous 24h (Lun-Ven 9h-18h).";
        } else {
            $bot_msg = "Je ne suis pas sûr de comprendre votre demande. Pour une assistance personnalisée, n'hésitez pas à utiliser le formulaire de contact ou à nous écrire à contact@cyna-it.fr.";
        }
    }

    // Logger la conversation
    try {
        $connexion->prepare("INSERT INTO chat_logs (user_id, session_id, user_message, bot_response) VALUES (?,?,?,?)")
                  ->execute([$_SESSION['utilisateur_id'] ?? null, session_id(), $user_msg, $bot_msg]);
    } catch (Exception $e) {}

    echo json_encode(['response' => $bot_msg]);
    exit;
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CYNA — Contact</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root{--blue:#1a2980;--cyan:#26d0ce;--grad:linear-gradient(135deg,#1a2980,#26d0ce);--bg:#0b1020;--card:#0f1628;--border:rgba(255,255,255,.07);--muted:rgba(255,255,255,.45);}
    *{box-sizing:border-box;}
    body{background:var(--bg);color:#e8eaf2;font-family:'DM Sans',sans-serif;margin:0;}

    /* NAVBAR */
    .navbar{background:rgba(11,16,32,.97)!important;border-bottom:1px solid var(--border);backdrop-filter:blur(14px);height:62px;padding:0;}
    .navbar .container{height:62px;align-items:center;}
    .navbar-brand{font-weight:900;font-size:1.3rem;background:var(--grad);-webkit-background-clip:text;-webkit-text-fill-color:transparent;padding:0;margin-right:20px;}
    .nav-link-p{color:rgba(255,255,255,.6);text-decoration:none;font-size:.83rem;padding:6px 12px;border-radius:8px;transition:all .15s;}
    .nav-link-p:hover{color:#fff;background:rgba(255,255,255,.06);}
    .cart-btn{display:inline-flex;align-items:center;gap:6px;background:rgba(38,208,206,.1);border:1px solid rgba(38,208,206,.2);color:#26d0ce;border-radius:20px;padding:5px 14px;font-size:.8rem;font-weight:700;text-decoration:none;}
    .btn-cyna{background:var(--grad);color:#fff;border:none;border-radius:9px;padding:7px 16px;font-size:.82rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;}
    .btn-cyna:hover{opacity:.85;color:#fff;}

    /* HERO */
    .hero{padding:52px 0 36px;text-align:center;}
    .hero-tag{display:inline-block;background:rgba(38,208,206,.1);border:1px solid rgba(38,208,206,.2);color:var(--cyan);border-radius:20px;padding:4px 14px;font-size:.72rem;font-weight:700;letter-spacing:.5px;text-transform:uppercase;margin-bottom:14px;}
    .hero h1{font-size:clamp(1.6rem,3vw,2.2rem);font-weight:800;color:#fff;margin-bottom:8px;}
    .hero p{color:var(--muted);max-width:480px;margin:0 auto;font-size:.92rem;}

    /* CARDS */
    .ccard{background:var(--card);border:1px solid var(--border);border-radius:18px;padding:28px;}
    .ccard h2{font-size:.95rem;font-weight:700;color:#fff;margin-bottom:20px;display:flex;align-items:center;gap:8px;}

    /* FORM */
    .form-label{font-size:.7rem;font-weight:700;color:#8b92a8;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px;display:block;}
    .form-control,.form-select{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:10px;padding:10px 14px;font-size:.88rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;width:100%;transition:border-color .15s;}
    .form-control::placeholder{color:#3a3f52;}
    .form-control:focus,.form-select:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(38,208,206,.1);}
    .form-select option{background:#0f1628;}
    textarea.form-control{resize:vertical;min-height:130px;}
    .btn-send{background:var(--grad);color:#fff;border:none;border-radius:10px;padding:12px 28px;font-size:.9rem;font-weight:700;cursor:pointer;font-family:'DM Sans',sans-serif;width:100%;transition:opacity .15s;}
    .btn-send:hover{opacity:.85;}
    .error-box{background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.2);border-radius:10px;padding:12px 16px;font-size:.83rem;color:#f87171;margin-bottom:16px;}
    .success-box{background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.2);border-radius:14px;padding:28px;text-align:center;}
    .success-box .ico{font-size:2.5rem;margin-bottom:10px;}
    .success-box h3{font-size:1.05rem;font-weight:700;color:#fff;margin-bottom:6px;}
    .success-box p{font-size:.83rem;color:var(--muted);}

    /* INFOS */
    .info-item{display:flex;align-items:flex-start;gap:12px;margin-bottom:18px;}
    .info-icon{width:36px;height:36px;border-radius:9px;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;}
    .info-label{font-size:.68rem;font-weight:700;color:#5c6378;text-transform:uppercase;letter-spacing:.5px;margin-bottom:2px;}
    .info-val{font-size:.85rem;color:#e8eaf2;line-height:1.5;}
    .info-val a{color:var(--cyan);text-decoration:none;}

    /* FAQ */
    .faq-item{border-bottom:1px solid var(--border);padding:13px 0;}
    .faq-item:last-child{border-bottom:none;}
    .faq-q{font-size:.86rem;font-weight:600;color:#fff;cursor:pointer;display:flex;justify-content:space-between;align-items:center;gap:8px;}
    .faq-a{font-size:.81rem;color:var(--muted);margin-top:8px;line-height:1.7;display:none;}
    .faq-a.open{display:block;}
    .chevron{transition:transform .2s;font-size:.65rem;flex-shrink:0;}
    .chevron.open{transform:rotate(180deg);}

    /* ── CHATBOT ─────────────────────────────────── */
    .chat-fab{position:fixed;bottom:28px;right:28px;z-index:1000;background:var(--grad);border:none;border-radius:50px;padding:13px 22px;color:#fff;font-family:'DM Sans',sans-serif;font-size:.88rem;font-weight:700;cursor:pointer;box-shadow:0 8px 24px rgba(38,208,206,.3);display:flex;align-items:center;gap:8px;transition:transform .2s,box-shadow .2s;}
    .chat-fab:hover{transform:translateY(-3px);box-shadow:0 12px 32px rgba(38,208,206,.4);}
    .chat-fab .dot{width:8px;height:8px;border-radius:50%;background:#4ade80;animation:pulse 2s infinite;}
    @keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.6;transform:scale(1.2)}}

    .chat-window{position:fixed;bottom:100px;right:28px;z-index:1000;width:360px;max-height:520px;background:#0f1628;border:1px solid rgba(255,255,255,.1);border-radius:20px;overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.5);transform:scale(0);transform-origin:bottom right;transition:transform .25s cubic-bezier(.34,1.56,.64,1);pointer-events:none;}
    .chat-window.open{transform:scale(1);pointer-events:all;}

    .chat-header{background:var(--grad);padding:16px 18px;display:flex;align-items:center;gap:12px;flex-shrink:0;}
    .chat-avatar{width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,.2);display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0;}
    .chat-header-info{flex:1;}
    .chat-header-name{font-size:.88rem;font-weight:700;color:#fff;}
    .chat-header-status{font-size:.7rem;color:rgba(255,255,255,.7);display:flex;align-items:center;gap:4px;}
    .chat-header-status::before{content:'';width:6px;height:6px;border-radius:50%;background:#4ade80;display:inline-block;}
    .chat-close{background:rgba(255,255,255,.15);border:none;border-radius:50%;width:28px;height:28px;color:#fff;cursor:pointer;font-size:.9rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
    .chat-close:hover{background:rgba(255,255,255,.25);}

    .chat-messages{flex:1;overflow-y:auto;padding:16px;display:flex;flex-direction:column;gap:10px;scroll-behavior:smooth;}
    .chat-messages::-webkit-scrollbar{width:4px;}
    .chat-messages::-webkit-scrollbar-track{background:transparent;}
    .chat-messages::-webkit-scrollbar-thumb{background:rgba(255,255,255,.1);border-radius:2px;}

    .msg{max-width:82%;font-size:.83rem;line-height:1.5;}
    .msg.bot{align-self:flex-start;}
    .msg.user{align-self:flex-end;}
    .msg-bubble{padding:10px 14px;border-radius:14px;}
    .msg.bot .msg-bubble{background:#131c30;color:#e8eaf2;border-bottom-left-radius:4px;}
    .msg.user .msg-bubble{background:var(--grad);color:#fff;border-bottom-right-radius:4px;}
    .msg-time{font-size:.62rem;color:#5c6378;margin-top:3px;padding:0 2px;}
    .msg.user .msg-time{text-align:right;}

    /* Typing indicator */
    .typing .msg-bubble{display:flex;align-items:center;gap:4px;padding:12px 16px;}
    .typing-dot{width:6px;height:6px;border-radius:50%;background:#5c6378;animation:typing 1.2s infinite;}
    .typing-dot:nth-child(2){animation-delay:.2s;}
    .typing-dot:nth-child(3){animation-delay:.4s;}
    @keyframes typing{0%,60%,100%{transform:translateY(0)}30%{transform:translateY(-6px)}}

    /* Suggestions */
    .chat-suggestions{padding:8px 16px;display:flex;gap:6px;flex-wrap:wrap;flex-shrink:0;border-top:1px solid var(--border);}
    .sug-btn{background:rgba(38,208,206,.08);border:1px solid rgba(38,208,206,.18);color:var(--cyan);border-radius:20px;padding:4px 11px;font-size:.72rem;font-weight:600;cursor:pointer;font-family:'DM Sans',sans-serif;transition:all .15s;white-space:nowrap;}
    .sug-btn:hover{background:rgba(38,208,206,.18);}

    /* Input */
    .chat-input-wrap{padding:12px 14px;border-top:1px solid var(--border);display:flex;gap:8px;flex-shrink:0;background:#0f1628;}
    .chat-input{flex:1;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:22px;padding:9px 16px;font-size:.84rem;color:#e8eaf2;font-family:'DM Sans',sans-serif;outline:none;transition:border-color .15s;}
    .chat-input::placeholder{color:#3a3f52;}
    .chat-input:focus{border-color:rgba(38,208,206,.4);}
    .chat-send{background:var(--grad);border:none;border-radius:50%;width:36px;height:36px;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:.9rem;flex-shrink:0;transition:opacity .15s;}
    .chat-send:hover{opacity:.85;}
    .chat-send:disabled{opacity:.4;cursor:not-allowed;}

    footer{border-top:1px solid var(--border);margin-top:60px;padding:24px 0;text-align:center;color:#5c6378;font-size:.78rem;}
    footer a{color:#5c6378;text-decoration:none;margin:0 12px;}
    footer a:hover{color:#8b92a8;}
    @media(max-width:480px){.chat-window{width:calc(100vw - 24px);right:12px;bottom:90px;}}
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-expand-lg sticky-top">
  <div class="container">
    <a class="navbar-brand" href="../index.php">CYNA</a>
    <div class="d-flex align-items-center gap-2 ms-auto">
      <a href="catalogue.php" class="nav-link-p d-none d-md-block">Catalogue</a>
      <a href="panier.php" class="cart-btn">🛒<?= $nb_panier > 0 ? " ($nb_panier)" : '' ?></a>
      <?php if ($est_connecte): ?>
        <a href="mon-compte.php" class="nav-link-p">Mon compte</a>
        <a href="deconnexion.php" class="nav-link-p">Déconnexion</a>
      <?php else: ?>
        <a href="connexion.php" class="nav-link-p">Connexion</a>
        <a href="inscription.php" class="btn-cyna">S'inscrire</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<div class="container">
  <div class="hero">
    <div class="hero-tag">💬 Support</div>
    <h1>Contactez-nous</h1>
    <p>Notre équipe est disponible du lundi au vendredi, de 9h à 18h pour répondre à vos questions.</p>
  </div>

  <div class="row g-4 mb-5">

    <!-- FORMULAIRE -->
    <div class="col-12 col-lg-7">
      <div class="ccard">
        <h2>📩 Envoyer un message</h2>

        <?php if ($success): ?>
          <div class="success-box">
            <div class="ico">✅</div>
            <h3>Message envoyé !</h3>
            <p>Merci ! Notre équipe vous répondra sous 24h à l'adresse indiquée.</p>
            <a href="Contact.php" style="display:inline-block;margin-top:14px;color:var(--cyan);font-size:.84rem">Envoyer un autre message →</a>
          </div>
        <?php else: ?>
          <?php if ($errors): ?>
            <div class="error-box"><?php foreach($errors as $e): ?>⚠ <?= htmlspecialchars($e) ?><br><?php endforeach; ?></div>
          <?php endif; ?>
          <form method="POST">
            <input type="hidden" name="action" value="contact">
            <div class="mb-3">
              <label class="form-label">Adresse email *</label>
              <input class="form-control" type="email" name="email" required
                value="<?= htmlspecialchars($prefill_email ?: ($_POST['email'] ?? '')) ?>"
                placeholder="vous@exemple.com">
            </div>
            <div class="mb-3">
              <label class="form-label">Sujet *</label>
              <select class="form-select" name="sujet">
                <option value="">— Choisir un sujet —</option>
                <?php foreach (['Question sur les abonnements','Problème technique','Demande de devis','Facturation','Partenariat','Autre'] as $s): ?>
                  <option value="<?= $s ?>" <?= ($_POST['sujet']??'')===$s?'selected':'' ?>><?= $s ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-4">
              <label class="form-label">Message *</label>
              <textarea class="form-control" name="message" placeholder="Décrivez votre demande..."><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
            </div>
            <button class="btn-send" type="submit">Envoyer le message →</button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <!-- INFOS + FAQ -->
    <div class="col-12 col-lg-5">
      <div class="ccard" style="margin-bottom:16px">
        <h2>📋 Nos coordonnées</h2>

        <div class="info-item">
          <div class="info-icon">📍</div>
          <div><div class="info-label">Adresse</div><div class="info-val">10 Rue de Penthièvre<br>75008 Paris, France</div></div>
        </div>
        <div class="info-item">
          <div class="info-icon">✉</div>
          <div><div class="info-label">Email</div><div class="info-val"><a href="mailto:contact@cyna-it.fr">contact@cyna-it.fr</a></div></div>
        </div>
        <div class="info-item">
          <div class="info-icon">🕐</div>
          <div><div class="info-label">Horaires</div><div class="info-val">Lun–Ven : 9h–18h<br>Hors jours fériés</div></div>
        </div>
        <div class="info-item" style="margin-bottom:0">
          <div class="info-icon">🌐</div>
          <div><div class="info-label">Site web</div><div class="info-val"><a href="https://www.cyna-it.fr" target="_blank">www.cyna-it.fr</a></div></div>
        </div>
      </div>

      <div class="ccard">
        <h2>❓ Questions fréquentes</h2>
        <?php
        $faqs = [
          ['Comment modifier mon abonnement ?', 'Connectez-vous → "Mes abonnements" → cliquez sur "Changer" à côté de l\'abonnement souhaité.'],
          ['Quels modes de paiement acceptez-vous ?', 'Nous acceptons Visa, Mastercard et American Express via Stripe (paiement sécurisé SSL).'],
          ['Comment résilier mon abonnement ?', 'Depuis "Mes abonnements" → bouton "Résilier". La résiliation est effective à la fin de la période en cours.'],
          ['Y a-t-il une période d\'essai ?', 'Certains services proposent un essai gratuit. Consultez la page produit correspondante pour les détails.'],
          ['Comment récupérer mon mot de passe ?', 'Page de connexion → "Mot de passe oublié" → entrez votre email → cliquez le lien reçu (valide 24h).'],
        ];
        foreach ($faqs as [$q, $a]):
        ?>
        <div class="faq-item">
          <div class="faq-q" onclick="toggleFaq(this)"><?= $q ?> <span class="chevron">▼</span></div>
          <div class="faq-a"><?= $a ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

  </div>
</div>

<footer>
  <a href="mention_legales.php">Mentions légales</a>
  <a href="Cgu.php">CGU</a>
  <a href="Contact.php">Contact</a>
  <a href="a-propos.php">À propos</a>
  <span>© 2025 CYNA-IT</span>
</footer>

<!-- ── CHATBOT ─────────────────────────────────────────────── -->

<!-- Bouton flottant -->
<button class="chat-fab" onclick="toggleChat()" id="chatFab">
  <span class="dot"></span>
  💬 Contact Me
</button>

<!-- Fenêtre de chat -->
<div class="chat-window" id="chatWindow">
  <div class="chat-header">
    <div class="chat-avatar">🤖</div>
    <div class="chat-header-info">
      <div class="chat-header-name">Assistant CYNA</div>
      <div class="chat-header-status">En ligne — Répond instantanément</div>
    </div>
    <button class="chat-close" onclick="toggleChat()">✕</button>
  </div>

  <div class="chat-messages" id="chatMessages">
    <!-- Message de bienvenue -->
    <div class="msg bot">
      <div class="msg-bubble">👋 Bonjour ! Je suis l'assistant virtuel CYNA. Je peux répondre à vos questions sur nos services SOC, EDR, XDR, les abonnements, les paiements et plus encore. Comment puis-je vous aider ?</div>
      <div class="msg-time">Maintenant</div>
    </div>
  </div>

  <!-- Suggestions rapides -->
  <div class="chat-suggestions" id="chatSuggestions">
    <button class="sug-btn" onclick="sendSuggestion('Quels sont vos tarifs ?')">💰 Tarifs</button>
    <button class="sug-btn" onclick="sendSuggestion('Comment modifier mon abonnement ?')">🔄 Abonnement</button>
    <button class="sug-btn" onclick="sendSuggestion('Modes de paiement acceptés ?')">💳 Paiement</button>
    <button class="sug-btn" onclick="sendSuggestion('Parler à un humain')">👤 Agent</button>
  </div>

  <!-- Input -->
  <div class="chat-input-wrap">
    <input class="chat-input" id="chatInput" type="text" placeholder="Écrivez votre message..."
           onkeydown="if(event.key==='Enter')sendMessage()">
    <button class="chat-send" id="chatSendBtn" onclick="sendMessage()">➤</button>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── FAQ ──────────────────────────────────────────────────────
function toggleFaq(el) {
  var ans  = el.nextElementSibling;
  var chev = el.querySelector('.chevron');
  ans.classList.toggle('open');
  chev.classList.toggle('open');
}

// ── CHATBOT ──────────────────────────────────────────────────
var chatOpen = false;

function toggleChat() {
  chatOpen = !chatOpen;
  document.getElementById('chatWindow').classList.toggle('open', chatOpen);
  if (chatOpen) {
    document.getElementById('chatInput').focus();
    document.getElementById('chatFab').innerHTML = '<span class="dot"></span> ✕ Fermer';
  } else {
    document.getElementById('chatFab').innerHTML = '<span class="dot"></span> 💬 Contact Me';
  }
}

function getTime() {
  var d = new Date();
  return d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0');
}

function addMessage(text, type) {
  var msgs = document.getElementById('chatMessages');
  var div  = document.createElement('div');
  div.className = 'msg ' + type;
  div.innerHTML = '<div class="msg-bubble">' + text + '</div><div class="msg-time">' + getTime() + '</div>';
  msgs.appendChild(div);
  msgs.scrollTop = msgs.scrollHeight;
  return div;
}

function showTyping() {
  var msgs = document.getElementById('chatMessages');
  var div  = document.createElement('div');
  div.className = 'msg bot typing';
  div.id = 'typingIndicator';
  div.innerHTML = '<div class="msg-bubble"><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span></div>';
  msgs.appendChild(div);
  msgs.scrollTop = msgs.scrollHeight;
}

function hideTyping() {
  var t = document.getElementById('typingIndicator');
  if (t) t.remove();
}

function sendMessage() {
  var input = document.getElementById('chatInput');
  var msg   = input.value.trim();
  if (!msg) return;

  input.value = '';
  document.getElementById('chatSuggestions').style.display = 'none';

  addMessage(msg, 'user');

  var btn = document.getElementById('chatSendBtn');
  btn.disabled = true;
  showTyping();

  // Appel API chatbot
  var formData = new FormData();
  formData.append('action', 'chat');
  formData.append('message', msg);

  fetch('Contact.php', { method: 'POST', body: formData })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      hideTyping();
      btn.disabled = false;
      addMessage(data.response || "Désolé, je n'ai pas pu répondre.", 'bot');

      // Si l'utilisateur veut parler à un humain
      if (msg.toLowerCase().includes('humain') || msg.toLowerCase().includes('agent') || msg.toLowerCase().includes('formulaire')) {
        setTimeout(function() {
          addMessage('📩 Vous pouvez utiliser le formulaire de contact ci-dessus ou nous écrire directement à <a href="mailto:contact@cyna-it.fr" style="color:var(--cyan)">contact@cyna-it.fr</a>', 'bot');
        }, 500);
      }
    })
    .catch(function() {
      hideTyping();
      btn.disabled = false;
      addMessage("Une erreur est survenue. Veuillez réessayer ou utiliser le formulaire de contact.", 'bot');
    });
}

function sendSuggestion(text) {
  document.getElementById('chatInput').value = text;
  sendMessage();
}

// Ouvrir le chat automatiquement après 8 secondes si pas encore ouvert
setTimeout(function() {
  if (!chatOpen) {
    // Petite notification sur le bouton
    var fab = document.getElementById('chatFab');
    fab.style.animation = 'none';
    fab.style.transform = 'scale(1.05)';
    setTimeout(function() { fab.style.transform = ''; }, 300);
  }
}, 8000);
</script>
</body>
</html>