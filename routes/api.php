<?php

use Slim\Routing\RouteCollectorProxy;
use App\Controllers\ProteinController;
use App\Controllers\FlavoursController;
use App\Controllers\CutsController;
use App\Controllers\AuthController;
use App\Middleware\AuthMiddleware;

// ============================================
// PUBLIC AUTH ROUTES (no authentication)
// ============================================
$app->group('/auth', function (RouteCollectorProxy $group) {
    $group->post('/register', [AuthController::class, 'register']);
    $group->post('/login', [AuthController::class, 'login']);
    $group->post('/refresh', [AuthController::class, 'refresh']);
    $group->post('/logout', [AuthController::class, 'logout']);
});

// ============================================
// PROTECTED AUTH ROUTES (require authentication)
// ============================================
$app->group('/auth', function (RouteCollectorProxy $group) {
    $group->get('/me', [AuthController::class, 'me']);
    $group->post('/api-key', [AuthController::class, 'generateApiKey']);
})->add(AuthMiddleware::class);

// ============================================
// PROTECTED API ROUTES (require authentication)
// ============================================
// Group all API routes with /api prefix and auth middleware
$app->group('/api', function (RouteCollectorProxy $group) {
    
    // Protein routes
    $group->get('/protein', [ProteinController::class, 'index']);
    $group->get('/protein/{protein_id}', [ProteinController::class, 'getById']);
    $group->post('/protein', [ProteinController::class, 'addProtein']);
    $group->delete('/protein/{protein_id}', [ProteinController::class, 'deleteProtein']);
    $group->post('/protein/{protein_id}/flavours', [ProteinController::class, 'addFlavourToProtein']);
    $group->put('/protein/{protein_id}/flavours/{flavour_id}', [ProteinController::class, 'updateFlavourPriceForProtein']);
    $group->delete('/protein/{protein_id}/flavours', [ProteinController::class, 'removeFlavourFromProtein']);
    $group->post('/protein/{protein_id}/cuts', [ProteinController::class, 'addCutToProtein']);
    $group->delete('/protein/{protein_id}/cuts', [ProteinController::class, 'removeCutFromProtein']);
    $group->put('/protein/{protein_id}/cuts/{cut_id}', [ProteinController::class, 'updateCutPriceForProtein']);

    // Cuts routes
    $group->get('/cuts', [CutsController::class, 'index']);
    $group->post('/cuts', [CutsController::class, 'addCut']);
    $group->delete('/cuts/{cut_id}', [CutsController::class, 'deleteCut']);
    
    // Flavours routes
    $group->get('/flavours', [FlavoursController::class, 'index']);
    $group->post('/flavours', [FlavoursController::class, 'addFlavour']);
    $group->delete('/flavours/{flavour_id}', [FlavoursController::class, 'deleteFlavour']);
    
})->add(AuthMiddleware::class);