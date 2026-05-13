<?php
// ==============================================
// BOT TELEGRAM CYBERCOARI - COMPLETO
// ==============================================

// ==============================================
// CONFIGURAÇÕES
// ==============================================

// Telegram
define('TELEGRAM_TOKEN', '7838148953:AAE9S3mZDB-kD6XrF4c2n5oEzjZxuXlU7wE');

// Mercado Pago
// define('MP_ACCESS_TOKEN', 'APP_USR-6051070112657004-041817-58f9464f55dc507b51a24891d5c1cbb9-2069289633');

// URLs
define('BASE_URL', 'https://cybercoari.com.br');
define('WEBHOOK_MP_URL', BASE_URL . '/api/webhook.php');

// ==============================================
// BANCO DE DADOS
// ==============================================

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
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("❌ Erro no banco de dados. Contate o suporte.");
}

// ==============================================
// PLANOS
// ==============================================

$planos = [
    '1' => ['nome' => '📱 Básico',      'dias' => 30,  'preco' => 0.01],
    '2' => ['nome' => '🚀 Profissional','dias' => 90,  'preco' => 0.02],
    '3' => ['nome' => '💎 Master',      'dias' => 180, 'preco' => 0.03],
    '4' => ['nome' => '👑 Vitalício',   'dias' => 3650,'preco' => 0.05]
];

// ==============================================
// FUNÇÕES DO BOT
// ==============================================

function sendMessage($chat_id, $text, $reply_markup = null) {
    $api_url = "https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/";
    $data = ['chat_id' => $chat_id, 'text' => $text, 'parse_mode' => 'HTML'];
    if ($reply_markup) {
        $data['reply_markup'] = json_encode($reply_markup);
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . "sendMessage?" . http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    
    return $result;
}

function sendPhoto($chat_id, $base64_image, $caption = '') {
    $api_url = "https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/";
    $image_data = base64_decode($base64_image);
    $temp_file = tempnam(sys_get_temp_dir(), 'qr_');
    file_put_contents($temp_file, $image_data);
    
    $post_fields = [
        'chat_id' => $chat_id,
        'photo' => new CURLFile($temp_file, 'image/png', 'qrcode.png'),
        'caption' => $caption
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . "sendPhoto");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
    
    unlink($temp_file);
    return $result;
}

function criarPix($email, $valor, $referencia, $chat_id) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Email inválido");
    }
    
    $payload = [
        'transaction_amount' => (float)$valor,
        'description' => "CyberCoari - Pedido #{$referencia}",
        'payment_method_id' => 'pix',
        'payer' => [
            'email' => $email,
            'first_name' => 'Cliente',
            'identification' => [
                'type' => 'CPF',
                'number' => '00000000000'
            ]
        ],
        'external_reference' => (string)$referencia,
        'notification_url' => WEBHOOK_MP_URL,
        'metadata' => ['chat_id' => $chat_id]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.mercadopago.com/v1/payments');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        'Content-Type: application/json',
        'X-Idempotency-Key: ' . uniqid($referencia, true)
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Log para debug
    file_put_contents(__DIR__ . '/pix_log.txt', date('Y-m-d H:i:s') . " - HTTP $http_code - " . substr($response, 0, 500) . "\n", FILE_APPEND);
    
    if ($http_code == 201) {
        $payment = json_decode($response, true);
        return [
            'id' => $payment['id'],
            'qr_code' => $payment['point_of_interaction']['transaction_data']['qr_code'],
            'qr_code_base64' => $payment['point_of_interaction']['transaction_data']['qr_code_base64']
        ];
    } else {
        $error = json_decode($response, true);
        $msg = $error['message'] ?? 'Erro desconhecido';
        throw new Exception("Erro MP: " . $msg);
    }
}

function gerarKey($chat_id, $dias) {
    global $pdo;
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

function verificarKey($key) {
    global $pdo;
    
    $stmt = $pdo->prepare("SELECT * FROM keys_telegram WHERE key_code = ? AND ativo = 1");
    $stmt->execute([$key]);
    $key_data = $stmt->fetch();
    
    if (!$key_data) {
        return ['valida' => false, 'msg' => '❌ KEY inválida.'];
    }
    
    if (time() > $key_data['expira_em']) {
        return ['valida' => false, 'msg' => '❌ KEY expirada.'];
    }
    
    $dias_restantes = ceil(($key_data['expira_em'] - time()) / 86400);
    return [
        'valida' => true,
        'msg' => "✅ KEY VÁLIDA!\n\n🔑 <code>{$key}</code>\n📅 {$dias_restantes} dias restantes"
    ];
}

// ==============================================
// PROCESSAR MENSAGENS DO TELEGRAM
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
        'inline_keyboard' => [
            [['text' => "📱 Básico - R$ 0,01", 'callback_data' => "plano_1"]],
            [['text' => "🚀 Profissional - R$ 0,02", 'callback_data' => "plano_2"]],
            [['text' => "💎 Master - R$ 0,03", 'callback_data' => "plano_3"]],
            [['text' => "👑 Vitalício - R$ 0,05", 'callback_data' => "plano_4"]],
            [['text' => "🔍 VERIFICAR KEY", 'callback_data' => "verificar"]],
            [['text' => "❓ AJUDA", 'callback_data' => "ajuda"]]
        ]
    ];
    
    $welcome = "🤖 *Bem-vindo à CyberCoari!*\n\n";
    $welcome .= "Escolha um dos planos abaixo para adquirir sua key de acesso:\n\n";
    $welcome .= "💰 *Planos disponíveis:*\n";
    $welcome .= "• Básico: R$ 0,01 (30 dias)\n";
    $welcome .= "• Profissional: R$ 0,02 (90 dias)\n";
    $welcome .= "• Master: R$ 0,03 (180 dias)\n";
    $welcome .= "• Vitalício: R$ 0,05 (10 anos)\n\n";
    $welcome .= "⚡ Após o pagamento, sua key chega automaticamente!";
    
    sendMessage($chat_id, $welcome, $keyboard);
    exit;
}

