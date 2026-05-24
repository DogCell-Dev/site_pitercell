<?php
// ── php/includes/cliente_header.php ─────────────────────────
requireCliente();

// Mapa de pages para highlight da sidebar e bottom nav
$navPages = [
  'dashboard' => ['href' => 'dashboard.php',      'label' => 'Início',    'icon' => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>'],
  'ordens'    => ['href' => 'minhas_ordens.php',   'label' => 'Ordens',    'icon' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>'],
  'orcamento' => ['href' => 'novo_orcamento.php',  'label' => 'Orçamento', 'icon' => '<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/>'],
  'delivery'  => ['href' => 'delivery.php',        'label' => 'Entrega',   'icon' => '<rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>'],
  'perfil'    => ['href' => 'perfil.php',          'label' => 'Perfil',    'icon' => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>'],
];
$active = $activePage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title><?= $pageTitle ?? 'Painel' ?> — PiterCell</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/cliente.css">
</head>
<body>

<!-- TOPNAV -->
<nav class="nav">
  <a href="<?= SITE_URL ?>/assets/pages/cliente/dashboard.php" class="nav-logo">
    <img src="<?= SITE_URL ?>/assets/img/logo_piterCell.jpg" alt="PiterCell"/>
  </a>
  <div class="nav-right">
    <span class="nav-user">Olá, <strong><?= h($_SESSION['cliente_nome']) ?></strong></span>
    <a href="<?= SITE_URL ?>/assets/pages/logout.php" class="nav-sair">Sair</a>
  </div>
</nav>

<!-- WRAPPER -->
<div class="wrapper">

  <!-- SIDEBAR DESKTOP -->
  <aside class="sidebar">
    <span class="sidebar-label">Menu</span>
    <?php foreach (['dashboard','ordens','orcamento','delivery'] as $key):
      $item = $navPages[$key];
    ?>
    <a href="<?= SITE_URL ?>/assets/pages/cliente/<?= $item['href'] ?>"
       class="sidebar-item <?= $active === $key ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <?= $item['icon'] ?>
      </svg>
      <?= $key === 'dashboard' ? 'Visão Geral' : ($key === 'ordens' ? 'Minhas Ordens' : ($key === 'orcamento' ? 'Solicitar Orçamento' : 'Entrega / Retirada')) ?>
    </a>
    <?php endforeach; ?>

    <a href="<?= SITE_URL ?>/assets/pages/cliente/perfil.php"
       class="sidebar-item <?= $active === 'perfil' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
        <?= $navPages['perfil']['icon'] ?>
      </svg>
      Meu Perfil
    </a>
  </aside>

  <!-- CONTEÚDO DA PÁGINA -->
  <main class="main">
