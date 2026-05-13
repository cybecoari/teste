<?php
// licencas/verificar_licencas.php - Apenas verificação de licenças

require __DIR__ . "/../config/config.php";

echo "🔔 Verificando licenças próximas do vencimento...\n\n";

$dias = [7, 3, 1];
$total = 0;

foreach ($dias as $dia) {
    $sql = $pdo->prepare("
        SELECT l.*, u.id as usuario_id, u.nome, u.email 
        FROM licencas l 
        JOIN usuarios u ON l.usuario_id = u.id 
        WHERE l.status = 'ativa' 
          AND l.data_expiracao IS NOT NULL
          AND DATEDIFF(l.data_expiracao, NOW()) = ?
    ");
    $sql->execute([$dia]);
    $licencas = $sql->fetchAll();
    
    foreach ($licencas as $licenca) {
        $titulo = "⚠️ Sua licença expira em $dia dias!";
        $mensagem = "Olá {$licenca['nome']}, sua licença do produto '{$licenca['produto']}' expira em $dia dias (em " . date('d/m/Y', strtotime($licenca['data_expiracao'])) . "). Renove para não perder o acesso.";
        
        $notif = $pdo->prepare("INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, criado_em) VALUES (?, 'expiracao', ?, ?, NOW())");
        $notif->execute([$licenca['usuario_id'], $titulo, $mensagem]);
        $total++;
        
        echo "✅ Notificação criada para {$licenca['email']} - expira em $dia dias\n";
    }
}

// Atualizar expiradas
$update = $pdo->prepare("UPDATE licencas SET status = 'expirada' WHERE status = 'ativa' AND data_expiracao < NOW()");
$update->execute();
$expiradas = $update->rowCount();

echo "\n📊 Resumo:\n";
echo "- Notificações criadas: $total\n";
echo "- Licenças expiradas: $expiradas\n";
echo "\n✅ Verificação concluída!\n";
?>