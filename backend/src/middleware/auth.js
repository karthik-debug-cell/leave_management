const jwt = require('jsonwebtoken');

function requireLogin(req, res, next) {
  const header = req.headers.authorization || '';
  const token = header.startsWith('Bearer ') ? header.slice(7) : null;
  if (!token) return res.status(401).json({ error: 'Not authenticated.' });
  try {
    req.user = jwt.verify(token, process.env.JWT_SECRET);
    next();
  } catch {
    res.status(401).json({ error: 'Session expired. Please log in again.' });
  }
}

const requireRole = (role) => (req, res, next) =>
  req.user && req.user.role === role
    ? next()
    : res.status(403).json({ error: 'You do not have permission to do this.' });

module.exports = {
  requireLogin,
  requireAdmin: [requireLogin, requireRole('admin')],
  requireEmployee: [requireLogin, requireRole('employee')],
};
