<?php
// pagamento/index.php - Página pública de produtos

require_once __DIR__ . '/config/config.php';

$produtos = [
    1 => ['nome' => '📦 Software CyberCoari Pro', 'descricao' => 'Sistema completo de gestão', 'preco' => 0.01, 'imagem' => '🖥️'],
    2 => ['nome' => '🎮 Template Game Launcher', 'descricao' => 'Launcher para jogos', 'preco' => 49.90, 'imagem' => '🎮'],
    3 => ['nome' => '📱 App Mobile', 'descricao' => 'App híbrido Android/iOS', 'preco' => 99.90, 'imagem' => '📱'],
    4 => ['nome' => '💼 Sistema ERP', 'descricao' => 'ERP completo', 'preco' => 199.90, 'imagem' => '💼'],
];

$mensagem_sucesso = '';
if (isset($_GET['payment_success']) && isset($_SESSION['download_arquivo'])) {
    $mensagem_sucesso = '✅ Pagamento confirmado! Seu download está disponível.';
}

if (isset($_GET['clear'])) {
    unset($_SESSION['download_token'], $_SESSION['download_arquivo'], $_SESSION['produto_id'], $_SESSION['payment_id'], $_SESSION['external_reference']);
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CyberCoari Store</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        .header { text-align: center; color: white; margin-bottom: 40px; padding: 40px 20px; }
        .header h1 { font-size: 48px; margin-bottom: 10px; }
        .header p { font-size: 18px; opacity: 0.9; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 40px; }
        .product-card {
            background: white; border-radius: 20px; padding: 25px; text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease; cursor: pointer;
        }
        .product-card:hover { transform: translateY(-10px); box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
        .product-icon { font-size: 64px; margin-bottom: 15px; }
        .product-name { font-size: 20px; font-weight: bold; color: #333; margin-bottom: 10px; }
        .product-desc { color: #666; font-size: 14px; margin-bottom: 15px; }
        .product-price { font-size: 28px; color: #667eea; font-weight: bold; margin-bottom: 15px; }
        .btn-comprar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; border: none; padding: 12px 24px; border-radius: 10px;
            font-size: 16px; cursor: pointer; width: 100%;
        }
        .download-section { background: white; border-radius: 20px; padding: 30px; text-align: center; margin-top: 30px; }
        .btn-download { background: #28a745; color: white; padding: 15px 40px; border-radius: 10px; text-decoration: none; display: inline-block; margin-top: 15px; }
        .btn-download:hover { background: #218838; }
        .alert-success { background: #d4edda; color: #155724; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
        .footer { text-align: center; color: white; margin-top: 40px; padding: 20px; }
        @media (max-width: 768px) { .header h1 { font-size: 32px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎯 CyberCoari Store</h1>
            <p>Adquira seus produtos e faça o download imediato após o pagamento</p>
        </div>

        <?php if ($mensagem_sucesso): ?>
        <div class="alert-success"><?= h($mensagem_sucesso) ?></div>
        <?php endif; ?>

        <div class="products-grid">
            <?php foreach ($produtos as $id => $produto): ?>
            <div class="product-card" onclick="comprarProduto(<?= $id ?>)">
                <div class="product-icon"><?= $produto['imagem'] ?></div>
                <div class="product-name"><?= h($produto['nome']) ?></div>
                <div class="product-desc"><?= h($produto['descricao']) ?></div>
                <div class="product-price">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></div>
                <button class="btn-comprar" onclick="event.stopPropagation(); comprarProduto(<?= $id ?>)">🛒 Comprar Agora</button>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (isset($_SESSION['download_arquivo'])): ?>
        <div class="download-section">
            <h2>✅ Download Disponível!</h2>
            <p>Clique no botão abaixo para baixar seu arquivo.</p>
            <a href="download.php" class="btn-download">📥 Fazer Download Agora</a>
            <p style="margin-top: 15px;"><a href="?clear=1" style="color: #666;">Limpar e comprar outro produto</a></p>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>🔒 Pagamento 100% seguro via Mercado Pago • Suporte 24h</p>
        </div>
    </div>
    <script> function comprarProduto(id) { window.location.href = 'checkout.php?produto_id=' + id; } </script>
</body>
</html>