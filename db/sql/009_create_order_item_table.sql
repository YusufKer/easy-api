-- Migration: Create order_item table
-- Date: 2025-12-07

CREATE TABLE IF NOT EXISTS `order_item` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_id` INT UNSIGNED NOT NULL,
    `protein_id` INT UNSIGNED NOT NULL,
    `cut_id` INT UNSIGNED NOT NULL,
    `flavour_id` INT UNSIGNED NULL,
    `quantity` DECIMAL(10, 3) NOT NULL,
    `cut_price` DECIMAL(10, 2) NOT NULL,
    `flavour_price` DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    `unit_price` DECIMAL(10, 2) NOT NULL,
    `subtotal` DECIMAL(10, 2) NOT NULL,
    `notes` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `order_id` (`order_id`),
    CONSTRAINT `order_item_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `order` (`id`) ON DELETE CASCADE,
    CONSTRAINT `order_item_ibfk_2` FOREIGN KEY (`protein_id`) REFERENCES `protein` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `order_item_ibfk_3` FOREIGN KEY (`cut_id`) REFERENCES `cut` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `order_item_ibfk_4` FOREIGN KEY (`flavour_id`) REFERENCES `flavour` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
