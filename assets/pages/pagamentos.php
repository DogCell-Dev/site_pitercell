<?php
require_once '../includes/config.php';
require_once '../includes/layout.php';
requireLogin();
$db = getDB();

// Registrar pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'registrar_pagamento') {
    $osId  = intval($_POST['os_id']);
    $valor = floatval($_POST['valor_pago']);
    $forma = $_POST['forma_pagamento'];

    $r = $db->prepare("SELECT status FROM ordens WHERE id=?");
    $r->execute([$osId]);
    $ant = $r->fetchColumn();

    $db->prepare("UPDATE ordens SET valor_final=:v, status='entregue', atualizado_em=NOW() WHERE id=:id")
       ->execute([':v' => $valor, ':id' => $osId]);

    $db->prepare("INSERT INTO historico (ordem_id, status_ant, status_novo, nota) VALUES (?,?,?,?)")
       ->execute([$osId, $ant, 'entregue', "Pagamento registrado: R$".number_format($valor,2,',','.')." via $forma"]);

    header('Location: pagamentos.php?tab=recebidos'); exit;
}

$tab = $_GET['tab'] ?? 'pendentes';

// Stats
$stats = $db->query("
    SELECT
        COUNT(CASE WHEN status='pronto' THEN 1 END)    AS qtd_pendentes,
        COALESCE(SUM(CASE WHEN status='pronto'
            THEN COALESCE(valor_orcamento,0) END),0)   AS total_pendente,
        COUNT(CASE WHEN status='entregue' THEN 1 END)  AS qtd_recebidos,
        COALESCE(SUM(CASE WHEN status='entregue'
            THEN COALESCE(valor_final,valor_orcamento,0) END),0) AS total_recebido,
        COALESCE(SUM(CASE WHEN status NOT IN ('entregue','cancelado')
            THEN COALESCE(valor_orcamento,0) END),0)   AS total_em_aberto
    FROM ordens
")->fetch();

// ── Helper: monta nome do dispositivo de forma segura ────────
// Suporta banco antigo (coluna 'dispositivo') e banco novo (JOIN dispositivos_tipos)
function nomeDispositivo(array $o): string {
    // banco novo: dt_nome vem do JOIN
    $tipo  = $o['dt_nome']      ?? $o['dispositivo'] ?? '';
    $modelo = $o['modelo']      ?? '';
    return trim(htmlspecialchars($tipo . ' ' . $modelo));
}

// ── Query base com JOIN compatível ───────────────────────────
// LEFT JOIN garante que funciona mesmo sem dispositivo_id preenchido
// COALESCE prioriza o nome da tabela nova, cai no campo legado se existir
$SELECT_DISPOSITIVO = "
    LEFT JOIN dispositivos_tipos dt ON dt.id = o.dispositivo_id
";

// Pendentes
$pendentes = $db->query("
    SELECT o.*, c.nome AS cliente_nome, c.telefone,
           dt.nome AS dt_nome
    FROM ordens o
    JOIN clientes c ON c.id = o.cliente_id
    $SELECT_DISPOSITIVO
    WHERE o.status = 'pronto'
    ORDER BY o.atualizado_em ASC
")->fetchAll();

// Recebidos
$recebidos = $db->query("
    SELECT o.*, c.nome AS cliente_nome, c.telefone,
           dt.nome AS dt_nome,
           h.nota AS pagamento_info, h.criado_em AS pago_em
    FROM ordens o
    JOIN clientes c ON c.id = o.cliente_id
    $SELECT_DISPOSITIVO
    LEFT JOIN historico h ON h.ordem_id = o.id AND h.status_novo = 'entregue'
    WHERE o.status = 'entregue'
    ORDER BY o.atualizado_em DESC
    LIMIT 50
")->fetchAll();

// Em aberto
$emAberto = $db->query("
    SELECT o.*, c.nome AS cliente_nome, c.telefone,
           dt.nome AS dt_nome
    FROM ordens o
    JOIN clientes c ON c.id = o.cliente_id
    $SELECT_DISPOSITIVO
    WHERE o.status NOT IN ('entregue','cancelado')
    ORDER BY o.criado_em DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Pagamentos — Pitercell Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
  <style>
    .tabs { display:flex; gap:4px; margin-bottom:24px; background:rgba(255,255,255,0.03); border:1px solid var(--border); border-radius:12px; padding:4px; width:fit-content; }
    .tab-btn { padding:8px 20px; border-radius:9px; font-size:13px; font-weight:600; cursor:pointer; border:none; background:transparent; color:var(--muted); font-family:inherit; transition:all 0.2s; }
    .tab-btn.active { background:rgba(184,79,255,0.15); color:var(--accent); border:1px solid rgba(184,79,255,0.25); }
    .tab-btn:hover:not(.active) { color:var(--text); background:rgba(255,255,255,0.05); }
    .tab-content { display:none; }
    .tab-content.active { display:block; }
    .forma-badge { display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:6px; font-size:11px; font-weight:700; }
    .forma-pix      { color:#00c864; background:rgba(0,200,100,0.1);  border:1px solid rgba(0,200,100,0.2); }
    .forma-dinheiro { color:#f5a623; background:rgba(245,166,35,0.1); border:1px solid rgba(245,166,35,0.2); }
    .forma-cartao   { color:#3b82f6; background:rgba(59,130,246,0.1); border:1px solid rgba(59,130,246,0.2); }
    .alert-row { background:rgba(245,166,35,0.06); }
    .valor-destaque { font-family:'Orbitron',sans-serif; font-size:13px; font-weight:700; color:var(--accent); }
    .valor-green    { font-family:'Orbitron',sans-serif; font-size:13px; font-weight:700; color:var(--green); }
  </style>
</head>
<body>
<?php renderSidebar('pagamentos'); ?>
<div class="main">
  <div class="topbar">
    <span class="topbar-title">Pagamentos</span>
    <div class="topbar-right"><span class="topbar-badge">Pitercell Admin</span>
    <a href="logout.php" class="nav-sair">Sair</a>
    </div>
  </div>
  <div class="page">
    <div class="page-header">
      <h1>Controle de <em>Pagamentos</em></h1>
    </div>

    <!-- Cards resumo -->
    <div class="cards-grid" style="margin-bottom:28px">
      <div class="stat-card" style="--card-color:#f5a623">
        <div class="stat-card-top">
          <div class="stat-card-icon" style="background:rgba(245,166,35,0.1);border-color:rgba(245,166,35,0.2)">
            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          </div>
        </div>
        <div class="stat-card-num" style="font-size:20px">R$<?= number_format($stats['total_pendente'],2,',','.') ?></div>
        <div class="stat-card-label"><?= $stats['qtd_pendentes'] ?> OS prontas aguardando pagamento</div>
      </div>
      <div class="stat-card" style="--card-color:#b84fff">
        <div class="stat-card-top">
          <div class="stat-card-icon">
            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="2"/></svg>
          </div>
        </div>
        <div class="stat-card-num" style="font-size:20px">R$<?= number_format($stats['total_em_aberto'],2,',','.') ?></div>
        <div class="stat-card-label"><?= count($emAberto) ?> OS em andamento</div>
      </div>
      <div class="stat-card" style="--card-color:#00c864">
        <div class="stat-card-top">
          <div class="stat-card-icon" style="background:rgba(0,200,100,0.1);border-color:rgba(0,200,100,0.2)">
            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
          </div>
        </div>
        <div class="stat-card-num" style="font-size:20px">R$<?= number_format($stats['total_recebido'],2,',','.') ?></div>
        <div class="stat-card-label"><?= $stats['qtd_recebidos'] ?> OS entregues e pagas</div>
      </div>
      <div class="stat-card" style="--card-color:#3b82f6">
        <div class="stat-card-top">
          <div class="stat-card-icon" style="background:rgba(59,130,246,0.1);border-color:rgba(59,130,246,0.2)">
            <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          </div>
        </div>
        <div class="stat-card-num" style="font-size:20px">R$<?= number_format($stats['total_recebido'] + $stats['total_pendente'],2,',','.') ?></div>
        <div class="stat-card-label">Total geral (recebido + a receber)</div>
      </div>
    </div>

    <!-- Tabs -->
    <div class="tabs">
      <button class="tab-btn <?= $tab==='pendentes'?'active':'' ?>" onclick="setTab('pendentes',event)">
        🟡 Aguardando Pagamento (<?= count($pendentes) ?>)
      </button>
      <button class="tab-btn <?= $tab==='aberto'?'active':'' ?>" onclick="setTab('aberto',event)">
        🟣 Em Aberto (<?= count($emAberto) ?>)
      </button>
      <button class="tab-btn <?= $tab==='recebidos'?'active':'' ?>" onclick="setTab('recebidos',event)">
        🟢 Recebidos (<?= $stats['qtd_recebidos'] ?>)
      </button>
    </div>

    <!-- PENDENTES -->
    <div class="tab-content <?= $tab==='pendentes'?'active':'' ?>" id="tab-pendentes">
      <div class="table-wrap">
        <div class="table-head">
          <h3>Prontos — Aguardando Pagamento</h3>
        </div>
        <table>
          <thead><tr><th>Nº OS</th><th>Cliente</th><th>Dispositivo</th><th>Serviço</th><th>Valor</th><th>Pronto desde</th><th>Ações</th></tr></thead>
          <tbody>
          <?php if (empty($pendentes)): ?>
            <tr><td colspan="7"><div class="empty-state">
              <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
              <p>Nenhuma OS aguardando pagamento 🎉</p>
            </div></td></tr>
          <?php else: ?>
            <?php foreach ($pendentes as $o): ?>
            <tr class="alert-row">
              <td><strong><?= htmlspecialchars($o['numero']) ?></strong></td>
              <td>
                <div class="td-name"><?= htmlspecialchars($o['cliente_nome']) ?></div>
                <div class="td-sub"><?= htmlspecialchars($o['telefone']) ?></div>
              </td>
              <td><?= nomeDispositivo($o) ?></td>
              <td><?= htmlspecialchars($o['servico'] ?? '—') ?></td>
              <td><span class="valor-destaque">R$<?= number_format($o['valor_orcamento'] ?? 0, 2, ',', '.') ?></span></td>
              <td><?= date('d/m/Y H:i', strtotime($o['atualizado_em'])) ?></td>
              <td style="display:flex;gap:6px;flex-wrap:wrap">
                <button class="btn btn-green btn-sm"
                  onclick="abrirPagamento(<?= $o['id'] ?>, '<?= htmlspecialchars($o['numero'], ENT_QUOTES) ?>', '<?= htmlspecialchars($o['cliente_nome'], ENT_QUOTES) ?>', <?= $o['valor_orcamento'] ?? 0 ?>)">
                  <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                  Registrar Pagamento
                </button>
                <?php
                  $disp  = trim(($o['dt_nome'] ?? $o['dispositivo'] ?? '') . ' ' . ($o['modelo'] ?? ''));
                  $waMSG = urlencode("Olá {$o['cliente_nome']}! 👋\n\nSeu celular *{$disp}* está pronto para retirada! ✅\n\n💰 Valor: R$".number_format($o['valor_orcamento']??0,2,',','.').".\n\nAguardamos você na loja!\n\n_Pitercell — Seu celular novo de novo_ 🔧");
                ?>
                <a href="https://wa.me/<?= $o['telefone'] ?>?text=<?= $waMSG ?>" target="_blank" class="btn btn-ghost btn-sm">
                  <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.41 2 2 0 0 1 3.6 1.21h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                  Avisar
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- EM ABERTO -->
    <div class="tab-content <?= $tab==='aberto'?'active':'' ?>" id="tab-aberto">
      <div class="table-wrap">
        <div class="table-head"><h3>Ordens em Andamento</h3></div>
        <table>
          <thead><tr><th>Nº OS</th><th>Cliente</th><th>Dispositivo</th><th>Status</th><th>Orçamento</th><th>Data</th><th></th></tr></thead>
          <tbody>
          <?php if (empty($emAberto)): ?>
            <tr><td colspan="7"><div class="empty-state"><p>Nenhuma OS em aberto</p></div></td></tr>
          <?php else: ?>
            <?php foreach ($emAberto as $o): ?>
            <tr>
              <td><strong><?= htmlspecialchars($o['numero']) ?></strong></td>
              <td>
                <div class="td-name"><?= htmlspecialchars($o['cliente_nome']) ?></div>
                <div class="td-sub"><?= htmlspecialchars($o['telefone']) ?></div>
              </td>
              <td><?= nomeDispositivo($o) ?></td>
              <td><span class="status-badge <?= statusClass($o['status']) ?>"><?= statusLabel($o['status']) ?></span></td>
              <td><span class="valor-destaque"><?= $o['valor_orcamento'] ? 'R$'.number_format($o['valor_orcamento'],2,',','.') : '—' ?></span></td>
              <td><?= date('d/m/Y', strtotime($o['criado_em'])) ?></td>
              <td><a href="ordens.php?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">Ver OS</a></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- RECEBIDOS -->
    <div class="tab-content <?= $tab==='recebidos'?'active':'' ?>" id="tab-recebidos">
      <div class="table-wrap">
        <div class="table-head"><h3>Pagamentos Recebidos</h3></div>
        <table>
          <thead><tr><th>Nº OS</th><th>Cliente</th><th>Dispositivo</th><th>Forma</th><th>Valor Recebido</th><th>Pago em</th><th></th></tr></thead>
          <tbody>
          <?php if (empty($recebidos)): ?>
            <tr><td colspan="7"><div class="empty-state"><p>Nenhum pagamento registrado ainda</p></div></td></tr>
          <?php else: ?>
            <?php foreach ($recebidos as $o):
              $forma = '—'; $formaClass = '';
              if (preg_match('/via (PIX|Dinheiro|Cart[aã]o[\w\s]*)/i', $o['pagamento_info'] ?? '', $m)) {
                  $forma = $m[1];
                  $formaClass = match(true) {
                      stripos($forma,'pix')     !== false => 'forma-pix',
                      stripos($forma,'dinheiro')!== false => 'forma-dinheiro',
                      default                             => 'forma-cartao'
                  };
              }
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($o['numero']) ?></strong></td>
              <td>
                <div class="td-name"><?= htmlspecialchars($o['cliente_nome']) ?></div>
                <div class="td-sub"><?= htmlspecialchars($o['telefone']) ?></div>
              </td>
              <td><?= nomeDispositivo($o) ?></td>
              <td><?= $forma !== '—' ? '<span class="forma-badge '.$formaClass.'">'.htmlspecialchars($forma).'</span>' : '—' ?></td>
              <td><span class="valor-green">R$<?= number_format($o['valor_final'] ?? $o['valor_orcamento'] ?? 0, 2, ',', '.') ?></span></td>
              <td><?= $o['pago_em'] ? date('d/m/Y H:i', strtotime($o['pago_em'])) : date('d/m/Y', strtotime($o['atualizado_em'])) ?></td>
              <td><a href="ordens.php?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">Ver OS</a></td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>
</div>

<!-- Modal Registrar Pagamento -->
<div class="modal-overlay hidden" id="modalPagamento">
  <div class="modal" style="max-width:440px">
    <button class="modal-close" onclick="fecharPagamento()">✕</button>
    <h2>Registrar <em>Pagamento</em></h2>
    <form method="POST" id="formPagamento">
      <input type="hidden" name="acao" value="registrar_pagamento">
      <input type="hidden" name="os_id" id="pagOsId">
      <div style="background:rgba(184,79,255,0.07);border:1px solid rgba(184,79,255,0.2);border-radius:10px;padding:14px 16px;margin-bottom:20px">
        <div style="font-size:12px;color:var(--muted);margin-bottom:4px">OS / Cliente</div>
        <div style="font-size:15px;font-weight:600" id="pagInfo">—</div>
      </div>
      <div class="form-group" style="margin-bottom:14px">
        <label>Valor recebido (R$)</label>
        <input type="number" step="0.01" name="valor_pago" id="pagValor" required placeholder="0,00">
      </div>
      <div class="form-group" style="margin-bottom:20px">
        <label>Forma de pagamento</label>
        <select name="forma_pagamento" required>
          <option value="">Selecione...</option>
          <option value="PIX">PIX</option>
          <option value="Dinheiro">Dinheiro</option>
          <option value="Cartão Débito">Cartão Débito</option>
          <option value="Cartão Crédito">Cartão Crédito</option>
        </select>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="fecharPagamento()">Cancelar</button>
        <button type="submit" class="btn btn-primary">
          <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
          Confirmar Pagamento
        </button>
      </div>
    </form>
  </div>
</div>
<nav class="bottom-nav">
  <a href="tecnico.php" class="bottom-nav-item">
    <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/>
    </svg>
    Início
  </a>
  <a href="ordens.php" class="bottom-nav-item">
    <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/>
      <rect x="9" y="3" width="6" height="4" rx="2"/>
    </svg>
    OS
  </a>
  <a href="clientes.php" class="bottom-nav-item">
    <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
      <circle cx="9" cy="7" r="4"/>
    </svg>
    Clientes
  </a>
  <a href="pagamentos.php" class="bottom-nav-item">
    <svg viewBox="0 0 24 24" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
      <line x1="12" y1="1" x2="12" y2="23"/>
      <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
    </svg>
    Pagamentos
  </a>
</nav>

<script src="../js/sidebar.js"></script>
<script>
function setTab(t, e) {
  document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
  document.getElementById('tab-' + t).classList.add('active');
  if (e && e.currentTarget) e.currentTarget.classList.add('active');
  history.replaceState(null, '', '?tab=' + t);
}

function abrirPagamento(osId, numero, cliente, valor) {
  document.getElementById('pagOsId').value   = osId;
  document.getElementById('pagInfo').textContent = numero + ' — ' + cliente;
  document.getElementById('pagValor').value  = valor > 0 ? valor : '';
  document.getElementById('modalPagamento').classList.remove('hidden');
}
function fecharPagamento() {
  document.getElementById('modalPagamento').classList.add('hidden');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') fecharPagamento(); });
document.getElementById('modalPagamento').addEventListener('click', e => {
  if (e.target === document.getElementById('modalPagamento')) fecharPagamento();
});
</script>
</body>
</html>