<?php

namespace App\Middleware;

use App\Core\Middleware\MiddlewareInterface;
use App\Core\Request;

class AuthMiddleware implements MiddlewareInterface {
    
    public function handle(Request $request, callable $next) {
        // TODO: Implement real authentication logic
        // For now, we'll just log that auth middleware ran and allow all requests
        
        // Example of what you'd do in production:
        // $token = $request->header('Authorization');
        // 
        // if (!$token) {
        //     Response::unauthorized('Missing authentication token');
        // }
        // 
        // if (!$this->isValidToken($token)) {
        //     Response::unauthorized('Invalid or expired token');
        // }
        // 
        // Attach user to request for use in controllers
        // $request->user = $this->getUserFromToken($token);
        
        // For now: just pass through
        error_log("AuthMiddleware: Request to {$request->uri()} - Currently allowing all requests");
        
        // Call the next middleware or controller
        return $next($request);
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
