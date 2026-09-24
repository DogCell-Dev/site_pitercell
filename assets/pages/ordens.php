<?php
require_once '../includes/config.php';
require_once '../includes/layout.php';
requireLogin();
$db = getDB();
 
// ── Helpers de status (caso não existam no layout.php) ────────
if (!function_exists('statusLabel')) {
    function statusLabel(string $s): string {
        return match($s) {
            'aguardando' => 'Aguardando',
            'em_analise' => 'Em Análise',
            'em_reparo'  => 'Em Reparo',
            'pronto'     => 'Pronto',
            'entregue'   => 'Entregue',
            'cancelado'  => 'Cancelado',
            default      => ucfirst($s),
        };
    }
}
 
// ── POST ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
 
    // ── Salvar OS (novo ou editar) ────────────────────────────
    if ($acao === 'salvar_os') {
        $id     = intval($_POST['id'] ?? 0);
        $dispId = $_POST['dispositivo_id'] ?: null;
        if ($id) {
            $db->prepare("UPDATE ordens SET cliente_id=:cid, dispositivo_id=:did, modelo=:modelo, sistema=:sis, problema=:prob, servico=:serv, valor_orcamento=:vorc, valor_final=:vfin, status=:status, observacoes=:obs WHERE id=:id")
               ->execute([
                   ':cid'  => intval($_POST['cliente_id']), ':did' => $dispId,
                   ':modelo'=> $_POST['modelo'],            ':sis' => $_POST['sistema'],
                   ':prob'  => $_POST['problema'],          ':serv'=> $_POST['servico'],
                   ':vorc'  => $_POST['valor_orcamento'] ?: null,
                   ':vfin'  => $_POST['valor_final']     ?: null,
                   ':status'=> $_POST['status'],
                   ':obs'   => $_POST['observacoes'],        ':id'  => $id,
               ]);
        } else {
            $ultimo = $db->query("SELECT MAX(id) as m FROM ordens")->fetchColumn();
            $numero = 'OS-' . str_pad(($ultimo + 1), 4, '0', STR_PAD_LEFT);
            $db->prepare("INSERT INTO ordens (cliente_id,numero,dispositivo_id,modelo,sistema,problema,servico,valor_orcamento,status,observacoes) VALUES (:cid,:num,:did,:modelo,:sis,:prob,:serv,:vorc,:status,:obs)")
               ->execute([
                   ':cid'   => intval($_POST['cliente_id']), ':num'  => $numero,
                   ':did'   => $dispId,                      ':modelo'=> $_POST['modelo'],
                   ':sis'   => $_POST['sistema'],            ':prob' => $_POST['problema'],
                   ':serv'  => $_POST['servico'],            ':vorc' => $_POST['valor_orcamento'] ?: null,
                   ':status'=> 'aguardando',                 ':obs'  => $_POST['observacoes'],
               ]);
        }
        header('Location: ordens.php'); exit;
    }
 
    // ── Atualizar status (form antigo mantido) ────────────────
    if ($acao === 'atualizar_status') {
        $osId = intval($_POST['os_id']);
        $r = $db->prepare("SELECT status FROM ordens WHERE id=?");
        $r->execute([$osId]);
        $ant = $r->fetchColumn();
        $db->prepare("UPDATE ordens SET status=? WHERE id=?")->execute([$_POST['status_novo'], $osId]);
        $db->prepare("INSERT INTO historico (ordem_id,status_ant,status_novo,nota) VALUES (?,?,?,?)")
           ->execute([$osId, $ant, $_POST['status_novo'], trim($_POST['nota'] ?? '')]);
        header('Location: ordens.php?id=' . $osId); exit;
    }
 
    // ── Chat: enviar mensagem ao cliente ──────────────────────
    if ($acao === 'msg_admin') {
        $osId  = intval($_POST['os_id']);
        $texto = trim($_POST['msg_admin'] ?? '');
        if ($osId && $texto) {
            $db->prepare("INSERT INTO mensagens (ordem_id, remetente, texto) VALUES (?, 'admin', ?)")
               ->execute([$osId, $texto]);
        }
        header('Location: ordens.php?id=' . $osId . '#chat'); exit;
    }
 
    // ── Enviar orçamento ao cliente ───────────────────────────
    if ($acao === 'enviar_orcamento') {
        $osId = intval($_POST['os_id']);
        $val  = (float) str_replace(',', '.', $_POST['valor_orcamento'] ?? '0');
        if ($osId && $val > 0) {
            $r = $db->prepare("SELECT status FROM ordens WHERE id=?");
            $r->execute([$osId]);
            $ant = $r->fetchColumn();
 
            $db->prepare("UPDATE ordens SET valor_orcamento=?, status='em_analise', atualizado_em=NOW() WHERE id=?")
               ->execute([$val, $osId]);
            $db->prepare("INSERT INTO historico (ordem_id,status_ant,status_novo,nota) VALUES (?,?,?,?)")
               ->execute([$osId, $ant, 'em_analise', 'Orçamento enviado: R$ ' . number_format($val, 2, ',', '.')]);
            $db->prepare("INSERT INTO mensagens (ordem_id, remetente, texto) VALUES (?, 'admin', ?)")
               ->execute([$osId, '💰 Orçamento disponível: R$ ' . number_format($val, 2, ',', '.') . '. Acesse seu painel para aceitar.']);
        }
        header('Location: ordens.php?id=' . $osId . '#chat'); exit;
    }
 
    // ── Atualizar status via addon ────────────────────────────
    if ($acao === 'atualizar_status_addon') {
        $osId = intval($_POST['os_id']);
        $novo = $_POST['novo_status'] ?? '';
        $nota = trim($_POST['nota_status'] ?? '');
        if ($osId && $novo) {
            $r = $db->prepare("SELECT status FROM ordens WHERE id=?");
            $r->execute([$osId]);
            $ant = $r->fetchColumn();
 
            $db->prepare("UPDATE ordens SET status=?, atualizado_em=NOW() WHERE id=?")->execute([$novo, $osId]);
            $db->prepare("INSERT INTO historico (ordem_id,status_ant,status_novo,nota) VALUES (?,?,?,?)")
               ->execute([$osId, $ant, $novo, $nota ?: 'Status atualizado pelo admin.']);
            $db->prepare("INSERT INTO mensagens (ordem_id, remetente, texto) VALUES (?, 'admin', ?)")
               ->execute([$osId, '🔄 Status atualizado: ' . statusLabel($novo) . ($nota ? ' — ' . $nota : '')]);
        }
        header('Location: ordens.php?id=' . $osId . '#chat'); exit;
    }
 
    // ── Atualizar delivery ────────────────────────────────────
    if ($acao === 'atualizar_delivery') {
        $osId     = intval($_POST['os_id']);
        $ds       = $_POST['delivery_status'] ?? '';
        $obsAdmin = trim($_POST['obs_admin_delivery'] ?? '');
        if ($osId && $ds) {
            $db->prepare("UPDATE delivery_pedidos SET status=?, obs_admin=? WHERE ordem_id=?")
               ->execute([$ds, $obsAdmin ?: null, $osId]);
 
            // Mensagem automática ao cliente conforme status
            $msgDelivery = match($ds) {
                'confirmado' => '✅ Sua solicitação de delivery foi *confirmada*!' . ($obsAdmin ? ' — ' . $obsAdmin : ''),
                'a_caminho'  => '🚚 Estamos *a caminho*! Aguarde.' . ($obsAdmin ? ' — ' . $obsAdmin : ''),
                'concluido'  => '🎉 Delivery *concluído* com sucesso! Obrigado pela preferência.',
                'cancelado'  => '❌ Infelizmente o delivery foi *cancelado*.' . ($obsAdmin ? ' Motivo: ' . $obsAdmin : ''),
                default      => null,
            };
            if ($msgDelivery) {
                $db->prepare("INSERT INTO mensagens (ordem_id, remetente, texto) VALUES (?, 'admin', ?)")
                   ->execute([$osId, $msgDelivery]);
            }
        }
        header('Location: ordens.php?id=' . $osId . '#chat'); exit;
    }
}
 
