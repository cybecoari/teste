<?php
// pagamento/vendas.php - Lista de vendas (CORRIGIDO)

require_once __DIR__ . '/../config/config.php';

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Verificar se é admin (compatível com diferentes formas de armazenar o perfil)
$isAdmin = false;

// Verificar de diferentes formas
if (isset($_SESSION['perfil'])) {
    $isAdmin = ($_SESSION['perfil'] == 'admin');
} elseif (isset($_SESSION['nivel'])) {
    $isAdmin = ($_SESSION['nivel'] == 'admin');
} elseif (isset($_SESSION['tipo'])) {
    $isAdmin = ($_SESSION['tipo'] == 'admin');
} else {
    // Buscar no banco de dados
    try {
        $stmt = $pdo->prepare("SELECT perfil FROM usuarios WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if ($user) {
            $isAdmin = ($user['perfil'] == 'admin');
            $_SESSION['perfil'] = $user['perfil']; // Salvar na sessão
        }
    } catch (Exception $e) {
        // Erro ao buscar
    }
}

// Se não for admin, negar acesso
if (!$isAdmin) {
    echo "<!DOCTYPE html>";
    echo "<html><head><style>";
    echo "body { font-family: Arial; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; }";
    echo ".card { background: white; border-radius: 20px; padding: 40px; text-align: center; max-width: 400px; }";
    echo "h1 { color: #dc3545; margin-bottom: 15px; }";
    echo ".btn { display: inline-block; background: #667eea; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; margin-top: 20px; }";
    echo "</style></head><body>";
    echo "<div class='card'>";
    echo "<h1>⛔ Acesso Negado</h1>";
    echo "<p>Você não tem permissão para acessar esta página.</p>";
    echo "<p><strong>Usuário ID:</strong> " . $_SESSION['user_id'] . "</p>";
    echo "<a href='../dashboard.php' class='btn'>← Voltar ao Dashboard</a>";
    echo "</div></body></html>";
    exit;
}

// Filtros
$filter_status = $_GET['status'] ?? 'todos';
$filter_data_inicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$filter_data_fim = $_GET['data_fim'] ?? date('Y-m-d');
$produto_filtro = $_GET['produto'] ?? '';

// Buscar vendas
$sql = "SELECT p.*, 
        DATE_FORMAT(p.data_criacao, '%d/%m/%Y %H:%i') as data_formatada,
        DATE_FORMAT(p.data_pagamento, '%d/%m/%Y %H:%i') as data_pagamento_formatada
        FROM pedidos_telegram p 
        WHERE DATE(p.data_criacao) BETWEEN :data_inicio AND :data_fim";

if ($filter_status !== 'todos') {
    $sql .= " AND p.status = :status";
}
if ($produto_filtro) {
    $sql .= " AND p.plano_nome LIKE :produto";
}

$sql .= " ORDER BY p.data_criacao DESC";

$stmt = $pdo->prepare($sql);
$stmt->bindValue(':data_inicio', $filter_data_inicio);
$stmt->bindValue(':data_fim', $filter_data_fim);
if ($filter_status !== 'todos') {
    $stmt->bindValue(':status', $filter_status);
}
if ($produto_filtro) {
    $stmt->bindValue(':produto', "%$produto_filtro%");
}
$stmt->execute();
$vendas = $stmt->fetchAll();

// Estatísticas
$sql_stats = "SELECT 
                COUNT(*) as total_vendas,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as aprovadas,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pendentes,
                SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expiradas,
                SUM(CASE WHEN status = 'paid' THEN valor ELSE 0 END) as valor_total
              FROM pedidos_telegram 
              WHERE DATE(data_criacao) BETWEEN :data_inicio AND :data_fim";
$stmt_stats = $pdo->prepare($sql_stats);
$stmt_stats->bindValue(':data_inicio', $filter_data_inicio);
$stmt_stats->bindValue(':data_fim', $filter_data_fim);
$stmt_stats->execute();
$stats = $stmt_stats->fetch();

// Buscar produtos distintos para o filtro
$produtos_distintos = $pdo->query("SELECT DISTINCT plano_nome FROM pedidos_telegram WHERE plano_nome IS NOT NULL ORDER BY plano_nome")->fetchAll();

