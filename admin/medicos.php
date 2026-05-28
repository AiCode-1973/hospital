<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$pdo    = getDB();
$action = $_GET['action'] ?? 'listar';
$id     = (int) ($_GET['id'] ?? 0);
$errors = [];

$item = ['id' => 0, 'nome' => '', 'especialidade' => '', 'crm' => '', 'descricao' => '', 'foto' => '', 'ativo' => 1];

// ---- POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        die('Token CSRF inválido.');
    }

    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'excluir') {
        $delId = (int) ($_POST['id'] ?? 0);
        if ($delId > 0) {
            $pdo->prepare('DELETE FROM medicos WHERE id = ?')->execute([$delId]);
            setFlash('success', 'Médico excluído com sucesso.');
        }
        header('Location: medicos.php');
        exit;
    }

    if ($postAction === 'salvar') {
        $editId = (int) ($_POST['id'] ?? 0);
        $item   = [
            'id'           => $editId,
            'nome'         => trim($_POST['nome']          ?? ''),
            'especialidade'=> trim($_POST['especialidade'] ?? ''),
            'crm'          => trim($_POST['crm']           ?? ''),
            'descricao'    => trim($_POST['descricao']     ?? ''),
            'foto'         => trim($_POST['foto']          ?? ''),
            'ativo'        => isset($_POST['ativo']) ? 1 : 0,
        ];

        if ($item['nome'] === '')          $errors[] = 'Nome é obrigatório.';
        if ($item['especialidade'] === '') $errors[] = 'Especialidade é obrigatória.';
        if ($item['crm'] === '')           $errors[] = 'CRM é obrigatório.';

        if (empty($errors)) {
            if ($editId > 0) {
                $pdo->prepare('UPDATE medicos SET nome=?, especialidade=?, crm=?, descricao=?, foto=?, ativo=? WHERE id=?')
                    ->execute([$item['nome'], $item['especialidade'], $item['crm'], $item['descricao'], $item['foto'], $item['ativo'], $editId]);
                setFlash('success', 'Médico atualizado com sucesso.');
            } else {
                $pdo->prepare('INSERT INTO medicos (nome, especialidade, crm, descricao, foto, ativo) VALUES (?,?,?,?,?,?)')
                    ->execute([$item['nome'], $item['especialidade'], $item['crm'], $item['descricao'], $item['foto'], $item['ativo']]);
                setFlash('success', 'Médico cadastrado com sucesso.');
            }
            header('Location: medicos.php');
            exit;
        }

        $action = $editId > 0 ? 'editar' : 'novo';
    }
}

// ---- Carregar para edição ----
if ($action === 'editar' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM medicos WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) $item = $row;
}

// ---- Lista ----
$items = $pdo->query('SELECT * FROM medicos ORDER BY nome')->fetchAll();

$pageTitle = 'Médicos';
require_once __DIR__ . '/_header.php';
?>

<?php if ($action === 'novo' || $action === 'editar'): ?>
<!-- ===== FORMULÁRIO ===== -->
<div class="card">
  <div class="card-header">
    <h2>
      <i class="fa-solid fa-user-doctor"></i>
      <?= $action === 'editar' ? 'Editar' : 'Novo' ?> Médico
    </h2>
    <a href="medicos.php" class="btn btn-secondary btn-sm">
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
        <label for="nome">Nome completo <span style="color:var(--danger)">*</span></label>
        <input type="text" id="nome" name="nome"
               value="<?= esc($item['nome']) ?>" required maxlength="120" autofocus>
      </div>

      <div class="form-group">
        <label for="especialidade">Especialidade <span style="color:var(--danger)">*</span></label>
        <input type="text" id="especialidade" name="especialidade"
               value="<?= esc($item['especialidade']) ?>" required maxlength="120">
      </div>

      <div class="form-group">
        <label for="crm">CRM <span style="color:var(--danger)">*</span></label>
        <input type="text" id="crm" name="crm"
               value="<?= esc($item['crm']) ?>" required maxlength="30"
               placeholder="CRM/SP 123456">
      </div>

      <div class="form-group">
        <label for="foto">Caminho da foto</label>
        <input type="text" id="foto" name="foto"
               value="<?= esc($item['foto']) ?>" maxlength="255"
               placeholder="images/medicos/dr-joao.jpg">
        <span class="hint">Caminho relativo a partir da raiz do projeto.</span>
      </div>

      <div class="form-group" style="grid-column:1/-1;">
        <label for="descricao">Mini biografia</label>
        <textarea id="descricao" name="descricao" maxlength="600"><?= esc($item['descricao']) ?></textarea>
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
      <a href="medicos.php" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

<?php else: ?>
<!-- ===== LISTA ===== -->
<div class="card">
  <div class="card-header">
    <h2><i class="fa-solid fa-user-doctor"></i> Médicos (<?= count($items) ?>)</h2>
    <a href="?action=novo" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-plus"></i> Novo
    </a>
  </div>

  <?php if (empty($items)): ?>
  <div class="empty-state">
    <i class="fa-solid fa-user-doctor"></i>
    <p>Nenhum médico cadastrado ainda.</p>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nome</th>
          <th>Especialidade</th>
          <th>CRM</th>
          <th>Status</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $row): ?>
        <tr>
          <td style="color:var(--muted);font-size:.8rem;"><?= $row['id'] ?></td>
          <td><strong><?= esc($row['nome']) ?></strong></td>
          <td><?= esc($row['especialidade']) ?></td>
          <td style="font-size:.83rem;color:var(--muted);"><?= esc($row['crm']) ?></td>
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
                    onsubmit="return confirm('Excluir o médico «<?= esc(addslashes($row['nome'])) ?>»?')">
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
