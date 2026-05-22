<?php
// ── php/includes/cliente_header.php ─────────────────────────
// Inclua no início de cada página do painel cliente
requireCliente();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title><?= $pageTitle ?? 'Painel' ?> — PiterCell</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{background:#0d0b14;font-family:'Space Grotesk',sans-serif;color:#f0eeff;min-height:100vh;display:flex;flex-direction:column}
a{text-decoration:none;color:inherit}

/* ── NAV ── */
.nav{background:rgba(13,11,20,0.95);border-bottom:1px solid rgba(184,79,255,0.15);padding:0 28px;height:60px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100;backdrop-filter:blur(12px)}
.nav-logo{font-family:'Orbitron',monospace;font-size:14px;font-weight:900;color:#b84fff;letter-spacing:2px}
.nav-logo img{height:38px;width:auto}
.nav-right{display:flex;align-items:center;gap:12px}
.nav-user{font-size:13px;color:rgba(220,200,255,0.6)}
.nav-user strong{color:#f0eeff}
.nav-sair{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.2);border-radius:8px;padding:7px 14px;font-size:12px;font-weight:600;color:#f87171;cursor:pointer;font-family:'Space Grotesk',sans-serif}
.nav-sair:hover{background:rgba(239,68,68,0.2)}

/* ── LAYOUT ── */
.wrapper{display:flex;flex:1}
.sidebar{width:230px;background:#0d0b14;border-right:1px solid rgba(184,79,255,0.1);padding:24px 16px;display:flex;flex-direction:column;gap:4px;min-height:calc(100vh - 60px)}
.sidebar-item{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:10px;font-size:14px;font-weight:500;color:rgba(220,200,255,0.55);cursor:pointer;transition:all .2s;border:1px solid transparent}
.sidebar-item:hover{background:rgba(184,79,255,0.08);color:#f0eeff}
.sidebar-item.active{background:rgba(184,79,255,0.12);color:#b84fff;border-color:rgba(184,79,255,0.2)}
.sidebar-item svg{width:17px;height:17px;stroke:currentColor;fill:none;flex-shrink:0}
.sidebar-label{font-size:10px;font-weight:700;letter-spacing:1px;color:rgba(184,79,255,0.4);text-transform:uppercase;padding:16px 14px 6px}
.main{flex:1;padding:32px 28px;max-width:1000px}

/* ── CARDS / UTILITIES ── */
.card{background:rgba(255,255,255,0.03);border:1px solid rgba(184,79,255,0.15);border-radius:16px;padding:24px}
.page-title{font-family:'Orbitron',monospace;font-size:22px;font-weight:900;margin-bottom:4px}
.page-title em{font-style:normal;color:#b84fff}
.page-sub{font-size:14px;color:rgba(220,200,255,0.5);margin-bottom:28px}
.section-title{font-size:15px;font-weight:700;margin-bottom:16px;color:#f0eeff}
label{display:block;font-size:12px;font-weight:600;color:rgba(220,200,255,0.55);letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px}
input,select,textarea{width:100%;background:rgba(255,255,255,0.04);border:1px solid rgba(184,79,255,0.2);border-radius:10px;padding:10px 14px;color:#f0eeff;font-size:14px;font-family:'Space Grotesk',sans-serif;outline:none;transition:border-color .2s;margin-bottom:16px}
input:focus,select:focus,textarea:focus{border-color:#b84fff}
select option{background:#1a1528}
textarea{resize:vertical;min-height:90px}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.field{margin-bottom:0}
.field input,.field select,.field textarea{margin-bottom:0}
.btn-primary{background:#b84fff;color:#0d0b14;border:none;border-radius:10px;padding:11px 24px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Space Grotesk',sans-serif;display:inline-flex;align-items:center;gap:8px;transition:background .2s}
.btn-primary:hover{background:#a040e0}
.btn-ghost{background:transparent;color:#f0eeff;border:1px solid rgba(184,79,255,0.25);border-radius:10px;padding:10px 20px;font-size:13px;font-weight:600;cursor:pointer;font-family:'Space Grotesk',sans-serif;display:inline-flex;align-items:center;gap:8px}
.btn-ghost:hover{border-color:#b84fff;color:#b84fff}
.badge{display:inline-flex;align-items:center;gap:5px;border-radius:99px;padding:3px 11px;font-size:11px;font-weight:700;white-space:nowrap}
.erro{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:10px 14px;font-size:13px;color:#f87171;margin-bottom:16px}
.ok{background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:8px;padding:10px 14px;font-size:13px;color:#34d399;margin-bottom:16px}
.notif-dot{width:8px;height:8px;background:#b84fff;border-radius:50%;display:inline-block;margin-left:4px;vertical-align:middle;animation:pulse 2s infinite}
@keyframes pulse{0%,100%{opacity:1}50%{opacity:.3}}
@media(max-width:768px){.sidebar{display:none}.main{padding:20px 16px}}
</style>
</head>
<body>

<nav class="nav">
  <a href="<?= SITE_URL ?>/assets/pages/cliente/dashboard.php" class="nav-logo">
    <img src="<?= SITE_URL ?>/assets/img/logo_piterCell.jpg" alt="PiterCell"/>
  </a>
  <div class="nav-right">
    <span class="nav-user">Olá, <strong><?= h($_SESSION['cliente_nome']) ?></strong></span>
    <a href="<?= SITE_URL ?>/assets/pages/logout.php" class="nav-sair">Sair</a>
  </div>
</nav>

<div class="wrapper">
<aside class="sidebar">
  <span class="sidebar-label">Menu</span>
  <a href="<?= SITE_URL ?>/assets/pages/cliente/dashboard.php"
     class="sidebar-item <?= ($activePage??'')==='' ? 'active' : '' ?>">
    <svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    Visão Geral
  </a>
  <a href="<?= SITE_URL ?>/assets/pages/cliente/minhas_ordens.php"
     class="sidebar-item <?= ($activePage??'')==='' ? '' : '' ?>">
    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
    Minhas Ordens
  </a>
  <a href="<?= SITE_URL ?>/assets/pages/cliente/novo_orcamento.php"
     class="sidebar-item <?= ($activePage??'')==='' ? '' : '' ?>">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
    Solicitar Orçamento
  </a>
  <a href="<?= SITE_URL ?>/assets/pages/cliente/delivery.php"
     class="sidebar-item">
    <svg viewBox="0 0 24 24"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
    Delivery / Retirada
  </a>
  <span class="sidebar-label">Conta</span>
  <a href="<?= SITE_URL ?>/assets/pages/cliente/perfil.php"
     class="sidebar-item">
    <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
    Meu Perfil
  </a>
</aside>

<main class="main">
