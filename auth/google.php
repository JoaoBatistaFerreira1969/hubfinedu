<?php
require_once __DIR__ . '/../includes/config.php';

$params = http_build_query([
    'client_id' => GOOGLE_CLIENT_ID,
    'redirect_uri' => GOOGLE_REDIRECT_URI,
    'response_type' => 'code',
    'scope' => 'openid profile email',
    'access_type' => 'online',
    'prompt' => 'select_account',
]);

header('Location: https://accounts.google.com/o/oauth2/auth?' . $params);
exit;
