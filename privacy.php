<?php require_once 'includes/session.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Política de Privacidade - HuB Finedu</title>
  <link rel="icon" type="image/svg+xml" href="/assets/favicon.svg">
  <link rel="stylesheet" href="css/style.css">
  <style>
    .legal-page { padding: 60px 20px; max-width: 800px; margin: 0 auto; }
    .legal-page h1 { font-size: 2rem; margin-bottom: 8px; color: var(--primary); }
    .legal-page .date { color: #94a3b8; font-size: 0.9rem; margin-bottom: 32px; }
    .legal-page h2 { font-size: 1.3rem; margin: 28px 0 12px; color: var(--primary); }
    .legal-page p { color: #475569; line-height: 1.8; margin-bottom: 12px; }
    .legal-page ul { color: #475569; line-height: 1.8; padding-left: 24px; margin-bottom: 12px; }
    .legal-back { display: inline-block; margin-top: 32px; color: var(--accent-1); text-decoration: none; }
    .legal-back:hover { text-decoration: underline; }
  </style>
</head>
<body>
  <nav class="navbar">
    <div class="container">
      <a href="/" class="logo">
        <svg width="28" height="28" viewBox="0 0 32 32" fill="none">
          <circle cx="16" cy="16" r="15" fill="url(#logo-grad)"/>
          <path d="M10 20V12l6-4 6 4v8l-6 4-6-4z" fill="white" opacity="0.9"/>
          <path d="M16 12v8M12 16h8" stroke="#0f172a" stroke-width="2" stroke-linecap="round"/>
          <defs><linearGradient id="logo-grad" x1="0" y1="0" x2="32" y2="32"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#f59e0b"/></linearGradient></defs>
        </svg>
        HuB <span>Finedu</span>
      </a>
    </div>
  </nav>

  <div class="legal-page">
    <h1>Política de Privacidade</h1>
    <p class="date">Última atualização: Julho de 2026</p>

    <h2>1. Informações que Coletamos</h2>
    <p>Coletamos as seguintes informações quando você cria uma conta ou utiliza nossos serviços:</p>
    <ul>
      <li>Nome completo e sobrenome</li>
      <li>Endereço de e-mail</li>
      <li>Nome de usuário e senha</li>
      <li>Município/Estado</li>
      <li>Telefone</li>
      <li>CPF (opcional no cadastro inicial)</li>
      <li>Informações de perfil do Google (quando usado login via Google)</li>
    </ul>

    <h2>2. Como Usamos suas Informações</h2>
    <p>Utilizamos seus dados para:</p>
    <ul>
      <li>Criar e gerenciar sua conta</li>
      <li>Fornecer acesso à plataforma AVA</li>
      <li>Enviar comunicações relacionadas ao serviço</li>
      <li>Melhorar nossos cursos e materiais</li>
      <li>Cumprir obrigações legais</li>
    </ul>

    <h2>3. Armazenamento e Segurança</h2>
    <p>Seus dados são armazenados de forma segura e protegidos contra acesso não autorizado. Utilizamos criptografia para senhas e conexões SSL/TLS.</p>

    <h2>4. Compartilhamento de Dados</h2>
    <p>Não compartilhamos seus dados pessoais com terceiros, exceto quando exigido por lei.</p>

    <h2>5. Retenção de Dados</h2>
    <p>Seus dados serão mantidos enquanto sua conta estiver ativa. Contas não utilizadas por 30 dias após o término do teste serão excluídas.</p>

    <h2>6. Seus Direitos</h2>
    <p>Você pode solicitar a exclusão de seus dados a qualquer momento entrando em contato conosco.</p>

    <h2>7. Cookies</h2>
    <p>Utilizamos cookies essenciais para o funcionamento da plataforma (sessão e autenticação). Não utilizamos cookies de rastreamento ou publicidade.</p>

    <h2>8. Contato</h2>
    <p>Para questões sobre privacidade, entre em contato: <strong>contato@hubfinedu.com.br</strong></p>

    <a href="/register" class="legal-back">&larr; Voltar ao cadastro</a>
  </div>

  <footer class="footer">
    <div class="container">
      <div class="footer-bottom">
        &copy; 2026 HuB Finedu. Todos os direitos reservados.
      </div>
    </div>
  </footer>
</body>
</html>
