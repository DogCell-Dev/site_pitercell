<?php
// ── php/login.php ────────────────────────────────────────────
require_once __DIR__ . '/../includes/config.php';

// Se já logado, redireciona
if (isLoggedAdmin())   redirect('cliente/dashboard.php');
if (isLoggedCliente()) redirect('cliente/dashboard.php');

$erro = '';
$modo = $_POST['modo'] ?? $_GET['modo'] ?? 'cliente'; // 'cliente' ou 'admin'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    // $modo já foi capturado acima

    if ($modo === 'admin') {
        // ── Login Admin ──────────────────────────────────────
        $stmt = db()->prepare("SELECT id, nome, senha_hash FROM admins WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($senha, $admin['senha_hash'])) {
            session_regenerate_id(true);
            $_SESSION['admin_id']   = $admin['id'];
            $_SESSION['admin_nome'] = $admin['nome'];
            redirect('tecnico.php');
        } else {
            $erro = 'E-mail ou senha incorretos.';
        }
    } else {
        // ── Login Cliente ────────────────────────────────────
        $stmt = db()->prepare("
            SELECT u.id, u.cliente_id, u.senha_hash, c.nome
            FROM usuarios u
            JOIN clientes c ON c.id = u.cliente_id
            WHERE u.email = ? AND u.ativo = 1
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($senha, $user['senha_hash'])) {
            session_regenerate_id(true);
            $_SESSION['usuario_id']    = $user['id'];
            $_SESSION['cliente_id']    = $user['cliente_id'];
            $_SESSION['cliente_nome']  = $user['nome'];
            redirect('cliente/dashboard.php');
        } else {
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>Entrar — PiterCell</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@900&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{background:#0d0b14;font-family:'Space Grotesk',sans-serif;color:#f0eeff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
.card{background:rgba(255,255,255,0.03);border:1px solid rgba(184,79,255,0.25);border-radius:20px;padding:40px 36px;width:100%;max-width:420px}
.logo-wrap{text-align:center;margin-bottom:28px}
.logo-wrap img{height:56px;width:auto}
h1{font-family:'Orbitron',monospace;font-size:20px;font-weight:900;text-align:center;margin-bottom:4px}
h1 em{font-style:normal;color:#b84fff}
.subtitle{text-align:center;font-size:13px;color:rgba(220,200,255,0.5);margin-bottom:28px}
/* tabs */
.tabs{display:flex;border:1px solid rgba(184,79,255,0.2);border-radius:10px;margin-bottom:28px;overflow:hidden}
.tab{flex:1;padding:10px;text-align:center;font-size:13px;font-weight:600;cursor:pointer;background:transparent;border:none;color:rgba(220,200,255,0.5);font-family:'Space Grotesk',sans-serif;transition:all .2s}
.tab.active{background:rgba(184,79,255,0.15);color:#b84fff}
label{display:block;font-size:12px;font-weight:600;color:rgba(220,200,255,0.55);letter-spacing:.5px;text-transform:uppercase;margin-bottom:6px}
input{width:100%;background:rgba(255,255,255,0.04);border:1px solid rgba(184,79,255,0.2);border-radius:10px;padding:11px 14px;color:#f0eeff;font-size:14px;font-family:'Space Grotesk',sans-serif;outline:none;margin-bottom:16px;transition:border-color .2s}
input:focus{border-color:#b84fff}
.btn{width:100%;background:#b84fff;color:#0d0b14;border:none;border-radius:10px;padding:13px;font-size:14px;font-weight:700;cursor:pointer;font-family:'Space Grotesk',sans-serif;display:flex;align-items:center;justify-content:center;gap:8px}
.btn:hover{background:#a040e0}
.erro{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);border-radius:8px;padding:10px 14px;font-size:13px;color:#f87171;margin-bottom:16px;text-align:center}
.link-row{text-align:center;margin-top:20px;font-size:13px;color:rgba(220,200,255,0.5)}
.link-row a{color:#b84fff;text-decoration:none;font-weight:600}
.back{position:fixed;top:20px;left:20px;background:rgba(255,255,255,0.04);border:1px solid rgba(184,79,255,0.2);border-radius:8px;padding:8px 14px;font-size:13px;color:rgba(220,200,255,0.6);text-decoration:none;font-family:'Space Grotesk',sans-serif}
</style>
</head>
<body>
<a href="../../index.html" class="back">← Voltar</a>

<div class="card">
  <div class="logo-wrap">
    <img src="../img/logo_pitercell.jpg" alt="PiterCell"/>
  </div>
  <h1>Central de <em>Controle</em></h1>
  <p class="subtitle" id="subtitle-text">
    <?= $modo === 'admin' ? 'Acesso restrito ao técnico' : 'Acompanhe seus reparos' ?>
  </p>

  <!-- Tabs -->
  <div class="tabs">
    <button class="tab <?= $modo !== 'admin' ? 'active' : '' ?>"
            onclick="setModo('cliente')" type="button">Cliente</button>
    <button class="tab <?= $modo === 'admin' ? 'active' : '' ?>"
            onclick="setModo('admin')" type="button">Técnico / Admin</button>
  </div>

  <?php if ($erro): ?>
  <div class="erro"><?= h($erro) ?></div>
  <?php endif; ?>

  <form method="POST">
    <input type="hidden" name="modo" id="input-modo" value="<?= h($modo) ?>">

    <label>E-mail</label>
    <input type="email" name="email" placeholder="seu@email.com"
           value="<?= h($_POST['email'] ?? '') ?>" required autocomplete="email"/>

    <label>Senha</label>
    <input type="password" name="senha" placeholder="••••••••" required autocomplete="current-password"/>

    <button type="submit" class="btn">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
      Entrar
    </button>
  </form>

  <div class="link-row" id="link-cadastro">
    Não tem conta? <a href="cliente/cadastro.php">Cadastre-se grátis</a>
  </div>
</div>

<script>
function setModo(m) {
  document.getElementById('input-modo').value = m;
  document.querySelectorAll('.tab').forEach((t,i) => {
    t.classList.toggle('active', (m==='cliente'&&i===0)||(m==='admin'&&i===1));
  });
  document.getElementById('subtitle-text').textContent =
    m === 'admin' ? 'Acesso restrito ao técnico' : 'Acompanhe seus reparos';
  document.getElementById('link-cadastro').style.display =
    m === 'admin' ? 'none' : 'block';
}
</script>
</body>
</html>
