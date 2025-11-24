<?php

use App\Controllers\ProteinController;
use App\Controllers\FlavoursController;
use App\Controllers\CutsController;
use App\Middleware\AuthMiddleware;

// Define all API routes here

// Protected routes - require authentication
$router->get('/api/protein', ProteinController::class, 'index')
       ->middleware([AuthMiddleware::class]);

$router->get('/api/protein/:id', ProteinController::class, 'getById')
       ->middleware([AuthMiddleware::class]);

$router->post('/api/protein', ProteinController::class, 'addProtein')
       ->middleware([AuthMiddleware::class]);

$router->delete('/api/protein/:id', ProteinController::class, 'deleteProtein')
       ->middleware([AuthMiddleware::class]);

// Public routes - no authentication required
$router->get('/api/flavours', FlavoursController::class, 'index');
$router->get('/api/cuts', CutsController::class, 'index');
