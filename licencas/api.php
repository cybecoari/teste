<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require __DIR__ . "/../config/config.php";

$response = ['success' => false, 'message' => '', 'data' => null];

// Buscar chave
$chave = $_GET['chave'] ?? $_POST['chave'] ?? null;

if (empty($chave)) {
    $response['message'] = 'Chave da licença não fornecida';
    echo json_encode($response);
    exit;
}

$dispositivo = $_GET['dispositivo'] ?? $_POST['dispositivo'] ?? null;
$ip = $_SERVER['REMOTE_ADDR'];

// Buscar licença
$sql = $pdo->prepare("
    SELECT l.*, u.id as usuario_id, u.nome, u.email 
    FROM licencas l 
    JOIN usuarios u ON l.usuario_id = u.id 
    WHERE l.chave = ?
");
$sql->execute([$chave]);
$licenca = $sql->fetch(PDO::FETCH_ASSOC);

if (!$licenca) {
    $response['message'] = 'Licença não encontrada';
    echo json_encode($response);
    exit;
}

if ($licenca['status'] == 'cancelada') {
    $response['message'] = 'Licença cancelada';
    echo json_encode($response);
    exit;
}

if ($licenca['data_expiracao'] && strtotime($licenca['data_expiracao']) < time()) {
    $pdo->prepare("UPDATE licencas SET status = 'expirada' WHERE id = ?")->execute([$licenca['id']]);
    $response['message'] = 'Licença expirada';
    echo json_encode($response);
    exit;
}

if ($licenca['status'] == 'pendente') {
    $pdo->prepare("UPDATE licencas SET status = 'ativa' WHERE id = ?")->execute([$licenca['id']]);
    $licenca['status'] = 'ativa';
}

// Registrar validação
$log = $pdo->prepare("INSERT INTO logs_validacao (licenca_id, ip, data) VALUES (?, ?, NOW())");
$log->execute([$licenca['id'], $ip]);

if ($dispositivo) {
    $pdo->prepare("UPDATE licencas SET dispositivo = ?, ip = ? WHERE id = ?")->execute([$dispositivo, $ip, $licenca['id']]);
}

$dias_restantes = null;
if ($licenca['data_expiracao']) {
    $hoje = new DateTime();
    $expiracao = new DateTime($licenca['data_expiracao']);
    $dias_restantes = $hoje->diff($expiracao)->days;
}

$response = [
    'success' => true,
    'message' => 'Licença válida',
    'data' => [
        'chave' => $licenca['chave'],
        'status' => $licenca['status'],
        'produto' => $licenca['produto'] ?? 'Software',
        'validade' => $licenca['data_expiracao'] ? date('d/m/Y', strtotime($licenca['data_expiracao'])) : 'Vitalício',
        'dias_restantes' => $dias_restantes,
        'usuario' => $licenca['nome'],
        'email' => $licenca['email']
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);