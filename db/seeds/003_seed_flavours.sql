-- Seed: Populate flavour table
-- Date: 2025-12-07

INSERT INTO `flavour` (`name`) VALUES
    ('Lemon and Herb'),
    ('Peri-Peri'),
    ('Spicy BBQ'),
    ('Garlic and Rosemary'),
    ('Honey Mustard'),
    ('Teriyaki'),
    ('Smokey BBQ'),
    ('Cajun Spice'),
    ('Mild BBQ'),
    ('Hot Peri-Peri'),
    ('Garlic Butter'),
    ('Tandoori'),
    ('Sweet Chili'),
    ('Mediterranean'),
    ('Jerk'),
    ('Lemon Pepper'),
    ('Original'),
    ('Herb and Spice'),
    ('Piri-Piri'),
    ('Chimichurri')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);
