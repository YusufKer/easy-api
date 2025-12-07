# Logging Guide

## Overview

This project uses **Monolog** for structured JSON logging. All logs are written in JSON format to separate files based on log type, with automatic daily rotation.

## Log Files

Logs are stored in the `/logs` directory:

- **app.log** - General application logs (all levels)
- **access.log** - API access/request logs
- **error.log** - Error and critical messages
- **security.log** - Security events (auth, login, logout, etc.)

### Log Rotation

- Logs rotate **daily**
- **14 days** of logs are retained
- Old logs are automatically deleted

## Using the Logger Service

### Basic Usage

The `Logger` service is available via dependency injection:

```php
use App\Services\Logger;

class MyController {
    private Logger $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function myMethod() {
        $this->logger->info('Something happened', [
            'user_id' => 123,
            'action' => 'created'
        ]);
    }
}
```

### Log Levels

```php
// DEBUG - Detailed debugging information
$logger->debug('Variable value', ['var' => $value]);

// INFO - Informational messages
$logger->info('User action completed', ['user_id' => 123]);

// WARNING - Warning messages
$logger->warning('Deprecated method used', ['method' => 'oldMethod']);

// ERROR - Error messages
$logger->error('Database query failed', ['query' => $sql]);

// CRITICAL - Critical conditions
$logger->critical('System is down', ['component' => 'database']);
```

### Specialized Log Methods

```php
// ACCESS - Log API requests/responses
$logger->access('API Request', [
    'method' => 'POST',
    'uri' => '/api/users',
    'status' => 200
]);

// SECURITY - Log security events
$logger->security('Login attempt failed', [
    'email' => 'user@example.com',
    'ip' => '192.168.1.1'
]);

// EXCEPTION - Log exceptions with full context
try {
    // code...
} catch (\Exception $e) {
    $logger->exception($e, ['additional' => 'context']);
}
```

## Log Format

All logs are written in JSON format with the following structure:

```json
{
  "message": "User login successful",
  "context": {
    "email": "user@example.com",
    "user_id": 123
  },
  "level": 200,
  "level_name": "INFO",
  "channel": "security",
  "datetime": "2025-12-05T10:30:45.123456+00:00",
  "extra": {
    "uid": "abc123def456",
    "url": "https://api.example.com/auth/login",
    "ip": "192.168.1.1",
    "http_method": "POST",
    "server": "api.example.com",
    "referrer": null,
    "file": "/path/to/AuthController.php",
    "line": 89,
    "class": "App\\Controllers\\AuthController",
    "function": "login"
  }
}
```

### Key Fields

- **message** - The log message
- **context** - Custom context data you provide
- **level_name** - DEBUG, INFO, WARNING, ERROR, CRITICAL
- **channel** - Log channel (app, access, error, security)
- **extra.uid** - Unique ID for this log entry
- **extra.url** - Request URL (web requests only)
- **extra.ip** - Client IP address (web requests only)
- **extra.file/line/class/function** - Code location where log was created

## Best Practices

### 1. Use Appropriate Log Levels

```php
// ✅ Good - Use INFO for normal operations
$logger->info('User registered', ['user_id' => $user->id]);

// ❌ Bad - Don't use ERROR for expected conditions
$logger->error('User already exists'); // This is a validation error, use WARNING
```

### 2. Include Relevant Context

```php
// ✅ Good - Include context to help debugging
$logger->error('Payment failed', [
    'user_id' => $userId,
    'amount' => $amount,
    'payment_method' => $method,
    'error_code' => $errorCode
]);

// ❌ Bad - Not enough context
$logger->error('Payment failed');
```

### 3. Don't Log Sensitive Data

```php
// ❌ Bad - Never log passwords, tokens, or sensitive data
$logger->info('User login', [
    'email' => $email,
    'password' => $password  // NEVER DO THIS
]);

// ✅ Good - Omit sensitive fields
$logger->security('User login successful', [
    'user_id' => $user->id,
    'email' => $user->email
]);
```

### 4. Use Security Logger for Auth Events

```php
// Login attempts
$logger->security('Login successful', ['user_id' => $userId, 'email' => $email]);
$logger->security('Login failed', ['email' => $email, 'reason' => 'invalid_password']);

// Registration
$logger->security('User registered', ['user_id' => $userId, 'email' => $email]);

// Token operations
$logger->security('Token refreshed', ['user_id' => $userId]);
$logger->security('User logged out', ['user_id' => $userId]);

// API key operations
$logger->security('API key generated', ['user_id' => $userId]);
```

