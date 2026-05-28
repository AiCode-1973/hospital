<?php
/**
 * diagnostico.php — Diagnóstico de conexão com o banco de dados
 * ATENÇÃO: delete este arquivo do servidor após usar!
 * Acesso: https://hsesantos.com.br/diagnostico.php?token=hse@diag2024
 */
declare(strict_types=1);

// Proteção por token — altere se quiser
$token_esperado = 'hse@diag2024';
if (($_GET['token'] ?? '') !== $token_esperado) {
    http_response_code(403);
    die('Acesso negado.');
}

require_once __DIR__ . '/config.php';

$ok   = false;
$erro = '';
$tabelas = [];

try {
    $pdo     = getDB();
    $ok      = true;
    $tabelas = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
} catch (\Throwable $e) {
    $erro = $e->getMessage();
}

// Verifica arquivo de log
$logFile    = __DIR__ . '/logs/db_error.log';
$logContent = file_exists($logFile) ? file_get_contents($logFile) : '(sem registros)';
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Diagnóstico — HSE</title>
  <style>
    body  { font-family: monospace; padding: 24px; background:#f5f5f5; }
    h2    { color:#0057A8; }
    .ok   { color:green; font-weight:bold; }
    .err  { color:red;   font-weight:bold; }
    pre   { background:#fff; border:1px solid #ddd; padding:12px; border-radius:6px; overflow:auto; }
    table { border-collapse:collapse; margin-top:10px; }
    td,th { border:1px solid #ccc; padding:6px 12px; text-align:left; }
    th    { background:#eee; }
  </style>
</head>
<body>
<h2>🔍 Diagnóstico de Conexão — Hospital Santo Expedito</h2>
<hr>

<h3>Ambiente</h3>
<table>
  <tr><th>PHP Version</th>       <td><?= PHP_VERSION ?></td></tr>
  <tr><th>HTTP_HOST</th>         <td><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? '—') ?></td></tr>
  <tr><th>DB_HOST</th>           <td><?= DB_HOST ?></td></tr>
  <tr><th>DB_NAME</th>           <td><?= DB_NAME ?></td></tr>
  <tr><th>DB_USER</th>           <td><?= DB_USER ?></td></tr>
  <tr><th>DB_PASS</th>           <td><?= str_repeat('*', strlen(DB_PASS)) ?></td></tr>
  <tr><th>SITE_URL</th>          <td><?= SITE_URL ?></td></tr>
  <tr><th>display_errors</th>    <td><?= ini_get('display_errors') ?></td></tr>
</table>

<h3 style="margin-top:20px;">Conexão PDO</h3>
<?php if ($ok): ?>
  <p class="ok">✓ Conexão bem-sucedida!</p>
  <p>Tabelas encontradas (<?= count($tabelas) ?>):
    <strong><?= htmlspecialchars(implode(', ', $tabelas)) ?></strong>
  </p>
<?php else: ?>
  <p class="err">✗ Falha na conexão!</p>
  <pre><?= htmlspecialchars($erro) ?></pre>
<?php endif; ?>

<h3 style="margin-top:20px;">Último log de erro de DB</h3>
<pre><?= htmlspecialchars($logContent) ?></pre>

<hr>
<p style="color:#c00;font-size:.85em;">
  ⚠️ <strong>ATENÇÃO:</strong> Delete o arquivo <code>diagnostico.php</code> do servidor após concluir o diagnóstico.
</p>
</body>
</html>
