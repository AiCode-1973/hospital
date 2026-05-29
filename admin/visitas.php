<?php
/**
 * admin/visitas.php
 * Relatório de visitas à landing page — Hospital Santo Expedito - APAS
 */
declare(strict_types=1);
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$pdo = getDB();

// Garante tabela existente
$pdo->exec("
    CREATE TABLE IF NOT EXISTS visitas (
        id          INT          AUTO_INCREMENT PRIMARY KEY,
        ip_hash     VARCHAR(64)  NOT NULL,
        dispositivo ENUM('desktop','mobile','tablet') DEFAULT 'desktop',
        referrer    VARCHAR(500) NULL,
        visitado_em TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_data (visitado_em),
        INDEX idx_ip   (ip_hash)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

// ── Ação: limpar ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrfVerify($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Token inválido.'); header('Location: visitas.php'); exit;
    }
    if (($_POST['action'] ?? '') === 'limpar') {
        $pdo->exec('TRUNCATE TABLE visitas');
        setFlash('success', 'Histórico de visitas apagado.');
        header('Location: visitas.php'); exit;
    }
}

// ── Queries de resumo ────────────────────────────────────────
$totalVisitas  = (int)$pdo->query('SELECT COUNT(*) FROM visitas')->fetchColumn();
$totalUnicos   = (int)$pdo->query('SELECT COUNT(DISTINCT ip_hash) FROM visitas')->fetchColumn();
$visitasHoje   = (int)$pdo->query("SELECT COUNT(*) FROM visitas WHERE DATE(visitado_em) = CURDATE()")->fetchColumn();
$unicosHoje    = (int)$pdo->query("SELECT COUNT(DISTINCT ip_hash) FROM visitas WHERE DATE(visitado_em) = CURDATE()")->fetchColumn();
$visitasSemana = (int)$pdo->query("SELECT COUNT(*) FROM visitas WHERE visitado_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();

// ── Por dia — últimos 30 dias ────────────────────────────────
$porDia = $pdo->query("
    SELECT DATE(visitado_em) AS dia,
           COUNT(*) AS total,
           COUNT(DISTINCT ip_hash) AS unicos
    FROM visitas
    WHERE visitado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY dia
    ORDER BY dia
")->fetchAll();

// Preenche dias sem visitas (gaps) com zero
$diasIndexados = [];
foreach ($porDia as $r) { $diasIndexados[$r['dia']] = $r; }
$porDiaCompleto = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $porDiaCompleto[] = $diasIndexados[$d] ?? ['dia' => $d, 'total' => 0, 'unicos' => 0];
}
$maxDia = max(1, max(array_column($porDiaCompleto, 'total')));

// ── Por dispositivo ──────────────────────────────────────────
$porDispositivo = $pdo->query("
    SELECT dispositivo, COUNT(*) AS total
    FROM visitas
    GROUP BY dispositivo
    ORDER BY total DESC
")->fetchAll();
$totalDisp = max(1, array_sum(array_column($porDispositivo, 'total')));

// ── Por hora do dia (últimos 30 dias) ────────────────────────
$porHoraRaw = $pdo->query("
    SELECT HOUR(visitado_em) AS hora, COUNT(*) AS total
    FROM visitas
    WHERE visitado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY hora
    ORDER BY hora
")->fetchAll();
$porHoraIdx = [];
foreach ($porHoraRaw as $r) { $porHoraIdx[(int)$r['hora']] = (int)$r['total']; }
$porHora = [];
for ($h = 0; $h < 24; $h++) { $porHora[$h] = $porHoraIdx[$h] ?? 0; }
$maxHora = max(1, max($porHora));

// ── Top referrers ────────────────────────────────────────────
$topReferrers = $pdo->query("
    SELECT referrer, COUNT(*) AS total
    FROM visitas
    WHERE referrer IS NOT NULL AND referrer != ''
    GROUP BY referrer
    ORDER BY total DESC
    LIMIT 15
")->fetchAll();

// ── Últimas 20 visitas ───────────────────────────────────────
$ultimas = $pdo->query("
    SELECT dispositivo, referrer, visitado_em
    FROM visitas
    ORDER BY visitado_em DESC
    LIMIT 20
")->fetchAll();

$pageTitle = 'Relatório de Visitas';
require_once __DIR__ . '/_header.php';
$flash = getFlash();
?>

<!-- Cabeçalho -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px;">
  <h1 style="font-size:1.3rem;font-weight:700;">
    <i class="fa-solid fa-chart-line" style="color:var(--primary);"></i>
    Relatório de Visitas
  </h1>
  <form method="post" onsubmit="return confirm('Apagar TODO o histórico de visitas? Esta ação não pode ser desfeita.')">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="action"     value="limpar">
    <button class="btn btn-danger btn-sm"><i class="fa-solid fa-trash-can"></i> Limpar histórico</button>
  </form>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'error' ?>"><?= esc($flash['msg']) ?></div>
<?php endif; ?>

<!-- Cards de resumo -->
<div class="stats-grid" style="margin-bottom:24px;">
  <div class="stat-card" style="background:#eff6ff;border:1px solid #bfdbfe;">
    <div class="stat-icon blue"><i class="fa-solid fa-eye"></i></div>
    <div><strong><?= number_format($totalVisitas) ?></strong><span>Total de acessos</span></div>
  </div>
  <div class="stat-card" style="background:#f0fdf4;border:1px solid #bbf7d0;">
    <div class="stat-icon green"><i class="fa-solid fa-users"></i></div>
    <div><strong><?= number_format($totalUnicos) ?></strong><span>Visitantes únicos</span></div>
  </div>
  <div class="stat-card" style="background:#fefce8;border:1px solid #fde68a;">
    <div class="stat-icon" style="background:#fef9c3;color:#ca8a04;"><i class="fa-solid fa-calendar-day"></i></div>
    <div><strong><?= number_format($visitasHoje) ?></strong><span>Acessos hoje</span></div>
  </div>
  <div class="stat-card" style="background:#fdf4ff;border:1px solid #e9d5ff;">
    <div class="stat-icon purple"><i class="fa-solid fa-user-check"></i></div>
    <div><strong><?= number_format($unicosHoje) ?></strong><span>Únicos hoje</span></div>
  </div>
  <div class="stat-card" style="background:#fff7ed;border:1px solid #fed7aa;">
    <div class="stat-icon orange"><i class="fa-solid fa-calendar-week"></i></div>
    <div><strong><?= number_format($visitasSemana) ?></strong><span>Acessos (7 dias)</span></div>
  </div>
</div>

<!-- Gráfico de barras — últimos 30 dias -->
<div class="card" style="margin-bottom:24px;">
  <div class="card-header">
    <h2><i class="fa-solid fa-chart-bar"></i> Acessos diários — últimos 30 dias</h2>
  </div>
  <div style="padding:20px 20px 0;">
    <?php if ($totalVisitas === 0): ?>
      <p style="color:var(--muted);text-align:center;padding:32px 0;">Nenhum acesso registrado ainda.</p>
    <?php else: ?>
    <div style="display:flex;align-items:flex-end;gap:3px;height:180px;padding-bottom:28px;overflow-x:auto;">
      <?php foreach ($porDiaCompleto as $row):
        $pct = $maxDia > 0 ? round($row['total'] / $maxDia * 100) : 0;
        $label = date('d/m', strtotime($row['dia']));
        $isToday = ($row['dia'] === date('Y-m-d'));
      ?>
      <div style="flex:1;min-width:18px;max-width:36px;display:flex;flex-direction:column;align-items:center;position:relative;height:100%;">
        <?php if ($row['total'] > 0): ?>
        <span style="font-size:.65rem;color:var(--muted);position:absolute;top:<?= 100 - $pct ?>%;transform:translateY(-110%);white-space:nowrap;">
          <?= $row['total'] ?>
        </span>
        <?php endif; ?>
        <div style="
          width:100%;
          height:<?= max(2, $pct) ?>%;
          background:<?= $isToday ? 'var(--success)' : 'var(--primary)' ?>;
          border-radius:4px 4px 0 0;
          margin-top:auto;
          transition:height .4s ease;
          opacity:<?= $row['total'] === 0 ? '.2' : '1' ?>;
        " title="<?= $label ?>: <?= $row['total'] ?> acesso<?= $row['total'] !== 1 ? 's' : '' ?>"></div>
        <span style="font-size:.6rem;color:var(--muted);position:absolute;bottom:-20px;transform:rotate(-45deg);transform-origin:top left;white-space:nowrap;">
          <?= $label ?>
        </span>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:16px;padding:8px 0 16px;font-size:.78rem;color:var(--muted);">
      <span><span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:var(--primary);margin-right:4px;vertical-align:middle;"></span>Acessos</span>
      <span><span style="display:inline-block;width:12px;height:12px;border-radius:2px;background:var(--success);margin-right:4px;vertical-align:middle;"></span>Hoje</span>
    </div>
    <?php endif; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">

  <!-- Dispositivos -->
  <div class="card">
    <div class="card-header"><h2><i class="fa-solid fa-mobile-screen-button"></i> Dispositivos</h2></div>
    <?php if (empty($porDispositivo)): ?>
      <p style="padding:20px;color:var(--muted);text-align:center;">Sem dados.</p>
    <?php else: ?>
    <div style="padding:20px;display:flex;flex-direction:column;gap:14px;">
      <?php
      $icones = ['desktop' => 'fa-desktop', 'mobile' => 'fa-mobile-screen', 'tablet' => 'fa-tablet-screen-button'];
      $labels = ['desktop' => 'Desktop', 'mobile' => 'Mobile', 'tablet' => 'Tablet'];
      foreach ($porDispositivo as $d):
        $pct = round($d['total'] / $totalDisp * 100, 1);
      ?>
      <div>
        <div style="display:flex;justify-content:space-between;font-size:.88rem;margin-bottom:5px;">
          <span><i class="fa-solid <?= $icones[$d['dispositivo']] ?? 'fa-computer' ?>" style="margin-right:6px;color:var(--primary);"></i><?= $labels[$d['dispositivo']] ?? $d['dispositivo'] ?></span>
          <span style="color:var(--muted);"><?= $d['total'] ?> (<?= $pct ?>%)</span>
        </div>
        <div style="background:#e5e7eb;border-radius:5px;height:16px;overflow:hidden;">
          <div style="width:<?= $pct ?>%;background:var(--primary);height:100%;border-radius:5px;"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Por hora -->
  <div class="card">
    <div class="card-header"><h2><i class="fa-solid fa-clock"></i> Acessos por hora do dia</h2></div>
    <div style="padding:16px 16px 8px;">
      <div style="display:flex;align-items:flex-end;gap:2px;height:100px;">
        <?php for ($h = 0; $h < 24; $h++):
          $v = $porHora[$h];
          $hPct = $maxHora > 0 ? round($v / $maxHora * 100) : 0;
          $hora = str_pad((string)$h, 2, '0', STR_PAD_LEFT) . 'h';
        ?>
        <div style="flex:1;display:flex;flex-direction:column;align-items:center;height:100%;" title="<?= $hora ?>: <?= $v ?>">
          <div style="width:100%;height:<?= max(2, $hPct) ?>%;background:var(--primary);border-radius:2px 2px 0 0;margin-top:auto;opacity:<?= $v === 0 ? '.15' : '.85' ?>;"></div>
        </div>
        <?php endfor; ?>
      </div>
      <div style="display:flex;justify-content:space-between;font-size:.65rem;color:var(--muted);margin-top:4px;padding:0 2px;">
        <span>00h</span><span>06h</span><span>12h</span><span>18h</span><span>23h</span>
      </div>
    </div>
  </div>

</div>

<!-- Top referrers -->
<?php if (!empty($topReferrers)): ?>
<div class="card" style="margin-bottom:24px;">
  <div class="card-header"><h2><i class="fa-solid fa-link"></i> Principais origens de tráfego</h2></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>#</th><th>Origem (Referrer)</th><th>Acessos</th></tr></thead>
      <tbody>
        <?php foreach ($topReferrers as $i => $ref): ?>
        <tr>
          <td style="color:var(--muted);width:32px;"><?= $i + 1 ?></td>
          <td style="word-break:break-all;max-width:400px;font-size:.85rem;">
            <?= esc(parse_url($ref['referrer'], PHP_URL_HOST) ?: $ref['referrer']) ?>
            <br><small style="color:var(--muted);font-size:.75rem;"><?= esc(mb_substr($ref['referrer'], 0, 80)) ?><?= mb_strlen($ref['referrer']) > 80 ? '…' : '' ?></small>
          </td>
          <td><?= $ref['total'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Últimas visitas -->
<div class="card">
  <div class="card-header"><h2><i class="fa-solid fa-list-ul"></i> Últimos 20 acessos</h2></div>
  <?php if (empty($ultimas)): ?>
    <p style="padding:20px;color:var(--muted);text-align:center;">Nenhum acesso registrado.</p>
  <?php else: ?>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Data / Hora</th><th>Dispositivo</th><th>Origem</th></tr></thead>
      <tbody>
        <?php foreach ($ultimas as $v): ?>
        <tr>
          <td style="white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($v['visitado_em'])) ?></td>
          <td>
            <?php
            $icDev = ['desktop' => 'fa-desktop', 'mobile' => 'fa-mobile-screen', 'tablet' => 'fa-tablet-screen-button'];
            $lbDev = ['desktop' => 'Desktop', 'mobile' => 'Mobile', 'tablet' => 'Tablet'];
            ?>
            <i class="fa-solid <?= $icDev[$v['dispositivo']] ?? 'fa-computer' ?>" style="color:var(--primary);margin-right:4px;"></i>
            <?= $lbDev[$v['dispositivo']] ?? esc($v['dispositivo']) ?>
          </td>
          <td style="font-size:.82rem;color:var(--muted);word-break:break-all;">
            <?= $v['referrer'] ? esc(parse_url($v['referrer'], PHP_URL_HOST) ?: mb_substr($v['referrer'], 0, 50)) : '<span style="color:#ccc;">—</span>' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/_footer.php'; ?>
