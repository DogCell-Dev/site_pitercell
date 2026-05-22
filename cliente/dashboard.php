<?php
// ── php/pages/cliente/dashboard.php ─────────────────────────
require_once __DIR__ . '/../../../php/config.php';
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require_once __DIR__ . '/../../../php/includes/cliente_header.php';

$cid = $_SESSION['cliente_id'];

// Busca resumo das ordens
$st = db()->prepare("
    SELECT
      COUNT(*) AS total,
      SUM(status IN ('aguardando','em_analise')) AS em_aberto,
      SUM(status = 'em_reparo') AS em_reparo,
      SUM(status = 'pronto')    AS pronto,
      SUM(status = 'entregue')  AS entregues
    FROM ordens WHERE cliente_id = ?
");
$st->execute([$cid]);
$resumo = $st->fetch();

// Última OS
$st = db()->prepare("
    SELECT o.*, dt.nome AS dispositivo_nome
    FROM ordens o
    LEFT JOIN dispositivos_tipos dt ON dt.id = o.dispositivo_id
    WHERE o.cliente_id = ?
    ORDER BY o.criado_em DESC LIMIT 1
");
$st->execute([$cid]);
$ultima = $st->fetch();

// Mensagens não lidas
$st = db()->prepare("
    SELECT COUNT(*) AS n FROM mensagens m
    JOIN ordens o ON o.id = m.ordem_id
    WHERE o.cliente_id = ? AND m.remetente = 'admin' AND m.lida = 0
");
$st->execute([$cid]);
$naoLidas = (int)$st->fetch()['n'];
?>

<h1 class="page-title">Olá, <em><?= h(explode(' ', $_SESSION['cliente_nome'])[0]) ?></em> 👋</h1>
<p class="page-sub">Aqui está o resumo dos seus reparos</p>

<!-- Cards de resumo -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:14px;margin-bottom:28px">
  <?php
  $cards = [
    ['Total de OS',    $resumo['total'],    '#b84fff'],
    ['Em aberto',      $resumo['em_aberto'],'#f59e0b'],
    ['Em reparo',      $resumo['em_reparo'],'#3b82f6'],
    ['Pronto p/ ret.', $resumo['pronto'],   '#10b981'],
    ['Entregues',      $resumo['entregues'],'#6b7280'],
  ];
  foreach ($cards as [$label, $val, $cor]):
  ?>
  <div class="card" style="text-align:center;padding:20px 12px">
    <div style="font-family:'Orbitron',monospace;font-size:28px;font-weight:900;color:<?= $cor ?>">
      <?= (int)$val ?>
    </div>
    <div style="font-size:12px;color:rgba(220,200,255,0.5);margin-top:4px"><?= $label ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Mensagens não lidas -->
<?php if ($naoLidas > 0): ?>
<div style="background:rgba(184,79,255,0.08);border:1px solid rgba(184,79,255,0.3);border-radius:12px;padding:14px 18px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between">
  <span style="font-size:14px">
    💬 Você tem <strong style="color:#b84fff"><?= $naoLidas ?> mensagem(ns)</strong> não lida(s) do técnico.
  </span>
  <a href="<?= SITE_URL ?>/php/pages/cliente/minhas_ordens.php" class="btn-primary" style="padding:8px 16px;font-size:12px">
    Ver mensagens
  </a>
</div>
<?php endif; ?>

<!-- Última OS -->
<?php if ($ultima): ?>
<div class="card" style="margin-bottom:24px">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <span class="section-title" style="margin-bottom:0">Última ordem</span>
    <a href="<?= SITE_URL ?>/php/pages/cliente/ordem_detalhe.php?id=<?= $ultima['id'] ?>"
       class="btn-ghost" style="padding:7px 14px;font-size:12px">Ver detalhes</a>
  </div>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:14px">
    <div><span style="color:rgba(220,200,255,0.5)">Nº OS</span><br><strong><?= h($ultima['numero']) ?></strong></div>
    <div><span style="color:rgba(220,200,255,0.5)">Dispositivo</span><br><strong><?= h($ultima['dispositivo_nome'] ?? $ultima['dispositivo_id']) ?> <?= h($ultima['modelo'] ?? '') ?></strong></div>
    <div><span style="color:rgba(220,200,255,0.5)">Serviço</span><br><strong><?= h($ultima['servico'] ?? '—') ?></strong></div>
    <div><span style="color:rgba(220,200,255,0.5)">Status</span><br>
      <span class="badge" style="background:<?= statusColor($ultima['status']) ?>22;color:<?= statusColor($ultima['status']) ?>;border:1px solid <?= statusColor($ultima['status']) ?>44;margin-top:4px">
        ● <?= statusLabel($ultima['status']) ?>
      </span>
    </div>
    <?php if ($ultima['valor_orcamento']): ?>
    <div><span style="color:rgba(220,200,255,0.5)">Orçamento</span><br><strong style="color:#b84fff">R$ <?= number_format($ultima['valor_orcamento'],2,',','.') ?></strong></div>
    <?php endif; ?>
    <?php if ($ultima['valor_final']): ?>
    <div><span style="color:rgba(220,200,255,0.5)">Valor final</span><br><strong style="color:#10b981">R$ <?= number_format($ultima['valor_final'],2,',','.') ?></strong></div>
    <?php endif; ?>
  </div>
  <!-- Barra de progresso -->
  <?php
  $steps  = ['aguardando','em_analise','em_reparo','pronto','entregue'];
  $idx    = array_search($ultima['status'], $steps);
  $idx    = ($idx === false) ? 0 : $idx;
  $pct    = ($ultima['status'] === 'cancelado') ? 0 : round(($idx / (count($steps)-1)) * 100);
  ?>
  <div style="margin-top:20px">
    <div style="display:flex;justify-content:space-between;font-size:11px;color:rgba(220,200,255,0.4);margin-bottom:6px">
      <span>Aguardando</span><span>Análise</span><span>Reparo</span><span>Pronto</span><span>Entregue</span>
    </div>
    <div style="background:rgba(255,255,255,0.06);border-radius:99px;height:6px;overflow:hidden">
      <div style="background:linear-gradient(90deg,#7c3aed,#b84fff);height:100%;border-radius:99px;width:<?= $pct ?>%;transition:width .5s"></div>
    </div>
  </div>
</div>
<?php else: ?>
<div class="card" style="text-align:center;padding:48px 24px">
  <div style="font-size:40px;margin-bottom:12px">📱</div>
  <p style="color:rgba(220,200,255,0.5);margin-bottom:20px">Você ainda não tem nenhuma ordem de serviço.</p>
  <a href="<?= SITE_URL ?>/php/pages/cliente/novo_orcamento.php" class="btn-primary">
    + Solicitar orçamento
  </a>
</div>
<?php endif; ?>

<div style="display:flex;gap:12px;flex-wrap:wrap">
  <a href="<?= SITE_URL ?>/php/pages/cliente/novo_orcamento.php" class="btn-primary">+ Novo orçamento</a>
  <a href="<?= SITE_URL ?>/php/pages/cliente/minhas_ordens.php"   class="btn-ghost">Ver todas as ordens</a>
  <a href="<?= SITE_URL ?>/php/pages/cliente/delivery.php"         class="btn-ghost">🚚 Delivery / Retirada</a>
</div>

<?php require_once __DIR__ . '/../../../php/includes/cliente_footer.php'; ?>
