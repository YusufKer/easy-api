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

        $response = [
            'message' => 'Protein data retrieved successfully',
            'data' => $proteins,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        echo json_encode($response);
    }

    public function getById($id) {
        $query = "SELECT id, name FROM protein WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if($row){
            $response = [
                'message' => 'Protein retrieved successfully',
                'data' => $row,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            echo json_encode($response);
        } else {
            http_response_code(404);
            echo json_encode([
                'error' => 'Protein not found',
                'id' => $id
            ]);
        }
    }

    public function addProtein(){
        // Get JSON input
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate input
        if (!isset($input['name']) || empty(trim($input['name']))) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Name is required'
            ]);
            return;
        }
        
        $name = trim($input['name']);
        
        try {
            // Insert into database
            $query = 'INSERT INTO protein (name) VALUES (?)';
            $stmt = $this->db->prepare($query);
            $stmt->execute([$name]);
            
            $id = $this->db->lastInsertId();
            http_response_code(201);
            echo json_encode([
                'message' => 'Protein created successfully',
                'data' => [
                    'id' => $id,
                    'name' => $name
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode([
                'error' => 'Failed to create protein',
                'details' => $e->getMessage()
            ]);
        }
    }
}
