<?php
// pagamento/checkout.php - Checkout com QR Code

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/mercadopago_config.php';

$produtos = [
    1 => ['nome' => '📦 Software CyberCoari Pro', 'preco' => 0.01, 'arquivo' => 'cybercoari_pro.zip'],
    2 => ['nome' => '🎮 Template Game Launcher', 'preco' => 49.90, 'arquivo' => 'game_launcher.zip'],
    3 => ['nome' => '📱 App Mobile', 'preco' => 99.90, 'arquivo' => 'mobile_app.zip'],
    4 => ['nome' => '💼 Sistema ERP', 'preco' => 199.90, 'arquivo' => 'erp_system.zip'],
];

$produto_id = isset($_GET['produto_id']) ? (int)$_GET['produto_id'] : ($_SESSION['produto_id'] ?? 0);

if (!$produto_id || !isset($produtos[$produto_id])) {
    header("Location: index.php");
    exit;
}

$produto = $produtos[$produto_id];
$_SESSION['produto_id'] = $produto_id;
$_SESSION['produto_arquivo'] = $produto['arquivo'];

$qr_code_text = null;
$qr_code_base64 = null;
$erro = null;

// Gerar PIX
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'] ?? '';
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Digite um e-mail válido.";
    } else {
        $external_reference = 'DOWN_' . time() . '_' . rand(100, 999);
        $idempotency_key = uniqid() . '_' . time() . '_' . rand(1000, 9999);
        
        $data = [
            'transaction_amount' => (float)$produto['preco'],
            'description' => $produto['nome'],
            'payment_method_id' => 'pix',
            'payer' => [
                'email' => $email,
                'first_name' => 'Cliente',
                'identification' => ['type' => 'CPF', 'number' => '00000000000']
            ],
            'external_reference' => $external_reference,
            'notification_url' => 'https://cybercoari.com.br/api/webhook.php'
        ];
        
        $ch = curl_init("https://api.mercadopago.com/v1/payments");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . MP_ACCESS_TOKEN,
                'Content-Type: application/json',
                'X-Idempotency-Key: ' . $idempotency_key
            ],
            CURLOPT_SSL_VERIFYPEER => false
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $result = json_decode($response, true);
        curl_close($ch);
        
        if ($http_code == 201 && isset($result['id'])) {
            $qr_code_text = $result['point_of_interaction']['transaction_data']['qr_code'] ?? null;
            $qr_code_base64 = $result['point_of_interaction']['transaction_data']['qr_code_base64'] ?? null;
            
            $_SESSION['payment_id'] = $result['id'];
            $_SESSION['external_reference'] = $external_reference;
            $_SESSION['compra_email'] = $email;
            $_SESSION['pix_qr_text'] = $qr_code_text;
            $_SESSION['pix_qr_base64'] = $qr_code_base64;
        } else {
            $erro = $result['message'] ?? 'Erro ao gerar PIX.';
        }
    }
}

// Verificar pagamento confirmado
$pagamento_confirmado = false;
$pasta_pagamentos = __DIR__ . "/pagamentos/";
if (!file_exists($pasta_pagamentos)) mkdir($pasta_pagamentos, 0755, true);

