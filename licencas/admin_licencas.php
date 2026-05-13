<?php
// licencas/admin_licencas.php - Gerenciar licenças

require __DIR__ . "/../config/config.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Verificar se é admin
$sql = $pdo->prepare("SELECT perfil FROM usuarios WHERE id = ?");
$sql->execute([$_SESSION['user_id']]);
$user = $sql->fetch();

if (!$user || $user['perfil'] != 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

$erro = "";
$sucesso = "";

// Só processa se for POST e com o botão específico
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['gerar_licenca'])) {
    $usuario_id = intval($_POST['usuario_id'] ?? 0);
    $dias_validade = intval($_POST['dias_validade'] ?? 30);
    $produto = trim($_POST['produto'] ?? 'Software');
    
    if ($usuario_id <= 0) {
        $_SESSION['erro_licenca'] = "Selecione um usuário válido!";
    } else {
        // Gerar chave única
        $chave = gerarChaveLicenca();
        
        $data_expiracao = $dias_validade > 0 ? date('Y-m-d H:i:s', strtotime("+$dias_validade days")) : null;
        
        $sql = $pdo->prepare("INSERT INTO licencas (usuario_id, chave, produto, status, data_expiracao, criado_em) VALUES (?, ?, ?, 'ativa', ?, NOW())");
        
        if ($sql->execute([$usuario_id, $chave, $produto, $data_expiracao])) {
            $_SESSION['sucesso_licenca'] = "✅ Licença gerada com sucesso!<br>Chave: <strong>$chave</strong>";
        } else {
            $_SESSION['erro_licenca'] = "Erro ao gerar licença!";
        }
    }
    
    // REDIRECIONAR para evitar reenvio do POST
    header("Location: admin_licencas.php");
    exit;
}

// Revogar licença (GET com revogar)
if (isset($_GET['revogar']) && is_numeric($_GET['revogar'])) {
    $id = intval($_GET['revogar']);
    $sql = $pdo->prepare("UPDATE licencas SET status = 'cancelada' WHERE id = ?");
    $sql->execute([$id]);
    $_SESSION['sucesso_licenca'] = "✅ Licença revogada!";
    header("Location: admin_licencas.php");
    exit;
}

// Recuperar mensagens da sessão
if (isset($_SESSION['sucesso_licenca'])) {
    $sucesso = $_SESSION['sucesso_licenca'];
    unset($_SESSION['sucesso_licenca']);
}
if (isset($_SESSION['erro_licenca'])) {
    $erro = $_SESSION['erro_licenca'];
    unset($_SESSION['erro_licenca']);
}

function gerarChaveLicenca() {
    $prefixo = "CBC";
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
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
        
        .card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
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
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
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
        
        .status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-ativa { background: #d4edda; color: #155724; }
        .status-expirada { background: #f8d7da; color: #721c24; }
        .status-cancelada { background: #e2e3e5; color: #383d41; }
        
        .btn-revogar {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
        }
        
        .btn-voltar {
            background: #6c757d;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            color: white;
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
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            th, td { font-size: 12px; padding: 8px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>🔐 Gerenciar Licenças</h2>
            <a href="../dashboard.php" class="btn-voltar">← Voltar</a>
        </div>
        
        <?php if ($erro): ?>
            <div class="erro">❌ <?= $erro ?></div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="sucesso">✅ <?= $sucesso ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>🎫 Gerar Nova Licença</h3>
            <form method="POST" action="">
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
                
                <div class="form-row">
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
                </div>
                
                <button type="submit" name="gerar_licenca" id="btnGerar">🚀 Gerar Licença</button>
            </form>
        </div>
        
        <div class="card">
            <h3>📋 Licenças Geradas</h3>
            <div style="overflow-x: auto;">
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
                                <td><code><?= $licenca['chave'] ?></code></td>
                                <td><?= htmlspecialchars($licenca['produto'] ?? 'Software') ?></td>
                                <td>
                                    <span class="status status-<?= $licenca['status'] ?>">
                                        <?= ucfirst($licenca['status']) ?>
                                    </span>
                                </td>
                                <td><?= $licenca['data_expiracao'] ? date('d/m/Y', strtotime($licenca['data_expiracao'])) : 'Vitalício' ?></td>
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
    </div>
    
    <script>
        // Desabilitar botão após clique para evitar múltiplos envios
        document.getElementById('btnGerar')?.addEventListener('click', function(e) {
            this.disabled = true;
            this.textContent = '⏳ Processando...';
            this.form.submit();
        });
    </script>
</body>
</html>