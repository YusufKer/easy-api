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
            if ($route['method'] === $requestMethod) {
                $params = $this->matchRoute($route['path'], $requestUri);
                
                if ($params !== false) {
                    $controllerName = $route['controller'];
                    $action = $route['action'];
                    
                    $controller = new $controllerName($this->db);
                    $controller->$action(...$params);
                    return;
                }
            }
        }

        // 404 Not Found
        Response::notFound('Route not found', [
            'uri' => $requestUri,
            'method' => $requestMethod
        ]);
    }

    private function matchRoute($routePath, $requestUri) {
        // Convert route pattern to regex
        $pattern = preg_replace('/:([a-zA-Z0-9_]+)/', '([^/]+)', $routePath);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $requestUri, $matches)) {
            array_shift($matches); // Remove full match
            return $matches;
        }
        
        return false;
    }
}
