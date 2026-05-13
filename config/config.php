<?php
// config/config.php - Configuração principal com sessão no banco

// Forçar uso de pasta de sessão personalizada (fallback)
$session_path = __DIR__ . '/../sessions';
if (!file_exists($session_path)) {
    mkdir($session_path, 0755, true);
}
ini_set('session.save_path', $session_path);

require __DIR__ . "/db.php";

// ========== SEGURANÇA ==========

// Forçar HTTPS (apenas em produção)
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header("Location: https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Headers de segurança
header('X-Powered-By: Cyber Coari System');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');

// Configurações de sessão seguras
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Lax');

// ========== CRIAR TABELA DE SESSÕES ==========
try {
    $sql = "CREATE TABLE IF NOT EXISTS `php_sessions` (
        `session_id` VARCHAR(128) NOT NULL PRIMARY KEY,
        `data` LONGTEXT NOT NULL,
        `user_id` INT NULL,
        `ip_address` VARCHAR(45) NULL,
        `last_accessed` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_last_accessed (last_accessed)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
} catch (Exception $e) {
    // Tabela já existe
}

// ========== HANDLER DE SESSÃO NO BANCO ==========
class DatabaseSessionHandler implements SessionHandlerInterface {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function open($savePath, $sessionName): bool { return true; }
    public function close(): bool { return true; }
    
    public function read($sessionId): string|false {
        $stmt = $this->pdo->prepare("SELECT data FROM php_sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['data'] : '';
    }
    
    public function write($sessionId, $data): bool {
        $user_id = $_SESSION['user_id'] ?? null;
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
        
        $stmt = $this->pdo->prepare("
            REPLACE INTO php_sessions (session_id, data, user_id, ip_address, last_accessed) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        return $stmt->execute([$sessionId, $data, $user_id, $ip_address]);
    }
    
    public function destroy($sessionId): bool {
        $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE session_id = ?");
        return $stmt->execute([$sessionId]);
    }
    
    public function gc($maxLifetime): int|false {
        $stmt = $this->pdo->prepare("DELETE FROM php_sessions WHERE last_accessed < DATE_SUB(NOW(), INTERVAL ? SECOND)");
        $stmt->execute([$maxLifetime]);
        return $stmt->rowCount();
    }
}

// ========== REGISTRAR O HANDLER ==========
$handler = new DatabaseSessionHandler($pdo);
session_set_save_handler($handler, true);

// ========== INICIAR SESSÃO ==========
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerar ID a cada 30 minutos
if (!isset($_SESSION['last_regeneration'])) {
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

// ========== CONFIGURAÇÕES ==========
define('MAX_TENTATIVAS', 5);
define('BLOQUEIO_MINUTOS', 15);
define('SITE_NAME', 'Cyber Coari');
define('SITE_URL', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'cybercoari.com.br'));

// ========== FUNÇÕES AUXILIARES ==========

function estaLogado() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['perfil']) && $_SESSION['perfil'] === 'admin';
}

function setFlash($tipo, $mensagem) {
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function h($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

function logError($message, $context = []) {
    $logFile = __DIR__ . '/../logs/error.log';
    $dir = dirname($logFile);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    
    $log = date('Y-m-d H:i:s') . " - " . $message;
    if (!empty($context)) $log .= " - " . json_encode($context);
    $log .= PHP_EOL;
    
    file_put_contents($logFile, $log, FILE_APPEND);
}
?>