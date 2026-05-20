<?php
require_once '../includes/config.php';
require_once '../includes/layout.php';
requireLogin();
$db = getDB();

// Salvar OS
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'salvar_os') {
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $db->prepare("UPDATE ordens SET cliente_id=:cid,dispositivo=:disp,modelo=:modelo,sistema=:sis,problema=:prob,servico=:serv,valor_orcamento=:vorc,valor_final=:vfin,status=:status,observacoes=:obs WHERE id=:id")
               ->execute([
                   ':cid'=>intval($_POST['cliente_id']),':disp'=>$_POST['dispositivo'],
                   ':modelo'=>$_POST['modelo'],':sis'=>$_POST['sistema'],
                   ':prob'=>$_POST['problema'],':serv'=>$_POST['servico'],
                   ':vorc'=>$_POST['valor_orcamento']?:null,':vfin'=>$_POST['valor_final']?:null,
                   ':status'=>$_POST['status'],':obs'=>$_POST['observacoes'],':id'=>$id
               ]);
        } else {
            $ultimo = $db->query("SELECT MAX(id) as m FROM ordens")->fetchColumn();
            $numero = 'OS-'.str_pad(($ultimo+1),4,'0',STR_PAD_LEFT);
            $db->prepare("INSERT INTO ordens (cliente_id,numero,dispositivo,modelo,sistema,problema,servico,valor_orcamento,status,observacoes) VALUES (:cid,:num,:disp,:modelo,:sis,:prob,:serv,:vorc,:status,:obs)")
               ->execute([
                   ':cid'=>intval($_POST['cliente_id']),':num'=>$numero,
                   ':disp'=>$_POST['dispositivo'],':modelo'=>$_POST['modelo'],
                   ':sis'=>$_POST['sistema'],':prob'=>$_POST['problema'],
                   ':serv'=>$_POST['servico'],':vorc'=>$_POST['valor_orcamento']?:null,
                   ':status'=>'aguardando',':obs'=>$_POST['observacoes']
               ]);
        }
        header('Location: ordens.php'); exit;
    }

    if ($acao === 'atualizar_status') {
        $osId = intval($_POST['os_id']);
        $ant  = $db->prepare("SELECT status FROM ordens WHERE id=?")->execute([$osId]) ? null : null;
        $r    = $db->prepare("SELECT status FROM ordens WHERE id=?"); $r->execute([$osId]); $ant = $r->fetchColumn();
        $db->prepare("UPDATE ordens SET status=? WHERE id=?")->execute([$_POST['status_novo'], $osId]);
        $db->prepare("INSERT INTO historico (ordem_id,status_ant,status_novo,nota) VALUES (?,?,?,?)")
           ->execute([$osId,$ant,$_POST['status_novo'],trim($_POST['nota']??'')]);
        header('Location: ordens.php?id='.$osId); exit;
    }
}

// Detalhe
$detalhe = null;
if (isset($_GET['id'])) {
    $stmt = $db->prepare("SELECT o.*,c.nome as cliente_nome,c.telefone,c.email FROM ordens o JOIN clientes c ON c.id=o.cliente_id WHERE o.id=?");
    $stmt->execute([intval($_GET['id'])]);
    $detalhe = $stmt->fetch();
    $historico = $db->prepare("SELECT * FROM historico WHERE ordem_id=? ORDER BY criado_em DESC"); $historico->execute([$detalhe['id']]); $historico=$historico->fetchAll();
}

// Lista
$busca = trim($_GET['q'] ?? '');
$filtroStatus = $_GET['status'] ?? '';
$sql = "SELECT o.*,c.nome as cliente_nome FROM ordens o JOIN clientes c ON c.id=o.cliente_id WHERE 1=1";
$params = [];
if ($busca)        { $sql .= " AND (o.numero LIKE :q OR c.nome LIKE :q OR o.modelo LIKE :q)"; $params[':q']="%$busca%"; }
if ($filtroStatus) { $sql .= " AND o.status=:st"; $params[':st']=$filtroStatus; }
$sql .= " ORDER BY o.criado_em DESC";
$stmt = $db->prepare($sql); $stmt->execute($params); $ordens=$stmt->fetchAll();

