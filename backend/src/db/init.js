// Usage: npm run db:init   (drops & recreates tables, loads seed data)
const fs = require('fs');
const path = require('path');
const pool = require('./pool');

(async () => {
  try {
    const sql = fs.readFileSync(path.join(__dirname, 'schema.sql'), 'utf8');
    await pool.query(sql);
    console.log('Database initialised with schema and seed data.');
  } catch (e) {
    console.error('DB init failed:', e.message);
    process.exitCode = 1;
  } finally {
    await pool.end();
  }
})();
