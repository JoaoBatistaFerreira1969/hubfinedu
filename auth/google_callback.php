<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';

if (!isset($_GET['code'])) {
    header('Location: /login');
    exit;
}

$tokenResponse = @file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'code' => $_GET['code'],
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'grant_type' => 'authorization_code',
        ])
    ]
]));

if (!$tokenResponse) {
    header('Location: /login');
    exit;
}

$tokenData = json_decode($tokenResponse, true);
if (!isset($tokenData['access_token'])) {
    header('Location: /login');
    exit;
}

$userResponse = @file_get_contents('https://www.googleapis.com/oauth2/v2/userinfo', false, stream_context_create([
    'http' => [
        'header' => 'Authorization: Bearer ' . $tokenData['access_token']
    ]
]));

if (!$userResponse) {
    header('Location: /login');
    exit;
}

$googleUser = json_decode($userResponse, true);

$user = [
    'id' => $googleUser['id'],
    'name' => $googleUser['name'],
    'email' => $googleUser['email'] ?? '',
    'photo' => $googleUser['picture'] ?? '',
    'provider' => 'google',
];

loginUser($user);

try {
    sendLoginConfirmation($user);
} catch (\Exception $e) {
    error_log('Erro ao enviar email de confirmação: ' . $e->getMessage());
}

header('Location: /dashboard');
exit;
