<?php
// app/config/paths.php - Definir caminhos absolutos

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', __DIR__);
define('CONFIG_PATH', __DIR__ . '/config');
define('INCLUDES_PATH', __DIR__ . '/includes');
define('VENDOR_PATH', ROOT_PATH . '/vendor');
define('URL_BASE', 'https://cybercoari.com.br/');

// Função para redirecionamento fácil
function redirect($path) {
    header("Location: " . URL_BASE . "/" . ltrim($path, '/'));
    exit;
}
?>