<?php
session_start();
require_once __DIR__ . '/../config/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email !== '' && $password !== '') {
        $stmt = $connexion->prepare("SELECT id, mot_de_passe, is_admin FROM utilisateurs WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && (int)$u['is_admin'] === 1 && password_verify($password, $u['mot_de_passe'])) {
            $_SESSION['utilisateur_id'] = (int)$u['id'];
            header('Location: index.php');
            exit;
        }
        $error = "Identifiants invalides ou compte non-admin.";
    } else {
        $error = "Veuillez remplir tous les champs.";
    }
}
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA Admin — Connexion</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400&display=swap" rel="stylesheet">
  <style>
    :root {
      --blue:#1a2980; --cyan:#26d0ce;
      --grad:linear-gradient(135deg,#1a2980,#26d0ce);
    }
    * { box-sizing:border-box; margin:0; padding:0; }
    body {
      font-family:'DM Sans',sans-serif;
      background:#07090f;
      color:#e8eaf2;
      min-height:100vh;
      display:flex; align-items:center; justify-content:center;
      position:relative; overflow:hidden;
    }

    /* Glow blobs background */
    body::before {
      content:'';
      position:fixed; top:-20%; left:-10%;
      width:600px; height:600px; border-radius:50%;
      background:radial-gradient(circle,rgba(26,41,128,.35),transparent 70%);
      pointer-events:none;
    }
    body::after {
      content:'';
      position:fixed; bottom:-20%; right:-10%;
      width:500px; height:500px; border-radius:50%;
      background:radial-gradient(circle,rgba(38,208,206,.2),transparent 70%);
      pointer-events:none;
    }

    .login-wrap {
      width:100%; max-width:420px;
      padding:16px;
      position:relative; z-index:10;
    }

    .login-brand {
      text-align:center; margin-bottom:32px;
    }
    .login-brand .logo {
      width:52px; height:52px; border-radius:14px;
      background:var(--grad); margin:0 auto 12px;
      display:flex; align-items:center; justify-content:center;
      font-size:1.3rem; font-weight:800; color:#fff;
      box-shadow:0 8px 32px rgba(26,41,128,.5);
    }
    .login-brand h1 { font-size:1.5rem; font-weight:700; color:#fff; }
    .login-brand p  { font-size:.82rem; color:#5c6378; margin-top:4px; }

    .login-card {
      background:#0e1117;
      border:1px solid rgba(255,255,255,.08);
      border-radius:20px;
      padding:32px;
      box-shadow:0 24px 80px rgba(0,0,0,.5);
    }

    .error-box {
      background:rgba(239,68,68,.1);
      border:1px solid rgba(239,68,68,.25);
      border-radius:10px; padding:12px 16px;
      font-size:.82rem; color:#f87171; margin-bottom:20px;
      display:flex; align-items:center; gap:8px;
    }

    .field { margin-bottom:18px; }
    .field label {
      display:block; font-size:.73rem; font-weight:600;
      color:#8b92a8; margin-bottom:6px; letter-spacing:.3px;
      text-transform:uppercase;
    }
    .field input {
      width:100%; background:rgba(255,255,255,.04);
      border:1px solid rgba(255,255,255,.1);
      border-radius:10px; padding:11px 14px;
      font-size:.9rem; color:#e8eaf2;
      font-family:'DM Sans',sans-serif; outline:none;
      transition:border-color .15s, box-shadow .15s;
    }
    .field input::placeholder { color:#3a3f52; }
    .field input:focus {
      border-color:#4f8cff;
      box-shadow:0 0 0 3px rgba(79,140,255,.15);
      background:rgba(79,140,255,.05);
    }

    .btn-login {
      width:100%; padding:12px;
      background:var(--grad); color:#fff; border:none;
      border-radius:10px; font-size:.9rem; font-weight:600;
      cursor:pointer; font-family:'DM Sans',sans-serif;
      transition:opacity .15s, transform .1s;
      margin-top:4px;
    }
    .btn-login:hover { opacity:.88; transform:translateY(-1px); }
    .btn-login:active { transform:translateY(0); }

    .login-note {
      margin-top:20px; padding:14px;
      background:rgba(255,255,255,.03); border:1px solid rgba(255,255,255,.06);
      border-radius:10px; font-size:.75rem; color:#5c6378; line-height:1.6;
    }
    .login-note code {
      font-family:'DM Mono',monospace; font-size:.72rem;
      color:#8b92a8; background:rgba(255,255,255,.06);
      padding:1px 5px; border-radius:4px;
    }
  </style>
</head>
<body>
<div class="login-wrap">
  <div class="login-brand">
    <div class="logo">C</div>
    <h1>CYNA Admin</h1>
    <p>Accès réservé aux administrateurs</p>
  </div>

  <div class="login-card">
    <?php if ($error): ?>
      <div class="error-box">⚠ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <div class="field">
        <label>Adresse e-mail</label>
        <input type="email" name="email" required placeholder="admin@cyna.com" autocomplete="email">
      </div>
      <div class="field">
        <label>Mot de passe</label>
        <input type="password" name="password" required placeholder="••••••••" autocomplete="current-password">
      </div>
      <button class="btn-login">Se connecter →</button>
    </form>

    <div class="login-note">
      Compte non-admin ? Exécutez dans phpMyAdmin :<br>
      <code>UPDATE utilisateurs SET is_admin=1 WHERE email='...';</code>
    </div>
  </div>
</div>
</body>
</html>