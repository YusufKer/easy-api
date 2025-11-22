<?php

namespace App\Core;

use App\Core\Middleware\MiddlewarePipeline;

class Router {
    private $routes = [];
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function get($path, $controller, $method) {
        return $this->addRoute('GET', $path, $controller, $method);
    }

    public function post($path, $controller, $method) {
        return $this->addRoute('POST', $path, $controller, $method);
    }

    public function put($path, $controller, $method) {
        return $this->addRoute('PUT', $path, $controller, $method);
    }

    public function delete($path, $controller, $method) {
        return $this->addRoute('DELETE', $path, $controller, $method);
    }

    private function addRoute($httpMethod, $path, $controller, $method) {
        $route = new Route($httpMethod, $path, $controller, $method);
        $this->routes[] = $route;
        return $route;
    }

    public function dispatch() {
        $request = new Request();

        foreach ($this->routes as $route) {
            if ($route->matches($request)) {
                $route->execute($request, $this->db);
                return;
            }
        }

        // 404 Not Found
        Response::notFound('Route not found', [
            'uri' => $request->uri(),
            'method' => $request->method()
        ]);
    }
}

class Route {
    private $httpMethod;
    private $path;
    private $controller;
    private $action;
    private $middlewares = [];

    public function __construct($httpMethod, $path, $controller, $action) {
        $this->httpMethod = $httpMethod;
        $this->path = $path;
        $this->controller = $controller;
        $this->action = $action;
    }

    public function middleware(array $middlewares): self {
        $this->middlewares = array_merge($this->middlewares, $middlewares);
        return $this;
    }

    public function matches(Request $request): bool {
        if ($this->httpMethod !== $request->method()) {
            return false;
        }

        $params = $this->extractParams($request->uri());
        return $params !== false;
    }

    public function execute(Request $request, $db) {
        $params = $this->extractParams($request->uri());
        
        $pipeline = new MiddlewarePipeline();
        
        // Add route-specific middlewares
        foreach ($this->middlewares as $middlewareClass) {
            $pipeline->pipe(new $middlewareClass());
        }
        
        // The final destination: execute the controller
        $destination = function($request) use ($db, $params) {
            $controller = new $this->controller($db);
            $controller->{$this->action}(...$params);
        };
        
        $pipeline->process($request, $destination);
    }

    private function extractParams($requestUri) {
        // Convert route pattern to regex
        $pattern = preg_replace('/:([a-zA-Z0-9_]+)/', '([^/]+)', $this->path);
        $pattern = '#^' . $pattern . '$#';
        
        if (preg_match($pattern, $requestUri, $matches)) {
            array_shift($matches); // Remove full match
            return $matches;
        }
        
        return false;
    }
}
