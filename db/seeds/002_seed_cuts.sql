-- Seed: Populate cut table
-- Date: 2025-12-07

INSERT INTO `cut` (`name`) VALUES
    ('Fillet'),
    ('Ribeye'),
    ('Sirloin'),
    ('T-Bone'),
    ('Rump'),
    ('Leg'),
    ('Thigh'),
    ('Breast'),
    ('Wing'),
    ('Drumstick'),
    ('Rack'),
    ('Chop'),
    ('Shank'),
    ('Shoulder'),
    ('Ribs'),
    ('Mince'),
    ('Sausage'),
    ('Whole'),
    ('Steak'),
    ('Skewers')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
