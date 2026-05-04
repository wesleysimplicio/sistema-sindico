-- Sprint 1 migration: auth recovery, memberships, password history, profile fields.
-- Idempotent where MySQL 8 supports it. Apply with:
--   mysql -u root sistema_sindico < database/migrations/002_sprint_1.sql

SET NAMES utf8mb4;

-- Idempotent ADD COLUMN guards (MySQL 8/9 do not support IF NOT EXISTS on ADD COLUMN reliably).
SET @col_locale := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'locale'
);
SET @sql_locale := IF(@col_locale = 0,
  "ALTER TABLE users ADD COLUMN locale VARCHAR(8) NOT NULL DEFAULT 'pt-BR' AFTER avatar_url",
  "DO 0");
PREPARE stmt FROM @sql_locale; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_pca := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'password_changed_at'
);
SET @sql_pca := IF(@col_pca = 0,
  "ALTER TABLE users ADD COLUMN password_changed_at DATETIME DEFAULT NULL AFTER last_login_at",
  "DO 0");
PREPARE stmt FROM @sql_pca; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS memberships (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  unit_id         BIGINT UNSIGNED DEFAULT NULL,
  role            ENUM('admin','sindico','morador','porteiro') NOT NULL,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_membership (user_id, condominium_id, role),
  KEY idx_membership_condo (condominium_id),
  CONSTRAINT fk_mb_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_mb_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
  CONSTRAINT fk_mb_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_resets (
  id                BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id           BIGINT UNSIGNED NOT NULL,
  code_hash         VARCHAR(255) NOT NULL,
  reset_token_hash  VARCHAR(255) DEFAULT NULL,
  expires_at        DATETIME NOT NULL,
  used_at           DATETIME DEFAULT NULL,
  created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_pr_user (user_id),
  CONSTRAINT fk_pr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS password_history (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_ph_user (user_id),
  CONSTRAINT fk_ph_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
