<?php
require __DIR__ . "/config/config.php";

$erro = "";
$sucesso = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha)) {
        $erro = "Preencha todos os campos!";
    } elseif (strlen($nome) < 3) {
        $erro = "Nome deve ter no mínimo 3 caracteres!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido!";
    } elseif (strlen($senha) < 5) {
        $erro = "Senha deve ter no mínimo 5 caracteres!";
    } elseif ($senha !== $confirmar_senha) {
        $erro = "As senhas não conferem!";
    } else {
        // Verificar se email ou nome já existe
        $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ? OR nome = ?");
        $check->execute([$email, $nome]);
        
        if ($check->rowCount() > 0) {
            $erro = "E-mail ou nome de usuário já está em uso!";
        } else {
            // Criar novo usuário
            $hash = password_hash($senha, PASSWORD_DEFAULT);
            $sql = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, criado_em, ativo) VALUES (?, ?, ?, NOW(), 1)");
            
            if ($sql->execute([$nome, $email, $hash])) {
                $sucesso = "✅ Conta criada com sucesso!";
                $sucesso .= "<br><a href='login.php' style='display:inline-block;margin-top:15px;background:#667eea;color:white;padding:10px 20px;text-decoration:none;border-radius:8px;'>🔐 Fazer login</a>";
                // Limpar formulário
                $_POST = [];
            } else {
                $erro = "Erro ao criar conta. Tente novamente!";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar conta - Cyber Coari</title>
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
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .input-group {
            margin-bottom: 20px;
        }
        
        .input-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
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
        
        .input-group .hint {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
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
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin-top: 10px;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        button:active {
            transform: translateY(0);
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
        
        .info a {
            color: #667eea;
            text-decoration: none;
        }
        
        .info a:hover {
            text-decoration: underline;
        }
        
        .password-strength {
            height: 4px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 8px;
            transition: all 0.3s ease;
        }
        
        .strength-bar {
            height: 100%;
            width: 0%;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .strength-text {
            font-size: 11px;
            margin-top: 5px;
            text-align: right;
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
        <h2>📝 Criar conta</h2>
        <div class="subtitle">Preencha os dados para se cadastrar</div>
        
        <?php if ($erro): ?>
            <div class="erro">❌ <?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="sucesso"><?= $sucesso ?></div>
        <?php endif; ?>
        
        <?php if (!$sucesso): ?>
        <form method="POST" id="formRegistro">
            <div class="input-group">
                <label>👤 Nome de usuário</label>
                <input type="text" name="nome" placeholder="Ex: joaosilva" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>" required autofocus>
                <div class="hint">Mínimo 3 caracteres</div>
            </div>
            
            <div class="input-group">
                <label>📧 E-mail</label>
                <input type="email" name="email" placeholder="seu@email.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                <div class="hint">Digite um e-mail válido</div>
            </div>
            
            <div class="input-group">
                <label>🔒 Senha</label>
                <input type="password" name="senha" id="senha" placeholder="Mínimo 5 caracteres" required>
                <div class="password-strength">
                    <div class="strength-bar" id="strengthBar"></div>
                </div>
                <div class="strength-text" id="strengthText"></div>
            </div>
            
            <div class="input-group">
                <label>🔒 Confirmar senha</label>
                <input type="password" name="confirmar_senha" placeholder="Digite a senha novamente" required>
            </div>
            
            <button type="submit">Criar conta</button>
        </form>
        
        <div class="info">
            Já tem uma conta? <a href="login.php">Fazer login</a>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Medidor de força da senha
        const senhaInput = document.getElementById('senha');
        const strengthBar = document.getElementById('strengthBar');
        const strengthText = document.getElementById('strengthText');
        
        senhaInput.addEventListener('input', function() {
            const senha = this.value;
            let forca = 0;
            
            if (senha.length >= 6) forca += 25;
            if (senha.length >= 8) forca += 25;
            if (/[A-Z]/.test(senha)) forca += 25;
            if (/[0-9]/.test(senha)) forca += 25;
            if (/[^A-Za-z0-9]/.test(senha)) forca += 25;
            
            if (forca > 100) forca = 100;
            
            strengthBar.style.width = forca + '%';
            
            if (forca <= 25) {
                strengthBar.style.background = '#ff4444';
                strengthText.textContent = 'Senha fraca';
                strengthText.style.color = '#ff4444';
            } else if (forca <= 50) {
                strengthBar.style.background = '#ffaa44';
                strengthText.textContent = 'Senha média';
                strengthText.style.color = '#ffaa44';
            } else if (forca <= 75) {
                strengthBar.style.background = '#44ff44';
                strengthText.textContent = 'Senha boa';
                strengthText.style.color = '#44aa44';
            } else {
                strengthBar.style.background = '#00cc00';
                strengthText.textContent = 'Senha forte!';
                strengthText.style.color = '#00cc00';
            }
            
            if (senha.length === 0) {
                strengthText.textContent = '';
            }
        });
    </script>
</body>
</html>