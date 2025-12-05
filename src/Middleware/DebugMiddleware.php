<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use App\Services\Logger;

class DebugMiddleware implements MiddlewareInterface {
    
    private Logger $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }
    
    public function process(Request $request, RequestHandler $handler): Response {
        $startTime = microtime(true);
        
        // Log incoming request
        $this->logger->access('API Request', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'path' => $request->getUri()->getPath(),
            'query' => $request->getQueryParams(),
            'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        // Process request
        $response = $handler->handle($request);
        
        // Calculate execution time
        $executionTime = microtime(true) - $startTime;
        
        // Log response
        $this->logger->access('API Response', [
            'status' => $response->getStatusCode(),
            'execution_time' => round($executionTime, 4),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
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