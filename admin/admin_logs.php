<?php
require __DIR__ . "/config/config.php";

// Verificar se está logado e é admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$sql = $pdo->prepare("SELECT perfil FROM usuarios WHERE id = ?");
$sql->execute([$_SESSION['user_id']]);
$user = $sql->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['perfil'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

$tipo = $_GET['tipo'] ?? 'login';
$busca = $_GET['busca'] ?? '';
$limite = 100;

// Buscar logs de login
if ($tipo == 'login') {
    $query = "SELECT * FROM logs_login";
    $params = [];
    
    if (!empty($busca)) {
        $query .= " WHERE email LIKE ? OR ip LIKE ?";
        $params = ["%$busca%", "%$busca%"];
    }
    
    $query .= " ORDER BY data DESC LIMIT $limite";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
}

// Buscar logs de validação de licenças
if ($tipo == 'validacao') {
    $query = "SELECT lv.*, l.chave as licenca_chave, u.nome as usuario_nome 
              FROM logs_validacao lv 
              LEFT JOIN licencas l ON lv.licenca_id = l.id 
              LEFT JOIN usuarios u ON l.usuario_id = u.id";
    $params = [];
    
    if (!empty($busca)) {
        $query .= " WHERE l.chave LIKE ? OR u.nome LIKE ? OR lv.ip LIKE ?";
        $params = ["%$busca%", "%$busca%", "%$busca%"];
    }
    
    $query .= " ORDER BY lv.data DESC LIMIT $limite";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
}

// Buscar tentativas de login
if ($tipo == 'tentativas') {
    $query = "SELECT * FROM tentativas_login";
    $params = [];
    
    if (!empty($busca)) {
        $query .= " WHERE email LIKE ? OR ip LIKE ?";
        $params = ["%$busca%", "%$busca%"];
    }
    
    $query .= " ORDER BY tentativa DESC LIMIT $limite";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
}

// Limpar logs antigos
if (isset($_GET['limpar']) && $_GET['limpar'] == 'sim') {
    if ($tipo == 'login') {
        $pdo->exec("DELETE FROM logs_login WHERE data < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $sucesso = "Logs de login antigos limpos!";
    } elseif ($tipo == 'tentativas') {
        $pdo->exec("DELETE FROM tentativas_login WHERE tentativa < DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $sucesso = "Tentativas antigas limpas!";
    }
    header("Location: admin_logs.php?tipo=$tipo");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs do Sistema - Cyber Coari</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .tab {
            padding: 10px 20px;
            background: white;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .tab.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .busca {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .busca input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .busca button, .btn-limpar {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .busca button {
            background: #667eea;
            color: white;
        }
        
        .btn-limpar {
            background: #dc3545;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            overflow-x: auto;
            display: block;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .sucesso-badge {
            background: #d4edda;
            color: #155724;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .falha-badge {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .logout {
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
        }
        
        .info {
            text-align: center;
            padding: 20px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            th, td {
                font-size: 12px;
                padding: 8px;
            }
            
            .tabs {
                justify-content: center;
            }
            
            .tab {
                padding: 8px 15px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>📊 Logs do Sistema</h2>
            <a href="dashboard.php" class="logout">← Voltar ao Dashboard</a>
        </div>
        
        <div class="tabs">
            <a href="?tipo=login" class="tab <?= $tipo == 'login' ? 'active' : '' ?>">🔐 Logs de Login</a>
            <a href="?tipo=validacao" class="tab <?= $tipo == 'validacao' ? 'active' : '' ?>">✅ Logs de Validação</a>
            <a href="?tipo=tentativas" class="tab <?= $tipo == 'tentativas' ? 'active' : '' ?>">⚠️ Tentativas Falhas</a>
        </div>
        
        <div class="card">
            <div class="busca">
                <form method="GET" style="display: flex; gap: 10px; flex: 1; flex-wrap: wrap;">
                    <input type="hidden" name="tipo" value="<?= $tipo ?>">
                    <input type="text" name="busca" placeholder="Buscar..." value="<?= htmlspecialchars($busca) ?>">
                    <button type="submit">🔍 Buscar</button>
                    <?php if ($busca): ?>
                        <a href="?tipo=<?= $tipo ?>" class="btn-limpar">Limpar</a>
                    <?php endif; ?>
                </form>
                <?php if ($tipo != 'validacao'): ?>
                    <a href="?tipo=<?= $tipo ?>&limpar=sim" class="btn-limpar" onclick="return confirm('Limpar logs antigos?')">🗑️ Limpar Antigos</a>
                <?php endif; ?>
            </div>
            
            <?php if (count($logs) > 0): ?>
                <div style="overflow-x: auto;">
                    <table>
                        <thead>
                            <tr>
                                <?php if ($tipo == 'login'): ?>
                                    <th>Email</th>
                                    <th>IP</th>
                                    <th>Status</th>
                                    <th>Data/Hora</th>
                                <?php elseif ($tipo == 'validacao'): ?>
                                    <th>Licença</th>
                                    <th>Usuário</th>
                                    <th>IP</th>
                                    <th>Data/Hora</th>
                                <?php elseif ($tipo == 'tentativas'): ?>
                                    <th>Email</th>
                                    <th>IP</th>
                                    <th>Tentativa</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <?php if ($tipo == 'login'): ?>
                                        <td><?= htmlspecialchars($log['email'] ?? '-') ?></td>
                                        <td><?= $log['ip'] ?></td>
                                        <td>
                                            <span class="<?= $log['sucesso'] ? 'sucesso-badge' : 'falha-badge' ?>">
                                                <?= $log['sucesso'] ? '✅ Sucesso' : '❌ Falha' ?>
                                            </span>
                                        </td>
                                        <td><?= date('d/m/Y H:i:s', strtotime($log['data'])) ?></td>
                                    <?php elseif ($tipo == 'validacao'): ?>
                                        <td><code><?= htmlspecialchars($log['licenca_chave'] ?? '-') ?></code></td>
                                        <td><?= htmlspecialchars($log['usuario_nome'] ?? '-') ?></td>
                                        <td><?= $log['ip'] ?></td>
                                        <td><?= date('d/m/Y H:i:s', strtotime($log['data'])) ?></td>
                                    <?php elseif ($tipo == 'tentativas'): ?>
                                        <td><?= htmlspecialchars($log['email'] ?? '-') ?></td>
                                        <td><?= $log['ip'] ?></td>
                                        <td><?= date('d/m/Y H:i:s', strtotime($log['tentativa'])) ?></td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="info">
                    📭 Nenhum log encontrado.
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>