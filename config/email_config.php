<?php
// config/email_config.php - Versão completa

// ========== ENVIO DE E-MAIL ==========

function enviarEmail($para, $assunto, $mensagem_html, $mensagem_text = '') {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: CYBERCOARI <suporte@cybercoari.com.br>\r\n";
    $headers .= "Reply-To: suporte@cybercoari.com.br\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    $headers .= "X-Priority: 3\r\n";
    
    return mail($para, $assunto, $mensagem_html, $headers, "-f suporte@cybercoari.com.br");
}

// ========== TEMPLATES ==========

function templateEmail($titulo, $conteudo, $botao_texto = '', $botao_link = '') {
    return '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>' . htmlspecialchars($titulo) . '</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; }
            .header h1 { color: white; margin: 0; font-size: 24px; }
            .content { padding: 30px; line-height: 1.6; color: #333; }
            .button { display: inline-block; padding: 12px 28px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 8px; margin: 20px 0; }
            .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; border-top: 1px solid #eee; }
            @media (max-width: 600px) { .content { padding: 20px; } .button { display: block; text-align: center; } }
        </style>
    </head>
    <body>
        <div style="padding: 20px;">
            <div class="container">
                <div class="header"><h1>🚀 CYBERCOARI</h1></div>
                <div class="content">
                    <h2>' . htmlspecialchars($titulo) . '</h2>
                    ' . $conteudo . '
                    ' . ($botao_texto && $botao_link ? '<div style="text-align: center;"><a href="' . htmlspecialchars($botao_link) . '" class="button">' . htmlspecialchars($botao_texto) . '</a></div>' : '') . '
                </div>
                <div class="footer">
                    <p>© ' . date('Y') . ' CYBERCOARI - Todos os direitos reservados.</p>
                    <p>Este é um email automático, por favor não responda.</p>
                </div>
            </div>
        </div>
    </body>
    </html>';
}

// ========== E-MAILS DO SISTEMA ==========

function emailRecuperacaoSenha($nome, $link) {
    $conteudo = '
        <p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>
        <p>Recebemos uma solicitação para redefinir sua senha.</p>
        <p>Clique no botão abaixo para criar uma nova senha. Este link é válido por <strong>1 hora</strong>.</p>
        <p style="font-size: 12px; color: #666;">Se você não solicitou, ignore este email.</p>
    ';
    return templateEmail('🔐 Recuperação de Senha', $conteudo, 'Redefinir Senha', $link);
}

function emailBoasVindas($nome, $email, $senha_temporaria = null) {
    $conteudo = '
        <p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>
        <p>Seja bem-vindo ao <strong>CYBERCOARI</strong>! Sua conta foi criada com sucesso.</p>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <p><strong>📧 Email:</strong> ' . htmlspecialchars($email) . '</p>
            ' . ($senha_temporaria ? '<p><strong>🔑 Senha temporária:</strong> <code>' . htmlspecialchars($senha_temporaria) . '</code></p>' : '') . '
        </div>
        <p>Recomendamos que você altere sua senha após o primeiro acesso.</p>
    ';
    return templateEmail('🎉 Bem-vindo ao CYBERCOARI', $conteudo, 'Acessar Dashboard', 'https://cybercoari.com.br/dashboard.php');
}

function emailLicencaAtivada($nome, $chave, $produto, $validade) {
    $conteudo = '
        <p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>
        <p>Sua licença foi ativada com sucesso!</p>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <p><strong>🔑 Chave:</strong> <code>' . htmlspecialchars($chave) . '</code></p>
            <p><strong>📦 Produto:</strong> ' . htmlspecialchars($produto) . '</p>
            <p><strong>📅 Validade:</strong> ' . ($validade ? date('d/m/Y', strtotime($validade)) : 'Vitalício') . '</p>
        </div>
        <p>Você pode validar sua licença a qualquer momento em nosso portal.</p>
    ';
    return templateEmail('🎫 Licença Ativada', $conteudo, 'Ver Minhas Licenças', 'https://cybercoari.com.br/licencas/minhas.php');
}

function emailLicencaExpiracao($nome, $produto, $dias, $data_expiracao) {
    $conteudo = '
        <p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>
        <p>Sua licença do produto <strong>' . htmlspecialchars($produto) . '</strong> está expirando em <strong style="color: #dc3545;">' . $dias . ' dias</strong>!</p>
        <p><strong>📅 Data de expiração:</strong> ' . date('d/m/Y', strtotime($data_expiracao)) . '</p>
        <p>Renove sua licença para continuar utilizando o serviço.</p>
    ';
    return templateEmail('⚠️ Licença Próxima do Vencimento', $conteudo, 'Renovar Licença', 'https://cybercoari.com.br/pagamento/checkout.php');
}

function emailSuporte($nome, $email_usuario, $assunto, $mensagem, $prioridade) {
    $conteudo = '
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <p><strong>👤 Usuário:</strong> ' . htmlspecialchars($nome) . '</p>
            <p><strong>📧 Email:</strong> ' . htmlspecialchars($email_usuario) . '</p>
            <p><strong>⚠️ Prioridade:</strong> ' . ucfirst($prioridade) . '</p>
            <p><strong>📅 Data:</strong> ' . date('d/m/Y H:i:s') . '</p>
        </div>
        <div style="background: white; padding: 15px; border-radius: 8px; border-left: 4px solid #667eea;">
            <p><strong>📝 Mensagem:</strong></p>
            <p>' . nl2br(htmlspecialchars($mensagem)) . '</p>
        </div>
    ';
    return templateEmail('📧 Nova Solicitação de Suporte', $conteudo);
}

// ========== NOVOS E-MAILS PARA O SISTEMA ==========

/**
 * E-mail de confirmação de pagamento
 */
function emailPagamentoConfirmado($nome, $produto, $key, $dias, $valor) {
    $conteudo = '
        <p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>
        <p>Seu pagamento de <strong>R$ ' . number_format($valor, 2, ',', '.') . '</strong> foi confirmado com sucesso!</p>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <p><strong>📦 Produto:</strong> ' . htmlspecialchars($produto) . '</p>
            <p><strong>🔑 Sua Chave de Licença:</strong> <code style="font-size: 18px;">' . htmlspecialchars($key) . '</code></p>
            <p><strong>📅 Validade:</strong> ' . ($dias > 0 ? $dias . ' dias' : 'Vitalício') . '</p>
        </div>
        <p>Você pode baixar seu produto diretamente no nosso portal.</p>
        <p><strong>⚠️ Importante:</strong> Guarde sua chave em um local seguro!</p>
    ';
    return templateEmail('✅ Pagamento Confirmado', $conteudo, 'Baixar Produto', 'https://cybercoari.com.br/pagamento/download.php');
}

/**
 * E-mail de pagamento pendente
 */
function emailPagamentoPendente($nome, $produto, $valor, $qr_code_text) {
    $conteudo = '
        <p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>
        <p>Seu pedido foi criado, mas o pagamento ainda não foi confirmado.</p>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <p><strong>📦 Produto:</strong> ' . htmlspecialchars($produto) . '</p>
            <p><strong>💰 Valor:</strong> R$ ' . number_format($valor, 2, ',', '.') . '</p>
            <p><strong>📱 Código PIX:</strong> <code>' . htmlspecialchars($qr_code_text) . '</code></p>
        </div>
        <p>Realize o pagamento para ativar sua licença.</p>
    ';
    return templateEmail('⏳ Pagamento Pendente', $conteudo, 'Ver Pagamento', 'https://cybercoari.com.br/pagamento/checkout.php');
}

/**
 * E-mail de boas-vindas para afiliado
 */
function emailAfiliadoAprovado($nome, $codigo_afiliado, $link_afiliado) {
    $conteudo = '
        <p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>
        <p>Parabéns! Você foi aprovado como afiliado do <strong>CYBERCOARI</strong>.</p>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <p><strong>🔑 Seu código de afiliado:</strong> <code>' . htmlspecialchars($codigo_afiliado) . '</code></p>
            <p><strong>🔗 Seu link de indicação:</strong> <a href="' . htmlspecialchars($link_afiliado) . '">' . htmlspecialchars($link_afiliado) . '</a></p>
            <p><strong>💰 Comissão:</strong> 15% sobre cada venda</p>
        </div>
        <p>Compartilhe seu link e ganhe comissão!</p>
    ';
    return templateEmail('🎉 Você é Afiliado CyberCoari', $conteudo, 'Painel de Afiliado', 'https://cybercoari.com.br/afiliado/dashboard.php');
}

/**
 * E-mail de notificação de venda para afiliado
 */
function emailVendaAfiliado($nome_afiliado, $nome_cliente, $produto, $valor_venda, $comissao) {
    $conteudo = '
        <p>Olá <strong>' . htmlspecialchars($nome_afiliado) . '</strong>,</p>
        <p>Você recebeu uma nova venda através do seu link de afiliado!</p>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <p><strong>👤 Cliente:</strong> ' . htmlspecialchars($nome_cliente) . '</p>
            <p><strong>📦 Produto:</strong> ' . htmlspecialchars($produto) . '</p>
            <p><strong>💰 Valor da venda:</strong> R$ ' . number_format($valor_venda, 2, ',', '.') . '</p>
            <p><strong>💵 Sua comissão:</strong> R$ ' . number_format($comissao, 2, ',', '.') . '</p>
        </div>
        <p>A comissão será creditada em sua conta.</p>
    ';
    return templateEmail('💰 Nova Venda como Afiliado', $conteudo, 'Ver Minhas Vendas', 'https://cybercoari.com.br/afiliado/vendas.php');
}

/**
 * E-mail de confirmação de saque para afiliado
 */
function emailSaqueAfiliado($nome_afiliado, $valor, $metodo, $codigo_saque) {
    $conteudo = '
        <p>Olá <strong>' . htmlspecialchars($nome_afiliado) . '</strong>,</p>
        <p>Seu saque foi processado com sucesso!</p>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0;">
            <p><strong>💰 Valor:</strong> R$ ' . number_format($valor, 2, ',', '.') . '</p>
            <p><strong>💳 Método:</strong> ' . htmlspecialchars($metodo) . '</p>
            <p><strong>🔑 Código:</strong> <code>' . htmlspecialchars($codigo_saque) . '</code></p>
        </div>
        <p>O valor será creditado em até 5 dias úteis.</p>
    ';
    return templateEmail('💸 Saque Processado', $conteudo, 'Ver Extrato', 'https://cybercoari.com.br/afiliado/saldo.php');
}

/**
 * E-mail de cupom de desconto
 */
function emailCupomDesconto($nome, $codigo_cupom, $desconto, $validade = null) {
    $validade_texto = $validade ? '<p><strong>📅 Válido até:</strong> ' . date('d/m/Y', strtotime($validade)) . '</p>' : '';
    $conteudo = '
        <p>Olá <strong>' . htmlspecialchars($nome) . '</strong>,</p>
        <p>Receba um cupom exclusivo de desconto!</p>
        <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center;">
            <p><strong>💸 Desconto:</strong> ' . number_format($desconto, 0) . '% OFF</p>
            <p><strong>🎫 Código:</strong> <code style="font-size: 20px;">' . htmlspecialchars($codigo_cupom) . '</code></p>
            ' . $validade_texto . '
        </div>
        <p>Aproveite essa oferta especial!</p>
    ';
    return templateEmail('🎫 Cupom de Desconto Exclusivo', $conteudo, 'Usar Cupom Agora', 'https://cybercoari.com.br/pagamento/index.php');
}
?>