<?php
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

try {
    $pdo = getDB();
    $esp  = (int) $pdo->query('SELECT COUNT(*) FROM especialidades WHERE ativo = 1')->fetchColumn();
    $med  = (int) $pdo->query('SELECT COUNT(*) FROM medicos        WHERE ativo = 1')->fetchColumn();
    $conv = (int) $pdo->query('SELECT COUNT(*) FROM convenios      WHERE ativo = 1')->fetchColumn();
    $dep  = (int) $pdo->query('SELECT COUNT(*) FROM depoimentos    WHERE ativo = 1')->fetchColumn();
    $avi  = (int) $pdo->query('SELECT COUNT(*) FROM avisos         WHERE ativo = 1')->fetchColumn();
    $enq  = (int) $pdo->query('SELECT COUNT(*) FROM enquete_votos')->fetchColumn();
} catch (\PDOException $e) {
    $esp = $med = $conv = $dep = $avi = $enq = 0;
}

$pageTitle = 'Dashboard';
require_once __DIR__ . '/_header.php';
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fa-solid fa-stethoscope"></i></div>
    <div>
      <strong><?= $esp ?></strong>
      <span>Especialidades ativas</span>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fa-solid fa-user-doctor"></i></div>
    <div>
      <strong><?= $med ?></strong>
      <span>Médicos ativos</span>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon orange"><i class="fa-solid fa-shield-heart"></i></div>
    <div>
      <strong><?= $conv ?></strong>
      <span>Convênios ativos</span>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon purple"><i class="fa-solid fa-comment-dots"></i></div>
    <div>
      <strong><?= $dep ?></strong>
      <span>Depoimentos ativos</span>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon red"><i class="fa-solid fa-bell"></i></div>
    <div>
      <strong><?= $avi ?></strong>
      <span>Avisos ativos</span>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon teal"><i class="fa-solid fa-check-to-slot"></i></div>
    <div>
      <strong><?= $enq ?></strong>
      <span>Votos nas enquetes</span>
    </div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2><i class="fa-solid fa-bolt"></i> Acesso rápido</h2>
  </div>
  <div style="display:flex;flex-wrap:wrap;gap:10px;">
    <a href="especialidades.php?action=novo" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Nova Especialidade</a>
    <a href="medicos.php?action=novo"        class="btn btn-success"><i class="fa-solid fa-plus"></i> Novo Médico</a>
    <a href="convenios.php?action=novo"      class="btn btn-outline"><i class="fa-solid fa-plus"></i> Novo Convênio</a>
    <a href="depoimentos.php?action=novo"    class="btn btn-secondary"><i class="fa-solid fa-plus"></i> Novo Depoimento</a>
    <a href="enquetes.php?action=nova"        class="btn btn-outline"><i class="fa-solid fa-plus"></i> Nova Enquete</a>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2><i class="fa-solid fa-circle-info"></i> Informações do Sistema</h2>
  </div>
  <table>
    <tbody>
      <tr>
        <td style="width:180px;color:var(--muted);font-size:.82rem;">PHP Version</td>
        <td><?= esc(PHP_VERSION) ?></td>
      </tr>
      <tr>
        <td style="color:var(--muted);font-size:.82rem;">Banco de dados</td>
        <td><?= esc(DB_NAME) ?></td>
      </tr>
      <tr>
        <td style="color:var(--muted);font-size:.82rem;">Servidor</td>
        <td><?= esc(DB_HOST) ?></td>
      </tr>
      <tr>
        <td style="color:var(--muted);font-size:.82rem;">Administrador</td>
        <td><?= esc(adminName()) ?></td>
      </tr>
      <tr>
        <td style="color:var(--muted);font-size:.82rem;">Data/Hora</td>
        <td><?= esc(date('d/m/Y H:i:s')) ?></td>
      </tr>
    </tbody>
  </table>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
