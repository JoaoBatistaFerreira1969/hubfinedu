<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/functions.php';
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isDB()) {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$attemptId = (int)($input['attemptId'] ?? 0);
$answers = $input['answers'] ?? [];
$totalTimeSpent = (int)($input['timeSpent'] ?? 0);

$attempt = dbGetAttempt($attemptId);
if (!$attempt || $attempt['user_id'] !== getUser()['id']) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado']);
    exit;
}

$correctCount = 0;
foreach ($answers as $a) {
    $questionId = (int)($a['questionId'] ?? 0);
    $answer = $a['answer'] ?? null;
    $timeSpent = min((int)($a['timeSpent'] ?? 0), 300);

    $question = dbGetQuestion($questionId);
    if (!$question) continue;

    $isCorrect = ($answer !== null && strtoupper($answer) === $question['correct_answer']);
    if ($isCorrect) $correctCount++;

    dbSaveAnswer($attemptId, $questionId, $answer ? strtoupper($answer) : null, $isCorrect, $timeSpent);
}

$totalQuestions = count($answers);
$score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 1) : 0;

dbCompleteAttempt($attemptId, $score, $correctCount, $totalQuestions, $totalTimeSpent);

$moduleId = (int)$attempt['module_id'];
$xpGain = round($score * 1.5);
dbUpsertProgress(getUser()['id'], $moduleId, $score, 1);

$userProgress = dbGetUserProgress(getUser()['id'], $moduleId);
$bestScore = $userProgress['best_score'] ?? 0;
if ($score >= 50) {
    $stmt = getDB()->prepare('SELECT id FROM modules WHERE order_num = (SELECT order_num + 1 FROM modules WHERE id = ?)');
    $stmt->execute([$moduleId]);
    $nextModule = $stmt->fetch();
    if ($nextModule) {
        $existing = getDB()->prepare('SELECT id FROM user_progress WHERE user_id = ? AND module_id = ?');
        $existing->execute([getUser()['id'], $nextModule['id']]);
        if (!$existing->fetch()) {
            dbUpsertProgress(getUser()['id'], $nextModule['id'], 0, 0);
        }
    }
}

$user = getUser();
dbUpdateUser($user['id'], ['xp' => ($user['xp'] ?? 0) + $xpGain]);

echo json_encode([
    'success' => true,
    'score' => $score,
    'correct' => $correctCount,
    'total' => $totalQuestions,
    'xpGain' => $xpGain,
]);