// ── Detalhe da OS ────────────────────────────────────────────
$detalhe = null;
if (isset($_GET['id'])) {
    $stmt = $db->prepare("
        SELECT o.*, c.nome as cliente_nome, c.telefone, c.email,
               COALESCE(dt.nome,'') as dispositivo_nome
        FROM ordens o
        JOIN clientes c ON c.id = o.cliente_id
        LEFT JOIN dispositivos_tipos dt ON dt.id = o.dispositivo_id
        WHERE o.id=?
    ");
    $stmt->execute([intval($_GET['id'])]);
    $detalhe = $stmt->fetch();
 
    if ($detalhe) {
        $historico = $db->prepare("SELECT * FROM historico WHERE ordem_id=? ORDER BY criado_em DESC");
        $historico->execute([$detalhe['id']]);
        $historico = $historico->fetchAll();
 
        // Mensagens (marca do cliente como lidas)
        $db->prepare("UPDATE mensagens SET lida=1 WHERE ordem_id=? AND remetente='cliente'")
           ->execute([$detalhe['id']]);
        $stMsg = $db->prepare("SELECT * FROM mensagens WHERE ordem_id=? ORDER BY criado_em ASC");
        $stMsg->execute([$detalhe['id']]);
        $mensagens = $stMsg->fetchAll();
 
        // Delivery
        $stDel = $db->prepare("
            SELECT dp.*, dz.bairro, dz.distancia_km,
                   ce.logradouro, ce.numero AS end_num, ce.complemento, ce.cidade
            FROM delivery_pedidos dp
            LEFT JOIN delivery_zonas dz    ON dz.id = dp.zona_id
            LEFT JOIN cliente_enderecos ce ON ce.id = dp.endereco_id
            WHERE dp.ordem_id=?
        ");
        $stDel->execute([$detalhe['id']]);
        $delivery = $stDel->fetch();
 
        $naoLidas = count(array_filter($mensagens, fn($m) => $m['remetente'] === 'cliente' && !$m['lida']));
    }
}
 
// ── Lista de OS ──────────────────────────────────────────────
$busca        = trim($_GET['q'] ?? '');
$filtroStatus = $_GET['status'] ?? '';
$sql = "SELECT o.*, c.nome as cliente_nome, COALESCE(dt.nome,'') as dispositivo_nome
        FROM ordens o
        JOIN clientes c ON c.id = o.cliente_id
        LEFT JOIN dispositivos_tipos dt ON dt.id = o.dispositivo_id
        WHERE 1=1";
$params = [];
if ($busca)        { $sql .= " AND (o.numero LIKE :q OR c.nome LIKE :q OR o.modelo LIKE :q)"; $params[':q'] = "%$busca%"; }
if ($filtroStatus) { $sql .= " AND o.status=:st"; $params[':st'] = $filtroStatus; }
$sql .= " ORDER BY o.criado_em DESC";
$stmt = $db->prepare($sql); $stmt->execute($params); $ordens = $stmt->fetchAll();
 
$todoClientes     = $db->query("SELECT id,nome FROM clientes ORDER BY nome")->fetchAll();
$todoDispositivos = $db->query("SELECT id,nome FROM dispositivos_tipos ORDER BY nome")->fetchAll();
$novoModal = isset($_GET['novo']);
$editOS    = null;
if (isset($_GET['editar'])) {
    $s = $db->prepare("SELECT * FROM ordens WHERE id=?");
    $s->execute([intval($_GET['editar'])]);
    $editOS = $s->fetch();
}
$statusOpts = ['aguardando'=>'Aguardando','em_analise'=>'Em Análise','em_reparo'=>'Em Reparo','pronto'=>'Pronto','entregue'=>'Entregue','cancelado'=>'Cancelado'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Ordens de Serviço — Pitercell Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
  <script src="../js/sidebug.js"></script>
<?php renderSidebar('ordens'); ?>
<div class="main">
  <div class="topbar">
    <span class="topbar-title">Ordens de Serviço</span>
    <div class="topbar-right"><span class="topbar-badge">Pitercell Admin</span>
    <a href="logout.php" class="nav-sair">Sair</a>
    </div>
  </div>
  <div class="page">
 
  <?php if ($detalhe): ?>
  <!-- ════════════════════════════════════════════════════════
       DETALHE DA OS
  ════════════════════════════════════════════════════════ -->
    <div class="page-header">
      <h1><em><?= htmlspecialchars($detalhe['numero']) ?></em>
        <?php if ($naoLidas > 0): ?>
          <span style="font-size:13px;background:rgba(184,79,255,0.15);border:1px solid rgba(184,79,255,0.3);border-radius:99px;padding:3px 10px;font-weight:700;color:#b84fff;vertical-align:middle;margin-left:8px">
            💬 <?= $naoLidas ?> nova(s)
          </span>
        <?php endif; ?>
      </h1>
      <div style="display:flex;gap:8px">
        <a href="?editar=<?= $detalhe['id'] ?>" class="btn btn-ghost">Editar OS</a>
        <?php
          $dispStr = trim($detalhe['dispositivo_nome'] . ' ' . $detalhe['modelo']);
          $waMSG   = urlencode("*Pitercell — Atualização da sua OS*\n\nOlá {$detalhe['cliente_nome']}! 👋\n\nSua OS *{$detalhe['numero']}* ({$dispStr}) está com status: *" . statusLabel($detalhe['status']) . "*.\n\n" . ($detalhe['valor_orcamento'] ? "💰 Orçamento: R$" . number_format($detalhe['valor_orcamento'], 2, ',', '.') . ".\n" : "") . "Qualquer dúvida estamos à disposição!\n\n_Pitercell — Seu celular novo de novo_ 🔧");
        ?>
        <a href="https://wa.me/<?= preg_replace('/\D/', '', $detalhe['telefone']) ?>?text=<?= $waMSG ?>" target="_blank" class="btn btn-green">
          <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.41 2 2 0 0 1 3.6 1.21h3a2 2 0 0 1 2 1.72 19.86 19.86 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45 19.86 19.86 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          WhatsApp
        </a>
        <a href="ordens.php" class="btn btn-ghost">← Voltar</a>
      </div>
    </div>
 
    <!-- Grid info + histórico (seu layout original) -->
    <div class="detail-grid">
      <div>
        <div class="detail-card" style="margin-bottom:16px">
          <h3>Informações da OS</h3>
          <div class="detail-row"><span>Status</span><span><span class="status-badge <?= statusClass($detalhe['status']) ?>"><?= statusLabel($detalhe['status']) ?></span></span></div>
          <div class="detail-row"><span>Cliente</span><span><?= htmlspecialchars($detalhe['cliente_nome']) ?></span></div>
          <div class="detail-row"><span>Telefone</span><span><?= htmlspecialchars($detalhe['telefone']) ?></span></div>
          <div class="detail-row"><span>Dispositivo</span><span><?= htmlspecialchars(trim($detalhe['dispositivo_nome'] . ' ' . $detalhe['modelo'])) ?></span></div>
          <div class="detail-row"><span>Sistema</span><span><?= strtoupper($detalhe['sistema']) ?></span></div>
          <div class="detail-row"><span>Serviço</span><span><?= htmlspecialchars($detalhe['servico'] ?? '—') ?></span></div>
          <div class="detail-row"><span>Problema</span><span><?= htmlspecialchars($detalhe['problema']) ?></span></div>
          <div class="detail-row"><span>Orçamento</span><span style="color:#b84fff;font-weight:700"><?= $detalhe['valor_orcamento'] ? 'R$' . number_format($detalhe['valor_orcamento'], 2, ',', '.') : '—' ?></span></div>
          <div class="detail-row"><span>Valor Final</span><span style="color:#00c864;font-weight:700"><?= $detalhe['valor_final'] ? 'R$' . number_format($detalhe['valor_final'], 2, ',', '.') : '—' ?></span></div>
          <?php if ($detalhe['observacoes']): ?>
          <div class="detail-row"><span>Obs.</span><span><?= htmlspecialchars($detalhe['observacoes']) ?></span></div>
          <?php endif; ?>
          <div class="detail-row"><span>Criado em</span><span><?= date('d/m/Y H:i', strtotime($detalhe['criado_em'])) ?></span></div>
        </div>
 
        <!-- Atualizar status (form original mantido) -->
        <div class="detail-card">
          <h3>Atualizar Status</h3>
          <form method="POST" style="display:flex;flex-direction:column;gap:12px;margin-top:4px">
            <input type="hidden" name="acao" value="atualizar_status">
            <input type="hidden" name="os_id" value="<?= $detalhe['id'] ?>">
            <div class="form-group">
              <label>Novo status</label>
              <select name="status_novo">
                <?php foreach ($statusOpts as $v => $l): ?>
                  <option value="<?= $v ?>" <?= $detalhe['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Nota interna (opcional)</label>
              <textarea name="nota" placeholder="Ex: Aguardando peça do fornecedor..." style="min-height:60px"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-start">Atualizar</button>
          </form>
        </div>
      </div>
 
      <!-- Histórico -->
      <div class="detail-card">
        <h3>Histórico de Status</h3>
        <?php if (empty($historico)): ?>
          <div class="empty-state" style="padding:30px 0"><p>Nenhuma atualização ainda</p></div>
        <?php else: ?>
          <div class="history-list">
            <?php foreach ($historico as $h): ?>
            <div class="history-item">
              <div class="history-dot"></div>
              <div class="history-body">
                <p>
                  <span class="status-badge <?= statusClass($h['status_ant'] ?? '') ?>" style="font-size:10px"><?= statusLabel($h['status_ant'] ?? '') ?></span>
                  → <span class="status-badge <?= statusClass($h['status_novo']) ?>" style="font-size:10px"><?= statusLabel($h['status_novo']) ?></span>
                </p>
                <?php if ($h['nota']): ?><p style="margin-top:4px;font-size:12px;color:var(--muted)"><?= htmlspecialchars($h['nota']) ?></p><?php endif; ?>
                <span><?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
 
    <!-- ════════════════════════════════════════════════════
         CHAT + ORÇAMENTO + DELIVERY  (novo)
    ════════════════════════════════════════════════════ -->
    <div id="chat" style="display:flex;flex-direction:column;gap:20px;margin-top:24px">
 
      <!-- CHAT -->
      <div class="detail-card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
          <h3 style="margin:0">💬 Chat com o cliente</h3>
          <?php if ($naoLidas > 0): ?>
          <span style="background:rgba(184,79,255,0.15);border:1px solid rgba(184,79,255,0.3);border-radius:99px;padding:3px 10px;font-size:11px;font-weight:700;color:#b84fff">
            <?= $naoLidas ?> nova(s)
          </span>
          <?php endif; ?>
        </div>
 
        <!-- Balões de mensagem -->
        <div id="admin-chat-msgs"
             style="display:flex;flex-direction:column;gap:10px;max-height:380px;overflow-y:auto;padding:4px;margin-bottom:16px">
          <?php if (!$mensagens): ?>
            <p style="text-align:center;color:rgba(220,200,255,0.3);font-size:13px;padding:24px 0">Sem mensagens ainda.</p>
          <?php else: foreach ($mensagens as $m):
            $eAdmin = $m['remetente'] === 'admin'; ?>
            <div style="display:flex;flex-direction:column;align-items:<?= $eAdmin ? 'flex-end' : 'flex-start' ?>">
              <div style="max-width:75%;background:<?= $eAdmin ? 'rgba(184,79,255,0.15)' : 'rgba(255,255,255,0.05)' ?>;border:1px solid <?= $eAdmin ? 'rgba(184,79,255,0.25)' : 'rgba(255,255,255,0.08)' ?>;border-radius:<?= $eAdmin ? '14px 14px 2px 14px' : '14px 14px 14px 2px' ?>;padding:10px 14px;font-size:13px;color:#f0eeff;line-height:1.5">
                <?= nl2br(htmlspecialchars($m['texto'], ENT_QUOTES, 'UTF-8')) ?>
              </div>
              <span style="font-size:11px;color:rgba(220,200,255,0.3);margin-top:3px">
                <?= $eAdmin ? '🔧 Você (admin)' : '👤 Cliente' ?> · <?= date('d/m H:i', strtotime($m['criado_em'])) ?>
              </span>
            </div>
          <?php endforeach; endif; ?>
        </div>
 
        <!-- Input enviar mensagem -->
        <form method="POST" style="display:flex;gap:10px;align-items:flex-end">
          <input type="hidden" name="acao" value="msg_admin">
          <input type="hidden" name="os_id" value="<?= $detalhe['id'] ?>">
          <textarea name="msg_admin" placeholder="Responda ao cliente..." rows="2"
            style="flex:1;background:rgba(255,255,255,0.04);border:1px solid rgba(184,79,255,0.2);border-radius:10px;padding:10px 14px;color:var(--text);font-size:13px;font-family:inherit;outline:none;resize:none;min-height:44px"></textarea>
          <button type="submit" class="btn btn-primary" style="height:44px;padding:0 20px;white-space:nowrap">Enviar</button>
        </form>
      </div>
 
      <!-- ORÇAMENTO & STATUS ADDON -->
      <div class="detail-card">
        <h3 style="margin-bottom:20px">💰 Orçamento & Atualização rápida</h3>
 
        <!-- Enviar orçamento -->
        <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;padding-bottom:16px;border-bottom:1px solid rgba(255,255,255,0.06);margin-bottom:16px">
          <input type="hidden" name="acao" value="enviar_orcamento">
          <input type="hidden" name="os_id" value="<?= $detalhe['id'] ?>">
          <div style="flex:1;min-width:160px">
            <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.5px;color:var(--muted);text-transform:uppercase;margin-bottom:6px">Valor do orçamento (R$)</label>
            <input type="number" name="valor_orcamento" step="0.01" min="0" placeholder="Ex: 299.00"
                   value="<?= $detalhe['valor_orcamento'] ? number_format($detalhe['valor_orcamento'], 2, '.', '') : '' ?>"
                   style="background:rgba(255,255,255,0.04);border:1px solid rgba(16,185,129,0.3);border-radius:10px;padding:10px 14px;color:var(--text);font-size:14px;font-family:inherit;outline:none;width:100%"/>
          </div>
          <button type="submit" class="btn btn-green">✓ Enviar ao cliente</button>
        </form>
 
        <!-- Atualizar status rápido -->
        <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
          <input type="hidden" name="acao" value="atualizar_status_addon">
          <input type="hidden" name="os_id" value="<?= $detalhe['id'] ?>">
          <div style="flex:1;min-width:160px">
            <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.5px;color:var(--muted);text-transform:uppercase;margin-bottom:6px">Novo status</label>
            <select name="novo_status" style="background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:10px;padding:10px 14px;color:var(--text);font-size:13px;font-family:inherit;outline:none;width:100%">
              <option value="">— Selecionar —</option>
              <?php foreach ($statusOpts as $v => $l): ?>
                <option value="<?= $v ?>" <?= $detalhe['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="flex:2;min-width:200px">
            <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.5px;color:var(--muted);text-transform:uppercase;margin-bottom:6px">Nota p/ cliente (opcional)</label>
            <input type="text" name="nota_status" placeholder="Ex: Peça chegou, iniciando reparo..."
                   style="background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:10px;padding:10px 14px;color:var(--text);font-size:13px;font-family:inherit;outline:none;width:100%"/>
          </div>
          <button type="submit" class="btn btn-primary">Atualizar + Notificar</button>
        </form>
      </div>
 
      <!-- DELIVERY (só aparece se houver pedido) -->
      <?php if (!empty($delivery)): ?>
      <div class="detail-card" style="border-color:rgba(59,130,246,0.25)">
        <h3 style="margin-bottom:16px">🚚 Pedido de Delivery</h3>
 
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;font-size:13px;margin-bottom:16px">
          <div><span style="color:var(--muted);font-size:11px">TIPO</span><br><strong><?= $delivery['tipo'] === 'retirada' ? '📦 Retirada' : '🚚 Entrega' ?></strong></div>
          <div><span style="color:var(--muted);font-size:11px">BAIRRO</span><br><strong><?= htmlspecialchars($delivery['bairro'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
          <div><span style="color:var(--muted);font-size:11px">DISTÂNCIA</span><br><strong><?= $delivery['distancia_km'] ?> km</strong></div>
          <div><span style="color:var(--muted);font-size:11px">TAXA</span><br><strong style="color:#b84fff">R$ <?= number_format($delivery['taxa_cobrada'], 2, ',', '.') ?></strong></div>
          <?php if ($delivery['data_agendada']): ?>
          <div><span style="color:var(--muted);font-size:11px">DATA</span><br><strong><?= date('d/m/Y', strtotime($delivery['data_agendada'])) ?></strong></div>
          <?php endif; ?>
          <?php if ($delivery['hora_agendada']): ?>
          <div><span style="color:var(--muted);font-size:11px">HORA</span><br><strong><?= substr($delivery['hora_agendada'], 0, 5) ?></strong></div>
          <?php endif; ?>
        </div>
 
        <?php if ($delivery['logradouro']): ?>
        <div style="background:rgba(255,255,255,0.03);border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:10px">
          📍 <?= htmlspecialchars($delivery['logradouro'] . ', ' . ($delivery['end_num'] ?? '') . ($delivery['complemento'] ? ' — ' . $delivery['complemento'] : '') . ', ' . ($delivery['cidade'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>
 
        <?php if (!empty($delivery['obs_cliente'])): ?>
        <div style="background:rgba(255,255,255,0.03);border-radius:8px;padding:10px 14px;font-size:13px;color:var(--muted);margin-bottom:12px">
          💬 <strong>Cliente:</strong> <?= htmlspecialchars($delivery['obs_cliente'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>
 
        <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
          <input type="hidden" name="acao" value="atualizar_delivery">
          <input type="hidden" name="os_id" value="<?= $detalhe['id'] ?>">
          <div style="flex:1;min-width:160px">
            <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.5px;color:var(--muted);text-transform:uppercase;margin-bottom:6px">Status do delivery</label>
            <select name="delivery_status" style="background:rgba(255,255,255,0.04);border:1px solid rgba(59,130,246,0.3);border-radius:10px;padding:10px 14px;color:var(--text);font-size:13px;font-family:inherit;outline:none;width:100%">
              <?php foreach (['aguardando' => 'Aguardando', 'confirmado' => 'Confirmado', 'a_caminho' => 'A caminho', 'concluido' => 'Concluído', 'cancelado' => 'Cancelado'] as $v => $l): ?>
                <option value="<?= $v ?>" <?= ($delivery['status'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div style="flex:2;min-width:200px">
            <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.5px;color:var(--muted);text-transform:uppercase;margin-bottom:6px">Obs. para o cliente</label>
            <input type="text" name="obs_admin_delivery"
                   placeholder="Ex: Saindo às 14h, ligue antes..."
                   value="<?= htmlspecialchars($delivery['obs_admin'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                   style="background:rgba(255,255,255,0.04);border:1px solid rgba(59,130,246,0.2);border-radius:10px;padding:10px 14px;color:var(--text);font-size:13px;font-family:inherit;outline:none;width:100%"/>
          </div>
          <button type="submit" style="background:#3b82f6;color:#fff;border:none;border-radius:10px;padding:11px 22px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap">
            Salvar delivery
          </button>
        </form>
      </div>
      <?php endif; ?>
 
    </div><!-- #chat -->
 
  <?php else: ?>
  <!-- ════════════════════════════════════════════════════════
       LISTA DE OS
  ════════════════════════════════════════════════════════ -->
    <div class="page-header">
      <h1>Ordens de <em>Serviço</em></h1>
      <button class="btn btn-primary" onclick="document.getElementById('modalOS').classList.remove('hidden')">
        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nova OS
      </button>
    </div>
 
    <div class="table-wrap">
      <div class="table-head">
        <h3>Todas as Ordens</h3>
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
          <div class="table-search">
            <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <input type="text" name="q" placeholder="Buscar OS, cliente, modelo..." value="<?= htmlspecialchars($busca) ?>">
          </div>
          <select name="status" style="background:rgba(255,255,255,0.04);border:1px solid var(--border);border-radius:8px;padding:7px 12px;color:var(--text);font-family:inherit;font-size:13px;outline:none">
            <option value="">Todos os status</option>
            <?php foreach ($statusOpts as $v => $l): ?><option value="<?= $v ?>" <?= $filtroStatus === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-ghost btn-sm">Filtrar</button>
        </form>
      </div>
      <table>
        <thead><tr><th>Nº OS</th><th>Cliente</th><th>Dispositivo</th><th>Serviço</th><th>Status</th><th>Orçamento</th><th>Data</th><th>Ações</th></tr></thead>
        <tbody>
        <?php if (empty($ordens)): ?>
          <tr><td colspan="8"><div class="empty-state"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/></svg><p>Nenhuma OS encontrada</p></div></td></tr>
        <?php else: ?>
          <?php foreach ($ordens as $o): ?>
          <tr>
            <td><strong><?= htmlspecialchars($o['numero']) ?></strong></td>
            <td><div class="td-name"><?= htmlspecialchars($o['cliente_nome']) ?></div></td>
            <td><?= htmlspecialchars(trim($o['dispositivo_nome'] . ' ' . $o['modelo'])) ?></td>
            <td><?= htmlspecialchars($o['servico'] ?? '—') ?></td>
            <td><span class="status-badge <?= statusClass($o['status']) ?>"><?= statusLabel($o['status']) ?></span></td>
            <td><?= $o['valor_orcamento'] ? 'R$' . number_format($o['valor_orcamento'], 2, ',', '.') : '—' ?></td>
            <td><?= date('d/m/Y', strtotime($o['criado_em'])) ?></td>
            <td><a href="?id=<?= $o['id'] ?>" class="btn btn-ghost btn-sm">Abrir</a></td>
          </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
  </div>
</div>
 
<!-- Modal Nova/Editar OS -->
<div class="modal-overlay <?= ($novoModal || $editOS) ? '' : 'hidden' ?>" id="modalOS">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('modalOS').classList.add('hidden')">✕</button>
    <h2><?= $editOS ? 'Editar' : 'Nova' ?> <em>Ordem de Serviço</em></h2>
    <form method="POST">
      <input type="hidden" name="acao" value="salvar_os">
      <input type="hidden" name="id"   value="<?= $editOS['id'] ?? '' ?>">
      <div class="form-grid">
        <div class="form-group form-full">
          <label>Cliente</label>
          <select name="cliente_id" required>
            <option value="">Selecione o cliente...</option>
            <?php foreach ($todoClientes as $cl): ?>
              <option value="<?= $cl['id'] ?>" <?= ($editOS['cliente_id'] ?? '') == $cl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cl['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Dispositivo</label>
          <select name="dispositivo_id">
            <option value="">Selecione...</option>
            <?php foreach ($todoDispositivos as $d): ?>
              <option value="<?= $d['id'] ?>" <?= ($editOS['dispositivo_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Modelo</label>
          <input type="text" name="modelo" placeholder="Ex: 13 Pro, Galaxy A54" value="<?= htmlspecialchars($editOS['modelo'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Sistema</label>
          <select name="sistema">
            <option value="android" <?= ($editOS['sistema'] ?? '') === 'android' ? 'selected' : '' ?>>Android</option>
            <option value="ios"     <?= ($editOS['sistema'] ?? '') === 'ios'     ? 'selected' : '' ?>>iOS (Apple)</option>
            <option value="outro"   <?= ($editOS['sistema'] ?? '') === 'outro'   ? 'selected' : '' ?>>Outro</option>
          </select>
        </div>
        <div class="form-group">
          <label>Serviço</label>
          <input type="text" name="servico" placeholder="Ex: Troca de Tela" value="<?= htmlspecialchars($editOS['servico'] ?? '') ?>">
        </div>
        <div class="form-group form-full">
          <label>Problema relatado</label>
          <textarea name="problema" required placeholder="Descreva o problema..."><?= htmlspecialchars($editOS['problema'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label>Valor Orçamento (R$)</label>
          <input type="number" step="0.01" name="valor_orcamento" placeholder="0.00" value="<?= $editOS['valor_orcamento'] ?? '' ?>">
        </div>
        <div class="form-group">
          <label>Valor Final (R$)</label>
          <input type="number" step="0.01" name="valor_final" placeholder="0.00" value="<?= $editOS['valor_final'] ?? '' ?>">
        </div>
        <?php if ($editOS): ?>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <?php foreach ($statusOpts as $v => $l): ?><option value="<?= $v ?>" <?= $editOS['status'] === $v ? 'selected' : '' ?>><?= $l ?></option><?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="form-group form-full">
          <label>Observações internas</label>
          <textarea name="observacoes" placeholder="Notas internas..."><?= htmlspecialchars($editOS['observacoes'] ?? '') ?></textarea>
        </div>
      </div>
      <div class="form-actions">
        <button type="button" class="btn btn-ghost" onclick="document.getElementById('modalOS').classList.add('hidden')">Cancelar</button>
        <button type="submit" class="btn btn-primary">
          <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
          Salvar OS
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

<script>
document.addEventListener('keydown', e => {
  if (e.key === 'Escape') document.getElementById('modalOS').classList.add('hidden');
});
// Scroll chat para o fim
const chatMsgs = document.getElementById('admin-chat-msgs');
if (chatMsgs) chatMsgs.scrollTop = chatMsgs.scrollHeight;
</script>
<script src="../js/sidebar.js"></script>
</body>
</html>