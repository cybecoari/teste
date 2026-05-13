<?php
// admin/dashboard_vendas.php

require_once __DIR__ . '/../config/config.php';

// Verificar se é admin
if (!isset($_SESSION['user_id']) || $_SESSION['perfil'] !== 'admin') {
    header("Location: ../login.php");
    exit;
}

// Estatísticas
$total_vendas = $pdo->query("SELECT COUNT(*) FROM pedidos_telegram WHERE status = 'paid'")->fetchColumn();
$total_valor = $pdo->query("SELECT SUM(valor) FROM pedidos_telegram WHERE status = 'paid'")->fetchColumn();
$total_usuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$total_licencas = $pdo->query("SELECT COUNT(*) FROM licencas WHERE status = 'ativa'")->fetchColumn();

// Vendas por mês
$vendas_por_mes = $pdo->query("
    SELECT DATE_FORMAT(data_criacao, '%Y-%m') as mes, 
           COUNT(*) as total, 
           SUM(valor) as valor 
    FROM pedidos_telegram 
    WHERE status = 'paid' 
    GROUP BY mes 
    ORDER BY mes DESC 
    LIMIT 12
")->fetchAll();

// Últimas vendas
$ultimas_vendas = $pdo->prepare("
    SELECT p.*, u.nome as usuario_nome 
    FROM pedidos_telegram p
    LEFT JOIN usuarios u ON p.chat_id = u.id
    WHERE p.status = 'paid'
    ORDER BY p.data_criacao DESC
    LIMIT 10
");
$ultimas_vendas->execute();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard de Vendas - CyberCoari</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { margin-bottom: 30px; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .card h3 {
            margin-bottom: 15px;
            color: #333;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .btn-voltar {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📊 Dashboard de Vendas</h1>
            <p>Bem-vindo, <?= htmlspecialchars($_SESSION['nome']) ?></p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $total_vendas ?></div>
                <div class="stat-label">Total de Vendas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">R$ <?= number_format($total_valor ?? 0, 2, ',', '.') ?></div>
                <div class="stat-label">Receita Total</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_usuarios ?></div>
                <div class="stat-label">Usuários</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $total_licencas ?></div>
                <div class="stat-label">Licenças Ativas</div>
            </div>
        </div>

        <div class="card">
            <h3>📈 Últimas Vendas</h3>
            <table>
                <thead>
                    <tr><th>Pedido</th><th>Cliente</th><th>Produto</th><th>Valor</th><th>Data</th></tr>
                </thead>
                <tbody>
                    <?php while ($venda = $ultimas_vendas->fetch()): ?>
                    <tr>
                        <td>#<?= $venda['id'] ?></td>
                        <td><?= htmlspecialchars($venda['usuario_nome'] ?? 'Anônimo') ?></td>
                        <td><?= htmlspecialchars($venda['plano_nome']) ?></td>
                        <td>R$ <?= number_format($venda['valor'], 2, ',', '.') ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($venda['data_criacao'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>📊 Vendas por Mês</h3>
            <table>
                <thead><tr><th>Mês</th><th>Vendas</th><th>Receita</th></tr></thead>
                <tbody>
                    <?php foreach ($vendas_por_mes as $venda): ?>
                    <tr>
                        <td><?= date('m/Y', strtotime($venda['mes'] . '-01')) ?></td>
                        <td><?= $venda['total'] ?></td>
                        <td>R$ <?= number_format($venda['valor'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <a href="../dashboard.php" class="btn-voltar">← Voltar ao Dashboard</a>
    </div>
</body>
</html>