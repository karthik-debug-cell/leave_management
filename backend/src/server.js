const path = require('path');
require('dotenv').config({ path: path.join(__dirname, '..', '.env') });
const express = require('express');
const helmet = require('helmet');
const cors = require('cors');

if (!process.env.JWT_SECRET) {
  console.error('JWT_SECRET is not set. Copy .env.example to .env first.');
  process.exit(1);
}

const app = express();
app.use(helmet({
  // AngularJS + Bootstrap are loaded from CDNs; AngularJS needs 'unsafe-eval' off by default, so we allow the CDNs only
  contentSecurityPolicy: {
    directives: {
      defaultSrc: ["'self'"],
      scriptSrc: ["'self'", "'unsafe-eval'", 'https://cdnjs.cloudflare.com', 'https://cdn.jsdelivr.net'],
      styleSrc: ["'self'", "'unsafe-inline'", 'https://cdn.jsdelivr.net', 'https://fonts.googleapis.com'],
      fontSrc: ["'self'", 'https://cdn.jsdelivr.net', 'https://fonts.gstatic.com'],
      imgSrc: ["'self'", 'data:'],
      connectSrc: ["'self'", 'https://cdnjs.cloudflare.com', 'https://cdn.jsdelivr.net'],
    },
  },
}));
app.use(cors());           // fine for dev; restrict origin in production
app.use(express.json());

app.use('/api/auth', require('./routes/auth'));
app.use('/api/leaves', require('./routes/leaves'));
app.use('/api/admin', require('./routes/admin'));
app.use('/api', (req, res) => res.status(404).json({ error: 'Not found.' }));

// Serve the AngularJS frontend
const FRONTEND = path.join(__dirname, '..', '..', 'frontend');
app.use(express.static(FRONTEND));
// SPA fallback so deep links like /admin/requests work on refresh
app.get('*', (req, res) => res.sendFile(path.join(FRONTEND, 'index.html')));

// eslint-disable-next-line no-unused-vars
app.use((err, req, res, next) => {
  console.error(err);
  res.status(500).json({ error: 'Something went wrong on the server.' });
});

const port = process.env.PORT || 3000;
app.listen(port, () => console.log(`LeaveFlow running at http://localhost:${port}`));