// Exportar para Excel
if (isset($_GET['export']) && $_GET['export'] == 'excel') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=vendas_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'Data', 'Chat ID', 'E-mail', 'Produto', 'Valor', 'Status', 'Payment ID', 'Key', 'Data Pagamento']);
    
    foreach ($vendas as $venda) {
        fputcsv($output, [
            $venda['id'],
            $venda['data_formatada'],
            $venda['chat_id'],
            $venda['email_cliente'],
            $venda['plano_nome'],
            'R$ ' . number_format($venda['valor'], 2, ',', '.'),
            $venda['status'],
            $venda['payment_id'],
            $venda['key_gerada'],
            $venda['data_pagamento_formatada']
        ]);
    }
    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendas - CyberCoari</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        /* Cabeçalho */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        .header h1 { font-size: 24px; }
        .btn-voltar {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
        }
        .btn-voltar:hover { background: rgba(255,255,255,0.3); }
        
        /* Cards de estatísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .stat-label { color: #666; font-size: 14px; }
        .stat-card.total .stat-number { color: #667eea; }
        .stat-card.aprovadas .stat-number { color: #28a745; }
        .stat-card.pendentes .stat-number { color: #ffc107; }
        .stat-card.expiradas .stat-number { color: #dc3545; }
        .stat-card.valor .stat-number { color: #17a2b8; }
        
        /* Filtros */
        .filtros {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .filtros form {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: flex-end;
        }
        .filtros .grupo {
            flex: 1;
            min-width: 150px;
        }
        .filtros label {
            display: block;
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        .filtros select, .filtros input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .filtros button {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 8px;
            cursor: pointer;
        }
        .btn-excel {
            background: #28a745;
        }
        
        /* Tabela */
        .tabela-container {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow-x: auto;
        }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; font-weight: 600; color: #333; }
        tr:hover { background: #f8f9fa; }
        
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-paid { background: #d4edda; color: #155724; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-expired { background: #f8d7da; color: #721c24; }
        
        .key-code {
            font-family: monospace;
            font-size: 12px;
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
        }
        
        @media (max-width: 768px) {
            th, td { font-size: 12px; padding: 8px; }
            .stats-grid { gap: 10px; }
            .stat-card { padding: 15px; }
            .stat-number { font-size: 24px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>💰 Vendas</h1>
            <a href="../dashboard.php" class="btn-voltar">← Voltar ao Dashboard</a>
        </div>

        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card total">
                <div class="stat-number"><?= number_format($stats['total_vendas'] ?? 0) ?></div>
                <div class="stat-label">Total de Vendas</div>
            </div>
            <div class="stat-card aprovadas">
                <div class="stat-number"><?= number_format($stats['aprovadas'] ?? 0) ?></div>
                <div class="stat-label">Aprovadas</div>
            </div>
            <div class="stat-card pendentes">
                <div class="stat-number"><?= number_format($stats['pendentes'] ?? 0) ?></div>
                <div class="stat-label">Pendentes</div>
            </div>
            <div class="stat-card expiradas">
                <div class="stat-number"><?= number_format($stats['expiradas'] ?? 0) ?></div>
                <div class="stat-label">Expiradas/Canceladas</div>
            </div>
            <div class="stat-card valor">
                <div class="stat-number">R$ <?= number_format($stats['valor_total'] ?? 0, 2, ',', '.') ?></div>
                <div class="stat-label">Receita Total</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros">
            <form method="GET">
                <div class="grupo">
                    <label>📅 Data Início</label>
                    <input type="date" name="data_inicio" value="<?= htmlspecialchars($filter_data_inicio) ?>">
                </div>
                <div class="grupo">
                    <label>📅 Data Fim</label>
                    <input type="date" name="data_fim" value="<?= htmlspecialchars($filter_data_fim) ?>">
                </div>
                <div class="grupo">
                    <label>💰 Status</label>
                    <select name="status">
                        <option value="todos" <?= $filter_status == 'todos' ? 'selected' : '' ?>>Todos</option>
                        <option value="paid" <?= $filter_status == 'paid' ? 'selected' : '' ?>>Aprovados</option>
                        <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Pendentes</option>
                        <option value="expired" <?= $filter_status == 'expired' ? 'selected' : '' ?>>Expirados</option>
                    </select>
                </div>
                <div class="grupo">
                    <label>📦 Produto</label>
                    <select name="produto">
                        <option value="">Todos</option>
                        <?php foreach ($produtos_distintos as $p): ?>
                        <option value="<?= htmlspecialchars($p['plano_nome']) ?>" <?= $produto_filtro == $p['plano_nome'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($p['plano_nome']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grupo">
                    <button type="submit">🔍 Filtrar</button>
                </div>
                <div class="grupo">
                    <a href="?export=excel&data_inicio=<?= $filter_data_inicio ?>&data_fim=<?= $filter_data_fim ?>&status=<?= $filter_status ?>" class="btn-voltar" style="background: #28a745; display: inline-block; padding: 8px 20px;">📊 Exportar Excel</a>
                </div>
            </form>
        </div>

        <!-- Tabela de Vendas -->
        <div class="tabela-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Cliente (Chat ID)</th>
                        <th>E-mail</th>
                        <th>Produto</th>
                        <th>Valor</th>
                        <th>Status</th>
                        <th>Payment ID</th>
                        <th>Key</th>
                        <th>Data Pagamento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($vendas) > 0): ?>
                        <?php foreach ($vendas as $venda): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($venda['id']) ?></code></td>
                            <td><?= $venda['data_formatada'] ?></td>
                            <td><?= $venda['chat_id'] ?></td>
                            <td><?= htmlspecialchars($venda['email_cliente'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($venda['plano_nome']) ?></td>
                            <td><strong>R$ <?= number_format($venda['valor'], 2, ',', '.') ?></strong></td>
                            <td>
                                <?php
                                $status_class = '';
                                $status_text = '';
                                switch($venda['status']) {
                                    case 'paid':
                                        $status_class = 'status-paid';
                                        $status_text = '✅ Aprovado';
                                        break;
                                    case 'pending':
                                        $status_class = 'status-pending';
                                        $status_text = '⏳ Pendente';
                                        break;
                                    case 'expired':
                                        $status_class = 'status-expired';
                                        $status_text = '❌ Expirado';
                                        break;
                                    default:
                                        $status_text = $venda['status'];
                                }
                                ?>
                                <span class="status-badge <?= $status_class ?>"><?= $status_text ?></span>
                            </td>
                            <td><code><?= htmlspecialchars($venda['payment_id'] ?? '-') ?></code></td>
                            <td>
                                <?php if ($venda['key_gerada']): ?>
                                    <code class="key-code"><?= htmlspecialchars($venda['key_gerada']) ?></code>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td><?= $venda['data_pagamento_formatada'] ?? '-' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px;">
                                📭 Nenhuma venda encontrada no período selecionado.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>