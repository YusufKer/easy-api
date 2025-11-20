<?php

class Router {
    private $routes = [];
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function get($path, $controller, $method) {
        $this->addRoute('GET', $path, $controller, $method);
    }

    public function post($path, $controller, $method) {
        $this->addRoute('POST', $path, $controller, $method);
    }

    public function put($path, $controller, $method) {
        $this->addRoute('PUT', $path, $controller, $method);
    }

    public function delete($path, $controller, $method) {
        $this->addRoute('DELETE', $path, $controller, $method);
    }

    private function addRoute($httpMethod, $path, $controller, $method) {
        $this->routes[] = [
            'method' => $httpMethod,
            'path' => $path,
            'controller' => $controller,
            'action' => $method
        ];
    }

    public function dispatch() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = $_SERVER['REQUEST_URI'];
        
        // Remove query string from URI
        $requestUri = strtok($requestUri, '?');

        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod && $route['path'] === $requestUri) {
                $controllerName = $route['controller'];
                $action = $route['action'];
                
                $controller = new $controllerName($this->db);
                $controller->$action();
                return;
            }
        }

        // 404 Not Found
        http_response_code(404);
        echo json_encode([
            'error' => 'Route not found',
            'uri' => $requestUri,
            'method' => $requestMethod
        ]);
    }
}
