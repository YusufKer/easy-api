CREATE TABLE order_plate_item (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `order_plate_id` INT UNSIGNED NOT NULL,
    `protein_id` INT UNSIGNED NOT NULL,
    `flavour_id` INT UNSIGNED NOT NULL,
    `cut_id` INT UNSIGNED NOT NULL,
    `meat_name` VARCHAR(100) NOT NULL,
    `flavour_name` VARCHAR(100) NOT NULL,
    `cut_name` VARCHAR(100) NOT NULL,
    `unit_price` DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (`order_plate_id`) REFERENCES `order_plate`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`protein_id`) REFERENCES `protein`(`id`),
    FOREIGN KEY (`flavour_id`) REFERENCES `flavour`(`id`),
    FOREIGN KEY (`cut_id`) REFERENCES `cut`(`id`),
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;