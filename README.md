# 🗓️ LeaveFlow — Employee Leave Management System

A fully functional web-based Employee Leave Management System built with **PHP**, **MySQL**, **Bootstrap 5**, and **vanilla JavaScript**. Designed to run on a local XAMPP server, it digitizes the entire leave request lifecycle — from application to approval and reporting.

---

## 📸 Screenshots

| Admin Dashboard | Employee Dashboard |
|---|---|
| ![Admin Dashboard](assets/screenshots/admin_dashboard.png) | ![Employee Dashboard](assets/screenshots/employee_dashboard.png) |

| Leave Requests | Review Request |
|---|---|
| ![Leave Requests](assets/screenshots/leave_requests.png) | ![Review](assets/screenshots/review_request.png) |

---

## 🚀 Features

### 👤 Employee
- Secure login with role-based session authentication
- Personal dashboard with live stats — Total, Pending, Approved, Rejected
- Apply for leave with type, date range, and reason
- Real-time duration calculator when selecting dates
- View full leave history with status badges
- See admin comments on approved or rejected requests

### 🛡️ Admin
- Admin dashboard with organization-wide statistics
- View all employee leave requests in one place
- Filter requests by employee, status, leave type, from date, and to date
- Review individual requests with full employee details
- Approve or reject requests with an optional comment
- Manage employee accounts — view department, leave counts
- Add new employees directly from the admin panel
- Generate leave reports filtered by employee and date range

### 🔐 Security
- Passwords hashed using **bcrypt** via `password_hash()` and verified with `password_verify()`
- All SQL queries use **PDO prepared statements** — fully protected against SQL injection
- Every protected page enforces role checks via `requireAdmin()` or `requireEmployee()`
- All user output escaped with `htmlspecialchars()` — prevents XSS attacks
- Session ID regenerated on login to prevent session fixation

---

## 🛠️ Technology Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8 |
| Database | MySQL 5.7+ |
| Frontend | HTML5, CSS3, Bootstrap 5.3 |
| Icons | Bootstrap Icons 1.11 |
| Fonts | Google Fonts — DM Sans, Fraunces |
| JavaScript | Vanilla JS (date validation, alerts) |
| Local Server | XAMPP (Apache + MySQL) |

---

## 📁 Project Structure

```
leave_management/
│
├── index.php                          # Root redirect to login
├── database.sql                       # Full database setup script
├── README.md                          # Project documentation
│
├── config/
│   ├── db.php                         # PDO database connection (singleton)
│   └── session.php                    # Session start, role guards, flash messages
│
├── auth/
│   ├── login.php                      # Login form + authentication logic
│   ├── logout.php                     # Session destroy + redirect
│   └── register.php                   # Admin-only: create new employee accounts
│
├── admin/
│   ├── dashboard.php                  # Admin home — org-wide stats + recent requests
│   ├── requests.php                   # All leave requests with filters
│   ├── view_request.php               # Review single request + approve/reject
│   ├── employees.php                  # Employee list with leave counts
│   └── reports.php                    # Approved leave reports by date/employee
│
├── employee/
│   ├── dashboard.php                  # Employee home — personal stats + recent leaves
│   ├── apply.php                      # Leave application form
│   └── history.php                    # Full leave history with status filter
│
├── components/
│   ├── head.php                       # Shared HTML <head> — CSS imports
│   ├── navbar.php                     # Role-aware navigation bar
│   ├── flash.php                      # Flash message display component
│   └── footer.php                     # Shared footer — JS imports
│
└── assets/
    ├── css/
    │   └── style.css                  # Global custom stylesheet
    └── js/
        └── app.js                     # Date validation, alerts, animations
```

---

## 🗄️ Database Schema

### `users` table

| Column | Type | Description |
|---|---|---|
| id | INT (PK) | Auto increment primary key |
| name | VARCHAR(100) | Employee full name |
| email | VARCHAR(150) | Unique login email |
| password | VARCHAR(255) | Bcrypt hashed password |
| role | ENUM | `admin` or `employee` |
| department | VARCHAR(100) | Department name |
| created_at | TIMESTAMP | Account creation time |

### `leaves` table

| Column | Type | Description |
|---|---|---|
| id | INT (PK) | Auto increment primary key |
| user_id | INT (FK) | References `users.id` |
| leave_type | ENUM | Annual / Sick / Casual / Maternity / Paternity / Unpaid |
| start_date | DATE | Leave start date |
| end_date | DATE | Leave end date |
| reason | TEXT | Employee's stated reason |
| status | ENUM | `Pending` / `Approved` / `Rejected` |
| admin_comment | TEXT | Optional admin note on decision |
| applied_at | TIMESTAMP | When the request was submitted |
| updated_at | TIMESTAMP | Last status update time |

---

## ⚙️ Installation & Setup

