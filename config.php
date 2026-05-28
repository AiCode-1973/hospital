<?php
/**
 * config.php
 * Configurações de conexão com o banco de dados MySQL via PDO
 * Hospital Santo Expedito - APAS
 */

// --- Ambiente: troque os valores conforme o ambiente ---
// LOCAL (XAMPP):  DB_USER = 'root', DB_PASS = ''
// PRODUCAO (cPanel): DB_USER = 'apassa73__hospital_santo_expedito', DB_PASS = 'Dema@1973'
define('DB_HOST', 'localhost');
define('DB_NAME', 'apassa73__hospital_santo_expedito');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'Hospital Santo Expedito - APAS');
define('SITE_EMAIL', 'contato@hospitalsantoexpedito.com.br');
define('SITE_PHONE', '(13) 3226-5000');
define('SITE_WHATSAPP', '5513974040563');
define('SITE_ADDRESS', 'Rua Carvalho Mendonça, 335 - Vila Belmiro, Santos - SP, 011070-101');
define('GOOGLE_MAPS_URL', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3657.098!2d-46.6542!3d-23.5646!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zMjPCsDMzJzUyLjYiUyA0NsKwMzknMTUuMSJX!5e0!3m2!1spt-BR!2sbr!4v1620000000000');

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
