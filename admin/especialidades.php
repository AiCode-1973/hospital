<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$pdo    = getDB();
$action = $_GET['action'] ?? 'listar';
$id     = (int) ($_GET['id'] ?? 0);
$errors = [];

$item = ['id' => 0, 'nome' => '', 'descricao' => '', 'icone' => 'fa-stethoscope', 'ativo' => 1];

// ---- POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        die('Token CSRF inválido.');
    }

    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'excluir') {
        $delId = (int) ($_POST['id'] ?? 0);
        if ($delId > 0) {
            $pdo->prepare('DELETE FROM especialidades WHERE id = ?')->execute([$delId]);
            setFlash('success', 'Especialidade excluída com sucesso.');
        }
        header('Location: especialidades.php');
        exit;
    }

    if ($postAction === 'salvar') {
        $editId = (int) ($_POST['id'] ?? 0);
        $item   = [
            'id'       => $editId,
            'nome'     => trim($_POST['nome']      ?? ''),
            'descricao'=> trim($_POST['descricao'] ?? ''),
            'icone'    => trim($_POST['icone']     ?? 'fa-stethoscope'),
            'ativo'    => isset($_POST['ativo']) ? 1 : 0,
        ];

        if ($item['nome'] === '')      $errors[] = 'Nome é obrigatório.';
        if ($item['descricao'] === '') $errors[] = 'Descrição é obrigatória.';

        if (empty($errors)) {
            if ($editId > 0) {
                $pdo->prepare('UPDATE especialidades SET nome=?, descricao=?, icone=?, ativo=? WHERE id=?')
                    ->execute([$item['nome'], $item['descricao'], $item['icone'], $item['ativo'], $editId]);
                setFlash('success', 'Especialidade atualizada com sucesso.');
            } else {
                $pdo->prepare('INSERT INTO especialidades (nome, descricao, icone, ativo) VALUES (?,?,?,?)')
                    ->execute([$item['nome'], $item['descricao'], $item['icone'], $item['ativo']]);
                setFlash('success', 'Especialidade cadastrada com sucesso.');
            }
            header('Location: especialidades.php');
            exit;
        }

        $action = $editId > 0 ? 'editar' : 'novo';
    }
}

// ---- Carregar para edição ----
if ($action === 'editar' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM especialidades WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) $item = $row;
}

// ---- Lista ----
$items = $pdo->query('SELECT * FROM especialidades ORDER BY nome')->fetchAll();

$pageTitle = 'Especialidades';
require_once __DIR__ . '/_header.php';
?>

<?php if ($action === 'novo' || $action === 'editar'): ?>
<!-- ===== FORMULÁRIO ===== -->
<div class="card">
  <div class="card-header">
    <h2>
      <i class="fa-solid fa-stethoscope"></i>
      <?= $action === 'editar' ? 'Editar' : 'Nova' ?> Especialidade
    </h2>
    <a href="especialidades.php" class="btn btn-secondary btn-sm">
      <i class="fa-solid fa-arrow-left"></i> Voltar
    </a>
  </div>

  <?php if ($errors): ?>
  <div class="flash flash-error">
    <i class="fa-solid fa-circle-exclamation"></i>
    <?= esc(implode(' &nbsp;|&nbsp; ', $errors)) ?>
  </div>
  <?php endif; ?>

  <form method="POST" novalidate>
    <input type="hidden" name="csrf_token" value="<?= esc($csrf) ?>">
    <input type="hidden" name="action"     value="salvar">
    <input type="hidden" name="id"         value="<?= (int) $item['id'] ?>">

    <div class="form-grid form-grid-2">
      <div class="form-group">
        <label for="nome">Nome <span style="color:var(--danger)">*</span></label>
        <input type="text" id="nome" name="nome"
               value="<?= esc($item['nome']) ?>" required maxlength="120" autofocus>
      </div>

      <div class="form-group">
        <label for="icone">Ícone Font Awesome</label>
        <input type="text" id="icone" name="icone"
               value="<?= esc($item['icone']) ?>" placeholder="fa-stethoscope" maxlength="80">
        <span class="hint">
          Ex: <code>fa-heart</code>, <code>fa-brain</code>.
          <a href="https://fontawesome.com/search?o=r&m=free&s=solid" target="_blank" rel="noopener">Ver ícones →</a>
        </span>
      </div>

      <div class="form-group" style="grid-column:1/-1;">
        <label for="descricao">Descrição <span style="color:var(--danger)">*</span></label>
        <textarea id="descricao" name="descricao" required
                  maxlength="500"><?= esc($item['descricao']) ?></textarea>
      </div>

      <div class="form-group">
        <label class="form-check">
          <input type="checkbox" name="ativo" value="1"
                 <?= $item['ativo'] ? 'checked' : '' ?>>
          Ativo (visível no site)
        </label>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk"></i> Salvar
      </button>
      <a href="especialidades.php" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

<?php else: ?>
<!-- ===== LISTA ===== -->
<div class="card">
  <div class="card-header">
    <h2><i class="fa-solid fa-stethoscope"></i> Especialidades (<?= count($items) ?>)</h2>
    <a href="?action=novo" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-plus"></i> Nova
    </a>
  </div>

  <?php if (empty($items)): ?>
  <div class="empty-state">
    <i class="fa-solid fa-stethoscope"></i>
    <p>Nenhuma especialidade cadastrada ainda.</p>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nome</th>
          <th>Ícone</th>
          <th>Descrição</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $row): ?>
        <tr>
          <td style="color:var(--muted);font-size:.8rem;"><?= $row['id'] ?></td>
          <td><strong><?= esc($row['nome']) ?></strong></td>
          <td>
            <i class="fa-solid <?= esc($row['icone']) ?>" title="<?= esc($row['icone']) ?>"></i>
            <small style="color:var(--muted);margin-left:4px;"><?= esc($row['icone']) ?></small>
          </td>
          <td style="max-width:280px;color:var(--muted);font-size:.83rem;">
            <?= esc(mb_substr($row['descricao'], 0, 70)) ?><?= mb_strlen($row['descricao']) > 70 ? '…' : '' ?>
          </td>
          <td>
            <span class="badge <?= $row['ativo'] ? 'badge-success' : 'badge-danger' ?>">
              <?= $row['ativo'] ? 'Ativo' : 'Inativo' ?>
            </span>
          </td>
          <td>
            <div class="td-actions">
              <a href="?action=editar&id=<?= $row['id'] ?>"
                 class="btn btn-outline btn-sm btn-icon" title="Editar">
                <i class="fa-solid fa-pen"></i>
              </a>
              <form class="confirm-delete" method="POST"
                    onsubmit="return confirm('Excluir a especialidade «<?= esc(addslashes($row['nome'])) ?>»?')">
                <input type="hidden" name="csrf_token" value="<?= esc($csrf) ?>">
                <input type="hidden" name="action"     value="excluir">
                <input type="hidden" name="id"         value="<?= $row['id'] ?>">
                <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Excluir">
                  <i class="fa-solid fa-trash"></i>
                </button>
              </form>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/_footer.php'; ?>
