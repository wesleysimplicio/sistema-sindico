-- Sprint 5 review hardening migration:
-- 1. webhook_nonces table for replay protection on /api/webhooks/access-event
-- 2. composite indexes on access_logs (cid, unit_id, occurred_at) and incidents (cid, status)
-- 3. drop redundant single-column indexes superseded by the composite ones
-- Idempotent.

SET NAMES utf8mb4;

-- 1. webhook_nonces ----------------------------------------------------------
SET @t := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'webhook_nonces');
SET @s := IF(@t = 0,
  "CREATE TABLE webhook_nonces (
     signature_hash  CHAR(64) NOT NULL PRIMARY KEY,
     expires_at      DATETIME NOT NULL,
     created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
     KEY idx_wn_expires (expires_at)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. access_logs composite index (condominium_id, unit_id, occurred_at) -----
SET @i := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'access_logs'
             AND INDEX_NAME = 'idx_access_condo_unit_time');
SET @s := IF(@i = 0,
  "CREATE INDEX idx_access_condo_unit_time ON access_logs (condominium_id, unit_id, occurred_at)",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. incidents composite index (condominium_id, status) ---------------------
SET @i := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incidents'
             AND INDEX_NAME = 'idx_inc_condo_status');
SET @s := IF(@i = 0,
  "CREATE INDEX idx_inc_condo_status ON incidents (condominium_id, status)",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. drop redundant single-column status index (covered by composite above) -
SET @i := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'incidents'
             AND INDEX_NAME = 'idx_inc_status');
SET @s := IF(@i > 0,
  "DROP INDEX idx_inc_status ON incidents",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
