-- Sprint 2 migration: unit hub (residents, vehicles, contractors, porter notes, login invitations).
-- Idempotent. Apply with:
--   mysql -u root sistema_sindico < database/migrations/004_sprint_2.sql

SET NAMES utf8mb4;

-- 1. residents -------------------------------------------------------------
SET @tbl_residents := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'residents'
);
SET @sql_residents := IF(@tbl_residents = 0,
  "CREATE TABLE residents (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     condominium_id  BIGINT UNSIGNED NOT NULL,
     unit_id         BIGINT UNSIGNED NOT NULL,
     user_id         BIGINT UNSIGNED NULL,
     full_name       VARCHAR(120) NOT NULL,
     document        VARCHAR(32)  NULL,
     birth_date      DATE         NULL,
     relationship    ENUM('owner','tenant','dependent','other') NOT NULL DEFAULT 'owner',
     is_responsible  TINYINT(1) NOT NULL DEFAULT 0,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     KEY idx_res_unit (unit_id),
     KEY idx_res_condo (condominium_id),
     CONSTRAINT fk_res_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
     CONSTRAINT fk_res_unit  FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
     CONSTRAINT fk_res_user  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @sql_residents; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. vehicles --------------------------------------------------------------
SET @tbl_vehicles := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vehicles'
);
SET @sql_vehicles := IF(@tbl_vehicles = 0,
  "CREATE TABLE vehicles (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     condominium_id  BIGINT UNSIGNED NOT NULL,
     unit_id         BIGINT UNSIGNED NOT NULL,
     resident_id     BIGINT UNSIGNED NULL,
     plate           VARCHAR(16)  NOT NULL,
     brand           VARCHAR(64)  NULL,
     model           VARCHAR(64)  NULL,
     color           VARCHAR(32)  NULL,
     vehicle_type    ENUM('car','motorcycle','bike','other') NOT NULL DEFAULT 'car',
     parking_spot    VARCHAR(16)  NULL,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     UNIQUE KEY uq_vehicle_plate_condo (condominium_id, plate),
     KEY idx_veh_unit (unit_id),
     CONSTRAINT fk_veh_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
     CONSTRAINT fk_veh_unit  FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE,
     CONSTRAINT fk_veh_resident FOREIGN KEY (resident_id) REFERENCES residents(id) ON DELETE SET NULL
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @sql_vehicles; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. contractors -----------------------------------------------------------
SET @tbl_contractors := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contractors'
);
SET @sql_contractors := IF(@tbl_contractors = 0,
  "CREATE TABLE contractors (
     id               BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     condominium_id   BIGINT UNSIGNED NOT NULL,
     unit_id          BIGINT UNSIGNED NOT NULL,
     full_name        VARCHAR(120) NOT NULL,
     document         VARCHAR(32)  NULL,
     service_type     VARCHAR(64)  NULL,
     access_starts_at DATE NULL,
     access_ends_at   DATE NULL,
     status           ENUM('pending','approved','expired','revoked') NOT NULL DEFAULT 'pending',
     created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
     KEY idx_contractor_unit (unit_id),
     KEY idx_contractor_condo (condominium_id, status),
     CONSTRAINT fk_contractor_condo FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
     CONSTRAINT fk_contractor_unit  FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @sql_contractors; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. porter_notes ----------------------------------------------------------
SET @tbl_porter_notes := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'porter_notes'
);
SET @sql_porter_notes := IF(@tbl_porter_notes = 0,
  "CREATE TABLE porter_notes (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     condominium_id  BIGINT UNSIGNED NOT NULL,
     unit_id         BIGINT UNSIGNED NULL,
     author_user_id  BIGINT UNSIGNED NOT NULL,
     body            TEXT NOT NULL,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     KEY idx_pn_condo (condominium_id, created_at),
     KEY idx_pn_unit (unit_id),
     CONSTRAINT fk_pn_condo  FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
     CONSTRAINT fk_pn_unit   FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
     CONSTRAINT fk_pn_author FOREIGN KEY (author_user_id) REFERENCES users(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @sql_porter_notes; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. login_invitations -----------------------------------------------------
SET @tbl_login_inv := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'login_invitations'
);
SET @sql_login_inv := IF(@tbl_login_inv = 0,
  "CREATE TABLE login_invitations (
     id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     condominium_id      BIGINT UNSIGNED NOT NULL,
     unit_id             BIGINT UNSIGNED NULL,
     email               VARCHAR(120) NULL,
     phone               VARCHAR(32)  NULL,
     full_name           VARCHAR(120) NOT NULL,
     document            VARCHAR(32)  NULL,
     role                ENUM('sindico','morador','porteiro') NOT NULL,
     token               VARCHAR(64)  NOT NULL,
     expires_at          DATETIME     NOT NULL,
     accepted_at         DATETIME     NULL,
     created_by_user_id  BIGINT UNSIGNED NOT NULL,
     created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     UNIQUE KEY uq_login_inv_token (token),
     KEY idx_login_inv_condo (condominium_id),
     KEY idx_login_inv_unit (unit_id),
     CONSTRAINT fk_li_condo  FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
     CONSTRAINT fk_li_unit   FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE SET NULL,
     CONSTRAINT fk_li_author FOREIGN KEY (created_by_user_id) REFERENCES users(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @sql_login_inv; EXECUTE stmt; DEALLOCATE PREPARE stmt;
