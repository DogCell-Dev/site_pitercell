<?php
// ── assets/pages/cliente/perfil.php ─────────────────────────
require_once __DIR__ . '/../../includes/config.php';
requireCliente();

$cid   = $_SESSION['cliente_id'];
$erro  = '';
$sucesso = '';

// ── POST: salvar dados ────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome']     ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email    = trim($_POST['email']    ?? '');

    if (!$nome || !$telefone || !$email) {
        $erro = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } else {
        // Verifica e-mail duplicado (outro usuário)
        $st = db()->prepare("SELECT u.id FROM usuarios u WHERE u.email = ? AND u.cliente_id != ? LIMIT 1");
        $st->execute([$email, $cid]);
        if ($st->fetch()) {
            $erro = 'Este e-mail já está em uso por outra conta.';
        } else {
            db()->prepare("UPDATE clientes SET nome=?, telefone=?, email=? WHERE id=?")
               ->execute([$nome, $telefone, $email, $cid]);
            db()->prepare("UPDATE usuarios SET email=? WHERE cliente_id=?")
               ->execute([$email, $cid]);

            // Atualiza nome na sessão
            $_SESSION['cliente_nome'] = $nome;
            $sucesso = 'Dados atualizados com sucesso!';
        }
    }
}

// ── Carrega dados atuais ──────────────────────────────────────
$st = db()->prepare("SELECT c.nome, c.telefone, c.email, c.cpf, c.criado_em
                     FROM clientes c WHERE c.id = ?");
$st->execute([$cid]);
$cliente = $st->fetch();

// ── Resumo de ordens ─────────────────────────────────────────
$st = db()->prepare("SELECT
    COUNT(*) AS total,
    SUM(status NOT IN ('entregue','cancelado')) AS em_aberto,
    SUM(status = 'entregue') AS concluidas
    FROM ordens WHERE cliente_id = ?");
$st->execute([$cid]);
$resumo = $st->fetch();

$pageTitle  = 'Meu Perfil';
$activePage = 'perfil';
require_once __DIR__ . '/../../includes/cliente_header.php';
?>

<h1 class="page-title">Meu <em>Perfil</em></h1>
<p class="page-sub">Gerencie seus dados cadastrais</p>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;align-items:start">

  <!-- Coluna esquerda: avatar + resumo -->
  <div style="display:flex;flex-direction:column;gap:16px">

    <!-- Avatar card -->
    <div class="card" style="text-align:center;padding:32px 24px">
      <div style="width:80px;height:80px;border-radius:50%;background:rgba(184,79,255,0.15);border:2px solid rgba(184,79,255,0.3);display:flex;align-items:center;justify-content:center;margin:0 auto 16px;font-size:32px">
        <?= mb_strtoupper(mb_substr($cliente['nome'], 0, 1, 'UTF-8'), 'UTF-8') ?>
      </div>
      <div style="font-size:17px;font-weight:700;color:#f0eeff;margin-bottom:4px">
        <?= h($cliente['nome']) ?>
      </div>
      <div style="font-size:13px;color:rgba(220,200,255,0.5)">
        <?= h($cliente['email']) ?>
      </div>
      <div style="font-size:11px;color:rgba(220,200,255,0.3);margin-top:8px">
        Cliente desde <?= date('m/Y', strtotime($cliente['criado_em'])) ?>
      </div>
    </div>

    <!-- Resumo de ordens -->
    <div class="card">
      <p class="section-title" style="margin-bottom:14px">Resumo de ordens</p>
      <div style="display:flex;flex-direction:column;gap:10px">
        <?php foreach ([
          ['Total de OS',   $resumo['total'],      '#b84fff'],
          ['Em aberto',     $resumo['em_aberto'],  '#f59e0b'],
          ['Concluídas',    $resumo['concluidas'], '#10b981'],
        ] as [$label, $val, $cor]): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);border-radius:10px">
          <span style="font-size:13px;color:rgba(220,200,255,0.6)"><?= $label ?></span>
          <span style="font-family:'Orbitron',monospace;font-size:16px;font-weight:900;color:<?= $cor ?>">
            <?= (int)$val ?>
          </span>
        </div>
        <?php endforeach; ?>
      </div>
      <a href="minhas_ordens.php" class="btn-ghost" style="display:flex;justify-content:center;margin-top:14px;font-size:13px">
        Ver todas as ordens
      </a>
    </div>

  </div>

  <!-- Coluna direita: formulário -->
  <div class="card">
    <p class="section-title" style="margin-bottom:20px">Editar dados</p>

    <?php if ($erro):    ?><div class="erro"><?= h($erro) ?></div><?php endif; ?>
    <?php if ($sucesso): ?><div class="ok"><?= h($sucesso) ?></div><?php endif; ?>

    <form method="POST">

      <div class="row2" style="margin-bottom:0">
        <div class="field">
          <label>Nome completo *</label>
          <input type="text" name="nome"
                 value="<?= h($cliente['nome']) ?>"
                 placeholder="Seu nome completo" required/>
        </div>
        <div class="field">
          <label>Telefone *</label>
          <input type="tel" name="telefone"
                 value="<?= h($cliente['telefone']) ?>"
                 placeholder="(11) 99999-0000" required/>
        </div>
      </div>

      <label style="margin-top:4px">E-mail *</label>
      <input type="email" name="email"
             value="<?= h($cliente['email']) ?>"
             placeholder="seu@email.com" required/>

      <?php if ($cliente['cpf']): ?>
      <label>CPF</label>
      <input type="text" value="<?= h($cliente['cpf']) ?>" disabled
             style="opacity:.5;cursor:not-allowed"/>
      <p style="font-size:11px;color:rgba(220,200,255,0.35);margin-top:-12px;margin-bottom:16px">
        O CPF não pode ser alterado. Entre em contato com o suporte se necessário.
      </p>
      <?php endif; ?>

      <div style="display:flex;gap:12px;margin-top:8px">
        <button type="submit" class="btn-primary">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
            <polyline points="17 21 17 13 7 13 7 21"/>
            <polyline points="7 3 7 8 15 8"/>
          </svg>
          Salvar alterações
        </button>
        <a href="dashboard.php" class="btn-ghost">Cancelar</a>
      </div>
    </form>
  </div>

</div>

<?php require_once __DIR__ . '/../../includes/cliente_footer.php'; ?>
