-- Sprint 1 security migration: brute-force lockout for password reset codes.
-- Idempotent. Apply with:
--   mysql -u root sistema_sindico < database/migrations/003_sprint_1_security.sql

SET NAMES utf8mb4;

SET @col_attempt_count := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'password_resets'
    AND COLUMN_NAME = 'attempt_count'
);
SET @sql_attempt_count := IF(@col_attempt_count = 0,
  "ALTER TABLE password_resets ADD COLUMN attempt_count TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER used_at",
  "DO 0");
PREPARE stmt FROM @sql_attempt_count; EXECUTE stmt; DEALLOCATE PREPARE stmt;
