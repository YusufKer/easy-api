<?php

namespace App\Core;

class Request {
    private $headers;
    private $method;
    private $uri;
    private $queryParams;
    private $body;
    
    public function __construct() {
        $this->method = $_SERVER['REQUEST_METHOD'];
        $this->uri = strtok($_SERVER['REQUEST_URI'], '?');
        $this->queryParams = $_GET;
        $this->headers = $this->parseHeaders();
        $this->body = $this->parseBody();
    }
    
    public function method(): string {
        return $this->method;
    }
    
    public function uri(): string {
        return $this->uri;
    }
    
    public function header(string $name, $default = null) {
        $name = strtolower($name);
        return $this->headers[$name] ?? $default;
    }
    
    public function hasHeader(string $name): bool {
        return isset($this->headers[strtolower($name)]);
    }
    
    public function query(?string $key = null, $default = null) {
        if ($key === null) {
            return $this->queryParams;
        }
        return $this->queryParams[$key] ?? $default;
    }
    
    public function body(?string $key = null, $default = null) {
        if ($key === null) {
            return $this->body;
        }
        return $this->body[$key] ?? $default;
    }
    
    public function all(): array {
        return array_merge($this->queryParams, $this->body ?? []);
    }
    
    public function ip(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    private function parseHeaders(): array {
        $headers = [];
        
        foreach ($_SERVER as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $header = str_replace('_', '-', substr($key, 5));
                $headers[strtolower($header)] = $value;
            }
        }
        
        // Add content-type if present
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        
        // Add authorization if present
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers['authorization'] = $_SERVER['HTTP_AUTHORIZATION'];
        }
        
        return $headers;
    }
    
    private function parseBody() {
        if (in_array($this->method, ['POST', 'PUT', 'PATCH'])) {
            $contentType = $this->header('content-type', '');
            
            if (strpos($contentType, 'application/json') !== false) {
                return json_decode(file_get_contents('php://input'), true) ?? [];
            }
            
            return $_POST;
        }
        
        return [];
    }
}
