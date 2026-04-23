CREATE TABLE IF NOT EXISTS `ilceler` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `il` VARCHAR(120) NOT NULL,
  `ilce` VARCHAR(120) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_il_ilce` (`il`,`ilce`),
  KEY `idx_il` (`il`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
