<?php
// licencas/cron_jobs.php - Tarefas agendadas do sistema
// Executar diariamente via cron job

require __DIR__ . "/../config/config.php";

// Definir timezone
date_default_timezone_set('America/Sao_Paulo');

// Criar pasta de logs se não existir
$log_dir = __DIR__ . '/../logs';
if (!file_exists($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// Função para escrever log
function escreverLog($mensagem, $arquivo = 'cron_log.txt') {
    global $log_dir;
    $data = date('Y-m-d H:i:s');
    $log = "[$data] $mensagem" . PHP_EOL;
    file_put_contents($log_dir . '/' . $arquivo, $log, FILE_APPEND);
    echo $mensagem . "\n";
}

// Iniciar log
escreverLog("========================================");
escreverLog("🕐 SISTEMA DE TAREFAS AGENDADAS");
escreverLog("Data: " . date('Y-m-d H:i:s'));
escreverLog("========================================\n");

// ========== 1. VERIFICAR LICENÇAS PRÓXIMAS DO VENCIMENTO ==========
escreverLog("1️⃣ VERIFICANDO LICENÇAS PRÓXIMAS DO VENCIMENTO");
escreverLog("----------------------------------------");

$dias = [30, 15, 7, 3, 1];
$totalNotificacoes = 0;

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
        
        try {
            // Verificar se já foi enviada notificação hoje
            $check = $pdo->prepare("SELECT COUNT(*) FROM notificacoes WHERE usuario_id = ? AND tipo = 'expiracao' AND DATE(criado_em) = CURDATE()");
            $check->execute([$licenca['usuario_id']]);
            $ja_enviado = $check->fetchColumn();
            
            if ($ja_enviado == 0) {
                $notif = $pdo->prepare("INSERT INTO notificacoes (usuario_id, tipo, titulo, mensagem, criado_em) VALUES (?, 'expiracao', ?, ?, NOW())");
                $notif->execute([$licenca['usuario_id'], $titulo, $mensagem]);
                $totalNotificacoes++;
                escreverLog("   ✅ {$licenca['email']} - expira em $dia dias");
            }
        } catch (PDOException $e) {
            escreverLog("   ❌ Erro: {$licenca['email']} - " . $e->getMessage());
        }
    }
}

escreverLog("\n📊 Notificações criadas: $totalNotificacoes\n");

// ========== 2. ATUALIZAR LICENÇAS EXPIRADAS ==========
escreverLog("2️⃣ ATUALIZANDO LICENÇAS EXPIRADAS");
escreverLog("----------------------------------------");

