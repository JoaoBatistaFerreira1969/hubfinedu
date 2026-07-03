<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
requireAuth();

if (!isDB()) { echo '<h2>Banco não configurado.</h2>'; exit; }

$attemptId = (int)($_GET['attempt'] ?? 0);
$attempt = dbGetAttempt($attemptId);
if (!$attempt || $attempt['user_id'] !== getUser()['id']) {
    echo '<h2>Tentativa não encontrada.</h2><a href="/quiz">Voltar</a>'; exit;
}

$module = dbGetModule((int)$attempt['module_id']);
$answers = getDB()->prepare('SELECT qa.*, q.question_text, q.correct_answer, q.justification, q.option_a, q.option_b, q.option_c, q.option_d FROM quiz_answers qa JOIN questions q ON qa.question_id = q.id WHERE qa.attempt_id = ?');
$answers->execute([$attemptId]);
$allAnswers = $answers->fetchAll();

$passed = $attempt['score'] >= 50;
$userProgress = dbGetUserProgress(getUser()['id'], (int)$attempt['module_id']);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resultado - <?= htmlspecialchars($module['name'] ?? '') ?> - HuB Finedu</title>
  <link rel="stylesheet" href="/css/style.css">
  <style>
    .result-page { max-width: 800px; margin: 0 auto; padding: 40px 20px; }
    .result-card { background: #fff; border-radius: 16px; padding: 40px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.08); margin-bottom: 32px; }
    .result-card .big-score { font-size: 3.5rem; font-weight: 800; margin: 16px 0; }
    .result-card .big-score.passed { color: #16a34a; }
    .result-card .big-score.failed { color: #dc2626; }
    .result-card .status { font-size: 1.2rem; font-weight: 700; margin-bottom: 8px; }
    .result-card .status.passed { color: #16a34a; }
    .result-card .status.failed { color: #dc2626; }
    .result-card .stats { display: flex; justify-content: center; gap: 32px; margin: 20px 0; flex-wrap: wrap; }
    .result-card .stats div { text-align: center; }
    .result-card .stats .num { font-size: 1.5rem; font-weight: 700; color: var(--primary); }
    .result-card .stats .label { font-size: 0.85rem; color: #94a3b8; }
    .result-actions { display: flex; gap: 12px; justify-content: center; flex-wrap: wrap; }
    .result-actions .btn { padding: 12px 32px; border-radius: 50px; font-weight: 600; text-decoration: none; transition: 0.2s; }
    .btn-back { background: var(--primary); color: #fff; }
    .btn-back:hover { background: var(--accent-1-dark); }
    .btn-retry { background: #f8fafc; color: var(--primary); border: 1.5px solid #e2e8f0; }
    .btn-retry:hover { border-color: var(--accent-1); }
    .review-section { margin-top: 32px; }
    .review-section h2 { font-size: 1.3rem; margin-bottom: 20px; color: var(--primary); }
    .review-item { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
    .review-item .q { color: var(--primary); font-weight: 500; margin-bottom: 12px; }
    .review-item .your-answer { font-size: 0.9rem; margin-bottom: 4px; }
    .review-item .correct { color: #16a34a; font-weight: 600; }
    .review-item .wrong { color: #dc2626; font-weight: 600; }
    .review-item .justification { background: #f8fafc; border-radius: 8px; padding: 12px 16px; margin-top: 8px; font-size: 0.9rem; color: #475569; line-height: 1.6; }
  </style>
</head>
<body>
  <div class="result-page">
    <div class="result-card">
      <div class="status <?= $passed ? 'passed' : 'failed' ?>"><?= $passed ? '?? Parabéns!' : '?? Tente novamente' ?></div>
      <div class="big-score <?= $passed ? 'passed' : 'failed' ?>"><?= number_format($attempt['score'], 1) ?>%</div>
      <div class="stats">
        <div><div class="num"><?= $attempt['correct_answers'] ?></div><div class="label">Acertos</div></div>
        <div><div class="num"><?= ($attempt['total_questions'] ?? 0) - ($attempt['correct_answers'] ?? 0) ?></div><div class="label">Erros</div></div>
        <div><div class="num"><?= $attempt['total_questions'] ?? 0 ?></div><div class="label">Total</div></div>
        <div><div class="num"><?= gmdate('i:s', (int)$attempt['time_spent']) ?></div><div class="label">Tempo</div></div>
      </div>
      <div class="result-actions">
        <a href="/quiz" class="btn btn-back">?? Voltar aos módulos</a>
        <a href="/quiz/take?module=<?= $attempt['module_id'] ?>" class="btn btn-retry">?? Tentar novamente</a>
      </div>
    </div>

    <div class="review-section">
      <h2>?? Revisão das questões</h2>
      <?php foreach ($allAnswers as $i => $a): 
        $isCorrect = $a['is_correct'];
        $userLetter = $a['selected_answer'];
        $correctLetter = $a['correct_answer'];
        $options = ['A' => $a['option_a'], 'B' => $a['option_b'], 'C' => $a['option_c'], 'D' => $a['option_d']];
      ?>
        <div class="review-item">
          <div class="q"><?= ($i + 1) ?>. <?= htmlspecialchars($a['question_text']) ?></div>
          <div class="your-answer <?= $isCorrect ? 'correct' : 'wrong' ?>">
            Sua resposta: <?= $userLetter ? htmlspecialchars($userLetter . ') ' . ($options[$userLetter] ?? '')) : 'Não respondida' ?>
          </div>
          <?php if (!$isCorrect): ?>
            <div class="correct">Resposta correta: <?= htmlspecialchars($correctLetter . ') ' . ($options[$correctLetter] ?? '')) ?></div>
          <?php endif; ?>
          <?php if ($a['justification'] && !$isCorrect): ?>
            <div class="justification">?? <?= htmlspecialchars($a['justification']) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
