# Refactoring to Slim Framework - Complete Guide

This document shows exactly what refactoring your API to use Slim would entail.

## Step 1: Update composer.json

**Current:**

```json
{
  "require": {
    "vlucas/phpdotenv": "^5.6"
  },
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  }
}
```

**With Slim:**

```json
{
  "require": {
    "slim/slim": "^4.12",
    "slim/psr7": "^1.6",
    "vlucas/phpdotenv": "^5.6",
    "php-di/php-di": "^7.0"
  },
  "autoload": {
    "psr-4": {
      "App\\": "src/"
    }
  }
}
```

Then run: `composer update`

---

## Step 2: New index.php (Entry Point)

**Current:** Uses custom Router, Request, Response classes

**With Slim:**

```php
<?php

use Slim\Factory\AppFactory;
use App\Middleware\CorsMiddleware;
use DI\Container;

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/config.php';

// Create DI Container
$container = new Container();

// Add database to container
$container->set('db', function() {
    return getDbConnection();
});

// Create Slim App with container
AppFactory::setContainer($container);
$app = AppFactory::create();

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add CORS middleware globally
$app->add(new CorsMiddleware(['http://localhost:5173']));

// Load routes
require __DIR__ . '/routes/api.php';

// Run app
$app->run();
```

**Key Changes:**

- No more custom Router - Slim handles it
- Dependency injection container for database
- Built-in error handling
- PSR-7 request/response objects

---

## Step 3: Routes (routes/api.php)

**Current:** Uses custom router methods

**With Slim:**

```php
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
```

**Key Changes:**

- Route groups for cleaner organization
- `{id}` instead of `:id` for parameters
- Middleware applied to entire group
- More concise syntax

---

## Step 4: Controllers

**Current:** Custom Request/Response, manual dependency injection

**With Slim:**

```php
<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ProteinController {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function index(Request $request, Response $response): Response {
        $query = "SELECT id, name FROM protein";
        $stmt = $this->db->query($query);
        $proteins = $stmt->fetchAll();

        $payload = [
            'success' => true,
            'message' => 'Protein data retrieved successfully',
            'data' => $proteins,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function getById(Request $request, Response $response, array $args): Response {
        $id = $args['id'];

        $get_protein = "SELECT id, name FROM protein WHERE id = ?";
        $protein_stmt = $this->db->prepare($get_protein);
        $protein_stmt->execute([$id]);
        $protein = $protein_stmt->fetch();

        if (!$protein) {
            $payload = [
                'success' => false,
                'error' => 'Protein not found',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        // Get cuts
        $get_cuts = "SELECT c.id, c.name, pc.price FROM cut c
                     JOIN protein_cut pc ON c.id = pc.cut_id
                     WHERE pc.protein_id = ?";
        $cuts_stmt = $this->db->prepare($get_cuts);
        $cuts_stmt->execute([$id]);
        $cuts = $cuts_stmt->fetchAll();

        // Get flavours
        $get_flavours = "SELECT f.id, f.name, pf.price FROM flavour f
                         JOIN protein_flavour pf ON f.id = pf.flavour_id
                         WHERE pf.protein_id = ?";
        $flavours_stmt = $this->db->prepare($get_flavours);
        $flavours_stmt->execute([$id]);
        $flavours = $flavours_stmt->fetchAll();

        $protein['cuts'] = $cuts;
        $protein['flavours'] = $flavours;

        $payload = [
            'success' => true,
            'message' => 'Protein retrieved successfully',
            'data' => $protein,
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function addProtein(Request $request, Response $response): Response {
        $data = $request->getParsedBody();

        // Validation would go here

        $query = "INSERT INTO protein (name) VALUES (?)";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$data['name']]);

        $payload = [
            'success' => true,
            'message' => 'Protein added successfully',
            'data' => ['id' => $this->db->lastInsertId()],
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode($payload));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }

    public function deleteProtein(Request $request, Response $response, array $args): Response {
        $id = $args['id'];

        $query = "DELETE FROM protein WHERE id = ?";
        $stmt = $this->db->prepare($query);
        $stmt->execute([$id]);

        $payload = [
            'success' => true,
            'message' => 'Protein deleted successfully',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
```

**Key Changes:**

