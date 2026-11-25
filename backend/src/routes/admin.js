// Admin endpoints: dashboard, requests, review, employees, reports
const router = require('express').Router();
const bcrypt = require('bcryptjs');
const { body, param } = require('express-validator');
const pool = require('../db/pool');
const validate = require('../middleware/validate');
const { requireAdmin } = require('../middleware/auth');

router.use(requireAdmin);

router.get('/dashboard', async (req, res, next) => {
  try {
    const stats = await pool.query(`
      SELECT (SELECT COUNT(*)::int FROM users WHERE role='employee') AS employees,
        COUNT(*)::int AS total,
        COUNT(*) FILTER (WHERE status='Pending')::int  AS pending,
        COUNT(*) FILTER (WHERE status='Approved')::int AS approved,
        COUNT(*) FILTER (WHERE status='Rejected')::int AS rejected
      FROM leaves`);
    const recent = await pool.query(`
      SELECT l.*, u.name AS employee_name, u.department
      FROM leaves l JOIN users u ON u.id = l.user_id
      ORDER BY l.applied_at DESC LIMIT 10`);
    res.json({ stats: stats.rows[0], recent: recent.rows });
  } catch (e) { next(e); }
});

// ---- Filterable request list ----
router.get('/requests', async (req, res, next) => {
  try {
    const where = ['1=1'], params = [];
    const add = (cond, val) => { params.push(val); where.push(cond.replace('?', '$' + params.length)); };
    const { status, user_id, leave_type, from, to } = req.query;
    if (['Pending', 'Approved', 'Rejected', 'Cancelled'].includes(status)) add('l.status = ?', status);
    if (user_id && /^\d+$/.test(user_id)) add('l.user_id = ?', Number(user_id));
    if (leave_type) add('l.leave_type = ?', leave_type);
    if (from) add('l.start_date >= ?', from);
    if (to) add('l.end_date <= ?', to);
    const { rows } = await pool.query(`
      SELECT l.*, u.name AS employee_name, u.department
      FROM leaves l JOIN users u ON u.id = l.user_id
      WHERE ${where.join(' AND ')} ORDER BY l.applied_at DESC`, params);
    res.json(rows);
  } catch (e) { next(e); }
});

router.get('/requests/:id', param('id').isInt(), validate, async (req, res, next) => {
  try {
    const { rows } = await pool.query(`
      SELECT l.*, u.name AS employee_name, u.email AS employee_email, u.department
      FROM leaves l JOIN users u ON u.id = l.user_id WHERE l.id = $1`, [req.params.id]);
    if (!rows[0]) return res.status(404).json({ error: 'Leave request not found.' });
    res.json(rows[0]);
  } catch (e) { next(e); }
});

// ---- Approve / Reject ----
router.patch('/requests/:id',
  param('id').isInt(),
  body('status').isIn(['Approved', 'Rejected']).withMessage('Status must be Approved or Rejected.'),
  body('admin_comment').optional({ nullable: true }).isLength({ max: 1000 }),
  validate,
  async (req, res, next) => {
    try {
      const { rows } = await pool.query(`
        UPDATE leaves SET status = $1, admin_comment = $2, updated_at = NOW()
        WHERE id = $3 AND status = 'Pending' RETURNING *`,
        [req.body.status, (req.body.admin_comment || '').trim() || null, req.params.id]);
      if (!rows[0]) {
        return res.status(400).json({ error: 'Request not found or it has already been reviewed/cancelled.' });
      }
      res.json(rows[0]);
    } catch (e) { next(e); }
  });

// ---- Employees ----
router.get('/employees', async (req, res, next) => {
  try {
    const { rows } = await pool.query(`
      SELECT u.id, u.name, u.email, u.department, u.created_at,
        COUNT(l.id)::int AS total_leaves,
        COUNT(l.id) FILTER (WHERE l.status='Pending')::int  AS pending,
        COUNT(l.id) FILTER (WHERE l.status='Approved')::int AS approved
      FROM users u LEFT JOIN leaves l ON l.user_id = u.id
      WHERE u.role = 'employee' GROUP BY u.id ORDER BY u.name`);
    res.json(rows);
  } catch (e) { next(e); }
});

router.post('/employees',
  body('name').trim().notEmpty().withMessage('Name is required.'),
  body('email').isEmail().withMessage('Invalid email address.').normalizeEmail(),
  body('password').isLength({ min: 6 }).withMessage('Password must be at least 6 characters.'),
  body('role').optional().isIn(['admin', 'employee']).withMessage('Invalid role selected.'),
  validate,
  async (req, res, next) => {
    try {
      const { name, email, password, department } = req.body;
      const hash = await bcrypt.hash(password, 12);
      const { rows } = await pool.query(`
        INSERT INTO users (name, email, password, role, department)
        VALUES ($1,$2,$3,$4,$5) RETURNING id, name, email, role, department`,
        [name, email, hash, req.body.role || 'employee', department || null]);
      res.status(201).json(rows[0]);
    } catch (e) {
      if (e.code === '23505') return res.status(409).json({ error: 'A user with this email already exists.' });
      next(e);
    }
  });

// ---- Reports (approved leaves, by employee + date range) ----
router.get('/reports', async (req, res, next) => {
  try {
    const now = new Date();
    const from = req.query.from || new Date(now.getFullYear(), now.getMonth(), 1).toLocaleDateString('en-CA');
    const to = req.query.to || now.toLocaleDateString('en-CA');
    const where = ["l.status = 'Approved'", 'l.start_date >= $1', 'l.end_date <= $2'];
    const params = [from, to];
    if (req.query.user_id && /^\d+$/.test(req.query.user_id)) {
      params.push(Number(req.query.user_id));
      where.push('l.user_id = $3');
    }
    const w = where.join(' AND ');
    const summary = await pool.query(`
      SELECT l.leave_type, COUNT(*)::int AS count, SUM(l.days)::float AS total_days
      FROM leaves l WHERE ${w} GROUP BY l.leave_type ORDER BY l.leave_type`, params);
    const details = await pool.query(`
      SELECT l.*, u.name AS employee_name, u.department
      FROM leaves l JOIN users u ON u.id = l.user_id
      WHERE ${w} ORDER BY l.start_date DESC`, params);
    const totalDays = summary.rows.reduce((s, r) => s + r.total_days, 0);
    res.json({ from, to, summary: summary.rows, details: details.rows, totalDays });
  } catch (e) { next(e); }
});

module.exports = router;
