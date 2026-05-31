-- Finova Bank - Full database schema and seed data
-- Import via phpMyAdmin or: mysql -u root < finova_bank.sql

CREATE DATABASE IF NOT EXISTS finova_bank CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE finova_bank;

-- Users
CREATE TABLE users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(100) NOT NULL UNIQUE,
    phone         VARCHAR(15) DEFAULT NULL,
    password      VARCHAR(255) DEFAULT NULL,
    google_id     VARCHAR(100) DEFAULT NULL,
    profile_pic   VARCHAR(255) DEFAULT NULL,
    auth_provider ENUM('local','google') DEFAULT 'local',
    address       TEXT,
    date_joined   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status        ENUM('active','frozen','inactive') DEFAULT 'active'
);

-- Admins
CREATE TABLE admins (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    admin_name VARCHAR(100) NOT NULL,
    email      VARCHAR(100) NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    phone      VARCHAR(15),
    role       VARCHAR(50) DEFAULT 'admin',
    status     ENUM('active','inactive') DEFAULT 'active'
);

-- Accounts
CREATE TABLE accounts (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user_id      INT NOT NULL,
    account_no   VARCHAR(20) NOT NULL UNIQUE,
    account_type ENUM('savings','current','salary') DEFAULT 'savings',
    balance      DECIMAL(12,2) DEFAULT 0.00,
    open_date    DATE NOT NULL,
    status       ENUM('active','frozen','closed') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Transactions
CREATE TABLE transactions (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    account_id       INT NOT NULL,
    transaction_type ENUM('credit','debit','transfer') NOT NULL,
    amount           DECIMAL(12,2) NOT NULL,
    description      VARCHAR(255),
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status           ENUM('success','failed','pending') DEFAULT 'success',
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

-- Transaction history (balance after each transaction)
CREATE TABLE transaction_history (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    account_id     INT NOT NULL,
    amount         DECIMAL(12,2) NOT NULL,
    balance_after  DECIMAL(12,2) NOT NULL,
    history_date   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    remarks        VARCHAR(255),
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

-- OTP tokens (2FA)
CREATE TABLE otp_tokens (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    otp_code   VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used       TINYINT(1) DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password resets
CREATE TABLE password_resets (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(100) NOT NULL,
    token      VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used       TINYINT(1) DEFAULT 0
);

-- Loan types
CREATE TABLE loan_types (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    type_name     VARCHAR(100) NOT NULL,
    min_amount    DECIMAL(12,2) DEFAULT 10000.00,
    max_amount    DECIMAL(12,2) DEFAULT 1000000.00,
    min_tenure    INT DEFAULT 6,
    max_tenure    INT DEFAULT 60,
    interest_rate DECIMAL(5,2) NOT NULL,
    is_active     TINYINT(1) DEFAULT 1
);

-- Loan applications
CREATE TABLE loan_applications (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    loan_ref         VARCHAR(20) NOT NULL UNIQUE,
    user_id          INT NOT NULL,
    account_id       INT NOT NULL,
    loan_type_id     INT NOT NULL,
    amount_requested DECIMAL(12,2) NOT NULL,
    tenure_months    INT NOT NULL,
    interest_rate    DECIMAL(5,2) NOT NULL,
    emi_amount       DECIMAL(12,2) NOT NULL,
    total_payable    DECIMAL(12,2) NOT NULL,
    total_interest   DECIMAL(12,2) NOT NULL,
    purpose          TEXT,
    status           ENUM('pending','approved','rejected','active','closed') DEFAULT 'pending',
    admin_remarks    TEXT,
    applied_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at      TIMESTAMP NULL,
    FOREIGN KEY (loan_type_id) REFERENCES loan_types(id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

-- Loan repayments
CREATE TABLE loan_repayments (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    loan_id          INT NOT NULL,
    installment_no   INT NOT NULL,
    due_date         DATE NOT NULL,
    emi_amount       DECIMAL(12,2) NOT NULL,
    principal_part   DECIMAL(12,2) NOT NULL,
    interest_part    DECIMAL(12,2) NOT NULL,
    outstanding_bal  DECIMAL(12,2) NOT NULL,
    paid_amount      DECIMAL(12,2) DEFAULT 0.00,
    paid_date        DATE NULL,
    status           ENUM('pending','paid','overdue') DEFAULT 'pending',
    FOREIGN KEY (loan_id) REFERENCES loan_applications(id) ON DELETE CASCADE
);

-- Fixed deposits
CREATE TABLE fixed_deposits (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    fd_ref          VARCHAR(20) NOT NULL UNIQUE,
    user_id         INT NOT NULL,
    account_id      INT NOT NULL,
    principal       DECIMAL(12,2) NOT NULL,
    interest_rate   DECIMAL(5,2) NOT NULL,
    tenure_months   INT NOT NULL,
    maturity_amount DECIMAL(12,2) NOT NULL,
    start_date      DATE NOT NULL,
    maturity_date   DATE NOT NULL,
    status          ENUM('active','matured','closed') DEFAULT 'active',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

-- Seed: loan types
INSERT INTO loan_types (type_name, min_amount, max_amount, min_tenure, max_tenure, interest_rate) VALUES
('Personal Loan',   10000.00,    500000.00,  6,  60,  10.50),
('Home Loan',      100000.00, 10000000.00, 12, 240,   7.25),
('Car Loan',        50000.00,  1500000.00, 12,  84,   8.75),
('Education Loan',  25000.00,  1500000.00, 12, 120,   9.00),
('Business Loan',   50000.00,  5000000.00, 12,  84,  11.50);

-- Seed: admin (password: admin123)
INSERT INTO admins (admin_name, email, password, role) VALUES
('Super Admin', 'admin@finovabank.com', '$2y$10$vK.uT8Sjks9yoa1WB4aiI.G9ZAN5mLTzwor4Wt1fQGFGhFBD9zMXm', 'admin');

-- Seed: test user (password: user123)
INSERT INTO users (full_name, email, phone, password, address, status, auth_provider) VALUES
('Akshat Sharma', 'akshat@test.com', '9876543210',
 '$2y$10$6WGR0iPxSdD8ld7ob2BsIOX7e6P9ZHIW28fx7VyRvZX.5nKj6ZcA2',
 'Pune, Maharashtra', 'active', 'local');

INSERT INTO users (full_name, email, phone, password, address, status, auth_provider) VALUES
('Priya Patel', 'priya@test.com', '9123456780',
 '$2y$10$6WGR0iPxSdD8ld7ob2BsIOX7e6P9ZHIW28fx7VyRvZX.5nKj6ZcA2',
 'Mumbai, Maharashtra', 'active', 'local');

-- Seed: accounts
INSERT INTO accounts (user_id, account_no, account_type, balance, open_date, status) VALUES
(1, 'FNV1234567', 'savings', 50000.00, CURDATE(), 'active'),
(2, 'FNV9876543', 'savings', 25000.00, CURDATE(), 'active');

-- Seed: sample transactions for user 1
INSERT INTO transactions (account_id, transaction_type, amount, description, status) VALUES
(1, 'credit', 50000.00, 'Initial deposit', 'success'),
(1, 'debit',  5000.00, 'Fund transfer to FNV9876543', 'success'),
(1, 'credit', 10000.00, 'Salary credit', 'success');

INSERT INTO transaction_history (transaction_id, account_id, amount, balance_after, remarks) VALUES
(1, 1, 50000.00, 50000.00, 'Opening balance'),
(2, 1, 5000.00, 45000.00, 'Transfer out'),
(3, 1, 10000.00, 55000.00, 'Salary credit');

-- Adjust account balance to match last history (seed had 50000 but history ends at 55000 - sync balance)
UPDATE accounts SET balance = 55000.00 WHERE id = 1;
