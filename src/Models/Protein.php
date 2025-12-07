<?php

namespace App\Models;

use PDO;
use PDOException;

class Protein
{
    private $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Get all proteins
     */
    public function findAll(): array
    {
        $query = "SELECT id, name FROM protein";
        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    /**
     * Find protein by ID
     */
    public function findById(int $id): ?array
    {
        $query = "SELECT id, name FROM protein WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Check if protein exists by name
     */
    public function existsByName(string $name): bool
    {
        $query = 'SELECT id FROM protein WHERE name = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$name]);
        return $stmt->fetch() !== false;
    }

    /**
     * Create new protein
     */
    public function create(string $name): int
    {
        $query = 'INSERT INTO protein (name) VALUES (?)';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$name]);
        return (int) $this->db->lastInsertId();
    }

    /**
     * Get cuts for a protein
     */
    public function getCuts(int $proteinId): array
    {
        $query = "SELECT c.id, c.name, pc.price FROM cut c
                  JOIN protein_cut pc ON c.id = pc.cut_id
                  WHERE pc.protein_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$proteinId]);
        return $stmt->fetchAll();
    }

    /**
     * Get flavours for a protein
     */
    public function getFlavours(int $proteinId): array
    {
        $query = "SELECT f.id, f.name, pf.price FROM flavour f
                  JOIN protein_flavour pf ON f.id = pf.flavour_id
                  WHERE pf.protein_id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$proteinId]);
        return $stmt->fetchAll();
    }

    /**
     * Check if flavour is linked to protein
     */
    public function hasFlavour(int $proteinId, int $flavourId): bool
    {
        $query = 'SELECT 1 FROM protein_flavour WHERE protein_id = ? AND flavour_id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$proteinId, $flavourId]);
        return $stmt->fetch() !== false;
    }

    /**
     * Add flavour to protein
     */
    public function addFlavour(int $proteinId, int $flavourId, float $price): void
    {
        $query = 'INSERT INTO protein_flavour (protein_id, flavour_id, price) VALUES (?, ?, ?)';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$proteinId, $flavourId, $price]);
    }

    /**
     * Get protein_flavour record
     */
    public function getProteinFlavour(int $proteinId, int $flavourId): ?array
    {
        $query = 'SELECT id, price FROM protein_flavour WHERE protein_id = ? AND flavour_id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$proteinId, $flavourId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Update flavour price for protein
     */
    public function updateFlavourPrice(int $proteinFlavourId, float $price): void
    {
        $query = 'UPDATE protein_flavour SET price = ? WHERE id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$price, $proteinFlavourId]);
    }

    /**
     * Remove flavour from protein
     */
    public function removeFlavour(int $proteinId, int $flavourId): void
    {
        $query = 'DELETE FROM protein_flavour WHERE protein_id = ? AND flavour_id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$proteinId, $flavourId]);
    }

    /**
     * Check if cut is linked to protein
     */
    public function hasCut(int $proteinId, int $cutId): bool
    {
        $query = 'SELECT 1 FROM protein_cut WHERE protein_id = ? AND cut_id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$proteinId, $cutId]);
        return $stmt->fetch() !== false;
    }

    /**
     * Add cut to protein
     */
    public function addCut(int $proteinId, int $cutId, float $price): void
    {
        $query = 'INSERT INTO protein_cut (protein_id, cut_id, price) VALUES (?, ?, ?)';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$proteinId, $cutId, $price]);
    }

    /**
     * Get protein_cut record
     */
    public function getProteinCut(int $proteinId, int $cutId): ?array
    {
        $query = 'SELECT id, price FROM protein_cut WHERE protein_id = ? AND cut_id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$proteinId, $cutId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Update cut price for protein
     */
    public function updateCutPrice(int $proteinCutId, float $price): void
    {
        $query = 'UPDATE protein_cut SET price = ? WHERE id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$price, $proteinCutId]);
    }

    /**
     * Remove cut from protein
     */
    public function removeCut(int $proteinId, int $cutId): void
    {
        $query = 'DELETE FROM protein_cut WHERE protein_id = ? AND cut_id = ?';
        $stmt = $this->db->prepare($query);
        $stmt->execute([$proteinId, $cutId]);
    }

    /**
     * Delete protein (related records are automatically deleted via CASCADE)
     */
    public function delete(int $id): void
    {
        try {
            $query = 'DELETE FROM protein WHERE id = ?';
            $stmt = $this->db->prepare($query);
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw $e;
        }
    }
}
