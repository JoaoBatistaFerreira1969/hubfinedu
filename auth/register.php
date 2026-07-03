<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$username = trim($input['username'] ?? '');
$password = $input['password'] ?? '';
$email = trim($input['email'] ?? '');
$confirmEmail = trim($input['confirmEmail'] ?? '');
$name = trim($input['name'] ?? '');
$surname = trim($input['surname'] ?? '');
$city = trim($input['city'] ?? '');
$phone = trim($input['phone'] ?? '');
$cpf = trim($input['cpf'] ?? '');
$recaptchaToken = $input['recaptchaToken'] ?? '';

$recaptchaValid = verifyRecaptcha($recaptchaToken);
if (!$recaptchaValid) {
    http_response_code(400);
    echo json_encode(['error' => 'Falha na verificação do reCAPTCHA. Tente novamente.']);
    exit;
}

$errors = [];

if (strlen($username) < 3) $errors[] = 'Usuário deve ter ao menos 3 caracteres';
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/', $password))
    $errors[] = 'Senha deve ter ao menos 8 caracteres, 1 dígito, 1 minúscula, 1 maiúscula e 1 caractere especial';
if (empty($email)) $errors[] = 'Email é obrigatório';
if ($email !== $confirmEmail) $errors[] = 'Emails não conferem';
if (empty($name)) $errors[] = 'Nome é obrigatório';
if (empty($surname)) $errors[] = 'Sobrenome é obrigatório';
if (empty($city)) $errors[] = 'Município/Estado é obrigatório';
if (empty($phone)) $errors[] = 'Telefone é obrigatório';

if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(['error' => implode('; ', $errors)]);
    exit;
}

$existing = findByEmail($email);
if ($existing) {
    http_response_code(409);
    echo json_encode(['error' => 'Este email já está cadastrado']);
    exit;
}

$hashedPassword = password_hash('123456789+', PASSWORD_BCRYPT);
$confirmationToken = bin2hex(random_bytes(16));
$now = new DateTime();

$user = [
    'id' => bin2hex(random_bytes(16)),
    'username' => $username,
    'password' => $hashedPassword,
    'email' => strtolower($email),
    'name' => $name,
    'surname' => $surname,
    'city' => $city,
    'phone' => $phone,
    'cpf' => $cpf,
    'confirmed' => false,
    'confirmationToken' => $confirmationToken,
    'trialEndsAt' => $now->modify('+7 days')->format('c'),
    'expiresAt' => (new DateTime())->modify('+30 days')->format('c'),
    'createdAt' => (new DateTime())->format('c'),
    'provider' => 'local',
];

createUser($user);

try {
    sendConfirmationEmail($user, $confirmationToken);
} catch (\Exception $e) {
    error_log('Erro ao enviar email de confirmação: ' . $e->getMessage());
}

echo json_encode(['success' => true, 'message' => 'Conta criada! Verifique seu email para confirmar o cadastro.']);
