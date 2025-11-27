<?php

namespace App\Controllers;

use App\Core\Validator;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;

class FlavoursController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index(Request $request, Response $response): Response {
        $query = "SELECT id, name FROM flavour";
        $stmt = $this->db->query($query);
        $flavours = $stmt->fetchAll();

        $payload = [
            'success' => true,
            'message' => 'Flavour data retrieved successfully',
            'data' => $flavours,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function addFlavour(Request $request, Response $response): Response {
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

        // Check if flavour already exists
        $checkQuery = 'SELECT id FROM flavour WHERE name = ?';
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$name]);

        if ($checkStmt->fetch()) {
            $payload = [
                'success' => false,
                'error' => 'Flavour already exists',
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
            $query = 'INSERT INTO flavour (name) VALUES (?)';
            $stmt = $this->db->prepare($query);
            $stmt->execute([$name]);

            $id = $this->db->lastInsertId();
            $payload = [
                'success' => true,
                'message' => 'Flavour created successfully',
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
                'error' => 'Failed to create Flavour',
                'details' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    public function deleteFlavour(Request $request, Response $response, array $args): Response {
        $id = $args['id'];
        
        // Check if flavour exists
        $checkQuery = 'SELECT id, name FROM flavour WHERE id = ?';
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$id]);
        $flavour = $checkStmt->fetch();

        if (!$flavour) {
            $payload = [
                'success' => false,
                'error' => 'Flavour not found',
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
            $deleteProteinFlavour = 'DELETE FROM protein_flavour WHERE flavour_id = ?';
            $stmt1 = $this->db->prepare($deleteProteinFlavour);
            $stmt1->execute([$id]);

            // Delete the flavour itself
            $deleteFlavour = 'DELETE FROM flavour WHERE id = ?';
            $stmt3 = $this->db->prepare($deleteFlavour);
            $stmt3->execute([$id]);

            // Commit transaction
            $this->db->commit();

            $payload = [
                'success' => true,
                'message' => 'Flavour deleted successfully',
                'data' => [
                    'id' => $id,
                    'name' => $flavour['name']
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
                'error' => 'Failed to delete flavour',
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