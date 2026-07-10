<?php
require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = DB_HOST ?? 'localhost';
        $name = DB_NAME ?? '';
        $user = DB_USER ?? '';
        $pass = DB_PASS ?? '';
        $charset = 'utf8mb4';
        $dsn = "mysql:host=$host;dbname=$name;charset=$charset";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function dbMapUser(array $u): array {
    return [
        'id' => $u['id'],
        'username' => $u['username'],
        'password' => $u['password'] ?? '',
        'email' => $u['email'],
        'name' => $u['name'] ?? '',
        'surname' => $u['surname'] ?? '',
        'city' => $u['city'] ?? '',
        'phone' => $u['phone'] ?? '',
        'cpf' => $u['cpf'] ?? '',
        'confirmed' => (bool)($u['confirmed'] ?? false),
        'confirmationToken' => $u['confirmation_token'] ?? null,
        'trialEndsAt' => $u['trial_ends_at'] ?? null,
        'expiresAt' => $u['expires_at'] ?? null,
        'provider' => $u['provider'] ?? 'local',
        'photo' => $u['photo'] ?? '',
        'xp' => (int)($u['xp'] ?? 0),
        'level' => (int)($u['level'] ?? 1),
        'createdAt' => $u['created_at'] ?? null,
    ];
}

function dbFindByEmail(string $email): ?array {
    $stmt = getDB()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([strtolower($email)]);
    $user = $stmt->fetch();
    return $user ? dbMapUser($user) : null;
}

function dbFindByToken(string $token): ?array {
    $stmt = getDB()->prepare('SELECT * FROM users WHERE confirmation_token = ?');
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    return $user ? dbMapUser($user) : null;
}

function dbCreateUser(array $data): array {
    $db = getDB();
    $stmt = $db->prepare('INSERT INTO users (id, username, password, email, name, surname, city, phone, cpf, confirmed, confirmation_token, trial_ends_at, expires_at, provider, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        $data['id'],
        $data['username'],
        $data['password'] ?? null,
        $data['email'],
        $data['name'] ?? '',
        $data['surname'] ?? '',
        $data['city'] ?? '',
        $data['phone'] ?? '',
        $data['cpf'] ?? '',
        $data['confirmed'] ? 1 : 0,
        $data['confirmationToken'] ?? null,
        $data['trialEndsAt'] ?? null,
        $data['expiresAt'] ?? null,
        $data['provider'] ?? 'local',
        $data['createdAt'] ?? date('c'),
    ]);
    return $data;
}

function dbUpdateUser(string $id, array $updates): ?array {
    $fields = [];
    $values = [];
    $fieldMap = [
        'confirmed' => 'confirmed',
        'confirmationToken' => 'confirmation_token',
        'trialEndsAt' => 'trial_ends_at',
        'name' => 'name',
        'surname' => 'surname',
        'phone' => 'phone',
        'city' => 'city',
        'cpf' => 'cpf',
        'password' => 'password',
        'xp' => 'xp',
        'level' => 'level',
    ];
    foreach ($updates as $key => $value) {
        if (isset($fieldMap[$key])) {
            $fields[] = $fieldMap[$key] . ' = ?';
            $values[] = $value;
        }
    }
    if (empty($fields)) return null;
    $values[] = $id;
    $stmt = getDB()->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?');
    $stmt->execute($values);
    $stmt = getDB()->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    return $user ? dbMapUser($user) : null;
}

function dbGetAllUsers(): array {
    $stmt = getDB()->query('SELECT * FROM users ORDER BY created_at DESC');
    $users = $stmt->fetchAll();
    return array_map('dbMapUser', $users);
}

function dbGetCategories(): array {
    $stmt = getDB()->query('SELECT * FROM categories WHERE enabled = 1 ORDER BY name');
    return $stmt->fetchAll();
}

