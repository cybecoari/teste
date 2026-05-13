<?php
// ==============================================
// BOT TELEGRAM CORRIGIDO - CYBERCOARI
// ==============================================

require_once __DIR__ . '/config_seguro.php';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

// Configura Mercado Pago
MercadoPago\SDK::setAccessToken(MP_ACCESS_TOKEN);

$api_url = "https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/";

// Planos disponíveis
$planos = [
    '1' => ['nome' => '📱 Básico',      'dias' => 30,  'preco' => 29.90],
    '2' => ['nome' => '🚀 Profissional','dias' => 90,  'preco' => 69.90],
    '3' => ['nome' => '💎 Master',      'dias' => 180, 'preco' => 99.90],
    '4' => ['nome' => '👑 Vitalício',   'dias' => 3650,'preco' => 199.90]
];

// ==============================================
// FUNÇÕES AUXILIARES
// ==============================================

function sendMessage($chat_id, $text, $reply_markup = null) {
    global $api_url;
    $data = [
        'chat_id' => $chat_id, 
        'text' => $text, 
        'parse_mode' => 'HTML'
    ];
    if ($reply_markup) {
        $data['reply_markup'] = json_encode($reply_markup);
    }
    
    $url = $api_url . "sendMessage?" . http_build_query($data);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

function enviarFoto($chat_id, $base64_image, $caption = '') {
    global $api_url;
    
    // Decodifica base64 para imagem
    $image_data = base64_decode($base64_image);
    $temp_file = tempnam(sys_get_temp_dir(), 'qr_');
    file_put_contents($temp_file, $image_data);
    
    $post_fields = [
        'chat_id' => $chat_id,
        'photo' => new CURLFile($temp_file),
        'caption' => $caption
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . "sendPhoto");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    unlink($temp_file);
    return $response;
}

function gerarKey($chat_id, $dias) {
    global $pdo;
    
    // Gera key única
    $prefixos = ['CYBER', 'COARI', 'PREMIUM', 'MASTER'];
    $prefixo = $prefixos[array_rand($prefixos)];
    
    do {
        $key = $prefixo . '-' . 
               substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4) . '-' .
               substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4) . '-' .
               substr(str_shuffle('ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 4);
        
        // Verifica se key já existe
        $stmt = $pdo->prepare("SELECT key_code FROM keys_telegram WHERE key_code = ?");
        $stmt->execute([$key]);
        $exists = $stmt->fetch();
    } while ($exists);
    
    $expira = time() + ($dias * 86400);
    
    // Salva no banco
    $stmt = $pdo->prepare("INSERT INTO keys_telegram (key_code, chat_id, plano_dias, expira_em) VALUES (?, ?, ?, ?)");
    $stmt->execute([$key, $chat_id, $dias, $expira]);
    
    // Cria pasta por compatibilidade (opcional)
    $dir = __DIR__ . "/../keys/{$key}";
    if (!file_exists($dir)) {
        mkdir($dir, 0777, true);
        $content = "KEY: {$key}\nVALIDADE: {$dias} dias\nEXPIRE: {$expira}\nCRIADO: " . date('Y-m-d H:i:s');
        file_put_contents("{$dir}/list", $content);
    }
    
    return $key;
}

function criarPix($email, $valor, $referencia, $chat_id) {
    // Valida email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email inválido");
    }
    
    $payment = new MercadoPago\Payment();
    $payment->transaction_amount = (float)$valor;
    $payment->description = "Key CyberCoari - Pedido #{$referencia}";
    $payment->payment_method_id = "pix";
    $payment->payer = [
        'email' => $email,
        'first_name' => "Cliente Telegram",
        'identification' => [
            'type' => 'CPF',
            'number' => '00000000000' // Opcional, mas pode pedir ao usuário
        ]
    ];
    $payment->external_reference = $referencia;
    $payment->notification_url = WEBHOOK_URL;
    $payment->metadata = ['chat_id' => $chat_id];
    
    $payment->save();
    
    if ($payment->error) {
        error_log("Erro MP: " . json_encode($payment->error));
        throw new Exception($payment->error->message ?? "Erro ao gerar PIX");
    }
    
    return [
        'id' => $payment->id,
        'qr_code' => $payment->point_of_interaction->transaction_data->qr_code,
        'qr_code_base64' => $payment->point_of_interaction->transaction_data->qr_code_base64
    ];
}

function verificarKey($key) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM keys_telegram WHERE key_code = ? AND ativo = TRUE");
    $stmt->execute([$key]);
    $key_data = $stmt->fetch();
    
    if (!$key_data) {
        return ['valida' => false, 'msg' => '❌ KEY inválida ou já utilizada.'];
    }
    
    if (time() > $key_data['expira_em']) {
        return ['valida' => false, 'msg' => '❌ KEY expirada.'];
    }
    
    $dias_restantes = ceil(($key_data['expira_em'] - time()) / 86400);
    return [
        'valida' => true, 
        'msg' => "✅ KEY válida!\n📅 Validade: {$dias_restantes} dias restantes\n🔑 <code>{$key}</code>"
    ];
}

// ==============================================
// PROCESSAMENTO DO WEBHOOK DO TELEGRAM
// ==============================================

$update = json_decode(file_get_contents('php://input'), true);
if (!$update) exit;

$message = $update['message'] ?? null;
$callback = $update['callback_query'] ?? null;
$chat_id = $message['chat']['id'] ?? $callback['from']['id'] ?? null;

if (!$chat_id) exit;

