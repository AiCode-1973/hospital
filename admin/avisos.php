<?php
/**
 * admin/avisos.php
 * CRUD de Avisos/Modal — Hospital Santo Expedito - APAS
 */
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$pdo = getDB();

// Garante tabela existente
$pdo->exec("
    CREATE TABLE IF NOT EXISTS avisos (
        id            INT          AUTO_INCREMENT PRIMARY KEY,
        titulo        VARCHAR(200) NOT NULL,
        conteudo      TEXT         NULL,
        tipo          ENUM('texto','imagem','documento') NOT NULL DEFAULT 'texto',
        arquivo       VARCHAR(255) NULL,
        exibir_auto   TINYINT(1)   NOT NULL DEFAULT 1,
        ativo         TINYINT(1)   NOT NULL DEFAULT 1,
        criado_em     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$uploadDir = __DIR__ . '/../uploads/avisos/';
$action    = $_GET['action'] ?? 'list';
$id        = (int)($_GET['id'] ?? 0);

// ---------- helpers ----------
function salvarArquivo(array $file, string $dir): ?string
{
    if ($file['error'] !== UPLOAD_ERR_OK) return null;
    $allowed = ['image/jpeg','image/png','image/gif','image/webp','application/pdf'];
    if (function_exists('mime_content_type')) {
        $mime = mime_content_type($file['tmp_name']);
    } else {
        $mime = $file['type'];
    }
    if (!in_array($mime, $allowed, true)) return null;
    if ($file['size'] > 5 * 1024 * 1024) return null;

    $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $nome = bin2hex(random_bytes(12)) . '.' . $ext;
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    if (!move_uploaded_file($file['tmp_name'], $dir . $nome)) return null;
    return $nome;
}

function removerArquivo(string $dir, ?string $arquivo): void
{
    if ($arquivo && is_file($dir . $arquivo)) {
        unlink($dir . $arquivo);
    }
}

// ---------- POST ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token de segurança inválido.');
        header('Location: avisos.php'); exit;
    }

    $postAction = $_POST['action'] ?? '';

    /* ── Criar ── */
    if ($postAction === 'criar') {
        $titulo     = trim($_POST['titulo'] ?? '');
        $tipo       = in_array($_POST['tipo'] ?? '', ['texto','imagem','documento']) ? $_POST['tipo'] : 'texto';
        $conteudo   = trim($_POST['conteudo'] ?? '');
        $exibirAuto = isset($_POST['exibir_auto']) ? 1 : 0;
        $ativo      = isset($_POST['ativo'])       ? 1 : 0;
        $arquivo    = null;

        if ($titulo === '') {
            setFlash('error', 'O título é obrigatório.');
            header('Location: avisos.php?action=novo'); exit;
        }
        if ($tipo !== 'texto' && !empty($_FILES['arquivo']['name'])) {
            $arquivo = salvarArquivo($_FILES['arquivo'], $uploadDir);
            if ($arquivo === null) {
                setFlash('error', 'Arquivo inválido. Use imagens (JPG/PNG/GIF/WEBP) ou PDF até 5 MB.');
                header('Location: avisos.php?action=novo'); exit;
            }
        }
        $pdo->prepare(
            'INSERT INTO avisos (titulo, conteudo, tipo, arquivo, exibir_auto, ativo) VALUES (?,?,?,?,?,?)'
        )->execute([$titulo, $conteudo ?: null, $tipo, $arquivo, $exibirAuto, $ativo]);

        setFlash('success', 'Aviso criado com sucesso!');
        header('Location: avisos.php'); exit;
    }

    /* ── Atualizar ── */
    if ($postAction === 'atualizar') {
        $editId     = (int)($_POST['id'] ?? 0);
        $titulo     = trim($_POST['titulo'] ?? '');
        $tipo       = in_array($_POST['tipo'] ?? '', ['texto','imagem','documento']) ? $_POST['tipo'] : 'texto';
        $conteudo   = trim($_POST['conteudo'] ?? '');
        $exibirAuto = isset($_POST['exibir_auto']) ? 1 : 0;
        $ativo      = isset($_POST['ativo'])       ? 1 : 0;

        $stmt = $pdo->prepare('SELECT arquivo FROM avisos WHERE id = ?');
        $stmt->execute([$editId]);
        $row = $stmt->fetch();

        if (!$row) { setFlash('error', 'Aviso não encontrado.'); header('Location: avisos.php'); exit; }
        if ($titulo === '') { setFlash('error', 'O título é obrigatório.'); header('Location: avisos.php?action=editar&id='.$editId); exit; }

        $arquivo = $row['arquivo'];

        // Novo arquivo enviado
        if ($tipo !== 'texto' && !empty($_FILES['arquivo']['name'])) {
            $novoArq = salvarArquivo($_FILES['arquivo'], $uploadDir);
            if ($novoArq === null) {
                setFlash('error', 'Arquivo inválido. Use imagens (JPG/PNG/GIF/WEBP) ou PDF até 5 MB.');
                header('Location: avisos.php?action=editar&id='.$editId); exit;
            }
            removerArquivo($uploadDir, $arquivo);
            $arquivo = $novoArq;
        }
        // Tipo mudou para texto: apaga arquivo antigo
        if ($tipo === 'texto') {
            removerArquivo($uploadDir, $arquivo);
            $arquivo = null;
        }

        $pdo->prepare(
            'UPDATE avisos SET titulo=?,conteudo=?,tipo=?,arquivo=?,exibir_auto=?,ativo=?,atualizado_em=NOW() WHERE id=?'
        )->execute([$titulo, $conteudo ?: null, $tipo, $arquivo, $exibirAuto, $ativo, $editId]);

        setFlash('success', 'Aviso atualizado!');
        header('Location: avisos.php'); exit;
    }

    /* ── Toggle ativo ── */
    if ($postAction === 'toggle') {
        $togId = (int)($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE avisos SET ativo = 1 - ativo WHERE id = ?')->execute([$togId]);
        setFlash('success', 'Status alterado!');
        header('Location: avisos.php'); exit;
    }

    /* ── Excluir ── */
    if ($postAction === 'excluir') {
        $delId = (int)($_POST['id'] ?? 0);
        $stmt  = $pdo->prepare('SELECT arquivo FROM avisos WHERE id = ?');
        $stmt->execute([$delId]);
        $r = $stmt->fetch();
        if ($r) removerArquivo($uploadDir, $r['arquivo']);
        $pdo->prepare('DELETE FROM avisos WHERE id = ?')->execute([$delId]);
        setFlash('success', 'Aviso excluído!');
        header('Location: avisos.php'); exit;
    }
}

// ---------- GET: dados ----------
$avisos  = $pdo->query('SELECT * FROM avisos ORDER BY criado_em DESC')->fetchAll();
$editRow = null;

if ($action === 'editar' && $id) {
    $stmt = $pdo->prepare('SELECT * FROM avisos WHERE id = ?');
    $stmt->execute([$id]);
    $editRow = $stmt->fetch();
    if (!$editRow) { header('Location: avisos.php'); exit; }
}

$pageTitle = 'Avisos / Modal';
require_once __DIR__ . '/_header.php';
$csrf = csrfToken();
?>

<?php if ($action === 'novo' || $action === 'editar'): ?>
<!-- ===== Formulário ===== -->
<div class="card">
  <div class="card-header">
    <h2>
      <i class="fa-solid fa-<?= $action === 'novo' ? 'plus' : 'pen' ?>"></i>
      <?= $action === 'novo' ? 'Novo Aviso' : 'Editar Aviso' ?>
    </h2>
    <a href="avisos.php" class="btn btn-outline btn-sm">
      <i class="fa-solid fa-arrow-left"></i> Voltar
    </a>
  </div>

  <form method="POST" enctype="multipart/form-data" class="form-grid" novalidate>
    <input type="hidden" name="csrf_token" value="<?= esc($csrf) ?>">
    <input type="hidden" name="action"     value="<?= $action === 'novo' ? 'criar' : 'atualizar' ?>">
    <?php if ($action === 'editar'): ?>
    <input type="hidden" name="id" value="<?= (int)$editRow['id'] ?>">
    <?php endif; ?>

    <!-- Título -->
    <div class="form-group full">
      <label>Título <span class="req">*</span></label>
      <input type="text" name="titulo" maxlength="200" required
             value="<?= esc($editRow['titulo'] ?? '') ?>">
    </div>

    <!-- Tipo -->
    <div class="form-group">
      <label>Tipo de conteúdo</label>
      <select name="tipo" id="tipoSelect">
        <?php foreach (['texto'=>'Texto','imagem'=>'Imagem','documento'=>'Documento (PDF)'] as $v => $l): ?>
        <option value="<?= $v ?>" <?= ($editRow['tipo'] ?? 'texto') === $v ? 'selected' : '' ?>>
          <?= $l ?>
        </option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Checkboxes -->
    <div class="form-group" style="display:flex;flex-direction:column;gap:12px;justify-content:flex-end;">
      <label class="check-label">
        <input type="checkbox" name="exibir_auto" value="1"
               <?= ($editRow['exibir_auto'] ?? 1) ? 'checked' : '' ?>>
        Abrir automaticamente ao carregar a página
      </label>
      <label class="check-label">
        <input type="checkbox" name="ativo" value="1"
               <?= ($editRow['ativo'] ?? 1) ? 'checked' : '' ?>>
        Aviso ativo (visível no site)
      </label>
    </div>

    <!-- Conteúdo texto -->
    <div class="form-group full" id="grupoConteudo"
         style="<?= ($editRow['tipo'] ?? 'texto') !== 'texto' ? 'display:none' : '' ?>">
      <label>Mensagem</label>
      <textarea name="conteudo" rows="6" placeholder="Escreva a mensagem do aviso..."><?= esc($editRow['conteudo'] ?? '') ?></textarea>
    </div>

    <!-- Upload arquivo -->
    <div class="form-group full" id="grupoArquivo"
         style="<?= ($editRow['tipo'] ?? 'texto') === 'texto' ? 'display:none' : '' ?>">
      <label id="labelArquivo">Arquivo</label>
      <input type="file" name="arquivo" id="inputArquivo" accept="image/*,.pdf">
      <small style="color:var(--gray-text);">Tamanho máximo: 5 MB. Imagens: JPG, PNG, GIF, WEBP. Documento: PDF.</small>
      <?php if (!empty($editRow['arquivo'])): ?>
      <p style="margin-top:8px;font-size:.85rem;">
        Arquivo atual:
        <a href="../uploads/avisos/<?= esc($editRow['arquivo']) ?>" target="_blank" rel="noopener">
          <?= esc($editRow['arquivo']) ?>
        </a>
        <em style="color:var(--gray-text);"> (envie um novo para substituir)</em>
      </p>
      <?php endif; ?>
    </div>

    <div class="form-actions full">
      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk"></i>
        <?= $action === 'novo' ? 'Criar Aviso' : 'Salvar Alterações' ?>
      </button>
      <a href="avisos.php" class="btn btn-outline">Cancelar</a>
    </div>
  </form>
</div>

<script>
(function(){
  const sel   = document.getElementById('tipoSelect');
  const gConteudo = document.getElementById('grupoConteudo');
  const gArquivo  = document.getElementById('grupoArquivo');
  const lblArq    = document.getElementById('labelArquivo');
  const inputArq  = document.getElementById('inputArquivo');

  function update() {
    const v = sel.value;
    gConteudo.style.display = v === 'texto'    ? '' : 'none';
    gArquivo.style.display  = v !== 'texto'    ? '' : 'none';
    lblArq.textContent      = v === 'imagem'   ? 'Imagem' : 'Documento (PDF)';
    inputArq.accept         = v === 'imagem'   ? 'image/*' : '.pdf,application/pdf';
  }
  sel.addEventListener('change', update);
  update();
})();
</script>

<?php else: ?>
<!-- ===== Listagem ===== -->
<div class="card-header" style="margin-bottom:16px;">
  <h2><i class="fa-solid fa-bell"></i> Avisos / Modal do Site</h2>
  <a href="avisos.php?action=novo" class="btn btn-primary">
    <i class="fa-solid fa-plus"></i> Novo Aviso
  </a>
</div>

<div class="card">
  <?php if (empty($avisos)): ?>
  <p style="padding:24px;color:var(--gray-text);text-align:center;">
    Nenhum aviso cadastrado. <a href="avisos.php?action=novo">Criar o primeiro</a>.
  </p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Título</th>
          <th>Tipo</th>
          <th>Auto-abrir</th>
          <th>Status</th>
          <th>Criado em</th>
          <th>Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($avisos as $av): ?>
        <tr>
          <td><?= $av['id'] ?></td>
          <td><strong><?= esc($av['titulo']) ?></strong></td>
          <td>
            <?php
              $icons = ['texto'=>'fa-align-left','imagem'=>'fa-image','documento'=>'fa-file-pdf'];
              $labels = ['texto'=>'Texto','imagem'=>'Imagem','documento'=>'PDF'];
            ?>
            <i class="fa-solid <?= $icons[$av['tipo']] ?? 'fa-file' ?>"></i>
            <?= $labels[$av['tipo']] ?? $av['tipo'] ?>
          </td>
          <td>
            <?= $av['exibir_auto'] ? '<span class="badge badge-green">Sim</span>' : '<span class="badge badge-gray">Não</span>' ?>
          </td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="csrf_token" value="<?= esc($csrf) ?>">
              <input type="hidden" name="action"     value="toggle">
              <input type="hidden" name="id"         value="<?= $av['id'] ?>">
              <button type="submit" class="badge <?= $av['ativo'] ? 'badge-green' : 'badge-gray' ?>"
                      title="Clique para alternar" style="cursor:pointer;border:none;background:none;padding:0;">
                <?= $av['ativo'] ? 'Ativo' : 'Inativo' ?>
              </button>
            </form>
          </td>
          <td><?= date('d/m/Y H:i', strtotime($av['criado_em'])) ?></td>
          <td class="actions">
            <a href="avisos.php?action=editar&id=<?= $av['id'] ?>" class="btn btn-sm btn-outline" title="Editar">
              <i class="fa-solid fa-pen"></i>
            </a>
            <form method="POST" style="display:inline"
                  onsubmit="return confirm('Excluir este aviso?')">
              <input type="hidden" name="csrf_token" value="<?= esc($csrf) ?>">
              <input type="hidden" name="action"     value="excluir">
              <input type="hidden" name="id"         value="<?= $av['id'] ?>">
              <button type="submit" class="btn btn-sm btn-danger" title="Excluir">
                <i class="fa-solid fa-trash"></i>
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="card" style="margin-top:16px;">
  <div class="card-header"><h3><i class="fa-solid fa-circle-info"></i> Como funciona</h3></div>
  <ul style="padding:16px 24px;line-height:2;color:var(--gray-text);font-size:.9rem;">
    <li><strong>Ativo:</strong> o aviso aparece no site. Apenas o primeiro aviso ativo é exibido.</li>
    <li><strong>Auto-abrir:</strong> o modal abre automaticamente ao carregar a página.</li>
    <li><strong>Botão no hero:</strong> mesmo com auto-abrir desativado, o visitante pode clicar no botão "Ver Aviso" no topo do site.</li>
    <li><strong>Tipos:</strong> Texto (mensagem livre), Imagem (exibe a imagem no modal), Documento PDF (link para download/visualização).</li>
  </ul>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/_footer.php'; ?>
