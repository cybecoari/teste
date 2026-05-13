<?php
// pagamento/download.php

require_once __DIR__ . '/../config/config.php';

if (!isset($_SESSION['download_arquivo'])) {
    header("Location: index.php");
    exit;
}

$arquivo = $_SESSION['download_arquivo'];
$caminho_arquivo = __DIR__ . "/../arquivos_protegidos/" . $arquivo;

if (!file_exists($caminho_arquivo)) {
    die("❌ Arquivo não encontrado. Contate o suporte.");
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $arquivo . '"');
header('Content-Length: ' . filesize($caminho_arquivo));
header('Cache-Control: no-cache, must-revalidate');

readfile($caminho_arquivo);

unset($_SESSION['download_token'], $_SESSION['download_arquivo'], $_SESSION['payment_id'], $_SESSION['external_reference']);
exit;
?>