### Prerequisites
- [XAMPP](https://www.apachefriends.org/) installed (Apache + MySQL)
- A modern web browser

---

### Step 1 — Clone or Download the Repository

**Option A — Clone with Git:**
```bash
cd C:\xampp\htdocs
git clone https://github.com/YOURUSERNAME/leave_management.git
```

**Option B — Download ZIP:**
- Click the green **Code** button on this page
- Click **Download ZIP**
- Extract it inside `C:\xampp\htdocs\`
- Make sure the folder is named `leave_management`

---

### Step 2 — Start XAMPP

Open the XAMPP Control Panel and start both:
- **Apache**
- **MySQL**

---

### Step 3 — Create the Database

1. Open your browser and go to `http://localhost/phpmyadmin`
2. Click **New** in the left sidebar
3. Enter the name `leave_management` and click **Create**
4. Click on the new database in the left sidebar
5. Click the **SQL** tab at the top
6. Open the `database.sql` file from this project, copy everything inside it
7. Paste it into the SQL tab and click **Go**

You should see a success message. The database now has the `users` and `leaves` tables with sample data.

---

### Step 4 — Configure Database Credentials

Open `config/db.php` and update the values if your MySQL setup is different:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Your MySQL username
define('DB_PASS', '');           // Your MySQL password (blank for default XAMPP)
define('DB_NAME', 'leave_management');
```

Default XAMPP uses `root` with no password — so you likely do not need to change anything.

---

### Step 5 — Open the Application

Go to this URL in your browser:

```
http://localhost/leave_management/
```

You will be automatically redirected to the login page.

---

## 🔑 Demo Login Credentials

| Role | Email | Password |
|---|---|---|
| Admin | admin@company.com | Admin@123 |
| Employee | john@company.com | Emp@123 |
| Employee | sara@company.com | Emp@123 |
| Employee | ravi@company.com | Emp@123 |
| Employee | priya@company.com | Emp@123 |

---

## 🔄 How the System Works

### Login Flow
1. User submits email and password
2. PHP fetches the user record by email using a prepared statement
3. `password_verify()` checks the submitted password against the stored bcrypt hash
4. On success, `user_id`, `name`, `email`, and `role` are stored in the PHP session
5. Admin is redirected to `/admin/dashboard.php`
6. Employee is redirected to `/employee/dashboard.php`

### Leave Application Flow
1. Employee fills the apply form — leave type, start date, end date, reason
2. PHP validates all fields and checks that end date is not before start date
3. A new row is inserted into the `leaves` table with `status = 'Pending'`
4. Employee is redirected to their history page where the new request appears immediately

### Approval Flow
1. Admin opens Leave Requests and sees all pending requests
2. Admin clicks Review on any request to open the detail page
3. Admin optionally types a comment, then clicks Approve or Reject
4. PHP updates the `status` column in the database to `Approved` or `Rejected`
5. The employee sees the updated status and admin comment on their history page

---

## ➕ How to Add More Employees

### Option A — Via Admin Panel (Recommended)
1. Log in as Admin
2. Go to **Employees** in the navbar
3. Click **Add Employee**
4. Fill in the name, email, password, role, and department
5. Click **Create User** — password is automatically hashed

### Option B — Via SQL in phpMyAdmin

Run this query in the phpMyAdmin SQL tab. The hash below gives the password `Emp@123`:

```sql
INSERT INTO users (name, email, password, role, department) VALUES (
  'New Employee Name',
  'newemail@company.com',
  '$2b$12$AOuphjThpGyCv0oEDg0tGOJSgi1z1qd4fZkvjECAw9z0l/EcMEkOy',
  'employee',
  'Department Name'
);
```

---

## 🔒 Security Implementation Details

| Threat | Protection Used |
|---|---|
| SQL Injection | PDO prepared statements on every query |
| Password theft | bcrypt hashing — never stored as plain text |
| XSS | `htmlspecialchars()` on all user output |
| Unauthorized access | `requireAdmin()` / `requireEmployee()` on every page |
| Session fixation | `session_regenerate_id(true)` called on login |
| Role confusion | Role stored in session and validated on every request |

---

## 📤 Pushing to GitHub

```bash
cd C:\xampp\htdocs\leave_management
git init
git add .
git commit -m "Initial commit - Employee Leave Management System"
git branch -M main
git remote add origin https://github.com/YOURUSERNAME/leave_management.git
git push -u origin main
```

---

## 🙏 Acknowledgements

- [Bootstrap 5](https://getbootstrap.com/) — UI framework
- [Bootstrap Icons](https://icons.getbootstrap.com/) — Icon library
- [Google Fonts](https://fonts.google.com/) — DM Sans and Fraunces typefaces
- [XAMPP](https://www.apachefriends.org/) — Local development server

---

## 📄 License

This project is submitted as an academic project for the Department of Information Technology.  
© 2026 — All rights reserved.
