-- Migration 001: multi-tenant memberships table
-- Sprint 1 — S1-03, S1-04

CREATE TABLE IF NOT EXISTS memberships (
  id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id         BIGINT UNSIGNED NOT NULL,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  role            ENUM('admin','sindico','morador','porteiro') NOT NULL,
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_membership (user_id, condominium_id, role),
  KEY idx_membership_condo (condominium_id),
  CONSTRAINT fk_mb_user  FOREIGN KEY (user_id)        REFERENCES users(id)        ON DELETE CASCADE,
  CONSTRAINT fk_mb_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
