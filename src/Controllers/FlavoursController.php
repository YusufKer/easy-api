<?php

namespace App\Controllers;

use App\Core\Validator;
use App\Models\Flavour;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;

class FlavoursController {
    private $flavourModel;

    public function __construct(Flavour $flavourModel) {
        $this->flavourModel = $flavourModel;
    }

    public function index(Request $request, Response $response): Response {
        $flavours = $this->flavourModel->findAll();

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
        if ($this->flavourModel->existsByName($name)) {
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
            $id = $this->flavourModel->create($name);
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
        $flavour_id = $args['flavour_id'];
        
        // Check if flavour exists
        $flavour = $this->flavourModel->findById($flavour_id);

        if (!$flavour) {
            $payload = [
                'success' => false,
                'error' => 'Flavour not found',
                'details' => ['flavour_id' => $flavour_id],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        try {
            $this->flavourModel->delete($flavour_id);

            $payload = [
                'success' => true,
                'message' => 'Flavour deleted successfully',
                'data' => [
                    'flavour_id' => $flavour_id,
                    'name' => $flavour['name']
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
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