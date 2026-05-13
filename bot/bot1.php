<?php
// ==============================================
// BOT TELEGRAM - VENDA DE KEYS COM MERCADO PAGO
// ==============================================

$token = "seu_token";
$api_url = "https://api.telegram.org/bot$token/";

// SDK Mercado Pago
require_once __DIR__ . '/../vendor/autoload.php';
MercadoPago\SDK::setAccessToken("SEU_ACCESS_TOKEN_MP"); // ⚠️ Coloque seu token de produção

$planos = [
    '1' => ['nome' => '📱 Básico',      'dias' => 30,  'preco' => 29.90],
    '2' => ['nome' => '🚀 Profissional','dias' => 90,  'preco' => 69.90],
    '3' => ['nome' => '💎 Master',      'dias' => 180, 'preco' => 99.90],
    '4' => ['nome' => '👑 Vitalício',   'dias' => 3650,'preco' => 199.90]
];

$pedidos_dir = __DIR__ . "/pedidos";
if (!file_exists($pedidos_dir)) mkdir($pedidos_dir, 0777, true);

function sendMessage($chat_id, $text, $reply_markup = null) {
    global $api_url;
    $data = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML'];
    if ($reply_markup) $data['reply_markup'] = $reply_markup;
    file_get_contents($api_url . "sendMessage?" . http_build_query($data));
}

function gerarKey($prefixo, $dias) {
    $key = $prefixo . '-' . substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4) . '-' .
           substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4) . '-' .
           substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
    $dir = __DIR__ . "/../keys/{$key}";
    if (!file_exists($dir)) mkdir($dir, 0777, true);
    $expira = time() + ($dias * 86400);
    $content = "KEY: {$key}\nVALIDADE: {$dias} dias\nEXPIRE: {$expira}\nCRIADO: " . date('Y-m-d H:i:s');
    file_put_contents("{$dir}/list", $content);
    return $key;
}

function criarPix($email, $valor, $referencia) {
    $payment = new MercadoPago\Payment();
    $payment->transaction_amount = (float)$valor;
    $payment->description = "Key CyberCoari - $referencia";
    $payment->payment_method_id = "pix";
    $payment->payer = ['email' => $email];
    $payment->external_reference = $referencia;
    $payment->notification_url = "https://cybercoari.com.br/api/webhook.php";
    $payment->save();
    if ($payment->error) throw new Exception(json_encode($payment->error));
    return [
        'id' => $payment->id,
        'qr_code' => $payment->point_of_interaction->transaction_data->qr_code,
        'qr_code_base64' => $payment->point_of_interaction->transaction_data->qr_code_base64
    ];
}

$update = json_decode(file_get_contents('php://input'), true);
if (!$update) exit;

$message = $update['message'] ?? null;
$callback = $update['callback_query'] ?? null;
$chat_id = $message['chat']['id'] ?? $callback['from']['id'] ?? null;
if (!$chat_id) exit;

if ($message && $message['text'] == '/start') {
    $keyboard = ['inline_keyboard' => []];
    foreach ($planos as $id => $p) {
        $keyboard['inline_keyboard'][] = [['text' => "{$p['nome']} - R$ {$p['preco']}", 'callback_data' => "plano_$id"]];
    }
    $keyboard['inline_keyboard'][] = [['text' => "🔍 VERIFICAR KEY", 'callback_data' => "verificar"]];
    sendMessage($chat_id, "🤖 Escolha um plano:", json_encode($keyboard));
    exit;
}

if ($callback) {
    $data = $callback['data'];
    $chat_id = $callback['from']['id'];

    if (strpos($data, 'plano_') === 0) {
        $plano_id = substr($data, 6);
        $plano = $planos[$plano_id];
        $pedido_id = time() . rand(100, 999);
        $email = "cliente_$chat_id@telegram.com";

        // Salva pedido aguardando pagamento
        $pedido = [
            'id' => $pedido_id,
            'chat_id' => $chat_id,
            'plano' => $plano,
            'status' => 'pending',
            'data' => date('Y-m-d H:i:s')
        ];
        file_put_contents("$pedidos_dir/$pedido_id.json", json_encode($pedido));

        try {
            $cobranca = criarPix($email, $plano['preco'], (string)$pedido_id);
            $pedido['payment_id'] = $cobranca['id'];
            file_put_contents("$pedidos_dir/$pedido_id.json", json_encode($pedido));

            // Envia QR Code e código copiável
            $text = "🎯 *PEDIDO #$pedido_id*\n";
            $text .= "📦 {$plano['nome']}\n💰 R$ {$plano['preco']}\n📅 {$plano['dias']} dias\n\n";
            $text .= "💳 *Pagamento PIX*\n";
            $text .= "🔗 *Código copiável:*\n<code>{$cobranca['qr_code']}</code>\n\n";
            $text .= "⏳ Após o pagamento, a KEY será enviada automaticamente para você.";
            sendMessage($chat_id, $text);
            // Envia QR Code imagem
            file_get_contents($api_url . "sendPhoto?chat_id=$chat_id&photo=" . urlencode($cobranca['qr_code_base64']) . "&caption=QR Code PIX");
        } catch (Exception $e) {
            sendMessage($chat_id, "❌ Erro ao gerar pagamento. Tente novamente.");
        }
        exit;
    }

    if ($data == 'verificar') {
        sendMessage($chat_id, "🔑 Envie a KEY para verificar:");
        exit;
    }
}

// Verificação de KEY enviada diretamente
if ($message && preg_match('/^[A-Z]+-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $message['text'])) {
    $key = $message['text'];
    $file = __DIR__ . "/../keys/$key/list";
    if (file_exists($file)) {
        sendMessage($chat_id, "✅ KEY válida! <code>$key</code>");
    } else {
        sendMessage($chat_id, "❌ KEY inválida.");
    }
    exit;
}