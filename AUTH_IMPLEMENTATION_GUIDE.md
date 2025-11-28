# Authentication Implementation Guide

## Overview

This guide walks through implementing JWT-based authentication with API key fallback for the Easy API project. We'll build a secure, stateless authentication system that follows modern security practices.

## Phase 1: Dependencies & Environment Setup

### 1.1 Install Required Packages

```bash
composer require firebase/php-jwt
composer require ramsey/uuid
```

**Why these packages?**

- `firebase/php-jwt`: The most popular and well-maintained JWT library for PHP
- `ramsey/uuid`: Generates cryptographically secure UUIDs for API keys

### 1.2 Environment Variables

Add to `.env` file:

```env
JWT_SECRET=your-256-bit-secret-key-here
JWT_EXPIRY=1800  # 30 minutes in seconds
JWT_REFRESH_EXPIRY=604800  # 7 days in seconds
```

**Security Note**: JWT_SECRET should be a strong, randomly generated key. In production, use `openssl rand -base64 32` to generate it.

## Phase 2: Database Schema

### 2.1 Create Users Table

```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    api_key VARCHAR(64) UNIQUE NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Field Explanations:**

- `email`: User's unique identifier for login
- `password_hash`: Never store plain passwords! Use PHP's `password_hash()`
- `role`: Simple role-based access control
- `api_key`: Optional API key for service-to-service auth
- `is_active`: Soft delete/disable users without losing data

### 2.2 Create Refresh Tokens Table

```sql
CREATE TABLE refresh_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_revoked BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

**Why Refresh Tokens?**

- Access tokens are short-lived (security)
- Refresh tokens allow seamless token renewal
- Can be revoked (logout, security breach)
- Prevents forced re-login every 30 minutes

## Phase 3: Core Utilities

### 3.1 JWT Helper Class (`src/Utils/JwtHelper.php`)

**Purpose**: Centralize all JWT operations - encoding, decoding, validation

**Key Methods:**

- `generateAccessToken($userId, $email, $role)`: Creates short-lived JWT
- `generateRefreshToken()`: Creates secure random refresh token
- `validateToken($token)`: Verifies and decodes JWT
- `isTokenExpired($token)`: Check token expiration

**Security Features:**

- Uses HS256 algorithm (HMAC with SHA-256)
- Includes standard JWT claims (iss, aud, iat, exp)
- Validates token signature and expiration

### 3.2 Database Connection Helper (`src/Utils/Database.php`)

**Purpose**: PDO database connection with error handling

**Why PDO?**

- Prepared statements prevent SQL injection
- Better error handling than mysqli
- More portable across databases

## Phase 4: Models & Data Layer

### 4.1 User Model (`src/Models/User.php`)

**Purpose**: Handle all user-related database operations

**Key Methods:**

- `create($email, $password, $role)`: Register new user
- `findByEmail($email)`: Login lookup
- `findById($id)`: Get user by ID
- `verifyPassword($password, $hash)`: Secure password verification
- `generateApiKey()`: Create unique API key
- `findByApiKey($key)`: API key authentication

**Security Practices:**

- Always use `password_hash()` with `PASSWORD_DEFAULT`
- Verify passwords with `password_verify()`
- Generate cryptographically secure API keys

### 4.2 RefreshToken Model (`src/Models/RefreshToken.php`)

**Purpose**: Manage refresh token lifecycle

**Key Methods:**

