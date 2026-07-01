require('dotenv').config();

const express = require('express');
const session = require('express-session');
const passport = require('./src/auth/oauth2');
const authRoutes = require('./src/routes/auth');
const registerRoutes = require('./src/routes/register');
const path = require('path');
const fs = require('fs');

const app = express();
const PORT = process.env.PORT || 3000;

const baseUrl = process.env.BASE_URL || 'http://localhost:3000';
const basePath = (() => {
  try { return new URL(baseUrl).pathname.replace(/\/+$/, ''); }
  catch { return ''; }
})();

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.use(session({
  secret: process.env.SESSION_SECRET,
  resave: false,
  saveUninitialized: false
}));

app.use(passport.initialize());
app.use(passport.session());

app.use(basePath, express.static(path.join(__dirname, '.')));

app.use(basePath + '/auth', authRoutes);
app.use(basePath + '/auth', registerRoutes);

app.get(basePath + '/dashboard', (req, res) => {
  if (!req.isAuthenticated()) {
    return res.redirect(basePath + '/login');
  }
  res.sendFile(path.join(__dirname, 'dashboard.html'));
});

app.get(basePath + '/login', (req, res) => {
  res.sendFile(path.join(__dirname, 'login.html'));
});

app.get(basePath + '/register', (req, res) => {
  let html = fs.readFileSync(path.join(__dirname, 'register.html'), 'utf-8');
  html = html.replace('{RECAPTCHA_SITE_KEY}', process.env.RECAPTCHA_SITE_KEY || '');
  res.send(html);
});

app.listen(PORT, () => {
  console.log(`HuB Finedu rodando em ${baseUrl}`);
});
