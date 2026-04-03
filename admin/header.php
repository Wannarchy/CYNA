<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../config/config.php';

$nb_orders   = (int)$connexion->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$nb_products = (int)$connexion->query("SELECT COUNT(*) FROM products")->fetchColumn();
$nb_cats     = (int)$connexion->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$revenue     = (float)$connexion->query("SELECT COALESCE(SUM(total),0) FROM orders")->fetchColumn();
$cur = basename($_SERVER['PHP_SELF']);

$titles = [
  'index.php'      => 'Dashboard',
  'categories.php' => 'Catégories',
  'products.php'   => 'Produits',
  'slides.php'     => 'Slides homepage',
  'home_text.php'  => 'Texte homepage',
  'orders.php'     => 'Commandes',
  'order_view.php' => 'Détail commande',
  'login.php'      => 'Connexion',
];
$pageTitle = $titles[$cur] ?? 'Administration';
?>
<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>CYNA Admin — <?= $pageTitle ?></title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    /* ── DESIGN TOKENS ─────────────────────────────── */
    :root {
      --c-bg:       #07090f;
      --c-surface:  #0e1117;
      --c-card:     #131720;
      --c-border:   rgba(255,255,255,.07);
      --c-border2:  rgba(255,255,255,.12);
      --c-text:     #e8eaf2;
      --c-muted:    #5c6378;
      --c-muted2:   #8b92a8;
      --c-blue:     #1a2980;
      --c-cyan:     #26d0ce;
      --c-accent:   #4f8cff;
      --c-success:  #22c55e;
      --c-danger:   #ef4444;
      --c-warning:  #f59e0b;
      --grad:       linear-gradient(135deg,#1a2980,#26d0ce);
      --grad-soft:  linear-gradient(135deg,rgba(26,41,128,.25),rgba(38,208,206,.15));
      --sw:         256px;
      --radius:     10px;
      --radius-lg:  16px;
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: var(--c-bg);
      color: var(--c-text);
      min-height: 100vh;
      font-size: 14px;
      line-height: 1.6;
    }

    /* ── SCROLLBAR ──────────────────────────────────── */
    ::-webkit-scrollbar { width: 6px; }
    ::-webkit-scrollbar-track { background: var(--c-bg); }
    ::-webkit-scrollbar-thumb { background: rgba(255,255,255,.1); border-radius: 3px; }

    /* ── SIDEBAR ────────────────────────────────────── */
    .sb {
      position: fixed; top: 0; left: 0;
      width: var(--sw); height: 100vh;
      background: var(--c-surface);
      border-right: 1px solid var(--c-border);
      display: flex; flex-direction: column;
      z-index: 200;
      overflow-y: auto;
    }

    .sb-brand {
      padding: 24px 20px 20px;
      border-bottom: 1px solid var(--c-border);
      display: flex; align-items: center; gap: 10px;
    }
    .sb-brand-logo {
      width: 34px; height: 34px; border-radius: 8px;
      background: var(--grad);
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 13px; color: #fff; flex-shrink: 0;
    }
    .sb-brand-text .name { font-size: 1rem; font-weight: 700; color: #fff; line-height: 1.1; }
    .sb-brand-text .sub  { font-size: .65rem; color: var(--c-muted2); letter-spacing: 1px; text-transform: uppercase; }

    .sb-section {
      font-size: .62rem; font-weight: 600;
      text-transform: uppercase; letter-spacing: 1.4px;
      color: var(--c-muted); padding: 18px 20px 6px;
    }

    .sb-nav { display: flex; flex-direction: column; gap: 2px; padding: 0 10px; }
    .sb-nav a {
      display: flex; align-items: center; gap: 10px;
      padding: 9px 12px; border-radius: var(--radius);
      color: var(--c-muted2); font-size: .85rem; font-weight: 400;
      text-decoration: none; transition: all .15s;
      border: 1px solid transparent;
    }
    .sb-nav a .icon { font-size: .9rem; width: 18px; text-align: center; flex-shrink: 0; opacity: .7; }
    .sb-nav a:hover { color: var(--c-text); background: rgba(255,255,255,.04); }
    .sb-nav a.active {
      color: #fff; background: var(--grad-soft);
      border-color: rgba(79,140,255,.2); font-weight: 500;
    }
    .sb-nav a.active .icon { opacity: 1; }
    .sb-nav a .count {
      margin-left: auto; font-size: .65rem; font-weight: 600;
      background: rgba(79,140,255,.2); color: var(--c-accent);
      padding: 1px 7px; border-radius: 20px;
    }

    .sb-footer {
      margin-top: auto; padding: 16px 10px;
      border-top: 1px solid var(--c-border);
      display: flex; flex-direction: column; gap: 2px;
    }
    .sb-footer a {
      display: flex; align-items: center; gap: 10px;
      padding: 8px 12px; border-radius: var(--radius);
      font-size: .82rem; color: var(--c-muted); text-decoration: none; transition: all .15s;
    }
    .sb-footer a:hover { color: var(--c-text); background: rgba(255,255,255,.04); }
    .sb-footer .logout { color: rgba(239,68,68,.6); }
    .sb-footer .logout:hover { color: var(--c-danger); background: rgba(239,68,68,.08); }

    /* ── MAIN LAYOUT ────────────────────────────────── */
    .main { margin-left: var(--sw); display: flex; flex-direction: column; min-height: 100vh; }

    /* ── TOPBAR ─────────────────────────────────────── */
    .topbar {
      height: 60px; padding: 0 28px;
      background: var(--c-surface); border-bottom: 1px solid var(--c-border);
      display: flex; align-items: center; justify-content: space-between;
      position: sticky; top: 0; z-index: 100;
    }
    .topbar-left { display: flex; align-items: center; gap: 12px; }
    .topbar-breadcrumb { font-size: .78rem; color: var(--c-muted); }
    .topbar-breadcrumb span { color: var(--c-text); font-weight: 500; }
    .topbar-right { display: flex; align-items: center; gap: 10px; }
    .t-pill {
      font-size: .72rem; font-weight: 500;
      padding: 4px 11px; border-radius: 20px;
      background: rgba(255,255,255,.05); border: 1px solid var(--c-border2);
      color: var(--c-muted2);
    }
    .t-pill b { color: var(--c-accent); }
    .t-avatar {
      width: 30px; height: 30px; border-radius: 50%;
      background: var(--grad); font-size: .72rem; font-weight: 700;
      display: flex; align-items: center; justify-content: center; color: #fff;
    }

    /* ── PAGE CONTENT ───────────────────────────────── */
    .content { padding: 28px; flex: 1; }

    /* ── PAGE HEADER ────────────────────────────────── */
    .ph { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 24px; gap: 12px; }
    .ph-left h1 { font-size: 1.3rem; font-weight: 700; color: #fff; line-height: 1.2; }
    .ph-left p  { font-size: .8rem; color: var(--c-muted2); margin-top: 4px; }

    /* ── CARDS ──────────────────────────────────────── */
    .card {
      background: var(--c-card); border: 1px solid var(--c-border);
      border-radius: var(--radius-lg); overflow: hidden;
    }
    .card-head {
      padding: 14px 18px; border-bottom: 1px solid var(--c-border);
      display: flex; align-items: center; justify-content: space-between;
      font-size: .82rem; font-weight: 600; color: var(--c-muted2);
      letter-spacing: .3px; text-transform: uppercase;
    }
    .card-body { padding: 18px; }

    /* ── STAT CARDS ─────────────────────────────────── */
    .stat-card {
      background: var(--c-card); border: 1px solid var(--c-border);
      border-radius: var(--radius-lg); padding: 20px;
      display: flex; align-items: center; gap: 14px;
      text-decoration: none; color: var(--c-text);
      transition: border-color .2s, transform .15s, box-shadow .2s;
      position: relative; overflow: hidden;
    }
    .stat-card::before {
      content: ''; position: absolute; inset: 0;
      background: var(--grad-soft); opacity: 0; transition: opacity .2s;
    }
    .stat-card:hover { border-color: rgba(79,140,255,.3); transform: translateY(-2px); box-shadow: 0 8px 32px rgba(0,0,0,.3); color: var(--c-text); }
    .stat-card:hover::before { opacity: 1; }
    .stat-icon {
      width: 44px; height: 44px; border-radius: 10px;
      background: var(--grad); display: flex; align-items: center;
      justify-content: center; font-size: 1.1rem; flex-shrink: 0;
      position: relative; z-index: 1;
    }
    .stat-info { position: relative; z-index: 1; }
    .stat-val { font-size: 1.5rem; font-weight: 700; color: #fff; line-height: 1.1; }
    .stat-lbl { font-size: .75rem; color: var(--c-muted2); margin-top: 3px; }

    /* ── TABLE ──────────────────────────────────────── */
    .ctable { width: 100%; border-collapse: collapse; font-size: .84rem; }
    .ctable thead tr { border-bottom: 1px solid var(--c-border2); }
    .ctable thead th {
      padding: 10px 16px; font-size: .67rem; font-weight: 600;
      text-transform: uppercase; letter-spacing: .8px; color: var(--c-muted);
      text-align: left;
    }
    .ctable tbody tr { border-bottom: 1px solid var(--c-border); transition: background .1s; }
    .ctable tbody tr:last-child { border-bottom: none; }
    .ctable tbody tr:hover { background: rgba(255,255,255,.02); }
    .ctable td { padding: 12px 16px; color: var(--c-text); vertical-align: middle; }
    .ctable td.muted { color: var(--c-muted2); font-size: .8rem; }
    .ctable td.mono  { font-family: 'DM Mono', monospace; font-size: .78rem; color: var(--c-muted2); }

    /* ── BADGES ─────────────────────────────────────── */
    .badge { display: inline-flex; align-items: center; gap: 4px; padding: 3px 9px; border-radius: 20px; font-size: .68rem; font-weight: 600; }
    .badge-green  { background: rgba(34,197,94,.12);  color: #4ade80; border: 1px solid rgba(34,197,94,.2); }
    .badge-red    { background: rgba(239,68,68,.12);  color: #f87171; border: 1px solid rgba(239,68,68,.2); }
    .badge-blue   { background: rgba(79,140,255,.12); color: #93c5fd; border: 1px solid rgba(79,140,255,.2); }
    .badge-yellow { background: rgba(245,158,11,.12); color: #fbbf24; border: 1px solid rgba(245,158,11,.2); }
    .badge-gray   { background: rgba(255,255,255,.06); color: var(--c-muted2); border: 1px solid var(--c-border2); }

    /* ── BUTTONS ─────────────────────────────────────── */
    .btn-cyna {
      display: inline-flex; align-items: center; gap: 6px;
      background: var(--grad); color: #fff; border: none;
      padding: 9px 20px; border-radius: var(--radius); font-size: .84rem; font-weight: 600;
      cursor: pointer; transition: opacity .15s, transform .1s;
      font-family: 'DM Sans', sans-serif; text-decoration: none;
    }
    .btn-cyna:hover { opacity: .85; transform: translateY(-1px); color: #fff; }
    .btn-cyna:active { transform: translateY(0); }

    .btn-ghost {
      display: inline-flex; align-items: center; gap: 6px;
      background: transparent; color: var(--c-muted2);
      border: 1px solid var(--c-border2); padding: 7px 14px;
      border-radius: var(--radius); font-size: .8rem; font-weight: 500;
      cursor: pointer; transition: all .15s; text-decoration: none;
      font-family: 'DM Sans', sans-serif;
    }
    .btn-ghost:hover { color: var(--c-text); border-color: rgba(255,255,255,.2); background: rgba(255,255,255,.04); }

    .btn-del {
      display: inline-flex; align-items: center; gap: 4px;
      background: rgba(239,68,68,.1); color: #f87171;
      border: 1px solid rgba(239,68,68,.2); padding: 5px 12px;
      border-radius: 7px; font-size: .75rem; font-weight: 500;
      text-decoration: none; transition: all .15s; cursor: pointer;
      font-family: 'DM Sans', sans-serif;
    }
    .btn-del:hover { background: rgba(239,68,68,.2); color: #fca5a5; }

    .btn-view {
      display: inline-flex; align-items: center; gap: 4px;
      background: rgba(79,140,255,.1); color: #93c5fd;
      border: 1px solid rgba(79,140,255,.2); padding: 5px 12px;
      border-radius: 7px; font-size: .75rem; font-weight: 500;
      text-decoration: none; transition: all .15s;
    }
    .btn-view:hover { background: rgba(79,140,255,.2); color: #bfdbfe; }

    /* ── FORMS ──────────────────────────────────────── */
    .form-label {
      display: block; font-size: .75rem; font-weight: 600;
      color: var(--c-muted2); margin-bottom: 5px; letter-spacing: .3px;
    }
    .form-control, .form-select {
      width: 100%; background: rgba(255,255,255,.04);
      border: 1px solid var(--c-border2); border-radius: var(--radius);
      padding: 9px 12px; font-size: .84rem; color: var(--c-text);
      transition: border-color .15s, box-shadow .15s;
      font-family: 'DM Sans', sans-serif; outline: none;
    }
    .form-control::placeholder { color: var(--c-muted); }
    .form-select option { background: #1a1f2e; }
    .form-control:focus, .form-select:focus {
      border-color: var(--c-accent); background: rgba(79,140,255,.06);
      box-shadow: 0 0 0 3px rgba(79,140,255,.12);
    }
    textarea.form-control { resize: vertical; min-height: 100px; }

    /* ── UTILITIES ──────────────────────────────────── */
    .text-right { text-align: right; }
    .gap-2 { gap: 8px !important; }
    .row { --bs-gutter-x: 16px; --bs-gutter-y: 16px; }
    a { color: inherit; }

    /* ── EMPTY STATE ────────────────────────────────── */
    .empty-state {
      text-align: center; padding: 48px 24px; color: var(--c-muted);
    }
    .empty-state .icon { font-size: 2.5rem; margin-bottom: 12px; opacity: .4; }
    .empty-state p { font-size: .85rem; }

    /* ── DIVIDER ─────────────────────────────────────── */
    .divider { height: 1px; background: var(--c-border); margin: 20px 0; }
  </style>
</head>
<body>

<!-- ═══════════════ SIDEBAR ═══════════════ -->
<aside class="sb">
  <div class="sb-brand">
    <div class="sb-brand-logo">C</div>
    <div class="sb-brand-text">
      <div class="name">CYNA</div>
      <div class="sub">Administration</div>
    </div>
  </div>

  <div class="sb-section">Catalogue</div>
  <nav class="sb-nav">
    <a href="index.php" class="<?= $cur==='index.php'?'active':'' ?>">
      <span class="icon">◈</span> Dashboard
    </a>
    <a href="categories.php" class="<?= $cur==='categories.php'?'active':'' ?>">
      <span class="icon">▦</span> Catégories
      <span class="count"><?= $nb_cats ?></span>
    </a>
    <a href="products.php" class="<?= $cur==='products.php'?'active':'' ?>">
      <span class="icon">⬡</span> Produits
      <span class="count"><?= $nb_products ?></span>
    </a>
  </nav>

  <div class="sb-section">Homepage</div>
  <nav class="sb-nav">
    <a href="slides.php" class="<?= $cur==='slides.php'?'active':'' ?>">
      <span class="icon">▣</span> Carrousel / Slides
    </a>
    <a href="home_text.php" class="<?= $cur==='home_text.php'?'active':'' ?>">
      <span class="icon">≡</span> Texte accueil
    </a>
  </nav>

  <div class="sb-section">Ventes</div>
  <nav class="sb-nav">
    <a href="orders.php" class="<?= in_array($cur,['orders.php','order_view.php'])?'active':'' ?>">
      <span class="icon">◎</span> Commandes
      <?php if ($nb_orders > 0): ?>
        <span class="count"><?= $nb_orders ?></span>
      <?php endif; ?>
    </a>
  </nav>

  <div class="sb-footer">
    <a href="../index.php"><span class="icon">↗</span> Voir le site</a>
    <a href="logout.php" class="logout"><span class="icon">⏻</span> Déconnexion</a>
  </div>
</aside>

<!-- ═══════════════ MAIN ═══════════════ -->
<div class="main">
  <header class="topbar">
    <div class="topbar-left">
      <div class="topbar-breadcrumb">
        Administration &rsaquo; <span><?= $pageTitle ?></span>
      </div>
    </div>
    <div class="topbar-right">
      <span class="t-pill"><b><?= $nb_orders ?></b> commandes</span>
      <span class="t-pill"><b><?= number_format($revenue,0,',',' ') ?> €</b> CA</span>
      <div class="t-avatar">A</div>
    </div>
  </header>
  <div class="content">