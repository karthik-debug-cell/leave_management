// Employee-facing leave endpoints
const router = require('express').Router();
const { body, param } = require('express-validator');
const pool = require('../db/pool');
const validate = require('../middleware/validate');
const { requireEmployee } = require('../middleware/auth');
const { countDays, todayISO } = require('../utils');

router.use(requireEmployee);

const isDate = (v) => /^\d{4}-\d{2}-\d{2}$/.test(v) && !isNaN(Date.parse(v));

// ---- Leave types with this employee's balance for the current year ----
async function getBalances(userId) {
  const year = new Date().getFullYear();
  const { rows } = await pool.query(`
    SELECT t.name AS leave_type, t.yearly_quota AS quota,
      COALESCE(SUM(l.days) FILTER (WHERE l.status = 'Approved'), 0) AS used,
      COALESCE(SUM(l.days) FILTER (WHERE l.status = 'Pending'),  0) AS pending
    FROM leave_types t
    LEFT JOIN leaves l ON l.leave_type = t.name AND l.user_id = $1
                      AND EXTRACT(YEAR FROM l.start_date) = $2
    GROUP BY t.name, t.yearly_quota
    ORDER BY t.name`, [userId, year]);
  return rows.map((r) => {
    const quota = r.quota === null ? null : Number(r.quota);
    const used = Number(r.used), pending = Number(r.pending);
    return { leave_type: r.leave_type, quota, used, pending,
             remaining: quota === null ? null : quota - used - pending };
  });
}

router.get('/types', async (req, res, next) => {
  try { res.json(await getBalances(req.user.id)); } catch (e) { next(e); }
});

// ---- Dashboard ----
router.get('/dashboard', async (req, res, next) => {
  try {
    const stats = await pool.query(`
      SELECT COUNT(*)::int AS total,
        COUNT(*) FILTER (WHERE status='Pending')::int   AS pending,
        COUNT(*) FILTER (WHERE status='Approved')::int  AS approved,
        COUNT(*) FILTER (WHERE status='Rejected')::int  AS rejected
      FROM leaves WHERE user_id = $1`, [req.user.id]);
    const recent = await pool.query(
      'SELECT * FROM leaves WHERE user_id = $1 ORDER BY applied_at DESC LIMIT 5', [req.user.id]);
    res.json({ stats: stats.rows[0], recent: recent.rows, balances: await getBalances(req.user.id) });
  } catch (e) { next(e); }
});

// ---- History (optional ?status= filter) ----
router.get('/', async (req, res, next) => {
  try {
    const params = [req.user.id];
    let sql = 'SELECT * FROM leaves WHERE user_id = $1';
    if (['Pending', 'Approved', 'Rejected', 'Cancelled'].includes(req.query.status)) {
      params.push(req.query.status);
      sql += ' AND status = $2';
    }
    const { rows } = await pool.query(sql + ' ORDER BY applied_at DESC', params);
    res.json(rows);
  } catch (e) { next(e); }
});

// ---- Apply for leave ----
router.post('/',
  body('leave_type').trim().notEmpty().withMessage('Leave type is required.'),
  body('start_date').custom(isDate).withMessage('A valid start date is required.'),
  body('end_date').custom(isDate).withMessage('A valid end date is required.'),
  body('reason').trim().notEmpty().withMessage('Reason is required.')
    .isLength({ max: 1000 }).withMessage('Reason is too long (max 1000 characters).'),
  body('is_half_day').optional().isBoolean().withMessage('Invalid half-day option.'),
  body('half_day_session').optional({ nullable: true, checkFalsy: true })
    .isIn(['First Half', 'Second Half']).withMessage('Invalid half-day session.'),
  body('contact_number').optional({ nullable: true, checkFalsy: true })
    .matches(/^[0-9+()\-\s]{6,20}$/).withMessage('Invalid contact number.'),
  validate,
  async (req, res, next) => {
    try {
      const { leave_type, start_date, reason } = req.body;
      const isHalf = req.body.is_half_day === true || req.body.is_half_day === 'true';
      // A half day is always a single date
      const end_date = isHalf ? start_date : req.body.end_date;
      const session = isHalf ? (req.body.half_day_session || 'First Half') : null;

      if (end_date < start_date) return res.status(400).json({ error: 'End date cannot be before start date.' });
      if (start_date < todayISO()) return res.status(400).json({ error: 'Start date cannot be in the past.' });

      const type = await pool.query('SELECT * FROM leave_types WHERE name = $1', [leave_type]);
      if (!type.rows[0]) return res.status(400).json({ error: 'Invalid leave type selected.' });

      const days = countDays(start_date, end_date, isHalf);

      // Overlap with an existing Pending/Approved request
      const overlap = await pool.query(`
        SELECT id FROM leaves
        WHERE user_id = $1 AND status IN ('Pending','Approved')
          AND start_date <= $3 AND end_date >= $2
          AND NOT (is_half_day AND $4 AND start_date = $2 AND half_day_session <> $5)
        LIMIT 1`, [req.user.id, start_date, end_date, isHalf, session]);
      if (overlap.rows[0]) {
        return res.status(409).json({ error: 'You already have a leave request covering these dates.' });
      }

      // Balance check (unlimited types have NULL quota)
      const bal = (await getBalances(req.user.id)).find((b) => b.leave_type === leave_type);
      if (bal && bal.remaining !== null && days > bal.remaining) {
        return res.status(400).json({
          error: `Insufficient ${leave_type} leave balance. Remaining: ${bal.remaining} day(s), requested: ${days}.`,
        });
      }

      const { rows } = await pool.query(`
        INSERT INTO leaves (user_id, leave_type, start_date, end_date, is_half_day,
                            half_day_session, days, reason, contact_number)
        VALUES ($1,$2,$3,$4,$5,$6,$7,$8,$9) RETURNING *`,
        [req.user.id, leave_type, start_date, end_date, isHalf, session, days,
         reason, req.body.contact_number || null]);
      res.status(201).json(rows[0]);
    } catch (e) { next(e); }
  });

// ---- Cancel own pending request ----
router.patch('/:id/cancel',
  param('id').isInt().withMessage('Invalid request id.'), validate,
  async (req, res, next) => {
    try {
      const { rows } = await pool.query(`
        UPDATE leaves SET status = 'Cancelled', updated_at = NOW()
        WHERE id = $1 AND user_id = $2 AND status = 'Pending' RETURNING *`,
        [req.params.id, req.user.id]);
      if (!rows[0]) return res.status(400).json({ error: 'Only your own pending requests can be cancelled.' });
      res.json(rows[0]);
    } catch (e) { next(e); }
  });

module.exports = router;
