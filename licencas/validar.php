<?php
require __DIR__ . "/../config/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$resultado = null;
$chave = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $chave = trim($_POST['chave'] ?? '');
    
    if (!empty($chave)) {
        $url = "https://" . $_SERVER['HTTP_HOST'] . "/licencas/api.php?chave=" . urlencode($chave);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $resultado = json_decode($response, true);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validar Licença - Cyber Coari</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        h1 {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            font-family: monospace;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
        }
        
        .resultado {
            margin-top: 25px;
            padding: 15px;
            border-radius: 10px;
        }
        
        .valido {
            background: #d4edda;
            border-left: 4px solid #28a745;
        }
        
        .invalido {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
        }
        
        .btn-voltar {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>✅ Validar Licença</h1>
            <div class="subtitle">Digite a chave da licença para verificar</div>
            
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="chave" placeholder="Ex: CBC-XXXX-XXXX-XXXX-XXXX" value="<?= htmlspecialchars($chave) ?>" required autofocus>
                </div>
                <button type="submit">Validar</button>
            </form>
            
            <?php if ($resultado): ?>
                <div class="resultado <?= $resultado['success'] ? 'valido' : 'invalido' ?>">
                    <?php if ($resultado['success']): ?>
                        <h3>✅ Licença Válida!</h3>
                        <div class="info-row">
                            <span><strong>Produto:</strong></span>
                            <span><?= htmlspecialchars($resultado['data']['produto']) ?></span>
                        </div>
                        <div class="info-row">
                            <span><strong>Validade:</strong></span>
                            <span><?= $resultado['data']['validade'] ?></span>
                        </div>
                        <div class="info-row">
                            <span><strong>Dias restantes:</strong></span>
                            <span><?= $resultado['data']['dias_restantes'] ?? 'Ilimitado' ?></span>
                        </div>
                    <?php else: ?>
                        <h3>❌ <?= htmlspecialchars($resultado['message']) ?></h3>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center;">
                <a href="index.php" class="btn-voltar">← Voltar</a>
            </div>
        </div>
    </div>
</body>
</html>