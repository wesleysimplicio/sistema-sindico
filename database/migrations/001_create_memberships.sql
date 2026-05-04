-- Migration 001 — create memberships table
-- Allows a single user to belong to multiple condominiums with distinct roles.
-- is_active controls whether the membership is currently valid (picker filter).

CREATE TABLE IF NOT EXISTS memberships (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  unit_id         BIGINT UNSIGNED DEFAULT NULL,
  role            ENUM('admin','sindico','morador','porteiro') NOT NULL DEFAULT 'morador',
  is_active       TINYINT(1) NOT NULL DEFAULT 1,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_membership (user_id, condominium_id),
  KEY idx_mem_user (user_id),
  KEY idx_mem_condo (condominium_id),
  CONSTRAINT fk_mem_user  FOREIGN KEY (user_id)        REFERENCES users(id)         ON DELETE CASCADE,
  CONSTRAINT fk_mem_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id)  ON DELETE CASCADE,
  CONSTRAINT fk_mem_unit  FOREIGN KEY (unit_id)        REFERENCES units(id)         ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
