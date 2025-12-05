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
     * Delete cut (related records are automatically deleted via CASCADE)
     */
    public function delete(int $id): void
    {
        try {
            $query = 'DELETE FROM cut WHERE id = ?';
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