// COMANDO /start
if ($message && $message['text'] == '/start') {
    $keyboard = [
        'inline_keyboard' => []
    ];
    
    foreach ($planos as $id => $p) {
        $keyboard['inline_keyboard'][] = [
            ['text' => "{$p['nome']} - R$ {$p['preco']}", 'callback_data' => "plano_{$id}"]
        ];
    }
    
    $keyboard['inline_keyboard'][] = [
        ['text' => "🔍 VERIFICAR KEY", 'callback_data' => "verificar"],
        ['text' => "❓ Ajuda", 'callback_data' => "ajuda"]
    ];
    
    sendMessage($chat_id, "🤖 *Bem-vindo à CyberCoari!*\n\nEscolha um plano abaixo para adquirir sua key:", $keyboard);
    exit;
}

// CALLBACK QUERY (botões inline)
if ($callback) {
    $data = $callback['data'];
    $chat_id = $callback['from']['id'];
    $callback_id = $callback['id'];
    
    // Responde o callback para o Telegram (remove o loading)
    file_get_contents($api_url . "answerCallbackQuery?callback_query_id={$callback_id}");
    
    if (strpos($data, 'plano_') === 0) {
        $plano_id = substr($data, 6);
        $plano = $planos[$plano_id];
        
        // Pergunta o email do cliente
        sendMessage($chat_id, "📧 Por favor, envie seu *email* para gerarmos o PIX:\n\nExemplo: cliente@email.com");
        
        // Salva temporariamente o plano escolhido na sessão (poderia usar banco)
        // Exemplo simples: armazenar em arquivo temporário
        $temp_data = [
            'plano' => $plano,
            'chat_id' => $chat_id,
            'step' => 'waiting_email'
        ];
        file_put_contents("/tmp/telegram_{$chat_id}.json", json_encode($temp_data));
        
        exit;
    }
    
    if ($data == 'verificar') {
        sendMessage($chat_id, "🔑 Envie a KEY que deseja verificar:\n\nFormato: <code>CYBER-ABCD-1234-EFGH</code>");
        exit;
    }
    
    if ($data == 'ajuda') {
        $help = "📖 *Ajuda - CyberCoari*\n\n";
        $help .= "/start - Voltar ao menu principal\n";
        $help .= "🔍 Verificar KEY - Valide se sua key está ativa\n\n";
        $help .= "*Dúvidas?*\nEntre em contato: @cybercoari_suporte";
        sendMessage($chat_id, $help);
        exit;
    }
}

// PROCESSAMENTO DE MENSAGENS
if ($message) {
    $text = $message['text'] ?? '';
    
    // Verifica se é email (fluxo de compra)
    $temp_file = "/tmp/telegram_{$chat_id}.json";
    if (file_exists($temp_file)) {
        $temp_data = json_decode(file_get_contents($temp_file), true);
        if (isset($temp_data['step']) && $temp_data['step'] == 'waiting_email') {
            $email = trim($text);
            
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $plano = $temp_data['plano'];
                $pedido_id = time() . rand(100, 999);
                
                // Salva pedido no banco
                global $pdo;
                $stmt = $pdo->prepare("INSERT INTO pedidos_telegram (id, chat_id, plano_nome, plano_dias, valor, email_cliente, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$pedido_id, $chat_id, $plano['nome'], $plano['dias'], $plano['preco'], $email]);
                
                try {
                    $cobranca = criarPix($email, $plano['preco'], $pedido_id, $chat_id);
                    
                    // Atualiza payment_id no banco
                    $stmt = $pdo->prepare("UPDATE pedidos_telegram SET payment_id = ? WHERE id = ?");
                    $stmt->execute([$cobranca['id'], $pedido_id]);
                    
                    // Envia informações do PIX
                    $text = "🎯 *PEDIDO #{$pedido_id}*\n";
                    $text .= "📦 {$plano['nome']}\n";
                    $text .= "💰 R$ {$plano['preco']}\n";
                    $text .= "📅 {$plano['dias']} dias\n\n";
                    $text .= "💳 *Pagamento PIX*\n";
                    $text .= "📧 Email: {$email}\n\n";
                    $text .= "🔗 *Código copiável:*\n";
                    $text .= "<code>{$cobranca['qr_code']}</code>\n\n";
                    $text .= "⏳ Após o pagamento, a KEY será enviada automaticamente.\n";
                    $text .= "✅ O pagamento pode levar até 2 minutos para ser confirmado.";
                    
                    sendMessage($chat_id, $text);
                    
                    // Envia QR Code como imagem
                    enviarFoto($chat_id, $cobranca['qr_code_base64'], "📱 Escaneie o QR Code abaixo para pagar:");
                    
                    // Limpa dados temporários
                    unlink($temp_file);
                    
                } catch (Exception $e) {
                    error_log("Erro ao gerar PIX: " . $e->getMessage());
                    sendMessage($chat_id, "❌ Erro ao gerar pagamento: " . $e->getMessage() . "\n\nTente novamente com /start");
                    unlink($temp_file);
                }
            } else {
                sendMessage($chat_id, "❌ Email inválido! Digite um email válido (exemplo@email.com)");
            }
            exit;
        }
    }
    
    // Verifica se é uma KEY
    if (preg_match('/^[A-Z]+-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $text)) {
        $resultado = verificarKey($text);
        sendMessage($chat_id, $resultado['msg']);
        exit;
    }
    
    // Comando desconhecido
    sendMessage($chat_id, "❓ Comando não reconhecido. Use /start para ver as opções disponíveis.");
    exit;
}