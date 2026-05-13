<?php
// admin/produtos.php - Gerenciar produtos

require_once __DIR__ . '/../config/config.php';

// Verificar se é admin
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$isAdmin = false;
if (isset($_SESSION['perfil'])) {
    $isAdmin = ($_SESSION['perfil'] == 'admin');
} else {
    $stmt = $pdo->prepare("SELECT perfil FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    $isAdmin = ($user && $user['perfil'] == 'admin');
}

if (!$isAdmin) {
    die("⛔ Acesso negado. Apenas administradores.");
}

// Processar formulário
$mensagem = '';
$erro = '';

// Upload de arquivo
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'adicionar') {
        $nome = $_POST['nome'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $preco = str_replace(',', '.', $_POST['preco'] ?? 0);
        $categoria = $_POST['categoria'] ?? 'Software';
        $versao = $_POST['versao'] ?? '1.0';
        $destaque = isset($_POST['destaque']) ? 1 : 0;
        
        // Upload do arquivo
        $arquivo_nome = '';
        if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] == 0) {
            $ext = pathinfo($_FILES['arquivo']['name'], PATHINFO_EXTENSION);
            $arquivo_nome = 'produto_' . time() . '_' . rand(100, 999) . '.' . $ext;
            $caminho = __DIR__ . '/../download_system/arquivos/' . $arquivo_nome;
            
            if (move_uploaded_file($_FILES['arquivo']['tmp_name'], $caminho)) {
                $tamanho = round($_FILES['arquivo']['size'] / 1048576, 1) . ' MB';
            } else {
                $erro = "Erro ao fazer upload do arquivo.";
            }
        }
        
        // Upload da imagem
        $imagem_base64 = '';
        if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] == 0) {
            $img_data = file_get_contents($_FILES['imagem']['tmp_name']);
            $imagem_base64 = 'data:image/' . pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION) . ';base64,' . base64_encode($img_data);
        } else {
            $imagem_base64 = '🖥️'; // Emoji padrão
        }
        
        if (!$erro && $arquivo_nome) {
            $stmt = $pdo->prepare("INSERT INTO produtos (nome, descricao, preco, arquivo, tamanho, versao, categoria, destaque, imagem) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nome, $descricao, $preco, $arquivo_nome, $tamanho, $versao, $categoria, $destaque, $imagem_base64]);
            $mensagem = "✅ Produto adicionado com sucesso!";
        }
    }
    
    // Editar produto
    if ($_POST['action'] == 'editar') {
        $id = $_POST['id'];
        $nome = $_POST['nome'] ?? '';
        $descricao = $_POST['descricao'] ?? '';
        $preco = str_replace(',', '.', $_POST['preco'] ?? 0);
        $categoria = $_POST['categoria'] ?? 'Software';
        $versao = $_POST['versao'] ?? '1.0';
        $destaque = isset($_POST['destaque']) ? 1 : 0;
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        
        $stmt = $pdo->prepare("UPDATE produtos SET nome=?, descricao=?, preco=?, categoria=?, versao=?, destaque=?, ativo=? WHERE id=?");
        $stmt->execute([$nome, $descricao, $preco, $categoria, $versao, $destaque, $ativo, $id]);
        $mensagem = "✅ Produto atualizado com sucesso!";
    }
    
    // Excluir produto
    if ($_POST['action'] == 'excluir') {
        $id = $_POST['id'];
        $stmt = $pdo->prepare("SELECT arquivo FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        $produto = $stmt->fetch();
        
        if ($produto) {
            $caminho = __DIR__ . '/../download_system/arquivos/' . $produto['arquivo'];
            if (file_exists($caminho)) {
                unlink($caminho);
            }
        }
        
        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
        $stmt->execute([$id]);
        $mensagem = "✅ Produto excluído com sucesso!";
    }
}

// Buscar produtos
$produtos = $pdo->query("SELECT * FROM produtos ORDER BY id DESC")->fetchAll();

