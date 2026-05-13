<?php
// config/session.php - Configuração de sessão

// Iniciar sessão de forma segura
function iniciarSessao() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return true;
}

// Destruir sessão de forma segura
function destruirSessao() {
    iniciarSessao();
    $_SESSION = array();
    
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
}

// Verificar se está logado
function estaLogado() {
    iniciarSessao();
    return isset($_SESSION['user_id']);
}

// Função chamada no início de TODOS os arquivos
iniciarSessao();
?>