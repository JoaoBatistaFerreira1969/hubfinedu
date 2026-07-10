<?php
require_once 'includes/session.php';
require_once 'includes/functions.php';

$loggedIn = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === ADMIN_PASSWORD) {
        $_SESSION['admin_logged'] = true;
        $loggedIn = true;
    }
}
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged']);
    header('Location: /admin');
    exit;
}
if (!empty($_SESSION['admin_logged'])) {
    $loggedIn = true;
}

$actionMsg = '';
if ($loggedIn && isDB() && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['grant_category'])) {
        $targetUserId = $_POST['user_id'] ?? '';
        $categoryId = (int)($_POST['category_id'] ?? 0);
        if ($targetUserId && $categoryId) {
            dbGrantUserCategory($targetUserId, $categoryId);
            $actionMsg = 'Acesso liberado com sucesso!';
        }
    }
    if (isset($_POST['revoke_category'])) {
        $targetUserId = $_POST['user_id'] ?? '';
        $categoryId = (int)($_POST['category_id'] ?? 0);
        if ($targetUserId && $categoryId) {
            dbRevokeUserCategory($targetUserId, $categoryId);
            $actionMsg = 'Acesso removido com sucesso!';
        }
    }
    if (isset($_POST['grant_all'])) {
        $targetUserId = $_POST['user_id'] ?? '';
        if ($targetUserId) {
            dbGrantAllCategories($targetUserId);
            $actionMsg = 'Todas as certificações foram liberadas!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - HuB Finedu</title>
  <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .admin-page { padding: 40px 20px; max-width: 1000px; margin: 0 auto; }
    .admin-page h1 { font-size: 1.8rem; margin-bottom: 24px; color: var(--primary); }
    .admin-login { max-width: 360px; margin: 100px auto; text-align: center; }
    .admin-login input { padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 1rem; width: 100%; margin: 12px 0; box-sizing: border-box; }
    .admin-login button { padding: 12px 32px; border-radius: 50px; background: var(--primary); color: #fff; border: none; font-size: 1rem; cursor: pointer; }
    .admin-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow); }
    .admin-table th { background: var(--primary); color: #fff; padding: 12px 16px; text-align: left; font-size: 0.85rem; }
    .admin-table td { padding: 10px 16px; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; vertical-align: top; }
    .admin-table tr:hover td { background: #f8fafc; }
    .badge-confirmed { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .badge-yes { background: #dcfce7; color: #16a34a; }
    .badge-no { background: #fef2f2; color: #dc2626; }
    .admin-actions { display: flex; gap: 12px; margin-bottom: 24px; align-items: center; flex-wrap: wrap; }
    .admin-nav { display: flex; gap: 0; margin-bottom: 24px; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; }
    .admin-nav a { padding: 10px 24px; text-decoration: none; font-size: 0.9rem; font-weight: 600; color: #94a3b8; background: #fff; transition: 0.2s; }
    .admin-nav a:hover { background: #f8fafc; }
    .admin-nav a.active { background: var(--primary); color: #fff; }
    .admin-count { color: #64748b; font-size: 0.95rem; }
    .admin-error { color: #dc2626; margin-top: 8px; }
    .btn-admin { padding: 8px 16px; border-radius: 8px; background: var(--accent-1); color: #fff; text-decoration: none; font-size: 0.85rem; border: none; cursor: pointer; font-family: inherit; }
    .btn-admin:hover { background: var(--accent-1-dark); }
    .btn-danger { padding: 6px 12px; border-radius: 6px; background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; font-size: 0.75rem; cursor: pointer; font-family: inherit; }
    .btn-danger:hover { background: #fecaca; }
    .btn-success { padding: 6px 12px; border-radius: 6px; background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; font-size: 0.75rem; cursor: pointer; font-family: inherit; }
    .btn-success:hover { background: #bbf7d0; }
    .cat-tags { display: flex; flex-wrap: wrap; gap: 4px; margin: 4px 0; }
    .cat-tag { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: 0.7rem; font-weight: 600; background: #eff6ff; color: #3b82f6; }
    .cat-tag.locked { background: #fef2f2; color: #dc2626; }
    .action-msg { padding: 10px 14px; border-radius: 8px; background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; font-size: 0.85rem; margin-bottom: 16px; }
    .admin-section { background: #fff; border-radius: 12px; padding: 20px; margin-bottom: 24px; box-shadow: var(--shadow); }
    .admin-section h2 { font-size: 1.1rem; color: var(--primary); margin: 0 0 16px 0; }
    .inline-form { display: inline; }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="container">
      <a href="/" class="logo">HuB <span>Finedu</span></a>
      <ul class="nav-links">
        <li><a href="/">Início</a></li>
        <?php if ($loggedIn): ?>
          <li><a href="/admin?logout=1" class="nav-btn">Sair</a></li>
        <?php endif; ?>
      </ul>
    </div>
  </nav>

  <div class="admin-page">
    <?php if (!$loggedIn): ?>
      <div class="admin-login">
        <h1>Admin HuB Finedu</h1>
        <form method="POST">
          <input type="password" name="password" placeholder="Senha de administrador" required>
          <button type="submit">Entrar</button>
        </form>
        <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
          <p class="admin-error">Senha incorreta.</p>
        <?php endif; ?>
      </div>
    <?php else:
      $section = $_GET['section'] ?? 'users';
    ?>
      <div class="admin-nav">
        <a href="?section=users" class="<?= $section === 'users' ? 'active' : '' ?>">Usuários</a>
        <a href="/admin/import" class="<?= $section === 'import' ? 'active' : '' ?>">Importar</a>
      </div>

      <?php if ($actionMsg): ?>
        <div class="action-msg"><?= htmlspecialchars($actionMsg) ?></div>
      <?php endif; ?>

      <?php if ($section === 'users'): ?>
        <div class="admin-actions">
          <h1>Usuários Cadastrados</h1>
          <a href="?refresh=1" class="btn-admin">Atualizar</a>
        </div>

        <?php
        $users = getAllUsers();
        $confirmed = count(array_filter($users, fn($u) => $u['confirmed'] ?? false));
        $categories = isDB() ? dbGetCategories() : [];
        ?>
        <p class="admin-count">Total: <strong><?= count($users) ?></strong> | Confirmados: <strong><?= $confirmed ?></strong> | Pendentes: <strong><?= count($users) - $confirmed ?></strong></p>

        <?php if (empty($users)): ?>
          <p style="color:#94a3b8;margin-top:32px">Nenhum usuário cadastrado ainda.</p>
        <?php else: ?>
          <table class="admin-table">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Email</th>
                <th>Usuário</th>
                <th>Criado em</th>
                <th>Confirmado</th>
                <th>Certificações</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u):
                $userCats = isDB() ? dbGetUserCategories($u['id']) : [];
                $userCatIds = array_map(fn($uc) => $uc['id'], $userCats);
              ?>
                <tr>
                  <td><?= htmlspecialchars(($u['name'] ?? '') . ' ' . ($u['surname'] ?? '')) ?></td>
                  <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
                  <td><?= htmlspecialchars($u['username'] ?? '') ?></td>
                  <td><?= htmlspecialchars(date('d/m/Y', strtotime($u['createdAt'] ?? 'now'))) ?></td>
                  <td>
                    <span class="badge-confirmed <?= ($u['confirmed'] ?? false) ? 'badge-yes' : 'badge-no' ?>">
                      <?= ($u['confirmed'] ?? false) ? 'Sim' : 'Não' ?>
                    </span>
                  </td>
                  <td>
                    <?php if (isDB()): ?>
                      <div class="cat-tags">
                        <?php foreach ($userCats as $uc): ?>
                          <span class="cat-tag"><?= htmlspecialchars($uc['code']) ?></span>
                        <?php endforeach; ?>
                        <?php if (empty($userCats)): ?>
                          <span style="color:#94a3b8;font-size:0.8rem">Nenhuma</span>
                        <?php endif; ?>
                      </div>
                    <?php else: ?>
                      <span style="color:#94a3b8;font-size:0.8rem">BD não configurado</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if (isDB() && !empty($categories)): ?>
                      <details style="font-size:0.8rem">
                        <summary style="cursor:pointer;color:var(--accent-1)">Gerenciar</summary>
                        <div style="margin-top:8px">
                          <form method="POST" class="inline-form">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                            <select name="category_id" required style="padding:4px 8px;border:1px solid #e2e8f0;border-radius:4px;font-size:0.8rem;margin-bottom:4px">
                              <option value="">Selecione...</option>
                              <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                              <?php endforeach; ?>
                            </select>
                            <button type="submit" name="grant_category" class="btn-success">Liberar</button>
                            <button type="submit" name="revoke_category" class="btn-danger">Remover</button>
                          </form>
                          <form method="POST" class="inline-form" style="display:block;margin-top:4px">
                            <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                            <button type="submit" name="grant_all" class="btn-admin" style="font-size:0.75rem;padding:4px 10px">Liberar todas</button>
                          </form>
                        </div>
                      </details>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>
