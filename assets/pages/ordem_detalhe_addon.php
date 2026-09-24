<?php
// ══════════════════════════════════════════════════════════════
//  assets/pages/ordem_detalhe_addon.php
//  Pode ser aberto direto OU incluído em outra página.
// ══════════════════════════════════════════════════════════════

// ── Inclui config se ainda não foi incluído ──────────────────
if (!function_exists('getDB')) {
    require_once __DIR__ . '/../includes/config.php';
}
if (!function_exists('requireLogin')) {
    require_once __DIR__ . '/../includes/layout.php';
}

requireLogin();
$db = getDB();

// ── Pega o ID da OS ──────────────────────────────────────────
$ordemId = intval($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$ordemId) {
    die('<p style="color:#f87171;font-family:sans-serif;padding:20px">ID da OS não informado. Use ?id=X na URL.</p>');
}

// ── Verifica se a OS existe ──────────────────────────────────
$stCheck = $db->prepare("SELECT id FROM ordens WHERE id = ?");
$stCheck->execute([$ordemId]);
if (!$stCheck->fetch()) {
    die('<p style="color:#f87171;font-family:sans-serif;padding:20px">OS não encontrada.</p>');
}

// ── Helpers de status (caso não venham do layout.php) ────────
if (!function_exists('statusLabel')) {
    function statusLabel(string $s): string {
        return match($s) {
            'aguardando'  => 'Aguardando',
            'em_analise'  => 'Em análise',
            'em_reparo'   => 'Em reparo',
            'pronto'      => 'Pronto p/ retirada',
            'entregue'    => 'Entregue',
            'cancelado'   => 'Cancelado',
            default       => ucfirst($s),
        };
    }
}

// ── POST: ações ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Enviar mensagem ao cliente
    if (!empty($_POST['msg_admin'])) {
        $texto = trim($_POST['msg_admin']);
        $db->prepare("INSERT INTO mensagens (ordem_id, remetente, texto) VALUES (?, 'admin', ?)")
           ->execute([$ordemId, $texto]);
    }

    // Enviar orçamento
    if (isset($_POST['valor_orcamento']) && $_POST['valor_orcamento'] !== '') {
        $val = (float) str_replace(',', '.', $_POST['valor_orcamento']);

        $stAnt = $db->prepare("SELECT status FROM ordens WHERE id = ?");
        $stAnt->execute([$ordemId]);
        $atual = $stAnt->fetchColumn();

        $db->prepare("UPDATE ordens SET valor_orcamento = ?, status = 'em_analise', atualizado_em = NOW() WHERE id = ?")
           ->execute([$val, $ordemId]);

        $db->prepare("INSERT INTO historico (ordem_id, status_ant, status_novo, nota) VALUES (?,?,?,?)")
           ->execute([$ordemId, $atual, 'em_analise', 'Orçamento enviado: R$ ' . number_format($val, 2, ',', '.')]);

        $db->prepare("INSERT INTO mensagens (ordem_id, remetente, texto) VALUES (?, 'admin', ?)")
           ->execute([$ordemId, '💰 Orçamento disponível: R$ ' . number_format($val, 2, ',', '.') . '. Acesse seu painel para aceitar.']);
    }

    // Atualizar status
    if (!empty($_POST['novo_status'])) {
        $stAnt = $db->prepare("SELECT status FROM ordens WHERE id = ?");
        $stAnt->execute([$ordemId]);
        $atual = $stAnt->fetchColumn();

        $novo = $_POST['novo_status'];
        $nota = trim($_POST['nota_status'] ?? '');

        $db->prepare("UPDATE ordens SET status = ?, atualizado_em = NOW() WHERE id = ?")
           ->execute([$novo, $ordemId]);

        $db->prepare("INSERT INTO historico (ordem_id, status_ant, status_novo, nota) VALUES (?,?,?,?)")
           ->execute([$ordemId, $atual, $novo, $nota ?: 'Status atualizado pelo admin.']);

        $db->prepare("INSERT INTO mensagens (ordem_id, remetente, texto) VALUES (?, 'admin', ?)")
           ->execute([$ordemId, '🔄 Status atualizado: ' . statusLabel($novo) . ($nota ? ' — ' . $nota : '')]);
    }

    // Atualizar delivery
    if (isset($_POST['delivery_status'])) {
        $ds       = $_POST['delivery_status'];
        $obsAdmin = trim($_POST['obs_admin_delivery'] ?? '');
        $db->prepare("UPDATE delivery_pedidos SET status = ?, obs_admin = ? WHERE ordem_id = ?")
           ->execute([$ds, $obsAdmin ?: null, $ordemId]);
    }

    header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?') . '?id=' . $ordemId . '#addon');
    exit;
}

