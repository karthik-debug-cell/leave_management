// Inclusive calendar-day count between two YYYY-MM-DD strings (matches the PHP version)
function countDays(start, end, isHalfDay = false) {
  if (isHalfDay) return 0.5;
  const ms = Date.parse(end + 'T00:00:00Z') - Date.parse(start + 'T00:00:00Z');
  return Math.round(ms / 86400000) + 1;
}

const todayISO = () => new Date().toISOString().slice(0, 10);

module.exports = { countDays, todayISO };
