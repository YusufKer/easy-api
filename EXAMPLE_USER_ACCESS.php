<?php
/**
 * Example Controller Methods Showing User Access
 * 
 * This demonstrates how to access authenticated user information
 * in your controllers after the AuthMiddleware has processed the request.
 */

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Services\Logger;

class ExampleController {
    
    private Logger $logger;
    
    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
    
    /**
     * Example: Get current user's ID directly
     */
    public function getUserId(Request $request): ?int {
        return $request->getAttribute('user_id');
    }
    
    /**
     * Example: Get full user object
     */
    public function getUser(Request $request): ?array {
        return $request->getAttribute('user');
        // Returns: ['id' => 123, 'email' => 'user@example.com', 'role' => 'admin']
    }
    
    /**
     * Example: Using user_id in audit logs
     */
    public function updateSomething(Request $request, Response $response): Response {
        $userId = $request->getAttribute('user_id');
        $user = $request->getAttribute('user');
        
        // Your business logic here
        $oldValue = 'old';
        $newValue = 'new';
        
        // Audit log with user information
        $this->logger->audit('Something updated', [
            'action' => 'update_something',
            'old_value' => $oldValue,
            'new_value' => $newValue,
            'user_id' => $userId,           // Will now have actual ID
            'user_email' => $user['email']   // Bonus: can also log email
        ]);
        
        $payload = [
            'success' => true,
            'message' => 'Updated successfully',
            'updated_by' => $user['email']
        ];
        
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
    
    /**
     * Example: Check user role for authorization
     */
    public function adminOnly(Request $request, Response $response): Response {
        $user = $request->getAttribute('user');
        
        if ($user['role'] !== 'admin') {
            $payload = [
                'success' => false,
                'error' => 'Forbidden',
                'message' => 'Admin access required'
            ];
            $response->getBody()->write(json_encode($payload));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }
        
        // Admin-only logic here
        $payload = ['success' => true, 'message' => 'Admin access granted'];
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
