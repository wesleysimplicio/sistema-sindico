-- Sprint 3 migration: visitors expansion + event invitations + access logs + audit_logs scope.
-- Idempotent. Apply with:
--   mysql -u root sistema_sindico < database/migrations/005_sprint_3.sql

SET NAMES utf8mb4;

-- 1. visitors: photo_url, qr_expires_at -----------------------------------
SET @col_photo := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'photo_url'
);
SET @sql_photo := IF(@col_photo = 0,
  "ALTER TABLE visitors ADD COLUMN photo_url VARCHAR(255) NULL AFTER notes",
  "DO 0");
PREPARE stmt FROM @sql_photo; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_qrexp := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors' AND COLUMN_NAME = 'qr_expires_at'
);
SET @sql_qrexp := IF(@col_qrexp = 0,
  "ALTER TABLE visitors ADD COLUMN qr_expires_at DATETIME NULL AFTER qr_token",
  "DO 0");
PREPARE stmt FROM @sql_qrexp; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. invitations -----------------------------------------------------------
SET @tbl_inv := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invitations'
);
SET @sql_inv := IF(@tbl_inv = 0,
  "CREATE TABLE invitations (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     condominium_id  BIGINT UNSIGNED NOT NULL,
     unit_id         BIGINT UNSIGNED NOT NULL,
     host_user_id    BIGINT UNSIGNED NOT NULL,
     title           VARCHAR(120) NOT NULL,
     starts_at       DATETIME NOT NULL,
     ends_at         DATETIME NULL,
     notes           TEXT NULL,
     status          ENUM('draft','active','done','cancelled') NOT NULL DEFAULT 'active',
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     KEY idx_inv_condo (condominium_id, starts_at),
     KEY idx_inv_unit (unit_id),
     KEY idx_inv_host (host_user_id),
     CONSTRAINT fk_inv_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
     CONSTRAINT fk_inv_unit  FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
     CONSTRAINT fk_inv_host  FOREIGN KEY (host_user_id) REFERENCES users(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @sql_inv; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. invitation_guests -----------------------------------------------------
SET @tbl_gst := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'invitation_guests'
);
SET @sql_gst := IF(@tbl_gst = 0,
  "CREATE TABLE invitation_guests (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     invitation_id   BIGINT UNSIGNED NOT NULL,
     full_name       VARCHAR(120) NOT NULL,
     document        VARCHAR(32) NULL,
     status          ENUM('expected','arrived','no_show') NOT NULL DEFAULT 'expected',
     arrived_at      DATETIME NULL,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     KEY idx_gst_inv (invitation_id),
     CONSTRAINT fk_guest_inv FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @sql_gst; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. access_logs -----------------------------------------------------------
SET @tbl_alog := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'access_logs'
);
SET @sql_alog := IF(@tbl_alog = 0,
  "CREATE TABLE access_logs (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     condominium_id  BIGINT UNSIGNED NOT NULL,
     reader_id       BIGINT UNSIGNED NULL,
     user_id         BIGINT UNSIGNED NULL,
     visitor_id      BIGINT UNSIGNED NULL,
     unit_id         BIGINT UNSIGNED NULL,
     direction       ENUM('in','out') NOT NULL,
     result          ENUM('granted','denied') NOT NULL DEFAULT 'granted',
     reason          VARCHAR(120) NULL,
     photo_url       VARCHAR(255) NULL,
     occurred_at     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     KEY idx_access_time (condominium_id, occurred_at),
     KEY idx_access_visitor (visitor_id),
     KEY idx_access_user (user_id),
     CONSTRAINT fk_alog_condo   FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
     CONSTRAINT fk_alog_user    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
     CONSTRAINT fk_alog_visitor FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE SET NULL,
     CONSTRAINT fk_alog_unit    FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @sql_alog; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. audit_logs: add condominium_id for tenant scoping --------------------
SET @col_audit_cid := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'audit_logs' AND COLUMN_NAME = 'condominium_id'
);
SET @sql_audit_cid := IF(@col_audit_cid = 0,
  "ALTER TABLE audit_logs ADD COLUMN condominium_id BIGINT UNSIGNED NULL AFTER user_id, ADD KEY idx_audit_condo (condominium_id, created_at)",
  "DO 0");
PREPARE stmt FROM @sql_audit_cid; EXECUTE stmt; DEALLOCATE PREPARE stmt;
