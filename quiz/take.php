<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
requireAuth();

if (!isDB()) {
    echo '<h2>Banco de dados não configurado. Acesse /setup primeiro.</h2>'; exit;
}

$userId = getUser()['id'];
$moduleId = (int)($_GET['module'] ?? 0);
$module = dbGetModule($moduleId);
if (!$module) { echo '<h2>Módulo inválido.</h2>'; exit; }

$attemptNum = 1;
$attempts = dbGetUserAttempts($userId, $moduleId);
if (!empty($attempts)) {
    foreach ($attempts as $a) {
        if ($a['status'] === 'in_progress') {
            header('Location: /quiz/result?attempt=' . $a['id']);
            exit;
        }
        $attemptNum = max($attemptNum, $a['attempt_number'] + 1);
    }
}

if ($attemptNum > $module['max_attempts']) {
    echo '<h2>Limite de tentativas atingido para este módulo.</h2><a href="/quiz">Voltar</a>'; exit;
}

$attemptId = dbCreateAttempt($userId, $moduleId, $attemptNum);

$questions = dbGetQuestions($moduleId, $module['total_questions']);
if (empty($questions)) {
    echo '<h2>Nenhuma questão cadastrada para este módulo.</h2><a href="/quiz">Voltar</a>'; exit;
}

