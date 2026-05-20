<?php
require_once '../includes/config.php';
require_once '../includes/layout.php';
requireLogin();
$db = getDB();

$clienteId = intval($_GET['cliente'] ?? 0);
if (!$clienteId) { header('Location: clientes.php'); exit; }

$cliente = $db->prepare("SELECT * FROM clientes WHERE id=?"); $cliente->execute([$clienteId]); $cliente=$cliente->fetch();
if (!$cliente) { header('Location: clientes.php'); exit; }

$ordens = $db->prepare("SELECT * FROM ordens WHERE cliente_id=? ORDER BY criado_em DESC"); $ordens->execute([$clienteId]); $ordens=$ordens->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Histórico — Pitercell Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
<?php renderSidebar('clientes'); ?>
<div class="main">
  <div class="topbar">
    <span class="topbar-title">Histórico do Cliente</span>
    <div class="topbar-right"><span class="topbar-badge">Pitercell Admin</span></div>
  </div>
  <div class="page">
    <div class="page-header">
      <h1>Histórico — <em><?= htmlspecialchars($cliente['nome']) ?></em></h1>
      <a href="clientes.php" class="btn btn-ghost">← Voltar</a>
    </div>

    <!-- Info do cliente -->
    <div class="detail-card" style="margin-bottom:24px;display:grid;grid-template-columns:repeat(4,1fr);gap:16px">
      <div><div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Nome</div><strong><?= htmlspecialchars($cliente['nome']) ?></strong></div>
      <div><div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Telefone</div><?= htmlspecialchars($cliente['telefone']) ?></div>
      <div><div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">E-mail</div><?= htmlspecialchars($cliente['email']?:'—') ?></div>
      <div><div style="font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Total de OS</div><span style="color:var(--accent);font-family:'Orbitron',sans-serif;font-weight:700"><?= count($ordens) ?></span></div>
    </div>

    <div class="table-wrap">
      <div class="table-head"><h3>Ordens de Serviço</h3></div>
      <table>
        <thead><tr><th>Nº OS</th><th>Dispositivo</th><th>Serviço</th><th>Status</th><th>Orçamento</th><th>Valor Final</th><th>Data</th><th></th></tr></thead>
        <tbody>
        <?php if(empty($ordens)): ?>
          <tr><td colspan="8"><div class="empty-state"><p>Nenhuma OS para este cliente</p></div></td></tr>
        <?php else: ?>
          <?php foreach($ordens as $o): ?>
          <tr>
            <td><strong><?= htmlspecialchars($o['numero']) ?></strong></td>
            <td><?= htmlspecialchars($o['dispositivo'].' '.$o['modelo']) ?></td>
            <td><?= htmlspecialchars($o['servico']??'—') ?></td>
            <td><span class="status-badge <?= statusClass($o['status']) ?>"><?= statusLabel($o['status']) ?></span></td>
            <td><?= $o['valor_orcamento']?'R$'.number_format($o['valor_orcamento'],2,',','.'):'—' ?></td>
            <td><?= $o['valor_final']?'R$'.number_format($o['valor_final'],2,',','.'):'—' ?></td>
            <td><?= date('d/m/Y',strtotime($o['criado_em'])) ?></td>
            <td><a href="ordens.php?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">Ver OS</a></td>
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
