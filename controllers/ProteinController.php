<?php

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

        if($protein){
            $protein['cuts'] = $cuts;
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
}
