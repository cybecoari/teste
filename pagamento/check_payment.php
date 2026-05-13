<?php
// pagamento/check_payment.php

require_once __DIR__ . '/../config/config.php';

$response = ['status' => 'pending'];

if (isset($_SESSION['external_reference'])) {
    $arquivo_pagamento = __DIR__ . "/pagamentos/" . $_SESSION['external_reference'] . ".json";
    
    if (file_exists($arquivo_pagamento)) {
        $pagamento = json_decode(file_get_contents($arquivo_pagamento), true);
        if ($pagamento['status'] == 'approved') {
            $_SESSION['download_token'] = bin2hex(random_bytes(32));
            $_SESSION['download_arquivo'] = $_SESSION['produto_arquivo'];
            $response['status'] = 'approved';
        }
    }
}

header('Content-Type: application/json');
echo json_encode($response);
?>