<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response as SlimResponse;

class CorsMiddleware implements MiddlewareInterface {
    
    private array $allowedOrigins;
    private array $allowedMethods;
    private array $allowedHeaders;
    private bool $allowCredentials;
    private int $maxAge;
    
    public function __construct(
        array $allowedOrigins = ['*'],
        array $allowedMethods = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
        array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With'],
        bool $allowCredentials = true,
        int $maxAge = 86400
    ) {
        $this->allowedOrigins = $allowedOrigins;
        $this->allowedMethods = $allowedMethods;
        $this->allowedHeaders = $allowedHeaders;
        $this->allowCredentials = $allowCredentials;
        $this->maxAge = $maxAge;
    }
    
    public function process(Request $request, RequestHandler $handler): Response {
        $origin = $request->getHeaderLine('Origin');
        
        // Handle preflight OPTIONS request
        if ($request->getMethod() === 'OPTIONS') {
            $response = new SlimResponse();
            return $this->addCorsHeaders($response, $origin)->withStatus(204);
        }
        
        // Process request and add CORS headers to response
        $response = $handler->handle($request);
        return $this->addCorsHeaders($response, $origin);
    }
    
    private function addCorsHeaders(Response $response, string $origin): Response {
        // Set CORS headers
        if ($this->isOriginAllowed($origin)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        } elseif (in_array('*', $this->allowedOrigins)) {
            $response = $response->withHeader('Access-Control-Allow-Origin', '*');
        }
        
        if ($this->allowCredentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }
        
        $response = $response
            ->withHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods))
            ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders))
            ->withHeader('Access-Control-Max-Age', (string)$this->maxAge);
        
        return $response;
    }
    
    private function isOriginAllowed(string $origin): bool {
        if (empty($origin)) {
            return false;
        }
        
        return in_array($origin, $this->allowedOrigins);
    }
}
