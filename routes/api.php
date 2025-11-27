<?php

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\ProteinController;
use App\Controllers\FlavoursController;
use App\Controllers\CutsController;
use App\Middleware\AuthMiddleware;

// Group all API routes with /api prefix and auth middleware
$app->group('/api', function (RouteCollectorProxy $group) {
    
    // Protein routes
    $group->get('/protein', [ProteinController::class, 'index']);
    $group->get('/protein/{id}', [ProteinController::class, 'getById']);
    $group->post('/protein', [ProteinController::class, 'addProtein']);
    $group->delete('/protein/{id}', [ProteinController::class, 'deleteProtein']);
    
    // Cuts routes
    $group->get('/cuts', [CutsController::class, 'index']);
    $group->post('/cuts', [CutsController::class, 'addCut']);
    $group->delete('/cuts/{id}', [CutsController::class, 'deleteCut']);
    
    // Flavours routes
    $group->get('/flavours', [FlavoursController::class, 'index']);
    $group->post('/flavours', [FlavoursController::class, 'addFlavour']);
    $group->delete('/flavours/{id}', [FlavoursController::class, 'deleteFlavour']);
    
})->add(AuthMiddleware::class);