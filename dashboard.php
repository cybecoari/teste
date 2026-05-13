<?php
// dashboard.php - Versão corrigida

require __DIR__ . "/config/config.php";

// Verificar login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Buscar dados do usuário
$sql = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
$sql->execute([$_SESSION['user_id']]);
$user = $sql->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit;
}

$isAdmin = ($user['perfil'] == 'admin');
$nome = $user['nome'] ?? $user['email'] ?? 'Usuário';

// Buscar notificações não lidas
$notificacoesNaoLidas = $pdo->prepare("
    SELECT id, usuario_id, tipo, titulo, mensagem, lida, criado_em 
    FROM notificacoes 
    WHERE usuario_id = ? AND lida = 0 
    ORDER BY criado_em DESC 
    LIMIT 10
");
$notificacoesNaoLidas->execute([$_SESSION['user_id']]);
$listaNotificacoes = $notificacoesNaoLidas->fetchAll();
$totalNotificacoes = count($listaNotificacoes);

// Buscar licenças próximas do vencimento (para aviso)
try {
    $licencasProximas = $pdo->prepare("
        SELECT l.*, 
               DATEDIFF(l.data_expiracao, NOW()) as dias_restantes
        FROM licencas l 
        WHERE l.usuario_id = ? 
          AND l.status = 'ativa'
          AND l.data_expiracao IS NOT NULL
          AND DATEDIFF(l.data_expiracao, NOW()) <= 7
          AND DATEDIFF(l.data_expiracao, NOW()) > 0
        ORDER BY dias_restantes ASC
    ");
    $licencasProximas->execute([$_SESSION['user_id']]);
    $licencasProximas = $licencasProximas->fetchAll();
} catch (PDOException $e) {
    $licencasProximas = [];
}

// Buscar estatísticas (apenas se for admin)
if ($isAdmin) {
    try {
        $totalUsuarios = $pdo->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
        $totalLicencas = $pdo->query("SELECT COUNT(*) FROM licencas")->fetchColumn();
        $licencasAtivas = $pdo->query("SELECT COUNT(*) FROM licencas WHERE status = 'ativa'")->fetchColumn();
        $totalPedidos = $pdo->query("SELECT COUNT(*) FROM pedidos_telegram")->fetchColumn();
        $totalKeys = $pdo->query("SELECT COUNT(*) FROM keys_telegram")->fetchColumn();
    } catch (PDOException $e) {
        $totalUsuarios = $totalLicencas = $licencasAtivas = $totalPedidos = $totalKeys = 0;
    }
}

// Marcar notificação como lida
if (isset($_GET['marcar_lida'])) {
    $id = intval($_GET['marcar_lida']);
    $pdo->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND usuario_id = ?")->execute([$id, $_SESSION['user_id']]);
    header("Location: dashboard.php");
    exit;
}

// Marcar todas como lidas
if (isset($_GET['marcar_todas'])) {
    $pdo->prepare("UPDATE notificacoes SET lida = 1 WHERE usuario_id = ?")->execute([$_SESSION['user_id']]);
    header("Location: dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cyber Coari</title>
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
        }
        
        /* Navbar */
        .navbar {
            background: rgba(255,255,255,0.95);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        
        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .user-name {
            color: #333;
            font-weight: 500;
        }
        
        /* Notificações */
        .notificacoes {
            position: relative;
        }
        
        .notificacoes-btn {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            position: relative;
            padding: 5px;
        }
        
        .badge-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 50%;
            font-weight: bold;
        }
        
        .dropdown {
            position: absolute;
            top: 40px;
            right: 0;
            width: 350px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.2);
            display: none;
            z-index: 1000;
        }
        
        .dropdown.active {
            display: block;
        }
        
        .dropdown-header {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .dropdown-header h4 {
            color: #333;
        }
        
        .dropdown-header a {
            font-size: 12px;
            color: #667eea;
            text-decoration: none;
        }
        
        .lista-notificacoes {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .notificacao-item {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .notificacao-item:hover {
            background: #f8f9fa;
        }
        
        .notificacao-item.nao-lida {
            background: #e8f4f8;
            border-left: 3px solid #667eea;
        }
        
        .notificacao-titulo {
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 14px;
            color: #333;
        }
        
        .notificacao-mensagem {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }
        
        .notificacao-data {
            font-size: 10px;
            color: #999;
        }
        
        .dropdown-footer {
            padding: 10px;
            text-align: center;
            border-top: 1px solid #eee;
        }
        
        .dropdown-footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 12px;
        }
        
        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .badge-admin {
            background: #dc3545;
            color: white;
        }
        
        .badge-user {
            background: #28a745;
            color: white;
        }
        
        .logout-btn {
            background: #dc3545;
            color: white;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
            display: inline-block;
        }
        
        .logout-btn:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        /* Container principal */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* Welcome Card */
        .welcome-card {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
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
        
        .welcome-card h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .welcome-card p {
            color: #666;
        }
        
        /* Alertas */
        .alert {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
        }
        
        .alert-warning {
            background: #fff3cd;
            border-left-color: #ffc107;
        }
        
        .alert-danger {
            background: #f8d7da;
            border-left-color: #dc3545;
        }
        
        /* Grid de menus */
        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        .menu-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            display: block;
        }
        
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }
        
        .menu-icon {
            font-size: 48px;
            margin-bottom: 15px;
        }
        
        .menu-card h3 {
            margin-bottom: 10px;
            color: #333;
        }
        
        .menu-card p {
            font-size: 14px;
            color: #666;
        }
        
        /* Estatísticas */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 30px;
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
            color: #666;
            margin-top: 5px;
        }
        
        /* Seção Admin */
        .admin-section {
            margin-top: 30px;
            border-top: 2px solid #e0e0e0;
            padding-top: 30px;
        }
        
        .admin-title {
            color: #dc3545;
            margin-bottom: 20px;
            font-size: 20px;
        }
        
        /* Responsivo */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                text-align: center;
            }
            
            .user-info {
                justify-content: center;
            }
            
            .dropdown {
                width: 300px;
                right: -50px;
            }
            
            .container {
                padding: 20px;
            }
            
            .welcome-card {
                padding: 20px;
            }
            
            .welcome-card h1 {
                font-size: 24px;
            }
            
            .menu-grid {
                gap: 15px;
            }
            
            .menu-card {
                padding: 20px;
            }
            
            .menu-icon {
                font-size: 36px;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .welcome-card h1 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">🚀 Cyber Coari</div>
            <div class="user-info">
                <!-- Notificações -->
                <div class="notificacoes">
                    <button class="notificacoes-btn" onclick="toggleNotificacoes()">
                        🔔
                        <?php if ($totalNotificacoes > 0): ?>
                            <span class="badge-count"><?= $totalNotificacoes ?></span>
                        <?php endif; ?>
                    </button>
                    <div class="dropdown" id="dropdownNotificacoes">
                        <div class="dropdown-header">
                            <h4>🔔 Notificações</h4>
                            <a href="?marcar_todas=1">Marcar todas como lidas</a>
                        </div>
                        <div class="lista-notificacoes">
                            <?php if ($totalNotificacoes > 0): ?>
                                <?php foreach ($listaNotificacoes as $notif): ?>
                                    <div class="notificacao-item nao-lida" onclick="location.href='?marcar_lida=<?= $notif['id'] ?>'">
                                        <div class="notificacao-titulo"><?= htmlspecialchars($notif['titulo']) ?></div>
                                        <div class="notificacao-mensagem"><?= htmlspecialchars(substr($notif['mensagem'], 0, 80)) ?>...</div>
                                        <div class="notificacao-data"><?= date('d/m/Y H:i', strtotime($notif['criado_em'])) ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div style="padding: 30px; text-align: center; color: #999;">
                                    📭 Nenhuma notificação nova
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="dropdown-footer">
                            <a href="notificacoes.php">Ver todas</a>
                        </div>
                    </div>
                </div>
                
                <span class="user-name">👤 <?= htmlspecialchars($nome) ?></span>
                <span class="badge <?= $isAdmin ? 'badge-admin' : 'badge-user' ?>">
                    <?= $isAdmin ? '👑 Administrador' : '📱 Usuário' ?>
                </span>
                <a href="logout.php" class="logout-btn">🚪 Sair</a>
            </div>
        </div>
    </nav>
    
    <!-- Conteúdo principal -->
    <div class="container">
        <div class="welcome-card">
            <h1>🎉 Olá, <?= htmlspecialchars($nome) ?>!</h1>
            <p>Bem-vindo ao seu dashboard. Aqui você pode gerenciar suas licenças e configurações.</p>
        </div>
        
        <!-- Alertas de licenças próximas do vencimento -->
        <?php if (count($licencasProximas) > 0): ?>
            <div class="alert alert-warning">
                <strong>⚠️ Atenção!</strong> Você tem licenças próximas do vencimento:<br>
                <?php foreach ($licencasProximas as $lic): ?>
                    • <strong><?= htmlspecialchars($lic['produto'] ?? 'Software') ?></strong> - Expira em <?= $lic['dias_restantes'] ?> dias (<?= date('d/m/Y', strtotime($lic['data_expiracao'])) ?>)<br>
                <?php endforeach; ?>
                <small>Renove sua licença para não perder o acesso.</small>
            </div>
        <?php endif; ?>
        
        <!-- MENUS COMUNS (para todos os usuários) -->
        <div class="menu-grid">
            <a href="licencas/" class="menu-card">
                <div class="menu-icon">🔐</div>
                <h3>Sistema de Licenças</h3>
                <p>Validar, visualizar e gerenciar licenças</p>
            </a>
            
            <a href="pagamento/checkout.php" class="menu-card">
                <div class="menu-icon">💳</div>
                <h3>Adquirir Licença</h3>
                <p>Compre uma nova licença via Mercado Pago</p>
            </a>
            
            <a href="perfil.php" class="menu-card">
                <div class="menu-icon">👤</div>
                <h3>Meu Perfil</h3>
                <p>Atualize seus dados pessoais</p>
            </a>
        </div>
        
        <!-- MENUS ADMIN (apenas para administradores) -->
        <?php if ($isAdmin): ?>
        <div class="admin-section">
            <h2 class="admin-title">🔧 Área Administrativa</h2>
            <div class="menu-grid">
                <a href="licencas/admin_licencas.php" class="menu-card">
                    <div class="menu-icon">🎫</div>
                    <h3>Gerenciar Licenças</h3>
                    <p>Criar, revogar e gerenciar licenças</p>
                </a>
                
                <a href="admin_usuarios.php" class="menu-card">
                    <div class="menu-icon">👥</div>
                    <h3>Gerenciar Usuários</h3>
                    <p>Visualizar e gerenciar todos os usuários</p>
                </a>
                
                <a href="admin_logs.php" class="menu-card">
                    <div class="menu-icon">📊</div>
                    <h3>Logs do Sistema</h3>
                    <p>Visualizar logs de login e validações</p>
                </a>
                
                <a href="notificacoes_admin.php" class="menu-card">
                    <div class="menu-icon">📧</div>
                    <h3>Enviar Notificações</h3>
                    <p>Enviar alertas e comunicados</p>
                </a>
                
                <a href="pedidos_telegram_lista.php" class="menu-card">
                    <div class="menu-icon">🤖</div>
                    <h3>Pedidos Telegram</h3>
                    <p>Visualizar pedidos do bot</p>
                </a>
                
                <a href="keys_telegram_lista.php" class="menu-card">
                    <div class="menu-icon">🔑</div>
                    <h3>Keys Telegrams</h3>
                    <p>Gerenciar keys geradas</p>
                </a>
            </div>
        </div>
        
        <!-- Estatísticas -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number"><?= $totalUsuarios ?? 0 ?></div>
                <div class="stat-label">Total de Usuários</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalLicencas ?? 0 ?></div>
                <div class="stat-label">Total de Licenças</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $licencasAtivas ?? 0 ?></div>
                <div class="stat-label">Licenças Ativas</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalPedidos ?? 0 ?></div>
                <div class="stat-label">Pedidos Telegram</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalKeys ?? 0 ?></div>
                <div class="stat-label">Keys Geradas</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <script>
        function toggleNotificacoes() {
            const dropdown = document.getElementById('dropdownNotificacoes');
            dropdown.classList.toggle('active');
        }
        
        // Fechar dropdown ao clicar fora
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('dropdownNotificacoes');
            const btn = document.querySelector('.notificacoes-btn');
            if (btn && !btn.contains(event.target) && dropdown && !dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    </script>
</body>
</html>