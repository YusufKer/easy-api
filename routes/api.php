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
    $group->get('/protein/{protein_id}', [ProteinController::class, 'getById']);
    $group->post('/protein', [ProteinController::class, 'addProtein']);
    $group->delete('/protein/{protein_id}', [ProteinController::class, 'deleteProtein']);
    $group->post('/protein/{protein_id}/flavours', [ProteinController::class, 'addFlavourToProtein']);
    $group->post('/protein/{protein_id}/cuts', [ProteinController::class, 'addCutToProtein']);
    
    // Cuts routes
    $group->get('/cuts', [CutsController::class, 'index']);
    $group->post('/cuts', [CutsController::class, 'addCut']);
    $group->delete('/cuts/{cut_id}', [CutsController::class, 'deleteCut']);
    
    // Flavours routes
    $group->get('/flavours', [FlavoursController::class, 'index']);
    $group->post('/flavours', [FlavoursController::class, 'addFlavour']);
    $group->delete('/flavours/{flavour_id}', [FlavoursController::class, 'deleteFlavour']);
    
})->add(AuthMiddleware::class);