<?php

namespace App\Controllers;

use App\Core\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;

class CutsController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index(Request $request, Response $response): Response {
        $query = "SELECT id, name FROM cut";
        $stmt = $this->db->query($query);
        $cuts = $stmt->fetchAll();

        $payload = [
            'success' => true,
            'message' => 'Cuts data retrieved successfully',
            'data' => $cuts,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function addCut(Request $request, Response $response): Response {
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

        // Check if cut already exists
        $checkQuery = 'SELECT id FROM cut WHERE name = ?';
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$name]);

        if ($checkStmt->fetch()) {
            $payload = [
                'success' => false,
                'error' => 'Cut already exists',
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
            $query = 'INSERT INTO cut (name) VALUES (?)';
            $stmt = $this->db->prepare($query);
            $stmt->execute([$name]);

            $id = $this->db->lastInsertId();
            $payload = [
                'success' => true,
                'message' => 'Cut created successfully',
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
                'error' => 'Failed to create cut',
                'details' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    public function deleteCut(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        
        // Check if cut exists
        $checkQuery = 'SELECT id, name FROM cut WHERE id = ?';
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$id]);
        $cut = $checkStmt->fetch();

        if (!$cut) {
            $payload = [
                'success' => false,
                'error' => 'Cut not found',
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
            $deleteProteinCut = 'DELETE FROM protein_cut WHERE cut_id = ?';
            $stmt1 = $this->db->prepare($deleteProteinCut);
            $stmt1->execute([$id]);

            // Delete the cut itself
            $deleteCut = 'DELETE FROM cut WHERE id = ?';
            $stmt3 = $this->db->prepare($deleteCut);
            $stmt3->execute([$id]);

            // Commit transaction
            $this->db->commit();

            $payload = [
                'success' => true,
                'message' => 'Cut deleted successfully',
                'data' => [
                    'id' => $id,
                    'name' => $cut['name']
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
                'error' => 'Failed to delete cut',
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