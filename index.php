<?php 

    require_once 'config.php';
    require_once 'core/Router.php';
    require_once 'controllers/ProteinController.php';
    require_once 'controllers/FlavoursController.php';
    require_once 'controllers/CutsController.php';

    // Set response header to JSON
    header('Content-Type: application/json');
    
    // Get database connection
    $db = getDbConnection();

    // Initialize router
    $router = new Router($db);

    // Load route definitions
    require_once 'routes/api.php';

    // Dispatch the request
    $router->dispatch();