- Methods receive PSR-7 `Request` and `Response` objects
- Return `Response` object instead of calling `Response::success()`
- Route parameters in `$args` array
- Must explicitly write to response body
- Immutable responses (use `withHeader()`, `withStatus()`)
- Dependency injection via constructor

---

## Step 5: Middleware

**Current:** Custom MiddlewareInterface

**With Slim (PSR-15):**

```php
<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware implements MiddlewareInterface {

    public function process(Request $request, RequestHandler $handler): Response {
        // Example auth logic
        $token = $request->getHeaderLine('Authorization');

        if (empty($token)) {
            $response = new SlimResponse();
            $payload = [
                'success' => false,
                'error' => 'Missing authentication token',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            $response->getBody()->write(json_encode($payload));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        // If valid, add user to request attributes
        // $request = $request->withAttribute('user', $user);

        // Continue to next middleware/controller
        return $handler->handle($request);
    }
}
```

**CORS Middleware:**

```php
<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;

class CorsMiddleware implements MiddlewareInterface {
    private array $allowedOrigins;

    public function __construct(array $allowedOrigins = ['*']) {
        $this->allowedOrigins = $allowedOrigins;
    }

    public function process(Request $request, RequestHandler $handler): Response {
        $response = $handler->handle($request);

        $origin = $request->getHeaderLine('Origin');

        if (in_array('*', $this->allowedOrigins) || in_array($origin, $this->allowedOrigins)) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $origin ?: '*')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
                ->withHeader('Access-Control-Max-Age', '3600');
        }

        return $response;
    }
}
```

**Key Changes:**

- Implements PSR-15 `MiddlewareInterface`
- Uses `process()` method instead of `handle()`
- Receives `RequestHandler` instead of `callable`
- Must return PSR-7 Response

---

## What You Can Delete

After refactoring, you can remove:

1. **src/Core/Router.php** - Replaced by Slim's router
2. **src/Core/Request.php** - Replaced by PSR-7 ServerRequest
3. **src/Core/Response.php** - Replaced by PSR-7 Response
4. **src/Core/Middleware/MiddlewareInterface.php** - Use PSR-15
5. **src/Core/Middleware/MiddlewarePipeline.php** - Slim handles this

Keep:

- **src/Core/Validator.php** - Still useful for validation
- **src/Controllers/** - Updated to use PSR-7
- **config.php** - Database configuration

---

## Migration Effort Summary

### Files to Modify: ~6-8 files

- `composer.json`
- `index.php`
- `routes/api.php`
- All controllers (3 files)
- All middleware (2 files)

### Files to Delete: ~5 files

- Core routing and request/response classes

### Time Estimate:

- **Small project (like yours):** 2-4 hours
- **Medium project:** 1-2 days
- **Large project:** 3-5 days

---

## Pros of Refactoring

✅ **PSR Standards** - Industry-standard interfaces
✅ **Dependency Injection** - Better testability
✅ **Ecosystem** - Access to Slim/PSR middleware
✅ **Documentation** - Extensive Slim docs & community
✅ **Error Handling** - Built-in error middleware
✅ **Testing** - Easier to write unit/integration tests
✅ **Maintainability** - Less custom code to maintain

## Cons of Refactoring

❌ **Learning Curve** - New patterns to learn (PSR-7/15)
❌ **Verbosity** - More code in controllers (explicit response handling)
❌ **Dependencies** - More packages to manage
❌ **Breaking Changes** - Complete rewrite of request/response handling

---

## Recommendation

For your project size, the refactor is **worthwhile if**:

1. You plan to scale this API
2. You want to add third-party middleware (auth, caching, rate limiting)
3. You need better testing infrastructure
4. Other developers will work on this

**Not worthwhile if**:

1. This is a learning/personal project
2. You're satisfied with current functionality
3. You prefer minimal dependencies
4. You want to deeply understand the internals

---

## Alternative: Hybrid Approach

Instead of full refactor, you could:

1. **Add PSR-7 compatibility** to your existing classes
2. **Keep your router** but adopt PSR-15 middleware standard
3. **Gradually migrate** one controller at a time
4. **Use Slim components** (like PSR-7) without full framework

This gives you 70% of benefits with 30% of the work.
