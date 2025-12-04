<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        
        $isProduction = getenv('APP_ENV') === 'production';
        
        // Only add HSTS in production (requires HTTPS)
        if ($isProduction) {
            $response = $response->withHeader(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }
        
        // Prevent clickjacking - don't allow site to be embedded in iframes
        $response = $response->withHeader('X-Frame-Options', 'DENY');
        
        // Prevent MIME-sniffing
        $response = $response->withHeader('X-Content-Type-Options', 'nosniff');
        
        // Content Security Policy - strict for API
        $response = $response->withHeader(
            'Content-Security-Policy',
            "default-src 'none'; frame-ancestors 'none'"
        );
        
        // XSS Protection (legacy, but doesn't hurt)
        $response = $response->withHeader('X-XSS-Protection', '1; mode=block');
        
        // Referrer Policy - don't leak referrer information
        $response = $response->withHeader('Referrer-Policy', 'no-referrer');
        
        // Permissions Policy - disable unnecessary browser features
        $response = $response->withHeader(
            'Permissions-Policy',
            'geolocation=(), microphone=(), camera=()'
        );
        
        return $response;
    }
}
