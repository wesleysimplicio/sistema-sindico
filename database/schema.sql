-- Sistema Sindico - schema
-- MySQL 8+, utf8mb4

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS notification_preferences;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS documents;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS common_areas;
DROP TABLE IF EXISTS visitors;
DROP TABLE IF EXISTS deliveries;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS maintenance_requests;
DROP TABLE IF EXISTS notices;
DROP TABLE IF EXISTS api_tokens;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS units;
DROP TABLE IF EXISTS condominiums;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE condominiums (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  name            VARCHAR(150) NOT NULL,
  cnpj            VARCHAR(20) DEFAULT NULL,
  address         VARCHAR(255) DEFAULT NULL,
  city            VARCHAR(100) DEFAULT NULL,
  state           CHAR(2) DEFAULT NULL,
  zipcode         VARCHAR(15) DEFAULT NULL,
  phone           VARCHAR(30) DEFAULT NULL,
  logo_url        VARCHAR(255) DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_cnpj (cnpj)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE units (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  block           VARCHAR(20) DEFAULT NULL,
  number          VARCHAR(20) NOT NULL,
  floor           VARCHAR(10) DEFAULT NULL,
  type            VARCHAR(40) DEFAULT 'apartamento',
  area_m2         DECIMAL(8,2) DEFAULT NULL,
  parking_slots   TINYINT UNSIGNED DEFAULT 0,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_units_condo (condominium_id),
  UNIQUE KEY uniq_unit (condominium_id, block, number),
  CONSTRAINT fk_units_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  condominium_id  BIGINT UNSIGNED DEFAULT NULL,
  unit_id         BIGINT UNSIGNED DEFAULT NULL,
  name            VARCHAR(150) NOT NULL,
  email           VARCHAR(150) NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,
  role            ENUM('admin','sindico','morador','porteiro') NOT NULL DEFAULT 'morador',
  phone           VARCHAR(30) DEFAULT NULL,
  document        VARCHAR(20) DEFAULT NULL,
  avatar_url      VARCHAR(255) DEFAULT NULL,
  active          TINYINT(1) NOT NULL DEFAULT 1,
  last_login_at   DATETIME DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_email (email),
  KEY idx_users_condo (condominium_id),
  KEY idx_users_unit (unit_id),
  CONSTRAINT fk_users_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE SET NULL,
  CONSTRAINT fk_users_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE api_tokens (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED NOT NULL,
  device          VARCHAR(100) DEFAULT NULL,
  token_hash      VARCHAR(255) NOT NULL,
  last_used_at    DATETIME DEFAULT NULL,
  expires_at      DATETIME DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_tokens_user (user_id),
  CONSTRAINT fk_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notices (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  author_id       BIGINT UNSIGNED DEFAULT NULL,
  title           VARCHAR(200) NOT NULL,
  body            TEXT NOT NULL,
  category        VARCHAR(50) DEFAULT 'geral',
  pinned          TINYINT(1) NOT NULL DEFAULT 0,
  published_at    DATETIME DEFAULT NULL,
  expires_at      DATETIME DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_notices_condo (condominium_id, published_at),
  CONSTRAINT fk_notices_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
  CONSTRAINT fk_notices_author FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE maintenance_requests (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  unit_id         BIGINT UNSIGNED DEFAULT NULL,
  requester_id    BIGINT UNSIGNED DEFAULT NULL,
  assignee_id     BIGINT UNSIGNED DEFAULT NULL,
  title           VARCHAR(200) NOT NULL,
  description     TEXT,
  category        VARCHAR(50) DEFAULT 'geral',
  priority        ENUM('baixa','media','alta','urgente') NOT NULL DEFAULT 'media',
  status          ENUM('aberto','em_andamento','aguardando','concluido','cancelado') NOT NULL DEFAULT 'aberto',
  photo_url       VARCHAR(255) DEFAULT NULL,
  resolved_at     DATETIME DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_maint_condo (condominium_id, status),
  KEY idx_maint_requester (requester_id),
  CONSTRAINT fk_maint_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
  CONSTRAINT fk_maint_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
  CONSTRAINT fk_maint_requester FOREIGN KEY (requester_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_maint_assignee FOREIGN KEY (assignee_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  unit_id         BIGINT UNSIGNED DEFAULT NULL,
  resident_id     BIGINT UNSIGNED DEFAULT NULL,
  reference       VARCHAR(20) NOT NULL,
  description     VARCHAR(200) DEFAULT NULL,
  amount          DECIMAL(10,2) NOT NULL,
  due_date        DATE NOT NULL,
  paid_at         DATETIME DEFAULT NULL,
  status          ENUM('pendente','pago','atrasado','cancelado') NOT NULL DEFAULT 'pendente',
  barcode         VARCHAR(60) DEFAULT NULL,
  pix_code        TEXT,
  receipt_url     VARCHAR(255) DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_pay_condo (condominium_id, status),
  KEY idx_pay_unit (unit_id),
  CONSTRAINT fk_pay_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
  CONSTRAINT fk_pay_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
  CONSTRAINT fk_pay_resident FOREIGN KEY (resident_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE deliveries (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  unit_id         BIGINT UNSIGNED DEFAULT NULL,
  resident_id     BIGINT UNSIGNED DEFAULT NULL,
  sender          VARCHAR(150) DEFAULT NULL,
  courier         VARCHAR(80) DEFAULT NULL,
  tracking_code   VARCHAR(80) DEFAULT NULL,
  description     VARCHAR(255) DEFAULT NULL,
  photo_url       VARCHAR(255) DEFAULT NULL,
  received_at     DATETIME DEFAULT CURRENT_TIMESTAMP,
  withdrawn_at    DATETIME DEFAULT NULL,
  withdrawn_by    VARCHAR(150) DEFAULT NULL,
  status          ENUM('aguardando','retirada','devolvida') NOT NULL DEFAULT 'aguardando',
  KEY idx_del_condo (condominium_id, status),
  CONSTRAINT fk_del_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
  CONSTRAINT fk_del_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
  CONSTRAINT fk_del_resident FOREIGN KEY (resident_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE visitors (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  unit_id         BIGINT UNSIGNED DEFAULT NULL,
  host_id         BIGINT UNSIGNED DEFAULT NULL,
  name            VARCHAR(150) NOT NULL,
  document        VARCHAR(30) DEFAULT NULL,
  phone           VARCHAR(30) DEFAULT NULL,
  qr_token        VARCHAR(64) DEFAULT NULL,
  expected_at     DATETIME DEFAULT NULL,
  entered_at      DATETIME DEFAULT NULL,
  exited_at       DATETIME DEFAULT NULL,
  status          ENUM('previsto','liberado','dentro','saiu','expirado','negado') NOT NULL DEFAULT 'previsto',
  notes           VARCHAR(255) DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_vis_condo (condominium_id, status),
  UNIQUE KEY uniq_qr (qr_token),
  CONSTRAINT fk_vis_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
  CONSTRAINT fk_vis_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
  CONSTRAINT fk_vis_host FOREIGN KEY (host_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE common_areas (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  name            VARCHAR(120) NOT NULL,
  description     TEXT,
  capacity        INT UNSIGNED DEFAULT NULL,
  opening_time    TIME DEFAULT NULL,
  closing_time    TIME DEFAULT NULL,
  requires_approval TINYINT(1) NOT NULL DEFAULT 0,
  active          TINYINT(1) NOT NULL DEFAULT 1,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_areas_condo (condominium_id),
  CONSTRAINT fk_areas_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bookings (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  common_area_id  BIGINT UNSIGNED NOT NULL,
  unit_id         BIGINT UNSIGNED DEFAULT NULL,
  resident_id     BIGINT UNSIGNED DEFAULT NULL,
  starts_at       DATETIME NOT NULL,
  ends_at         DATETIME NOT NULL,
  status          ENUM('solicitado','aprovado','rejeitado','cancelado','concluido') NOT NULL DEFAULT 'solicitado',
  notes           VARCHAR(255) DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_book_condo (condominium_id, starts_at),
  KEY idx_book_area (common_area_id, starts_at),
  CONSTRAINT fk_book_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
  CONSTRAINT fk_book_area FOREIGN KEY (common_area_id) REFERENCES common_areas(id) ON DELETE CASCADE,
  CONSTRAINT fk_book_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
  CONSTRAINT fk_book_resident FOREIGN KEY (resident_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE documents (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  uploaded_by     BIGINT UNSIGNED DEFAULT NULL,
  title           VARCHAR(200) NOT NULL,
  description     VARCHAR(255) DEFAULT NULL,
  file_path       VARCHAR(255) NOT NULL,
  category        VARCHAR(50) DEFAULT 'geral',
  size_bytes      BIGINT UNSIGNED DEFAULT NULL,
  mime_type       VARCHAR(100) DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_docs_condo (condominium_id, category),
  CONSTRAINT fk_docs_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
  CONSTRAINT fk_docs_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE messages (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  condominium_id  BIGINT UNSIGNED NOT NULL,
  from_user_id    BIGINT UNSIGNED DEFAULT NULL,
  to_user_id      BIGINT UNSIGNED DEFAULT NULL,
  subject         VARCHAR(200) DEFAULT NULL,
  body            TEXT NOT NULL,
  channel         ENUM('sindico','portaria','suporte','direto') NOT NULL DEFAULT 'sindico',
  read_at         DATETIME DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_msg_condo (condominium_id, created_at),
  KEY idx_msg_from (from_user_id),
  KEY idx_msg_to (to_user_id),
  CONSTRAINT fk_msg_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
  CONSTRAINT fk_msg_from FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_msg_to FOREIGN KEY (to_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification_preferences (
  user_id         BIGINT UNSIGNED NOT NULL,
  pref_key        VARCHAR(60) NOT NULL,
  enabled         TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (user_id, pref_key),
  CONSTRAINT fk_pref_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE audit_logs (
  id              BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
  user_id         BIGINT UNSIGNED DEFAULT NULL,
  action          VARCHAR(80) NOT NULL,
  entity          VARCHAR(80) DEFAULT NULL,
  entity_id       BIGINT UNSIGNED DEFAULT NULL,
  payload         JSON DEFAULT NULL,
  ip              VARCHAR(45) DEFAULT NULL,
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_audit_user (user_id),
  KEY idx_audit_action (action),
  CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
