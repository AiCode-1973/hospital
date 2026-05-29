<?php
/**
 * vote.php — Endpoint AJAX para registrar votos nas enquetes
 * Hospital Santo Expedito - APAS
 */
declare(strict_types=1);
require_once __DIR__ . '/config.php';

header('Content-Type: application/json; charset=utf-8');

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'erro' => 'Método inválido']);
    exit;
}

$enqueteId = (int)($_POST['enquete_id'] ?? 0);
$opcaoId   = (int)($_POST['opcao_id']   ?? 0);

if (!$enqueteId || !$opcaoId) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'erro' => 'Dados inválidos']);
    exit;
}

// Hash do IP para privacidade (não armazena o IP real)
$ip     = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0')[0]);
$ipHash = hash('sha256', $ip . 'hse_poll_2024');

try {
    $pdo = getDB();

    // Verifica se a opção pertence à enquete ativa
    $stmt = $pdo->prepare("
        SELECT e.id
        FROM enquetes e
        JOIN enquete_opcoes o ON o.enquete_id = e.id AND o.id = ?
        WHERE e.id = ? AND e.ativo = 1
    ");
    $stmt->execute([$opcaoId, $enqueteId]);

    if (!$stmt->fetch()) {
        echo json_encode(['ok' => false, 'erro' => 'Enquete ou opção inválida']);
        exit;
    }

    // Verifica voto duplicado
    $dup = $pdo->prepare('SELECT COUNT(*) FROM enquete_votos WHERE enquete_id = ? AND ip_hash = ?');
    $dup->execute([$enqueteId, $ipHash]);

    if ((int)$dup->fetchColumn() > 0) {
        echo json_encode(['ok' => true, 'ja_votou' => true, 'resultado' => getResultado($pdo, $enqueteId)]);
        exit;
    }

    // Registra voto
    $pdo->prepare('INSERT INTO enquete_votos (enquete_id, opcao_id, ip_hash) VALUES (?, ?, ?)')
        ->execute([$enqueteId, $opcaoId, $ipHash]);

    echo json_encode(['ok' => true, 'ja_votou' => false, 'resultado' => getResultado($pdo, $enqueteId)]);

} catch (PDOException $e) {
    error_log('Erro ao votar: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'erro' => 'Erro interno']);
}

function getResultado(PDO $pdo, int $enqueteId): array
{
    $stmt = $pdo->prepare("
        SELECT o.id, o.texto, COUNT(v.id) AS votos
        FROM enquete_opcoes o
        LEFT JOIN enquete_votos v ON v.opcao_id = o.id
        WHERE o.enquete_id = ?
        GROUP BY o.id, o.texto
        ORDER BY o.ordem, o.id
    ");
    $stmt->execute([$enqueteId]);
    $rows  = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $total = (int)array_sum(array_column($rows, 'votos'));

    foreach ($rows as &$r) {
        $r['votos'] = (int)$r['votos'];
        $r['pct']   = $total > 0 ? round($r['votos'] / $total * 100, 1) : 0.0;
    }
    return ['opcoes' => $rows, 'total' => $total];
}
