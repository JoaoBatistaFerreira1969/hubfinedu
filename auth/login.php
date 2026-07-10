<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Email e senha são obrigatórios.']);
    exit;
}

$user = findByEmail($email);

if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Email ou senha incorretos.']);
    exit;
}

if (!($user['confirmed'] ?? false)) {
    http_response_code(401);
    echo json_encode(['error' => 'Conta não confirmada. Verifique seu email.']);
    exit;
}

if (!password_verify($password, $user['password'] ?? '')) {
    http_response_code(401);
    echo json_encode(['error' => 'Email ou senha incorretos.']);
    exit;
}

$trialEnd = $user['trialEndsAt'] ?? null;
if ($trialEnd && new DateTime() > new DateTime($trialEnd)) {
    http_response_code(401);
    echo json_encode(['error' => 'Período de teste de 7 dias expirado. Adquira o acesso.']);
    exit;
}

loginUser([
    'id' => $user['id'],
    'name' => $user['name'] ?? '',
    'email' => $user['email'],
    'photo' => '',
    'provider' => 'local',
]);

// Garantir que usuário tenha acesso às certificações
if (isDB()) {
    $userCats = dbGetUserCategories($user['id']);
    if (empty($userCats)) {
        dbGrantAllCategories($user['id']);
    }
}

echo json_encode(['success' => true, 'redirect' => '/dashboard']);
