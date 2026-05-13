<?php
// admin_usuarios.php
require __DIR__ . "/../config/config.php";

// Verificar se está logado e é admin
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Verificar perfil admin
$sql = $pdo->prepare("SELECT perfil FROM usuarios WHERE id = ?");
$sql->execute([$_SESSION['user_id']]);
$user = $sql->fetch(PDO::FETCH_ASSOC);

if (!$user || $user['perfil'] != 'admin') {
    header("Location: dashboard.php");
    exit;
}

$erro = "";
$sucesso = "";
$busca = $_GET['busca'] ?? '';

// Processar ações
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // Criar novo usuário
    if (isset($_POST['criar_usuario'])) {
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';
        $perfil = $_POST['perfil'] ?? 'user';
        
        if (empty($nome) || empty($email) || empty($senha)) {
            $erro = "Preencha todos os campos!";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = "Email inválido!";
        } elseif (strlen($senha) < 6) {
            $erro = "Senha deve ter no mínimo 6 caracteres!";
        } else {
            $check = $pdo->prepare("SELECT id FROM usuarios WHERE email = ?");
            $check->execute([$email]);
            if ($check->rowCount() > 0) {
                $erro = "Email já existe!";
            } else {
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, perfil, ativo, criado_em) VALUES (?, ?, ?, ?, 1, NOW())");
                if ($insert->execute([$nome, $email, $hash, $perfil])) {
                    $sucesso = "✅ Usuário criado com sucesso!";
                } else {
                    $erro = "Erro ao criar usuário!";
                }
            }
        }
    }
    
    // Editar usuário
    if (isset($_POST['editar_usuario'])) {
        $id = $_POST['id'] ?? 0;
        $nome = trim($_POST['nome'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $perfil = $_POST['perfil'] ?? 'user';
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        $update = $pdo->prepare("UPDATE usuarios SET nome = ?, email = ?, perfil = ?, ativo = ? WHERE id = ?");
        if ($update->execute([$nome, $email, $perfil, $ativo, $id])) {
            $sucesso = "✅ Usuário atualizado com sucesso!";
        } else {
            $erro = "Erro ao atualizar usuário!";
        }
    }
    
    // Resetar senha
    if (isset($_POST['resetar_senha'])) {
        $id = $_POST['id'] ?? 0;
        $nova_senha = $_POST['nova_senha'] ?? '123456';
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        
        $update = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE id = ?");
        if ($update->execute([$hash, $id])) {
            $sucesso = "✅ Senha resetada para: $nova_senha";
        } else {
            $erro = "Erro ao resetar senha!";
        }
    }
    
    // Excluir usuário
    if (isset($_POST['excluir_usuario'])) {
        $id = $_POST['id'] ?? 0;
        
        // Não permitir excluir o próprio admin
        if ($id == $_SESSION['user_id']) {
            $erro = "Você não pode excluir sua própria conta!";
        } else {
            $delete = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            if ($delete->execute([$id])) {
                $sucesso = "✅ Usuário excluído com sucesso!";
            } else {
                $erro = "Erro ao excluir usuário!";
            }
        }
    }
}

// Buscar usuários
$query = "SELECT * FROM usuarios";
$params = [];

if (!empty($busca)) {
    $query .= " WHERE nome LIKE ? OR email LIKE ?";
    $params = ["%$busca%", "%$busca%"];
}

$query .= " ORDER BY id DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$usuarios = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Usuários - Cyber Coari</title>
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
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
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
            font-size: 14px;
        }
        
        button.danger {
            background: #dc3545;
        }
        
        button.warning {
            background: #ffc107;
            color: #333;
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
        
        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-ativo {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inativo {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-admin {
            background: #dc3545;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
        }
        
        .badge-user {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
        }
        
        .acoes button {
            padding: 5px 10px;
            margin: 2px;
            font-size: 12px;
        }
        
        .busca {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .busca input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
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
        
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            
            th, td {
                font-size: 12px;
                padding: 8px;
            }
            
            .acoes button {
                font-size: 10px;
                padding: 3px 6px;
            }
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            padding: 25px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
        }
        
        .modal-content h3 {
            margin-bottom: 15px;
        }
        
        .modal-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-voltar {
            background: #6c757d;
            text-decoration: none;
            padding: 8px 15px;
            border-radius: 5px;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>👥 Gerenciar Usuários</h2>
            <a href="dashboard.php" class="logout">← Voltar ao Dashboard</a>
        </div>
        
        <?php if ($erro): ?>
            <div class="erro">❌ <?= $erro ?></div>
        <?php endif; ?>
        
        <?php if ($sucesso): ?>
            <div class="sucesso">✅ <?= $sucesso ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h3>➕ Criar Novo Usuário</h3>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nome</label>
                        <input type="text" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Senha</label>
                        <input type="password" name="senha" placeholder="Mínimo 6 caracteres" required>
                    </div>
                    <div class="form-group">
                        <label>Perfil</label>
                        <select name="perfil">
                            <option value="user">Usuário Comum</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="criar_usuario">🚀 Criar Usuário</button>
            </form>
        </div>
        
        <div class="card">
            <h3>📋 Lista de Usuários</h3>
            
            <div class="busca">
                <form method="GET" style="display: flex; gap: 10px; width: 100%;">
                    <input type="text" name="busca" placeholder="Buscar por nome ou email..." value="<?= htmlspecialchars($busca) ?>">
                    <button type="submit">🔍 Buscar</button>
                    <?php if ($busca): ?>
                        <a href="admin_usuarios.php" style="text-decoration: none; background: #6c757d; color: white; padding: 10px 20px; border-radius: 5px;">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>
            
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nome</th>
                            <th>Email</th>
                            <th>Perfil</th>
                            <th>Status</th>
                            <th>Criado em</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $user): ?>
                            <tr id="user-<?= $user['id'] ?>">
                                <td><?= $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['nome'] ?? '') ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="<?= $user['perfil'] == 'admin' ? 'badge-admin' : 'badge-user' ?>">
                                        <?= $user['perfil'] == 'admin' ? '👑 Admin' : '👤 User' ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?= $user['ativo'] == 1 ? 'status-ativo' : 'status-inativo' ?>">
                                        <?= $user['ativo'] == 1 ? 'Ativo' : 'Inativo' ?>
                                    </span>
                                </td>
                                <td><?= date('d/m/Y', strtotime($user['criado_em'])) ?></td>
                                <td class="acoes">
                                    <button class="warning" onclick="editarUsuario(<?= htmlspecialchars(json_encode($user)) ?>)">✏️</button>
                                    <button class="warning" onclick="resetarSenha(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nome'] ?? $user['email']) ?>')">🔑</button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <button class="danger" onclick="excluirUsuario(<?= $user['id'] ?>, '<?= htmlspecialchars($user['nome'] ?? $user['email']) ?>')">🗑️</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Modal Editar -->
    <div id="modalEditar" class="modal">
        <div class="modal-content">
            <h3>✏️ Editar Usuário</h3>
            <form method="POST" id="formEditar">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label>Nome</label>
                    <input type="text" name="nome" id="edit_nome" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" required>
                </div>
                <div class="form-group">
                    <label>Perfil</label>
                    <select name="perfil" id="edit_perfil">
                        <option value="user">Usuário Comum</option>
                        <option value="admin">Administrador</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="ativo" id="edit_ativo"> Usuário Ativo
                    </label>
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="editar_usuario">💾 Salvar</button>
                    <button type="button" onclick="fecharModal('modalEditar')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Resetar Senha -->
    <div id="modalResetar" class="modal">
        <div class="modal-content">
            <h3>🔑 Resetar Senha</h3>
            <form method="POST">
                <input type="hidden" name="id" id="reset_id">
                <p>Usuário: <strong id="reset_nome"></strong></p>
                <div class="form-group">
                    <label>Nova senha</label>
                    <input type="text" name="nova_senha" value="123456" required>
                    <small>Senha padrão: 123456</small>
                </div>
                <div class="modal-buttons">
                    <button type="submit" name="resetar_senha">🔄 Resetar</button>
                    <button type="button" onclick="fecharModal('modalResetar')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Modal Excluir -->
    <div id="modalExcluir" class="modal">
        <div class="modal-content">
            <h3>🗑️ Excluir Usuário</h3>
            <form method="POST">
                <input type="hidden" name="id" id="delete_id">
                <p>Tem certeza que deseja excluir o usuário <strong id="delete_nome"></strong>?</p>
                <p style="color: red; font-size: 12px;">Esta ação não pode ser desfeita!</p>
                <div class="modal-buttons">
                    <button type="submit" name="excluir_usuario" class="danger">🗑️ Excluir</button>
                    <button type="button" onclick="fecharModal('modalExcluir')">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function editarUsuario(user) {
            document.getElementById('edit_id').value = user.id;
            document.getElementById('edit_nome').value = user.nome || '';
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_perfil').value = user.perfil || 'user';
            document.getElementById('edit_ativo').checked = user.ativo == 1;
            document.getElementById('modalEditar').style.display = 'flex';
        }
        
        function resetarSenha(id, nome) {
            document.getElementById('reset_id').value = id;
            document.getElementById('reset_nome').innerHTML = nome;
            document.getElementById('modalResetar').style.display = 'flex';
        }
        
        function excluirUsuario(id, nome) {
            document.getElementById('delete_id').value = id;
            document.getElementById('delete_nome').innerHTML = nome;
            document.getElementById('modalExcluir').style.display = 'flex';
        }
        
        function fecharModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>