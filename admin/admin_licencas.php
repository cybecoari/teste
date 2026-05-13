<?php
require __DIR__ . "/config/config.php";

// Verificar se está logado
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$erro = "";
$sucesso = "";

// Gerar nova licença
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['gerar_licenca'])) {
    $usuario_id = $_POST['usuario_id'] ?? 0;
    $dias_validade = $_POST['dias_validade'] ?? 30;
    $produto = $_POST['produto'] ?? 'Software';
    
    // Gerar chave única
    $chave = gerarChaveLicenca();
    
    $data_expiracao = date('Y-m-d H:i:s', strtotime("+$dias_validade days"));
    
    $sql = $pdo->prepare("INSERT INTO licencas (usuario_id, chave, produto, status, data_expiracao, criado_em) VALUES (?, ?, ?, 'ativa', ?, NOW())");
    
    if ($sql->execute([$usuario_id, $chave, $produto, $data_expiracao])) {
        $sucesso = "✅ Licença gerada com sucesso!<br>Chave: <strong>$chave</strong>";
    } else {
        $erro = "Erro ao gerar licença!";
    }
}

// Revogar licença
if (isset($_GET['revogar'])) {
    $id = $_GET['revogar'];
    $sql = $pdo->prepare("UPDATE licencas SET status = 'cancelada' WHERE id = ?");
    $sql->execute([$id]);
    $sucesso = "✅ Licença revogada!";
}

// Função para gerar chave
function gerarChaveLicenca() {
    $prefixo = "SPFC";
    $partes = [];
    for ($i = 0; $i < 4; $i++) {
        $partes[] = strtoupper(bin2hex(random_bytes(4)));
    }
    return $prefixo . "-" . implode("-", $partes);
}

// Listar usuários para o select
$usuarios = $pdo->query("SELECT id, nome, email FROM usuarios ORDER BY nome")->fetchAll();

// Listar licenças
$licencas = $pdo->query("
    SELECT l.*, u.nome, u.email 
    FROM licencas l 
    JOIN usuarios u ON l.usuario_id = u.id 
    ORDER BY l.id DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Licenças - Cyber Coari</title>
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
        }
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group select, .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        
        button:hover {
            opacity: 0.9;
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
            font-weight: 600;
        }
        
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-ativa {
            background: #d4edda;
            color: #155724;
        }
        
        .status-expirada {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-pendente {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-cancelada {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .btn-revogar {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .btn-copiar {
            background: #28a745;
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }
        
        .chave {
            font-family: monospace;
            font-size: 14px;
            font-weight: bold;
        }
        
        .sucesso {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .erro {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .logout {
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🔐 Gerenciador de Licenças</h2>
            <a href="dashboard.php" class="logout">← Voltar</a>
        </div>
        
        <?php if ($erro): ?>
            <div class="erro">❌ <?= $erro ?></div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="sucesso">✅ <?= $sucesso ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>🎫 Gerar Nova Licença</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Usuário</label>
                    <select name="usuario_id" required>
                        <option value="">Selecione um usuário</option>
                        <?php foreach ($usuarios as $user): ?>
                            <option value="<?= $user['id'] ?>">
                                <?= htmlspecialchars($user['nome']) ?> - <?= htmlspecialchars($user['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Produto</label>
                    <input type="text" name="produto" value="Software" required>
                </div>
                
                <div class="form-group">
                    <label>Dias de validade</label>
                    <select name="dias_validade">
                        <option value="7">7 dias</option>
                        <option value="30" selected>30 dias</option>
                        <option value="90">90 dias</option>
                        <option value="180">180 dias</option>
                        <option value="365">1 ano</option>
                        <option value="0">Vitalício (não expira)</option>
                    </select>
                </div>
                
                <button type="submit" name="gerar_licenca">🚀 Gerar Licença</button>
            </form>
        </div>
        
        <div class="card">
            <h3>📋 Licenças Geradas</h3>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuário</th>
                        <th>Chave</th>
                        <th>Produto</th>
                        <th>Status</th>
                        <th>Expira em</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($licencas as $licenca): ?>
                        <tr>
                            <td><?= $licenca['id'] ?></td>
                            <td><?= htmlspecialchars($licenca['nome']) ?></td>
                            <td>
                                <span class="chave"><?= $licenca['chave'] ?></span>
                                <button class="btn-copiar" onclick="copiarChave('<?= $licenca['chave'] ?>')">📋</button>
                            </td>
                            <td><?= htmlspecialchars($licenca['produto'] ?? 'Software') ?></td>
                            <td>
                                <span class="status status-<?= $licenca['status'] ?>">
                                    <?= ucfirst($licenca['status']) ?>
                                </span>
                            </td>
                            <td><?= $licenca['data_expiracao'] ? date('d/m/Y H:i', strtotime($licenca['data_expiracao'])) : 'Vitalício' ?></td>
                            <td>
                                <?php if ($licenca['status'] == 'ativa'): ?>
                                    <a href="?revogar=<?= $licenca['id'] ?>" class="btn-revogar" onclick="return confirm('Revogar esta licença?')">Revogar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <script>
        function copiarChave(chave) {
            navigator.clipboard.writeText(chave);
            alert('Chave copiada: ' + chave);
        }
    </script>
</body>
</html>