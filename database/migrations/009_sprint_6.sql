-- Sprint 6 — Notifications, push, security, contact, settings.
-- Idempotent via INFORMATION_SCHEMA.

SET NAMES utf8mb4;

-- 1. notifications -----------------------------------------------------------
SET @t := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notifications');
SET @s := IF(@t = 0,
  "CREATE TABLE notifications (
     id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
     user_id         BIGINT UNSIGNED NOT NULL,
     condominium_id  BIGINT UNSIGNED DEFAULT NULL,
     type            VARCHAR(60) NOT NULL,
     title           VARCHAR(200) NOT NULL,
     body            TEXT DEFAULT NULL,
     related_entity  VARCHAR(60) DEFAULT NULL,
     related_id      BIGINT UNSIGNED DEFAULT NULL,
     read_at         DATETIME DEFAULT NULL,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     KEY idx_notif_user_unread (user_id, read_at),
     KEY idx_notif_user_created (user_id, created_at),
     CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. user_devices (FCM tokens) ----------------------------------------------
SET @t := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_devices');
SET @s := IF(@t = 0,
  "CREATE TABLE user_devices (
     id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
     user_id         BIGINT UNSIGNED NOT NULL,
     platform        ENUM('ios','android','web') NOT NULL,
     fcm_token       VARCHAR(255) NOT NULL,
     device_name     VARCHAR(100) DEFAULT NULL,
     last_used_at    DATETIME DEFAULT NULL,
     revoked_at      DATETIME DEFAULT NULL,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     UNIQUE KEY uniq_fcm_token (fcm_token),
     KEY idx_dev_user (user_id),
     CONSTRAINT fk_dev_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. contact_messages -------------------------------------------------------
SET @t := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_messages');
SET @s := IF(@t = 0,
  "CREATE TABLE contact_messages (
     id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
     condominium_id  BIGINT UNSIGNED NOT NULL,
     user_id         BIGINT UNSIGNED DEFAULT NULL,
     name            VARCHAR(120) NOT NULL,
     email           VARCHAR(150) DEFAULT NULL,
     subject         VARCHAR(200) NOT NULL,
     body            TEXT NOT NULL,
     status          ENUM('new','read','replied') NOT NULL DEFAULT 'new',
     reply           TEXT DEFAULT NULL,
     replied_at      DATETIME DEFAULT NULL,
     replied_by      BIGINT UNSIGNED DEFAULT NULL,
     ip              VARCHAR(45) DEFAULT NULL,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     KEY idx_contact_condo_status (condominium_id, status),
     KEY idx_contact_condo_created (condominium_id, created_at),
     CONSTRAINT fk_contact_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
     CONSTRAINT fk_contact_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
     CONSTRAINT fk_contact_reply FOREIGN KEY (replied_by) REFERENCES users(id) ON DELETE SET NULL
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. users.totp_secret -------------------------------------------------------
SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'totp_secret');
SET @s := IF(@c = 0,
  "ALTER TABLE users ADD COLUMN totp_secret VARCHAR(64) DEFAULT NULL AFTER password_changed_at",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. users.twofa_enabled -----------------------------------------------------
SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'twofa_enabled');
SET @s := IF(@c = 0,
  "ALTER TABLE users ADD COLUMN twofa_enabled TINYINT(1) NOT NULL DEFAULT 0 AFTER totp_secret",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. api_tokens.ip / user_agent / revoked_at ---------------------------------
SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'api_tokens' AND COLUMN_NAME = 'ip');
SET @s := IF(@c = 0,
  "ALTER TABLE api_tokens ADD COLUMN ip VARCHAR(45) DEFAULT NULL AFTER device",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'api_tokens' AND COLUMN_NAME = 'user_agent');
SET @s := IF(@c = 0,
  "ALTER TABLE api_tokens ADD COLUMN user_agent VARCHAR(255) DEFAULT NULL AFTER ip",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @c := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'api_tokens' AND COLUMN_NAME = 'revoked_at');
SET @s := IF(@c = 0,
  "ALTER TABLE api_tokens ADD COLUMN revoked_at DATETIME DEFAULT NULL AFTER expires_at",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7. api_tokens.token_hash unique --------------------------------------------
SET @i := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'api_tokens' AND INDEX_NAME = 'uniq_at_token_hash');
SET @s := IF(@i = 0,
  "CREATE UNIQUE INDEX uniq_at_token_hash ON api_tokens (token_hash)",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
