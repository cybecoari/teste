<?php
// teste_recuperar.php - Diagnóstico

require __DIR__ . "/../config/db.php";

echo "<h2>Diagnóstico do Sistema de Recuperação</h2>";

// Verificar se a coluna reset_token existe
echo "<h3>1. Verificando colunas da tabela usuarios:</h3>";
$colunas = $pdo->query("SHOW COLUMNS FROM usuarios")->fetchAll(PDO::FETCH_COLUMN);
echo "<pre>";
print_r($colunas);
echo "</pre>";

// Verificar se reset_equipa existe
if (in_array('reset_equipa', $colunas)) {
    echo "<p style='color:green'>✅ Coluna 'reset_equipa' encontrada!</p>";
} else {
    echo "<p style='color:red'>❌ Coluna 'reset_equipa' NÃO encontrada!</p>";
}

// Verificar se reset_token existe
if (in_array('reset_token', $colunas)) {
    echo "<p style='color:green'>✅ Coluna 'reset_token' encontrada!</p>";
} else {
    echo "<p style='color:red'>❌ Coluna 'reset_token' NÃO encontrada!</p>";
}

// Testar busca de usuário
echo "<h3>2. Testando busca de usuário:</h3>";
$email = "admin@cybercoari.com.br";
$sql = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE email = ?");
$sql->execute([$email]);
$user = $sql->fetch();

if ($user) {
    echo "<p style='color:green'>✅ Usuário encontrado: {$user['nome']} ({$user['email']})</p>";
    
    // Testar atualização
    echo "<h3>3. Testando atualização de token:</h3>";
    $token = bin2hex(random_bytes(32));
    $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    try {
        $update = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_equipa = ? WHERE id = ?");
        $update->execute([$token, $expiracao, $user['id']]);
        echo "<p style='color:green'>✅ Token atualizado com sucesso!</p>";
        echo "<p>Token: $token</p>";
        echo "<p>Expira em: $expiracao</p>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Erro: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color:red'>❌ Usuário não encontrado!</p>";
}

echo "<h3>4. Estrutura da tabela usuarios:</h3>";
$estrutura = $pdo->query("DESCRIBE usuarios")->fetchAll();
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th></tr>";
foreach ($estrutura as $campo) {
    echo "<tr>";
    echo "<td>{$campo['Field']}</td>";
    echo "<td>{$campo['Type']}</td>";
    echo "<td>{$campo['Null']}</td>";
    echo "<td>{$campo['Key']}</td>";
    echo "</tr>";
}
echo "</table>";
?>

<meta name="viewport" content="width=device-width, initial-scale=1.0">