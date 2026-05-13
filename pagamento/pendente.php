<?php
// pagamento/pendente.php - Página de pagamento pendente

require_once __DIR__ . '/../config/config.php';

$payment_id = $_GET['payment_id'] ?? $_GET['preference_id'] ?? null;
$external_reference = $_GET['external_reference'] ?? null;

$mensagem = "⏳ Seu pagamento está sendo processado. Em breve será confirmado.";
$auto_refresh = true;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Pendente - CyberCoari</title>
    <?php if ($auto_refresh): ?>
    <meta http-equiv="refresh" content="10;url=sucesso.php<?= $external_reference ? '?external_reference=' . $external_reference : '' ?>">
    <?php endif; ?>
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
        .icon { font-size: 80px; margin-bottom: 20px; animation: pulse 2s infinite; }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        h2 { color: #ffc107; margin-bottom: 15px; }
        .loading {
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 20px 0;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .btn-voltar {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="icon">⏳</div>
            <h2>Pagamento Pendente</h2>
            <p><?= htmlspecialchars($mensagem) ?></p>
            <div class="loading"></div>
            <p style="font-size: 14px; color: #666;">Aguardando confirmação do banco...</p>
            <a href="/index.php" class="btn-voltar">← Voltar para loja</a>
        </div>
    </div>
</body>
</html>