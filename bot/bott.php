<?php

$token = "seu_token";

// RECEBE DADOS
$update = json_decode(file_get_contents("php://input"), true);

// DEBUG (pode remover depois)
file_put_contents("log.txt", print_r($update, true), FILE_APPEND);

$chat_id = $update["message"]["chat"]["id"] ?? null;
$text = $update["message"]["text"] ?? "";

// FUNÇÃO ENVIAR MSG
function sendMessage($chat_id, $text) {
    global $token;
    file_get_contents("https://api.telegram.org/bot$token/sendMessage?chat_id=$chat_id&text=" . urlencode($text));
}

// MENU PRINCIPAL
if ($text == "/start") {

    $msg = "🔥 *Bem-vindo ao GestãoPRO Bot*\n\n";
    $msg .= "Escolha uma opção:\n\n";
    $msg .= "1️⃣ Ver Planos\n";
    $msg .= "2️⃣ Suporte\n";

    file_get_contents("https://api.telegram.org/bot$token/sendMessage?" . http_build_query([
        'chat_id' => $chat_id,
        'text' => $msg,
        'parse_mode' => 'Markdown'
    ]));
}

// VER PLANOS
elseif ($text == "1" || strtolower($text) == "planos") {

    $msg = "💳 *PLANOS DISPONÍVEIS*\n\n";
    $msg .= "🔹 Básico - R$ 19\n";
    $msg .= "🔹 Pro - R$ 39\n";
    $msg .= "🔹 Premium - R$ 59\n\n";
    $msg .= "Digite:\n";
    $msg .= "/comprar_basico\n";
    $msg .= "/comprar_pro\n";
    $msg .= "/comprar_premium";

    sendMessage($chat_id, $msg);
}

// COMPRA BASICO
elseif ($text == "/comprar_basico") {

    $id = rand(1000,9999);

    $msg = "💰 Pagamento via PIX\n\n";
    $msg .= "Plano: Básico\n";
    $msg .= "Valor: R$ 19\n";
    $msg .= "ID: $id\n\n";
    $msg .= "Após pagar envie:\n";
    $msg .= "/pago_$id";

    sendMessage($chat_id, $msg);
}

// CONFIRMA PAGAMENTO
elseif (strpos($text, "/pago_") === 0) {

    $id = str_replace("/pago_", "", $text);

    // gera licença fake
    $licenca = strtoupper(substr(md5($chat_id . time()), 0, 10));

    $msg = "✅ Pagamento confirmado!\n\n";
    $msg .= "🔐 Sua licença:\n";
    $msg .= "$licenca\n\n";
    $msg .= "Obrigado pela compra! 🚀";

    sendMessage($chat_id, $msg);
}

// SUPORTE
elseif ($text == "2" || strtolower($text) == "suporte") {

    $msg = "📞 Suporte\n\n";
    $msg .= "Entre em contato:\n";
    $msg .= "WhatsApp: (seu número)\n";

    sendMessage($chat_id, $msg);
}