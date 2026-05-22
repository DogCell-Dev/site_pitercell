<?php
// ── php/pages/cliente/delivery.php ──────────────────────────
require_once __DIR__ . '/../../../php/config.php';
$pageTitle  = 'Delivery / Retirada';
$activePage = 'delivery';
require_once __DIR__ . '/../../../php/includes/cliente_header.php';

$cid     = $_SESSION['cliente_id'];
$erro    = $sucesso = '';
$ordemId = (int)($_GET['ordem_id'] ?? 0);

// Carrega ordens do cliente que podem ter delivery (pronto ou aprovado)
$st = db()->prepare("
    SELECT o.id, o.numero, o.modelo, dt.nome AS dispositivo_nome, o.status,
           (SELECT COUNT(*) FROM delivery_pedidos dp WHERE dp.ordem_id = o.id) AS tem_delivery
    FROM ordens o
    LEFT JOIN dispositivos_tipos dt ON dt.id = o.dispositivo_id
    WHERE o.cliente_id = ?
    ORDER BY o.criado_em DESC
");
$st->execute([$cid]);
$ordens = $st->fetchAll();

// Carrega zonas de delivery
$zonas = db()->query("SELECT * FROM delivery_zonas WHERE ativo = 1 ORDER BY distancia_km")->fetchAll();

// Carrega endereços do cliente
$enderecos = db()->prepare("SELECT * FROM cliente_enderecos WHERE cliente_id = ? ORDER BY principal DESC");
$enderecos->execute([$cid]);
$enderecos = $enderecos->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid      = (int)($_POST['ordem_id']   ?? 0);
    $tipo     = $_POST['tipo']              ?? '';
    $zonaId   = (int)($_POST['zona_id']    ?? 0);
    $endId    = (int)($_POST['endereco_id']?? 0);
    $data     = $_POST['data_agendada']     ?? '';
    $hora     = $_POST['hora_agendada']     ?? '';
    $obs      = trim($_POST['obs_cliente']  ?? '');

    // Valida OS
    $stOs = db()->prepare("SELECT id FROM ordens WHERE id = ? AND cliente_id = ?");
    $stOs->execute([$oid, $cid]);
    if (!$stOs->fetch()) { $erro = 'Ordem inválida.'; }
    elseif (!in_array($tipo, ['retirada','entrega'])) { $erro = 'Tipo inválido.'; }
    elseif (!$zonaId) { $erro = 'Selecione o bairro.'; }
    else {
        // Busca taxa da zona
        $stZona = db()->prepare("SELECT taxa, distancia_km FROM delivery_zonas WHERE id = ?");
        $stZona->execute([$zonaId]);
        $zona = $stZona->fetch();

        db()->prepare("
            INSERT INTO delivery_pedidos
            (ordem_id, tipo, endereco_id, zona_id, taxa_cobrada, data_agendada, hora_agendada, obs_cliente)
            VALUES (?,?,?,?,?,?,?,?)
        ")->execute([
            $oid, $tipo,
            $endId ?: null,
            $zonaId,
            $zona['taxa'],
            $data ?: null,
            $hora ?: null,
            $obs ?: null,
        ]);

        // Mensagem automática ao admin
        db()->prepare("
            INSERT INTO mensagens (ordem_id, remetente, texto)
            VALUES (?, 'cliente', ?)
        ")->execute([$oid, "📦 Solicitei um delivery ({$tipo}). Bairro: {$bairro}. Taxa: R$ ".number_format($zona['taxa'],2,',','.')."."]);

        $sucesso = 'Pedido de delivery enviado! O técnico entrará em contato para confirmar.';
    }
}
?>

<h1 class="page-title">Delivery & <em>Retirada</em></h1>
<p class="page-sub">Solicite a retirada do aparelho ou entrega após o reparo. Taxa: R$ <?= number_format(TAXA_KM,2,',','.') ?>/km</p>

<?php if ($erro):    ?><div class="erro"><?= h($erro) ?></div><?php endif; ?>
<?php if ($sucesso): ?>
  <div class="ok"><?= h($sucesso) ?></div>
  <a href="<?= SITE_URL ?>/php/pages/cliente/minhas_ordens.php" class="btn-primary">← Ver minhas ordens</a>
<?php else: ?>

<!-- Tabela de zonas -->
<div class="card" style="margin-bottom:24px">
  <p class="section-title">📍 Tabela de bairros e taxas</p>
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:10px">
    <?php foreach ($zonas as $z): ?>
    <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(184,79,255,0.1);border-radius:10px;padding:12px 14px;display:flex;justify-content:space-between;align-items:center">
      <span style="font-size:14px"><?= h($z['bairro']) ?><br><span style="font-size:11px;color:rgba(220,200,255,0.4)"><?= $z['distancia_km'] ?> km</span></span>
      <span style="font-weight:700;color:#b84fff;font-size:14px">R$ <?= number_format($z['taxa'],2,',','.') ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Formulário de solicitação -->
