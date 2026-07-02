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
    .admin-login input { padding: 10px 14px; border: 1.5px solid #e2e8f0; border-radius: 8px; font-size: 1rem; width: 100%; margin: 12px 0; }
    .admin-login button { padding: 12px 32px; border-radius: 50px; background: var(--primary); color: #fff; border: none; font-size: 1rem; cursor: pointer; }
    .admin-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: var(--shadow); }
    .admin-table th { background: var(--primary); color: #fff; padding: 12px 16px; text-align: left; font-size: 0.85rem; }
    .admin-table td { padding: 10px 16px; border-bottom: 1px solid #e2e8f0; font-size: 0.9rem; }
    .admin-table tr:hover td { background: #f8fafc; }
    .badge-confirmed { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
    .badge-yes { background: #dcfce7; color: #16a34a; }
    .badge-no { background: #fef2f2; color: #dc2626; }
    .admin-actions { display: flex; gap: 12px; margin-bottom: 24px; align-items: center; }
    .admin-count { color: #64748b; font-size: 0.95rem; }
    .admin-error { color: #dc2626; margin-top: 8px; }
    .btn-admin { padding: 8px 16px; border-radius: 8px; background: var(--accent-1); color: #fff; text-decoration: none; font-size: 0.85rem; }
    .btn-admin:hover { background: var(--accent-1-dark); }
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
    <?php else: ?>
      <div class="admin-actions">
        <h1>Usuários Cadastrados</h1>
        <a href="/admin?refresh=1" class="btn-admin">Atualizar</a>
      </div>

      <?php
      $users = readUsers();
      $confirmed = count(array_filter($users, fn($u) => $u['confirmed'] ?? false));
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
              <th>Telefone</th>
              <th>Criado em</th>
              <th>Confirmado</th>
              <th>Provedor</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($users as $u): ?>
              <tr>
                <td><?= htmlspecialchars(($u['name'] ?? '') . ' ' . ($u['surname'] ?? '')) ?></td>
                <td><?= htmlspecialchars($u['email'] ?? '') ?></td>
                <td><?= htmlspecialchars($u['username'] ?? '') ?></td>
                <td><?= htmlspecialchars($u['phone'] ?? '') ?></td>
                <td><?= htmlspecialchars(date('d/m/Y', strtotime($u['createdAt'] ?? 'now'))) ?></td>
                <td>
                  <span class="badge-confirmed <?= ($u['confirmed'] ?? false) ? 'badge-yes' : 'badge-no' ?>">
                    <?= ($u['confirmed'] ?? false) ? 'Sim' : 'Não' ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($u['provider'] ?? 'local') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</body>
</html>
