<?php
// ── php/pages/cliente/cadastro.php ───────────────────────────
require_once __DIR__ . '/../../includes/config.php';

if (isLoggedCliente()) redirect('dashboard.php');

$erro  = '';
$sucesso = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome     = trim($_POST['nome']     ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $cpf      = trim($_POST['cpf']      ?? '');
    $senha    = $_POST['senha']         ?? '';
    $conf     = $_POST['confirmar']     ?? '';

    // Validações básicas
    if (!$nome || !$telefone || !$email || !$senha) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'E-mail inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter no mínimo 6 caracteres.';
    } elseif ($senha !== $conf) {
        $erro = 'As senhas não coincidem.';
    } else {
        // Verifica e-mail duplicado
        $st = db()->prepare("SELECT id FROM usuarios WHERE email = ? LIMIT 1");
        $st->execute([$email]);
        if ($st->fetch()) {
            $erro = 'Este e-mail já está cadastrado.';
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                // Insere cliente
                $st = $pdo->prepare("INSERT INTO clientes (nome, telefone, email, cpf) VALUES (?,?,?,?)");
                $st->execute([$nome, $telefone, $email, $cpf ?: null]);
                $clienteId = (int)$pdo->lastInsertId();

                // Insere usuário
                $hash = password_hash($senha, PASSWORD_BCRYPT);
                $st = $pdo->prepare("INSERT INTO usuarios (cliente_id, email, senha_hash) VALUES (?,?,?)");
                $st->execute([$clienteId, $email, $hash]);

                $pdo->commit();
                $sucesso = 'Cadastro realizado! Redirecionando...';

                // Login automático
                session_regenerate_id(true);
                $_SESSION['usuario_id']   = (int)$pdo->lastInsertId();
                $_SESSION['cliente_id']   = $clienteId;
                $_SESSION['cliente_nome'] = $nome;

                header('Refresh: 2; url= dashboard.php');
            } catch (Exception $e) {
                $pdo->rollBack();
                $erro = 'Erro ao cadastrar. Tente novamente.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Cadastro — PiterCell</title>
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{background:#0d0b14;font-family:'Space Grotesk',sans-serif;color:#f0eeff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.card{background:rgba(255,255,255,0.03);border:1px solid rgba(184,79,255,0.25);border-radius:20px;padding:40px 36px;width:100%;max-width:460px}
.logo-wrap{text-align:center;margin-bottom:24px}
.logo-wrap img{height:52px}
h1{font-family:'Orbitron',monospace;font-size:19px;font-weight:900;text-align:center;margin-bottom:4px}
h1 em{font-style:normal;color:#b84fff}
.subtitle{text-align:center;font-size:13px;color:rgba(220,200,255,0.5);margin-bottom:28px}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:12px}
label{display:block;font-size:12px;font-weight:600;color:rgba(220,200,255,0.55);letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px}
input{width:100%;background:rgba(255,255,255,0.04);border:1px solid rgba(184,79,255,0.2);border-radius:10px;padding:11px 14px;color:#f0eeff;font-size:14px;font-family:'Space Grotesk',sans-serif;outline:none;margin-bottom:16px;transition:border-color .2s}
input:focus{border-color:#b84fff}
.field{margin-bottom:0}
.btn{width:100%;background:#b84fff;color:#0d0b14;border:none;border-radius:10px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Space Grotesk',sans-serif;margin-top:8px}
.btn:hover{background:#a040e0}
.erro{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:10px 14px;font-size:13px;color:#f87171;margin-bottom:16px;text-align:center}
.ok{background:rgba(16,185,129,0.1);border:1px solid rgba(16,185,129,0.3);border-radius:8px;padding:10px 14px;font-size:13px;color:#34d399;margin-bottom:16px;text-align:center}
.link-row{text-align:center;margin-top:20px;font-size:13px;color:rgba(220,200,255,0.5)}
.link-row a{color:#b84fff;text-decoration:none;font-weight:600}
.back{position:fixed;top:20px;left:20px;background:rgba(255,255,255,0.04);border:1px solid rgba(184,79,255,0.2);border-radius:8px;padding:8px 14px;font-size:13px;color:rgba(220,200,255,0.6);text-decoration:none;font-family:'Space Grotesk',sans-serif}
.obrig{color:#b84fff}
</style>
</head>
<body>
<a href="../login.php" class="back">← Voltar</a>

<div class="card">
  <div class="logo-wrap">
    <img src="../../img/" alt="PiterCell"/>
  </div>
  <h1>Criar <em>Conta</em></h1>
  <p class="subtitle">Cadastre-se para acompanhar seus reparos</p>

  <?php if ($erro):    ?><div class="erro"><?= h($erro) ?></div><?php endif; ?>
  <?php if ($sucesso): ?><div class="ok"><?= h($sucesso) ?></div><?php endif; ?>

  <form method="POST">
    <div class="row2">
      <div class="field">
        <label>Nome completo <span class="obrig">*</span></label>
        <input type="text" name="nome" placeholder="João Silva"
               value="<?= h($_POST['nome'] ?? '') ?>" required/>
      </div>
      <div class="field">
        <label>Telefone <span class="obrig">*</span></label>
        <input type="tel" name="telefone" placeholder="(11) 99999-0000"
               value="<?= h($_POST['telefone'] ?? '') ?>" required/>
      </div>
    </div>

    <div class="row2">
      <div class="field">
        <label>E-mail <span class="obrig">*</span></label>
        <input type="email" name="email" placeholder="seu@email.com"
               value="<?= h($_POST['email'] ?? '') ?>" required/>
      </div>
      <div class="field">
        <label>CPF (opcional)</label>
        <input type="text" name="cpf" placeholder="000.000.000-00"
               value="<?= h($_POST['cpf'] ?? '') ?>" maxlength="14"/>
      </div>
    </div>

    <div class="row2">
      <div class="field">
        <label>Senha <span class="obrig">*</span></label>
        <input type="password" name="senha" placeholder="Mín. 6 caracteres" required/>
      </div>
      <div class="field">
        <label>Confirmar senha <span class="obrig">*</span></label>
        <input type="password" name="confirmar" placeholder="Repita a senha" required/>
      </div>
    </div>

    <button type="submit" class="btn">Criar minha conta</button>
  </form>

  <div class="link-row">
    Já tem conta? <a href="../login.php">Entrar</a>
  </div>
</div>
</body>
</html>
