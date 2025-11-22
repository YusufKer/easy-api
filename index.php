<?php 

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use App\Core\Router;

// Set response header to JSON
header('Content-Type: application/json');

// Get database connection
$db = getDbConnection();

// Initialize router
$router = new Router($db);

// Load route definitions
require_once __DIR__ . '/routes/api.php';

// Dispatch the request
$router->dispatch();