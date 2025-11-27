<?php

namespace App\Controllers;

use App\Core\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;

class ProteinController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index(Request $request, Response $response): Response {
        $query = "SELECT id, name FROM protein";
        $stmt = $this->db->query($query);
        $proteins = $stmt->fetchAll();

        $payload = [
            'success' => true,
            'message' => 'Protein data retrieved successfully',
            'data' => $proteins,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getById(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        
        $get_protein = "SELECT id, name FROM protein WHERE id = ?";
        $protein_stmt = $this->db->prepare($get_protein);
        $protein_stmt->execute([$id]);
        $protein = $protein_stmt->fetch();

        if (!$protein) {
            $payload = [
                'success' => false,
                'error' => 'Protein not found',
                'details' => ['id' => $id],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

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

        $protein['cuts'] = $cuts;
        $protein['flavours'] = $flavours;

        $payload = [
            'success' => true,
            'message' => 'Protein retrieved successfully',
            'data' => $protein,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function addProtein(Request $request, Response $response): Response {
        $input = $request->getParsedBody();
        
        // Validate input
        $validator = new Validator($input);
        $validator->required('name')->string()->min(2)->max(100);
        
        if ($validator->fails()) {
            $payload = [
                'success' => false,
                'error' => 'Validation failed',
                'details' => $validator->errors(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
        
        $name = trim($input['name']);
        
        // Check if protein already exists
        $checkQuery = 'SELECT id FROM protein WHERE name = ?';
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$name]);
        
        if ($checkStmt->fetch()) {
            $payload = [
                'success' => false,
                'error' => 'Protein already exists',
                'details' => ['name' => $name],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(409);
        }
        
        try {
            // Insert into database
            $query = 'INSERT INTO protein (name) VALUES (?)';
            $stmt = $this->db->prepare($query);
            $stmt->execute([$name]);
            
            $id = $this->db->lastInsertId();
            $payload = [
                'success' => true,
                'message' => 'Protein created successfully',
                'data' => [
                    'id' => $id,
                    'name' => $name
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201);
        } catch (PDOException $e) {
            $payload = [
                'success' => false,
                'error' => 'Failed to create protein',
                'details' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    public function deleteProtein(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        
        // Check if protein exists
        $checkQuery = 'SELECT id, name FROM protein WHERE id = ?';
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$id]);
        $protein = $checkStmt->fetch();
        
        if (!$protein) {
            $payload = [
                'success' => false,
                'error' => 'Protein not found',
                'details' => ['id' => $id],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
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
            
            $payload = [
                'success' => true,
                'message' => 'Protein deleted successfully',
                'data' => [
                    'id' => $id,
                    'name' => $protein['name']
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            // Rollback on error
            $this->db->rollBack();
            $payload = [
                'success' => false,
                'error' => 'Failed to delete protein',
                'details' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
}
