-- Migration 001 — password_reset_tokens
-- Stores hashed 6-digit codes used in the POST /api/auth/forgot-password flow.
-- TTL enforced by expires_at; old rows purged on each new request for the same user.

CREATE TABLE IF NOT EXISTS password_reset_tokens (
  id         BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id    BIGINT UNSIGNED NOT NULL,
  token_hash VARCHAR(255)    NOT NULL COMMENT 'bcrypt hash of the 6-digit code',
  expires_at DATETIME        NOT NULL,
  used_at    DATETIME        DEFAULT NULL,
  created_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_prt_user    (user_id),
  KEY idx_prt_expires (expires_at),
  CONSTRAINT fk_prt_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
