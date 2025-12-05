<?php

namespace App\Controllers;

use App\Core\Validator;
use App\Models\Cut;
use App\Services\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;

class CutsController {
    private $cutModel;
    private Logger $logger;

    public function __construct(Cut $cutModel, Logger $logger) {
        $this->cutModel = $cutModel;
        $this->logger = $logger;
    }

    public function index(Request $request, Response $response): Response {
        $cuts = $this->cutModel->findAll();

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
        if ($this->cutModel->existsByName($name)) {
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
            
            $id = $this->cutModel->create($name);
            
            // Audit log the creation
            $this->logger->audit('Cut created', [
                'action' => 'create_cut',
                'cut_id' => $id,
                'cut_name' => $name,
                'user_id' => $request->getAttribute('user_id') ?? null
            ]);
            
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
        $cut_id = $args['cut_id'];
        
        // Check if cut exists
        $cut = $this->cutModel->findById($cut_id);

        if (!$cut) {
            $payload = [
                'success' => false,
                'error' => 'Cut not found',
                'details' => ['cut_id' => $cut_id],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        try {
            
            $this->cutModel->delete($cut_id);
            
            // Audit log the deletion
            $this->logger->audit('Cut deleted', [
                'action' => 'delete_cut',
                'cut_id' => $cut_id,
                'cut_name' => $cut['name'],
                'user_id' => $request->getAttribute('user_id') ?? null
            ]);

            $payload = [
                'success' => true,
                'message' => 'Cut deleted successfully',
                'data' => [
                    'cut_id' => $cut_id,
                    'name' => $cut['name']
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
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