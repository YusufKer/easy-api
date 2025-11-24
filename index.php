<?php 

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';

use App\Core\Router;
use App\Core\Request;
use App\Middleware\CorsMiddleware;

// Handle CORS first
$corsMiddleware = new CorsMiddleware(['http://localhost:5173']);
$request = new Request();
$corsMiddleware->handle($request, function($req) {
    return null; // Continue processing
});

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