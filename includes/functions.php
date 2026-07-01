<?php
require_once __DIR__ . '/config.php';

function getDataDir(): string {
    $dir = __DIR__ . '/../data';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    return $dir;
}

function getUsersFile(): string {
    return getDataDir() . '/users.json';
}

function readUsers(): array {
    $file = getUsersFile();
    if (!file_exists($file)) {
        file_put_contents($file, json_encode(['users' => []]));
        return [];
    }
    $data = json_decode(file_get_contents($file), true);
    return $data['users'] ?? [];
}

function writeUsers(array $users): void {
    file_put_contents(getUsersFile(), json_encode(['users' => $users], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function findByEmail(string $email): ?array {
    $users = readUsers();
    foreach ($users as $u) {
        if (($u['email'] ?? '') === strtolower($email)) {
            return $u;
        }
    }
    return null;
}

function findByToken(string $token): ?array {
    $users = readUsers();
    foreach ($users as $u) {
        if (($u['confirmationToken'] ?? '') === $token) {
            return $u;
        }
    }
    return null;
}

function createUser(array $data): array {
    $users = readUsers();
    $users[] = $data;
    writeUsers($users);
    return $data;
}

function updateUser(string $id, array $updates): ?array {
    $users = readUsers();
    foreach ($users as &$u) {
        if (($u['id'] ?? '') === $id) {
            $u = array_merge($u, $updates);
            writeUsers($users);
            return $u;
        }
    }
    return null;
}

function verifyRecaptcha(string $token): bool {
    $secret = RECAPTCHA_SECRET_KEY;
    if (str_starts_with($secret, '6LeIxAcT')) {
        return true;
    }
    $response = @file_get_contents('https://www.google.com/recaptcha/api/siteverify', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query(['secret' => $secret, 'response' => $token])
        ]
    ]));
    if ($response) {
        $data = json_decode($response, true);
        return $data['success'] ?? false;
    }
    return false;
}

function sendEmail(array $to, string $subject, string $html): bool {
    if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer')) {
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->Port = SMTP_PORT;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USER;
            $mail->Password = SMTP_PASS;
            if (SMTP_SECURE) {
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            }
            $mail->setFrom(SMTP_FROM, APP_NAME);
            $mail->addAddress($to['email'], $to['name'] ?? '');
            $mail->isHTML(true);
            $mail->CharSet = 'UTF-8';
            $mail->Subject = $subject;
            $mail->Body = $html;
            $mail->send();
            return true;
        } catch (\Exception $e) {
            error_log('Email error: ' . $e->getMessage());
            return false;
        }
    }
    return false;
}

function sendLoginConfirmation(array $user): void {
    $html = '
    <div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif">
        <div style="background:linear-gradient(135deg,#3b82f6,#f59e0b);padding:24px;text-align:center;border-radius:12px 12px 0 0">
            <h1 style="color:#fff;margin:0;font-size:24px">' . APP_NAME . '</h1>
        </div>
        <div style="background:#fff;padding:32px;border:1px solid #e2e8f0;border-top:0;border-radius:0 0 12px 12px">
            <h2 style="color:#0f172a;margin-top:0">Login confirmado</h2>
            <p style="color:#475569;font-size:15px;line-height:1.6">
                Olá <strong>' . ($user['name'] ?? '') . '</strong>,<br><br>
                Seu login na plataforma ' . APP_NAME . ' foi realizado com sucesso usando sua conta Google.<br><br>
                <strong>Detalhes do acesso:</strong><br>
                \u2022 Email: ' . ($user['email'] ?? '') . '<br>
                \u2022 Data: ' . date('d/m/Y H:i:s') . '<br><br>
                Se n\u00e3o foi voc\u00ea, responda a este email imediatamente.
            </p>
            <hr style="border:0;border-top:1px solid #e2e8f0;margin:24px 0">
            <p style="color:#94a3b8;font-size:13px;text-align:center">
                ' . APP_NAME . ' — Plataforma de Educação Financeira
            </p>
        </div>
    </div>';
    sendEmail(
        ['email' => $user['email'], 'name' => $user['name'] ?? ''],
        'Login confirmado - ' . APP_NAME,
        $html
    );
}

function sendConfirmationEmail(array $user, string $token): void {
    $confirmLink = BASE_URL . '/auth/confirm?token=' . $token;
    $tempPassword = $user['username'] . '@T' . bin2hex(random_bytes(3));
    $html = '
    <div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif">
        <div style="background:linear-gradient(135deg,#3b82f6,#f59e0b);padding:24px;text-align:center;border-radius:12px 12px 0 0">
            <h1 style="color:#fff;margin:0;font-size:24px">' . APP_NAME . '</h1>
        </div>
        <div style="background:#fff;padding:32px;border:1px solid #e2e8f0;border-top:0;border-radius:0 0 12px 12px">
            <h2 style="color:#0f172a;margin-top:0">Confirma\u00e7\u00e3o de conta</h2>
            <p style="color:#475569;font-size:15px;line-height:1.6">
                Ol\u00e1!<br><br>
                Uma nova conta foi criada em <strong>' . APP_NAME . '</strong> usando seu endere\u00e7o de e-mail.<br><br>
                Para confirmar sua nova conta, acesse o seguinte endere\u00e7o:<br><br>
                <a href="' . $confirmLink . '" style="color:#3b82f6;font-size:14px">' . $confirmLink . '</a><br><br>
                Na maioria dos programas de E-mail isso deve aparecer como um link azul que voc\u00ea pode simplesmente clicar. Se isto n\u00e3o funcionar, copie e cole este link na barra de endere\u00e7os do seu navegador.<br><br>
                <strong>Senha para acessar o per\u00edodo de "TESTE" no AVA por 7 dias:</strong><br>
                <div style="background:#f8fafc;padding:12px 16px;border-radius:8px;font-family:monospace;font-size:16px;text-align:center;margin:8px 0">' . $tempPassword . '</div><br>
                Lembre-se que seus dados ser\u00e3o exclu\u00eddos em 30 dias, contados do t\u00e9rmino do TESTE no AVA, caso n\u00e3o fa\u00e7a "aquisi\u00e7\u00e3o" do acesso ao AVA real de ESTUDO.<br><br>
                Se precisar de ajuda, contate o administrador do site.<br><br>
                Atenciosamente,<br>
                <strong>Suporte ' . APP_NAME . '</strong>
            </p>
            <hr style="border:0;border-top:1px solid #e2e8f0;margin:24px 0">
            <p style="color:#94a3b8;font-size:13px;text-align:center">
                ' . APP_NAME . ' — Plataforma de Educa\u00e7\u00e3o Financeira
            </p>
        </div>
    </div>';
    sendEmail(
        ['email' => $user['email'], 'name' => $user['name'] ?? ''],
        'Confirma\u00e7\u00e3o de conta - ' . APP_NAME,
        $html
    );
}
