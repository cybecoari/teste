<?php
// pagamento/aplicar_afiliado.php

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

$codigo = $_POST['codigo'] ?? $_GET['codigo'] ?? '';

if (empty($codigo)) {
    echo json_encode(['success' => false, 'message' => 'Código de afiliado não informado']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM afiliados WHERE codigo = ? AND status = 'ativo'");
$stmt->execute([$codigo]);
$afiliado = $stmt->fetch();

if ($afiliado) {
    $_SESSION['afiliado_id'] = $afiliado['id'];
    $_SESSION['afiliado_codigo'] = $afiliado['codigo'];
    echo json_encode(['success' => true, 'message' => "Código de afiliado {$codigo} aplicado com sucesso!"]);
} else {
    echo json_encode(['success' => false, 'message' => 'Código de afiliado inválido']);
}
?>