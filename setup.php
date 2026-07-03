<?php
require_once 'includes/config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <title>Setup - HuB Finedu</title>
  <style>
    body { font-family: monospace; background: #0f172a; color: #e2e8f0; padding: 40px; max-width: 700px; margin: 0 auto; }
    h1 { color: #f59e0b; }
    .ok { color: #22c55e; }
    .erro { color: #ef4444; }
    .info { color: #94a3b8; }
    code { background: #1e293b; padding: 2px 6px; border-radius: 4px; }
  </style>
</head>
<body>
  <h1>??? Setup HuB Finedu</h1>
  <hr style="border-color:#334155">

<?php
if (DB_NAME === '') {
    echo '<p class="erro">Configure DB_HOST, DB_NAME, DB_USER e DB_PASS em <code>includes/config.php</code> primeiro.</p>';
    echo '<p class="info">Acesse o hPanel da Hostinger > MySQL Databases para criar o banco.</p>';
    exit;
}

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p class='ok'>? Banco <strong>" . DB_NAME . "</strong> criado (ou ja existe).</p>";

    $pdo->exec("USE `" . DB_NAME . "`");

    $schema = file_get_contents(__DIR__ . '/database/schema.sql');
    $statements = array_filter(array_map('trim', explode(';', $schema)));

    $count = 0;
    foreach ($statements as $sql) {
        if (str_starts_with($sql, 'INSERT') || str_starts_with($sql, 'CREATE') || str_starts_with($sql, 'ALTER')) {
            try {
                $pdo->exec($sql);
                $count++;
            } catch (PDOException $e) {
                if (str_contains($e->getMessage(), 'Duplicate')) {
                    echo "<p class='info'>? Tabela ja existe (ignorado)</p>";
                } else {
                    throw $e;
                }
            }
        }
    }
    echo "<p class='ok'>? $count comandos executados com sucesso.</p>";
    echo "<p class='ok'>? Tabelas criadas: usuarios, modulos, questoes, tentativas, progresso</p>";

    $stmt = $pdo->query("SELECT COUNT(*) as total FROM modules");
    $mods = $stmt->fetch()['total'];
    echo "<p class='ok'>? $mods modulos inseridos (M1 a M7 + Simulado)</p>";

    echo "<hr style='border-color:#334155'>";
    echo "<p class='ok' style='font-size:1.2rem'>? Setup concluido com sucesso!</p>";
    echo '<p>Remova ou proteja este arquivo <code>setup.php</code> apos o uso.</p>';

} catch (PDOException $e) {
    echo "<p class='erro'>? Erro: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo '<p class="info">Verifique as credenciais em <code>includes/config.php</code>.</p>';
}
?>
  <p><a href="/" style="color:#3b82f6">Voltar ao inicio</a></p>
</body>
</html>
