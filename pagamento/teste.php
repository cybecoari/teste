<?php
// pagamento/teste_completo.php - Teste completo do sistema

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/mercadopago_config.php';

$resultados = [];
$erros = [];

// ========== 1. TESTE DE CONEXÃO COM BANCO ==========
echo "<h2>🧪 TESTE COMPLETO DO SISTEMA</h2>\n";
echo "<div style='font-family: monospace;'>\n";

// Teste 1: Banco de Dados
try {
    $stmt = $pdo->query("SELECT 1");
    $resultados['banco'] = "✅ Conectado";
} catch (Exception $e) {
    $erros['banco'] = "❌ Erro: " . $e->getMessage();
}

// Teste 2: Tabelas
$tabelas = ['pedidos_telegram', 'keys_telegram', 'php_sessions', 'usuarios', 'licencas'];
foreach ($tabelas as $tabela) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM {$tabela}");
        $resultados["tabela_{$tabela}"] = "✅ Existe";
    } catch (Exception $e) {
        $erros["tabela_{$tabela}"] = "❌ Não existe";
    }
}

// Teste 3: Mercado Pago
if (defined('MP_ACCESS_TOKEN') && !empty(MP_ACCESS_TOKEN)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/v1/merchant_orders?limit=1');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . MP_ACCESS_TOKEN]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200) {
        $resultados['mercadopago'] = "✅ Token válido (HTTP $http_code)";
    } elseif ($http_code == 401) {
        $erros['mercadopago'] = "❌ Token inválido (HTTP $http_code)";
    } else {
        $erros['mercadopago'] = "⚠️ HTTP $http_code - Verifique";
    }
} else {
    $erros['mercadopago'] = "❌ MP_ACCESS_TOKEN não definido";
}

// Teste 4: Pastas
$pastas = [
    'sessions' => __DIR__ . '/../sessions',
    'pagamentos' => __DIR__ . '/pagamentos',
    'arquivos_protegidos' => __DIR__ . '/../arquivos_protegidos'
];

foreach ($pastas as $nome => $caminho) {
    if (file_exists($caminho)) {
        $resultados["pasta_{$nome}"] = "✅ Existe - " . $caminho;
    } else {
        $erros["pasta_{$nome}"] = "❌ Não existe - " . $caminho;
    }
}

// Teste 5: Permissões de escrita
foreach ($pastas as $nome => $caminho) {
    if (file_exists($caminho) && is_writable($caminho)) {
        $resultados["permissao_{$nome}"] = "✅ Gravável";
    } elseif (file_exists($caminho)) {
        $erros["permissao_{$nome}"] = "⚠️ Sem permissão de escrita";
    }
}

// Teste 6: Sessão
$_SESSION['teste_session'] = time();
if (isset($_SESSION['teste_session'])) {
    $resultados['sessao'] = "✅ Funcionando (ID: " . session_id() . ")";
} else {
    $erros['sessao'] = "❌ Sessão não está funcionando";
}

// ========== 2. TESTE DE CRIAÇÃO DE PIX ==========
echo "<h3>📱 Teste de Pagamento PIX</h3>\n";

// Criar pedido de teste
$teste_reference = 'TESTE_' . time() . '_' . rand(100, 999);
$teste_email = 'teste@cybercoari.com.br';

$data = [
    'transaction_amount' => 0.01,
    'description' => 'Teste de Integração',
    'payment_method_id' => 'pix',
    'payer' => ['email' => $teste_email],
    'external_reference' => $teste_reference,
    'notification_url' => 'https://cybercoari.com.br/api/webhook.php'
];

$ch = curl_init("https://api.mercadopago.com/v1/payments");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        'Content-Type: application/json',
        'X-Idempotency-Key: ' . uniqid()
    ],
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$result = json_decode($response, true);
curl_close($ch);

if ($http_code == 201 && isset($result['id'])) {
    $resultados['pix_criacao'] = "✅ PIX criado com sucesso (ID: {$result['id']})";
    $resultados['qr_code'] = "✅ QR Code gerado: " . (isset($result['point_of_interaction']['transaction_data']['qr_code']) ? "Sim" : "Não");
    
    // Salvar teste no arquivo
    file_put_contents(__DIR__ . '/pagamentos/' . $teste_reference . '.json', json_encode([
        'status' => 'pending',
        'email' => $teste_email,
        'payment_id' => $result['id'],
        'qr_code' => $result['point_of_interaction']['transaction_data']['qr_code'] ?? null,
        'data' => date('Y-m-d H:i:s')
    ]));
} else {
    $erros['pix_criacao'] = "❌ Erro ao criar PIX: " . ($result['message'] ?? 'Erro desconhecido');
}

// ========== 3. TESTE DE WEBHOOK ==========
echo "<h3>🔗 Teste do Webhook</h3>\n";

$webhook_url = "https://cybercoari.com.br/api/webhook.php";
$ch = curl_init($webhook_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['type' => 'test', 'test' => true]));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$webhook_response = curl_exec($ch);
$webhook_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($webhook_code == 200) {
    $resultados['webhook'] = "✅ Webhook acessível (HTTP $webhook_code)";
} else {
    $erros['webhook'] = "❌ Webhook não responde (HTTP $webhook_code)";
}

