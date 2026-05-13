<?php
// login.php - Versão corrigida (sem tabelas removidas)

require __DIR__ . "/config/config.php";

$erro = "";

// Funções auxiliares (adicionar no config.php ou aqui)
if (!function_exists('verificarBloqueio')) {
    function verificarBloqueio($pdo, $ip) {
        // Sem tabela tentativas_login, sempre retorna false
        return false;
    }
}

if (!function_exists('registrarLog')) {
    function registrarLog($pdo, $login, $sucesso, $ip) {
        // Usa a tabela logs_login que ainda existe
        try {
            $stmt = $pdo->prepare("INSERT INTO logs_login (email, ip, sucesso, data) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$login, $ip, $sucesso ? 1 : 0]);
        } catch (PDOException $e) {
            // Se a tabela não existir, ignora
            error_log("Erro ao registrar log: " . $e->getMessage());
        }
    }
}

if (!function_exists('registrarTentativaFalha')) {
    function registrarTentativaFalha($pdo, $ip, $login) {
        // Tabela tentativas_login foi removida - função vazia
        return false;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $lembrar = isset($_POST['lembrar']);
    $ip = $_SERVER['REMOTE_ADDR'];
    
    if (verificarBloqueio($pdo, $ip)) {
        $erro = "Muitas tentativas. Aguarde " . BLOQUEIO_MINUTOS . " minutos.";
    } else {
        $sql = $pdo->prepare("SELECT * FROM usuarios WHERE email = ? OR nome = ?");
        $sql->execute([$login, $login]);
        $user = $sql->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($senha, $user['senha'])) {
            session_regenerate_id(true);
            
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['nome'] = $user['nome'] ?? $user['email'];
            
            $update = $pdo->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?");
            $update->execute([$user['id']]);
            
            registrarLog($pdo, $login, true, $ip);
            
            // Tabela tentativas_login foi removida - remover esta linha
            // $pdo->prepare("DELETE FROM tentativas_login WHERE ip = ?")->execute([$ip]);
            
            // Verificar se a coluna remember_token existe antes de usar
            if ($lembrar) {
                try {
                    $token = bin2hex(random_bytes(32));
                    $hash_token = password_hash($token, PASSWORD_DEFAULT);
                    
                    // Verificar se a coluna existe
                    $check = $pdo->query("SHOW COLUMNS FROM usuarios LIKE 'remember_token'");
                    if ($check->rowCount() > 0) {
                        $pdo->prepare("UPDATE usuarios SET remember_token = ? WHERE id = ?")
                            ->execute([$hash_token, $user['id']]);
                        setcookie('remember_token', $token, time() + 86400 * 30, '/', '', true, true);
                    }
                } catch (Exception $e) {
                    // Coluna não existe, ignorar
                }
            }
            
            header("Location: dashboard.php");
            exit;
        } else {
            $erro = "Email ou senha inválidos!";
            registrarTentativaFalha($pdo, $ip, $login);
            registrarLog($pdo, $login, false, $ip);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Cyber Coari</title>
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
            max-width: 400px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        }
        h2 { text-align: center; color: #333; margin-bottom: 30px; }
        .input-group { margin-bottom: 20px; }
        .input-group label { display: block; margin-bottom: 8px; font-weight: 500; color: #555; }
        .input-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
        }
        .checkbox-group {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
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
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }
        .erro { background: #fee; color: #c33; padding: 12px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .info { text-align: center; margin-top: 20px; font-size: 14px; }
        .info a { color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔐 Login</h2>
        
        <?php if ($erro): ?>
            <div class="erro">❌ <?= htmlspecialchars($erro) ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="input-group">
                <label>📧 Email ou Nome</label>
                <input type="text" name="login" placeholder="Digite seu email ou nome" required autofocus>
            </div>
            
            <div class="input-group">
                <label>🔒 Senha</label>
                <input type="password" name="senha" placeholder="Digite sua senha" required>
            </div>
            
            <div class="checkbox-group">
                <label>
                    <input type="checkbox" name="lembrar"> Lembrar-me
                </label>
                <a href="senha/recuperar_senha.php">Esqueceu a senha?</a>
            </div>
            
            <button type="submit">Entrar</button>
            
            <div class="info">
                Não tem uma conta? <a href="registro.php">Criar conta</a>
            </div>
        </form>
    </div>
</body>
</html>