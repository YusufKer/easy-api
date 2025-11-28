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
        $protein_id = $args['protein_id'];
        
        $get_protein = "SELECT id, name FROM protein WHERE id = ?";
        $protein_stmt = $this->db->prepare($get_protein);
        $protein_stmt->execute([$protein_id]);
        $protein = $protein_stmt->fetch();

        if (!$protein) {
            $payload = [
                'success' => false,
                'error' => 'Protein not found',
                'details' => ['protein_id' => $protein_id],
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
        $cuts_stmt->execute([$protein_id]);
        $cuts = $cuts_stmt->fetchAll();

        $get_flavours = "SELECT f.id, f.name, pf.price FROM flavour f
                         JOIN protein_flavour pf ON f.id = pf.flavour_id
                         WHERE pf.protein_id = ?";
        
        $flavours_stmt = $this->db->prepare($get_flavours);
        $flavours_stmt->execute([$protein_id]);
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

    public function addFlavourToProtein(Request $request, Response $response, array $args): Response {
        $protein_id = $args['protein_id'];
        $input = $request->getParsedBody();

        // Validate input
        $validator = new Validator($input);
        $validator->required('flavour_id')->integer();
        $validator->required('price')->numeric()->min(0);

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

        $flavour_id = $input['flavour_id'];
        $price = $input['price'];

        // check if protein exists
        $checkProteinQuery = 'SELECT id FROM protein WHERE id = ?';
        $checkProteinStmt = $this->db->prepare($checkProteinQuery);
        $checkProteinStmt->execute([$protein_id]);
        if (!$checkProteinStmt->fetch()) {
            $payload = [
                'success' => false,
                'error' => 'Protein not found',
                'details' => ['id' => $protein_id],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }
        // check if flavour exists
        $checkFlavourQuery = 'SELECT id FROM flavour WHERE id = ?';
        $checkFlavourStmt = $this->db->prepare($checkFlavourQuery);
        $checkFlavourStmt->execute([$flavour_id]);
        if (!$checkFlavourStmt->fetch()) {
            $payload = [
                'success' => false,
                'error' => 'Flavour not found',
                'details' => ['id' => $flavour_id],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }
        // Check if this flavour is already added to this protein
        $checkDuplicateQuery = 'SELECT 1 FROM protein_flavour WHERE protein_id = ? AND flavour_id = ?';
        $checkDuplicateStmt = $this->db->prepare($checkDuplicateQuery);
        $checkDuplicateStmt->execute([$protein_id, $flavour_id]);
        if ($checkDuplicateStmt->fetch()) {
            $payload = [
                'success' => false,
                'error' => 'Flavour already added to this protein',
                'details' => [
                    'protein_id' => $protein_id,
                    'flavour_id' => $flavour_id
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(409);
        }
        try {
            $insertFlavourQuery = 'INSERT INTO protein_flavour (protein_id, flavour_id, price) VALUES (?, ?, ?)';
            $insertFlavourStmt = $this->db->prepare($insertFlavourQuery);
            $insertFlavourStmt->execute([$protein_id, $flavour_id, $price]);
            $payload = [
                'success' => true,
                'message' => 'Flavour added to protein successfully',
                'data' => [
                    'protein_id' => $protein_id,
                    'flavour_id' => $flavour_id,
                    'price' => $price
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $payload = [
                'success' => false,
                'error' => 'Failed to add flavour to protein',
                'details' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    public function removeFlavourFromProtein(Request $request, Response $response, array $args): Response {
        $protein_id = $args['protein_id'];
        $input = $request->getParsedBody();
        $validator = new Validator($input);
        $validator->required('flavour_id')->integer();

        if($validator->fails()) {
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

        $flavour_id = $input['flavour_id'];

        $checkProteinQuery = 'SELECT id FROM protein WHERE id = ?';
        $checkProteinStmt = $this->db->prepare($checkProteinQuery);
        $checkProteinStmt->execute([$protein_id]);
        if (!$checkProteinStmt->fetch()) {
            $payload = [
                'success' => false,
                'error' => 'Protein not found',
                'details' => ['id' => $protein_id],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $checkFlavourQuery = 'SELECT id FROM flavour WHERE id = ?';
        $checkFlavourStmt = $this->db->prepare($checkFlavourQuery);
        $checkFlavourStmt->execute([$flavour_id]);
        if (!$checkFlavourStmt->fetch()) {
            $payload = [
                'success' => false,
                'error' => 'Flavour not found',
                'details' => ['id' => $flavour_id],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $checkIfLinkedQuery = 'SELECT id FROM protein_flavour WHERE protein_id = ? AND flavour_id = ?';
        $checkIfLinkedStmt = $this->db->prepare($checkIfLinkedQuery);
        $checkIfLinkedStmt->execute([$protein_id, $flavour_id]);

        $checkIfLinkedResult = $checkIfLinkedStmt->fetch();
        if ($checkIfLinkedResult) {
            try{
                $proteinFlavourId = $checkIfLinkedResult['id'];
                // if linked, delete the id from the
                $deleteProteinFlavourQuery = 'DELETE FROM protein_flavour WHERE id = ?';
                $deleteProteinStmt = $this->db->prepare($deleteProteinFlavourQuery);
                $deleteProteinStmt->execute([$proteinFlavourId]);
                
                $payload = [
                    'success' => true,
                    'message' => 'Flavour removed from protein successfully',
                    'data' => [
                        'protein_id' => $protein_id,
                        'flavour_id' => $flavour_id,
                    ],
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                $response->getBody()->write(json_encode($payload));
                return $response
                    ->withHeader('Content-Type', 'application/json');
            } catch(PDOException $e) {
                $payload = [
                    'success' => false,
                    'error' => 'Failed to remove flavour from protein',
                    'details' => $e->getMessage(),
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                $response->getBody()->write(json_encode($payload));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }

        } else {
            $payload = [
                'success' => false,
                'error' => 'Flavour not linked to protein',
                'details' => [
                    'flavour_id' => $flavour_id,
                    'protein_id' => $protein_id
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }
    }

    public function addCutToProtein(Request $request, Response $response, array $args): Response {
        $protein_id = $args['protein_id'];
        $input = $request->getParsedBody();

        // Validate input
        $validator = new Validator($input);
        $validator->required('cut_id')->integer();
        $validator->required('price')->numeric()->min(0);

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

        $cut_id = $input['cut_id'];
        $price = $input['price'];

        // check if protein exists
        $checkProteinQuery = 'SELECT id FROM protein WHERE id = ?';
        $checkProteinStmt = $this->db->prepare($checkProteinQuery);
        $checkProteinStmt->execute([$protein_id]);
        if (!$checkProteinStmt->fetch()) {
            $payload = [
                'success' => false,
                'error' => 'Protein not found',
                'details' => ['protein_id' => $protein_id],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // check if cut exists
        $checkCutQuery = 'SELECT id FROM cut WHERE id = ?';
        $checkCutStmt = $this->db->prepare($checkCutQuery);
        $checkCutStmt->execute([$cut_id]);
        if (!$checkCutStmt->fetch()) {
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

        // Check if this Cut is already added to this protein
        $checkDuplicateQuery = 'SELECT 1 FROM protein_cut WHERE protein_id = ? AND cut_id = ?';
        $checkDuplicateStmt = $this->db->prepare($checkDuplicateQuery);
        $checkDuplicateStmt->execute([$protein_id, $cut_id]);
        if ($checkDuplicateStmt->fetch()) {
            $payload = [
                'success' => false,
                'error' => 'Cut already added to this protein',
                'details' => [
                    'protein_id' => $protein_id,
                    'cut_id' => $cut_id
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(409);
        }


        try {
            $insertCutQuery = 'INSERT INTO protein_cut (protein_id, cut_id, price) VALUES (?, ?, ?)';
            $insertCutStmt = $this->db->prepare($insertCutQuery);
            $insertCutStmt->execute([$protein_id, $cut_id, $price]);
            $payload = [
                'success' => true,
                'message' => 'Cut added to protein successfully',
                'data' => [
                    'protein_id' => $protein_id,
                    'cut_id' => $cut_id,
                    'price' => $price
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $payload = [
                'success' => false,
                'error' => 'Failed to add cut to protein',
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
        $protein_id = $args['protein_id'];
        
        // Check if protein exists
        $checkQuery = 'SELECT id, name FROM protein WHERE id = ?';
        $checkStmt = $this->db->prepare($checkQuery);
        $checkStmt->execute([$protein_id]);
        $protein = $checkStmt->fetch();
        
        if (!$protein) {
            $payload = [
                'success' => false,
                'error' => 'Protein not found',
                'details' => ['protein_id' => $protein_id],
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
            $stmt1->execute([$protein_id]);
            
            $deleteFlavours = 'DELETE FROM protein_flavour WHERE protein_id = ?';
            $stmt2 = $this->db->prepare($deleteFlavours);
            $stmt2->execute([$protein_id]);
            
            // Delete the protein itself
            $deleteProtein = 'DELETE FROM protein WHERE id = ?';
            $stmt3 = $this->db->prepare($deleteProtein);
            $stmt3->execute([$protein_id]);
            
            // Commit transaction
            $this->db->commit();
            
            $payload = [
                'success' => true,
                'message' => 'Protein deleted successfully',
                'data' => [
                    'protein_id' => $protein_id,
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
