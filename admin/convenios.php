<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$pdo    = getDB();
$action = $_GET['action'] ?? 'listar';
$id     = (int) ($_GET['id'] ?? 0);
$errors = [];

$item = ['id' => 0, 'nome' => '', 'logo' => '', 'ativo' => 1];

// ---- POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        die('Token CSRF inválido.');
    }

    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'excluir') {
        $delId = (int) ($_POST['id'] ?? 0);
        if ($delId > 0) {
            $pdo->prepare('DELETE FROM convenios WHERE id = ?')->execute([$delId]);
            setFlash('success', 'Convênio excluído com sucesso.');
        }
        header('Location: convenios.php');
        exit;
    }

    if ($postAction === 'salvar') {
        $editId = (int) ($_POST['id'] ?? 0);
        $item   = [
            'id'   => $editId,
            'nome' => trim($_POST['nome'] ?? ''),
            'logo' => trim($_POST['logo'] ?? ''),
            'ativo'=> isset($_POST['ativo']) ? 1 : 0,
        ];

        if ($item['nome'] === '') $errors[] = 'Nome é obrigatório.';

        if (empty($errors)) {
            if ($editId > 0) {
                $pdo->prepare('UPDATE convenios SET nome=?, logo=?, ativo=? WHERE id=?')
                    ->execute([$item['nome'], $item['logo'], $item['ativo'], $editId]);
                setFlash('success', 'Convênio atualizado com sucesso.');
            } else {
                $pdo->prepare('INSERT INTO convenios (nome, logo, ativo) VALUES (?,?,?)')
                    ->execute([$item['nome'], $item['logo'], $item['ativo']]);
                setFlash('success', 'Convênio cadastrado com sucesso.');
            }
            header('Location: convenios.php');
            exit;
        }

        $action = $editId > 0 ? 'editar' : 'novo';
    }
}

// ---- Carregar para edição ----
if ($action === 'editar' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM convenios WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) $item = $row;
}

// ---- Lista ----
$items = $pdo->query('SELECT * FROM convenios ORDER BY nome')->fetchAll();

$pageTitle = 'Convênios';
require_once __DIR__ . '/_header.php';
?>

<?php if ($action === 'novo' || $action === 'editar'): ?>
<!-- ===== FORMULÁRIO ===== -->
<div class="card">
  <div class="card-header">
    <h2>
      <i class="fa-solid fa-shield-heart"></i>
      <?= $action === 'editar' ? 'Editar' : 'Novo' ?> Convênio
    </h2>
    <a href="convenios.php" class="btn btn-secondary btn-sm">
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
        <label for="nome">Nome do plano <span style="color:var(--danger)">*</span></label>
        <input type="text" id="nome" name="nome"
               value="<?= esc($item['nome']) ?>" required maxlength="120" autofocus>
      </div>

      <div class="form-group">
        <label for="logo">Caminho do logo</label>
        <input type="text" id="logo" name="logo"
               value="<?= esc($item['logo']) ?>" maxlength="255"
               placeholder="images/convenios/unimed.png">
        <span class="hint">Caminho relativo a partir da raiz do projeto.</span>
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
      <a href="convenios.php" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

<?php else: ?>
<!-- ===== LISTA ===== -->
<div class="card">
  <div class="card-header">
    <h2><i class="fa-solid fa-shield-heart"></i> Convênios (<?= count($items) ?>)</h2>
    <a href="?action=novo" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-plus"></i> Novo
    </a>
  </div>

  <?php if (empty($items)): ?>
  <div class="empty-state">
    <i class="fa-solid fa-shield-heart"></i>
    <p>Nenhum convênio cadastrado ainda.</p>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nome</th>
          <th>Logo</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $row): ?>
        <tr>
          <td style="color:var(--muted);font-size:.8rem;"><?= $row['id'] ?></td>
          <td><strong><?= esc($row['nome']) ?></strong></td>
          <td style="font-size:.8rem;color:var(--muted);">
            <?php if ($row['logo']): ?>
              <?php $logoAbs = __DIR__ . '/../' . $row['logo']; ?>
              <?php if (file_exists($logoAbs)): ?>
                <img src="../<?= esc($row['logo']) ?>" alt="<?= esc($row['nome']) ?>"
                     style="height:28px;object-fit:contain;">
              <?php else: ?>
                <span style="color:var(--danger);" title="Arquivo não encontrado">
                  <i class="fa-solid fa-triangle-exclamation"></i> <?= esc($row['logo']) ?>
                </span>
              <?php endif; ?>
            <?php else: ?>
              <span style="color:var(--muted);">—</span>
            <?php endif; ?>
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
                    onsubmit="return confirm('Excluir o convênio «<?= esc(addslashes($row['nome'])) ?>»?')">
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
