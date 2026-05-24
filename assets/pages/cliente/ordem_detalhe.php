<?php
// ── assets/pages/cliente/ordem_detalhe.php ──────────────────
require_once __DIR__ . '/../../includes/config.php';
requireCliente();
 
$cid     = $_SESSION['cliente_id'];
$ordemId = (int)($_GET['id'] ?? 0);
 
// ── TODOS OS POSTS ANTES DE QUALQUER OUTPUT ──────────────────
 
// Envio de mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mensagem'])) {
    $texto = trim($_POST['mensagem'] ?? '');
    if ($texto && $ordemId) {
        db()->prepare("INSERT INTO mensagens (ordem_id, remetente, texto) VALUES (?, 'cliente', ?)")
            ->execute([$ordemId, $texto]);
    }
    redirect(SITE_URL . '/assets/pages/cliente/ordem_detalhe.php?id=' . $ordemId . '#chat');
}
 
// Aceitar orçamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['aceitar_orcamento'])) {
    if ($ordemId) {
        db()->prepare("UPDATE ordens SET status = 'em_analise' WHERE id = ? AND status = 'aguardando'")->execute([$ordemId]);
        db()->prepare("INSERT INTO historico (ordem_id, status_ant, status_novo, nota) VALUES (?,?,?,?)")
            ->execute([$ordemId, 'aguardando', 'em_analise', 'Cliente aceitou o orçamento.']);
    }
    redirect(SITE_URL . '/assets/pages/cliente/ordem_detalhe.php?id=' . $ordemId);
}
 
// ── Agora sim: carrega dados e inclui header ─────────────────
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
?>
 
<div style="margin-bottom:4px">
  <a href="minhas_ordens.php" style="color:rgba(220,200,255,0.45);font-size:13px">← Minhas ordens</a>
</div>
<h1 class="page-title" style="margin-top:8px">
  <?= h($ordem['numero']) ?> <em>— <?= h($ordem['dispositivo_nome'] ?? '?') ?></em>
</h1>
<p class="page-sub"><?= h($ordem['modelo'] ?? '') ?> · <?= date('d/m/Y', strtotime($ordem['criado_em'])) ?></p>
 
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
        <div style="background:linear-gradient(90deg,#7c3aed,#b84fff);height:100%;border-radius:99px;width:<?= $pct ?>%"></div>
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
 
    <?php if ($ordem['status'] === 'aguardando' && $ordem['valor_orcamento']): ?>
    <form method="POST" style="margin-top:14px">
      <input type="hidden" name="aceitar_orcamento" value="1"/>
      <button type="submit" class="btn-primary" style="width:100%;background:#10b981">
        ✓ Aceitar orçamento de R$ <?= number_format($ordem['valor_orcamento'],2,',','.') ?>
      </button>
    </form>
    <?php endif; ?>
 
    <?php if (!$delivery && $ordem['status'] === 'pronto'): ?>
    <div style="margin-top:12px">
      <a href="delivery.php?ordem_id=<?= $ordem['id'] ?>" class="btn-ghost" style="display:flex;width:100%;justify-content:center">
        🚚 Solicitar retirada / delivery
      </a>
    </div>
    <?php endif; ?>
  </div>
</div>
 
<!-- Delivery info -->
<?php if ($delivery): ?>
<div class="card" style="margin-bottom:24px;background:rgba(59,130,246,0.05);border-color:rgba(59,130,246,0.2)">
  <p class="section-title">🚚 Delivery / Retirada</p>
  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;font-size:13px">
    <div><span style="color:rgba(220,200,255,0.45)">Tipo</span><br><strong><?= $delivery['tipo'] === 'retirada' ? '📦 Retirada' : '🚚 Entrega' ?></strong></div>
    <div><span style="color:rgba(220,200,255,0.45)">Bairro</span><br><strong><?= h($delivery['bairro'] ?? '—') ?></strong></div>
    <div><span style="color:rgba(220,200,255,0.45)">Taxa</span><br><strong style="color:#b84fff">R$ <?= number_format($delivery['taxa_cobrada'],2,',','.') ?></strong></div>
    <div><span style="color:rgba(220,200,255,0.45)">Data</span><br><strong><?= $delivery['data_agendada'] ? date('d/m/Y', strtotime($delivery['data_agendada'])) : '—' ?></strong></div>
    <div>
      <span style="color:rgba(220,200,255,0.45)">Status</span><br>
      <span class="badge" style="background:rgba(59,130,246,0.15);color:#60a5fa;border:1px solid rgba(59,130,246,0.3)">
        <?= ucfirst($delivery['status']) ?>
      </span>
    </div>
  </div>
  <?php if (!empty($delivery['obs_admin'])): ?>
  <div style="margin-top:12px;padding:10px;background:rgba(255,255,255,0.03);border-radius:8px;font-size:13px;color:rgba(220,200,255,0.7)">
    <strong>Técnico:</strong> <?= h($delivery['obs_admin']) ?>
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