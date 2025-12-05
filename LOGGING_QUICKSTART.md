# Structured Logging - Quick Reference

## What Changed?

✅ **Monolog** is now installed for professional structured logging  
✅ All logs are in **JSON format** for easy parsing  
✅ Logs automatically **rotate daily** (14 days retention)  
✅ **4 separate log files** for different purposes  
✅ Logger integrated into **AuthController**, **AuthMiddleware**, and **DebugMiddleware**

## Log Files (in `/logs` directory)

| File                      | Purpose                  | When to Use                  |
| ------------------------- | ------------------------ | ---------------------------- |
| `app-YYYY-MM-DD.log`      | General application logs | Info, debug, warnings        |
| `access-YYYY-MM-DD.log`   | API requests/responses   | Every API call               |
| `error-YYYY-MM-DD.log`    | Errors and exceptions    | Errors, critical issues      |
| `security-YYYY-MM-DD.log` | Security events          | Login, logout, auth failures |

## How to Use

### 1. Inject Logger in Your Controller

```php
use App\Services\Logger;

class MyController {
    private Logger $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }
}
```

### 2. Register in DI Container (in `index.php`)

```php
$container->set(MyController::class, function($c) {
    return new MyController($c->get(Logger::class));
});
```

### 3. Use Logger Methods

```php
// General info
$this->logger->info('User created', ['user_id' => 123]);

// Warnings
$this->logger->warning('Deprecated feature used', ['feature' => 'old_api']);

// Errors
$this->logger->error('Database query failed', ['query' => $sql]);

// Security events
$this->logger->security('Login failed', ['email' => $email]);

// API access
$this->logger->access('API Request', [
    'method' => 'GET',
    'uri' => '/api/users'
]);

// Exceptions
try {
    // code
} catch (\Exception $e) {
    $this->logger->exception($e, ['context' => 'data']);
}
```

## Log Levels

| Method       | Level    | Use For                    |
| ------------ | -------- | -------------------------- |
| `debug()`    | DEBUG    | Detailed debugging info    |
| `info()`     | INFO     | Normal operations          |
| `warning()`  | WARNING  | Concerning but not errors  |
| `error()`    | ERROR    | Errors that need attention |
| `critical()` | CRITICAL | Critical failures          |

## Viewing Logs

```bash
# View live logs (pretty-printed)
tail -f logs/app-2025-12-05.log | jq

# Search for errors
jq 'select(.level_name == "ERROR")' logs/*.log

# Find logs for specific user
jq 'select(.context.user_id == 123)' logs/*.log
```

## What Was Updated?

### Files Created

- ✅ `src/Services/Logger.php` - Main logger service
- ✅ `LOGGING_GUIDE.md` - Comprehensive documentation

### Files Modified

- ✅ `composer.json` - Added monolog dependency
- ✅ `index.php` - Registered Logger in DI container
- ✅ `src/Controllers/AuthController.php` - Added security logging
- ✅ `src/Middleware/AuthMiddleware.php` - Added auth failure logging
- ✅ `src/Middleware/DebugMiddleware.php` - Switched to structured logging
- ✅ `PRODUCTION_READINESS_TODO.md` - Marked task #5 complete

## Security Best Practices

### ✅ DO Log:

- User IDs
- Email addresses
- IP addresses
- Request methods/URIs
- Error messages
- Timestamps

### ❌ DON'T Log:

- Passwords (plain or hashed)
- JWT tokens
- Refresh tokens
- API keys
- Credit card numbers
- Any PII without proper masking

## Example: Full Controller Integration

```php
<?php

namespace App\Controllers;

use App\Services\Logger;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserController {
    private Logger $logger;
    private UserService $userService;

    public function __construct(UserService $userService, Logger $logger) {
        $this->userService = $userService;
        $this->logger = $logger;
    }

    public function create(Request $request, Response $response): Response {
        try {
            $data = $request->getParsedBody();

            $this->logger->info('Creating new user', [
                'email' => $data['email']
            ]);

            $user = $this->userService->create($data);

            $this->logger->info('User created successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'data' => $user
            ]));

            return $response->withStatus(201)
                           ->withHeader('Content-Type', 'application/json');

        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'operation' => 'user_creation',
                'email' => $data['email'] ?? null
            ]);

            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Failed to create user'
            ]));

            return $response->withStatus(500)
                           ->withHeader('Content-Type', 'application/json');
        }
    }
}
```

## Next Steps

1. **Start using Logger in your new code** - Inject it in controllers/services
2. **Review LOGGING_GUIDE.md** - Full documentation with examples
3. **Monitor logs** - Use `tail -f` or set up log monitoring
4. **Consider log shipping** - Send to ELK, Splunk, CloudWatch, etc.

## Questions?

See `LOGGING_GUIDE.md` for comprehensive documentation.
