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

define('UPLOAD_DIR', __DIR__ . '/../uploads');
define('UPLOAD_URL', '/uploads');

$uploadResult = null;
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_image'])) {
    if (!is_dir(UPLOAD_DIR)) { mkdir(UPLOAD_DIR, 0755, true); }
    if (!empty($_FILES['image_file']['tmp_name'])) {
        $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
        if (!in_array($ext, $allowed)) {
            $uploadResult = 'Formato não permitido. Use: ' . implode(', ', $allowed);
        } else {
            $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['image_file']['name']);
            $dest = UPLOAD_DIR . '/' . $filename;
            if (move_uploaded_file($_FILES['image_file']['tmp_name'], $dest)) {
                header('Location: /admin/import#tab-images');
                exit;
            } else {
                $uploadResult = 'Erro ao salvar a imagem.';
            }
        }
    } else {
        $uploadResult = 'Nenhum arquivo enviado.';
    }
}

$deleteImage = $_GET['delete'] ?? '';
if ($loggedIn && $deleteImage && str_starts_with($deleteImage, '/uploads/')) {
    $filePath = __DIR__ . '/..' . $deleteImage;
    $realUploads = realpath(UPLOAD_DIR);
    $realFile = realpath(dirname($filePath));
    if ($realFile === $realUploads && file_exists($filePath)) {
        unlink($filePath);
    }
    header('Location: /admin/import#tab-images');
    exit;
}

