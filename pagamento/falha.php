<?php
// pagamento/falha.php - Página de falha

require_once __DIR__ . '/../config/config.php';

$payment_id = $_GET['payment_id'] ?? $_GET['preference_id'] ?? null;
$mensagem = "❌ O pagamento não foi concluído. Tente novamente.";

// Registrar log de falha
file_put_contents(__DIR__ . '/pagamentos/falhas_log.txt', date('Y-m-d H:i:s') . " - Pagamento falhou: $payment_id\n", FILE_APPEND);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pagamento Falhou - CyberCoari</title>
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
        h2 { color: #dc3545; margin-bottom: 15px; }
        .btn-tentar {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 12px 30px;
            border-radius: 10px;
            text-decoration: none;
            margin-top: 20px;
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
        .dicas { margin-top: 20px; text-align: left; background: #f8f9fa; padding: 15px; border-radius: 10px; font-size: 14px; }
        .dicas li { margin: 5px 0; margin-left: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="icon">❌</div>
            <h2>Falha no Pagamento</h2>
            <p><?= htmlspecialchars($mensagem) ?></p>
            
            <div class="dicas">
                <strong>💡 Possíveis motivos:</strong>
                <ul>
                    <li>Saldo insuficiente</li>
                    <li>Dados do cartão incorretos</li>
                    <li>Limite diário excedido</li>
                    <li>Pagamento recusado pelo banco</li>
                </ul>
            </div>
            
            <a href="javascript:history.back()" class="btn-tentar">🔄 Tentar novamente</a>
            <a href="/index.php" class="btn-voltar">← Voltar para loja</a>
        </div>
    </div>
</body>
</html>