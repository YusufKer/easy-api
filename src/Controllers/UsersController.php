<?php

namespace App\Controllers;

use App\Core\Validator;
use App\Models\User;
use App\Services\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDOException;

class UsersController {
    private $userModel;
    private Logger $logger;

    public function __construct(User $userModel, Logger $logger) {
        $this->userModel = $userModel;
        $this->logger = $logger;
    }

    public function index(Request $request, Response $response): Response {
        $userId = $request->getAttribute('user_id');
        if(!$userId || !is_numeric($userId)) {
            $payload = [
                'success' => false,
                'error' => 'Invalid or missing user ID',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }
        try{
            $unsafeUser = $this->userModel->findById($userId);
            $user = $this->userModel->getSafeUser($unsafeUser);
            if(!$user) {
                $payload = [
                    'success' => false,
                    'error' => 'User not found',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
                $response->getBody()->write(json_encode($payload));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(404);
            }

            $userDetails = $this->userModel->getUserDetails($user['id']);
            $user['details'] = $userDetails;

            $userAddresses = $this->userModel->getUserAddresses($user['id']);
            $user['addresses'] = $userAddresses;

            $payload = [
                'success' => true,
                'message' => 'User data retrieved successfully',
                'data' => $user,
                'timestamp' => date('Y-m-d H:i:s')
            ];

            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json');
        }catch (PDOException $e){
            $payload = [
                'success' => false,
                'error' => 'Failed to get user',
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