-- Sprint 7 — Performance composite indexes.
-- Idempotent: each index added only when missing (INFORMATION_SCHEMA.STATISTICS).

SET NAMES utf8mb4;

-- 1. notices(condominium_id, scope) — list by tenant filtering on scope.
SET @i := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notices'
             AND INDEX_NAME = 'idx_notices_condo_scope');
SET @s := IF(@i = 0,
  "ALTER TABLE notices ADD INDEX idx_notices_condo_scope (condominium_id, scope)",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. notices(condominium_id, pinned, published_at) — pinned-first list.
SET @i := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notices'
             AND INDEX_NAME = 'idx_notices_condo_pinned_pub');
SET @s := IF(@i = 0,
  "ALTER TABLE notices ADD INDEX idx_notices_condo_pinned_pub (condominium_id, pinned, published_at)",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 3. notice_reads(user_id, notice_id) — LEFT JOIN by user reading notice list.
SET @i := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'notice_reads'
             AND INDEX_NAME = 'idx_nread_user_notice');
SET @s := IF(@i = 0,
  "ALTER TABLE notice_reads ADD INDEX idx_nread_user_notice (user_id, notice_id)",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 4. payments(condominium_id, due_date) — overdue/upcoming dashboards.
SET @i := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments'
             AND INDEX_NAME = 'idx_pay_condo_due');
SET @s := IF(@i = 0,
  "ALTER TABLE payments ADD INDEX idx_pay_condo_due (condominium_id, due_date)",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 5. deliveries(condominium_id, received_at) — recency listing.
SET @i := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'deliveries'
             AND INDEX_NAME = 'idx_del_condo_recv');
SET @s := IF(@i = 0,
  "ALTER TABLE deliveries ADD INDEX idx_del_condo_recv (condominium_id, received_at)",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 6. visitors(condominium_id, expected_at) — schedule listing.
SET @i := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors'
             AND INDEX_NAME = 'idx_vis_condo_expected');
SET @s := IF(@i = 0,
  "ALTER TABLE visitors ADD INDEX idx_vis_condo_expected (condominium_id, expected_at)",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 7. deliveries(unit_id, received_at) — resident view.
SET @i := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'deliveries'
             AND INDEX_NAME = 'idx_del_unit_recv');
SET @s := IF(@i = 0,
  "ALTER TABLE deliveries ADD INDEX idx_del_unit_recv (unit_id, received_at)",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 8. visitors(unit_id, created_at) — resident view.
SET @i := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'visitors'
             AND INDEX_NAME = 'idx_vis_unit_created');
SET @s := IF(@i = 0,
  "ALTER TABLE visitors ADD INDEX idx_vis_unit_created (unit_id, created_at)",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 9. users(condominium_id, role) — role-scoped lookups (e.g. sindico bulk).
SET @i := (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS
           WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
             AND INDEX_NAME = 'idx_users_condo_role');
SET @s := IF(@i = 0,
  "ALTER TABLE users ADD INDEX idx_users_condo_role (condominium_id, role)",
  "DO 0");
PREPARE stmt FROM @s; EXECUTE stmt; DEALLOCATE PREPARE stmt;
