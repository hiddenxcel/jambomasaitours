-- ═══════════════════════════════════════════════════
--  JAMBO MASAI TOURS — Site Settings Table
--  Import via phpMyAdmin into: jambo_masai_tours
-- ═══════════════════════════════════════════════════

CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_key`   VARCHAR(80)  NOT NULL PRIMARY KEY,
  `setting_value` TEXT,
  `updated_at`    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `site_settings` (`setting_key`, `setting_value`) VALUES
('logo_url',        ''),
('logo_width',      '160'),
('site_name',       'Jambo Masai Tours'),
('site_tagline',    'Tanzania Safari Experts')
ON DUPLICATE KEY UPDATE `setting_key` = `setting_key`;
