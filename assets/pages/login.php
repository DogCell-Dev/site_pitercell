<?php
require_once '../includes/config.php';

if ($_SESSION['logado'] ?? false) { 
  header('Location: tecnico.php'); exit; 
  }

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_POST['senha'] === ADMIN_PASS) {
        $_SESSION['logado'] = true;
        header('Location: tecnico.php'); exit;
    }
    $erro = 'Senha incorreta. Tente novamente.';
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login — Pitercell Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Orbitron:wght@700;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/admin.css">
</head>
<body>
  <a href="../../index.html" >← Voltar</a>
<div class="login-wrap">
  <div class="login-box">
    <img src="../img/logo_piterCell.jpg" alt="Pitercell">
    <h2>Central de <em style="color:#b84fff">Controle</em></h2>
    <p>Acesso restrito ao técnico</p>
    <?php if ($erro): ?>
      <div class="login-error"><?= htmlspecialchars($erro) ?></div>
    <?php endif; ?>
    <form method="POST">
      <div class="form-group">
        <label>Senha de acesso</label>
        <input type="password" name="senha" placeholder="••••••••" autofocus>
      </div>
      <button type="submit" class="btn btn-primary">
        <svg viewBox="0 0 24 24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
        Entrar
      </button>
    </form>
  </div>
</div>
</body>
</html>
