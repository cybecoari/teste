<?php
require __DIR__ . "/config/config.php";

// Limpar cookie de lembrar-me
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Destruir sessão
$_SESSION = array();
session_destroy();

header("Location: login.php");
exit;
?>