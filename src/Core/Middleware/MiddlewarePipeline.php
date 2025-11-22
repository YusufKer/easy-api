<?php

namespace App\Core\Middleware;

use App\Core\Request;

class MiddlewarePipeline {
    private $middlewares = [];
    
    public function pipe($middleware): self {
        $this->middlewares[] = $middleware;
        return $this;
    }
    
    public function process(Request $request, callable $destination) {
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function ($next, $middleware) {
                return function ($request) use ($next, $middleware) {
                    return $middleware->handle($request, $next);
                };
            },
            $destination
        );
        
        return $pipeline($request);
    }
}
