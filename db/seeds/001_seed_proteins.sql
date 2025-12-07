-- Seed: Populate protein table
-- Date: 2025-12-07

INSERT INTO `protein` (`name`) VALUES
    ('Beef'),
    ('Chicken'),
    ('Lamb'),
    ('Pork'),
    ('Turkey'),
    ('Duck'),
    ('Fish'),
    ('Prawns')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