// CALLBACK QUERY (botões inline)
if ($callback) {
    $data = $callback['data'];
    $chat_id = $callback['from']['id'];
    $callback_id = $callback['id'];
    
    // Responde o callback (remove loading)
    $api_url = "https://api.telegram.org/bot" . TELEGRAM_TOKEN . "/answerCallbackQuery";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url . "?callback_query_id={$callback_id}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
    
    // Plano escolhido
    if (strpos($data, 'plano_') === 0) {
        $plano_id = substr($data, 6);
        $plano = $planos[$plano_id];
        
        // Salva estado aguardando email
        file_put_contents("/tmp/bot_{$chat_id}.json", json_encode([
            'step' => 'awaiting_email',
            'plano' => $plano
        ]));
        
        sendMessage($chat_id, "📧 *Qual seu email?*\n\nEnvie o email que será usado para gerar o PIX:");
        exit;
    }
    
    // Verificar key
    if ($data == 'verificar') {
        sendMessage($chat_id, "🔍 *Verificar KEY*\n\nEnvie a key que você quer verificar:\n\n<code>Exemplo: CYBER-ABCD-1234-EFGH</code>");
        exit;
    }
    
    // Ajuda
    if ($data == 'ajuda') {
        $help = "❓ *Ajuda - CyberCoari*\n\n";
        $help .= "/start - Voltar ao menu principal\n";
        $help .= "🔍 Verificar KEY - Validar se sua key está ativa\n\n";
        $help .= "📞 *Suporte:* @cybercoari_suporte\n";
        $help .= "🌐 *Site:* cybercoari.com.br";
        sendMessage($chat_id, $help);
        exit;
    }
}

// MENSAGENS NORMAIS
if ($message) {
    $text = trim($message['text'] ?? '');
    $state_file = "/tmp/bot_{$chat_id}.json";
    
    // Verifica se está aguardando email
    if (file_exists($state_file)) {
        $state = json_decode(file_get_contents($state_file), true);
        
        if ($state['step'] == 'awaiting_email') {
            $email = $text;
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                sendMessage($chat_id, "❌ *Email inválido!*\n\nDigite um email válido (exemplo@email.com):");
                exit;
            }
            
            $plano = $state['plano'];
            $pedido_id = time() . rand(100, 999);
            
            // Salva pedido no banco
            $stmt = $pdo->prepare("INSERT INTO pedidos_telegram (id, chat_id, plano_nome, plano_dias, valor, email_cliente, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$pedido_id, $chat_id, $plano['nome'], $plano['dias'], $plano['preco'], $email]);
            
            sendMessage($chat_id, "⏳ *Gerando PIX...*\n\nAguarde um momento.");
            
            try {
                $pix = criarPix($email, $plano['preco'], $pedido_id, $chat_id);
                
                // Atualiza payment_id
                $stmt = $pdo->prepare("UPDATE pedidos_telegram SET payment_id = ? WHERE id = ?");
                $stmt->execute([$pix['id'], $pedido_id]);
                
                // Mensagem com QR Code
                $msg = "🎯 *PEDIDO #{$pedido_id}*\n\n";
                $msg .= "📦 *Plano:* {$plano['nome']}\n";
                $msg .= "💰 *Valor:* R$ " . number_format($plano['preco'], 2) . "\n";
                $msg .= "📅 *Duração:* {$plano['dias']} dias\n";
                $msg .= "📧 *Email:* {$email}\n\n";
                $msg .= "💳 *PIX para pagamento:*\n";
                $msg .= "<code>{$pix['qr_code']}</code>\n\n";
                $msg .= "⏳ *Após o pagamento, sua key será enviada automaticamente!*\n";
                $msg .= "✅ O PIX pode levar até 2 minutos para ser confirmado.";
                
                sendMessage($chat_id, $msg);
                sendPhoto($chat_id, $pix['qr_code_base64'], "📱 Escaneie o QR Code abaixo para pagar:");
                
                // Limpa estado
                unlink($state_file);
                
            } catch (Exception $e) {
                sendMessage($chat_id, "❌ *Erro ao gerar pagamento!*\n\n" . $e->getMessage() . "\n\nTente novamente com /start");
                error_log("Erro PIX: " . $e->getMessage());
            }
            exit;
        }
    }
    
    // Verificar KEY
    if (preg_match('/^[A-Z]+-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $text)) {
        $resultado = verificarKey($text);
        sendMessage($chat_id, $resultado['msg']);
        exit;
    }
    
    // Comando desconhecido
    if ($text != '/start') {
        sendMessage($chat_id, "❓ *Comando não reconhecido*\n\nUse /start para ver os planos disponíveis.");
    }
}
?>