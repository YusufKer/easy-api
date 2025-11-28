<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use App\Utils\DebugLogger;

class DebugMiddleware implements MiddlewareInterface {
    
    public function process(Request $request, RequestHandler $handler): Response {
        $startTime = microtime(true);
        
        // Log incoming request
        DebugLogger::logRequest($request);
        
        // Process request
        $response = $handler->handle($request);
        
        // Calculate execution time
        $executionTime = microtime(true) - $startTime;
        
        // Log response
        DebugLogger::logResponse($response, $response->getStatusCode());
        DebugLogger::log('Request processed', [
            'execution_time' => $executionTime . 's',
            'memory_usage' => memory_get_usage(true) . ' bytes',
            'peak_memory' => memory_get_peak_usage(true) . ' bytes'
        ]);
        
        // Add debug headers in development
        if (getenv('APP_ENV') !== 'production') {
            $response = $response
                ->withHeader('X-Debug-Time', $executionTime . 's')
                ->withHeader('X-Debug-Memory', memory_get_usage(true) . ' bytes');
        }
        
        return $response;
    }
}