const router = require('express').Router();
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { body } = require('express-validator');
const pool = require('../db/pool');
const validate = require('../middleware/validate');
const { requireLogin } = require('../middleware/auth');

router.post('/login',
  body('email').isEmail().withMessage('Enter a valid email.').normalizeEmail(),
  body('password').notEmpty().withMessage('Password is required.'),
  validate,
  async (req, res, next) => {
    try {
      const { email, password } = req.body;
      const { rows } = await pool.query('SELECT * FROM users WHERE email = $1', [email]);
      const user = rows[0];
      const ok = user && await bcrypt.compare(password, user.password);
      if (!ok) return res.status(401).json({ error: 'Invalid email or password.' });

      const payload = { id: user.id, name: user.name, email: user.email, role: user.role };
      const token = jwt.sign(payload, process.env.JWT_SECRET, {
        expiresIn: process.env.JWT_EXPIRES_IN || '8h',
      });
      res.json({ token, user: { ...payload, department: user.department } });
    } catch (e) { next(e); }
  });

router.get('/me', requireLogin, async (req, res, next) => {
  try {
    const { rows } = await pool.query(
      'SELECT id, name, email, role, department FROM users WHERE id = $1', [req.user.id]);
    if (!rows[0]) return res.status(401).json({ error: 'User no longer exists.' });
    res.json(rows[0]);
  } catch (e) { next(e); }
});

module.exports = router;
