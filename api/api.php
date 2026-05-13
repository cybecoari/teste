<?php
// ==============================================
// WEBHOOK DO MERCADO PAGO - VERSÃO CORRIGIDA
// ==============================================

// CONFIGURAÇÕES (USANDO AS MESMAS DO SEU BOT)
define('TELEGRAM_TOKEN', '7838148953:AAE9S3mZDB-kD6XrF4c2n5oEzjZxuXlU7wE');
define('MP_ACCESS_TOKEN', 'APP_USR-6051070112657004-041817-58f9464f55dc507b51a24891d5c1cbb9-2069289633');

// BANCO DE DADOS (MESMO DO SEU BOT)
$host = "localhost";
$db   = "cybe3195_teste";
$user = "cybe3195_teste";
$pass = "@cybercoari";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    file_put_contents(__DIR__ . '/webhook_erro.txt', date('Y-m-d H:i:s') . " - Banco: " . $e->getMessage() . "\n", FILE_APPEND);
    http_response_code(500);
    exit;
}

// LOG PARA DEBUG
$input = file_get_contents('php://input');
file_put_contents(__DIR__ . '/mp_log.txt', date('Y-m-d H:i:s') . ' - ' . $input . PHP_EOL, FILE_APPEND);

// PROCESSA NOTIFICAÇÃO
$data = json_decode($input, true);
$payment_id = $data['data']['id'] ?? null;

if (!$payment_id) {
    http_response_code(200);
    exit;
}

// BUSCAR PAGAMENTO NO MERCADO PAGO
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/$payment_id");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . MP_ACCESS_TOKEN]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$resp = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode != 200) {
    file_put_contents(__DIR__ . '/webhook_erro.txt', date('Y-m-d H:i:s') . " - HTTP $httpCode ao buscar payment $payment_id\n", FILE_APPEND);
    http_response_code(200);
    exit;
}

$payment = json_decode($resp, true);

if ($payment['status'] != 'approved') {
    file_put_contents(__DIR__ . '/mp_log.txt', date('Y-m-d H:i:s') . " - Pagamento $payment_id status: " . $payment['status'] . "\n", FILE_APPEND);
    http_response_code(200);
    exit;
}

// PEGAR REFERÊNCIA DO PEDIDO
$pedido_id = $payment['external_reference'];
$chat_id = $payment['metadata']['chat_id'] ?? null;

if (!$pedido_id || !$chat_id) {
    file_put_contents(__DIR__ . '/webhook_erro.txt', date('Y-m-d H:i:s') . " - Pedido ID ou Chat ID não encontrado\n", FILE_APPEND);
    http_response_code(200);
    exit;
}

// BUSCAR PEDIDO NO BANCO
$stmt = $pdo->prepare("SELECT * FROM pedidos_telegram WHERE id = ? AND status = 'pending'");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch();

if (!$pedido) {
    file_put_contents(__DIR__ . '/webhook_erro.txt', date('Y-m-d H:i:s') . " - Pedido $pedido_id não encontrado ou já pago\n", FILE_APPEND);
    http_response_code(200);
    exit;
}

// FUNÇÃO GERAR KEY (USANDO BANCO)
function gerarKey($pdo, $chat_id, $dias) {
    $prefixos = ['CYBER', 'COARI', 'PREMIUM', 'MASTER'];
    $prefixo = $prefixos[array_rand($prefixos)];
    
    do {
        $key = $prefixo . '-' . 
               substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4) . '-' .
               substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4) . '-' .
               substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
        
        $stmt = $pdo->prepare("SELECT key_code FROM keys_telegram WHERE key_code = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetch();
    } while ($exists);
    
    $expira = time() + ($dias * 86400);
    $stmt = $pdo->prepare("INSERT INTO keys_telegram (key_code, chat_id, plano_dias, expira_em) VALUES (?, ?, ?, ?)");
    $stmt->execute([$key, $chat_id, $dias, $expira]);
    
    return $key;
}

// GERAR KEY
$key = gerarKey($pdo, $chat_id, $pedido['plano_dias']);

// ATUALIZAR PEDIDO
$stmt = $pdo->prepare("UPDATE pedidos_telegram SET status = 'paid', key_gerada = ? WHERE id = ?");
$stmt->execute([$key, $pedido_id]);

// ENVIAR MENSAGEM NO TELEGRAM
$api_telegram = "https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/";
$mensagem = "✅ *PAGAMENTO CONFIRMADO!*\n\n";
$mensagem .= "🔑 *SUA KEY:*\n<code>$key</code>\n\n";
$mensagem .= "📅 *Validade:* {$pedido['plano_dias']} dias\n";
$mensagem .= "💾 Guarde sua key em um local seguro!";

$url = $api_telegram . "sendMessage?chat_id=" . $chat_id . "&text=" . urlencode($mensagem) . "&parse_mode=HTML";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_exec($ch);
curl_close($ch);

// LOG DE SUCESSO
file_put_contents(__DIR__ . '/mp_log.txt', date('Y-m-d H:i:s') . " - Pagamento $payment_id aprovado! Key $key enviada para chat $chat_id\n", FILE_APPEND);

http_response_code(200);
echo "OK";
?>