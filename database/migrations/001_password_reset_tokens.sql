-- Migration 001: password_reset_tokens
-- Supports the forgot-password → verify-code → reset-password flow.

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id          BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id     BIGINT UNSIGNED NOT NULL,
  code        CHAR(6) NOT NULL,
  reset_token VARCHAR(64) DEFAULT NULL,
  used_at     DATETIME DEFAULT NULL,
  expires_at  DATETIME NOT NULL,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_prt_user (user_id),
  KEY idx_prt_token (reset_token),
  CONSTRAINT fk_prt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
