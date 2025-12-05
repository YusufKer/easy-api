<?php

namespace App\Middleware;

use App\Models\User;
use App\Services\Logger;
use App\Utils\JwtHelper;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response as SlimResponse;
use PDO;
use Exception;

class AuthMiddleware implements MiddlewareInterface {
    
    private $userModel;
    private $optional;
    private ?Logger $logger;

    /**
     * @param PDO $db Database connection
     * @param bool $optional If true, allows requests without auth (user will be null)
     * @param Logger|null $logger Logger service
     */
    public function __construct(PDO $db, bool $optional = false, ?Logger $logger = null) {
        $this->userModel = new User($db);
        $this->optional = $optional;
        $this->logger = $logger;
    }
    
    public function process(Request $request, RequestHandler $handler): Response {
        // Try JWT authentication first
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (!empty($authHeader) && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            
            try {
                $decoded = JwtHelper::validateToken($token);
                $user = $this->userModel->findById($decoded->sub);
                
                if ($user && $user['is_active']) {
                    // Attach user to request (without password_hash)
                    $safeUser = [
                        'id' => $user['id'],
                        'email' => $user['email'],
                        'role' => $user['role']
                    ];
                    $request = $request->withAttribute('user', $safeUser)
                                       ->withAttribute('user_id', $user['id']);
                    
                    return $handler->handle($request);
                } else {
                    if ($this->logger) {
                        $this->logger->warning('JWT authentication failed - user not found or inactive', [
                            'user_id' => $decoded->sub ?? null
                        ]);
                    }
                    return $this->unauthorizedResponse('User not found or inactive');
                }
            } catch (Exception $e) {
                if ($this->logger) {
                    $this->logger->warning('JWT authentication failed - invalid token', [
                        'error' => $e->getMessage()
                    ]);
                }
                return $this->unauthorizedResponse('Invalid or expired token: ' . $e->getMessage());
            }
        }
        
        // Try API key authentication
        $apiKey = $request->getHeaderLine('X-API-Key');
        
        if (!empty($apiKey)) {
            $user = $this->userModel->findByApiKey($apiKey);
            
            if ($user) {
                // Attach user to request
                $safeUser = [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role']
                ];
                $request = $request->withAttribute('user', $safeUser)
                                   ->withAttribute('user_id', $user['id']);
                
                return $handler->handle($request);
            } else {
                if ($this->logger) {
                    $this->logger->warning('API key authentication failed - invalid key');
                }
                return $this->unauthorizedResponse('Invalid API key');
            }
        }
        
        // No authentication provided
        if ($this->optional) {
            // Optional auth - continue without user
            return $handler->handle($request);
        }
        
        // Required auth - return 401
        if ($this->logger) {
            $this->logger->warning('Missing authentication credentials');
        }
        return $this->unauthorizedResponse('Missing authentication. Provide Authorization header or X-API-Key');
    }
    
    /**
     * Create unauthorized response
     */
    private function unauthorizedResponse(string $message): Response {
        $response = new SlimResponse();
        $payload = [
            'success' => false,
            'error' => 'Unauthorized',
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        $response->getBody()->write(json_encode($payload));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
