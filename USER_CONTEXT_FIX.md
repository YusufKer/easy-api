# User Context - Before & After

## The Problem ❌

**Before the fix:**

```php
// In ProteinController.php
$this->logger->audit('Price updated', [
    'action' => 'update_price',
    'user_id' => $request->getAttribute('user_id') ?? null  // ❌ Always null!
]);
```

**Why it was null:**

- AuthMiddleware decoded the JWT token
- But only attached `'user'` attribute, not `'user_id'`
- Controllers looked for `'user_id'` which didn't exist

## The Solution ✅

**After the fix:**

### 1. Updated AuthMiddleware

```php
// src/Middleware/AuthMiddleware.php
$request = $request->withAttribute('user', $safeUser)
                   ->withAttribute('user_id', $user['id']);  // ✅ Now set!
```

### 2. Now Works in Controllers

```php
// In any controller
$userId = $request->getAttribute('user_id');  // ✅ Returns actual ID: 123

$this->logger->audit('Price updated', [
    'action' => 'update_price',
    'user_id' => $userId,  // ✅ Now has value: 123
]);
```

## What Changed

### AuthMiddleware.php

- ✅ Now sets both `'user'` and `'user_id'` attributes
- ✅ Logs successful authentication to security log
- ✅ Works for both JWT and API key authentication

### All Controllers (ProteinController, etc.)

- ✅ Can access `$request->getAttribute('user_id')`
- ✅ Can access `$request->getAttribute('user')` for full user object
- ✅ Audit logs now show who made changes

## Quick Test

```bash
# 1. Login
curl -X POST http://localhost/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password"}' \
  | jq -r '.data.access_token' > token.txt

# 2. Make authenticated request
curl -H "Authorization: Bearer $(cat token.txt)" \
  http://localhost/auth/me

# Should return:
# {
#   "user": {
#     "id": 123,
#     "email": "test@example.com",
#     "role": "admin"
#   }
# }

# 3. Update a price (triggers audit log)
curl -X PUT http://localhost/api/protein/1/flavours/2 \
  -H "Authorization: Bearer $(cat token.txt)" \
  -H "Content-Type: application/json" \
  -d '{"price": 39.99}'

# 4. Check audit log
grep "AUDIT.*price updated" logs/app-$(date +%Y-%m-%d).log | tail -1

# Should show:
# {
#   "message": "AUDIT: Flavour price updated",
#   "context": {
#     "user_id": "123",  // ✅ Now populated!
#     ...
#   }
# }
```

## Benefits

✅ **Complete audit trail** - Know exactly who made each change  
✅ **Security compliance** - Meet SOC 2, GDPR audit requirements  
✅ **Better debugging** - "Who changed this price?" is now answered  
✅ **User accountability** - Actions are tied to specific users