$update = $pdo->prepare("
    UPDATE licencas 
    SET status = 'expirada' 
    WHERE status = 'ativa' 
      AND data_expiracao IS NOT NULL 
      AND data_expiracao < NOW()
");
$update->execute();
$expiradas = $update->rowCount();

escreverLog("   ✅ $expiradas licenças marcadas como expiradas\n");

// ========== 3. LIMPAR PEDIDOS PENDENTES ANTIGOS ==========
escreverLog("3️⃣ LIMPANDO PEDIDOS PENDENTES ANTIGOS");
escreverLog("----------------------------------------");

// Verificar se a tabela pedidos_telegram existe
try {
    $update_pedidos = $pdo->prepare("
        UPDATE pedidos_telegram 
        SET status = 'expired' 
        WHERE status = 'pending' 
          AND data_criacao < DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $update_pedidos->execute();
    $pedidos_expirados = $update_pedidos->rowCount();
    escreverLog("   ✅ $pedidos_expirados pedidos pendentes expirados");
} catch (PDOException $e) {
    escreverLog("   ⚠️ Tabela pedidos_telegram não encontrada: " . $e->getMessage());
}
escreverLog("");

// ========== 4. LIMPAR LOGS ANTIGOS ==========
escreverLog("4️⃣ LIMPANDO LOGS ANTIGOS");
escreverLog("----------------------------------------");

// Logs de login com mais de 90 dias
try {
    $pdo->exec("DELETE FROM logs_login WHERE data < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    escreverLog("   ✅ Logs de login limpos");
} catch (PDOException $e) {
    escreverLog("   ⚠️ Tabela logs_login não encontrada");
}

// Tentativas de login com mais de 30 dias (se existir)
try {
    $pdo->exec("DELETE FROM tentativas_login WHERE tentativa < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    escreverLog("   ✅ Tentativas de login limpas");
} catch (PDOException $e) {
    escreverLog("   ⚠️ Tabela tentativas_login não encontrada");
}

// Notificações lidas com mais de 90 dias
try {
    $pdo->exec("DELETE FROM notificacoes WHERE lida = 1 AND criado_em < DATE_SUB(NOW(), INTERVAL 90 DAY)");
    escreverLog("   ✅ Notificações antigas limpas");
} catch (PDOException $e) {
    escreverLog("   ⚠️ Tabela notificacoes não encontrada");
}

// Sessões com mais de 7 dias
try {
    $sessoes = $pdo->exec("DELETE FROM php_sessions WHERE last_accessed < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    escreverLog("   ✅ Sessões antigas limpas");
} catch (PDOException $e) {
    escreverLog("   ⚠️ Tabela php_sessions não encontrada");
}
escreverLog("");

// ========== 5. VERIFICAR KEYS EXPIRADAS ==========
escreverLog("5️⃣ VERIFICANDO KEYS DO TELEGRAM EXPIRADAS");
escreverLog("----------------------------------------");

try {
    $update_keys = $pdo->prepare("
        UPDATE keys_telegram 
        SET ativo = 0 
        WHERE ativo = 1 
          AND expira_em IS NOT NULL 
          AND expira_em < ?
    ");
    $update_keys->execute([time()]);
    $keys_expiradas = $update_keys->rowCount();
    escreverLog("   ✅ $keys_expiradas keys do Telegram expiradas");
} catch (PDOException $e) {
    escreverLog("   ⚠️ Tabela keys_telegram não encontrada");
}
escreverLog("");

// ========== 6. ESTATÍSTICAS DO SISTEMA ==========
escreverLog("6️⃣ ESTATÍSTICAS DO SISTEMA");
escreverLog("----------------------------------------");

$totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalLicencas = $pdo->query("SELECT COUNT(*) FROM licencas")->fetchColumn();
$licencasAtivas = $pdo->query("SELECT COUNT(*) FROM licencas WHERE status = 'ativa'")->fetchColumn();
$notificacoesNaoLidas = $pdo->query("SELECT COUNT(*) FROM notificacoes WHERE lida = 0")->fetchColumn();

// Total de vendas (se a tabela existir)
try {
    $totalVendas = $pdo->query("SELECT COUNT(*) FROM pedidos_telegram WHERE status = 'paid'")->fetchColumn();
    $totalReceita = $pdo->query("SELECT SUM(valor) FROM pedidos_telegram WHERE status = 'paid'")->fetchColumn();
    escreverLog("   💰 Total de vendas: " . ($totalVendas ?? 0));
    escreverLog("   💵 Receita total: R$ " . number_format($totalReceita ?? 0, 2, ',', '.'));
} catch (PDOException $e) {
    escreverLog("   ⚠️ Tabela pedidos_telegram não encontrada");
}

escreverLog("   👥 Usuários totais: $totalUsuarios");
escreverLog("   🎫 Licenças totais: $totalLicencas");
escreverLog("   ✅ Licenças ativas: $licencasAtivas");
escreverLog("   🔔 Notificações não lidas: $notificacoesNaoLidas\n");

// ========== 7. GERAR RELATÓRIO DIÁRIO ==========
escreverLog("7️⃣ GERANDO RELATÓRIO DIÁRIO");
escreverLog("----------------------------------------");

$hoje = date('Y-m-d');
$relatorio = "=== RELATÓRIO DIÁRIO ===\n";
$relatorio .= "Data: $hoje\n";
$relatorio .= "Usuários totais: $totalUsuarios\n";
$relatorio .= "Licenças totais: $totalLicencas\n";
$relatorio .= "Licenças ativas: $licencasAtivas\n";
$relatorio .= "Notificações enviadas: $totalNotificacoes\n";
$relatorio .= "Licenças expiradas: $expiradas\n";

if (isset($totalVendas)) {
    $relatorio .= "Vendas totais: $totalVendas\n";
    $relatorio .= "Receita total: R$ " . number_format($totalReceita ?? 0, 2, ',', '.') . "\n";
}

file_put_contents($log_dir . '/relatorio_' . $hoje . '.txt', $relatorio);
escreverLog("   📄 Relatório salvo: logs/relatorio_$hoje.txt\n");

// ========== 8. FINALIZAR ==========
escreverLog("========================================");
escreverLog("✅ TAREFAS CONCLUÍDAS COM SUCESSO!");
escreverLog("========================================");