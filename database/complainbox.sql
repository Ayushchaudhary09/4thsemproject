-- ============================================================
-- ComplaintBox — College Complaint Management System
-- MySQL Database Schema (Normalized: 5 tables)
-- Install: import this file in phpMyAdmin
--
-- Tables: admin, user, status, categories, complaints
--   - admin      : administrators (super_admin + admin), separate from users
--   - user       : students & employees (no created_at)
--   - status     : complaint status values (pending/review/approved/resolved/rejected)
--   - categories : complaint category values
--   - complaints : filed by users only (students/employees), FK to user/status/categories
--
-- NOTE: Passwords are stored as PLAIN TEXT (no hashing), per project requirement.
-- ============================================================

CREATE DATABASE IF NOT EXISTS complaintbox
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE complaintbox;

-- ------------------------------------------------------------
-- Admin table
-- Separate from regular users. role: super_admin | admin
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS admin (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name  VARCHAR(120) NOT NULL,
  email      VARCHAR(120) NOT NULL UNIQUE,
  password   VARCHAR(255) NOT NULL,
  phone      VARCHAR(15)  NOT NULL,
  role       ENUM('super_admin','admin') NOT NULL DEFAULT 'admin',
  status     ENUM('active','inactive') NOT NULL DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_admin_email (email),
  INDEX idx_admin_role  (role)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- User table
-- Students & employees only (admins live in the admin table).
-- role: student | employee
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS user (
  id        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(120) NOT NULL,
  email     VARCHAR(120) NOT NULL UNIQUE,
  password  VARCHAR(255) NOT NULL,
  phone     VARCHAR(15)  NOT NULL,
  role      ENUM('student','employee') NOT NULL DEFAULT 'student',
  status    ENUM('active','inactive') NOT NULL DEFAULT 'active',

  INDEX idx_user_email  (email),
  INDEX idx_user_status (status)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- Status table (complaint statuses only)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS status (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  status_name VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO status (status_name)
VALUES ('pending'), ('review'), ('approved'), ('resolved'), ('rejected')
ON DUPLICATE KEY UPDATE status_name = status_name;

-- ------------------------------------------------------------
-- Categories table
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  category_name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

INSERT INTO categories (category_name)
VALUES ('academic'), ('infrastructure'), ('faculty'), ('administration'),
       ('hostel'), ('library'), ('laboratory'), ('harassment'), ('other')
ON DUPLICATE KEY UPDATE category_name = category_name;

-- ------------------------------------------------------------
-- Complaints table
-- Filed by users (students/employees) only.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS complaints (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  complaint_id VARCHAR(20)  NOT NULL UNIQUE,
  user_id      INT UNSIGNED NOT NULL,
  title        VARCHAR(160) NOT NULL,
  description  TEXT         NOT NULL,
  category_id  INT UNSIGNED NOT NULL,
  status_id    INT UNSIGNED NOT NULL,
  anonymous    TINYINT(1)   NOT NULL DEFAULT 0,
  admin_remark TEXT         NULL,
  evidence     VARCHAR(255) NULL,
  created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  INDEX idx_complaint_user    (user_id),
  INDEX idx_complaint_status  (status_id),
  INDEX idx_complaint_category(category_id),

  CONSTRAINT fk_complaints_user
    FOREIGN KEY (user_id) REFERENCES user(id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT fk_complaints_status
    FOREIGN KEY (status_id) REFERENCES status(id),
  CONSTRAINT fk_complaints_category
    FOREIGN KEY (category_id) REFERENCES categories(id)
) ENGINE=InnoDB;

-- ------------------------------------------------------------
-- ADMIN SETUP
-- ------------------------------------------------------------
-- Normal users can NEVER register as admin (registration only offers
-- 'student' or 'employee'). Admin accounts are created through this
-- controlled database setup. This account is the SUPER ADMIN.
-- Passwords are plain text.
-- ------------------------------------------------------------
INSERT INTO admin (full_name, email, password, phone, role, status)
VALUES
  ('Sadiksha Shah', 'sadikshashah.081@kathford.edu.np', 'sadikshashah', '9812345678', 'super_admin', 'active')
ON DUPLICATE KEY UPDATE id = id;

-- ============================================================
-- MIGRATION for an existing (old 2-table) installation
-- Run these only if you already had the old `users` table.
-- ============================================================
-- -- Move old admins into admin table (keeps original ids):
-- INSERT INTO admin (id, full_name, email, password, phone, role, status, created_at)
--   SELECT id, full_name, email, password, phone,
--          CASE WHEN role = 'super_admin' THEN 'super_admin' ELSE 'admin' END, status, created_at
--   FROM users WHERE role IN ('admin','super_admin');
--
-- -- Move old students/employees into user table:
-- INSERT INTO user (id, full_name, email, password, phone, role, status)
--   SELECT id, full_name, email, password, phone, role, status
--   FROM users WHERE role IN ('student','employee');
--
-- -- Rebuild complaints with status/category foreign keys:
-- RENAME TABLE complaints TO complaints_old;
-- CREATE TABLE complaints (
--   id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
--   complaint_id VARCHAR(20)  NOT NULL UNIQUE,
--   user_id      INT UNSIGNED NOT NULL,
--   title        VARCHAR(160) NOT NULL,
--   description  TEXT         NOT NULL,
--   category_id  INT UNSIGNED NOT NULL,
--   status_id    INT UNSIGNED NOT NULL,
--   anonymous    TINYINT(1)   NOT NULL DEFAULT 0,
--   admin_remark TEXT         NULL,
--   evidence     VARCHAR(255) NULL,
--   created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
--   updated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
--   INDEX idx_complaint_user    (user_id),
--   INDEX idx_complaint_status  (status_id),
--   INDEX idx_complaint_category(category_id),
--   CONSTRAINT fk_complaints_user
--     FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE RESTRICT ON UPDATE CASCADE,
--   CONSTRAINT fk_complaints_status
--     FOREIGN KEY (status_id) REFERENCES status(id),
--   CONSTRAINT fk_complaints_category
--     FOREIGN KEY (category_id) REFERENCES categories(id)
-- ) ENGINE=InnoDB;
--
-- INSERT INTO complaints (id, complaint_id, user_id, title, description,
--                         category_id, status_id, anonymous, admin_remark,
--                         evidence, created_at, updated_at)
--   SELECT o.id, o.complaint_id, o.user_id, o.title, o.description,
--          (SELECT id FROM categories WHERE category_name = o.category),
--          (SELECT id FROM status    WHERE status_name   = o.status),
--          o.anonymous, o.admin_remark, o.evidence, o.created_at, o.updated_at
--   FROM complaints_old o;
--
-- ALTER TABLE complaints AUTO_INCREMENT = 1;
-- DROP TABLE complaints_old;
-- DROP TABLE users;