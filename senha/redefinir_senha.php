<?php
// senha/redefinir_senha.php - Redefinir senha com token

require __DIR__ . "/../config/db.php";

$erro = "";
$sucesso = "";
$token = $_GET['token'] ?? '';

if (empty($token)) {
    die("Token inválido! <a href='recuperar_senha.php'>Solicitar novo link</a>");
}

// Buscar usuário pelo token
$sql = $pdo->prepare("SELECT id, nome, email, reset_token, reset_expira FROM usuarios WHERE reset_token = ?");
$sql->execute([$token]);
$user = $sql->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Token inválido! <a href='recuperar_senha.php'>Solicitar novo link</a>");
}

// Verificar expiração
if (strtotime($user['reset_expira']) < time()) {
    die("Token expirado! <a href='recuperar_senha.php'>Solicitar novo link</a>");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    if (strlen($nova_senha) < 5) {
        $erro = "A senha deve ter no mínimo 5 caracteres!";
    } elseif ($nova_senha !== $confirmar_senha) {
        $erro = "As senhas não conferem!";
    } else {
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $update = $pdo->prepare("UPDATE usuarios SET senha = ?, reset_token = NULL, reset_expira = NULL WHERE id = ?");
        $update->execute([$hash, $user['id']]);
        $sucesso = "✅ Senha redefinida com sucesso!";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Cyber Coari</title>
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
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            width: 100%;
            max-width: 450px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        
        .user-info {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
        }
        
        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        
        .input-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
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
        }
        
        button:hover {
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
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
            border-left: 4px solid #28a745;
        }
        
        .info {
            text-align: center;
            margin-top: 20px;
        }
        
        .info a {
            color: #667eea;
            text-decoration: none;
        }
        
        .info a:hover {
            text-decoration: underline;
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 25px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔐 Redefinir Senha</h2>
        
        <?php if ($sucesso): ?>
            <div class="sucesso">
                ✅ <?= $sucesso ?>
            </div>
            <div class="info">
                <a href="../login.php">← Voltar para o login</a>
            </div>
        <?php else: ?>
            <div class="user-info">
                <strong>👤 Usuário:</strong> <?= htmlspecialchars($user['nome'] ?? $user['email']) ?><br>
                <strong>📧 Email:</strong> <?= htmlspecialchars($user['email']) ?>
            </div>
            
            <?php if ($erro): ?>
                <div class="erro">❌ <?= $erro ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group">
                    <label>🔒 Nova senha (mínimo 5 caracteres)</label>
                    <input type="password" name="nova_senha" required autofocus>
                </div>
                
                <div class="input-group">
                    <label>🔒 Confirmar nova senha</label>
                    <input type="password" name="confirmar_senha" required>
                </div>
                
                <button type="submit">Redefinir Senha</button>
            </form>
            
            <div class="info">
                <a href="recuperar_senha.php">← Solicitar novo link</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>