<?php
/**
 * admin/enquetes.php
 * CRUD de Enquetes + Relatórios — Hospital Santo Expedito - APAS
 */
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$pdo = getDB();

// Garante tabelas existentes
$pdo->exec("
    CREATE TABLE IF NOT EXISTS enquetes (
        id         INT          AUTO_INCREMENT PRIMARY KEY,
        titulo     VARCHAR(200) NOT NULL,
        descricao  TEXT         NULL,
        ativo      TINYINT(1)   NOT NULL DEFAULT 1,
        criado_em  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
$pdo->exec("
    CREATE TABLE IF NOT EXISTS enquete_opcoes (
        id          INT          AUTO_INCREMENT PRIMARY KEY,
        enquete_id  INT          NOT NULL,
        texto       VARCHAR(200) NOT NULL,
        ordem       INT          NOT NULL DEFAULT 0,
        FOREIGN KEY (enquete_id) REFERENCES enquetes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
$pdo->exec("
    CREATE TABLE IF NOT EXISTS enquete_votos (
        id          INT         AUTO_INCREMENT PRIMARY KEY,
        enquete_id  INT         NOT NULL,
        opcao_id    INT         NOT NULL,
        ip_hash     VARCHAR(64) NOT NULL,
        votado_em   TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ip (enquete_id, ip_hash),
        FOREIGN KEY (enquete_id) REFERENCES enquetes(id) ON DELETE CASCADE,
        FOREIGN KEY (opcao_id)   REFERENCES enquete_opcoes(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$action = $_GET['action'] ?? 'list';
$id     = (int)($_GET['id'] ?? 0);

// ============================================================
// POST handlers
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token de segurança inválido.');
        header('Location: enquetes.php'); exit;
    }

    $postAction = $_POST['action'] ?? '';

    /* ── Criar ── */
    if ($postAction === 'criar') {
        $titulo    = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $ativo     = isset($_POST['ativo']) ? 1 : 0;
        $opcoes    = array_values(array_filter(array_map('trim', $_POST['opcoes'] ?? [])));

        if ($titulo === '') {
            setFlash('error', 'O título é obrigatório.'); header('Location: enquetes.php?action=nova'); exit;
        }
        if (count($opcoes) < 2) {
            setFlash('error', 'Adicione pelo menos 2 opções.'); header('Location: enquetes.php?action=nova'); exit;
        }

        $stmt = $pdo->prepare('INSERT INTO enquetes (titulo, descricao, ativo) VALUES (?, ?, ?)');
        $stmt->execute([$titulo, $descricao, $ativo]);
        $novoId = (int)$pdo->lastInsertId();

        $stmtOp = $pdo->prepare('INSERT INTO enquete_opcoes (enquete_id, texto, ordem) VALUES (?, ?, ?)');
        foreach ($opcoes as $i => $txt) {
            $stmtOp->execute([$novoId, $txt, $i]);
        }

        setFlash('success', 'Enquete criada com sucesso!');
        header('Location: enquetes.php'); exit;
    }

    /* ── Atualizar ── */
    if ($postAction === 'atualizar') {
        $titulo    = trim($_POST['titulo'] ?? '');
        $descricao = trim($_POST['descricao'] ?? '');
        $ativo     = isset($_POST['ativo']) ? 1 : 0;
        $opcoes    = array_values(array_filter(array_map('trim', $_POST['opcoes'] ?? [])));
        $eid       = (int)($_POST['id'] ?? 0);

        if ($titulo === '') {
            setFlash('error', 'O título é obrigatório.'); header("Location: enquetes.php?action=editar&id=$eid"); exit;
        }
        if (count($opcoes) < 2) {
            setFlash('error', 'Adicione pelo menos 2 opções.'); header("Location: enquetes.php?action=editar&id=$eid"); exit;
        }

        $pdo->prepare('UPDATE enquetes SET titulo=?, descricao=?, ativo=?, atualizado_em=NOW() WHERE id=?')
            ->execute([$titulo, $descricao, $ativo, $eid]);

        // Remove opções antigas e reinserindo (mais simples que diff)
        $pdo->prepare('DELETE FROM enquete_opcoes WHERE enquete_id = ?')->execute([$eid]);
        $stmtOp = $pdo->prepare('INSERT INTO enquete_opcoes (enquete_id, texto, ordem) VALUES (?, ?, ?)');
        foreach ($opcoes as $i => $txt) {
            $stmtOp->execute([$eid, $txt, $i]);
        }

        setFlash('success', 'Enquete atualizada com sucesso!');
        header('Location: enquetes.php'); exit;
    }

    /* ── Toggle ativo ── */
    if ($postAction === 'toggle') {
        $eid = (int)($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE enquetes SET ativo = NOT ativo WHERE id = ?')->execute([$eid]);
        setFlash('success', 'Status da enquete alterado.');
        header('Location: enquetes.php'); exit;
    }

    /* ── Excluir ── */
    if ($postAction === 'excluir') {
        $eid = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM enquetes WHERE id = ?')->execute([$eid]);
        setFlash('success', 'Enquete excluída.');
        header('Location: enquetes.php'); exit;
    }

    /* ── Limpar votos ── */
    if ($postAction === 'limpar_votos') {
        $eid = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM enquete_votos WHERE enquete_id = ?')->execute([$eid]);
        setFlash('success', 'Votos zerados com sucesso.');
        header("Location: enquetes.php?action=relatorio&id=$eid"); exit;
    }
}

// ============================================================
// Helper: resultados da enquete
// ============================================================
function resultadosEnquete(PDO $pdo, int $eid): array
{
    $stmt = $pdo->prepare("
        SELECT o.id, o.texto, COUNT(v.id) AS votos
        FROM enquete_opcoes o
        LEFT JOIN enquete_votos v ON v.opcao_id = o.id
        WHERE o.enquete_id = ?
        GROUP BY o.id, o.texto
        ORDER BY o.ordem, o.id
    ");
    $stmt->execute([$eid]);
    $rows  = $stmt->fetchAll();
    $total = array_sum(array_column($rows, 'votos'));
    foreach ($rows as &$r) {
        $r['votos'] = (int)$r['votos'];
        $r['pct']   = $total > 0 ? round($r['votos'] / $total * 100, 1) : 0.0;
    }
    return ['opcoes' => $rows, 'total' => (int)$total];
}

$pageTitle = 'Enquetes';
require_once __DIR__ . '/_header.php';

$flash = getFlash();
?>

<!-- ============================================================
     RELATÓRIO
     ============================================================ -->
<?php if ($action === 'relatorio' && $id > 0):
    $enquete = $pdo->prepare('SELECT * FROM enquetes WHERE id = ?');
    $enquete->execute([$id]);
    $enquete = $enquete->fetch();
    if (!$enquete) { echo '<p>Enquete não encontrada.</p>'; require __DIR__ . '/_footer.php'; exit; }
    $res = resultadosEnquete($pdo, $id);
    $votos_por_dia = $pdo->prepare("
        SELECT DATE(votado_em) AS dia, COUNT(*) AS total
        FROM enquete_votos WHERE enquete_id = ?
        GROUP BY dia ORDER BY dia DESC LIMIT 30
    ");
    $votos_por_dia->execute([$id]);
    $porDia = array_reverse($votos_por_dia->fetchAll());
?>
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
  <div>
    <a href="enquetes.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
  </div>
  <h1 style="font-size:1.3rem;font-weight:700;">
    <i class="fa-solid fa-chart-bar" style="color:var(--primary);"></i>
    Relatório: <?= esc($enquete['titulo']) ?>
  </h1>
  <form method="post" onsubmit="return confirm('Zerar todos os votos desta enquete?')">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="action" value="limpar_votos">
    <input type="hidden" name="id" value="<?= $id ?>">
    <button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash-can"></i> Zerar votos</button>
  </form>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= esc($flash['msg']) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;margin-bottom:24px;">
  <div class="stat-card" style="background:#f0f9ff;border:1px solid #bae6fd;">
    <div class="stat-icon blue"><i class="fa-solid fa-check-to-slot"></i></div>
    <div><strong><?= $res['total'] ?></strong><span>Total de votos</span></div>
  </div>
  <div class="stat-card" style="background:#f0fdf4;border:1px solid #bbf7d0;">
    <div class="stat-icon green"><i class="fa-solid fa-list-check"></i></div>
    <div><strong><?= count($res['opcoes']) ?></strong><span>Opções</span></div>
  </div>
  <div class="stat-card" style="background:<?= $enquete['ativo'] ? '#f0fdf4' : '#fff7ed' ?>;border:1px solid <?= $enquete['ativo'] ? '#bbf7d0' : '#fed7aa' ?>;">
    <div class="stat-icon <?= $enquete['ativo'] ? 'green' : 'orange' ?>">
      <i class="fa-solid fa-<?= $enquete['ativo'] ? 'circle-check' : 'circle-xmark' ?>"></i>
    </div>
    <div><strong><?= $enquete['ativo'] ? 'Ativa' : 'Inativa' ?></strong><span>Status</span></div>
  </div>
</div>

<!-- Resultados com barras -->
<div class="card" style="margin-bottom:24px;">
  <div class="card-header"><h2><i class="fa-solid fa-chart-bar"></i> Resultado por opção</h2></div>
  <?php if ($res['total'] === 0): ?>
    <p style="padding:20px;color:var(--muted);text-align:center;">Nenhum voto registrado ainda.</p>
  <?php else: ?>
  <div style="padding:20px;display:flex;flex-direction:column;gap:16px;">
    <?php foreach ($res['opcoes'] as $op): ?>
    <div>
      <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:.9rem;">
        <span style="font-weight:500;"><?= esc($op['texto']) ?></span>
        <span style="color:var(--muted);"><?= $op['votos'] ?> voto<?= $op['votos'] !== 1 ? 's' : '' ?> &mdash; <?= $op['pct'] ?>%</span>
      </div>
      <div style="background:#e5e7eb;border-radius:6px;height:22px;overflow:hidden;">
        <div style="width:<?= $op['pct'] ?>%;background:var(--primary);height:100%;border-radius:6px;transition:width .6s ease;"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Votos por dia -->
<?php if (!empty($porDia)): ?>
<div class="card">
  <div class="card-header"><h2><i class="fa-solid fa-calendar-days"></i> Votos por dia (últimos 30 dias)</h2></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Data</th><th>Votos</th></tr></thead>
      <tbody>
        <?php foreach ($porDia as $row): ?>
        <tr>
          <td><?= date('d/m/Y', strtotime($row['dia'])) ?></td>
          <td><?= $row['total'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php
// ============================================================
// FORMULÁRIO CRIAR / EDITAR
// ============================================================
elseif ($action === 'nova' || $action === 'editar'):
    $enquete = null;
    $opcoes  = ['', '', ''];

    if ($action === 'editar' && $id > 0) {
        $s = $pdo->prepare('SELECT * FROM enquetes WHERE id = ?');
        $s->execute([$id]);
        $enquete = $s->fetch();
        if (!$enquete) { header('Location: enquetes.php'); exit; }
        $s2 = $pdo->prepare('SELECT texto FROM enquete_opcoes WHERE enquete_id = ? ORDER BY ordem');
        $s2->execute([$id]);
        $opcoes = array_column($s2->fetchAll(), 'texto');
    }
?>
<div style="display:flex;align-items:center;gap:16px;margin-bottom:24px;">
  <a href="enquetes.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrow-left"></i> Voltar</a>
  <h1 style="font-size:1.3rem;font-weight:700;">
    <?= $enquete ? 'Editar Enquete' : 'Nova Enquete' ?>
  </h1>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= esc($flash['msg']) ?></div>
<?php endif; ?>

<div class="card">
  <form method="post" id="formEnquete">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="action"     value="<?= $enquete ? 'atualizar' : 'criar' ?>">
    <?php if ($enquete): ?>
    <input type="hidden" name="id"         value="<?= $enquete['id'] ?>">
    <?php endif; ?>

    <div class="form-grid">
      <div class="full">
        <label>Título da enquete <span style="color:red">*</span></label>
        <input type="text" name="titulo" required maxlength="200"
               value="<?= esc($enquete['titulo'] ?? '') ?>"
               placeholder="Ex: O que você achou da nossa nova página?">
      </div>
      <div class="full">
        <label>Descrição <span style="color:var(--muted);font-size:.8rem;">(opcional)</span></label>
        <textarea name="descricao" rows="3" placeholder="Contexto ou instruções para o votante"><?= esc($enquete['descricao'] ?? '') ?></textarea>
      </div>
    </div>

    <!-- Opções dinâmicas -->
    <div style="margin-top:20px;">
      <label style="display:block;margin-bottom:10px;font-weight:600;">
        Opções de resposta <span style="color:red">*</span>
        <span style="font-weight:400;color:var(--muted);font-size:.8rem;">(mínimo 2)</span>
      </label>
      <div id="listaOpcoes" style="display:flex;flex-direction:column;gap:8px;">
        <?php foreach ($opcoes as $i => $txt): ?>
        <div class="opcao-row" style="display:flex;gap:8px;align-items:center;">
          <span style="color:var(--muted);font-size:.85rem;width:20px;text-align:right;"><?= $i + 1 ?>.</span>
          <input type="text" name="opcoes[]" value="<?= esc($txt) ?>"
                 placeholder="Opção <?= $i + 1 ?>" maxlength="200"
                 style="flex:1;" class="opcao-input">
          <?php if ($i >= 2): ?>
          <button type="button" class="btn btn-danger btn-sm btn-remover-opcao" title="Remover">
            <i class="fa-solid fa-xmark"></i>
          </button>
          <?php else: ?>
          <span style="width:34px;"></span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <button type="button" id="btnAddOpcao" class="btn btn-outline btn-sm" style="margin-top:10px;">
        <i class="fa-solid fa-plus"></i> Adicionar opção
      </button>
    </div>

    <div style="margin-top:20px;">
      <label class="check-label">
        <input type="checkbox" name="ativo" value="1" <?= !$enquete || $enquete['ativo'] ? 'checked' : '' ?>>
        Enquete ativa (visível no site)
      </label>
    </div>

    <div class="form-actions">
      <a href="enquetes.php" class="btn btn-outline">Cancelar</a>
      <button type="submit" class="btn btn-primary">
        <i class="fa-solid fa-floppy-disk"></i>
        <?= $enquete ? 'Salvar alterações' : 'Criar enquete' ?>
      </button>
    </div>
  </form>
</div>

<script>
(function(){
  var lista = document.getElementById('listaOpcoes');
  var btnAdd = document.getElementById('btnAddOpcao');
  var totalOpcoes = <?= count($opcoes) ?>;

  function atualizarNumeros(){
    var rows = lista.querySelectorAll('.opcao-row');
    rows.forEach(function(row, i){
      row.querySelector('span').textContent = (i+1) + '.';
      row.querySelector('input').placeholder = 'Opção ' + (i+1);
    });
    totalOpcoes = rows.length;
  }

  btnAdd.addEventListener('click', function(){
    totalOpcoes++;
    var div = document.createElement('div');
    div.className = 'opcao-row';
    div.style.cssText = 'display:flex;gap:8px;align-items:center;';
    div.innerHTML = '<span style="color:var(--muted);font-size:.85rem;width:20px;text-align:right;">' + totalOpcoes + '.</span>' +
      '<input type="text" name="opcoes[]" placeholder="Opção ' + totalOpcoes + '" maxlength="200" style="flex:1;" class="opcao-input">' +
      '<button type="button" class="btn btn-danger btn-sm btn-remover-opcao" title="Remover"><i class="fa-solid fa-xmark"></i></button>';
    lista.appendChild(div);
    div.querySelector('input').focus();
    div.querySelector('.btn-remover-opcao').addEventListener('click', remover);
  });

  function remover(){
    var rows = lista.querySelectorAll('.opcao-row');
    if(rows.length <= 2){ alert('A enquete precisa ter pelo menos 2 opções.'); return; }
    this.closest('.opcao-row').remove();
    atualizarNumeros();
  }

  lista.querySelectorAll('.btn-remover-opcao').forEach(function(btn){
    btn.addEventListener('click', remover.bind(btn));
  });
})();
</script>

<?php
// ============================================================
// LISTAGEM
// ============================================================
else:
    $enquetes = $pdo->query("
        SELECT e.*,
               COUNT(DISTINCT v.id)   AS total_votos,
               COUNT(DISTINCT o.id)   AS total_opcoes
        FROM enquetes e
        LEFT JOIN enquete_opcoes o ON o.enquete_id = e.id
        LEFT JOIN enquete_votos  v ON v.enquete_id = e.id
        GROUP BY e.id
        ORDER BY e.criado_em DESC
    ")->fetchAll();
?>
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
  <h1 style="font-size:1.3rem;font-weight:700;"><i class="fa-solid fa-poll-h" style="color:var(--primary);"></i> Enquetes</h1>
  <a href="enquetes.php?action=nova" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nova enquete</a>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= esc($flash['msg']) ?></div>
<?php endif; ?>

<?php if (empty($enquetes)): ?>
<div class="card" style="text-align:center;padding:48px 20px;color:var(--muted);">
  <i class="fa-solid fa-poll-h" style="font-size:3rem;margin-bottom:12px;display:block;opacity:.3;"></i>
  <p>Nenhuma enquete criada ainda.</p>
  <a href="enquetes.php?action=nova" class="btn btn-primary" style="margin-top:16px;">Criar primeira enquete</a>
</div>
<?php else: ?>
<div class="table-wrap">
  <table class="data-table">
    <thead>
      <tr>
        <th>Título</th>
        <th>Opções</th>
        <th>Votos</th>
        <th>Status</th>
        <th>Criado em</th>
        <th>Ações</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($enquetes as $e): ?>
      <tr>
        <td>
          <strong><?= esc($e['titulo']) ?></strong>
          <?php if ($e['descricao']): ?>
          <br><small style="color:var(--muted);"><?= esc(mb_substr($e['descricao'], 0, 60)) ?><?= mb_strlen($e['descricao']) > 60 ? '…' : '' ?></small>
          <?php endif; ?>
        </td>
        <td><?= $e['total_opcoes'] ?></td>
        <td><strong><?= $e['total_votos'] ?></strong></td>
        <td>
          <?php if ($e['ativo']): ?>
            <span class="badge badge-green">Ativa</span>
          <?php else: ?>
            <span class="badge badge-gray">Inativa</span>
          <?php endif; ?>
        </td>
        <td><?= date('d/m/Y', strtotime($e['criado_em'])) ?></td>
        <td>
          <div style="display:flex;gap:6px;flex-wrap:wrap;">
            <a href="enquetes.php?action=relatorio&id=<?= $e['id'] ?>" class="btn btn-outline btn-sm" title="Relatório">
              <i class="fa-solid fa-chart-bar"></i>
            </a>
            <a href="enquetes.php?action=editar&id=<?= $e['id'] ?>" class="btn btn-outline btn-sm" title="Editar">
              <i class="fa-solid fa-pen"></i>
            </a>
            <!-- Toggle -->
            <form method="post" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action"     value="toggle">
              <input type="hidden" name="id"         value="<?= $e['id'] ?>">
              <button class="btn btn-sm <?= $e['ativo'] ? 'btn-warning' : 'btn-success' ?>" title="<?= $e['ativo'] ? 'Desativar' : 'Ativar' ?>">
                <i class="fa-solid fa-<?= $e['ativo'] ? 'eye-slash' : 'eye' ?>"></i>
              </button>
            </form>
            <!-- Excluir -->
            <form method="post" style="display:inline;" onsubmit="return confirm('Excluir esta enquete e todos os votos?')">
              <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="action"     value="excluir">
              <input type="hidden" name="id"         value="<?= $e['id'] ?>">
              <button class="btn btn-danger btn-sm" title="Excluir">
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

<?php endif; ?>

<?php require_once __DIR__ . '/_footer.php'; ?>
