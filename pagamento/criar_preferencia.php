<?php
// pagamento/criar_preferencia.php - API para criar preferência

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/mercadopago_config.php';

header('Content-Type: application/json');

$produtos = [
    1 => ['nome' => 'Software CyberCoari Pro', 'preco' => 0.01],
    2 => ['nome' => 'Template Game Launcher', 'preco' => 49.90],
    3 => ['nome' => 'App Mobile', 'preco' => 99.90],
    4 => ['nome' => 'Sistema ERP', 'preco' => 199.90],
];

$produto_id = $_POST['produto_id'] ?? $_GET['produto_id'] ?? 0;

if (!$produto_id || !isset($produtos[$produto_id])) {
    echo json_encode(['error' => 'Produto não encontrado']);
    exit;
}

$produto = $produtos[$produto_id];
$external_reference = 'PREF_' . time() . '_' . rand(100, 999);

// Criar preferência no Mercado Pago
$data = [
    'items' => [
        [
            'id' => $produto_id,
            'title' => $produto['nome'],
            'quantity' => 1,
            'currency_id' => 'BRL',
            'unit_price' => (float)$produto['preco']
        ]
    ],
    'external_reference' => $external_reference,
    'notification_url' => 'https://cybercoari.com.br/api/webhook.php',
    'back_urls' => [
        'success' => 'https://cybercoari.com.br/pagamento/sucesso.php',
        'failure' => 'https://cybercoari.com.br/pagamento/falha.php',
        'pending' => 'https://cybercoari.com.br/pagamento/pendente.php'
    ],
    'auto_return' => 'approved'
];

$ch = curl_init("https://api.mercadopago.com/checkout/preferences");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . MP_ACCESS_TOKEN,
        'Content-Type: application/json'
    ],
    CURLOPT_SSL_VERIFYPEER => false
]);

$response = curl_exec($ch);
curl_close($ch);

$result = json_decode($response, true);

if (isset($result['init_point'])) {
    echo json_encode(['url' => $result['init_point'], 'external_reference' => $external_reference]);
} else {
    echo json_encode(['error' => 'Erro ao criar preferência', 'details' => $result]);
}
?>