// ── Marca mensagens do cliente como lidas ────────────────────
$db->prepare("UPDATE mensagens SET lida = 1 WHERE ordem_id = ? AND remetente = 'cliente'")
   ->execute([$ordemId]);

// ── Carrega mensagens ─────────────────────────────────────────
$stMsg = $db->prepare("SELECT * FROM mensagens WHERE ordem_id = ? ORDER BY criado_em ASC");
$stMsg->execute([$ordemId]);
$mensagens = $stMsg->fetchAll();

// ── Carrega OS atual ─────────────────────────────────────────
$stOrdem = $db->prepare("SELECT valor_orcamento, status FROM ordens WHERE id = ?");
$stOrdem->execute([$ordemId]);
$ordemAtual = $stOrdem->fetch();

// ── Carrega delivery ─────────────────────────────────────────
$stDel = $db->prepare("
    SELECT dp.*, dz.bairro, dz.distancia_km,
           ce.logradouro, ce.numero AS end_num, ce.complemento, ce.cidade
    FROM delivery_pedidos dp
    LEFT JOIN delivery_zonas dz    ON dz.id = dp.zona_id
    LEFT JOIN cliente_enderecos ce ON ce.id = dp.endereco_id
    WHERE dp.ordem_id = ?
");
$stDel->execute([$ordemId]);
$delivery = $stDel->fetch();

$naoLidas = count(array_filter($mensagens, fn($m) => $m['remetente'] === 'cliente' && !$m['lida']));
?>
<div id="addon" style="display:flex;flex-direction:column;gap:20px;margin-top:28px">

<!-- ══ CHAT ══════════════════════════════════════════════════ -->
<div style="background:rgba(255,255,255,0.02);border:1px solid rgba(184,79,255,0.15);border-radius:16px;padding:24px">

  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <h3 style="font-size:15px;font-weight:700;color:#f0eeff;margin:0">💬 Chat com o cliente</h3>
    <?php if ($naoLidas > 0): ?>
    <span style="background:rgba(184,79,255,0.15);border:1px solid rgba(184,79,255,0.3);border-radius:99px;padding:3px 10px;font-size:11px;font-weight:700;color:#b84fff">
      <?= $naoLidas ?> nova(s)
    </span>
    <?php endif; ?>
  </div>

  <div id="admin-chat-msgs"
       style="display:flex;flex-direction:column;gap:10px;max-height:360px;overflow-y:auto;padding:4px;margin-bottom:16px">
    <?php if (!$mensagens): ?>
      <p style="text-align:center;color:rgba(220,200,255,0.3);font-size:13px;padding:20px 0">Sem mensagens ainda.</p>
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

  <form method="POST" style="display:flex;gap:10px;align-items:flex-end">
    <input type="hidden" name="id" value="<?= $ordemId ?>">
    <textarea name="msg_admin" placeholder="Responda ao cliente..." rows="2"
      style="flex:1;background:rgba(255,255,255,0.04);border:1px solid rgba(184,79,255,0.2);border-radius:10px;padding:10px 14px;color:#f0eeff;font-size:13px;font-family:inherit;outline:none;resize:none;min-height:44px"></textarea>
    <button type="submit"
      style="background:#b84fff;color:#0d0b14;border:none;border-radius:10px;padding:0 20px;height:44px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap;flex-shrink:0">
      Enviar
    </button>
  </form>
</div>

<!-- ══ ORÇAMENTO & STATUS ════════════════════════════════════ -->
<div style="background:rgba(255,255,255,0.02);border:1px solid rgba(16,185,129,0.2);border-radius:16px;padding:24px">
  <h3 style="font-size:15px;font-weight:700;color:#f0eeff;margin:0 0 20px">💰 Orçamento & Status</h3>

  <!-- Enviar orçamento -->
  <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-bottom:16px;padding-bottom:16px;border-bottom:1px solid rgba(255,255,255,0.06)">
    <input type="hidden" name="id" value="<?= $ordemId ?>">
    <div style="flex:1;min-width:160px">
      <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.5px;color:rgba(220,200,255,0.5);text-transform:uppercase;margin-bottom:6px">Valor do orçamento (R$)</label>
      <input type="number" name="valor_orcamento" step="0.01" min="0" placeholder="Ex: 299.00"
             value="<?= $ordemAtual['valor_orcamento'] ? number_format($ordemAtual['valor_orcamento'], 2, '.', '') : '' ?>"
             style="background:rgba(255,255,255,0.04);border:1px solid rgba(16,185,129,0.3);border-radius:10px;padding:10px 14px;color:#f0eeff;font-size:14px;font-family:inherit;outline:none;width:100%"/>
    </div>
    <button type="submit"
      style="background:#10b981;color:#fff;border:none;border-radius:10px;padding:11px 22px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap">
      ✓ Enviar ao cliente
    </button>
  </form>

  <!-- Atualizar status -->
  <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap">
    <input type="hidden" name="id" value="<?= $ordemId ?>">
    <div style="flex:1;min-width:160px">
      <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.5px;color:rgba(220,200,255,0.5);text-transform:uppercase;margin-bottom:6px">Novo status</label>
      <select name="novo_status"
        style="background:rgba(255,255,255,0.04);border:1px solid rgba(184,79,255,0.2);border-radius:10px;padding:10px 14px;color:#f0eeff;font-size:13px;font-family:inherit;outline:none;width:100%">
        <option value="">— Selecionar status —</option>
        <?php foreach (['aguardando','em_analise','em_reparo','pronto','entregue','cancelado'] as $s): ?>
          <option value="<?= $s ?>" <?= $ordemAtual['status'] === $s ? 'selected' : '' ?>>
            <?= statusLabel($s) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="flex:2;min-width:200px">
      <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.5px;color:rgba(220,200,255,0.5);text-transform:uppercase;margin-bottom:6px">Nota (opcional)</label>
      <input type="text" name="nota_status" placeholder="Ex: Peça chegou, iniciando reparo..."
             style="background:rgba(255,255,255,0.04);border:1px solid rgba(184,79,255,0.2);border-radius:10px;padding:10px 14px;color:#f0eeff;font-size:13px;font-family:inherit;outline:none;width:100%"/>
    </div>
    <button type="submit"
      style="background:#b84fff;color:#0d0b14;border:none;border-radius:10px;padding:11px 22px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap">
      Atualizar status
    </button>
  </form>
</div>

<!-- ══ DELIVERY ══════════════════════════════════════════════ -->
<?php if ($delivery): ?>
<div style="background:rgba(59,130,246,0.05);border:1px solid rgba(59,130,246,0.2);border-radius:16px;padding:24px">
  <h3 style="font-size:15px;font-weight:700;color:#f0eeff;margin:0 0 16px">🚚 Pedido de Delivery</h3>

  <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(130px,1fr));gap:12px;font-size:13px;margin-bottom:16px">
    <div><span style="color:rgba(220,200,255,0.45);font-size:11px">TIPO</span><br><strong><?= $delivery['tipo'] === 'retirada' ? '📦 Retirada' : '🚚 Entrega' ?></strong></div>
    <div><span style="color:rgba(220,200,255,0.45);font-size:11px">BAIRRO</span><br><strong><?= htmlspecialchars($delivery['bairro'] ?? '—', ENT_QUOTES, 'UTF-8') ?></strong></div>
    <div><span style="color:rgba(220,200,255,0.45);font-size:11px">DISTÂNCIA</span><br><strong><?= $delivery['distancia_km'] ?> km</strong></div>
    <div><span style="color:rgba(220,200,255,0.45);font-size:11px">TAXA</span><br><strong style="color:#b84fff">R$ <?= number_format($delivery['taxa_cobrada'], 2, ',', '.') ?></strong></div>
    <?php if ($delivery['data_agendada']): ?>
    <div><span style="color:rgba(220,200,255,0.45);font-size:11px">DATA</span><br><strong><?= date('d/m/Y', strtotime($delivery['data_agendada'])) ?></strong></div>
    <?php endif; ?>
    <?php if ($delivery['hora_agendada']): ?>
    <div><span style="color:rgba(220,200,255,0.45);font-size:11px">HORA</span><br><strong><?= substr($delivery['hora_agendada'], 0, 5) ?></strong></div>
    <?php endif; ?>
  </div>

  <?php if ($delivery['logradouro']): ?>
  <div style="background:rgba(255,255,255,0.03);border-radius:8px;padding:10px 14px;font-size:13px;margin-bottom:10px">
    📍 <?= htmlspecialchars($delivery['logradouro'] . ', ' . ($delivery['end_num'] ?? '') . ($delivery['complemento'] ? ' — ' . $delivery['complemento'] : '') . ', ' . ($delivery['cidade'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php endif; ?>

  <?php if (!empty($delivery['obs_cliente'])): ?>
  <div style="background:rgba(255,255,255,0.03);border-radius:8px;padding:10px 14px;font-size:13px;color:rgba(220,200,255,0.7);margin-bottom:10px">
    💬 <strong>Cliente:</strong> <?= htmlspecialchars($delivery['obs_cliente'], ENT_QUOTES, 'UTF-8') ?>
  </div>
  <?php endif; ?>

  <form method="POST" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;margin-top:12px">
    <input type="hidden" name="id" value="<?= $ordemId ?>">
    <div style="flex:1;min-width:160px">
      <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.5px;color:rgba(220,200,255,0.5);text-transform:uppercase;margin-bottom:6px">Status do delivery</label>
      <select name="delivery_status"
        style="background:rgba(255,255,255,0.04);border:1px solid rgba(59,130,246,0.3);border-radius:10px;padding:10px 14px;color:#f0eeff;font-size:13px;font-family:inherit;outline:none;width:100%">
        <?php foreach (['aguardando' => 'Aguardando', 'confirmado' => 'Confirmado', 'a_caminho' => 'A caminho', 'concluido' => 'Concluído', 'cancelado' => 'Cancelado'] as $v => $l): ?>
          <option value="<?= $v ?>" <?= $delivery['status'] === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="flex:2;min-width:200px">
      <label style="display:block;font-size:11px;font-weight:700;letter-spacing:.5px;color:rgba(220,200,255,0.5);text-transform:uppercase;margin-bottom:6px">Obs. para o cliente</label>
      <input type="text" name="obs_admin_delivery"
             placeholder="Ex: Saindo às 14h, ligue antes..."
             value="<?= htmlspecialchars($delivery['obs_admin'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
             style="background:rgba(255,255,255,0.04);border:1px solid rgba(59,130,246,0.2);border-radius:10px;padding:10px 14px;color:#f0eeff;font-size:13px;font-family:inherit;outline:none;width:100%"/>
    </div>
    <button type="submit"
      style="background:#3b82f6;color:#fff;border:none;border-radius:10px;padding:11px 22px;font-size:13px;font-weight:700;cursor:pointer;font-family:inherit;white-space:nowrap">
      Salvar delivery
    </button>
  </form>
</div>
<?php endif; ?>

</div><!-- #addon -->

<script>
(function() {
  const c = document.getElementById('admin-chat-msgs');
  if (c) c.scrollTop = c.scrollHeight;
})();
</script>