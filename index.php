<?php

use Slim\Factory\AppFactory;
use App\Middleware\CorsMiddleware;
use App\Controllers\ProteinController;
use App\Controllers\FlavoursController;
use App\Controllers\CutsController;
use DI\Container;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

// Create DI Container
$container = new Container();

// Add database to container
$container->set('db', function() {
    return getDbConnection();
});

// Configure controllers with database dependency
$container->set(ProteinController::class, function($c) {
    return new ProteinController($c->get('db'));
});

$container->set(FlavoursController::class, function($c) {
    return new FlavoursController($c->get('db'));
});

$container->set(CutsController::class, function($c) {
    return new CutsController($c->get('db'));
});

// Create Slim App with container
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add CORS middleware globally
$app->add(new CorsMiddleware(['http://localhost:5173']));

// Add JSON body parsing middleware
$app->addBodyParsingMiddleware();

// Load routes
require __DIR__ . '/routes/api.php';

// Run app
$app->run();