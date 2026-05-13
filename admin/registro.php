<?php
// admin/registro.php - Criar novos usuários (apenas admin)

require __DIR__ . "/../config/config.php";

// Verificar se está logado e é ADMIN
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Verificar se o usuário é administrador
$sql = $pdo->prepare("SELECT perfil FROM usuarios WHERE id = ?");
$sql->execute([$_SESSION['user_id']]);
$user = $sql->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['perfil'] != 'admin') {
    header("Location: ../dashboard.php");
    exit;
}

$erro = "";
$sucesso = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar = $_POST['confirmar'] ?? '';
    $perfil = $_POST['perfil'] ?? 'user';
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = "Preencha todos os campos!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Email inválido!";
    } elseif (strlen($senha) < 6) {
        $erro = "Senha deve ter no mínimo 6 caracteres!";
    } elseif ($senha !== $confirmar) {
        $erro = "As senhas não conferem!";
    } else {
        // Verificar se email ou nome já existe
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR nome = ?");
        $check->execute([$email, $nome]);
        
        if ($check->rowCount() > 0) {
            $erro = "Email ou nome de usuário já existe!";
        } else {
            // Criar usuário
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, perfil, criado_em) VALUES (?, ?, ?, ?, NOW())");
            
            if ($sql->execute([$nome, $email, $senha_hash, $perfil])) {
                $sucesso = "✅ Usuário criado com sucesso!";
                
                // Limpar formulário
                $_POST = [];
            } else {
                $erro = "Erro ao criar usuário!";
            }
        }
    }
}

// Buscar estatísticas
$totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
$totalAdmins = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE perfil = 'admin'")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE perfil = 'user'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Usuário - Admin | Cyber Coari</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .header {
            background: white;
            border-radius: 20px;
            padding: 25px;
            margin-bottom: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            color: #333;
            margin-bottom: 5px;
        }
        
        .header p {
            color: #666;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
        }
        
        .card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        .btn-voltar {
            display: inline-block;
            background: #6c757d;
            margin-top: 20px;
            text-align: center;
            text-decoration: none;
        }
        
        .btn-voltar:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .erro {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #c33;
        }
        
        .sucesso {
            background: #efe;
            color: #3c3;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #3c3;
        }
        
        .info {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
            color: #666;
        }
        
        @media (max-width: 768px) {
            .stats {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .card {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>👥 Criar Novo Usuário</h1>
            <p>Área administrativa - Apenas administradores</p>
        </div>
        
        <!-- Estatísticas -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $totalUsuarios ?></div>
                <div class="stat-label">Total de Usuários</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalAdmins ?></div>
                <div class="stat-label">Administradores</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalUsers ?></div>
                <div class="stat-label">Usuários Comuns</div>
            </div>
        </div>
        
        <div class="card">
            <?php if ($erro): ?>
                <div class="erro">❌ <?= $erro ?></div>
            <?php endif; ?>
            
            <?php if ($sucesso): ?>
                <div class="sucesso">✅ <?= $sucesso ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>👤 Nome de usuário</label>
                        <input type="text" name="nome" placeholder="Ex: joaosilva" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>📧 E-mail</label>
                        <input type="email" name="email" placeholder="seu@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>🔒 Senha</label>
                        <input type="password" name="senha" placeholder="Mínimo 6 caracteres" required>
                    </div>
                    
                    <div class="form-group">
                        <label>🔒 Confirmar senha</label>
                        <input type="password" name="confirmar" placeholder="Digite a senha novamente" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>👑 Perfil</label>
                    <select name="perfil">
                        <option value="user">📱 Usuário Comum</option>
                        <option value="admin">👑 Administrador</option>
                    </select>
                </div>
                
                <button type="submit">➕ Criar Usuário</button>
            </form>
            
            <div class="info">
                <a href="../dashboard.php" class="btn-voltar" style="display: inline-block; padding: 12px 24px; text-decoration: none; color: white; border-radius: 10px;">← Voltar ao Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>