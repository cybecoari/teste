<?php
// senha/recuperar_senha.php - Solicitar recuperação de senha

require __DIR__ . "/../config/db.php";

$erro = "";
$sucesso = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $erro = "Digite seu e-mail!";
    } else {
        $sql = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE email = ?");
        $sql->execute([$email]);
        $user = $sql->fetch();
        
        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expiracao = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            // Usando reset_expira (correto para seu banco)
            $update = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_expira = ? WHERE id = ?");
            $update->execute([$token, $expiracao, $user['id']]);
            
            $link = "https://" . $_SERVER['HTTP_HOST'] . "/senha/redefinir_senha.php?token=" . $token;
            
            $sucesso = "✅ Link de recuperação gerado!<br>";
            $sucesso .= "<strong>Link válido por 1 hora:</strong><br>";
            $sucesso .= "<a href='$link' target='_blank'>$link</a><br>";
            $sucesso .= "<br>💡 <em>Este link seria enviado para seu e-mail.</em>";
        } else {
            $erro = "E-mail não encontrado!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Cyber Coari</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
        h2 { text-align: center; color: #333; margin-bottom: 10px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 30px; font-size: 14px; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
        }
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
        }
        .erro { background: #fee; color: #c33; padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .sucesso { background: #efe; color: #3c3; padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; word-break: break-all; }
        .info { text-align: center; margin-top: 20px; }
        .info a { color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔐 Recuperar Senha</h2>
        <div class="subtitle">Digite seu e-mail para recuperar o acesso</div>
        
        <?php if ($erro): ?>
            <div class="erro">❌ <?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="sucesso"><?= $sucesso ?></div>
        <?php endif; ?>
        
        <?php if (!$sucesso): ?>
        <form method="POST">
            <div class="input-group">
                <label>📧 E-mail</label>
                <input type="email" name="email" placeholder="Digite seu e-mail cadastrado" required autofocus>
            </div>
            <button type="submit">Enviar link de recuperação</button>
        </form>
        <?php endif; ?>
        
        <div class="info">
            <a href="../login.php">← Voltar para o login</a>
        </div>
    </div>
</body>
</html>