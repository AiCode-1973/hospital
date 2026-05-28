<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$pdo    = getDB();
$action = $_GET['action'] ?? 'listar';
$id     = (int) ($_GET['id'] ?? 0);
$errors = [];

$item = ['id' => 0, 'nome' => '', 'texto' => '', 'avaliacao' => 5, 'data' => date('Y-m-d'), 'ativo' => 1];

// ---- POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        die('Token CSRF inválido.');
    }

    $postAction = $_POST['action'] ?? '';

    if ($postAction === 'excluir') {
        $delId = (int) ($_POST['id'] ?? 0);
        if ($delId > 0) {
            $pdo->prepare('DELETE FROM depoimentos WHERE id = ?')->execute([$delId]);
            setFlash('success', 'Depoimento excluído com sucesso.');
        }
        header('Location: depoimentos.php');
        exit;
    }

    if ($postAction === 'salvar') {
        $editId = (int) ($_POST['id'] ?? 0);
        $aval   = max(1, min(5, (int) ($_POST['avaliacao'] ?? 5)));
        $item   = [
            'id'       => $editId,
            'nome'     => trim($_POST['nome']  ?? ''),
            'texto'    => trim($_POST['texto'] ?? ''),
            'avaliacao'=> $aval,
            'data'     => trim($_POST['data']  ?? date('Y-m-d')),
            'ativo'    => isset($_POST['ativo']) ? 1 : 0,
        ];

        if ($item['nome'] === '')  $errors[] = 'Nome é obrigatório.';
        if ($item['texto'] === '') $errors[] = 'Texto do depoimento é obrigatório.';

        // Validar data
        $d = \DateTime::createFromFormat('Y-m-d', $item['data']);
        if (!$d) {
            $errors[] = 'Data inválida.';
            $item['data'] = date('Y-m-d');
        }

        if (empty($errors)) {
            if ($editId > 0) {
                $pdo->prepare('UPDATE depoimentos SET nome=?, texto=?, avaliacao=?, data=?, ativo=? WHERE id=?')
                    ->execute([$item['nome'], $item['texto'], $item['avaliacao'], $item['data'], $item['ativo'], $editId]);
                setFlash('success', 'Depoimento atualizado com sucesso.');
            } else {
                $pdo->prepare('INSERT INTO depoimentos (nome, texto, avaliacao, data, ativo) VALUES (?,?,?,?,?)')
                    ->execute([$item['nome'], $item['texto'], $item['avaliacao'], $item['data'], $item['ativo']]);
                setFlash('success', 'Depoimento cadastrado com sucesso.');
            }
            header('Location: depoimentos.php');
            exit;
        }

        $action = $editId > 0 ? 'editar' : 'novo';
    }
}

// ---- Carregar para edição ----
if ($action === 'editar' && $id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM depoimentos WHERE id = ?');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) $item = $row;
}

// ---- Lista ----
$items = $pdo->query('SELECT * FROM depoimentos ORDER BY data DESC')->fetchAll();

$pageTitle = 'Depoimentos';
require_once __DIR__ . '/_header.php';
?>

<?php if ($action === 'novo' || $action === 'editar'): ?>
<!-- ===== FORMULÁRIO ===== -->
<div class="card">
  <div class="card-header">
    <h2>
      <i class="fa-solid fa-comment-dots"></i>
      <?= $action === 'editar' ? 'Editar' : 'Novo' ?> Depoimento
    </h2>
    <a href="depoimentos.php" class="btn btn-secondary btn-sm">
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
        <label for="nome">Nome do paciente <span style="color:var(--danger)">*</span></label>
        <input type="text" id="nome" name="nome"
               value="<?= esc($item['nome']) ?>" required maxlength="120" autofocus>
      </div>

      <div class="form-group">
        <label for="avaliacao">Avaliação (1–5 estrelas)</label>
        <select id="avaliacao" name="avaliacao">
          <?php for ($s = 5; $s >= 1; $s--): ?>
          <option value="<?= $s ?>" <?= (int) $item['avaliacao'] === $s ? 'selected' : '' ?>>
            <?= $s ?> <?= $s === 1 ? 'estrela' : 'estrelas' ?>
          </option>
          <?php endfor; ?>
        </select>
      </div>

      <div class="form-group" style="grid-column:1/-1;">
        <label for="texto">Texto do depoimento <span style="color:var(--danger)">*</span></label>
        <textarea id="texto" name="texto" required maxlength="800"><?= esc($item['texto']) ?></textarea>
      </div>

      <div class="form-group">
        <label for="data">Data</label>
        <input type="date" id="data" name="data"
               value="<?= esc($item['data']) ?>"
               max="<?= date('Y-m-d') ?>">
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
      <a href="depoimentos.php" class="btn btn-secondary">Cancelar</a>
    </div>
  </form>
</div>

<?php else: ?>
<!-- ===== LISTA ===== -->
<div class="card">
  <div class="card-header">
    <h2><i class="fa-solid fa-comment-dots"></i> Depoimentos (<?= count($items) ?>)</h2>
    <a href="?action=novo" class="btn btn-primary btn-sm">
      <i class="fa-solid fa-plus"></i> Novo
    </a>
  </div>

  <?php if (empty($items)): ?>
  <div class="empty-state">
    <i class="fa-solid fa-comment-dots"></i>
    <p>Nenhum depoimento cadastrado ainda.</p>
  </div>
  <?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Paciente</th>
          <th>Avaliação</th>
          <th>Depoimento</th>
          <th>Data</th>
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
            <span class="stars">
              <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="fa-<?= $i <= (int)$row['avaliacao'] ? 'solid' : 'regular' ?> fa-star"></i>
              <?php endfor; ?>
            </span>
          </td>
          <td style="max-width:260px;color:var(--muted);font-size:.83rem;">
            "<?= esc(mb_substr($row['texto'], 0, 60)) ?><?= mb_strlen($row['texto']) > 60 ? '…' : '' ?>"
          </td>
          <td style="font-size:.83rem;white-space:nowrap;">
            <?= esc(date('d/m/Y', strtotime($row['data']))) ?>
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
                    onsubmit="return confirm('Excluir o depoimento de «<?= esc(addslashes($row['nome'])) ?>»?')">
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
