<?php
// ==============================================
// BOT TELEGRAM - VENDA DE KEYS COM MERCADO PAGO
// ==============================================

// CONFIGURAÇÕES SEGURAS
require_once __DIR__ . '/../config.php'; // Arquivo com tokens e BD
$pdo = require __DIR__ . '/../database.php'; // Conexão PDO

$token = TELEGRAM_TOKEN; // Definido no config.php
$api_url = "https://api.telegram.org/bot$token/";

// SDK Mercado Pago
require_once __DIR__ . '/../vendor/autoload.php';
MercadoPago\SDK::setAccessToken(MP_ACCESS_TOKEN);

function atualizarPedido($pedido_id, $status, $key = null) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE pedidos_telegram SET status = ?, key_gerada = ? WHERE id = ?");
    return $stmt->execute([$status, $key, $pedido_id]);
}

function enviarKey($chat_id, $key, $dias) {
    $text = "✅ **PAGAMENTO CONFIRMADO!**\n\n";
    $text .= "🔑 **SUA KEY:** <code>$key</code>\n";
    $text .= "📅 Validade: $dias dias\n";
    $text .= "⚡ Use no app: cybercoari.com.br/ativar";
    sendMessage($chat_id, $text);
}

// Webhook do Mercado Pago processa pagamento e chama essa função
function confirmarPagamento($payment_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM pedidos_telegram WHERE payment_id = ?");
    $stmt->execute([$payment_id]);
    $pedido = $stmt->fetch();
    
    if ($pedido && $pedido['status'] == 'pending') {
        $key = gerarKey($pedido['plano'], $pedido['dias']);
        atualizarPedido($pedido['id'], 'paid', $key);
        enviarKey($pedido['chat_id'], $key, $pedido['dias']);
    }
}