<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/config.php';

$loggedIn = !empty($_SESSION['admin_logged']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_pass'])) {
    if ($_POST['admin_pass'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged'] = true;
        $loggedIn = true;
    }
}
if (isset($_GET['logout'])) { unset($_SESSION['admin_logged']); header('Location: /admin/import'); exit; }

$result = null;
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    require_once __DIR__ . '/../includes/functions.php';
    if (!isDB()) {
        $result = ['type' => 'error', 'message' => 'Banco de dados não configurado.'];
    } else {
        $moduleMap = [];
        $mods = dbGetModules();
        foreach ($mods as $m) { $moduleMap[trim($m['code'])] = $m['id']; }

        $text = $_POST['questions'] ?? '';
        $lines = array_filter(explode("\n", str_replace("\r\n", "\n", $text)));

        $current = [];
        $parsed = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/^ID:\s*(.+)$/i', $line, $m)) { if (!empty($current)) $parsed[] = $current; $current = ['id' => trim($m[1])]; }
            elseif (preg_match('/^MODULO:\s*(.+)$/i', $line, $m)) { $current['module'] = trim($m[1]); }
            elseif (preg_match('/^T[OÓ]PICO:\s*(.+)$/i', $line, $m)) { $current['topic'] = trim($m[1]); }
            elseif (preg_match('/^PERGUNTA:\s*(.+)$/i', $line, $m)) { $current['question'] = trim($m[1]); }
            elseif (preg_match('/^(OP[CÇ][OÕ]ES?|OPCOES):\s*(.+)$/i', $line, $m)) { $current['raw_options'] = trim($m[2]); }
            elseif (preg_match('/^RESPOSTA_CORRETA:\s*(.+)$/i', $line, $m)) { $current['correct'] = trim($m[1]); }
            elseif (preg_match('/^JUSTIFICATIVA:\s*(.+)$/i', $line, $m)) { $current['justification'] = trim($m[1]); }
        }
        if (!empty($current)) $parsed[] = $current;

        $inserted = 0;
        $errors = [];
        foreach ($parsed as $q) {
            $modCode = $q['module'] ?? '';
            $modId = null;
            foreach ($moduleMap as $code => $id) {
                if (stripos($modCode, $code) !== false || stripos($code, $modCode) !== false) {
                    $modId = $id; break;
                }
            }
            if (!$modId && !empty($modCode)) {
                foreach ($moduleMap as $code => $id) {
                    if (stripos($modCode, 'M1') !== false && $code === 'M1') { $modId = $id; break; }
                }
            }
            if (!$modId) { $errors[] = 'Módulo não encontrado: ' . $modCode; continue; }

            $options = $q['raw_options'] ?? '';
            $optA = $optB = $optC = $optD = '';
            $parts = preg_split('/\s+(?=[A-D]\))\s*/', $options);
            if (count($parts) < 2) {
                $parts = preg_split('/\s+(?=[A-D]\))/', $options);
            }
            foreach ($parts as $part) {
                if (preg_match('/^A\)\s*(.+)$/i', trim($part), $m)) $optA = trim($m[1]);
                elseif (preg_match('/^B\)\s*(.+)$/i', trim($part), $m)) $optB = trim($m[1]);
                elseif (preg_match('/^C\)\s*(.+)$/i', trim($part), $m)) $optC = trim($m[1]);
                elseif (preg_match('/^D\)\s*(.+)$/i', trim($part), $m)) $optD = trim($m[1]);
            }

            if (empty($optA) || empty($optB) || empty($q['question'])) {
                $errors[] = 'Questão inválida (ID: ' . ($q['id'] ?? '?') . ')'; continue;
            }

            $correct = $q['correct'] ?? '';
            $correctLetter = 'A';
            foreach (['A','B','C','D'] as $l) {
                if (stripos($correct, $l) === 0) { $correctLetter = $l; break; }
            }

            try {
                $ok = dbInsertQuestions([[
                    'module_id' => $modId,
                    'topic' => $q['topic'] ?? '',
                    'question_text' => $q['question'],
                    'option_a' => $optA,
                    'option_b' => $optB ?: 'N/A',
                    'option_c' => $optC ?: 'N/A',
                    'option_d' => $optD ?: 'N/A',
                    'correct_answer' => $correctLetter,
                    'justification' => $q['justification'] ?? '',
                ]]);
                if ($ok) $inserted++;
            } catch (\Exception $e) {
                $errors[] = 'Erro ao inserir: ' . $e->getMessage();
            }
        }

        $result = [
            'type' => $inserted > 0 ? 'success' : 'error',
            'message' => "$inserted questões importadas com sucesso!" . (empty($errors) ? '' : ' (' . count($errors) . ' erros)'),
            'errors' => $errors,
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Importar Questões - Admin</title>
  <link rel="stylesheet" href="/css/style.css">
  <style>
    body { background: #f1f5f9; }
    .import-page { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
    .import-page h1 { font-size: 1.5rem; margin-bottom: 20px; color: var(--primary); }
    .import-page textarea { width: 100%; min-height: 400px; padding: 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-family: monospace; font-size: 0.85rem; line-height: 1.6; resize: vertical; }
    .import-page textarea:focus { border-color: var(--accent-1); outline: none; }
    .import-page .btn { padding: 12px 32px; border-radius: 50px; background: var(--primary); color: #fff; border: none; font-size: 1rem; font-weight: 600; cursor: pointer; }
    .import-page .btn:hover { background: var(--accent-1-dark); }
    .msg { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; }
    .msg.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
    .msg.error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
    .errors { background: #fef2f2; padding: 12px 16px; border-radius: 8px; margin-top: 12px; max-height: 200px; overflow-y: auto; }
    .errors li { font-size: 0.85rem; color: #dc2626; margin-bottom: 4px; }
    .admin-login { max-width: 360px; margin: 100px auto; text-align: center; }
    .admin-login input { padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 1rem; width: 100%; margin: 12px 0; }
    .admin-login button { padding: 12px 32px; border-radius: 50px; background: var(--primary); color: #fff; border: none; font-size: 1rem; cursor: pointer; }
    .format-info { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 20px; font-size: 0.85rem; color: #475569; }
    .format-info code { background: #e2e8f0; padding: 1px 4px; border-radius: 3px; font-size: 0.8rem; }
  </style>
</head>
<body>
  <div class="import-page">
    <?php if (!$loggedIn): ?>
      <div class="admin-login">
        <h1>?? Importar Questões</h1>
        <form method="POST">
          <input type="password" name="admin_pass" placeholder="Senha de administrador" required>
          <button type="submit">Entrar</button>
        </form>
      </div>
    <?php else: ?>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
        <h1>?? Importar Questões (TXT)</h1>
        <a href="/admin/import?logout=1" style="color:#94a3b8;font-size:0.9rem">Sair</a>
      </div>

      <div class="format-info">
        <strong>Formato esperado:</strong><br>
        <code>ID: 02000</code><br>
        <code>MODULO: Economia e finança</code><br>
        <code>TÓPICO: DESCONTO</code><br>
        <code>PERGUNTA: Texto da questão</code><br>
        <code>OPÇÕES: A) texto A B) texto B C) texto C D) texto D</code><br>
        <code>RESPOSTA_CORRETA: A</code><br>
        <code>JUSTIFICATIVA: Texto explicativo</code><br>
      </div>

      <?php if ($result): ?>
        <div class="msg <?= $result['type'] ?>"><?= htmlspecialchars($result['message']) ?></div>
        <?php if (!empty($result['errors'])): ?>
          <div class="errors"><ul><?php foreach (array_slice($result['errors'], 0, 20) as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
      <?php endif; ?>

      <form method="POST">
        <textarea name="questions" placeholder="Cole aqui o conteúdo do arquivo TXT com as questões..."></textarea>
        <div style="margin-top:16px;display:flex;gap:12px;align-items:center">
          <button type="submit" name="import" class="btn">?? Importar</button>
          <span style="color:#94a3b8;font-size:0.85rem">As questões serão associadas aos módulos pelo nome.</span>
        </div>
      </form>
    <?php endif; ?>
  </div>
</body>
</html>
