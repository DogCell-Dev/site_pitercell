<?php
require_once '../includes/config.php';
require_once '../includes/layout.php';
requireLogin();
$db = getDB();

// Salvar cliente
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'salvar_cliente') {
    $id = intval($_POST['id'] ?? 0);
    $d  = [
        ':nome'     => trim($_POST['nome']),
        ':telefone' => trim($_POST['telefone']),
        ':email'    => trim($_POST['email']),
        ':cpf'      => trim($_POST['cpf']),
        ':endereco' => trim($_POST['endereco']),
    ];
    if ($id) {
        $db->prepare("UPDATE clientes SET nome=:nome,telefone=:telefone,email=:email,cpf=:cpf,endereco=:endereco WHERE id=:id")
           ->execute($d + [':id' => $id]);
    } else {
        $db->prepare("INSERT INTO clientes (nome,telefone,email,cpf,endereco) VALUES (:nome,:telefone,:email,:cpf,:endereco)")
           ->execute($d);
    }
    header('Location: clientes.php'); exit;
}

// Excluir
if (isset($_GET['excluir'])) {
    $db->prepare("DELETE FROM clientes WHERE id=?")->execute([intval($_GET['excluir'])]);
    header('Location: clientes.php'); exit;
}

$busca = trim($_GET['q'] ?? '');
$clientes = $db->prepare("
    SELECT c.*, COUNT(o.id) as total_os
    FROM clientes c
    LEFT JOIN ordens o ON o.cliente_id = c.id
    WHERE c.nome LIKE :q OR c.telefone LIKE :q
    GROUP BY c.id ORDER BY c.nome
");
$clientes->execute([':q' => "%$busca%"]);
$clientes = $clientes->fetchAll();

$editCliente = null;
if (isset($_GET['editar'])) {
    $editCliente = $db->prepare("SELECT * FROM clientes WHERE id=?")->execute([intval($_GET['editar'])]);
    $editCliente = $db->prepare("SELECT * FROM clientes WHERE id=?");
    $editCliente->execute([intval($_GET['editar'])]);
    $editCliente = $editCliente->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Clientes — Pitercell Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
  <script src="../js/sidebug.js"></script>
<?php renderSidebar('clientes'); ?>
<div class="main">
  <div class="topbar">
    <span class="topbar-title">Clientes</span>
    <div class="topbar-right"><span class="topbar-badge">Pitercell Admin</span></div>
  </div>
  <div class="page">
    <div class="page-header">
      <h1><em>Clientes</em> Cadastrados</h1>
      <button class="btn btn-primary" onclick="document.getElementById('modalCliente').classList.remove('hidden')">
        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Novo Cliente
      </button>
    </div>

    <div class="table-wrap">
      <div class="table-head">
        <h3>Lista de Clientes</h3>
        <form method="GET" style="display:flex;gap:8px">
          <div class="table-search">
            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" placeholder="Buscar por nome ou telefone..." value="<?= htmlspecialchars($busca) ?>">
          </div>
          <button type="submit" class="btn btn-ghost btn-sm">Buscar</button>
        </form>
      </div>
      <table>
        <thead><tr><th>Nome</th><th>Telefone</th><th>E-mail</th><th>OS</th><th>Cadastro</th><th>Ações</th></tr></thead>
        <tbody>
        <?php if (empty($clientes)): ?>
          <tr><td colspan="6"><div class="empty-state"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg><p>Nenhum cliente encontrado</p></div></td></tr>
        <?php else: ?>
          <?php foreach($clientes as $c): ?>
          <tr>
            <td><div class="td-name"><?= htmlspecialchars($c['nome']) ?></div></td>
            <td><?= htmlspecialchars($c['telefone']) ?></td>
            <td><?= htmlspecialchars($c['email'] ?: '—') ?></td>
            <td><span class="status-badge status-repair"><?= $c['total_os'] ?> OS</span></td>
            <td><?= date('d/m/Y', strtotime($c['criado_em'])) ?></td>
            <td style="display:flex;gap:6px">
              <a href="historico.php?cliente=<?= $c['id'] ?>" class="btn btn-ghost btn-sm">Histórico</a>
              <a href="?editar=<?= $c['id'] ?>" class="btn btn-ghost btn-sm">Editar</a>
              <a href="?excluir=<?= $c['id'] ?>" class="btn btn-red btn-sm" onclick="return confirm('Excluir cliente?')">Excluir</a>
            </td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Novo/Editar Cliente -->
<div class="modal-overlay <?= $editCliente ? '' : 'hidden' ?>" id="modalCliente">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('modalCliente').classList.add('hidden')">✕</button>
    <h2><?= $editCliente ? 'Editar' : 'Novo' ?> <em>Cliente</em></h2>
    <form method="POST">
      <input type="hidden" name="acao" value="salvar_cliente">
      <input type="hidden" name="id" value="<?= $editCliente['id'] ?? '' ?>">
      <div class="form-grid">
        <div class="form-group form-full">
          <label>Nome completo</label>
          <input type="text" name="nome" required placeholder="Ex: João Silva" value="<?= htmlspecialchars($editCliente['nome'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>WhatsApp</label>
          <input type="tel" name="telefone" required placeholder="(11) 99999-9999" value="<?= htmlspecialchars($editCliente['telefone'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>CPF</label>
          <input type="text" name="cpf" placeholder="000.000.000-00" value="<?= htmlspecialchars($editCliente['cpf'] ?? '') ?>">
        </div>
        <div class="form-group form-full">
          <label>E-mail</label>
          <input type="email" name="email" placeholder="cliente@email.com" value="<?= htmlspecialchars($editCliente['email'] ?? '') ?>">
        </div>
        <div class="form-group form-full">
          <label>Endereço</label>
          <input type="text" name="endereco" placeholder="Rua, número, bairro" value="<?= htmlspecialchars($editCliente['endereco'] ?? '') ?>">
        </div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('modalCliente').classList.add('hidden')">Cancelar</button>
        <button type="submit" class="btn btn-primary">
          <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          Salvar
        </button>
      </div>
    </form>
  </div>
</div>
<script>document.addEventListener('keydown',e=>{if(e.key==='Escape')document.getElementById('modalCliente').classList.add('hidden')});</script>
<script src="../js/sidebar.js"></script>
</body>
</html>
