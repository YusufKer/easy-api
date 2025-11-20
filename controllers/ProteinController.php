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
        $query = "SELECT id, name FROM protein WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if($row){
            Response::success('Protein retrieved successfully', $row);
        } else {
            Response::notFound('Protein not found', ['id' => $id]);
        }
    }

    public function addProtein(){
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!isset($input['name']) || empty(trim($input['name']))) {
            Response::badRequest('Name is required');
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
