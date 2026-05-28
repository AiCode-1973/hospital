<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

// Já logado → redireciona
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$user  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        $error = 'Token de segurança inválido. Recarregue a página.';
    } else {
        $user = trim($_POST['usuario'] ?? '');
        $pass = $_POST['senha'] ?? '';

        if ($user === '' || $pass === '') {
            $error = 'Preencha usuário e senha.';
        } else {
            try {
                $pdo  = getDB();
                $stmt = $pdo->prepare(
                    'SELECT id, nome, senha_hash FROM admin_usuarios WHERE usuario = ? AND ativo = 1 LIMIT 1'
                );
                $stmt->execute([$user]);
                $row = $stmt->fetch();

                if ($row && password_verify($pass, $row['senha_hash'])) {
                    session_regenerate_id(true);
                    $_SESSION['admin_id']   = $row['id'];
                    $_SESSION['admin_nome'] = $row['nome'];

                    $pdo->prepare('UPDATE admin_usuarios SET ultimo_acesso = NOW() WHERE id = ?')
                        ->execute([$row['id']]);

                    header('Location: index.php');
                    exit;
                } else {
                    $error = 'Usuário ou senha incorretos.';
                }
            } catch (\PDOException $e) {
                error_log('Admin login error: ' . $e->getMessage());
                $error = 'Erro ao acessar o banco de dados.';
            }
        }
    }
}

$csrf = csrfToken();
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — HSE Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">
  <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="login-page">

  <div class="login-card">
    <div class="login-logo">
      <img src="../images/hse.png" alt="Logo Hospital Santo Expedito">
      <h2>Área Administrativa</h2>
    </div>

    <h1>Entrar</h1>

    <?php if ($error): ?>
    <div class="flash flash-error">
      <i class="fa-solid fa-circle-exclamation"></i> <?= esc($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" autocomplete="off" novalidate>
      <input type="hidden" name="csrf_token" value="<?= esc($csrf) ?>">

      <div class="form-group">
        <label for="usuario">Usuário</label>
        <input
          type="text"
          id="usuario"
          name="usuario"
          value="<?= esc($user) ?>"
          required
          autofocus
          autocomplete="username"
          maxlength="60"
        >
      </div>

      <div class="form-group">
        <label for="senha">Senha</label>
        <input
          type="password"
          id="senha"
          name="senha"
          required
          autocomplete="current-password"
        >
      </div>

      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-right-to-bracket"></i> Entrar
      </button>
    </form>

    <p class="login-default">
      Credenciais padrão: <strong>admin</strong> / <strong>Admin@2024</strong>
    </p>
  </div>

</body>
</html>
