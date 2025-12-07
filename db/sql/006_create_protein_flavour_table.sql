-- Migration: Create protein_flavour junction table
-- Date: 2025-12-05

CREATE TABLE IF NOT EXISTS `protein_flavour` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `protein_id` INT UNSIGNED NOT NULL,
    `flavour_id` INT UNSIGNED NOT NULL,
    `price` DECIMAL(10, 2) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `protein_id` (`protein_id`),
    KEY `flavour_id` (`flavour_id`),
    CONSTRAINT `protein_flavour_ibfk_1` FOREIGN KEY (`protein_id`) REFERENCES `protein` (`id`) ON DELETE CASCADE,
    CONSTRAINT `protein_flavour_ibfk_2` FOREIGN KEY (`flavour_id`) REFERENCES `flavour` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
