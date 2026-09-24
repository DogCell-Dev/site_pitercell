<?php
// ── assets/pages/cliente/ordem_detalhe.php ──────────────────
require_once __DIR__ . '/../../includes/config.php';
requireCliente();
 
$cid     = $_SESSION['cliente_id'];
$ordemId = (int)($_GET['id'] ?? 0);
 
// ── POSTS ────────────────────────────────────────────────────
 
// Envio de mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensagem'])) {
    $texto = trim($_POST['mensagem'] ?? '');
    if ($texto && $ordemId) {
        db()->prepare("INSERT INTO mensagens (ordem_id, remetente, texto) VALUES (?, 'cliente', ?)")
            ->execute([$ordemId, $texto]);
    }
    redirect('ordem_detalhe.php?id=' . $ordemId . '#chat');
}
 
// Aceitar orçamento — funciona em 'aguardando' OU 'em_analise' (admin já enviou valor)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aceitar_orcamento'])) {
    if ($ordemId) {
        // Pega status atual para usar no histórico
        $stAtual = db()->prepare("SELECT status FROM ordens WHERE id = ? AND cliente_id = ?");
        $stAtual->execute([$ordemId, $cid]);
        $statusAtual = $stAtual->fetchColumn();
 
        if (in_array($statusAtual, ['aguardando', 'em_analise'])) {
            db()->prepare("UPDATE ordens SET status = 'em_reparo' WHERE id = ? AND cliente_id = ?")
                ->execute([$ordemId, $cid]);
            db()->prepare("INSERT INTO historico (ordem_id, status_ant, status_novo, nota, feito_por) VALUES (?,?,?,'Cliente aprovou o orçamento.','cliente')")
                ->execute([$ordemId, $statusAtual, 'em_reparo']);
            // Notifica admin via mensagem
            db()->prepare("INSERT INTO mensagens (ordem_id, remetente, texto) VALUES (?, 'cliente', ?)")
                ->execute([$ordemId, '✅ Orçamento aprovado! Pode iniciar o reparo.']);
        }
    }
    redirect('ordem_detalhe.php?id=' . $ordemId);
}
 
// Recusar orçamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['recusar_orcamento'])) {
    if ($ordemId) {
        $stAtual = db()->prepare("SELECT status FROM ordens WHERE id = ? AND cliente_id = ?");
        $stAtual->execute([$ordemId, $cid]);
        $statusAtual = $stAtual->fetchColumn();
 
        if (in_array($statusAtual, ['aguardando', 'em_analise'])) {
            db()->prepare("UPDATE ordens SET status = 'cancelado' WHERE id = ? AND cliente_id = ?")
                ->execute([$ordemId, $cid]);
            db()->prepare("INSERT INTO historico (ordem_id, status_ant, status_novo, nota, feito_por) VALUES (?,?,?,'Cliente recusou o orçamento.','cliente')")
                ->execute([$ordemId, $statusAtual, 'cancelado']);
            // Notifica admin
            db()->prepare("INSERT INTO mensagens (ordem_id, remetente, texto) VALUES (?, 'cliente', ?)")
                ->execute([$ordemId, '❌ Orçamento recusado pelo cliente.']);
        }
    }
    redirect('ordem_detalhe.php?id=' . $ordemId);
}
 
