const { Pool, types } = require('pg');
require('dotenv').config();

// Return DATE columns as plain 'YYYY-MM-DD' strings (avoids timezone day-shifts)
types.setTypeParser(1082, (v) => v);
// Return NUMERIC (e.g. half-day counts like 0.5) as numbers instead of strings
types.setTypeParser(1700, (v) => parseFloat(v));

const pool = new Pool({ connectionString: process.env.DATABASE_URL });
pool.on('error', (err) => console.error('Unexpected PG error', err));

module.exports = pool;
