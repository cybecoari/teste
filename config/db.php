<?php
// ==============================================
// CONFIGURAÇÕES DO BANCO E TOKENS
// ==============================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

// DADOS DO BANCO
$host = "localhost";
$db   = "cybe3195_teste";
$user = "cybe3195_teste";
$pass = "@cybercoari";

// ==============================================
// CONSTANTES GLOBAIS (acessíveis em todo projeto)
// ==============================================

// TELEGRAM
define('TELEGRAM_TOKEN', '7838148953:AAE9S3mZDB-kD6XrF4c2n5oEzjZxuXlU7wE');

// URLs do sistema
define('BASE_URL', 'https://cybercoari.com.br');
define('WEBHOOK_MP_URL', BASE_URL . '/api/webhook.php');

// ==============================================
// CONEXÃO PDO
// ==============================================

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    // Log do erro (sem expor detalhes ao usuário)
    error_log("Erro no banco de dados: " . $e->getMessage());
    die("Erro interno no banco de dados. Tente novamente mais tarde.");
}

// ==============================================
// FUNÇÃO PARA VERIFICAR SE TABELAS EXISTEM
// ==============================================

function verificarTabelas($pdo) {
    $tabelas_necessarias = ['pedidos_telegram', 'keys_telegram'];
    $faltam_tabelas = false;
    
    foreach ($tabelas_necessarias as $tabela) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$tabela]);
        if ($stmt->rowCount() == 0) {
            $faltam_tabelas = true;
            error_log("Tabela faltando: {$tabela}");
        }
    }
    
    if ($faltam_tabelas) {
        die("❌ Erro de configuração: Tabelas do banco não encontradas. Execute o SQL de instalação.");
    }
}

// Verifica tabelas (descomente quando as tabelas estiverem criadas)
// verificarTabelas($pdo);
?>