$totalTime = count($questions) * (int)$module['time_per_question'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Quiz - <?= htmlspecialchars($module['name']) ?> - HuB Finedu</title>
  <link rel="stylesheet" href="/css/style.css">
  <style>
    .quiz-container { max-width: 800px; margin: 0 auto; padding: 20px; }
    .quiz-topbar { background: var(--primary); color: #fff; padding: 12px 20px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 8px; }
    .quiz-topbar .mod-name { font-weight: 700; }
    .quiz-topbar .timer { font-size: 1.2rem; font-weight: 700; font-variant-numeric: tabular-nums; }
    .timer.warning { color: #f59e0b; }
    .timer.danger { color: #ef4444; animation: pulse 1s infinite; }
    @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.5; } }
    .question-card { background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    .q-number { font-size: 0.85rem; color: #94a3b8; margin-bottom: 4px; }
    .q-topic { font-size: 0.8rem; color: var(--accent-1); font-weight: 600; margin-bottom: 16px; }
    .q-text { font-size: 1.1rem; color: var(--primary); line-height: 1.7; margin-bottom: 24px; font-weight: 500; }
    .options { display: flex; flex-direction: column; gap: 12px; }
    .option { display: flex; align-items: center; gap: 12px; padding: 14px 18px; border: 2px solid #e2e8f0; border-radius: 12px; cursor: pointer; transition: 0.2s; font-size: 0.95rem; }
    .option:hover { border-color: var(--accent-1); background: rgba(59,130,246,0.03); }
    .option.selected { border-color: var(--accent-1); background: rgba(59,130,246,0.08); }
    .option .letter { width: 28px; height: 28px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; flex-shrink: 0; }
    .option.selected .letter { background: var(--accent-1); color: #fff; }
    .quiz-footer { display: flex; justify-content: space-between; align-items: center; margin-top: 24px; gap: 12px; }
    .btn-next { padding: 12px 32px; border-radius: 50px; background: var(--accent-1); color: #fff; border: none; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.2s; }
    .btn-next:hover { background: var(--accent-1-dark); }
    .btn-next:disabled { opacity: 0.5; cursor: not-allowed; }
    .btn-finish { padding: 12px 32px; border-radius: 50px; background: var(--accent-2); color: var(--primary); border: none; font-size: 1rem; font-weight: 700; cursor: pointer; transition: 0.2s; }
    .btn-finish:hover { background: var(--accent-2-dark); }
    .q-counter { color: #64748b; font-size: 0.9rem; }
    .q-progress { display: flex; gap: 6px; margin-bottom: 20px; flex-wrap: wrap; }
    .q-dot { width: 28px; height: 28px; border-radius: 50%; border: 2px solid #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 0.7rem; font-weight: 600; color: #94a3b8; cursor: pointer; transition: 0.2s; background: #fff; }
    .q-dot.active { border-color: var(--accent-1); background: var(--accent-1); color: #fff; }
    .q-dot.answered { border-color: #22c55e; background: #dcfce7; color: #16a34a; }
    .justification-box { background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px; padding: 20px; margin-top: 20px; }
    .justification-box h4 { color: #0369a1; margin-bottom: 8px; }
    .justification-box p { color: #475569; font-size: 0.9rem; line-height: 1.7; }
    .feedback-correct { color: #16a34a; font-weight: 700; display: flex; align-items: center; gap: 8px; margin-top: 12px; }
    .feedback-wrong { color: #dc2626; font-weight: 700; display: flex; align-items: center; gap: 8px; margin-top: 12px; }
    .loading { text-align: center; padding: 60px; color: #94a3b8; }
    @media (max-width: 600px) { .question-card { padding: 20px; } }
  </style>
</head>
<body>
  <div class="quiz-container">
    <div class="quiz-topbar">
      <div>
        <span class="mod-name">?? <?= htmlspecialchars($module['name']) ?></span>
        <span style="margin-left:12px;font-size:0.85rem;opacity:0.8">Tentativa <?= $attemptNum ?>/<?= $module['max_attempts'] ?></span>
      </div>
      <div class="timer" id="timer"><?= gmdate('i:s', $totalTime) ?></div>
    </div>

    <div id="quiz-content">
      <div class="loading">? Carregando questões...</div>
    </div>
  </div>

  <script>
    const questions = <?= json_encode(array_map(fn($q) => ['id' => (int)$q['id'], 'module_id' => (int)$q['module_id'], 'topic' => $q['topic'], 'question_text' => $q['question_text'], 'option_a' => $q['option_a'], 'option_b' => $q['option_b'], 'option_c' => $q['option_c'], 'option_d' => $q['option_d']], $questions)) ?>;
    const attemptId = <?= $attemptId ?>;
    const totalQuestions = questions.length;
    const timePerQuestion = <?= (int)$module['time_per_question'] ?>;
    let currentIndex = 0;
    let answers = new Array(totalQuestions).fill(null);
    let startTime = Date.now();
    let questionStartTime = Date.now();
    let totalTimeSpent = 0;
    let submitted = false;

    const timerEl = document.getElementById('timer');
    const contentEl = document.getElementById('quiz-content');

    function shuffleOptions(q) {
      const letters = ['A', 'B', 'C', 'D'];
      const options = [
        { letter: 'A', text: q.option_a },
        { letter: 'B', text: q.option_b },
        { letter: 'C', text: q.option_c },
        { letter: 'D', text: q.option_d },
      ];
      for (let i = options.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [options[i], options[j]] = [options[j], options[i]];
      }
      return options;
    }

    const shuffledOptions = questions.map(q => shuffleOptions(q));

    function renderQuestion(index) {
      const q = questions[index];
      const opts = shuffledOptions[index];
      const answered = answers[index];
      const count = index + 1;

      let dotsHtml = '';
      for (let i = 0; i < totalQuestions; i++) {
        const cls = i === index ? 'active' : (answers[i] !== null ? 'answered' : '');
        dotsHtml += `<div class="q-dot ${cls}" onclick="goToQuestion(${i})">${i + 1}</div>`;
      }

      let optionsHtml = '';
      opts.forEach(o => {
        const sel = answered === o.letter ? 'selected' : '';
        optionsHtml += `<div class="option ${sel}" onclick="selectOption(${index}, '${o.letter}')">
          <span class="letter">${o.letter}</span>
          <span>${o.text}</span>
        </div>`;
      });

      contentEl.innerHTML = `
        <div class="q-progress">${dotsHtml}</div>
        <div class="question-card">
          <div class="q-number">Questão ${count} de ${totalQuestions}</div>
          ${q.topic ? `<div class="q-topic">${q.topic}</div>` : ''}
          <div class="q-text">${q.question_text}</div>
          <div class="options">${optionsHtml}</div>
          <div class="quiz-footer">
            <div class="q-counter">${count}/${totalQuestions}</div>
            <div>
              ${count < totalQuestions
                ? `<button class="btn-next" id="btn-next" onclick="nextQuestion(${index})" ${answered === null ? 'disabled' : ''}>Próxima ?</button>`
                : `<button class="btn-finish" id="btn-finish" onclick="finishQuiz()" ${answered === null ? 'disabled' : ''}>?? Finalizar</button>`
              }
            </div>
          </div>
        </div>
      `;
      questionStartTime = Date.now();
    }

    function selectOption(qIndex, letter) {
      answers[qIndex] = letter;
      renderQuestion(currentIndex);
    }

    function goToQuestion(index) {
      currentIndex = index;
      renderQuestion(index);
    }

    function nextQuestion(index) {
      if (index < totalQuestions - 1) {
        currentIndex = index + 1;
        renderQuestion(currentIndex);
      }
    }

    function finishQuiz() {
      if (submitted) return;
      if (!confirm('Deseja finalizar o questionário? Questões não respondidas serão consideradas erradas.')) return;
      submitted = true;

      const timeSpent = Math.floor((Date.now() - startTime) / 1000);
      const results = [];
      for (let i = 0; i < totalQuestions; i++) {
        results.push({
          questionId: questions[i].id,
          answer: answers[i],
          timeSpent: Math.floor(timePerQuestion)
        });
      }

      fetch('/quiz/answer', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ attemptId, answers: results, timeSpent })
      })
      .then(r => r.json())
      .then(data => {
        window.location.href = '/quiz/result?attempt=' + attemptId;
      })
      .catch(() => {
        alert('Erro ao enviar respostas. Tente novamente.');
        submitted = false;
      });
    }

    function updateTimer() {
      if (submitted) return;
      const elapsed = Math.floor((Date.now() - startTime) / 1000);
      const remaining = (totalQuestions * timePerQuestion) - elapsed;
      if (remaining <= 0) {
        timerEl.textContent = '00:00';
        finishQuiz();
        return;
      }
      const m = Math.floor(remaining / 60);
      const s = remaining % 60;
      timerEl.textContent = `${String(m).padStart(2,'0')}:${String(s).padStart(2,'0')}`;
      timerEl.className = 'timer' + (remaining < 60 ? ' danger' : remaining < 180 ? ' warning' : '');
    }

    renderQuestion(0);
    setInterval(updateTimer, 1000);
  </script>
</body>
</html>
