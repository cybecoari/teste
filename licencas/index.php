<?php
// licencas/index.php - Menu do sistema de licenças

require __DIR__ . "/../config/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$isAdmin = isAdmin($pdo);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Licenças - Cyber Coari</title>
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
        
        .header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .header p {
            color: #666;
        }
        
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .menu-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .menu-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .menu-card h3 {
            margin-bottom: 10px;
        }
        
        .menu-card p {
            font-size: 14px;
            color: #666;
        }
        
        .btn-voltar {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 20px;
        }
        
        @media (max-width: 768px) {
            .menu-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 Sistema de Licenças</h1>
            <p>Gerencie e valide suas licenças de software</p>
        </div>
        
        <div class="menu-grid">
            <a href="validar.php" class="menu-card">
                <div class="menu-icon">✅</div>
                <h3>Validar Licença</h3>
                <p>Verifique se uma chave é válida</p>
            </a>
            
            <a href="minhas.php" class="menu-card">
                <div class="menu-icon">🔑</div>
                <h3>Minhas Licenças</h3>
                <p>Visualize suas licenças ativas</p>
            </a>
            
            <?php if ($isAdmin): ?>
            <a href="admin_licencas.php" class="menu-card">
                <div class="menu-icon">🎫</div>
                <h3>Gerenciar Licenças</h3>
                <p>Criar, revogar e gerenciar</p>
            </a>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center;">
            <a href="../dashboard.php" class="btn-voltar">← Voltar ao Dashboard</a>
        </div>
    </div>
</body>
</html>