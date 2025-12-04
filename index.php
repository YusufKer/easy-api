<?php

use Slim\Factory\AppFactory;
use App\Middleware\CorsMiddleware;
use App\Middleware\DebugMiddleware;
use App\Middleware\AuthMiddleware;
use App\Middleware\SecurityHeadersMiddleware;
use App\Controllers\ProteinController;
use App\Controllers\FlavoursController;
use App\Controllers\CutsController;
use App\Controllers\AuthController;
use App\Models\Protein;
use App\Models\Flavour;
use App\Models\Cut;
use App\Models\User;
use App\Services\AuthService;
use App\Utils\DebugLogger;
use DI\Container;

require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require __DIR__ . '/config.php';

// Create DI Container
$container = new Container();

// Add database to container
$container->set('db', function() {
    return getDbConnection();
});

// Register Models
$container->set(Protein::class, function($c) {
    return new Protein($c->get('db'));
});

$container->set(Flavour::class, function($c) {
    return new Flavour($c->get('db'));
});

$container->set(Cut::class, function($c) {
    return new Cut($c->get('db'));
});

$container->set(User::class, function($c) {
    return new User($c->get('db'));
});

// Register Services
$container->set(AuthService::class, function($c) {
    return new AuthService($c->get('db'));
});

// Register middleware
$container->set(AuthMiddleware::class, function($c) {
    return new AuthMiddleware($c->get('db'), false); // false = authentication required
});

// Configure controllers with model dependencies
$container->set(ProteinController::class, function($c) {
    return new ProteinController(
        $c->get(Protein::class),
        $c->get(Flavour::class),
        $c->get(Cut::class)
    );
});

$container->set(FlavoursController::class, function($c) {
    return new FlavoursController($c->get(Flavour::class));
});

$container->set(CutsController::class, function($c) {
    return new CutsController($c->get(Cut::class));
});

$container->set(AuthController::class, function($c) {
    return new AuthController($c->get(AuthService::class));
});

// Create Slim App with container
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add debug middleware (only in development)
if (getenv('APP_ENV') !== 'production') {
    $app->add(new DebugMiddleware());
}

// Add routing middleware
$app->addRoutingMiddleware();

// Add error middleware with environment-based configuration
$isProduction = getenv('APP_ENV') === 'production';
$displayErrorDetails = !$isProduction;  // Hide error details in production
$logErrors = true;                      // Always log errors
$logErrorDetails = !$isProduction;      // Hide error details in logs for production

$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, $logErrors, $logErrorDetails);
$errorHandler = $errorMiddleware->getDefaultErrorHandler();
$errorHandler->forceContentType('application/json');

// Add Security Headers middleware
$app->add(new SecurityHeadersMiddleware());

// Add CORS middleware globally
$allowedOrigins = getenv('ALLOWED_ORIGINS') ? explode(',', getenv('ALLOWED_ORIGINS')) : ['*'];
$app->add(new CorsMiddleware($allowedOrigins));

// Add JSON body parsing middleware
$app->addBodyParsingMiddleware();

// Load routes
require __DIR__ . '/routes/api.php';

// Run app
$app->run();