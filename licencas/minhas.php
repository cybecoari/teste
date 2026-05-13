<?php
require __DIR__ . "/../config/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$sql = $pdo->prepare("
    SELECT * FROM licencas 
    WHERE usuario_id = ? 
    ORDER BY id DESC
");
$sql->execute([$_SESSION['user_id']]);
$licencas = $sql->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Minhas Licenças - Cyber Coari</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1000px;
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
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
        }
        
        .status-ativa { color: #28a745; font-weight: bold; }
        .status-expirada { color: #dc3545; font-weight: bold; }
        .status-cancelada { color: #6c757d; font-weight: bold; }
        
        .btn-voltar {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
        }
        
        .chave {
            font-family: monospace;
            font-size: 13px;
        }
        
        @media (max-width: 768px) {
            th, td {
                font-size: 12px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>🔑 Minhas Licenças</h1>
            
            <?php if (count($licencas) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Chave</th>
                            <th>Produto</th>
                            <th>Status</th>
                            <th>Expira em</th>
                         </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($licencas as $licenca): ?>
                            <tr>
                                <td class="chave"><?= $licenca['chave'] ?></td>
                                <td><?= $licenca['produto'] ?? 'Software' ?></td>
                                <td class="status-<?= $licenca['status'] ?>"><?= ucfirst($licenca['status']) ?></td>
                                <td><?= $licenca['data_expiracao'] ? date('d/m/Y', strtotime($licenca['data_expiracao'])) : 'Vitalício' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666;">Você ainda não possui licenças.</p>
            <?php endif; ?>
            
            <div style="text-align: center;">
                <a href="index.php" class="btn-voltar">← Voltar</a>
            </div>
        </div>
    </div>
</body>
</html>