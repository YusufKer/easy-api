<?php

namespace App\Models;

use PDO;
use PDOException;

class Cut
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get all cuts
     */
    public function findAll(): array
    {
        $query = "SELECT id, name FROM cut";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    /**
     * Find cut by ID
     */
    public function findById(int $id): ?array
    {
        $query = "SELECT id, name FROM cut WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Check if cut exists by name
     */
    public function existsByName(string $name): bool
    {
        $query = 'SELECT id FROM cut WHERE name = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$name]);
        return $stmt->fetch() !== false;
    }

    /**
     * Create new cut
     */
    public function create(string $name): int
    {
        $query = 'INSERT INTO cut (name) VALUES (?)';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$name]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Delete cut and all related records
     */
    public function delete(int $id): void
    {
        $this->db->beginTransaction();
        
        try {
            // Delete related records from junction table
            $deleteProteinCut = 'DELETE FROM protein_cut WHERE cut_id = ?';
            $stmt1 = $this->db->prepare($deleteProteinCut);
            $stmt1->execute([$id]);
            
            // Delete the cut
            $deleteCut = 'DELETE FROM cut WHERE id = ?';
            $stmt2 = $this->db->prepare($deleteCut);
            $stmt2->execute([$id]);
            
            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
