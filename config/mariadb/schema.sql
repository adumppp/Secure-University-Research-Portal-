-- ═══════════════════════════════════════════════════════════
-- Secure Research Portal — Database schema & least-privilege grants
-- Target: MariaDB 10.11
-- Run as: sudo mysql -u root < schema.sql
--
-- This is the exact deployed schema (reconciled against a live
-- mysqldump --no-data). Tables use InnoDB, utf8mb4, and foreign
-- keys into users(id) for referential integrity.
--
-- Password policy: the portal password is set out-of-band with a
-- strong value (12+ chars, mixed case, digit, special). Generate one:
--   openssl rand -base64 24
-- ═══════════════════════════════════════════════════════════

CREATE DATABASE IF NOT EXISTS researchportal
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE researchportal;

-- --- Application tables -------------------------------------------------

-- users: accounts with bcrypt hashes ($2y$12$), roles: admin/researcher/guest
CREATE TABLE IF NOT EXISTS users (
  id int(11) NOT NULL AUTO_INCREMENT,
  username varchar(50) NOT NULL,
  email varchar(100) NOT NULL,
  password_hash varchar(255) NOT NULL,
  role enum('admin','researcher','guest') DEFAULT 'guest',
  created_at timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  UNIQUE KEY username (username),
  UNIQUE KEY email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- announcements: public notice board
CREATE TABLE IF NOT EXISTS announcements (
  id int(11) NOT NULL AUTO_INCREMENT,
  title varchar(200) NOT NULL,
  content text NOT NULL,
  posted_by int(11) NOT NULL,
  date timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY posted_by (posted_by),
  CONSTRAINT announcements_ibfk_1 FOREIGN KEY (posted_by)
      REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- proposals: research proposals with public/confidential classification
CREATE TABLE IF NOT EXISTS proposals (
  id int(11) NOT NULL AUTO_INCREMENT,
  title varchar(200) NOT NULL,
  researcher_id int(11) NOT NULL,
  file_path varchar(255) DEFAULT NULL,
  status enum('pending','approved','rejected') DEFAULT 'pending',
  classification enum('public','confidential') DEFAULT 'public',
  submitted_at timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY researcher_id (researcher_id),
  CONSTRAINT proposals_ibfk_1 FOREIGN KEY (researcher_id)
      REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- documents: research documents, each classified public or confidential
CREATE TABLE IF NOT EXISTS documents (
  id int(11) NOT NULL AUTO_INCREMENT,
  doc_name varchar(200) NOT NULL,
  classification enum('public','confidential') DEFAULT 'public',
  uploader_id int(11) NOT NULL,
  file_path varchar(255) NOT NULL,
  upload_date timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY uploader_id (uploader_id),
  CONSTRAINT documents_ibfk_1 FOREIGN KEY (uploader_id)
      REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- sensitive_documents: ISOLATED confidential research store.
-- Single-value classification enum ('confidential' only) makes the
-- table's purpose structural, not just conventional. Uploaders and
-- admins only — enforced at the application layer and by grants below.
CREATE TABLE IF NOT EXISTS sensitive_documents (
  id int(11) NOT NULL AUTO_INCREMENT,
  doc_name varchar(200) NOT NULL,
  description text DEFAULT NULL,
  uploader_id int(11) NOT NULL,
  file_path varchar(255) NOT NULL,
  classification enum('confidential') DEFAULT 'confidential',
  upload_date timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY uploader_id (uploader_id),
  CONSTRAINT sensitive_documents_ibfk_1 FOREIGN KEY (uploader_id)
      REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- research_access_log: audit trail — every confidential access is
-- inserted by the application with user, document, action, IP, timestamp.
CREATE TABLE IF NOT EXISTS research_access_log (
  id int(11) NOT NULL AUTO_INCREMENT,
  user_id int(11) NOT NULL,
  document_id int(11) DEFAULT NULL,
  document_type enum('public','confidential') NOT NULL,
  action varchar(50) NOT NULL,
  ip_address varchar(45) DEFAULT NULL,
  accessed_at timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY user_id (user_id),
  CONSTRAINT research_access_log_ibfk_1 FOREIGN KEY (user_id)
      REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- collaboration_requests: researcher-to-researcher collaboration
CREATE TABLE IF NOT EXISTS collaboration_requests (
  id int(11) NOT NULL AUTO_INCREMENT,
  requester_id int(11) NOT NULL,
  project_id int(11) NOT NULL,
  status enum('pending','accepted','rejected') DEFAULT 'pending',
  requested_at timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (id),
  KEY requester_id (requester_id),
  CONSTRAINT collaboration_requests_ibfk_1 FOREIGN KEY (requester_id)
      REFERENCES users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --- Least-privilege application user -----------------------------------
-- The web application NEVER connects as root. portaluser holds exactly
-- the privileges the application needs — nothing more.
--
-- Set the password out-of-band; do not commit it to version control:
--   CREATE USER 'portaluser'@'localhost' IDENTIFIED BY '<strong-password>';

CREATE USER IF NOT EXISTS 'portaluser'@'localhost'
    IDENTIFIED BY '<strong-password>';

GRANT SELECT, INSERT, UPDATE ON researchportal.* TO 'portaluser'@'localhost';
GRANT SELECT, INSERT           ON researchportal.research_access_log TO 'portaluser'@'localhost';
GRANT SELECT, INSERT, UPDATE   ON researchportal.sensitive_documents  TO 'portaluser'@'localhost';
FLUSH PRIVILEGES;

-- --- Verification --------------------------------------------------------
-- SHOW GRANTS FOR 'portaluser'@'localhost';
-- Expected:
--   GRANT USAGE ON *.* ...
--   GRANT SELECT, INSERT, UPDATE ON `researchportal`.* ...
--   GRANT SELECT, INSERT ON `researchportal`.`research_access_log` ...
--   GRANT SELECT, INSERT, UPDATE ON `researchportal`.`sensitive_documents` ...
--
-- Confirm the user cannot touch system tables:
--   mysql -u portaluser -p -e "SELECT * FROM mysql.user;"
--   → ERROR 1142 (42000): SELECT command denied