$result = null;
if ($loggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['upload_image'])) {
    require_once __DIR__ . '/../includes/functions.php';
    if (!isDB()) {
        $result = ['type' => 'error', 'message' => 'Banco de dados não configurado.'];
    } elseif (isset($_POST['import']) || isset($_FILES['file'])) {
        $moduleMap = [];
        $mods = dbGetModules();
        foreach ($mods as $m) { $moduleMap[preg_replace('/\s+/', '', $m['code'])] = $m['id']; }

        $categoryMap = [];
        $cats = dbGetCategories();
        foreach ($cats as $c) { $categoryMap[strtoupper(preg_replace('/\s+/', '', $c['code']))] = $c['id']; }

        $selectedCategoryId = null;
        $modulePrefix = '';

        $text = '';
        if (!empty($_FILES['file']['tmp_name'])) {
            $raw = file_get_contents($_FILES['file']['tmp_name']);
            if ($raw === false) {
                $result = ['type' => 'error', 'message' => 'Erro ao ler o arquivo enviado.'];
            } else {
                $text = mb_convert_encoding($raw, 'UTF-8', 'UTF-8,ISO-8859-1,WINDOWS-1252');
            }
        } else {
            $text = $_POST['questions'] ?? '';
        }

        if ($text === '') {
            $result = ['type' => 'error', 'message' => 'Nenhum texto ou arquivo enviado.'];
        } else {
            $lines = preg_split('/\r\n|\n|\r/', $text);
            $lines = array_filter($lines, fn($l) => trim($l) !== '');

            $current = [];
            $parsed = [];
            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^ID:\s*(.+)$/i', $line, $m)) {
                    if (!empty($current)) $parsed[] = $current;
                    $current = ['id' => trim($m[1]), 'custom_id' => trim($m[1])];
                } elseif (preg_match('/^(?:CERTIFICACAO|CERTIFICAÇÃO):?\s*(.+)$/i', $line, $m)) {
                    $certName = trim($m[1]);
                    $certCode = strtoupper(preg_replace('/\s+/', '', $certName));
                    if (isset($categoryMap[$certCode])) {
                        $selectedCategoryId = $categoryMap[$certCode];
                    }
                } elseif (preg_match('/^MOD(?:ULO|ULE|UL0):?\s*(.+)$/i', $line, $m)) { $current['module'] = trim($m[1]); }
                elseif (preg_match('/^T(?:O|Ó)PICO:?\s*(.+)$/i', $line, $m)) { $current['topic'] = trim($m[1]); }
                elseif (preg_match('/^P(?:ERGUNTA|ERUNTA|ERGLJNTA):?\s*(.+)$/i', $line, $m)) { $current['question'] = $current['question'] ?? '' . trim($m[1]); }
                elseif (preg_match('/^(?:OP(?:C|Ç|C)(?:O|Õ)ES?|OPCOES|ALTERNATIVAS?):?\s*(.+)$/i', $line, $m)) {
                    if (empty($current['question'])) {
                        $current['question'] = $current['question'] ?? '' . trim($m[1]);
                    } else {
                        $current['raw_options'] = trim($m[1]);
                    }
                } elseif (preg_match('/^RESPOSTA[_ ]CORRETA:?\s*(.+)$/i', $line, $m)) { $current['correct'] = trim($m[1]); }
                elseif (preg_match('/^(?:JUSTIFICATIVA|JUSTIFICATION|JUSTIFIQUE):?\s*(.+)$/i', $line, $m)) { $current['justification'] = $current['justification'] ?? '' . trim($m[1]); }
            }
            if (!empty($current)) $parsed[] = $current;

            $inserted = 0;
            $errors = [];
            $db = getDB();

            $stmt = $db->prepare('INSERT INTO questions (module_id, custom_id, topic, question_text, option_a, option_b, option_c, option_d, correct_answer, justification) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

            $totalParsed = count($parsed);
            foreach ($parsed as $idx => $q) {
                if (empty($q['question'])) { $errors[] = "Q" . ($idx + 1) . ": questão sem texto"; continue; }

                $modCode = preg_replace('/\s+/', '', $q['module'] ?? '');
                $modId = null;
                if ($selectedCategoryId) {
                    $catMods = dbGetModulesByCategory($selectedCategoryId);
                    foreach ($catMods as $cm) {
                        $cmCode = preg_replace('/\s+/', '', $cm['code']);
                        if ($modCode === '' || stripos($modCode, $cmCode) !== false || stripos($cmCode, $modCode) !== false) {
                            $modId = $cm['id']; break;
                        }
                    }
                }
                if (!$modId) {
                    foreach ($moduleMap as $code => $id) {
                        if ($modCode === '' || stripos($modCode, $code) !== false || stripos($code, $modCode) !== false) {
                            $modId = $id; break;
                        }
                    }
                }
                if (!$modId) { $errors[] = "Q" . ($idx + 1) . " (ID: {$q['id']}): módulo não encontrado: {$q['module']}"; continue; }

                $options = $q['raw_options'] ?? '';
                $optA = $optB = $optC = $optD = '';

                if (preg_match_all('/[A-D]\)\s*(.*?)(?=\s*[A-D]\)\s*|$)/u', $options, $matches)) {
                    foreach ($matches[0] as $m) {
                        if (preg_match('/^A\)\s*(.+)$/iu', trim($m), $mm)) $optA = trim($mm[1]);
                        elseif (preg_match('/^B\)\s*(.+)$/iu', trim($m), $mm)) $optB = trim($mm[1]);
                        elseif (preg_match('/^C\)\s*(.+)$/iu', trim($m), $mm)) $optC = trim($mm[1]);
                        elseif (preg_match('/^D\)\s*(.+)$/iu', trim($m), $mm)) $optD = trim($mm[1]);
                    }
                } else {
                    $parts = preg_split('/\s+(?=[A-D]\))/', $options);
                    foreach ($parts as $part) {
                        if (preg_match('/^A\)\s*(.+)$/iu', trim($part), $m)) $optA = trim($m[1]);
                        elseif (preg_match('/^B\)\s*(.+)$/iu', trim($part), $m)) $optB = trim($m[1]);
                        elseif (preg_match('/^C\)\s*(.+)$/iu', trim($part), $m)) $optC = trim($m[1]);
                        elseif (preg_match('/^D\)\s*(.+)$/iu', trim($part), $m)) $optD = trim($m[1]);
                    }
                }

                if (empty($optA) || empty($optB) || empty($q['question'])) {
                    $errors[] = "Q" . ($idx + 1) . " (ID: {$q['id']}): questão ou opções A/B inválidas";
                    continue;
                }

                $correct = trim($q['correct'] ?? '');
                $correctLetter = 'A';
                if (preg_match('/^[A-D]$/i', $correct)) {
                    $correctLetter = strtoupper($correct);
                } else {
                    $optionTexts = ['A' => $optA, 'B' => $optB, 'C' => $optC, 'D' => $optD];
                    $bestScore = 0;
                    $normalizedCorrect = mb_strtolower(trim(preg_replace('/\s+/', ' ', $correct)));
                    foreach ($optionTexts as $letter => $text) {
                        $normalizedText = mb_strtolower(trim(preg_replace('/\s+/', ' ', $text)));
                        if ($normalizedText === $normalizedCorrect) {
                            $correctLetter = $letter; break;
                        }
                        similar_text($normalizedCorrect, $normalizedText, $pct);
                        if ($pct > $bestScore) { $bestScore = $pct; $correctLetter = $letter; }
                    }
                }

                try {
                    $stmt->execute([
                        $modId,
                        $q['custom_id'] ?? null,
                        mb_substr($q['topic'] ?? '', 0, 200),
                        $q['question'],
                        $optA,
                        $optB ?: 'N/A',
                        $optC ?: 'N/A',
                        $optD ?: 'N/A',
                        $correctLetter,
                        $q['justification'] ?? '',
                    ]);
                    $inserted++;
                } catch (\Exception $e) {
                    $errors[] = "Q" . ($idx + 1) . " (ID: {$q['id']}): " . $e->getMessage();
                }
            }

            $updateStmt = $db->prepare('UPDATE modules SET total_questions = (SELECT COUNT(*) FROM questions WHERE module_id = ?) WHERE id = ?');
            foreach ($moduleMap as $code => $mid) {
                $updateStmt->execute([$mid, $mid]);
            }

            $result = [
                'type' => $inserted > 0 ? 'success' : 'error',
                'message' => "$inserted de $totalParsed questões importadas!" . (empty($errors) ? '' : ' (' . count($errors) . ' ignoradas)'),
                'errors' => $errors,
            ];
        }
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
    .import-page textarea { width: 100%; min-height: 300px; padding: 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-family: monospace; font-size: 0.85rem; line-height: 1.6; resize: vertical; }
    .import-page textarea:focus { border-color: var(--accent-1); outline: none; }
    .import-page .btn { padding: 12px 32px; border-radius: 50px; background: var(--primary); color: #fff; border: none; font-size: 1rem; font-weight: 600; cursor: pointer; }
    .import-page .btn:hover { background: var(--accent-1-dark); }
    .import-page .btn-outline { padding: 10px 24px; border-radius: 50px; background: transparent; color: var(--primary); border: 1.5px solid #e2e8f0; font-size: 0.9rem; cursor: pointer; }
    .import-page .btn-outline:hover { border-color: var(--accent-1); }
    .msg { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 0.9rem; }
    .msg.success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
    .msg.error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
    .msg.warning { background: #fffbeb; border: 1px solid #fde68a; color: #d97706; }
    .errors { background: #fef2f2; padding: 12px 16px; border-radius: 8px; margin-top: 12px; max-height: 300px; overflow-y: auto; }
    .errors li { font-size: 0.85rem; color: #dc2626; margin-bottom: 4px; }
    .admin-login { max-width: 360px; margin: 100px auto; text-align: center; }
    .admin-login input[type="password"] { padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 1rem; width: 100%; margin: 12px 0; box-sizing: border-box; }
    .admin-login button { padding: 12px 32px; border-radius: 50px; background: var(--primary); color: #fff; border: none; font-size: 1rem; cursor: pointer; }
    .format-info { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px; margin-bottom: 20px; font-size: 0.85rem; color: #475569; }
    .format-info code { background: #e2e8f0; padding: 1px 4px; border-radius: 3px; font-size: 0.8rem; }
    .tab-bar { display: flex; gap: 0; margin-bottom: 20px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .tab-bar button { flex: 1; padding: 10px; border: none; background: #fff; font-size: 0.9rem; font-weight: 600; cursor: pointer; color: #94a3b8; transition: 0.2s; }
    .tab-bar button.active { background: var(--primary); color: #fff; }
    .tab-bar button:not(.active):hover { background: #f8fafc; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .file-zone { border: 2px dashed #e2e8f0; border-radius: 12px; padding: 40px; text-align: center; transition: 0.2s; cursor: pointer; }
    .file-zone:hover { border-color: var(--accent-1); background: #f8fafc; }
    .file-zone.has-file { border-color: #22c55e; background: #f0fdf4; }
    .file-zone input[type="file"] { display: none; }
    .file-zone .icon { font-size: 2.5rem; margin-bottom: 8px; }
    .file-zone .hint { font-size: 0.85rem; color: #94a3b8; }
    .image-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(200px,1fr)); gap:16px; }
    .image-item { background:#fff; border:1.5px solid #e2e8f0; border-radius:12px; overflow:hidden; transition:0.2s; }
    .image-item:hover { border-color:var(--accent-1); box-shadow:0 4px 12px rgba(0,0,0,0.08); }
    .image-item img { width:100%; height:150px; object-fit:cover; display:block; background:#f8fafc; }
    .image-info { padding:10px 12px; }
    .image-name { display:block; font-size:0.75rem; color:#64748b; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; margin-bottom:4px; }
    .image-url { display:block; font-size:0.7rem; color:var(--accent-1); word-break:break-all; cursor:pointer; padding:4px 6px; background:#f8fafc; border-radius:4px; margin-bottom:4px; }
    .image-url:hover { background:#eff6ff; }
    .image-delete { font-size:0.75rem; color:#ef4444; text-decoration:none; }
    .image-delete:hover { text-decoration:underline; }
    @media (max-width: 600px) { .tab-bar { flex-direction: column; } }
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
        <h1>?? Importar Questões</h1>
        <a href="/admin/import?logout=1" style="color:#94a3b8;font-size:0.9rem">Sair</a>
      </div>

      <div class="format-info">
        <strong>Formato esperado (por questão):</strong><br>
        <code>ID: 02000</code><br>
        <code>MODULO: Economia e finança</code><br>
        <code>TÓPICO: DESCONTO</code><br>
        <code>PERGUNTA: Texto da questão</code><br>
        <code>OPÇÕES: A) texto A B) texto B C) texto C D) texto D</code><br>
        <code>RESPOSTA_CORRETA: Texto da alternativa correta</code><br>
        <code>JUSTIFICATIVA: Texto explicativo</code><br>
        <br>
        <strong>?</strong> Separe cada questão com uma linha em branco.<br>
        <strong>?</strong> Para 4.000+ questões use a aba <strong>Upload de arquivo</strong>.<br>
        <strong>?</strong> Para inserir imagens, use a aba <strong>Imagens</strong> para fazer upload e copiar a URL.<br>
        <strong>?</strong> Use <code>CERTIFICACAO: CPA</code> (ou C Pro-R, C Pro-I, EDUFIN, GESTFIN) no início do arquivo para direcionar as questões a uma certificação específica.
      </div>

      <?php if ($result): ?>
        <div class="msg <?= $result['type'] ?>"><?= htmlspecialchars($result['message']) ?></div>
        <?php if (!empty($result['errors'])): ?>
          <details style="margin-bottom:16px">
            <summary style="cursor:pointer;color:#dc2626;font-size:0.9rem;font-weight:600">? Ver <?= count($result['errors']) ?> erros</summary>
            <div class="errors"><ul><?php foreach ($result['errors'] as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul></div>
          </details>
        <?php endif; ?>
      <?php endif; ?>

      <div class="tab-bar">
        <button class="active" onclick="switchTab('text')" id="tab-text">? Colar texto</button>
        <button onclick="switchTab('file')" id="tab-file">?? Upload de arquivo</button>
        <button onclick="switchTab('images')" id="tab-images">?? Imagens</button>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <div class="tab-content active" id="content-text">
          <textarea name="questions" placeholder="Cole aqui o conteúdo do TXT com as questões..."></textarea>
        </div>
        <div class="tab-content" id="content-file">
          <div class="file-zone" id="file-zone" onclick="document.getElementById('file-input').click()">
            <div class="icon">??</div>
            <div><strong>Clique para selecionar</strong></div>
            <div class="hint">Upload de arquivo .txt (suporta 4.000+ questões)</div>
            <input type="file" id="file-input" name="file" accept=".txt,.csv" onchange="handleFile(event)">
          </div>
          <div id="file-name" style="margin-top:12px;font-size:0.9rem;color:#64748b"></div>
        </div>
        <div class="tab-content" id="content-images">
          <div style="margin-bottom:20px">
            <h3 style="font-size:1.1rem;color:var(--primary);margin-bottom:12px">?? Upload de Imagem</h3>
            <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
              <input type="file" name="image_file" accept="image/*" required style="padding:8px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:0.9rem">
              <button type="submit" name="upload_image" class="btn" style="padding:10px 24px;font-size:0.9rem">?? Enviar</button>
            </div>
            <?php if ($uploadResult): ?>
              <?php if (str_starts_with($uploadResult, 'success:')): 
                $url = substr($uploadResult, 8); ?>
                <div class="msg success" style="margin-top:12px">
                  Imagem enviada! Copie a URL:<br>
                  <code style="display:block;margin-top:6px;padding:8px 12px;background:#fff;border:1px solid #bbf7d0;border-radius:6px;word-break:break-all;cursor:pointer" onclick="copyUrl(this)"><?= htmlspecialchars(BASE_URL . $url) ?></code>
                  <span style="font-size:0.8rem;color:#16a34a;margin-top:4px;display:block">? Clique na URL para copiar</span>
                </div>
              <?php else: ?>
                <div class="msg error" style="margin-top:12px"><?= htmlspecialchars($uploadResult) ?></div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
          <div>
            <h3 style="font-size:1.1rem;color:var(--primary);margin-bottom:12px">?? Imagens Enviadas</h3>
            <?php
            $images = [];
            if (is_dir(UPLOAD_DIR)) {
              $files = scandir(UPLOAD_DIR);
              foreach ($files as $f) {
                if ($f === '.' || $f === '..') continue;
                $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','gif','webp','svg'])) {
                  $mtime = filemtime(UPLOAD_DIR . '/' . $f);
                  $images[] = ['file' => $f, 'url' => UPLOAD_URL . '/' . $f, 'mtime' => $mtime];
                }
              }
              usort($images, fn($a, $b) => $b['mtime'] - $a['mtime']);
            }
            if (empty($images)): ?>
              <p style="color:#94a3b8;font-size:0.9rem">Nenhuma imagem enviada ainda.</p>
            <?php else: ?>
              <div class="image-grid">
                <?php foreach ($images as $img): ?>
                  <div class="image-item">
                    <img src="<?= htmlspecialchars($img['url']) ?>" alt="<?= htmlspecialchars($img['file']) ?>" loading="lazy">
                    <div class="image-info">
                      <span class="image-name" title="<?= htmlspecialchars($img['file']) ?>"><?= htmlspecialchars($img['file']) ?></span>
                      <code class="image-url" onclick="copyUrl(this)"><?= htmlspecialchars(BASE_URL . $img['url']) ?></code>
                      <a href="?delete=<?= urlencode($img['url']) ?>" class="image-delete" onclick="return confirm('Excluir esta imagem?')">Excluir</a>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div style="margin-top:16px;display:flex;gap:12px;align-items:center">
          <button type="submit" name="import" class="btn">?? Importar</button>
          <span style="color:#94a3b8;font-size:0.85rem">As questões serão associadas aos módulos automaticamente.</span>
        </div>
      </form>
    <?php endif; ?>
  </div>

  <script>
    function switchTab(tab) {
      document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
      document.querySelectorAll('.tab-bar button').forEach(el => el.classList.remove('active'));
      document.getElementById('content-' + tab).classList.add('active');
      document.getElementById('tab-' + tab).classList.add('active');
      if (tab === 'images') location.hash = 'tab-images';
      else if (location.hash) history.replaceState(null, '', ' ');
    }
    function handleFile(e) {
      const file = e.target.files[0];
      if (file) {
        document.getElementById('file-name').textContent = '?? ' + file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        document.getElementById('file-zone').classList.add('has-file');
      }
    }
    function copyUrl(el) {
      const text = el.textContent || el.innerText;
      navigator.clipboard.writeText(text).then(() => {
        const orig = el.innerHTML;
        el.innerHTML = '?? Copiado!';
        setTimeout(() => el.innerHTML = orig, 1500);
      });
    }
    if (location.hash === '#tab-images') switchTab('images');
  </script>
</body>
</html>
