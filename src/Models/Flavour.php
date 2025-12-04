<?php

namespace App\Models;

use PDO;
use PDOException;

class Flavour
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get all flavours
     */
    public function findAll(): array
    {
        $query = "SELECT id, name FROM flavour";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    /**
     * Find flavour by ID
     */
    public function findById(int $id): ?array
    {
        $query = "SELECT id, name FROM flavour WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Check if flavour exists by name
     */
    public function existsByName(string $name): bool
    {
        $query = 'SELECT id FROM flavour WHERE name = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$name]);
        return $stmt->fetch() !== false;
    }

    /**
     * Create new flavour
     */
    public function create(string $name): int
    {
        $query = 'INSERT INTO flavour (name) VALUES (?)';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$name]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Delete flavour and all related records
     */
    public function delete(int $id): void
    {
        $this->db->beginTransaction();
        
        try {
            // Delete related records from junction table
            $deleteProteinFlavour = 'DELETE FROM protein_flavour WHERE flavour_id = ?';
            $stmt1 = $this->db->prepare($deleteProteinFlavour);
            $stmt1->execute([$id]);
            
            // Delete the flavour
            $deleteFlavour = 'DELETE FROM flavour WHERE id = ?';
            $stmt2 = $this->db->prepare($deleteFlavour);
            $stmt2->execute([$id]);
            
            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