<div class="card">
  <p class="section-title">Solicitar serviço</p>
  <form method="POST">

    <label>Ordem de serviço *</label>
    <select name="ordem_id" required>
      <option value="">Selecione a OS...</option>
      <?php foreach ($ordens as $o): ?>
      <option value="<?= $o['id'] ?>"
              <?= ($ordemId === $o['id'] || ($_POST['ordem_id']??0) == $o['id']) ? 'selected' : '' ?>
              <?= $o['tem_delivery'] ? 'disabled' : '' ?>>
        <?= h($o['numero']) ?> — <?= h($o['dispositivo_nome'] ?? '') ?> <?= h($o['modelo'] ?? '') ?>
        (<?= statusLabel($o['status']) ?>)
        <?= $o['tem_delivery'] ? ' — Delivery já solicitado' : '' ?>
      </option>
      <?php endforeach; ?>
    </select>

    <label>Tipo de serviço *</label>
    <div style="display:flex;gap:16px;margin-bottom:16px">
      <?php foreach (['retirada'=>'📦 Retirada do aparelho (no seu endereço)','entrega'=>'🚚 Entrega após reparo'] as $val=>$txt): ?>
      <label style="display:flex;align-items:center;gap:8px;font-size:13px;text-transform:none;letter-spacing:0;color:#f0eeff;margin-bottom:0;cursor:pointer;flex:1;background:rgba(255,255,255,0.02);border:1px solid rgba(184,79,255,0.15);border-radius:10px;padding:12px">
        <input type="radio" name="tipo" value="<?= $val ?>" <?= ($_POST['tipo']??'')===$val?'checked':'' ?> style="width:auto;margin-bottom:0"/>
        <?= $txt ?>
      </label>
      <?php endforeach; ?>
    </div>

    <label>Bairro / Zona *</label>
    <select name="zona_id" id="zona-sel" required onchange="updateTaxa(this)">
      <option value="">Selecione o bairro...</option>
      <?php foreach ($zonas as $z): ?>
      <option value="<?= $z['id'] ?>" data-taxa="<?= $z['taxa'] ?>"
              <?= ($_POST['zona_id']??0)==$z['id']?'selected':'' ?>>
        <?= h($z['bairro']) ?> — <?= $z['distancia_km'] ?> km
      </option>
      <?php endforeach; ?>
    </select>

    <div id="taxa-info" style="background:rgba(184,79,255,0.08);border:1px solid rgba(184,79,255,0.2);border-radius:10px;padding:12px 16px;margin-bottom:16px;display:none;font-size:14px">
      💰 Taxa para este bairro: <strong id="taxa-val" style="color:#b84fff"></strong>
    </div>

    <?php if ($enderecos): ?>
    <label>Endereço (opcional)</label>
    <select name="endereco_id">
      <option value="">Informar na observação</option>
      <?php foreach ($enderecos as $e): ?>
      <option value="<?= $e['id'] ?>" <?= ($_POST['endereco_id']??0)==$e['id']?'selected':'' ?>>
        <?= h($e['logradouro'].', '.$e['numero'].' — '.$e['bairro']) ?>
      </option>
      <?php endforeach; ?>
    </select>
    <?php endif; ?>

    <div class="row2">
      <div class="field">
        <label>Data preferida</label>
        <input type="date" name="data_agendada" min="<?= date('Y-m-d') ?>"
               value="<?= h($_POST['data_agendada'] ?? '') ?>"/>
      </div>
      <div class="field">
        <label>Horário preferido</label>
        <input type="time" name="hora_agendada" value="<?= h($_POST['hora_agendada'] ?? '') ?>"/>
      </div>
    </div>

    <label>Observações</label>
    <textarea name="obs_cliente" placeholder="Ex: Interfone 302, ligar antes de chegar, endereço completo..."><?= h($_POST['obs_cliente'] ?? '') ?></textarea>

    <button type="submit" class="btn-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      Solicitar delivery
    </button>
  </form>
</div>

<?php endif; ?>

<script>
function updateTaxa(sel) {
  const opt  = sel.options[sel.selectedIndex];
  const taxa = parseFloat(opt.dataset.taxa || 0);
  const box  = document.getElementById('taxa-info');
  if (taxa > 0) {
    box.style.display = 'block';
    document.getElementById('taxa-val').textContent =
      'R$ ' + taxa.toFixed(2).replace('.', ',');
  } else {
    box.style.display = 'none';
  }
}
// Dispara se já vier selecionado
const s = document.getElementById('zona-sel');
if (s && s.value) updateTaxa(s);
</script>

<?php require_once __DIR__ . '/../../../php/includes/cliente_footer.php'; ?>
