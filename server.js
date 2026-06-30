require('dotenv').config();

const express = require('express');
const session = require('express-session');
const passport = require('./src/auth/oauth2');
const authRoutes = require('./src/routes/auth');
const registerRoutes = require('./src/routes/register');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000;

app.use(express.json());
app.use(express.urlencoded({ extended: true }));

app.use(session({
  secret: process.env.SESSION_SECRET,
  resave: false,
  saveUninitialized: false
}));

app.use(passport.initialize());
app.use(passport.session());

app.use(express.static(path.join(__dirname, '.')));

app.use('/auth', authRoutes);
app.use('/auth', registerRoutes);

app.get('/', (req, res) => {
  res.sendFile(path.join(__dirname, 'index.html'));
});

app.get('/dashboard', (req, res) => {
  if (!req.isAuthenticated()) {
    return res.redirect('/login');
  }
  res.sendFile(path.join(__dirname, 'dashboard.html'));
});

app.get('/login', (req, res) => {
  res.sendFile(path.join(__dirname, 'login.html'));
});

app.get('/register', (req, res) => {
  res.sendFile(path.join(__dirname, 'register.html'));
});

app.listen(PORT, () => {
  console.log(`HuB Finedu rodando em http://localhost:${PORT}`);
});
