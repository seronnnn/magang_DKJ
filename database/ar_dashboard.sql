-- ============================================================
-- DKJ AR Dashboard — Database Schema + Dummy Data
-- Compatible with MySQL 5.7+ / MariaDB 10.3+
-- ============================================================

CREATE DATABASE IF NOT EXISTS dkj_ar_dashboard CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dkj_ar_dashboard;

-- ============================================================
-- TABLE: ar_data
-- ============================================================
DROP TABLE IF EXISTS `ar_data`;
CREATE TABLE `ar_data` (
  `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `plant`           VARCHAR(10)     NOT NULL COMMENT 'Plant/branch code e.g. 1511, 1512, 1515, 1516',
  `customer_id`     VARCHAR(20)     NOT NULL COMMENT 'SAP Customer ID',
  `customer_name`   VARCHAR(150)    NOT NULL,
  `collection_by`   VARCHAR(50)     NOT NULL COMMENT 'Collector name (Mega, Miya, Viona, Risa)',
  `current`         BIGINT          NOT NULL DEFAULT 0 COMMENT 'Current AR (IDR)',
  `days_1_30`       BIGINT          NOT NULL DEFAULT 0 COMMENT '1-30 days aging (IDR)',
  `days_30_60`      BIGINT          NOT NULL DEFAULT 0 COMMENT '30-60 days aging (IDR)',
  `days_60_90`      BIGINT          NOT NULL DEFAULT 0 COMMENT '60-90 days aging (IDR)',
  `days_over_90`    BIGINT          NOT NULL DEFAULT 0 COMMENT 'Over 90 days aging (IDR)',
  `total`           BIGINT          NOT NULL DEFAULT 0 COMMENT 'Total AR (IDR)',
  `so_without_od`   INT             NOT NULL DEFAULT 0 COMMENT 'Sales Orders without overdue',
  `so_with_od`      INT             NOT NULL DEFAULT 0 COMMENT 'Sales Orders with overdue',
  `total_so`        INT             NOT NULL DEFAULT 0 COMMENT 'Total Sales Orders',
  `ar_target`       BIGINT          NOT NULL DEFAULT 0 COMMENT 'AR collection target (IDR)',
  `ar_actual`       BIGINT          NOT NULL DEFAULT 0 COMMENT 'AR collection actual (IDR)',
  `period`          DATE            NOT NULL DEFAULT '2026-01-31' COMMENT 'Reporting period',
  `created_at`      TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`      TIMESTAMP       NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_plant`         (`plant`),
  KEY `idx_customer_id`   (`customer_id`),
  KEY `idx_collection_by` (`collection_by`),
  KEY `idx_period`        (`period`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DUMMY DATA — 25 rows spanning 4 plants & 4 collectors
-- ============================================================
INSERT INTO `ar_data`
  (`plant`, `customer_id`, `customer_name`, `collection_by`,
   `current`, `days_1_30`, `days_30_60`, `days_60_90`, `days_over_90`, `total`,
   `so_without_od`, `so_with_od`, `total_so`,
   `ar_target`, `ar_actual`, `period`)
VALUES
-- Plant 1511 · Collector: Mega
('1511','3000000305','AMCOR FLEXIBLES INDONESIA','Mega',
 22755000,0,0,0,0,22755000, 0,5,5, 0,91575000,'2026-01-31'),

('1511','3000006597','ANEKA KENCANA PLASTINDO','Mega',
 1100010000,0,0,0,0,1100010000, 0,0,0, 1190475000,501720000,'2026-01-31'),

('1511','3000009691','BUDI STARCH & SWEETENER TBK','Mega',
 223776000,0,0,0,0,223776000, 1,3,4, 359640000,539460000,'2026-01-31'),

('1511','3000012902','CIPTA KARYA SUKSES ABADI','Mega',
 865245000,220890000,0,0,0,1086135000, 19,2,21, 449550000,449550000,'2026-01-31'),

('1511','3000017065','GUANLONG PACKINGS INDONESIA','Mega',
 2275500000,0,0,0,0,2275500000, 1,18,19, 999000000,1110000000,'2026-01-31'),

('1511','3000002495','INDOFOOD CBP SUKSES MAKMUR Tbk','Mega',
 1569262500,9090900,0,0,0,1578353400, 0,13,13, 827244150,1401735750,'2026-01-31'),

('1511','3000002784','JAYA NURIMBA','Mega',
 1528470000,0,0,0,0,1528470000, 5,7,12, 0,0,'2026-01-31'),

('1511','3000007508','MITRA KARYA PLASTINDO','Mega',
 123465744,73730640,0,115896787,316934663,630027834, 0,0,0, 0,75500000,'2026-01-31'),

('1511','3000004476','PRALON','Mega',
 490768296,0,0,0,0,490768296, 0,0,0, 247531943,119769000,'2026-01-31'),

('1511','3000005866','TIRTA ALAM SEGAR','Mega',
 224430900,660156960,0,0,0,884587860, 0,0,0, 892462200,1047974310,'2026-01-31'),

-- Plant 1511 · Collector: Viona
('1511','3000000574','ASIETEX SINAR INDOPRATAMA','Viona',
 1086626166,317009007,0,0,0,1403635173, 0,14,14, 248099674,270296633,'2026-01-31'),

('1511','3000025902','BAROKAH MITRA USAHA LANCAR','Viona',
 0,0,0,0,211412178,211412178, 2,3,5, 278401592,0,'2026-01-31'),

('1511','3000001614','EASTERNTEX','Viona',
 463384584,616063864,0,0,0,1079448448, 0,1,1, 412456275,0,'2026-01-31'),

('1511','3000002003','GLORIA ORIGITA COSMETICS','Viona',
 230594230,0,0,0,0,230594230, 0,4,4, 135515282,137470780,'2026-01-31'),

('1511','3000002879','KAHATEX','Viona',
 990326016,0,0,0,0,990326016, 0,9,9, 435031200,0,'2026-01-31'),

-- Plant 1512 · Collector: Miya
('1512','3000000735','BASF INDONESIA','Miya',
 4919670921,0,0,0,0,4919670921, 0,0,0, 3216693776,1130685648,'2026-01-31'),

('1512','3000007206','EAGLE INDO PHARMA','Miya',
 3427948359,0,0,0,0,3427948359, 0,0,0, 3250891910,2067427066,'2026-01-31'),

('1512','3000002277','HENKEL ADHESIVE TECHNOLOGIES','Miya',
 1439753250,0,0,0,0,1439753250, 0,5,5, 360930375,360389250,'2026-01-31'),

('1512','3000023872','PUPUK LAPAN HARSA','Miya',
 14012935538,0,0,0,0,14012935538, 0,7,7, 0,0,'2026-01-31'),

('1512','3000004612','PZ CUSSONS INDONESIA','Miya',
 2340146466,0,0,0,0,2340146466, 0,11,11, 1620745365,1004271678,'2026-01-31'),

('1512','3000006208','VICTORIA CARE INDONESIA TBK','Miya',
 2858595627,0,0,0,0,2858595627, 0,16,16, 1465499591,1927252148,'2026-01-31'),

-- Plant 1515 · Collector: Risa
('1515','3000002090','GUNUNG MELAYU','Risa',
 486647144,0,0,0,0,486647144, 0,0,0, 0,0,'2026-01-31'),

('1515','3000003933','MUSIM MAS','Risa',
 832027140,13286700,0,0,0,845313840, 2,3,5, 34265700,0,'2026-01-31'),

('1515','3000005561','SUPRA MATRA ABADI','Risa',
 1276400933,0,0,0,0,1276400933, 0,0,0, 0,0,'2026-01-31'),

-- Plant 1516 · Collector: Mega
('1516','3000012169','AVIA AVIAN INDUSTRI PIPA','Mega',
 635697000,0,0,0,0,635697000, 0,0,0, 0,0,'2026-01-31');

-- ============================================================
-- VERIFICATION QUERIES (optional — run to spot-check)
-- ============================================================
-- SELECT plant, COUNT(*) AS rows, SUM(total) AS total_ar FROM ar_data GROUP BY plant;
-- SELECT collection_by, COUNT(*) AS customers, SUM(ar_actual) AS collected FROM ar_data GROUP BY collection_by;
