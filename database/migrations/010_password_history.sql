-- Sprint 6 — Password history (last N hashes per user).
-- Idempotent via INFORMATION_SCHEMA.

SET NAMES utf8mb4;

SET @t := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'password_history');
SET @s := IF(@t = 0,
  "CREATE TABLE password_history (
     id            BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
     user_id       BIGINT UNSIGNED NOT NULL,
     password_hash VARCHAR(255) NOT NULL,
     created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     KEY idx_pwhist_user (user_id, created_at),
     CONSTRAINT fk_pwhist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
