<?php require_once 'includes/session.php'; ?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Termos de Uso - HuB Finedu</title>
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
    <h1>Termos de Uso</h1>
    <p class="date">Última atualização: Julho de 2026</p>

    <h2>1. Aceitação dos Termos</h2>
    <p>Ao acessar ou usar a plataforma HuB Finedu, você concorda em cumprir estes Termos de Uso. Se não concordar, não utilize nossos serviços.</p>

    <h2>2. Descrição dos Serviços</h2>
    <p>O HuB Finedu oferece uma plataforma de aprendizagem online, consultoria e mentoria em educação financeira, gestão financeira e preparação para certificações CPA, C-Pro R e C-Pro I.</p>

    <h2>3. Conta do Usuário</h2>
    <p>Para acessar certos recursos, você deve criar uma conta. Você é responsável por:</p>
    <ul>
      <li>Manter a confidencialidade de sua senha</li>
      <li>Todas as atividades que ocorrerem em sua conta</li>
      <li>Fornecer informações precisas e atualizadas</li>
    </ul>

    <h2>4. Período de Teste</h2>
    <p>Novos usuários recebem 7 dias de teste gratuito no AVA. Após esse período, é necessária aquisição do acesso para continuar utilizando a plataforma.</p>

    <h2>5. Propriedade Intelectual</h2>
    <p>Todo o conteúdo disponibilizado na plataforma (cursos, materiais, textos, vídeos) é de propriedade do HuB Finedu e protegido por leis de direitos autorais.</p>

    <h2>6. Privacidade</h2>
    <p>O uso de seus dados é regido pela nossa <a href="/privacy">Política de Privacidade</a>.</p>

    <h2>7. Limitação de Responsabilidade</h2>
    <p>O HuB Finedu não se responsabiliza por danos indiretos decorrentes do uso ou da impossibilidade de uso da plataforma.</p>

    <h2>8. Alterações nos Termos</h2>
    <p>Podemos modificar estes termos a qualquer momento. Alterações significativas serão comunicadas via e-mail ou aviso na plataforma.</p>

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
