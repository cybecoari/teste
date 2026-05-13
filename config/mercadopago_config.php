<?php
// config/mercadopago_config.php

require_once __DIR__ . '/../vendor/autoload.php';

use MercadoPago\MercadoPagoConfig;
use MercadoPago\Client\Preference\PreferenceClient;
use MercadoPago\Client\Payment\PaymentClient;

define('MP_ACCESS_TOKEN', 'APP_USR-6051070112657004-041817-58f9464f55dc507b51a24891d5c1cbb9-2069289633');
define('MP_PUBLIC_KEY', 'APP_USR-03fbca80-6c61-46d0-839d-343c6b725381');
define('MP_WEBHOOK_URL', 'https://cybercoari.com.br/api/webhook.php');

MercadoPagoConfig::setAccessToken(MP_ACCESS_TOKEN);

function criarPreferencia($items, $external_reference) {
    $client = new PreferenceClient();
    
    $preference = $client->create([
        'items' => $items,
        'external_reference' => (string)$external_reference,
        'back_urls' => [
            'success' => 'https://cybercoari.com.br/pagamento/sucesso.php',
            'failure' => 'https://cybercoari.com.br/pagamento/falha.php',
            'pending' => 'https://cybercoari.com.br/pagamento/pendente.php'
        ],
        'auto_return' => 'approved',
        'notification_url' => MP_WEBHOOK_URL,
        'statement_descriptor' => 'Cyber Coari',
        'binary_mode' => false,
        'payment_methods' => [
            'installments' => 12,
            'default_installments' => 1
        ]
    ]);
    
    return $preference;
}

function consultarPagamento($payment_id) {
    $client = new PaymentClient();
    return $client->get($payment_id);
}

function gerarChaveLicenca() {
    $prefixo = "CBC";
    $partes = [];
    for ($i = 0; $i < 4; $i++) {
        $partes[] = strtoupper(bin2hex(random_bytes(4)));
    }
    return $prefixo . "-" . implode("-", $partes);
}
?>