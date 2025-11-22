<?php

use App\Controllers\ProteinController;
use App\Controllers\FlavoursController;
use App\Controllers\CutsController;

// Define all API routes here
$router->get('/api/protein', ProteinController::class, 'index');
$router->get('/api/protein/:id', ProteinController::class, 'getById');
$router->post('/api/protein', ProteinController::class, 'addProtein');
$router->get('/api/flavours', FlavoursController::class, 'index');
$router->get('/api/cuts', CutsController::class, 'index');