- `create($userId, $token, $expiresAt)`: Store new refresh token
- `findByToken($token)`: Validate refresh token
- `revokeByUserId($userId)`: Logout (revoke all user's tokens)
- `cleanupExpired()`: Remove expired tokens (maintenance)

## Phase 5: Authentication Service

### 5.1 AuthService (`src/Services/AuthService.php`)

**Purpose**: Business logic layer for authentication operations

**Key Methods:**

- `register($email, $password, $role)`: Complete user registration
- `login($email, $password)`: Authenticate and return tokens
- `refreshToken($refreshToken)`: Generate new access token
- `logout($refreshToken)`: Invalidate refresh token
- `validateApiKey($apiKey)`: API key authentication

**Why a Service Layer?**

- Separates business logic from controllers
- Easier to test in isolation
- Reusable across different endpoints
- Centralizes complex authentication logic

## Phase 6: Enhanced Middleware

### 6.1 Update AuthMiddleware (`src/Middleware/AuthMiddleware.php`)

**Current State**: Placeholder that logs and allows all requests

**Enhanced Features:**

- **Multiple Auth Methods**: JWT Bearer tokens OR API keys
- **Flexible Protection**: Optional vs required authentication
- **User Context**: Attach authenticated user to request
- **Detailed Errors**: Specific error messages for debugging

**Authentication Flow:**

1. Check for `Authorization: Bearer <jwt>` header
2. If no JWT, check for `X-API-Key` header
3. Validate token/key and extract user info
4. Attach user to request: `$request->withAttribute('user', $user)`
5. Allow access or return 401/403 error

**Error Scenarios:**

- Missing credentials → 401 Unauthorized
- Invalid/expired token → 401 Unauthorized
- Valid token but insufficient permissions → 403 Forbidden

## Phase 7: Authentication Controller

### 7.1 AuthController (`src/Controllers/AuthController.php`)

**Purpose**: Handle all authentication HTTP endpoints

**Endpoints:**

- `POST /auth/register`: User registration
- `POST /auth/login`: User authentication
- `POST /auth/refresh`: Token refresh
- `POST /auth/logout`: User logout
- `GET /auth/me`: Current user profile

**Input Validation:**

- Email format validation
- Password strength requirements
- Required field checks
- Sanitize all inputs

**Response Format:**

```json
{
    "success": true,
    "data": {
        "user": { "id": 1, "email": "user@example.com", "role": "user" },
        "accessToken": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "refreshToken": "550e8400-e29b-41d4-a716-446655440000"
    },
    "message": "Login successful"
}
```

## Phase 8: Route Integration

### 8.1 Add Auth Routes (`routes/api.php`)

```php
// Public auth routes (no middleware)
$app->group('/auth', function (RouteCollectorProxy $group) {
    $group->post('/register', [AuthController::class, 'register']);
    $group->post('/login', [AuthController::class, 'login']);
    $group->post('/refresh', [AuthController::class, 'refresh']);
});

// Protected auth routes (require auth middleware)
$app->group('/auth', function (RouteCollectorProxy $group) {
    $group->post('/logout', [AuthController::class, 'logout']);
    $group->get('/me', [AuthController::class, 'me']);
})->add(AuthMiddleware::class);
```

### 8.2 Apply AuthMiddleware to Existing Routes

Your existing API routes (`/api/protein`, etc.) should be protected:

```php
$app->group('/api', function (RouteCollectorProxy $group) {
    // All existing routes here
})->add(AuthMiddleware::class);
```

## Phase 9: Testing Strategy

### 9.1 Unit Tests

- `tests/Unit/JwtHelperTest.php`: JWT encoding/decoding
- `tests/Unit/UserModelTest.php`: User CRUD operations
- `tests/Unit/AuthServiceTest.php`: Authentication logic

### 9.2 Integration Tests

- `tests/Integration/AuthControllerTest.php`: Full auth flow
- Test scenarios: register → login → access protected route → refresh → logout

### 9.3 Manual Testing Checklist

- [ ] User registration with valid/invalid data
- [ ] Login with correct/incorrect credentials
- [ ] Access protected routes with/without token
- [ ] Token expiration and refresh
- [ ] API key authentication
- [ ] Logout and token invalidation

## Phase 10: Security Enhancements

### 10.1 Rate Limiting

Prevent brute force attacks on login endpoints:

- Max 5 login attempts per IP per minute
- Max 10 registration attempts per IP per hour

### 10.2 Password Requirements

- Minimum 8 characters
- At least one uppercase, lowercase, number
- Optional: Special characters

### 10.3 Additional Headers

```php
// Add to responses
$response = $response
    ->withHeader('X-Content-Type-Options', 'nosniff')
    ->withHeader('X-Frame-Options', 'DENY')
    ->withHeader('X-XSS-Protection', '1; mode=block');
```

## Phase 11: Documentation & Usage

### 11.1 API Documentation

Document all authentication endpoints with:

- Request/response examples
- Error codes and meanings
- Authentication header format
- Token expiration times

### 11.2 Frontend Integration Guide

```javascript
// Login example
const response = await fetch('/auth/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ email, password })
});

const { accessToken } = await response.json();
localStorage.setItem('accessToken', accessToken);

// Authenticated requests
fetch('/api/protein', {
    headers: { 'Authorization': `Bearer ${accessToken}` }
});
```

## Implementation Order (Recommended)

1. **Start**: Dependencies & environment setup
2. **Database**: Create tables and test connection
3. **JWT Helper**: Core token functionality
4. **User Model**: Basic CRUD operations
5. **AuthService**: Business logic layer
6. **AuthController**: HTTP endpoints
7. **Enhanced AuthMiddleware**: Token validation
8. **Route Integration**: Wire everything together
9. **Testing**: Verify all functionality
10. **Security**: Add rate limiting and hardening

## Key Security Principles Applied

1. **Defense in Depth**: Multiple layers of security
2. **Principle of Least Privilege**: Minimal permissions by default
3. **Fail Secure**: Deny access when in doubt
4. **Input Validation**: Sanitize all user input
5. **Secure Storage**: Hash passwords, secure JWT secrets
6. **Audit Trail**: Log authentication events
7. **Token Expiration**: Short-lived access tokens
8. **Revocation**: Ability to invalidate tokens

## Next Steps After Implementation

- Monitor authentication logs for suspicious activity
- Implement password reset functionality
- Add two-factor authentication (2FA)
- Set up automated security scanning
- Regular security audits and updates

---

**Ready to Start?** Begin with Phase 1 (Dependencies) and work through each phase systematically. Each phase builds on the previous ones, so order matters!
