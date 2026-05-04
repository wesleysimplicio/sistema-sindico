-- Sprint 7 — Rate limit storage (sliding window per bucket+key).
-- Idempotent via INFORMATION_SCHEMA.

SET NAMES utf8mb4;

SET @t := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'rate_limits');
SET @s := IF(@t = 0,
  "CREATE TABLE rate_limits (
     id           BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
     bucket       VARCHAR(64) NOT NULL,
     key_hash     CHAR(64) NOT NULL,
     count        INT UNSIGNED NOT NULL DEFAULT 0,
     window_start DATETIME NOT NULL,
     created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     UNIQUE KEY uniq_bucket_key (bucket, key_hash),
     KEY idx_window (window_start)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
