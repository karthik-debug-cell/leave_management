-- ============================================================
-- LeaveFlow — PostgreSQL schema
-- ============================================================
DROP TABLE IF EXISTS leaves CASCADE;
DROP TABLE IF EXISTS leave_types CASCADE;
DROP TABLE IF EXISTS users CASCADE;

CREATE TABLE users (
  id          SERIAL PRIMARY KEY,
  name        VARCHAR(100) NOT NULL,
  email       VARCHAR(150) NOT NULL UNIQUE,
  password    VARCHAR(255) NOT NULL,
  role        VARCHAR(10)  NOT NULL DEFAULT 'employee' CHECK (role IN ('admin','employee')),
  department  VARCHAR(100),
  created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

-- Yearly quota per leave type (NULL = unlimited)
CREATE TABLE leave_types (
  name         VARCHAR(30) PRIMARY KEY,
  yearly_quota NUMERIC(5,1)
);

CREATE TABLE leaves (
  id               SERIAL PRIMARY KEY,
  user_id          INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
  leave_type       VARCHAR(30) NOT NULL REFERENCES leave_types(name),
  start_date       DATE NOT NULL,
  end_date         DATE NOT NULL,
  is_half_day      BOOLEAN NOT NULL DEFAULT FALSE,
  half_day_session VARCHAR(12) CHECK (half_day_session IN ('First Half','Second Half')),
  days             NUMERIC(5,1) NOT NULL,
  reason           TEXT NOT NULL,
  contact_number   VARCHAR(30),
  status           VARCHAR(10) NOT NULL DEFAULT 'Pending'
                   CHECK (status IN ('Pending','Approved','Rejected','Cancelled')),
  admin_comment    TEXT,
  applied_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  updated_at       TIMESTAMPTZ NOT NULL DEFAULT NOW(),
  CHECK (end_date >= start_date),
  CHECK (NOT is_half_day OR start_date = end_date)
);

CREATE INDEX idx_leaves_user   ON leaves(user_id);
CREATE INDEX idx_leaves_status ON leaves(status);
CREATE INDEX idx_leaves_dates  ON leaves(start_date, end_date);

INSERT INTO leave_types (name, yearly_quota) VALUES
  ('Annual', 20), ('Sick', 10), ('Casual', 8),
  ('Maternity', 90), ('Paternity', 10), ('Unpaid', NULL);

-- Seed users (bcrypt hashes are the same as the PHP version)
-- admin@company.com / Admin@123 ; others / Emp@123
INSERT INTO users (name, email, password, role, department) VALUES
('Super Admin',   'admin@company.com', '$2b$12$AoFAlfZ5aC9/wnacbRCiQOxV0yqINUvDmQMt20RHp2WXLMNDsaKwO', 'admin',    'HR'),
('John Employee', 'john@company.com',  '$2b$12$AOuphjThpGyCv0oEDg0tGOJSgi1z1qd4fZkvjECAw9z0l/EcMEkOy', 'employee', 'Engineering'),
('Sara Mathew',   'sara@company.com',  '$2b$12$xOb.78YISUMdKmzFKSF/4eTrvAmn9i82yNH0ontmnhQoCBme4TsSm', 'employee', 'Design'),
('Ravi Kumar',    'ravi@company.com',  '$2b$12$6JMCxFyb8V4ncFv4j7Ekg.9s4AHzNNcME.doHeyKW9FWc5mXNMvOe', 'employee', 'Sales'),
('Priya Nair',    'priya@company.com', '$2b$12$vVkZac86F.94TdwQsZn.tujAKPmPxcYQTDL8mjSMsEQNeZTA2rsAW', 'employee', 'Marketing');

INSERT INTO leaves (user_id, leave_type, start_date, end_date, days, reason, status, admin_comment) VALUES
(2, 'Annual', '2026-12-20', '2026-12-22', 3, 'Family vacation',    'Pending',  NULL),
(2, 'Sick',   '2026-03-10', '2026-03-11', 2, 'Fever and cold',     'Approved', 'Get well soon'),
(2, 'Casual', '2026-02-14', '2026-02-14', 1, 'Personal work',      'Rejected', 'Insufficient reason'),
(3, 'Annual', '2026-12-25', '2026-12-28', 4, 'Wedding attendance', 'Pending',  NULL),
(4, 'Sick',   '2026-04-10', '2026-04-12', 3, 'Medical procedure',  'Approved', 'Approved. Take rest.'),
(5, 'Casual', '2026-12-15', '2026-12-15', 1, 'Bank work',          'Pending',  NULL);
