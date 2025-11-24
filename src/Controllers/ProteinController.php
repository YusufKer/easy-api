<?php

namespace App\Controllers;

use App\Core\Response;
use App\Core\Validator;

class ProteinController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index() {
        $query = "SELECT id, name FROM protein";
        $stmt = $this->db->query($query);
        $proteins = $stmt->fetchAll();

        Response::success('Protein data retrieved successfully', $proteins);
    }

    public function getById($id) {
        $get_protein = "SELECT id, name FROM protein WHERE id = ?";
        
        $protein_stmt = $this->db->prepare($get_protein);
        $protein_stmt->execute([$id]);
        $protein = $protein_stmt->fetch();

        $get_cuts = "SELECT c.id, c.name, pc.price FROM cut c
                     JOIN protein_cut pc ON c.id = pc.cut_id
                     WHERE pc.protein_id = ?";
        
        $cuts_stmt = $this->db->prepare($get_cuts);
        $cuts_stmt->execute([$id]);
        $cuts = $cuts_stmt->fetchAll();

        $get_flavours = "SELECT f.id, f.name, pf.price FROM flavour f
                         JOIN protein_flavour pf ON f.id = pf.flavour_id
                         WHERE pf.protein_id = ?";
        
        $flavours_stmt = $this->db->prepare($get_flavours);
        $flavours_stmt->execute([$id]);
        $flavours = $flavours_stmt->fetchAll();

        if($protein){
            $protein['cuts'] = $cuts;
            $protein['flavours'] = $flavours;
            Response::success('Protein retrieved successfully', $protein);
        } else {
            Response::notFound('Protein not found', ['id' => $id]);
        }
    }

    public function addProtein(){
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        $validator = new Validator($input);
        $validator->required('name')->string()->min(2)->max(100);
        
        if ($validator->fails()) {
            Response::badRequest('Validation failed', $validator->errors());
        }
        
        $name = trim($input['name']);
        
        // Check if protein already exists
        $checkQuery = 'SELECT id FROM protein WHERE name = ?';
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$name]);
        
        if ($checkStmt->fetch()) {
            Response::conflict('Protein already exists', ['name' => $name]);
        }
        
        try {
            // Insert into database
            $query = 'INSERT INTO protein (name) VALUES (?)';
            $stmt = $this->db->prepare($query);
            $stmt->execute([$name]);
            
            $id = $this->db->lastInsertId();
            Response::created('Protein created successfully', [
                'id' => $id,
                'name' => $name
            ]);
        } catch (PDOException $e) {
            Response::serverError('Failed to create protein', $e->getMessage());
        }
    }

    public function deleteProtein($id) {
        // Check if protein exists
        $checkQuery = 'SELECT id, name FROM protein WHERE id = ?';
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$id]);
        $protein = $checkStmt->fetch();
        
        if (!$protein) {
            Response::notFound('Protein not found', ['id' => $id]);
        }
        
        try {
            // Start transaction for atomic deletion
            $this->db->beginTransaction();
            
            // Delete related records from junction tables
            $deleteCuts = 'DELETE FROM protein_cut WHERE protein_id = ?';
            $stmt1 = $this->db->prepare($deleteCuts);
            $stmt1->execute([$id]);
            
            $deleteFlavours = 'DELETE FROM protein_flavour WHERE protein_id = ?';
            $stmt2 = $this->db->prepare($deleteFlavours);
            $stmt2->execute([$id]);
            
            // Delete the protein itself
            $deleteProtein = 'DELETE FROM protein WHERE id = ?';
            $stmt3 = $this->db->prepare($deleteProtein);
            $stmt3->execute([$id]);
            
            // Commit transaction
            $this->db->commit();
            
            Response::success('Protein deleted successfully', [
                'id' => $id,
                'name' => $protein['name']
            ]);
        } catch (PDOException $e) {
            // Rollback on error
            $this->db->rollBack();
            Response::serverError('Failed to delete protein', $e->getMessage());
        }
    }
}