if (isset($_SESSION['external_reference'])) {
    $arquivo_pagamento = $pasta_pagamentos . $_SESSION['external_reference'] . ".json";
    if (file_exists($arquivo_pagamento)) {
        $pagamento = json_decode(file_get_contents($arquivo_pagamento), true);
        if ($pagamento['status'] == 'approved') {
            $pagamento_confirmado = true;
            $_SESSION['download_token'] = bin2hex(random_bytes(32));
            $_SESSION['download_arquivo'] = $produto['arquivo'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento - CyberCoari Store</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .container { max-width: 500px; width: 100%; }
        .card { background: white; border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.1); }
        h2 { text-align: center; margin-bottom: 20px; color: #333; }
        .product-info { background: #f8f9fa; padding: 15px; border-radius: 10px; text-align: center; margin-bottom: 20px; }
        .product-name { font-size: 18px; font-weight: bold; }
        .product-price { font-size: 24px; color: #667eea; font-weight: bold; margin-top: 5px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }
        button { width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 14px; border-radius: 10px; font-size: 16px; cursor: pointer; }
        .qr-code { text-align: center; margin: 20px 0; }
        .qr-code img { max-width: 250px; border: 1px solid #ddd; border-radius: 10px; padding: 10px; background: white; }
        .pix-copy { background: #667eea; color: white; padding: 12px; border-radius: 8px; cursor: pointer; text-align: center; margin-top: 10px; font-weight: bold; }
        .pix-copy:hover { background: #5a67d8; }
        .erro { background: #f8d7da; color: #721c24; padding: 12px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .sucesso { background: #d4edda; color: #155724; padding: 20px; border-radius: 8px; margin-bottom: 20px; text-align: center; }
        .btn-voltar { display: block; text-align: center; margin-top: 20px; color: #666; text-decoration: none; }
        .btn-download { display: inline-block; background: #28a745; color: white; padding: 12px 24px; border-radius: 10px; text-decoration: none; margin-top: 15px; }
        .info-text { font-size: 12px; color: #666; text-align: center; margin-top: 15px; }
        .loading { display: inline-block; width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid #667eea; border-radius: 50%; animation: spin 1s linear infinite; margin-left: 10px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .text-center { text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <?php if ($pagamento_confirmado): ?>
                <div class="sucesso">
                    <h2>✅ Pagamento Confirmado!</h2>
                    <a href="download.php" class="btn-download">📥 Fazer Download Agora</a>
                </div>
                <a href="index.php" class="btn-voltar">← Voltar para loja</a>
                
            <?php elseif ($qr_code_text): ?>
                <h2>✅ PIX Gerado com Sucesso!</h2>
                
                <div class="qr-code">
                    <?php if ($qr_code_base64): ?>
                        <img src="data:image/png;base64,<?= $qr_code_base64 ?>" alt="QR Code PIX">
                    <?php else: ?>
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=<?= urlencode($qr_code_text) ?>" alt="QR Code PIX">
                    <?php endif; ?>
                    
                    <div class="pix-copy" onclick="copiarPIX()">📋 Clique para copiar o código PIX</div>
                </div>
                
                <div class="info-text" id="statusText">🔄 Aguardando confirmação do pagamento... <span class="loading"></span></div>
                <textarea id="pixCode" style="display:none;"><?= htmlspecialchars($qr_code_text) ?></textarea>
                
                <script>
                    function copiarPIX() {
                        navigator.clipboard.writeText(document.getElementById('pixCode').value);
                        alert('✅ Código PIX copiado!');
                    }
                    let verificacoes = 0;
                    const intervalo = setInterval(() => {
                        verificacoes++;
                        fetch('check_payment.php').then(r => r.json()).then(data => {
                            if (data.status === 'approved') {
                                clearInterval(intervalo);
                                window.location.href = 'index.php?payment_success=1';
                            }
                        }).catch(() => {});
                        if (verificacoes > 60) clearInterval(intervalo);
                    }, 5000);
                </script>
                <a href="index.php" class="btn-voltar">← Voltar para loja</a>
                
            <?php elseif ($erro): ?>
                <div class="erro">❌ <?= h($erro) ?></div>
                <a href="checkout.php?produto_id=<?= $produto_id ?>" class="btn-voltar">← Tentar novamente</a>
                
            <?php else: ?>
                <h2>💰 Pagamento via PIX</h2>
                <div class="product-info">
                    <div class="product-name"><?= h($produto['nome']) ?></div>
                    <div class="product-price">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></div>
                </div>
                <form method="POST">
                    <div class="form-group">
                        <label>📧 Seu e-mail:</label>
                        <input type="email" name="email" placeholder="seu@email.com" required autofocus>
                    </div>
                    <button type="submit">📱 Gerar QR Code PIX</button>
                </form>
                <div class="info-text">🔒 Pagamento seguro via Mercado Pago</div>
                <a href="index.php" class="btn-voltar">← Voltar para produtos</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>