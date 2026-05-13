<?php
// ==============================================
// WEBHOOK MERCADO PAGO
// ==============================================

// Configurações
define('TELEGRAM_TOKEN', '7838148953:AAE9S3mZDB-kD6XrF4c2n5oEzjZxuXlU7wE');
define('MP_ACCESS_TOKEN', 'APP_USR-6051070112657004-041817-58f9464f55dc507b51a24891d5c1cbb9-2069289633');

// Banco de dados
$host = "localhost";
$db   = "cybe3195_teste";
$user = "cybe3195_teste";
$pass = "@cybercoari";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    exit;
}

// Função para enviar mensagem no Telegram
function sendTelegramMessage($chat_id, $text) {
    $api_url = "https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/sendMessage";
    $data = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML'];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . "?" . http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_exec($ch);
    curl_close($ch);
}

// Função para gerar key
function gerarKeyWebhook($pdo, $chat_id, $dias) {
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

// Processa notificação
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['type']) || $data['type'] != 'payment') {
    http_response_code(200);
    exit;
}

$payment_id = $data['data']['id'];

// Busca pagamento
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/v1/payments/$payment_id");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . MP_ACCESS_TOKEN]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($http_code != 200) {
    http_response_code(200);
    exit;
}

$payment = json_decode($response, true);

if ($payment['status'] != 'approved') {
    http_response_code(200);
    exit;
}

$pedido_id = $payment['external_reference'];
$chat_id = $payment['metadata']['chat_id'] ?? null;

if (!$pedido_id || !$chat_id) {
    http_response_code(200);
    exit;
}

// Busca pedido no banco
$stmt = $pdo->prepare("SELECT * FROM pedidos_telegram WHERE id = ? AND status = 'pending'");
$stmt->execute([$pedido_id]);
$pedido = $stmt->fetch();

if (!$pedido) {
    http_response_code(200);
    exit;
}

// Gera key
$key = gerarKeyWebhook($pdo, $chat_id, $pedido['plano_dias']);

// Atualiza pedido
$stmt = $pdo->prepare("UPDATE pedidos_telegram SET status = 'paid', key_gerada = ? WHERE id = ?");
$stmt->execute([$key, $pedido_id]);

// Envia mensagem
$msg = "✅ *PAGAMENTO CONFIRMADO!*\n\n";
$msg .= "🔑 *SUA KEY:*\n<code>{$key}</code>\n\n";
$msg .= "📅 *Validade:* {$pedido['plano_dias']} dias\n";
$msg .= "💾 Guarde sua key em um local seguro!\n\n";
$msg .= "⚡ Ative sua key em: cybercoari.com.br/licencas/validar.php";

sendTelegramMessage($chat_id, $msg);

http_response_code(200);
echo "OK";
?>