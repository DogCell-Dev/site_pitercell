<?php
require_once '../includes/config.php';
require_once '../includes/layout.php';
requireLogin();
 
$db = getDB();
 
$stats = $db->query("
    SELECT
        COUNT(*) as total,
        SUM(status IN ('aguardando','em_analise','em_reparo')) as abertas,
        SUM(status = 'pronto') as prontas,
        SUM(status = 'entregue') as entregues,
        COALESCE(SUM(CASE WHEN status='entregue' THEN COALESCE(valor_final, valor_orcamento, 0) END),0) as faturamento
    FROM ordens
")->fetch();
 
$recentes = $db->query("
    SELECT o.*, c.nome as cliente_nome, c.telefone
    FROM ordens o JOIN clientes c ON c.id = o.cliente_id
    ORDER BY o.criado_em DESC LIMIT 8
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard — Pitercell Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<?php renderSidebar('dashboard'); ?>
<div class="main">
  <div class="topbar">
    <span class="topbar-title">Dashboard</span>
    <div class="topbar-right">
      <span class="topbar-badge">Pitercell Admin</span>
    </div>
  </div>
  <div class="page">
    <div class="page-header">
      <h1>Visão <em>Geral</em></h1>
      <a href="ordens.php?novo=1" class="btn btn-primary">
        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nova OS
      </a>
    </div>
 
    <div class="cards-grid">
      <div class="stat-card" style="--card-color:#b84fff">
        <div class="stat-card-top">
          <div class="stat-card-icon"><svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 0-1 1"/></svg></div>
        </div>
        <div class="stat-card-num"><?= $stats['abertas'] ?></div>
        <div class="stat-card-label">OS em Aberto</div>
      </div>
      <div class="stat-card" style="--card-color:#00c864">
        <div class="stat-card-top">
          <div class="stat-card-icon" style="background:rgba(0,200,100,0.1);border-color:rgba(0,200,100,0.2)"><svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
        </div>
        <div class="stat-card-num"><?= $stats['prontas'] ?></div>
        <div class="stat-card-label">Prontas p/ Entrega</div>
      </div>
      <div class="stat-card" style="--card-color:#3b82f6">
        <div class="stat-card-top">
          <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);border-color:rgba(59,130,246,0.2)"><svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></div>
        </div>
        <div class="stat-card-num"><?= $stats['entregues'] ?></div>
        <div class="stat-card-label">Entregues</div>
      </div>
      <div class="stat-card" style="--card-color:#f5a623">
        <div class="stat-card-top">
          <div class="stat-card-icon" style="background:rgba(245,166,35,0.1);border-color:rgba(245,166,35,0.2)"><svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
        </div>
        <div class="stat-card-num">R$<?= number_format($stats['faturamento'],0,'.','.') ?></div>
        <div class="stat-card-label">Faturamento</div>
      </div>
    </div>
 
    <div class="table-wrap">
      <div class="table-head">
        <h3>Ordens Recentes</h3>
        <a href="ordens.php" class="btn btn-ghost btn-sm">Ver todas</a>
      </div>
      <table>
        <thead><tr>
          <th>Nº OS</th><th>Cliente</th><th>Dispositivo</th><th>Serviço</th><th>Status</th><th>Orçamento</th><th>Ações</th>
        </tr></thead>
        <tbody>
        <?php if (empty($recentes)): ?>
          <tr><td colspan="7"><div class="empty-state"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/></svg><p>Nenhuma OS ainda</p></div></td></tr>
        <?php else: ?>
          <?php foreach($recentes as $o): ?>
          <tr>
            <td><strong><?= htmlspecialchars($o['numero']) ?></strong></td>
            <td>
              <div class="td-name"><?= htmlspecialchars($o['cliente_nome']) ?></div>
              <div class="td-sub"><?= htmlspecialchars($o['telefone']) ?></div>
            </td>
            <td><?= htmlspecialchars($o['dispositivo'].' '.$o['modelo']) ?></td>
            <td><?= htmlspecialchars($o['servico'] ?? '—') ?></td>
            <td><span class="status-badge <?= statusClass($o['status']) ?>"><?= statusLabel($o['status']) ?></span></td>
            <td><?= $o['valor_orcamento'] ? 'R$'.number_format($o['valor_orcamento'],2,',','.') : '—' ?></td>
            <td><a href="ordens.php?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">Detalhes</a></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
</body>
</html>