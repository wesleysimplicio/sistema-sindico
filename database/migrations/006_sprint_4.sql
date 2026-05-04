-- Sprint 4 migration: notices scope/reads/attachments, document_folders, maintenance attachments+comments, deliveries enrichment.
-- Idempotent. Apply with:
--   mysql -u root sistema_sindico < database/migrations/006_sprint_4.sql

SET NAMES utf8mb4;

-- 1. notices: add scope (all|block|unit|role) and supporting fields ----------
SET @col_scope := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notices' AND COLUMN_NAME = 'scope'
);
SET @sql_scope := IF(@col_scope = 0,
  "ALTER TABLE notices ADD COLUMN scope ENUM('all','block','unit','role') NOT NULL DEFAULT 'all' AFTER category",
  "DO 0");
PREPARE stmt FROM @sql_scope; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_block := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notices' AND COLUMN_NAME = 'scope_block'
);
SET @sql_block := IF(@col_block = 0,
  "ALTER TABLE notices ADD COLUMN scope_block VARCHAR(20) NULL AFTER scope",
  "DO 0");
PREPARE stmt FROM @sql_block; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_unit := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notices' AND COLUMN_NAME = 'scope_unit_id'
);
SET @sql_unit := IF(@col_unit = 0,
  "ALTER TABLE notices ADD COLUMN scope_unit_id BIGINT UNSIGNED NULL AFTER scope_block",
  "DO 0");
PREPARE stmt FROM @sql_unit; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_role := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notices' AND COLUMN_NAME = 'scope_role'
);
SET @sql_role := IF(@col_role = 0,
  "ALTER TABLE notices ADD COLUMN scope_role VARCHAR(20) NULL AFTER scope_unit_id",
  "DO 0");
PREPARE stmt FROM @sql_role; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. notice_attachments ------------------------------------------------------
SET @tbl_natt := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notice_attachments'
);
SET @sql_natt := IF(@tbl_natt = 0,
  "CREATE TABLE notice_attachments (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     notice_id       BIGINT UNSIGNED NOT NULL,
     file_path       VARCHAR(500) NOT NULL,
     original_name   VARCHAR(200) NULL,
     mime_type       VARCHAR(100) NULL,
     size_bytes      BIGINT UNSIGNED NULL,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     KEY idx_natt_notice (notice_id),
     CONSTRAINT fk_natt_notice FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @sql_natt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. notice_reads ------------------------------------------------------------
SET @tbl_nread := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notice_reads'
);
SET @sql_nread := IF(@tbl_nread = 0,
  "CREATE TABLE notice_reads (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     notice_id       BIGINT UNSIGNED NOT NULL,
     user_id         BIGINT UNSIGNED NOT NULL,
     read_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     UNIQUE KEY uniq_notice_user (notice_id, user_id),
     KEY idx_nread_user (user_id),
     CONSTRAINT fk_nread_notice FOREIGN KEY (notice_id) REFERENCES notices(id) ON DELETE CASCADE,
     CONSTRAINT fk_nread_user   FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @sql_nread; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. document_folders --------------------------------------------------------
SET @tbl_dfold := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'document_folders'
);
SET @sql_dfold := IF(@tbl_dfold = 0,
  "CREATE TABLE document_folders (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     condominium_id  BIGINT UNSIGNED NOT NULL,
     parent_id       BIGINT UNSIGNED NULL,
     name            VARCHAR(150) NOT NULL,
     created_by      BIGINT UNSIGNED NULL,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     KEY idx_dfold_condo (condominium_id, parent_id),
     CONSTRAINT fk_dfold_condo  FOREIGN KEY (condominium_id) REFERENCES condominiums(id) ON DELETE CASCADE,
     CONSTRAINT fk_dfold_parent FOREIGN KEY (parent_id) REFERENCES document_folders(id) ON DELETE CASCADE,
     CONSTRAINT fk_dfold_user   FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @sql_dfold; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. documents: add folder_id ------------------------------------------------
SET @col_folder := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documents' AND COLUMN_NAME = 'folder_id'
);
SET @sql_folder := IF(@col_folder = 0,
  "ALTER TABLE documents ADD COLUMN folder_id BIGINT UNSIGNED NULL AFTER uploaded_by, ADD KEY idx_docs_folder (folder_id), ADD CONSTRAINT fk_docs_folder FOREIGN KEY (folder_id) REFERENCES document_folders(id) ON DELETE SET NULL",
  "DO 0");
PREPARE stmt FROM @sql_folder; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. maintenance_attachments -------------------------------------------------
SET @tbl_matt := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_attachments'
);
SET @sql_matt := IF(@tbl_matt = 0,
  "CREATE TABLE maintenance_attachments (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     request_id      BIGINT UNSIGNED NOT NULL,
     file_path       VARCHAR(500) NOT NULL,
     original_name   VARCHAR(200) NULL,
     mime_type       VARCHAR(100) NULL,
     size_bytes      BIGINT UNSIGNED NULL,
     uploaded_by     BIGINT UNSIGNED NULL,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     KEY idx_matt_req (request_id),
     CONSTRAINT fk_matt_req  FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
     CONSTRAINT fk_matt_user FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @sql_matt; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7. maintenance_comments ----------------------------------------------------
SET @tbl_mcom := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'maintenance_comments'
);
SET @sql_mcom := IF(@tbl_mcom = 0,
  "CREATE TABLE maintenance_comments (
     id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
     request_id      BIGINT UNSIGNED NOT NULL,
     user_id         BIGINT UNSIGNED NULL,
     body            TEXT NOT NULL,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     KEY idx_mcom_req (request_id),
     CONSTRAINT fk_mcom_req  FOREIGN KEY (request_id) REFERENCES maintenance_requests(id) ON DELETE CASCADE,
     CONSTRAINT fk_mcom_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @sql_mcom; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 8. deliveries: add locker_code, received_by_id, withdrawn_user_id ----------
SET @col_locker := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'deliveries' AND COLUMN_NAME = 'locker_code'
);
SET @sql_locker := IF(@col_locker = 0,
  "ALTER TABLE deliveries ADD COLUMN locker_code VARCHAR(40) NULL AFTER tracking_code",
  "DO 0");
PREPARE stmt FROM @sql_locker; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_recby := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'deliveries' AND COLUMN_NAME = 'received_by_id'
);
SET @sql_recby := IF(@col_recby = 0,
  "ALTER TABLE deliveries ADD COLUMN received_by_id BIGINT UNSIGNED NULL AFTER received_at, ADD CONSTRAINT fk_del_recby FOREIGN KEY (received_by_id) REFERENCES users(id) ON DELETE SET NULL",
  "DO 0");
PREPARE stmt FROM @sql_recby; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @col_wdby := (
  SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'deliveries' AND COLUMN_NAME = 'withdrawn_user_id'
);
SET @sql_wdby := IF(@col_wdby = 0,
  "ALTER TABLE deliveries ADD COLUMN withdrawn_user_id BIGINT UNSIGNED NULL AFTER withdrawn_by, ADD CONSTRAINT fk_del_wdby FOREIGN KEY (withdrawn_user_id) REFERENCES users(id) ON DELETE SET NULL",
  "DO 0");
PREPARE stmt FROM @sql_wdby; EXECUTE stmt; DEALLOCATE PREPARE stmt;