// Buscar produto para edição
$edit_produto = null;
if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmt->execute([$_GET['editar']]);
    $edit_produto = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Produtos - CyberCoari</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: bold;
        }
        .btn-primary { background: #667eea; color: white; border: none; cursor: pointer; }
        .btn-success { background: #28a745; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-warning { background: #ffc107; color: #333; }
        
        .card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 500; }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        
        .grid-produtos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .produto-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .produto-card:hover { transform: translateY(-5px); }
        
        .produto-imagem {
            font-size: 80px;
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
        }
        .produto-imagem img { max-width: 100px; max-height: 100px; }
        
        .produto-info { padding: 15px; }
        .produto-nome { font-size: 18px; font-weight: bold; margin-bottom: 5px; }
        .produto-preco { font-size: 22px; color: #667eea; font-weight: bold; }
        .produto-desc { color: #666; font-size: 13px; margin: 10px 0; }
        .produto-meta {
            display: flex;
            gap: 10px;
            font-size: 12px;
            color: #888;
            margin-bottom: 10px;
        }
        
        .acoes {
            display: flex;
            gap: 10px;
            padding: 15px;
            border-top: 1px solid #eee;
        }
        .acoes a { flex: 1; text-align: center; padding: 8px; border-radius: 8px; text-decoration: none; font-size: 14px; }
        
        .alert { padding: 12px; border-radius: 8px; margin-bottom: 20px; }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .status-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-ativo { background: #d4edda; color: #155724; }
        .status-inativo { background: #f8d7da; color: #721c24; }
        
        @media (max-width: 768px) {
            .header { flex-direction: column; gap: 10px; text-align: center; }
            .grid-produtos { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📦 Gerenciar Produtos</h1>
            <div>
                <a href="../dashboard.php" class="btn btn-primary">← Dashboard</a>
                <a href="../download_system/index.php" class="btn btn-success" target="_blank">🌐 Ver Loja</a>
            </div>
        </div>

        <?php if ($mensagem): ?>
        <div class="alert alert-success"><?= $mensagem ?></div>
        <?php endif; ?>
        
        <?php if ($erro): ?>
        <div class="alert alert-error"><?= $erro ?></div>
        <?php endif; ?>

        <!-- Formulário de Adicionar/Editar Produto -->
        <div class="card">
            <h2><?= $edit_produto ? '✏️ Editar Produto' : '➕ Adicionar Novo Produto' ?></h2>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="<?= $edit_produto ? 'editar' : 'adicionar' ?>">
                <?php if ($edit_produto): ?>
                <input type="hidden" name="id" value="<?= $edit_produto['id'] ?>">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>📛 Nome do Produto *</label>
                    <input type="text" name="nome" required value="<?= $edit_produto['nome'] ?? '' ?>">
                </div>
                
                <div class="form-group">
                    <label>📝 Descrição *</label>
                    <textarea name="descricao" rows="3" required><?= $edit_produto['descricao'] ?? '' ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>💰 Preço (R$)</label>
                    <input type="text" name="preco" required value="<?= $edit_produto ? number_format($edit_produto['preco'], 2, ',', '.') : '0,01' ?>">
                </div>
                
                <div class="form-group">
                    <label>📦 Arquivo (ZIP) *</label>
                    <input type="file" name="arquivo" accept=".zip,.rar,.7z" <?= !$edit_produto ? 'required' : '' ?>>
                    <?php if ($edit_produto && $edit_produto['arquivo']): ?>
                    <small>Arquivo atual: <?= $edit_produto['arquivo'] ?> (deixe em branco para manter)</small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>🖼️ Imagem/Ícone (PNG, JPG, GIF)</label>
                    <input type="file" name="imagem" accept="image/*">
                    <small>Deixe em branco para usar emoji padrão</small>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>📌 Versão</label>
                        <input type="text" name="versao" value="<?= $edit_produto['versao'] ?? '1.0' ?>">
                    </div>
                    <div class="form-group">
                        <label>📁 Categoria</label>
                        <select name="categoria">
                            <option value="Software" <?= ($edit_produto['categoria'] ?? '') == 'Software' ? 'selected' : '' ?>>Software</option>
                            <option value="Games" <?= ($edit_produto['categoria'] ?? '') == 'Games' ? 'selected' : '' ?>>Games</option>
                            <option value="Apps" <?= ($edit_produto['categoria'] ?? '') == 'Apps' ? 'selected' : '' ?>>Apps</option>
                            <option value="Bots" <?= ($edit_produto['categoria'] ?? '') == 'Bots' ? 'selected' : '' ?>>Bots</option>
                            <option value="E-commerce" <?= ($edit_produto['categoria'] ?? '') == 'E-commerce' ? 'selected' : '' ?>>E-commerce</option>
                            <option value="Templates" <?= ($edit_produto['categoria'] ?? '') == 'Templates' ? 'selected' : '' ?>>Templates</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="destaque" value="1" <?= ($edit_produto['destaque'] ?? 0) ? 'checked' : '' ?>>
                            ⭐ Produto em destaque
                        </label>
                    </div>
                    <?php if ($edit_produto): ?>
                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="ativo" value="1" <?= ($edit_produto['ativo'] ?? 1) ? 'checked' : '' ?>>
                            ✅ Produto ativo
                        </label>
                    </div>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary">💾 <?= $edit_produto ? 'Atualizar Produto' : 'Adicionar Produto' ?></button>
                <?php if ($edit_produto): ?>
                <a href="produtos.php" class="btn btn-warning">✖️ Cancelar Edição</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Lista de Produtos -->
        <h2>📋 Produtos Cadastrados (<?= count($produtos) ?>)</h2>
        
        <div class="grid-produtos">
            <?php foreach ($produtos as $produto): ?>
            <div class="produto-card">
                <div class="produto-imagem">
                    <?php if (strpos($produto['imagem'] ?? '', 'data:image') === 0): ?>
                        <img src="<?= $produto['imagem'] ?>" alt="<?= htmlspecialchars($produto['nome']) ?>">
                    <?php else: ?>
                        <div style="font-size: 64px;">📦</div>
                    <?php endif; ?>
                </div>
                <div class="produto-info">
                    <div class="produto-nome"><?= htmlspecialchars($produto['nome']) ?></div>
                    <div class="produto-preco">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></div>
                    <div class="produto-desc"><?= htmlspecialchars(substr($produto['descricao'], 0, 80)) ?>...</div>
                    <div class="produto-meta">
                        <span>📦 <?= $produto['tamanho'] ?? 'N/A' ?></span>
                        <span>📌 v<?= $produto['versao'] ?></span>
                        <span>📁 <?= $produto['categoria'] ?></span>
                        <span class="status-badge <?= $produto['ativo'] ? 'status-ativo' : 'status-inativo' ?>">
                            <?= $produto['ativo'] ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </div>
                    <div class="produto-meta">
                        <span>📥 Downloads: <?= number_format($produto['downloads'] ?? 0) ?></span>
                        <span>⭐ <?= $produto['destaque'] ? 'Destaque' : 'Normal' ?></span>
                    </div>
                </div>
                <div class="acoes">
                    <a href="?editar=<?= $produto['id'] ?>" class="btn btn-primary">✏️ Editar</a>
                    <form method="POST" style="flex:1" onsubmit="return confirm('Tem certeza que deseja excluir este produto?')">
                        <input type="hidden" name="action" value="excluir">
                        <input type="hidden" name="id" value="<?= $produto['id'] ?>">
                        <button type="submit" class="btn btn-danger" style="width:100%">🗑️ Excluir</button>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>