// ── Carrega dados ────────────────────────────────────────────
$st = db()->prepare("
    SELECT o.*, dt.nome AS dispositivo_nome
    FROM ordens o
    LEFT JOIN dispositivos_tipos dt ON dt.id = o.dispositivo_id
    WHERE o.id = ? AND o.cliente_id = ?
");
$st->execute([$ordemId, $cid]);
$ordem = $st->fetch();
 
$pageTitle  = $ordem ? 'OS ' . $ordem['numero'] : 'Ordem não encontrada';
$activePage = 'ordens';
require_once __DIR__ . '/../../includes/cliente_header.php';
 
if (!$ordem) {
    echo '<p class="erro">Ordem não encontrada.</p>';
    require_once __DIR__ . '/../../includes/cliente_footer.php';
    exit;
}
 
// Marca mensagens do admin como lidas
db()->prepare("UPDATE mensagens SET lida = 1 WHERE ordem_id = ? AND remetente = 'admin'")->execute([$ordemId]);
 
// Carrega mensagens
$stMsg = db()->prepare("SELECT * FROM mensagens WHERE ordem_id = ? ORDER BY criado_em ASC");
$stMsg->execute([$ordemId]);
$mensagens = $stMsg->fetchAll();
 
// Carrega histórico
$stHist = db()->prepare("SELECT * FROM historico WHERE ordem_id = ? ORDER BY criado_em ASC");
$stHist->execute([$ordemId]);
$historico = $stHist->fetchAll();
 
// Delivery vinculado
$stDel = db()->prepare("
    SELECT dp.*, dz.bairro, dz.distancia_km, dz.taxa AS taxa_zona
    FROM delivery_pedidos dp
    LEFT JOIN delivery_zonas dz ON dz.id = dp.zona_id
    WHERE dp.ordem_id = ?
");
$stDel->execute([$ordemId]);
$delivery = $stDel->fetch();
 
$cor   = statusColor($ordem['status']);
$steps = ['aguardando','em_analise','em_reparo','pronto','entregue'];
$idx   = array_search($ordem['status'], $steps);
$idx   = ($idx === false) ? 0 : $idx;
$pct   = ($ordem['status'] === 'cancelado') ? 0 : round(($idx / (count($steps) - 1)) * 100);
 
// Orçamento pendente de aprovação: status aguardando ou em_analise E tem valor
$orcamentoPendente = in_array($ordem['status'], ['aguardando', 'em_analise'])
                     && $ordem['valor_orcamento'] > 0;
?>
 
<div style="margin-bottom:4px">
  <a href="minhas_ordens.php" style="color:rgba(220,200,255,0.45);font-size:13px">← Minhas ordens</a>
</div>
<h1 class="page-title" style="margin-top:8px">
  <?= h($ordem['numero']) ?> <em>— <?= h($ordem['dispositivo_nome'] ?? '?') ?></em>
</h1>
<p class="page-sub"><?= h($ordem['modelo'] ?? '') ?> · <?= date('d/m/Y', strtotime($ordem['criado_em'])) ?></p>
 
<!-- Banner de orçamento pendente (destaque) -->
<?php if ($orcamentoPendente): ?>
<div style="background:linear-gradient(135deg,rgba(184,79,255,0.12),rgba(124,58,237,0.08));border:1px solid rgba(184,79,255,0.35);border-radius:14px;padding:20px 24px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
  <div>
    <div style="font-size:12px;font-weight:700;color:rgba(184,79,255,0.7);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">⏳ Orçamento aguardando sua aprovação</div>
    <div style="font-family:'Orbitron',monospace;font-size:26px;font-weight:900;color:#b84fff">
      R$ <?= number_format($ordem['valor_orcamento'],2,',','.') ?>
    </div>
    <div style="font-size:12px;color:rgba(220,200,255,0.5);margin-top:4px">Aceite para iniciarmos o reparo ou recuse caso não queira prosseguir.</div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <form method="POST">
      <input type="hidden" name="aceitar_orcamento" value="1"/>
      <button type="submit" class="btn-primary" style="background:#10b981;padding:12px 24px;font-size:14px">
        ✓ Aceitar orçamento
      </button>
    </form>
    <form method="POST" onsubmit="return confirm('Tem certeza que deseja recusar? A OS será cancelada.')">
      <input type="hidden" name="recusar_orcamento" value="1"/>
      <button type="submit" class="btn-ghost" style="border-color:rgba(239,68,68,0.4);color:#f87171;padding:12px 24px;font-size:14px">
        ✕ Recusar
      </button>
    </form>
  </div>
</div>
<?php endif; ?>
 
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px">
 
  <!-- Detalhes -->
  <div class="card">
    <p class="section-title">Detalhes da ordem</p>
    <table style="width:100%;font-size:13px;border-collapse:collapse">
      <?php foreach ([
        'Problema'    => $ordem['problema'],
        'Serviço'     => $ordem['servico']    ?: '—',
        'Sistema'     => strtoupper($ordem['sistema']),
        'Observações' => $ordem['observacoes'] ?: '—',
      ] as $k => $v): ?>
      <tr>
        <td style="padding:7px 0;color:rgba(220,200,255,0.45);width:100px;vertical-align:top"><?= $k ?></td>
        <td style="padding:7px 0;color:#f0eeff"><?= h($v) ?></td>
      </tr>
      <?php endforeach; ?>
    </table>
  </div>
 
  <!-- Status e valores -->
  <div class="card">
    <p class="section-title">Status do reparo</p>
 
    <span class="badge" style="background:<?= $cor ?>22;color:<?= $cor ?>;border:1px solid <?= $cor ?>44;font-size:13px;padding:5px 14px;margin-bottom:16px;display:inline-block">
      ● <?= statusLabel($ordem['status']) ?>
    </span>
 
    <div style="margin:16px 0">
      <div style="display:flex;justify-content:space-between;font-size:10px;color:rgba(220,200,255,0.35);margin-bottom:5px">
        <span>Aguardando</span><span>Análise</span><span>Reparo</span><span>Pronto</span><span>Entregue</span>
      </div>
      <div style="background:rgba(255,255,255,0.06);border-radius:99px;height:7px">
        <div style="background:linear-gradient(90deg,#7c3aed,#b84fff);height:100%;border-radius:99px;width:<?= $pct ?>%;transition:width .5s"></div>
      </div>
    </div>
 
    <table style="width:100%;font-size:13px;border-collapse:collapse;margin-top:12px">
      <?php if ($ordem['valor_orcamento']): ?>
      <tr>
        <td style="padding:6px 0;color:rgba(220,200,255,0.45)">Orçamento</td>
        <td style="padding:6px 0;font-weight:700;color:#b84fff;text-align:right">R$ <?= number_format($ordem['valor_orcamento'],2,',','.') ?></td>
      </tr>
      <?php endif; ?>
      <?php if ($ordem['valor_final']): ?>
      <tr>
        <td style="padding:6px 0;color:rgba(220,200,255,0.45)">Valor final</td>
        <td style="padding:6px 0;font-weight:700;color:#10b981;text-align:right">R$ <?= number_format($ordem['valor_final'],2,',','.') ?></td>
      </tr>
      <?php endif; ?>
    </table>
 
    <?php if (!$delivery && $ordem['status'] === 'pronto'): ?>
    <div style="margin-top:16px;padding-top:14px;border-top:1px solid rgba(255,255,255,0.06)">
      <a href="delivery.php?ordem_id=<?= $ordem['id'] ?>" class="btn-ghost" style="display:flex;width:100%;justify-content:center">
        🚚 Solicitar retirada / delivery
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>
 
<!-- Delivery info -->
<?php if ($delivery):
  $deliveryStatusLabel = match($delivery['status']) {
    'aguardando'  => ['label'=>'Aguardando confirmação', 'cor'=>'#f59e0b', 'icon'=>'⏳'],
    'confirmado'  => ['label'=>'Confirmado pelo técnico', 'cor'=>'#3b82f6', 'icon'=>'✅'],
    'a_caminho'   => ['label'=>'A caminho!',              'cor'=>'#8b5cf6', 'icon'=>'🚚'],
    'concluido'   => ['label'=>'Concluído',               'cor'=>'#10b981', 'icon'=>'🎉'],
    'cancelado'   => ['label'=>'Cancelado',               'cor'=>'#ef4444', 'icon'=>'❌'],
    default       => ['label'=>ucfirst($delivery['status']), 'cor'=>'#6b7280', 'icon'=>'📦'],
  };
?>
<div class="card" style="margin-bottom:24px;border-color:rgba(59,130,246,0.25)">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px">
    <p class="section-title" style="margin:0">🚚 Delivery / Retirada</p>
    <span class="badge" style="background:<?= $deliveryStatusLabel['cor'] ?>22;color:<?= $deliveryStatusLabel['cor'] ?>;border:1px solid <?= $deliveryStatusLabel['cor'] ?>44;font-size:12px;padding:5px 12px">
      <?= $deliveryStatusLabel['icon'] ?> <?= $deliveryStatusLabel['label'] ?>
    </span>
  </div>
 
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;font-size:13px">
    <div><span style="color:rgba(220,200,255,0.45);font-size:11px">TIPO</span><br><strong><?= $delivery['tipo'] === 'retirada' ? '📦 Retirada' : '🚚 Entrega' ?></strong></div>
    <div><span style="color:rgba(220,200,255,0.45);font-size:11px">BAIRRO</span><br><strong><?= h($delivery['bairro'] ?? '—') ?></strong></div>
    <div><span style="color:rgba(220,200,255,0.45);font-size:11px">TAXA</span><br><strong style="color:#b84fff">R$ <?= number_format($delivery['taxa_cobrada'],2,',','.') ?></strong></div>
    <?php if ($delivery['data_agendada']): ?>
    <div><span style="color:rgba(220,200,255,0.45);font-size:11px">DATA</span><br><strong><?= date('d/m/Y', strtotime($delivery['data_agendada'])) ?></strong></div>
    <?php endif; ?>
    <?php if ($delivery['hora_agendada']): ?>
    <div><span style="color:rgba(220,200,255,0.45);font-size:11px">HORA</span><br><strong><?= substr($delivery['hora_agendada'],0,5) ?></strong></div>
    <?php endif; ?>
  </div>
 
  <?php if (!empty($delivery['obs_admin'])): ?>
  <div style="margin-top:14px;padding:12px 16px;background:rgba(59,130,246,0.08);border:1px solid rgba(59,130,246,0.2);border-radius:10px;font-size:13px">
    <strong style="color:#60a5fa">🔧 Técnico:</strong> <span style="color:rgba(220,200,255,0.8)"><?= h($delivery['obs_admin']) ?></span>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>
 
<!-- CHAT -->
<div class="card" id="chat">
  <p class="section-title">💬 Chat com o técnico</p>
  <p style="font-size:12px;color:rgba(220,200,255,0.4);margin-bottom:16px">Tire dúvidas diretamente com o técnico responsável.</p>
 
  <div style="display:flex;flex-direction:column;gap:10px;max-height:360px;overflow-y:auto;padding:4px;margin-bottom:16px" id="chat-msgs">
    <?php if (!$mensagens): ?>
      <p style="text-align:center;color:rgba(220,200,255,0.3);font-size:13px;padding:20px 0">Nenhuma mensagem ainda. Inicie a conversa!</p>
    <?php else: foreach ($mensagens as $m):
      $eCliente = $m['remetente'] === 'cliente'; ?>
      <div style="display:flex;flex-direction:column;align-items:<?= $eCliente ? 'flex-end' : 'flex-start' ?>">
        <div style="max-width:75%;background:<?= $eCliente ? 'rgba(184,79,255,0.15)' : 'rgba(255,255,255,0.05)' ?>;border:1px solid <?= $eCliente ? 'rgba(184,79,255,0.25)' : 'rgba(255,255,255,0.08)' ?>;border-radius:<?= $eCliente ? '14px 14px 2px 14px' : '14px 14px 14px 2px' ?>;padding:10px 14px;font-size:13px;color:#f0eeff;line-height:1.5">
          <?= nl2br(h($m['texto'])) ?>
        </div>
        <span style="font-size:11px;color:rgba(220,200,255,0.3);margin-top:3px">
          <?= $eCliente ? 'Você' : '🔧 Técnico' ?> · <?= date('d/m H:i', strtotime($m['criado_em'])) ?>
        </span>
      </div>
    <?php endforeach; endif; ?>
  </div>
 
  <form method="POST" style="display:flex;gap:10px;align-items:flex-end">
    <textarea name="mensagem" placeholder="Digite sua mensagem..." rows="2"
              style="flex:1;margin-bottom:0;resize:none"></textarea>
    <button type="submit" class="btn-primary" style="height:44px;padding:0 18px;flex-shrink:0">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/>
      </svg>
    </button>
  </form>
</div>
 
<!-- Histórico de status -->
<div class="card" style="margin-top:20px">
  <p class="section-title">Histórico de status</p>
  <div style="display:flex;flex-direction:column;gap:8px">
    <?php foreach (array_reverse($historico) as $h): ?>
    <div style="display:flex;gap:12px;align-items:flex-start;font-size:13px">
      <span style="color:rgba(220,200,255,0.35);font-size:11px;min-width:90px;padding-top:1px">
        <?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?>
      </span>
      <div>
        <?php if ($h['status_novo']): ?>
        <span class="badge" style="background:<?= statusColor($h['status_novo']) ?>22;color:<?= statusColor($h['status_novo']) ?>;border:1px solid <?= statusColor($h['status_novo']) ?>44">
          <?= statusLabel($h['status_novo']) ?>
        </span>
        <?php endif; ?>
        <?php if ($h['nota']): ?>
        <span style="color:rgba(220,200,255,0.6);margin-left:8px"><?= h($h['nota']) ?></span>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
 
<script>
const c = document.getElementById('chat-msgs');
if (c) c.scrollTop = c.scrollHeight;
</script>
 
<?php require_once __DIR__ . '/../../includes/cliente_footer.php'; ?>