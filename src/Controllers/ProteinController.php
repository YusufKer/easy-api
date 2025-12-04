<?php

namespace App\Controllers;

use App\Core\Validator;
use App\Models\Protein;
use App\Models\Flavour;
use App\Models\Cut;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;

class ProteinController {
    private $proteinModel;
    private $flavourModel;
    private $cutModel;

    public function __construct(Protein $proteinModel, Flavour $flavourModel, Cut $cutModel) {
        $this->proteinModel = $proteinModel;
        $this->flavourModel = $flavourModel;
        $this->cutModel = $cutModel;
    }

    public function index(Request $request, Response $response): Response {
        $proteins = $this->proteinModel->findAll();

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
        
        $protein = $this->proteinModel->findById($protein_id);

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

        $protein['cuts'] = $this->proteinModel->getCuts($protein_id);
        $protein['flavours'] = $this->proteinModel->getFlavours($protein_id);

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
        if ($this->proteinModel->existsByName($name)) {
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
            $id = $this->proteinModel->create($name);
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
        if (!$this->proteinModel->findById($protein_id)) {
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
        if (!$this->flavourModel->findById($flavour_id)) {
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
        if ($this->proteinModel->hasFlavour($protein_id, $flavour_id)) {
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
            $this->proteinModel->addFlavour($protein_id, $flavour_id, $price);
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

    public function updateFlavourPriceForProtein(Request $request, Response $response, array $args): Response {
        $protein_id = $args['protein_id'];
        $flavour_id = $args['flavour_id'];
        $input = $request->getParsedBody();

        // Validate input
        $validator = new Validator($input);
        $validator->required('price')->numeric()->min(0);

        if ($validator->fails()){
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
        
        $proteinFlavour = $this->proteinModel->getProteinFlavour($protein_id, $flavour_id);

        if (!$proteinFlavour) {
            $payload = [
                'success' => false,
                'error' => 'Flavour not linked to protein',
                'details' => [
                    'protein_id' => $protein_id,
                    'flavour_id' => $flavour_id
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $newPrice = $input['price'];

        try {
            $this->proteinModel->updateFlavourPrice($proteinFlavour['id'], $newPrice);
            $payload = [
                'success' => true,
                'message' => 'Flavour price updated successfully for protein',
                'data' => [
                    'protein_id' => $protein_id,
                    'flavour_id' => $flavour_id,
                    'new_price' => $newPrice
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $payload = [
                'success' => false,
                'error' => 'Failed to update flavour price for protein',
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

        if (!$this->proteinModel->findById($protein_id)) {
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

        if (!$this->flavourModel->findById($flavour_id)) {
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

        if ($this->proteinModel->hasFlavour($protein_id, $flavour_id)) {
            try{
                $this->proteinModel->removeFlavour($protein_id, $flavour_id);
                
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
        if (!$this->proteinModel->findById($protein_id)) {
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
        if (!$this->cutModel->findById($cut_id)) {
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
        if ($this->proteinModel->hasCut($protein_id, $cut_id)) {
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
            $this->proteinModel->addCut($protein_id, $cut_id, $price);
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

    public function updateCutPriceForProtein(Request $request, Response $response, array $args): Response {
        $protein_id = $args['protein_id'];
        $cut_id = $args['cut_id'];
        $input = $request->getParsedBody();

        // Validate input
        $validator = new Validator($input);
        $validator->required('price')->numeric()->min(0);

        if ($validator->fails()){
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
        
        $proteinCut = $this->proteinModel->getProteinCut($protein_id, $cut_id);

        if (!$proteinCut) {
            $payload = [
                'success' => false,
                'error' => 'Cut not linked to protein',
                'details' => [
                    'protein_id' => $protein_id,
                    'cut_id' => $cut_id
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $newPrice = $input['price'];

        try {
            $this->proteinModel->updateCutPrice($proteinCut['id'], $newPrice);
            $payload = [
                'success' => true,
                'message' => 'Cut price updated successfully for protein',
                'data' => [
                    'protein_id' => $protein_id,
                    'cut_id' => $cut_id,
                    'new_price' => $newPrice
                ],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $payload = [
                'success' => false,
                'error' => 'Failed to update cut price for protein',
                'details' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    public function removeCutFromProtein(Request $request, Response $response, array $args): Response {
        $protein_id = $args['protein_id'];
        $input = $request->getParsedBody();
        $validator = new Validator($input);
        $validator->required('cut_id')->integer();

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

        $cut_id = $input['cut_id'];

        if (!$this->proteinModel->findById($protein_id)) {
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

        if (!$this->cutModel->findById($cut_id)) {
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

        if ($this->proteinModel->hasCut($protein_id, $cut_id)) {
            try{
                $this->proteinModel->removeCut($protein_id, $cut_id);
                
                $payload = [
                    'success' => true,
                    'message' => 'Cut removed from protein successfully',
                    'data' => [
                        'protein_id' => $protein_id,
                        'cut_id' => $cut_id,
                    ],
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                $response->getBody()->write(json_encode($payload));
                return $response
                    ->withHeader('Content-Type', 'application/json');
            } catch(PDOException $e) {
                $payload = [
                    'success' => false,
                    'error' => 'Failed to remove cut from protein',
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
                'error' => 'Cut not linked to protein',
                'details' => [
                    'cut_id' => $cut_id,
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

    public function deleteProtein(Request $request, Response $response, array $args): Response {
        $protein_id = $args['protein_id'];
        
        // Check if protein exists
        $protein = $this->proteinModel->findById($protein_id);
        
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
            $this->proteinModel->delete($protein_id);
            
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
