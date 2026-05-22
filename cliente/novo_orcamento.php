<?php
// ── php/pages/cliente/novo_orcamento.php ────────────────────
require_once __DIR__ . '/../../../php/config.php';
$pageTitle  = 'Solicitar Orçamento';
$activePage = 'orcamento';
require_once __DIR__ . '/../../../php/includes/cliente_header.php';

$cid  = $_SESSION['cliente_id'];
$erro = $sucesso = '';

// Carrega tipos de dispositivo
$tipos = db()->query("SELECT id, nome FROM dispositivos_tipos ORDER BY nome")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dispId   = (int)($_POST['dispositivo_id'] ?? 0);
    $modelo   = trim($_POST['modelo']   ?? '');
    $sistema  = $_POST['sistema']        ?? 'android';
    $problema = trim($_POST['problema'] ?? '');
    $servico  = trim($_POST['servico']  ?? '');

    if (!$dispId || !$problema) {
        $erro = 'Preencha o dispositivo e descreva o problema.';
    } else {
        $numero = gerarNumeroOS();
        $st = db()->prepare("
            INSERT INTO ordens (cliente_id, numero, dispositivo_id, modelo, sistema, problema, servico, status, origem)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'aguardando', 'cliente')
        ");
        $st->execute([$cid, $numero, $dispId, $modelo ?: null, $sistema, $problema, $servico ?: null]);
        $ordemId = (int)db()->lastInsertId();

        // Histórico inicial
        $st = db()->prepare("INSERT INTO historico (ordem_id, status_ant, status_novo, nota, feito_por) VALUES (?,NULL,'aguardando','Ordem criada pelo cliente.','cliente')");
        $st->execute([$ordemId]);

        $sucesso = "Orçamento $numero enviado! Aguarde a análise do técnico.";
    }
}
?>

<h1 class="page-title">Solicitar <em>Orçamento</em></h1>
<p class="page-sub">Descreva o problema do seu aparelho e enviaremos um orçamento em breve.</p>

<?php if ($erro):    ?><div class="erro"><?= h($erro) ?></div><?php endif; ?>
<?php if ($sucesso): ?>
  <div class="ok"><?= h($sucesso) ?></div>
  <a href="<?= SITE_URL ?>/php/pages/cliente/minhas_ordens.php" class="btn-primary" style="margin-bottom:24px">Ver minhas ordens</a>
<?php else: ?>

<div class="card">
  <form method="POST">

    <p class="section-title">Dados do aparelho</p>
    <div class="row2" style="margin-bottom:0">
      <div class="field">
        <label>Tipo de dispositivo *</label>
        <select name="dispositivo_id" required>
          <option value="">Selecione...</option>
          <?php foreach ($tipos as $t): ?>
          <option value="<?= $t['id'] ?>" <?= ($_POST['dispositivo_id']??'')==$t['id']?'selected':'' ?>>
            <?= h($t['nome']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Modelo</label>
        <input type="text" name="modelo" placeholder="Ex: iPhone 13, Galaxy A54"
               value="<?= h($_POST['modelo'] ?? '') ?>"/>
      </div>
    </div>

    <label>Sistema operacional</label>
    <div style="display:flex;gap:10px;margin-bottom:16px">
      <?php foreach (['android'=>'Android','ios'=>'iOS','outro'=>'Outro'] as $val=>$label): ?>
      <label style="display:flex;align-items:center;gap:6px;font-size:13px;text-transform:none;letter-spacing:0;color:#f0eeff;margin-bottom:0;cursor:pointer">
        <input type="radio" name="sistema" value="<?= $val ?>"
               <?= ($_POST['sistema']??'android')===$val?'checked':'' ?>
               style="width:auto;margin-bottom:0"/>
        <?= $label ?>
      </label>
      <?php endforeach; ?>
    </div>

    <p class="section-title" style="margin-top:8px">Descrição do problema</p>

    <label>Descreva o problema com detalhes *</label>
    <textarea name="problema" placeholder="Ex: A tela rachou após queda, há listras horizontais e o touch não responde no canto inferior..." rows="4" required><?= h($_POST['problema'] ?? '') ?></textarea>

    <label>Serviço desejado (opcional)</label>
    <input type="text" name="servico" placeholder="Ex: Troca de tela, Troca de bateria, Diagnóstico..."
           value="<?= h($_POST['servico'] ?? '') ?>"/>

    <div style="background:rgba(184,79,255,0.06);border:1px solid rgba(184,79,255,0.15);border-radius:10px;padding:14px;margin-bottom:20px;font-size:13px;color:rgba(220,200,255,0.65)">
      ℹ️ Após o envio, o técnico analisará seu pedido e responderá com o valor do orçamento. Você receberá uma notificação aqui no painel.
    </div>

    <button type="submit" class="btn-primary">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
      Enviar solicitação
    </button>
  </form>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../../../php/includes/cliente_footer.php'; ?>
