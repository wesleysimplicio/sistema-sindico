-- Sprint 5 migration: cameras, gate_triggers, gate_trigger_logs, incident_types, incidents, incident_comments.
-- Idempotent. Apply with:
--   mysql -u root sistema_sindico < database/migrations/007_sprint_5.sql

SET NAMES utf8mb4;

-- 1. cameras -----------------------------------------------------------------
SET @t := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cameras');
SET @s := IF(@t = 0,
  "CREATE TABLE cameras (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     condominium_id  BIGINT UNSIGNED NOT NULL,
     name            VARCHAR(120) NOT NULL,
     location        VARCHAR(120) NULL,
     rtsp_url        VARCHAR(500) NULL,
     hls_path        VARCHAR(500) NULL,
     enabled         TINYINT(1) NOT NULL DEFAULT 1,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     KEY idx_cam_condo (condominium_id),
     CONSTRAINT fk_cam_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. gate_triggers -----------------------------------------------------------
SET @t := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gate_triggers');
SET @s := IF(@t = 0,
  "CREATE TABLE gate_triggers (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     condominium_id  BIGINT UNSIGNED NOT NULL,
     name            VARCHAR(120) NOT NULL,
     endpoint_url    VARCHAR(500) NOT NULL,
     auth_token      VARCHAR(255) NULL,
     timeout_ms      INT NOT NULL DEFAULT 5000,
     enabled         TINYINT(1) NOT NULL DEFAULT 1,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     KEY idx_gt_condo (condominium_id),
     CONSTRAINT fk_gt_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. gate_trigger_logs -------------------------------------------------------
SET @t := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gate_trigger_logs');
SET @s := IF(@t = 0,
  "CREATE TABLE gate_trigger_logs (
     id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     gate_trigger_id   BIGINT UNSIGNED NOT NULL,
     user_id           BIGINT UNSIGNED NULL,
     result            ENUM('success','failure') NOT NULL,
     http_status       INT NULL,
     duration_ms       INT NULL,
     error_message     VARCHAR(500) NULL,
     created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     KEY idx_gtl_trigger (gate_trigger_id),
     KEY idx_gtl_time (created_at),
     CONSTRAINT fk_gtl_trigger FOREIGN KEY (gate_trigger_id) REFERENCES gate_triggers(id) ON DELETE CASCADE,
     CONSTRAINT fk_gtl_user    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. incident_types ----------------------------------------------------------
SET @t := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incident_types');
SET @s := IF(@t = 0,
  "CREATE TABLE incident_types (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     condominium_id  BIGINT UNSIGNED NOT NULL,
     name            VARCHAR(120) NOT NULL,
     description     VARCHAR(255) NULL,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     KEY idx_it_condo (condominium_id),
     CONSTRAINT fk_it_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. incidents ---------------------------------------------------------------
SET @t := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incidents');
SET @s := IF(@t = 0,
  "CREATE TABLE incidents (
     id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     condominium_id    BIGINT UNSIGNED NOT NULL,
     incident_type_id  BIGINT UNSIGNED NULL,
     reporter_id       BIGINT UNSIGNED NULL,
     unit_id           BIGINT UNSIGNED NULL,
     title             VARCHAR(200) NOT NULL,
     body              TEXT NULL,
     status            ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
     occurred_at       DATETIME NULL,
     created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     updated_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     KEY idx_inc_condo (condominium_id),
     KEY idx_inc_status (status),
     CONSTRAINT fk_inc_condo  FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
     CONSTRAINT fk_inc_type   FOREIGN KEY (incident_type_id) REFERENCES incident_types(id) ON DELETE SET NULL,
     CONSTRAINT fk_inc_user   FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE SET NULL,
     CONSTRAINT fk_inc_unit   FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. incident_comments -------------------------------------------------------
SET @t := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incident_comments');
SET @s := IF(@t = 0,
  "CREATE TABLE incident_comments (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     incident_id     BIGINT UNSIGNED NOT NULL,
     user_id         BIGINT UNSIGNED NULL,
     body            TEXT NOT NULL,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     KEY idx_icom_incident (incident_id),
     CONSTRAINT fk_icom_incident FOREIGN KEY (incident_id) REFERENCES incidents(id) ON DELETE CASCADE,
     CONSTRAINT fk_icom_user     FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
