-- ============================================================
-- ComplaintBox — College Complaint Management System
-- MySQL Database Schema
-- Install: import this file in phpMyAdmin
-- ============================================================

CREATE DATABASE IF NOT EXISTS complaintbox
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE complaintbox;

-- ------------------------------------------------------------
-- Users table
-- Stores students, employees and administrators.
-- status: active | inactive  (soft deactivation preserves records)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name  VARCHAR(120) NOT NULL,
  email      VARCHAR(120) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  phone      VARCHAR(15)  NOT NULL,
  role       ENUM('super_admin','admin','employee','student') NOT NULL DEFAULT 'student',
  status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_user_email (email),
  INDEX idx_user_role  (role),
  INDEX idx_user_status(status)
) ENGINE=InnoDB;

-- Migration for databases created before the super_admin role existed:
-- keeps the old column shape (running this is harmless if already applied).
ALTER TABLE users
  MODIFY COLUMN role ENUM('super_admin','admin','employee','student') NOT NULL DEFAULT 'student';

-- ------------------------------------------------------------
-- Complaints table
-- Stores each complaint. Status life-cycle:
-- pending -> review -> approved -> resolved  (or rejected)
-- anonymous = 1 hides the complainant identity from admins.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS complaints (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  complaint_id VARCHAR(20)  NOT NULL UNIQUE,
  user_id      INT UNSIGNED NOT NULL,
  title        VARCHAR(160) NOT NULL,
  description  TEXT         NOT NULL,
  category     ENUM('academic','infrastructure','faculty','administration','hostel','library','laboratory','harassment','other') NOT NULL DEFAULT 'other',
  anonymous    TINYINT(1)   NOT NULL DEFAULT 0,
  status       ENUM('pending','review','approved','resolved','rejected') NOT NULL DEFAULT 'pending',
  admin_remark TEXT         NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_complaint_user   (user_id),
  INDEX idx_complaint_status (status),
  INDEX idx_complaint_category(category),

  CONSTRAINT fk_complaints_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- CONTROLLED ADMIN SETUP
-- ------------------------------------------------------------
-- Normal users can NEVER register as admin (registration only
-- offers 'student' or 'employee'). Admin accounts are created
-- here through this controlled database setup.
--
-- Demo / sample admin account only (clearly marked as sample).
-- This account is the SUPER ADMIN: it can create/remove other admins.
-- Other admins CANNOT deactivate or remove the super admin.
-- Password is hashed with PHP's password_hash() [BCrypt].
-- ------------------------------------------------------------
INSERT INTO users (full_name, email, password, phone, role, status)
VALUES
  ('Sadiksha Shah', 'sadikshashah125@gmail.com', '$2y$10$GQS5GzvewZ9E.ErPdkbLFuFeV8XuEiBopb2Lz2i0JWZQntYi6H4Va', '9812345678', 'super_admin', 'active')
ON DUPLICATE KEY UPDATE id = id;

-- Upgrade an existing 'admin' row (created by older schema) to super_admin.
UPDATE users SET role = 'super_admin'
WHERE email = 'sadikshashah125@gmail.com' AND role <> 'super_admin';

-- ------------------------------------------------------------
-- SAMPLE / DEMO DATA (optional — clearly marked)
-- ------------------------------------------------------------
-- A demo student and a demo employee, plus a couple of sample
-- complaints so the dashboards are not empty on first run.
-- Registration is restricted to @kathford.edu.np, so demo accounts
-- below use that domain too.
-- ------------------------------------------------------------
INSERT INTO users (full_name, email, password, phone, role, status)
VALUES
  ('Alex Rivera', 'alex@kathford.edu.np', '$2y$10$jMUOjwbG1JpdQsPqTIBp4.ag/6yVcmhcQ7aRTe22S3KOEMoJW..pe', '9801234567', 'student', 'active'),
  ('Maria Garcia', 'maria@kathford.edu.np', '$2y$10$jMUOjwbG1JpdQsPqTIBp4.ag/6yVcmhcQ7aRTe22S3KOEMoJW..pe', '9701234567', 'employee', 'active')
ON DUPLICATE KEY UPDATE id = id;

-- Sample complaints (user 2 = Alex, user 3 = Maria)
INSERT INTO complaints (complaint_id, user_id, title, description, category, anonymous, status, admin_remark)
VALUES
  ('CB-2025-0001', 2, 'Broken projector in Room 204', 'The projector in Room 204 is not turning on and needs repair.', 'infrastructure', 0, 'resolved', 'Projector replaced by facilities team on the same week.'),
  ('CB-2025-0002', 3, 'Slow cafeteria service', 'The queue at the cafeteria is too long during lunch break.', 'administration', 1, 'review', 'Under review by the administration office.')
ON DUPLICATE KEY UPDATE complaint_id = complaint_id;
