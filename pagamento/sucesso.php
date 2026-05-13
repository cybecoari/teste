<?php
// pagamento/sucesso.php - Página de sucesso

require_once __DIR__ . '/../config/config.php';

$payment_id = $_GET['payment_id'] ?? $_GET['preference_id'] ?? null;
$external_reference = $_GET['external_reference'] ?? null;

$mensagem = "✅ Pagamento realizado com sucesso!";
$status = "approved";

// Se tiver external_reference, buscar no webhook
if ($external_reference) {
    $arquivo_pagamento = __DIR__ . "/pagamentos/" . $external_reference . ".json";
    if (file_exists($arquivo_pagamento)) {
        $pagamento = json_decode(file_get_contents($arquivo_pagamento), true);
        if ($pagamento['status'] == 'approved') {
            $_SESSION['download_token'] = bin2hex(random_bytes(32));
            $_SESSION['download_arquivo'] = $_SESSION['produto_arquivo'] ?? 'cybercoari_pro.zip';
            $mensagem = "✅ Pagamento confirmado! Seu download está disponível.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Aprovado - CyberCoari</title>
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
        .card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        .icon { font-size: 80px; margin-bottom: 20px; }
        h2 { color: #28a745; margin-bottom: 15px; }
        .btn-download {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            margin-top: 20px;
            font-weight: bold;
        }
        .btn-voltar {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            margin-top: 10px;
        }
        .info { margin-top: 20px; font-size: 14px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="icon">✅</div>
            <h2>Pagamento Aprovado!</h2>
            <p><?= htmlspecialchars($mensagem) ?></p>
            
            <?php if (isset($_SESSION['download_arquivo'])): ?>
                <a href="download.php" class="btn-download">📥 Fazer Download</a>
            <?php endif; ?>
            
            <a href="/index.php" class="btn-voltar">← Voltar para loja</a>
            <div class="info">🔒 Seu pagamento foi processado com segurança</div>
        </div>
    </div>
</body>
</html>