$todoClientes = $db->query("SELECT id,nome FROM clientes ORDER BY nome")->fetchAll();
$novoModal = isset($_GET['novo']);
$editOS = null;
if (isset($_GET['editar'])) { $s=$db->prepare("SELECT * FROM ordens WHERE id=?"); $s->execute([intval($_GET['editar'])]); $editOS=$s->fetch(); }
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
<?php renderSidebar('ordens'); ?>
<div class="main">
  <div class="topbar">
    <span class="topbar-title">Ordens de Serviço</span>
    <div class="topbar-right"><span class="topbar-badge">Pitercell Admin</span></div>
  </div>
  <div class="page">

  <?php if ($detalhe): ?>
    <!-- DETALHE DA OS -->
    <div class="page-header">
      <h1><em><?= htmlspecialchars($detalhe['numero']) ?></em></h1>
      <div style="display:flex;gap:8px">
        <a href="?editar=<?= $detalhe['id'] ?>" class="btn btn-ghost">Editar OS</a>
        <?php
          $waMSG = urlencode("*Pitercell — Atualização da sua OS*\n\nOlá {$detalhe['cliente_nome']}! 👋\n\nSua OS *{$detalhe['numero']}* ({$detalhe['dispositivo']} {$detalhe['modelo']}) está com status: *".statusLabel($detalhe['status'])."*.\n\n".($detalhe['valor_orcamento']?"💰 Orçamento: R$".number_format($detalhe['valor_orcamento'],2,',','.').".\n":"")."Qualquer dúvida estamos à disposição!\n\n_Pitercell — Seu celular novo de novo_ 🔧");
        ?>
        <a href="https://wa.me/<?= $detalhe['telefone'] ?>?text=<?= $waMSG ?>" target="_blank" class="btn btn-green">
          <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.41 2 2 0 0 1 3.6 1.21h3a2 2 0 0 1 2 1.72 19.86 19.86 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.96a16 16 0 0 0 6 6l.92-.92a2 2 0 0 1 2.11-.45 19.86 19.86 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
          Enviar WhatsApp
        </a>
        <a href="ordens.php" class="btn btn-ghost">← Voltar</a>
      </div>
    </div>

    <div class="detail-grid">
      <div>
        <div class="detail-card" style="margin-bottom:16px">
          <h3>Informações da OS</h3>
          <div class="detail-row"><span>Status</span><span><span class="status-badge <?= statusClass($detalhe['status']) ?>"><?= statusLabel($detalhe['status']) ?></span></span></div>
          <div class="detail-row"><span>Cliente</span><span><?= htmlspecialchars($detalhe['cliente_nome']) ?></span></div>
          <div class="detail-row"><span>Telefone</span><span><?= htmlspecialchars($detalhe['telefone']) ?></span></div>
          <div class="detail-row"><span>Dispositivo</span><span><?= htmlspecialchars($detalhe['dispositivo'].' '.$detalhe['modelo']) ?></span></div>
          <div class="detail-row"><span>Sistema</span><span><?= strtoupper($detalhe['sistema']) ?></span></div>
          <div class="detail-row"><span>Serviço</span><span><?= htmlspecialchars($detalhe['servico']??'—') ?></span></div>
          <div class="detail-row"><span>Problema</span><span><?= htmlspecialchars($detalhe['problema']) ?></span></div>
          <div class="detail-row"><span>Orçamento</span><span style="color:#b84fff;font-weight:700"><?= $detalhe['valor_orcamento'] ? 'R$'.number_format($detalhe['valor_orcamento'],2,',','.') : '—' ?></span></div>
          <div class="detail-row"><span>Valor Final</span><span style="color:#00c864;font-weight:700"><?= $detalhe['valor_final'] ? 'R$'.number_format($detalhe['valor_final'],2,',','.') : '—' ?></span></div>
          <?php if($detalhe['observacoes']): ?><div class="detail-row"><span>Obs.</span><span><?= htmlspecialchars($detalhe['observacoes']) ?></span></div><?php endif; ?>
          <div class="detail-row"><span>Criado em</span><span><?= date('d/m/Y H:i', strtotime($detalhe['criado_em'])) ?></span></div>
        </div>

        <!-- Atualizar status -->
        <div class="detail-card">
          <h3>Atualizar Status</h3>
          <form method="POST" style="display:flex;flex-direction:column;gap:12px;margin-top:4px">
            <input type="hidden" name="acao" value="atualizar_status">
            <input type="hidden" name="os_id" value="<?= $detalhe['id'] ?>">
            <div class="form-group">
              <label>Novo status</label>
              <select name="status_novo">
                <?php foreach($statusOpts as $v=>$l): ?>
                  <option value="<?= $v ?>" <?= $detalhe['status']===$v?'selected':'' ?>><?= $l ?></option>
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
            <?php foreach($historico as $h): ?>
            <div class="history-item">
              <div class="history-dot"></div>
              <div class="history-body">
                <p>
                  <span class="status-badge <?= statusClass($h['status_ant']) ?>" style="font-size:10px"><?= statusLabel($h['status_ant']??'') ?></span>
                  → <span class="status-badge <?= statusClass($h['status_novo']) ?>" style="font-size:10px"><?= statusLabel($h['status_novo']) ?></span>
                </p>
                <?php if($h['nota']): ?><p style="margin-top:4px;font-size:12px;color:var(--muted)"><?= htmlspecialchars($h['nota']) ?></p><?php endif; ?>
                <span><?= date('d/m/Y H:i', strtotime($h['criado_em'])) ?></span>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

  <?php else: ?>
    <!-- LISTA DE OS -->
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
            <?php foreach($statusOpts as $v=>$l): ?><option value="<?=$v?>" <?=$filtroStatus===$v?'selected':''?>><?=$l?></option><?php endforeach; ?>
          </select>
          <button type="submit" class="btn btn-ghost btn-sm">Filtrar</button>
        </form>
      </div>
      <table>
        <thead><tr><th>Nº OS</th><th>Cliente</th><th>Dispositivo</th><th>Serviço</th><th>Status</th><th>Orçamento</th><th>Data</th><th>Ações</th></tr></thead>
        <tbody>
        <?php if(empty($ordens)): ?>
          <tr><td colspan="8"><div class="empty-state"><svg viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/></svg><p>Nenhuma OS encontrada</p></div></td></tr>
        <?php else: ?>
          <?php foreach($ordens as $o): ?>
          <tr>
            <td><strong><?= htmlspecialchars($o['numero']) ?></strong></td>
            <td><div class="td-name"><?= htmlspecialchars($o['cliente_nome']) ?></div></td>
            <td><?= htmlspecialchars($o['dispositivo'].' '.$o['modelo']) ?></td>
            <td><?= htmlspecialchars($o['servico']??'—') ?></td>
            <td><span class="status-badge <?= statusClass($o['status']) ?>"><?= statusLabel($o['status']) ?></span></td>
            <td><?= $o['valor_orcamento']?'R$'.number_format($o['valor_orcamento'],2,',','.'):'—' ?></td>
            <td><?= date('d/m/Y',strtotime($o['criado_em'])) ?></td>
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
<div class="modal-overlay <?= ($novoModal||$editOS)?'':'hidden' ?>" id="modalOS">
  <div class="modal">
    <button class="modal-close" onclick="document.getElementById('modalOS').classList.add('hidden')">✕</button>
    <h2><?= $editOS?'Editar':'Nova' ?> <em>Ordem de Serviço</em></h2>
    <form method="POST">
      <input type="hidden" name="acao" value="salvar_os">
      <input type="hidden" name="id" value="<?= $editOS['id']??'' ?>">
      <div class="form-grid">
        <div class="form-group form-full">
          <label>Cliente</label>
          <select name="cliente_id" required>
            <option value="">Selecione o cliente...</option>
            <?php foreach($todoClientes as $cl): ?>
              <option value="<?=$cl['id']?>" <?=($editOS['cliente_id']??'')==$cl['id']?'selected':''?>><?=htmlspecialchars($cl['nome'])?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Dispositivo</label>
          <input type="text" name="dispositivo" required placeholder="Ex: iPhone, Samsung" value="<?=htmlspecialchars($editOS['dispositivo']??'')?>">
        </div>
        <div class="form-group">
          <label>Modelo</label>
          <input type="text" name="modelo" placeholder="Ex: 13 Pro, Galaxy A54" value="<?=htmlspecialchars($editOS['modelo']??'')?>">
        </div>
        <div class="form-group">
          <label>Sistema</label>
          <select name="sistema">
            <option value="android" <?=($editOS['sistema']??'')==='android'?'selected':''?>>Android</option>
            <option value="ios" <?=($editOS['sistema']??'')==='ios'?'selected':''?>>iOS (Apple)</option>
            <option value="outro" <?=($editOS['sistema']??'')==='outro'?'selected':''?>>Outro</option>
          </select>
        </div>
        <div class="form-group">
          <label>Serviço</label>
          <input type="text" name="servico" placeholder="Ex: Troca de Tela" value="<?=htmlspecialchars($editOS['servico']??'')?>">
        </div>
        <div class="form-group form-full">
          <label>Problema relatado</label>
          <textarea name="problema" required placeholder="Descreva o problema..."><?=htmlspecialchars($editOS['problema']??'')?></textarea>
        </div>
        <div class="form-group">
          <label>Valor Orçamento (R$)</label>
          <input type="number" step="0.01" name="valor_orcamento" placeholder="0.00" value="<?=$editOS['valor_orcamento']??''?>">
        </div>
        <div class="form-group">
          <label>Valor Final (R$)</label>
          <input type="number" step="0.01" name="valor_final" placeholder="0.00" value="<?=$editOS['valor_final']??''?>">
        </div>
        <?php if($editOS): ?>
        <div class="form-group">
          <label>Status</label>
          <select name="status">
            <?php foreach($statusOpts as $v=>$l): ?><option value="<?=$v?>" <?=$editOS['status']===$v?'selected':''?>><?=$l?></option><?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>
        <div class="form-group form-full">
          <label>Observações internas</label>
          <textarea name="observacoes" placeholder="Notas internas..."><?=htmlspecialchars($editOS['observacoes']??'')?></textarea>
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
<script>document.addEventListener('keydown',e=>{if(e.key==='Escape')document.getElementById('modalOS').classList.add('hidden')});</script>
</body>
</html>
