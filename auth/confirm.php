<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/functions.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    echo '<h2>Token de confirmação não fornecido.</h2>';
    exit;
}

$user = findByToken($token);

if (!$user) {
    echo '<h2>Token inválido ou expirado.</h2>';
    exit;
}

if ($user['confirmed']) {
    echo '<h2>Conta já confirmada. Faça login.</h2><a href="/login">Ir para Login</a>';
    exit;
}

updateUser($user['id'], ['confirmed' => true, 'confirmationToken' => null]);

echo '
<h2>Conta confirmada com sucesso!</h2>
<p>Sua senha de teste foi enviada no email de confirmação.</p>
<p>Você tem 7 dias de teste no AVA.</p>
<a href="/login">Ir para Login</a>
';
