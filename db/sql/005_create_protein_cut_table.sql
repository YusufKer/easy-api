-- Migration: Create protein_cut junction table
-- Date: 2025-12-05

CREATE TABLE IF NOT EXISTS `protein_cut` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cut_id` INT UNSIGNED NOT NULL,
    `protein_id` INT UNSIGNED NOT NULL,
    `price` DECIMAL(10, 2) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `cut_id` (`cut_id`),
    KEY `protein_id` (`protein_id`),
    CONSTRAINT `protein_cut_ibfk_1` FOREIGN KEY (`cut_id`) REFERENCES `cut` (`id`) ON DELETE CASCADE,
    CONSTRAINT `protein_cut_ibfk_2` FOREIGN KEY (`protein_id`) REFERENCES `protein` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
