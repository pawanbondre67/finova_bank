# Finova Bank

Web-based Finance and Banking Management System (PHP, MySQL, HTML/CSS/JS).

## Setup (XAMPP)

1. Start **Apache** and **MySQL** in XAMPP Control Panel.
2. Import database:
   - Open phpMyAdmin → Import → `database/finova_bank.sql`
   - Or CLI: `mysql -u root < database/finova_bank.sql`
3. Install PHP dependencies:
   ```bash
   cd finova_bank
   php composer.phar install
   ```
4. Configure:
   - `config/db.php` — MySQL credentials (default: root, no password)
   - `config/mail.php` — Gmail SMTP for OTP and notifications
   - `config/google.php` — Google OAuth (optional)
5. Open: **http://localhost/finova_bank/**

## Test credentials

| Role   | Email                 | Password  |
|--------|-----------------------|-----------|
| User   | akshat@test.com       | user123   |
| User 2 | priya@test.com        | user123   |
| Admin  | admin@finovabank.com  | admin123  |

Account numbers: `FNV1234567`, `FNV9876543`

## Features

- Registration, login with OTP 2FA, password reset
- Google OAuth login (optional)
- Fund transfer, history, balance, profile
- Loans with EMI calculator, admin approval
- Fixed deposits
- PDF statements (FPDF)
- Admin dashboard with Chart.js

## URL base

If the project path differs, edit `config/app.php` → `APP_BASE`.