// ========== 4. TESTE DE DOWNLOAD ==========
echo "<h3>📥 Teste de Download</h3>\n";

$arquivo_teste = __DIR__ . "/../arquivos_protegidos/cybercoari_pro.zip";
if (!file_exists($arquivo_teste)) {
    file_put_contents($arquivo_teste, "Arquivo de teste - " . date('Y-m-d H:i:s'));
    $resultados['download_arquivo'] = "✅ Arquivo de teste criado";
} else {
    $resultados['download_arquivo'] = "✅ Arquivo já existe";
}

// ========== 5. TESTE DE INTEGRAÇÃO ==========
echo "<h3>🔧 Status do Sistema</h3>\n";

// Verificar configurações
$configs = [
    'PHP Version' => phpversion(),
    'PDO' => class_exists('PDO') ? '✅' : '❌',
    'cURL' => function_exists('curl_init') ? '✅' : '❌',
    'JSON' => function_exists('json_decode') ? '✅' : '❌',
];

foreach ($configs as $key => $value) {
    $resultados["config_{$key}"] = "$value";
}

// ========== EXIBIR RESULTADOS ==========
echo "<h3>📊 RESULTADOS DOS TESTES</h3>\n";

if (count($erros) > 0) {
    echo "<div style='background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 8px; margin: 15px 0;'>\n";
    echo "<strong>❌ ERROS ENCONTRADOS:</strong><br>\n";
    foreach ($erros as $teste => $erro) {
        echo "&nbsp;&nbsp;• $teste: $erro<br>\n";
    }
    echo "</div>\n";
} else {
    echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 8px; margin: 15px 0;'>\n";
    echo "<strong>✅ TODOS OS TESTES PASSARAM!</strong> Sistema funcionando perfeitamente.<br>\n";
    echo "</div>\n";
}

echo "<h3>✅ TESTES BEM-SUCEDIDOS</h3>\n";
echo "<div style='background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 15px; border-radius: 8px; margin: 15px 0; max-height: 300px; overflow: auto;'>\n";
foreach ($resultados as $teste => $resultado) {
    echo "&nbsp;&nbsp;• $teste: $resultado<br>\n";
}
echo "</div>\n";

// ========== BOTÕES DE AÇÃO ==========
?>
<div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 10px;">
    <h3>🎯 PRÓXIMOS PASSOS</h3>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 15px;">
        <a href="index.php" style="background: #28a745; color: white; padding: 12px; text-align: center; text-decoration: none; border-radius: 8px;">
            🛒 Ir para Loja
        </a>
        <a href="checkout.php?produto_id=1" style="background: #667eea; color: white; padding: 12px; text-align: center; text-decoration: none; border-radius: 8px;">
            💰 Fazer Compra de Teste (R$ 0,01)
        </a>
        <a href="pagamentos/" style="background: #17a2b8; color: white; padding: 12px; text-align: center; text-decoration: none; border-radius: 8px;">
            📁 Ver Pagamentos
        </a>
        <a href="../admin/dashboard_vendas.php" style="background: #6c757d; color: white; padding: 12px; text-align: center; text-decoration: none; border-radius: 8px;">
            📊 Dashboard Admin
        </a>
    </div>
</div>

<script>
    // Auto-refresh para verificar pagamento
    if (<?= isset($result['id']) ? 'true' : 'false' ?>) {
        let tentativas = 0;
        const intervalo = setInterval(() => {
            fetch('check_payment.php')
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'approved') {
                        clearInterval(intervalo);
                        location.reload();
                    }
                });
            if (++tentativas > 60) clearInterval(intervalo);
        }, 3000);
    }
</script>

<style>
    body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; padding: 20px; }
    h2, h3 { color: #333; margin-top: 20px; }
    .container { max-width: 900px; margin: 0 auto; background: white; border-radius: 15px; padding: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
</style>

<div class="container">
    <?php
    // Mostrar últimos pagamentos
    echo "<h3>📋 Últimos Pagamentos Registrados</h3>\n";
    $arquivos = glob(__DIR__ . "/pagamentos/*.json");
    if (count($arquivos) > 0) {
        echo "<table style='width:100%; border-collapse: collapse;'>\n";
        echo "<tr style='background: #f8f9fa;'><th>Arquivo</th><th>Status</th><th>Data</th></tr>\n";
        foreach (array_reverse($arquivos) as $arquivo) {
            $conteudo = json_decode(file_get_contents($arquivo), true);
            $nome = basename($arquivo);
            $status = $conteudo['status'] ?? 'unknown';
            $data = $conteudo['data'] ?? '---';
            $status_color = $status == 'approved' ? '#28a745' : ($status == 'pending' ? '#ffc107' : '#dc3545');
            echo "<tr style='border-bottom: 1px solid #ddd;'>";
            echo "<td style='padding: 8px;'><code>$nome</code></td>";
            echo "<td style='padding: 8px;'><span style='background: $status_color; color: white; padding: 2px 8px; border-radius: 12px; font-size: 12px;'>$status</span></td>";
            echo "<td style='padding: 8px;'>$data</td>";
            echo "</tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>Nenhum pagamento registrado ainda.</p>\n";
    }
    ?>
</div>
</div>