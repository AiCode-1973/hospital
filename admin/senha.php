<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$pdo    = getDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        die('Token CSRF inválido.');
    }

    $senhaAtual  = $_POST['senha_atual']     ?? '';
    $novaSenha   = $_POST['nova_senha']      ?? '';
    $confirmacao = $_POST['confirmar_senha'] ?? '';

    if ($senhaAtual === '')   $errors[] = 'Informe a senha atual.';
    if ($novaSenha === '')    $errors[] = 'Informe a nova senha.';
    if (strlen($novaSenha) < 8) $errors[] = 'A nova senha deve ter pelo menos 8 caracteres.';
    if ($novaSenha !== $confirmacao) $errors[] = 'A confirmação não confere com a nova senha.';

    if (empty($errors)) {
        $adminId = (int) $_SESSION['admin_id'];
        $stmt = $pdo->prepare('SELECT senha_hash FROM admin_usuarios WHERE id = ?');
        $stmt->execute([$adminId]);
        $row = $stmt->fetch();

        if (!$row || !password_verify($senhaAtual, $row['senha_hash'])) {
            $errors[] = 'Senha atual incorreta.';
        } else {
            $novoHash = password_hash($novaSenha, PASSWORD_BCRYPT);
            $pdo->prepare('UPDATE admin_usuarios SET senha_hash = ? WHERE id = ?')
                ->execute([$novoHash, $adminId]);
            setFlash('success', 'Senha alterada com sucesso!');
            header('Location: senha.php');
            exit;
        }
    }
}

$pageTitle = 'Alterar Senha';
require_once __DIR__ . '/_header.php';
?>

<div class="card" style="max-width:480px;">
  <div class="card-header">
    <h2><i class="fa-solid fa-key"></i> Alterar Senha</h2>
  </div>

  <?php if ($errors): ?>
  <div class="flash flash-error">
    <i class="fa-solid fa-circle-exclamation"></i>
    <?= esc(implode(' &nbsp;|&nbsp; ', $errors)) ?>
  </div>
  <?php endif; ?>

  <form method="POST" novalidate>
    <input type="hidden" name="csrf_token" value="<?= esc($csrf) ?>">

    <div class="form-grid">
      <div class="form-group">
        <label for="senha_atual">Senha atual <span style="color:var(--danger)">*</span></label>
        <input type="password" id="senha_atual" name="senha_atual" required autofocus
               autocomplete="current-password">
      </div>

      <div class="form-group">
        <label for="nova_senha">Nova senha <span style="color:var(--danger)">*</span></label>
        <input type="password" id="nova_senha" name="nova_senha" required
               minlength="8" autocomplete="new-password">
        <span class="hint">Mínimo 8 caracteres.</span>
      </div>

      <div class="form-group">
        <label for="confirmar_senha">Confirmar nova senha <span style="color:var(--danger)">*</span></label>
        <input type="password" id="confirmar_senha" name="confirmar_senha" required
               minlength="8" autocomplete="new-password">
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk"></i> Salvar nova senha
      </button>
    </div>
  </form>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
