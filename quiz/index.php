<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
requireAuth();

$userId = getUser()['id'];

if (isDB()) {
    $modules = dbGetModules();
    $userProgress = [];
    $allProgress = getDB()->prepare('SELECT * FROM user_progress WHERE user_id = ?');
    $allProgress->execute([$userId]);
    foreach ($allProgress->fetchAll() as $p) {
        $userProgress[$p['module_id']] = $p;
    }
    $lastAttempts = [];
    $laStmt = getDB()->prepare('SELECT a1.* FROM quiz_attempts a1 WHERE a1.user_id = ? AND a1.status = ? AND a1.completed_at = (SELECT MAX(a2.completed_at) FROM quiz_attempts a2 WHERE a2.user_id = a1.user_id AND a2.module_id = a1.module_id AND a2.status = ?)');
    $laStmt->execute([$userId, 'completed', 'completed']);
    foreach ($laStmt->fetchAll() as $la) {
        $lastAttempts[$la['module_id']] = (int)$la['id'];
    }
    $totalXp = getUser()['xp'] ?? 0;
    $totalLevel = getUser()['level'] ?? 1;
} else {
    $modules = [];
    $userProgress = [];
    $totalXp = 0;
    $totalLevel = 1;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz - HuB Finedu</title>
  <link rel="stylesheet" href="/css/style.css">
  <style>
    .quiz-page { padding: 40px 20px; max-width: 900px; margin: 0 auto; }
    .quiz-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; flex-wrap: wrap; gap: 12px; }
    .quiz-header h1 { font-size: 1.8rem; color: var(--primary); }
    .xp-badge { background: linear-gradient(135deg,#3b82f6,#f59e0b); color: #fff; padding: 8px 20px; border-radius: 50px; font-weight: 700; font-size: 0.9rem; }
    .module-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 20px; }
    .module-card { background: #fff; border-radius: 16px; padding: 24px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); transition: transform 0.3s; }
    .module-card:hover { transform: translateY(-4px); }
    .module-card h3 { font-size: 1.1rem; color: var(--primary); margin-bottom: 4px; }
    .module-card .code { font-size: 0.8rem; color: #94a3b8; margin-bottom: 8px; }
    .module-card .desc { font-size: 0.85rem; color: #64748b; margin-bottom: 12px; }
    .module-card .stats { display: flex; gap: 16px; font-size: 0.8rem; color: #94a3b8; margin-bottom: 12px; }
    .module-card .stats span { display: flex; align-items: center; gap: 4px; }
    .progress-bar { height: 6px; background: #e2e8f0; border-radius: 3px; margin-bottom: 12px; overflow: hidden; }
    .progress-bar .fill { height: 100%; border-radius: 3px; background: linear-gradient(90deg,#3b82f6,#f59e0b); transition: width 0.5s; }
    .module-card .btn { display: block; text-align: center; padding: 10px; border-radius: 50px; font-weight: 600; font-size: 0.9rem; text-decoration: none; transition: 0.3s; }
    .btn-start { background: var(--primary); color: #fff; }
    .btn-start:hover { background: var(--accent-1-dark, #2563eb); }
    .btn-retry { background: #f8fafc; color: var(--primary); border: 1.5px solid #e2e8f0; }
    .btn-retry:hover { border-color: var(--accent-1); }
    .btn-locked { background: #e2e8f0; color: #94a3b8; cursor: not-allowed; }
    .score-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; }
    .score-high { background: #dcfce7; color: #16a34a; }
    .score-mid { background: #fef9c3; color: #ca8a04; }
    .score-low { background: #fef2f2; color: #dc2626; }
    .empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; }
    .empty-state h2 { font-size: 1.3rem; color: var(--primary); margin-bottom: 8px; }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="container">
      <a href="/" class="logo">HuB <span>Finedu</span></a>
      <ul class="nav-links">
        <li><a href="/dashboard">Dashboard</a></li>
        <li><a href="/quiz" style="color:var(--accent-2)">Quiz</a></li>
        <li><a href="/auth/logout" class="nav-btn">Sair</a></li>
      </ul>
    </div>
  </nav>

  <div class="quiz-page">
    <div class="quiz-header">
      <h1>?? Questionários</h1>
      <span class="xp-badge">?? XP: <?= $totalXp ?> | Nível <?= $totalLevel ?></span>
    </div>

    <?php if (empty($modules)): ?>
      <div class="empty-state">
        <h2>? Nenhum módulo disponível</h2>
        <p>O banco de dados ainda não foi configurado. Acesse /setup para criar as tabelas.</p>
      </div>
    <?php else: ?>
      <p style="color:#64748b;margin-bottom:24px">Selecione um módulo para iniciar. São necessários 50% de acertos para avançar.</p>
      <div class="module-grid">
        <?php foreach ($modules as $mod):
          $progress = $userProgress[$mod['id']] ?? null;
          $bestScore = $progress['best_score'] ?? 0;
          $attemptsUsed = $progress['attempts_used'] ?? 0;
          $maxAttempts = $mod['max_attempts'];
          $isCompleted = $progress['is_completed'] ?? false;
          $isUnlocked = $progress['is_unlocked'] ?? ($mod['order_num'] === 1);
          $canStart = $isUnlocked && $attemptsUsed < $maxAttempts;
          $scoreClass = $bestScore >= 80 ? 'score-high' : ($bestScore >= 50 ? 'score-mid' : 'score-low');
        ?>
          <div class="module-card">
            <h3><?= htmlspecialchars($mod['name']) ?></h3>
            <div class="code"><?= htmlspecialchars($mod['code']) ?></div>
            <div class="desc"><?= htmlspecialchars($mod['description']) ?></div>
            <div class="stats">
              <span>?? <?= $mod['total_questions'] ?> questões</span>
              <span>?? <?= $mod['time_per_question'] ?>s cada</span>
            </div>
            <div class="progress-bar"><div class="fill" style="width:<?= $bestScore ?>%"></div></div>
            <div style="display:flex;justify-content:space-between;font-size:0.8rem;color:#64748b;margin-bottom:12px">
              <span>Melhor: <span class="score-badge <?= $scoreClass ?>"><?= number_format($bestScore, 1) ?>%</span></span>
              <span>Tentativas: <?= $attemptsUsed ?>/<?= $maxAttempts ?></span>
            </div>
            <?php if ($isCompleted): ?>
              <a href="/quiz/result?attempt=<?= $lastAttempts[$mod['id']] ?? 0 ?>" class="btn btn-retry">?? Ver resultados</a>
            <?php elseif ($canStart): ?>
              <a href="/quiz/take?module=<?= $mod['id'] ?>" class="btn btn-start">? Iniciar</a>
            <?php else: ?>
              <span class="btn btn-locked">?? Complete o módulo anterior</span>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>
