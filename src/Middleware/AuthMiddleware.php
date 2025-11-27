<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface {
    
    public function process(Request $request, RequestHandler $handler): Response {
        // TODO: Implement real authentication logic
        // For now, we'll just log that auth middleware ran and allow all requests
        
        // Example of what you'd do in production:
        // $token = $request->getHeaderLine('Authorization');
        // 
        // if (empty($token)) {
        //     $response = new SlimResponse();
        //     $payload = [
        //         'success' => false,
        //         'error' => 'Missing authentication token',
        //         'timestamp' => date('Y-m-d H:i:s')
        //     ];
        //     $response->getBody()->write(json_encode($payload));
        //     return $response
        //         ->withHeader('Content-Type', 'application/json')
        //         ->withStatus(401);
        // }
        // 
        // if (!$this->isValidToken($token)) {
        //     $response = new SlimResponse();
        //     $payload = [
        //         'success' => false,
        //         'error' => 'Invalid or expired token',
        //         'timestamp' => date('Y-m-d H:i:s')
        //     ];
        //     $response->getBody()->write(json_encode($payload));
        //     return $response
        //         ->withHeader('Content-Type', 'application/json')
        //         ->withStatus(401);
        // }
        // 
        // Attach user to request for use in controllers
        // $request = $request->withAttribute('user', $this->getUserFromToken($token));
        
        // For now: just pass through
        error_log("AuthMiddleware: Request to {$request->getUri()->getPath()} - Currently allowing all requests");
        
        // Continue to next middleware or controller
        return $handler->handle($request);
    }
    
    /**
     * Validate JWT or API token (placeholder)
     */
    private function isValidToken(string $token): bool {
        // TODO: Implement token validation
        // - Decode JWT
        // - Check expiration
        // - Verify signature
        // - Check against database/cache
        
        return true; // Placeholder
    }
    
    /**
     * Extract user from token (placeholder)
     */
    private function getUserFromToken(string $token) {
        // TODO: Decode token and fetch user from database
        
        return null; // Placeholder
    }
}