function dbGetCategory(int $id): ?array {
    $stmt = getDB()->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function dbGetCategoryByCode(string $code): ?array {
    $stmt = getDB()->prepare('SELECT * FROM categories WHERE code = ?');
    $stmt->execute([$code]);
    return $stmt->fetch() ?: null;
}

function dbGetModules(): array {
    $stmt = getDB()->query('SELECT m.*, c.name as category_name, c.code as category_code FROM modules m LEFT JOIN categories c ON m.category_id = c.id ORDER BY m.order_num');
    return $stmt->fetchAll();
}

function dbGetModule(int $id): ?array {
    $stmt = getDB()->prepare('SELECT m.*, c.name as category_name, c.code as category_code FROM modules m LEFT JOIN categories c ON m.category_id = c.id WHERE m.id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function dbGetModulesByCategory(int $categoryId): array {
    $stmt = getDB()->prepare('SELECT * FROM modules WHERE category_id = ? ORDER BY order_num');
    $stmt->execute([$categoryId]);
    return $stmt->fetchAll();
}

function dbGetModulesWithoutCategory(): array {
    $stmt = getDB()->query('SELECT * FROM modules WHERE category_id IS NULL ORDER BY order_num');
    return $stmt->fetchAll();
}

function dbGetUserCategoryAccess(string $userId, int $categoryId): bool {
    $stmt = getDB()->prepare('SELECT id FROM user_categories WHERE user_id = ? AND category_id = ? AND (expires_at IS NULL OR expires_at > NOW())');
    $stmt->execute([$userId, $categoryId]);
    return (bool)$stmt->fetch();
}

function dbGetUserCategories(string $userId): array {
    $stmt = getDB()->prepare('SELECT c.*, uc.expires_at as access_expires_at FROM user_categories uc JOIN categories c ON uc.category_id = c.id WHERE uc.user_id = ? AND (uc.expires_at IS NULL OR uc.expires_at > NOW()) AND c.enabled = 1 ORDER BY c.name');
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function dbGrantUserCategory(string $userId, int $categoryId, ?string $expiresAt = null): void {
    $stmt = getDB()->prepare('INSERT INTO user_categories (user_id, category_id, expires_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE expires_at = VALUES(expires_at), granted_at = CURRENT_TIMESTAMP');
    $stmt->execute([$userId, $categoryId, $expiresAt]);
}

function dbRevokeUserCategory(string $userId, int $categoryId): void {
    $stmt = getDB()->prepare('DELETE FROM user_categories WHERE user_id = ? AND category_id = ?');
    $stmt->execute([$userId, $categoryId]);
}

function dbGrantAllCategories(string $userId, ?string $expiresAt = null): void {
    $cats = dbGetCategories();
    foreach ($cats as $c) {
        dbGrantUserCategory($userId, $c['id'], $expiresAt);
    }
}

function dbGetQuestions(int $moduleId, int $limit = 0): array {
    if ($limit > 0) {
        $stmt = getDB()->prepare('SELECT * FROM questions WHERE module_id = ? ORDER BY RAND() LIMIT ?');
        $stmt->bindValue(1, $moduleId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    } else {
        $stmt = getDB()->prepare('SELECT * FROM questions WHERE module_id = ? ORDER BY RAND()');
        $stmt->execute([$moduleId]);
    }
    $stmt->execute();
    return $stmt->fetchAll();
}

function dbGetQuestion(int $id): ?array {
    $stmt = getDB()->prepare('SELECT * FROM questions WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function dbInsertQuestions(array $questions): int {
    $db = getDB();
    $count = 0;
    $stmt = $db->prepare('INSERT INTO questions (module_id, topic, question_text, option_a, option_b, option_c, option_d, correct_answer, justification) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
    foreach ($questions as $q) {
        try {
            $stmt->execute([
                $q['module_id'],
                $q['topic'] ?? '',
                $q['question_text'],
                $q['option_a'],
                $q['option_b'],
                $q['option_c'],
                $q['option_d'],
                $q['correct_answer'],
                $q['justification'] ?? '',
            ]);
            $count++;
        } catch (\Exception $e) {
            continue;
        }
    }
    return $count;
}

function dbCreateAttempt(string $userId, int $moduleId, int $attemptNum): int {
    $db = getDB();
    $stmt = $db->prepare('INSERT INTO quiz_attempts (user_id, module_id, attempt_number, status) VALUES (?, ?, ?, ?)');
    $stmt->execute([$userId, $moduleId, $attemptNum, 'in_progress']);
    return (int)$db->lastInsertId();
}

function dbGetAttempt(int $id): ?array {
    $stmt = getDB()->prepare('SELECT * FROM quiz_attempts WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch() ?: null;
}

function dbGetUserAttempts(string $userId, int $moduleId): array {
    $stmt = getDB()->prepare('SELECT * FROM quiz_attempts WHERE user_id = ? AND module_id = ? ORDER BY attempt_number');
    $stmt->execute([$userId, $moduleId]);
    return $stmt->fetchAll();
}

function dbSaveAnswer(int $attemptId, int $questionId, ?string $answer, bool $correct, int $timeSpent): void {
    $stmt = getDB()->prepare('INSERT INTO quiz_answers (attempt_id, question_id, selected_answer, is_correct, time_spent, answered_at) VALUES (?, ?, ?, ?, ?, NOW())');
    $stmt->execute([$attemptId, $questionId, $answer, $correct ? 1 : 0, $timeSpent]);
}

function dbCompleteAttempt(int $attemptId, float $score, int $correct, int $total, int $timeSpent): void {
    $stmt = getDB()->prepare('UPDATE quiz_attempts SET score = ?, correct_answers = ?, total_questions = ?, time_spent = ?, status = ?, completed_at = NOW() WHERE id = ?');
    $stmt->execute([$score, $correct, $total, $timeSpent, 'completed', $attemptId]);
}

function dbGetUserProgress(string $userId, int $moduleId): ?array {
    $stmt = getDB()->prepare('SELECT * FROM user_progress WHERE user_id = ? AND module_id = ?');
    $stmt->execute([$userId, $moduleId]);
    return $stmt->fetch() ?: null;
}

function dbUpsertProgress(string $userId, int $moduleId, float $score, int $attemptsUsed): void {
    $exists = dbGetUserProgress($userId, $moduleId);
    $db = getDB();
    if ($exists) {
        $newScore = max($exists['best_score'], $score);
        $completed = $score >= 50 ? 1 : $exists['is_completed'];
        $stmt = $db->prepare('UPDATE user_progress SET best_score = ?, attempts_used = attempts_used + ?, is_completed = ?, completed_at = IF(? AND !is_completed, NOW(), completed_at) WHERE user_id = ? AND module_id = ?');
        $stmt->execute([$newScore, $attemptsUsed, $completed, $completed, $userId, $moduleId]);
    } else {
        $completed = $score >= 50 ? 1 : 0;
        $stmt = $db->prepare('INSERT INTO user_progress (user_id, module_id, best_score, attempts_used, is_unlocked, is_completed, completed_at) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([$userId, $moduleId, $score, $attemptsUsed, 1, $completed, $completed ? date('c') : null]);
    }
}

function dbGetLeaderboard(int $limit = 50): array {
    $stmt = getDB()->prepare('SELECT u.id, u.username, u.name, u.xp, u.level, u.photo FROM users u ORDER BY u.xp DESC, u.level DESC LIMIT ?');
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}
