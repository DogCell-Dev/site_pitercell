<?php
// ── php/pages/cliente/minhas_ordens.php ─────────────────────
require_once __DIR__ . '/../../../php/config.php';
$pageTitle  = 'Minhas Ordens';
$activePage = 'ordens';
require_once __DIR__ . '/../../../php/includes/cliente_header.php';

$cid = $_SESSION['cliente_id'];

$st = db()->prepare("
    SELECT o.*, dt.nome AS dispositivo_nome,
           (SELECT COUNT(*) FROM mensagens m
            WHERE m.ordem_id = o.id AND m.remetente = 'admin' AND m.lida = 0) AS msgs_novas
    FROM ordens o
    LEFT JOIN dispositivos_tipos dt ON dt.id = o.dispositivo_id
    WHERE o.cliente_id = ?
    ORDER BY o.criado_em DESC
");
$st->execute([$cid]);
$ordens = $st->fetchAll();
?>

<h1 class="page-title">Minhas <em>Ordens</em></h1>
<p class="page-sub">Acompanhe o status de todos os seus reparos</p>

<div style="display:flex;justify-content:flex-end;margin-bottom:20px">
  <a href="<?= SITE_URL ?>/php/pages/cliente/novo_orcamento.php" class="btn-primary">+ Novo orçamento</a>
</div>

<?php if (!$ordens): ?>
<div class="card" style="text-align:center;padding:48px 24px">
  <div style="font-size:40px;margin-bottom:12px">📋</div>
  <p style="color:rgba(220,200,255,0.5)">Nenhuma ordem encontrada.</p>
</div>
<?php else: ?>

<div class="card" style="padding:0;overflow:hidden">
  <table style="width:100%;border-collapse:collapse;font-size:13px">
    <thead>
      <tr style="background:rgba(184,79,255,0.06);border-bottom:1px solid rgba(184,79,255,0.12)">
        <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;letter-spacing:.8px;color:rgba(220,200,255,0.45);text-transform:uppercase">Nº OS</th>
        <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;letter-spacing:.8px;color:rgba(220,200,255,0.45);text-transform:uppercase">Dispositivo</th>
        <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;letter-spacing:.8px;color:rgba(220,200,255,0.45);text-transform:uppercase">Serviço</th>
        <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;letter-spacing:.8px;color:rgba(220,200,255,0.45);text-transform:uppercase">Orçamento</th>
        <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;letter-spacing:.8px;color:rgba(220,200,255,0.45);text-transform:uppercase">Status</th>
        <th style="padding:12px 16px;text-align:left;font-size:11px;font-weight:700;letter-spacing:.8px;color:rgba(220,200,255,0.45);text-transform:uppercase">Data</th>
        <th style="padding:12px 16px;text-align:center;font-size:11px;font-weight:700;letter-spacing:.8px;color:rgba(220,200,255,0.45);text-transform:uppercase">Ações</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach ($ordens as $o): $cor = statusColor($o['status']); ?>
      <tr style="border-bottom:1px solid rgba(184,79,255,0.07);transition:background .15s" onmouseover="this.style.background='rgba(184,79,255,0.04)'" onmouseout="this.style.background=''">
        <td style="padding:14px 16px;font-weight:700;color:#f0eeff"><?= h($o['numero']) ?></td>
        <td style="padding:14px 16px;color:rgba(220,200,255,0.8)">
          <?= h($o['dispositivo_nome'] ?? '—') ?>
          <?php if ($o['modelo']): ?><br><span style="font-size:11px;color:rgba(220,200,255,0.4)"><?= h($o['modelo']) ?></span><?php endif; ?>
        </td>
        <td style="padding:14px 16px;color:rgba(220,200,255,0.7)"><?= h($o['servico'] ?? '—') ?></td>
        <td style="padding:14px 16px;font-weight:700;color:#b84fff">
          <?= $o['valor_orcamento'] ? 'R$ '.number_format($o['valor_orcamento'],2,',','.') : '<span style="color:rgba(220,200,255,0.35)">Aguardando</span>' ?>
        </td>
        <td style="padding:14px 16px">
          <span class="badge" style="background:<?= $cor ?>22;color:<?= $cor ?>;border:1px solid <?= $cor ?>44">
            ● <?= statusLabel($o['status']) ?>
          </span>
        </td>
        <td style="padding:14px 16px;color:rgba(220,200,255,0.5);font-size:12px">
          <?= date('d/m/Y', strtotime($o['criado_em'])) ?>
        </td>
        <td style="padding:14px 16px;text-align:center">
          <a href="<?= SITE_URL ?>/php/pages/cliente/ordem_detalhe.php?id=<?= $o['id'] ?>"
             class="btn-ghost" style="padding:6px 14px;font-size:12px">
            <?php if ($o['msgs_novas'] > 0): ?>
              💬 <span style="color:#b84fff"><?= $o['msgs_novas'] ?></span> &nbsp;
            <?php endif; ?>
            Detalhes
          </a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../../../php/includes/cliente_footer.php'; ?>
