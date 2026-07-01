const nodemailer = require('nodemailer');

function createTransporter() {
  return nodemailer.createTransport({
    host: process.env.SMTP_HOST,
    port: parseInt(process.env.SMTP_PORT || '587'),
    secure: process.env.SMTP_SECURE === 'true',
    auth: {
      user: process.env.SMTP_USER,
      pass: process.env.SMTP_PASS
    }
  });
}

async function sendLoginConfirmation(user) {
  const transporter = createTransporter();
  const appName = 'HuB Finedu';

  await transporter.sendMail({
    from: `"${appName}" <${process.env.SMTP_FROM}>`,
    to: user.email,
    subject: `Login confirmado - ${appName}`,
    html: `
      <div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif">
        <div style="background:linear-gradient(135deg,#3b82f6,#f59e0b);padding:24px;text-align:center;border-radius:12px 12px 0 0">
          <h1 style="color:#fff;margin:0;font-size:24px">${appName}</h1>
        </div>
        <div style="background:#fff;padding:32px;border:1px solid #e2e8f0;border-top:0;border-radius:0 0 12px 12px">
          <h2 style="color:#0f172a;margin-top:0">Login confirmado</h2>
          <p style="color:#475569;font-size:15px;line-height:1.6">
            Olá <strong>${user.name}</strong>,<br><br>
            Seu login na plataforma ${appName} foi realizado com sucesso usando sua conta Google.<br><br>
            <strong>Detalhes do acesso:</strong><br>
            \u2022 Email: ${user.email}<br>
            \u2022 Data: ${new Date().toLocaleString('pt-BR')}<br><br>
            Se não foi voc\u00ea, responda a este email imediatamente.
          </p>
          <hr style="border:0;border-top:1px solid #e2e8f0;margin:24px 0">
          <p style="color:#94a3b8;font-size:13px;text-align:center">
            ${appName} — Plataforma de Educação Financeira
          </p>
        </div>
      </div>
    `
  });
}

async function sendConfirmationEmail(user, token) {
  const transporter = createTransporter();
  const appName = 'HuB Finedu';
  const baseUrl = process.env.BASE_URL || 'http://localhost:3000';
  const confirmLink = `${baseUrl}/auth/confirm?token=${token}`;
  const tempPassword = user.username + '@T' + Math.random().toString(36).slice(2, 6);

  await transporter.sendMail({
    from: `"${appName}" <${process.env.SMTP_FROM}>`,
    to: user.email,
    subject: `Confirmação de conta - ${appName}`,
    html: `
      <div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif">
        <div style="background:linear-gradient(135deg,#3b82f6,#f59e0b);padding:24px;text-align:center;border-radius:12px 12px 0 0">
          <h1 style="color:#fff;margin:0;font-size:24px">${appName}</h1>
        </div>
        <div style="background:#fff;padding:32px;border:1px solid #e2e8f0;border-top:0;border-radius:0 0 12px 12px">
          <h2 style="color:#0f172a;margin-top:0">Confirmação de conta</h2>
          <p style="color:#475569;font-size:15px;line-height:1.6">
            Olá!<br><br>
            Uma nova conta foi criada em '<strong>${appName}</strong>' usando seu endereço de e-mail.<br><br>
            Para confirmar sua nova conta, acesse o seguinte endereço:<br><br>
            <a href="${confirmLink}" style="color:#3b82f6;font-size:14px">${confirmLink}</a><br><br>
            Na maioria dos programas de E-mail isso deve aparecer como um link azul que voc\u00ea pode simplesmente clicar. Se isto não funcionar, copie e cole este link na barra de endereços do seu navegador.<br><br>
            <strong>Senha para acessar o período de "TESTE" no AVA por 7 dias:</strong><br>
            <div style="background:#f8fafc;padding:12px 16px;border-radius:8px;font-family:monospace;font-size:16px;text-align:center;margin:8px 0">${tempPassword}</div><br>
            Lembre-se que seus dados serão excluídos em 30 dias, contados do término do TESTE no AVA, caso não faça "aquisição" do acesso ao AVA real de ESTUDO.<br><br>
            Se precisar de ajuda, contate o administrador do site.<br><br>
            Atenciosamente,<br>
            <strong>Suporte ${appName}</strong>
          </p>
          <hr style="border:0;border-top:1px solid #e2e8f0;margin:24px 0">
          <p style="color:#94a3b8;font-size:13px;text-align:center">
            ${appName} — Plataforma de Educação Financeira
          </p>
        </div>
      </div>
    `
  });
}

module.exports = { sendLoginConfirmation, sendConfirmationEmail };
