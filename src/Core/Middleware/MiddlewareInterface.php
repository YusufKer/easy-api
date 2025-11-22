<?php

namespace App\Core\Middleware;

use App\Core\Request;

interface MiddlewareInterface {
    /**
     * Handle an incoming request
     * 
     * @param Request $request
     * @param callable $next The next middleware/controller in the pipeline
     * @return mixed
     */
    public function handle(Request $request, callable $next);
}