### 5. Use Access Logger for Requests

```php
// Log all API requests and responses
$logger->access('API Request', [
    'method' => $request->getMethod(),
    'uri' => $request->getUri(),
    'status' => $response->getStatusCode(),
    'execution_time' => $executionTime
]);
```

### 6. Use Exception Logger for Errors

```php
try {
    // risky operation
    $result = $service->doSomething();
} catch (\PDOException $e) {
    // Logs full exception details including stack trace
    $logger->exception($e, [
        'operation' => 'database_query',
        'query' => $sql
    ]);

    // Re-throw or handle as needed
    throw $e;
}
```

## Integration Examples

### Controller Example

```php
namespace App\Controllers;

use App\Services\Logger;

class UserController {
    private Logger $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function create(Request $request, Response $response) {
        try {
            $data = $request->getParsedBody();

            $this->logger->info('Creating user', [
                'email' => $data['email']
            ]);

            $user = $this->userService->create($data);

            $this->logger->info('User created successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return $response->withJson(['success' => true]);

        } catch (\Exception $e) {
            $this->logger->exception($e, [
                'operation' => 'user_creation',
                'email' => $data['email'] ?? 'unknown'
            ]);

            return $response->withStatus(500)->withJson([
                'success' => false,
                'error' => 'Failed to create user'
            ]);
        }
    }
}
```

### Middleware Example

```php
namespace App\Middleware;

use App\Services\Logger;

class RequestLoggingMiddleware {
    private Logger $logger;

    public function __construct(Logger $logger) {
        $this->logger = $logger;
    }

    public function __invoke($request, $handler) {
        $startTime = microtime(true);

        // Log request
        $this->logger->access('Incoming request', [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri()
        ]);

        $response = $handler->handle($request);

        $executionTime = microtime(true) - $startTime;

        // Log response
        $this->logger->access('Request completed', [
            'status' => $response->getStatusCode(),
            'execution_time' => round($executionTime, 4)
        ]);

        return $response;
    }
}
```

## Viewing Logs

### View All Application Logs

```bash
tail -f logs/app.log | jq
```

### View Access Logs

```bash
tail -f logs/access.log | jq
```

### View Error Logs

```bash
tail -f logs/error.log | jq
```

### View Security Logs

```bash
tail -f logs/security.log | jq
```

### Search Logs

```bash
# Find all ERROR level logs
jq 'select(.level_name == "ERROR")' logs/app-*.log

# Find logs for specific user
jq 'select(.context.user_id == 123)' logs/*.log

# Find logs within time range
jq 'select(.datetime >= "2025-12-05T10:00:00")' logs/app.log
```

## Production Considerations

1. **Log Shipping**: Consider shipping logs to a centralized service (e.g., ELK Stack, Splunk, CloudWatch)
2. **Monitoring**: Set up alerts for ERROR and CRITICAL level logs
3. **Retention**: Adjust retention period based on compliance requirements
4. **Performance**: Monolog is optimized for performance, but avoid excessive logging in hot paths
5. **Storage**: Monitor disk usage and adjust rotation settings as needed

## Advanced Configuration

To customize the logger, edit `src/Services/Logger.php`:

```php
// Change retention period (default: 14 days)
$handler = new RotatingFileHandler($filePath, 30, $level); // Keep 30 days

// Change minimum log level
$handler = new RotatingFileHandler($filePath, 14, MonologLogger::WARNING); // Only WARNING and above

// Add additional processors
$logger->pushProcessor(new GitProcessor()); // Add git commit info
$logger->pushProcessor(new MemoryUsageProcessor()); // Add memory usage
```

## Troubleshooting

### Logs Not Being Created

1. Check that `/logs` directory exists and is writable
2. Verify Logger is properly injected via DI container
3. Check file permissions (should be 0755 for directory, 0644 for files)

### Log File Too Large

1. Check rotation settings in `Logger.php`
2. Reduce log retention period
3. Increase rotation frequency

### Missing Context Information

1. Ensure processors are properly configured
2. Check that you're passing context array to log methods
3. Verify web processors are working for HTTP requests

## Migration from error_log()

The old `error_log()` calls have been replaced with the structured Logger:

```php
// Old way ❌
error_log('Something happened');

// New way ✅
$logger->error('Something happened', ['context' => 'data']);

// Old way ❌
error_log('User login: ' . $email);

// New way ✅
$logger->security('User login', ['email' => $email]);
```
