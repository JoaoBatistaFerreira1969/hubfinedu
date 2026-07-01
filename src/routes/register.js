const { Router } = require('express');
const bcrypt = require('bcryptjs');
const { v4: uuidv4 } = require('uuid');
const { findByEmail, createUser, findByToken, updateUser } = require('../services/storage');
const { sendConfirmationEmail } = require('../services/email');

const router = Router();

const baseUrl = process.env.BASE_URL || 'http://localhost:3000';
const basePath = (() => {
  try { return new URL(baseUrl).pathname.replace(/\/+$/, ''); }
  catch { return ''; }
})();

async function verifyRecaptcha(token) {
  const secret = process.env.RECAPTCHA_SECRET_KEY;
  if (!secret || secret === 'sua_chave_secreta_do_recaptcha' || secret === '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe') {
    return true;
  }
  try {
    const https = require('https');
    const qs = new URLSearchParams({ secret, response: token });
    return new Promise((resolve, reject) => {
      const req = https.request(
        `https://www.google.com/recaptcha/api/siteverify`,
        { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' } },
        (res) => {
          let data = '';
          res.on('data', c => data += c);
          res.on('end', () => {
            try { const j = JSON.parse(data); resolve(j.success); }
            catch { resolve(false); }
          });
        }
      );
      req.on('error', () => resolve(false));
      req.write(qs.toString());
      req.end();
    });
  } catch { return false; }
}

router.post('/register', async (req, res) => {
  try {
    const { username, password, email, confirmEmail, name, surname, city, phone, cpf, recaptchaToken } = req.body;

    const recaptchaValid = await verifyRecaptcha(recaptchaToken);
    if (!recaptchaValid) {
      return res.status(400).json({ error: 'Falha na verificação do reCAPTCHA. Tente novamente.' });
    }

    const errors = [];

    if (!username || username.length < 3) errors.push('Usuário deve ter ao menos 3 caracteres');
    if (!password || !/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z0-9]).{8,}$/.test(password))
      errors.push('Senha deve ter ao menos 8 caracteres, 1 dígito, 1 minúscula, 1 maiúscula e 1 caractere especial');
    if (!email) errors.push('Email é obrigatório');
    if (email !== confirmEmail) errors.push('Emails não conferem');
    if (!name) errors.push('Nome é obrigatório');
    if (!surname) errors.push('Sobrenome é obrigatório');
    if (!city) errors.push('Município/Estado é obrigatório');
    if (!phone) errors.push('Telefone é obrigatório');

    if (errors.length > 0) {
      return res.status(400).json({ error: errors.join('; ') });
    }

    const existing = findByEmail(email);
    if (existing) {
      return res.status(409).json({ error: 'Este email já está cadastrado' });
    }

    const hashedPassword = await bcrypt.hash(password, 10);
    const confirmationToken = uuidv4();
    const now = new Date();

    const user = createUser({
      id: uuidv4(),
      username,
      password: hashedPassword,
      email: email.toLowerCase(),
      name,
      surname,
      city,
      phone,
      cpf: cpf || '',
      confirmed: false,
      confirmationToken,
      trialEndsAt: new Date(now.getTime() + 7 * 24 * 60 * 60 * 1000).toISOString(),
      expiresAt: new Date(now.getTime() + 30 * 24 * 60 * 60 * 1000).toISOString(),
      createdAt: now.toISOString(),
      provider: 'local'
    });

    try {
      await sendConfirmationEmail(user, confirmationToken);
    } catch (err) {
      console.error('Erro ao enviar email de confirmação:', err.message);
    }

    res.json({ success: true, message: 'Conta criada! Verifique seu email para confirmar o cadastro.' });
  } catch (err) {
    console.error('Erro no registro:', err);
    res.status(500).json({ error: 'Erro interno do servidor' });
  }
});

router.get('/confirm', async (req, res) => {
  const { token } = req.query;
  if (!token) {
    return res.status(400).send('<h2>Token de confirmação não fornecido.</h2>');
  }

  const user = findByToken(token);
  if (!user) {
    return res.status(400).send('<h2>Token inválido ou expirado.</h2>');
  }

  if (user.confirmed) {
    return res.send(`<h2>Conta já confirmada. Faça login.</h2><a href="${basePath}/login">Ir para Login</a>`);
  }

  updateUser(user.id, { confirmed: true, confirmationToken: null });
  res.send(`
    <h2>Conta confirmada com sucesso!</h2>
    <p>Sua senha de teste foi enviada no email de confirmação.</p>
    <p>Você tem 7 dias de teste no AVA.</p>
    <a href="${basePath}/login">Ir para Login</a>
  `);
});

module.exports = router;
