<?php
// pagamento/index.php - Com personalização

require_once __DIR__ . '/../config/config.php';

// ========== CONFIGURAÇÕES PERSONALIZÁVEIS ==========
$SITE_CONFIG = [
    'nome' => 'CyberCoari Store',
    'logo' => '🚀',
    'cor_primaria' => '#667eea',
    'cor_secundaria' => '#764ba2',
    'cor_sucesso' => '#28a745',
    'favicon' => 'https://cybercoari.com.br/favicon.ico',
    'whatsapp' => '5597981187297',
    'email_suporte' => 'suporte@cybercoari.com.br'
];

$produtos = [
    1 => ['nome' => '📦 Software CyberCoari Pro', 'descricao' => 'Sistema completo de gestão', 'preco' => 0.01, 'arquivo' => 'cybercoari_pro.zip', 'imagem' => '🖥️', 'destaque' => true],
    2 => ['nome' => '🎮 Template Game Launcher', 'descricao' => 'Launcher para jogos', 'preco' => 49.90, 'arquivo' => 'game_launcher.zip', 'imagem' => '🎮', 'destaque' => false],
    3 => ['nome' => '📱 App Mobile', 'descricao' => 'App híbrido', 'preco' => 99.90, 'arquivo' => 'mobile_app.zip', 'imagem' => '📱', 'destaque' => false],
    4 => ['nome' => '💼 Sistema ERP', 'descricao' => 'ERP completo', 'preco' => 199.90, 'arquivo' => 'erp_system.zip', 'imagem' => '💼', 'destaque' => true],
];

if (isset($_GET['clear'])) {
    unset($_SESSION['download_token'], $_SESSION['download_arquivo'], $_SESSION['produto_id']);
    header("Location: index.php");
    exit;
}
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $SITE_CONFIG['nome'] ?> - Loja Digital</title>
    <link rel="icon" href="<?= $SITE_CONFIG['favicon'] ?>">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, <?= $SITE_CONFIG['cor_primaria'] ?> 0%, <?= $SITE_CONFIG['cor_secundaria'] ?> 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { max-width: 1200px; margin: 0 auto; }
        
        /* Header */
        .header {
            text-align: center;
            color: white;
            margin-bottom: 40px;
            padding: 40px 20px;
        }
        .logo {
            font-size: 64px;
            margin-bottom: 10px;
        }
        .header h1 {
            font-size: 48px;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        /* Produtos */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .product-card {
            background: white;
            border-radius: 20px;
            padding: 25px;
            text-align: center;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            position: relative;
        }
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .product-card.destaque {
            border: 2px solid <?= $SITE_CONFIG['cor_sucesso'] ?>;
        }
        .badge-destaque {
            position: absolute;
            top: -10px;
            right: 20px;
            background: <?= $SITE_CONFIG['cor_sucesso'] ?>;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .product-icon { font-size: 64px; margin-bottom: 15px; }
        .product-name { font-size: 20px; font-weight: bold; color: #333; margin-bottom: 10px; }
        .product-desc { color: #666; font-size: 14px; margin-bottom: 15px; line-height: 1.4; }
        .product-price { font-size: 28px; color: <?= $SITE_CONFIG['cor_primaria'] ?>; font-weight: bold; margin: 15px 0; }
        .btn-comprar {
            background: linear-gradient(135deg, <?= $SITE_CONFIG['cor_primaria'] ?> 0%, <?= $SITE_CONFIG['cor_secundaria'] ?> 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 16px;
            cursor: pointer;
            width: 100%;
            transition: transform 0.2s;
        }
        .btn-comprar:hover { transform: scale(1.02); }
        
        /* Download Section */
        .download-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            text-align: center;
            margin-top: 30px;
        }
        .btn-download {
            background: <?= $SITE_CONFIG['cor_sucesso'] ?>;
            color: white;
            padding: 15px 40px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
            font-weight: bold;
        }
        .btn-download:hover { background: #218838; }
        
        /* Footer */
        .footer {
            text-align: center;
            color: white;
            margin-top: 40px;
            padding: 20px;
            opacity: 0.8;
        }
        .whatsapp-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #25d366;
            color: white;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            text-decoration: none;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            transition: transform 0.3s;
            z-index: 1000;
        }
        .whatsapp-btn:hover { transform: scale(1.1); }
        
        @media (max-width: 768px) {
            .header h1 { font-size: 32px; }
            .products-grid { gap: 15px; }
            .product-card { padding: 15px; }
        }
    </style>
</head>
<body>
    <!-- Botão WhatsApp flutuante -->
    <a href="https://wa.me/<?= $SITE_CONFIG['whatsapp'] ?>" class="whatsapp-btn" target="_blank">💬</a>

    <div class="container">
        <div class="header">
            <div class="logo"><?= $SITE_CONFIG['logo'] ?></div>
            <h1><?= $SITE_CONFIG['nome'] ?></h1>
            <p>Compre e baixe imediatamente após o pagamento</p>
        </div>

        <div class="products-grid">
            <?php foreach ($produtos as $id => $produto): ?>
            <div class="product-card <?= $produto['destaque'] ? 'destaque' : '' ?>" onclick="comprarProduto(<?= $id ?>)">
                <?php if ($produto['destaque']): ?>
                    <div class="badge-destaque">⭐ Destaque</div>
                <?php endif; ?>
                <div class="product-icon"><?= $produto['imagem'] ?></div>
                <div class="product-name"><?= htmlspecialchars($produto['nome']) ?></div>
                <div class="product-desc"><?= htmlspecialchars($produto['descricao']) ?></div>
                <div class="product-price">R$ <?= number_format($produto['preco'], 2, ',', '.') ?></div>
                <button class="btn-comprar" onclick="event.stopPropagation(); comprarProduto(<?= $id ?>)">🛒 Comprar Agora</button>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (isset($_SESSION['download_arquivo'])): ?>
        <div class="download-section">
            <h2>✅ Download Disponível!</h2>
            <p>Seu pagamento foi confirmado. Clique no botão abaixo para baixar.</p>
            <a href="download.php" class="btn-download">📥 Baixar Agora</a>
            <p style="margin-top: 15px;"><a href="?clear=1" style="color: #666;">Comprar outro produto</a></p>
        </div>
        <?php endif; ?>

        <div class="footer">
            <p>🔒 Pagamento 100% seguro via Mercado Pago</p>
            <p>📧 Suporte: <?= $SITE_CONFIG['email_suporte'] ?></p>
        </div>
    </div>

    <script>
        function comprarProduto(id) {
            window.location.href = 'checkout.php?produto_id=' + id;
        }
    </script>
</body>
</html>