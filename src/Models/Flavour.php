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
     * Delete flavour (related records are automatically deleted via CASCADE)
     */
    public function delete(int $id): void
    {
        try {
            $query = 'DELETE FROM flavour WHERE id = ?';
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
