# LeaveFlow — AngularJS + Express + PostgreSQL

LeaveFlow Employee Leave Management System.

| Layer    | Stack                                 |
|----------|---------------------------------------|
| Frontend | AngularJS 1.8 SPA (ngRoute, Bootstrap 5) |
| Backend  | Express.js REST API + JWT             |
| Database | PostgreSQL (`pg`)                     |

## Run it
```bash
# 1. Create the database
createdb leave_management            # or: psql -c "CREATE DATABASE leave_management"

# 2. Backend
cd backend
npm install
cp .env.example .env                 # edit DATABASE_URL and JWT_SECRET
npm run db:init                      # creates tables + seed data
npm start                            # http://localhost:3000  (also serves the frontend)
```
Open http://localhost:3000

Demo logins:
- Admin: `admin@company.com` / `Admin@123`
- Employee: `john@company.com` / `Emp@123`

## API
| Method | Path | Role |
|---|---|---|
| POST | `/api/auth/login` | public |
| GET | `/api/auth/me` | any |
| GET | `/api/leaves/dashboard`, `/api/leaves/types`, `/api/leaves?status=` | employee |
| POST | `/api/leaves` (apply) | employee |
| PATCH | `/api/leaves/:id/cancel` | employee |
| GET | `/api/admin/dashboard`, `/requests`, `/requests/:id`, `/employees`, `/reports` | admin |
| PATCH | `/api/admin/requests/:id` (approve / reject) | admin |
| POST | `/api/admin/employees` | admin |

## Features
- **Half-day leave** (first / second half) — counts as 0.5 day
- **Live leave balance** per type (yearly quotas in the `leave_types` table; Unpaid is unlimited)
- **Balance check** — blocked if the request exceeds the remaining days (pending requests count)
- **Overlap protection** — can't double-book dates (two opposite half-days on one date are allowed)
- **Cancel** your own pending request (new `Cancelled` status)
- Optional **contact number** during leave, reason character counter
- `days` is stored on each request, so reports sum correctly (including half days)

## Notes
- Change a quota: `UPDATE leave_types SET yearly_quota = 25 WHERE name = 'Annual';`
- Tokens live in `localStorage` for 8h (`JWT_EXPIRES_IN`). Set a strong `JWT_SECRET` in production.
