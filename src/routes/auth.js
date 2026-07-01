const { Router } = require('express');
const passport = require('../auth/oauth2');
const { isLoggedIn } = require('../middleware/auth');
const { sendLoginConfirmation } = require('../services/email');

const router = Router();

const basePath = (() => {
  try { return new URL(process.env.BASE_URL || 'http://localhost:3000').pathname.replace(/\/+$/, ''); }
  catch { return ''; }
})();

router.get('/google', passport.authenticate('google', {
  scope: ['profile', 'email']
}));

router.get('/google/callback',
  passport.authenticate('google', { failureRedirect: basePath + '/login' }),
  async (req, res) => {
    try {
      await sendLoginConfirmation(req.user);
    } catch (err) {
      console.error('Erro ao enviar email de confirmação:', err.message);
    }
    res.redirect(basePath + '/dashboard');
  }
);

router.get('/status', (req, res) => {
  if (req.isAuthenticated()) {
    return res.json({ authenticated: true, user: req.user });
  }
  res.json({ authenticated: false });
});

router.get('/logout', (req, res, next) => {
  req.logout((err) => {
    if (err) return next(err);
    res.redirect(basePath + '/');
  });
});

module.exports = router;
