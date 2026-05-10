-- ============================================================
-- LeaveFlow — Database Setup Script
-- Run this entire script in phpMyAdmin > SQL tab
-- ============================================================

CREATE DATABASE IF NOT EXISTS leave_management
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE leave_management;

DROP TABLE IF EXISTS leaves;
DROP TABLE IF EXISTS users;

-- ============================================================
-- USERS table — role column is what controls the dashboard
-- 'admin' sees admin dashboard, 'employee' sees employee dashboard
-- ============================================================
CREATE TABLE users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)             NOT NULL,
    email      VARCHAR(150)             NOT NULL UNIQUE,
    password   VARCHAR(255)             NOT NULL,
    role       ENUM('admin','employee') NOT NULL DEFAULT 'employee',
    department VARCHAR(100)             DEFAULT NULL,
    created_at TIMESTAMP                DEFAULT CURRENT_TIMESTAMP
);

-- ============================================================
-- LEAVES table
-- ============================================================
CREATE TABLE leaves (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    user_id       INT NOT NULL,
    leave_type    ENUM('Annual','Sick','Casual','Maternity','Paternity','Unpaid') NOT NULL,
    start_date    DATE NOT NULL,
    end_date      DATE NOT NULL,
    reason        TEXT NOT NULL,
    status        ENUM('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
    admin_comment TEXT DEFAULT NULL,
    applied_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- ============================================================
-- SEED USERS
-- admin@company.com  password: Admin@123
-- john@company.com   password: Emp@123
-- sara@company.com   password: Emp@123
-- ravi@company.com   password: Emp@123
-- priya@company.com  password: Emp@123
-- ============================================================
INSERT INTO users (name, email, password, role, department) VALUES
('Super Admin',   'admin@company.com', '$2b$12$AoFAlfZ5aC9/wnacbRCiQOxV0yqINUvDmQMt20RHp2WXLMNDsaKwO', 'admin',    'HR'),
('John Employee', 'john@company.com',  '$2b$12$AOuphjThpGyCv0oEDg0tGOJSgi1z1qd4fZkvjECAw9z0l/EcMEkOy', 'employee', 'Engineering'),
('Sara Mathew',   'sara@company.com',  '$2b$12$xOb.78YISUMdKmzFKSF/4eTrvAmn9i82yNH0ontmnhQoCBme4TsSm', 'employee', 'Design'),
('Ravi Kumar',    'ravi@company.com',  '$2b$12$6JMCxFyb8V4ncFv4j7Ekg.9s4AHzNNcME.doHeyKW9FWc5mXNMvOe', 'employee', 'Sales'),
('Priya Nair',    'priya@company.com', '$2b$12$vVkZac86F.94TdwQsZn.tujAKPmPxcYQTDL8mjSMsEQNeZTA2rsAW', 'employee', 'Marketing');

-- Sample leave records
INSERT INTO leaves (user_id, leave_type, start_date, end_date, reason, status, admin_comment) VALUES
(2, 'Annual', '2026-04-20', '2026-04-22', 'Family vacation',       'Pending',  NULL),
(2, 'Sick',   '2026-03-10', '2026-03-11', 'Fever and cold',        'Approved', 'Get well soon'),
(2, 'Casual', '2026-02-14', '2026-02-14', 'Personal work',         'Rejected', 'Insufficient reason'),
(3, 'Annual', '2026-04-25', '2026-04-28', 'Wedding attendance',    'Pending',  NULL),
(4, 'Sick',   '2026-04-10', '2026-04-12', 'Medical procedure',     'Approved', 'Approved. Take rest.'),
(5, 'Casual', '2026-04-15', '2026-04-15', 'Bank work',             'Pending',  NULL);

-- ============================================================
-- TO ADD MORE EMPLOYEES LATER, run this in phpMyAdmin SQL tab:
-- (password below = Emp@123)
-- ============================================================
-- INSERT INTO users (name, email, password, role, department) VALUES
-- ('New Name', 'email@company.com',
--  '$2b$12$AOuphjThpGyCv0oEDg0tGOJSgi1z1qd4fZkvjECAw9z0l/EcMEkOy',
--  'employee', 'Department');
