<?php
/**
 * config.php
 * Configurações globais — detecta ambiente automaticamente
 * Hospital Santo Expedito - APAS
 *
 * LOCAL   : http://localhost/hospital/
 * PRODUÇÃO: https://hsesantos.com.br/
 */

// ============================================================
// Detecção de ambiente
// ============================================================
$_host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_isProd = !in_array($_host, ['localhost', '127.0.0.1', '::1'], true)
           && !str_starts_with($_host, '192.168.')
           && !str_ends_with($_host, '.local');

// ============================================================
// Banco de dados
// ============================================================
if ($_isProd) {
    // ── PRODUÇÃO (cPanel — hsesantos.com.br) ──────────────────
    define('DB_HOST',    'localhost');
    define('DB_NAME',    'apassa73__hospital_santo_expedito');
    define('DB_USER',    'apassa73__hospital_santo_expedito');
    define('DB_PASS',    'Dema@1973');
} else {
    // ── LOCAL (XAMPP) ─────────────────────────────────────────
    define('DB_HOST',    'localhost');
    define('DB_NAME',    'apassa73__hospital_santo_expedito');
    define('DB_USER',    'root');
    define('DB_PASS',    '');
}

define('DB_CHARSET', 'utf8mb4');

// ============================================================
// URL do site
// ============================================================
define('SITE_URL', $_isProd
    ? 'https://hsesantos.com.br'
    : 'http://localhost/hospital'
);

// ============================================================
// Informações do hospital
// ============================================================
define('SITE_NAME',     'Hospital Santo Expedito - APAS');
define('SITE_EMAIL',    'centraldeatendimentoaocliente@apassantos.com.br');
define('SITE_PHONE',    '(13) 3226-5000');
define('SITE_WHATSAPP', '5513974040563');
define('SITE_ADDRESS',  'Rua Carvalho Mendonça, 335 - Vila Belmiro, Santos - SP, 011070-101');
define('GOOGLE_MAPS_URL', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3646.199569938959!2d-46.33729502487824!3d-23.95338207853245!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x94ce036deb1d80b9%3A0xc99bd7367c894f81!2sR.%20Carvalho%20de%20Mendon%C3%A7a%2C%20335%20-%20Vila%20Belmiro%2C%20Santos%20-%20SP%2C%2011070-101!5e0!3m2!1spt-BR!2sbr!4v1779973707408!5m2!1spt-BR!2sbr" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade');

// ============================================================
// Configurações de erro por ambiente
// ============================================================
if ($_isProd) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(0);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}



/**
 * Cria e retorna uma instância PDO de conexão com o banco de dados.
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Em produção, logue o erro e exiba mensagem genérica
            error_log('Erro de conexão: ' . $e->getMessage());
            die('Serviço temporariamente indisponível. Tente novamente mais tarde.');
        }
    }

    return $pdo;
}

/**
 * Sanitiza string para exibição segura em HTML.
 */
function esc(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
