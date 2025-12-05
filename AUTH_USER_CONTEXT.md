# Authentication & User Context Flow

## Overview

The AuthMiddleware now properly extracts user information from JWT tokens or API keys and makes it available to all controllers.

## How It Works

### 1. **Request Arrives**

```
Client sends request with:
Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc...
```

### 2. **AuthMiddleware Processes**

The middleware:

1. Extracts the JWT token from the `Authorization` header
2. Validates and decodes the token using `JwtHelper::validateToken()`
3. Retrieves user from database using `user_id` from token payload
4. Attaches user information to the request:
   - `$request->getAttribute('user')` - Full user object
   - `$request->getAttribute('user_id')` - User ID for convenience

### 3. **Controllers Access User Info**

Controllers can now access authenticated user:

```php
public function updatePrice(Request $request, Response $response, array $args): Response {
    // Get user ID directly
    $userId = $request->getAttribute('user_id');

    // Get full user object (id, email, role)
    $user = $request->getAttribute('user');

    // Use in audit logging
    $this->logger->audit('Price updated', [
        'action' => 'update_price',
        'old_price' => $oldPrice,
        'new_price' => $newPrice,
        'user_id' => $userId,        // ✅ Now populated!
        'user_email' => $user['email']
    ]);
}
```

## Available User Attributes

After authentication, these attributes are available:

| Attribute | Type  | Description        | Example                                              |
| --------- | ----- | ------------------ | ---------------------------------------------------- |
| `user_id` | int   | User's database ID | `123`                                                |
| `user`    | array | Full user object   | `['id' => 123, 'email' => '...', 'role' => 'admin']` |

### User Object Structure

```php
[
    'id' => 123,
    'email' => 'user@example.com',
    'role' => 'admin' // or 'user'
]
```

Note: `password_hash` is **not** included for security.

## Authentication Methods Supported

### 1. JWT Token (Bearer)

```bash
curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  http://localhost/api/protein
```

### 2. API Key

```bash
curl -H "X-API-Key: YOUR_API_KEY" \
  http://localhost/api/protein
```

Both methods populate the same user attributes.

## Security Logging

The middleware now logs all authentication attempts:

### Successful Authentication

```json
{
  "message": "JWT authentication successful",
  "context": {
    "user_id": 123,
    "email": "user@example.com",
    "method": "jwt"
  }
}
```

### Failed Authentication

```json
{
  "message": "JWT authentication failed - invalid token",
  "context": {
    "error": "Token expired"
  }
}
```

These are logged to `/logs/security-YYYY-MM-DD.log`.

## Audit Trail Example

Now that `user_id` is available, audit logs show who made changes:

```json
{
  "message": "AUDIT: Flavour price updated",
  "context": {
    "action": "update_flavour_price",
    "protein_id": "5",
    "flavour_id": "3",
    "old_price": "29.99",
    "new_price": "34.99",
    "user_id": "123",           // ✅ Actual user ID
    "timestamp": "2025-12-05 15:45:00"
  }
}
```

## Role-Based Access Control (RBAC)

You can now implement role checks in controllers:

```php
public function adminOnlyAction(Request $request, Response $response): Response {
    $user = $request->getAttribute('user');

    if ($user['role'] !== 'admin') {
        $payload = [
            'success' => false,
            'error' => 'Forbidden',
            'message' => 'Admin access required'
        ];
        $response->getBody()->write(json_encode($payload));
        return $response->withStatus(403)
                        ->withHeader('Content-Type', 'application/json');
    }

    // Admin-only logic here...
}
```

## Testing

### Get User Info Endpoint

Test that user context is working:

```bash
# Login to get token
TOKEN=$(curl -X POST http://localhost/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password"}' | jq -r '.data.access_token')

# Use token to access protected endpoint
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost/auth/me
```

### Verify Audit Logging

Make a price change and check the logs:

```bash
# Update a price
curl -X PUT http://localhost/api/protein/1/flavours/2 \
  -H "Authorization: Bearer $TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"price": 39.99}'

# Check audit log
grep "AUDIT.*price updated" logs/app-$(date +%Y-%m-%d).log | tail -1 | jq .
```

You should see the `user_id` populated in the audit log.

## Benefits

✅ **Accountability** - Every action is tied to a specific user  
✅ **Security** - Log who accessed what and when  
✅ **Debugging** - Know who made changes when things go wrong  
✅ **Compliance** - Meet audit requirements (SOC 2, GDPR, etc.)  
✅ **Analytics** - Track user behavior and usage patterns

## Next Steps

Consider adding:

1. **Rate limiting per user** - Track requests by `user_id`
2. **User activity dashboard** - Show recent actions by user
3. **Permission system** - Beyond just admin/user roles
4. **IP logging** - Add `$request->getServerParams()['REMOTE_ADDR']` to audit logs
