-- Migration: Create protein_flavour table
-- Created: 2025-12-05

CREATE TABLE IF NOT EXISTS protein_flavour (
    id INT AUTO_INCREMENT PRIMARY KEY,
    protein_id INT NOT NULL,
    flavour_id INT NOT NULL,
    price DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (protein_id) REFERENCES protein(id) ON DELETE CASCADE,
    FOREIGN KEY (flavour_id) REFERENCES flavour(id) ON DELETE CASCADE
);
