<?php
/**
 * admin/auth.php
 * Autenticação, sessão segura e setup da tabela admin_usuarios
 */
declare(strict_types=1);

require_once __DIR__ . '/../config.php';

// Inicia sessão segura
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'use_strict_mode' => true,
    ]);
}

// Cria tabela e usuário padrão se não existirem
(static function (): void {
    try {
        $pdo = getDB();
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admin_usuarios (
                id            INT          AUTO_INCREMENT PRIMARY KEY,
                usuario       VARCHAR(60)  NOT NULL UNIQUE,
                senha_hash    VARCHAR(255) NOT NULL,
                nome          VARCHAR(120) NOT NULL DEFAULT 'Administrador',
                ativo         TINYINT(1)   NOT NULL DEFAULT 1,
                ultimo_acesso DATETIME     NULL,
                criado_em     TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        $count = (int) $pdo->query('SELECT COUNT(*) FROM admin_usuarios')->fetchColumn();
        if ($count === 0) {
            $hash = password_hash('Admin@2024', PASSWORD_BCRYPT);
            $pdo->prepare('INSERT INTO admin_usuarios (usuario, senha_hash, nome) VALUES (?, ?, ?)')
                ->execute(['admin', $hash, 'Administrador']);
        }
    } catch (\PDOException $e) {
        error_log('Admin setup error: ' . $e->getMessage());
    }
})();

// ---------- Helpers ----------

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfVerify(string $token): bool
{
    return isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function requireLogin(): void
{
    if (empty($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit;
    }
}

function adminName(): string
{
    return $_SESSION['admin_nome'] ?? 'Admin';
}

function setFlash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}
